# Acl

Role-based access control on top of [Laminas Permissions ACL](https://docs.laminas.dev/laminas-permissions-acl/).  
Provides an admin UI for roles, resources, rules, and route bindings, plus a global route interceptor for named-route authorization.

## Requirements

- PHP 8.4+
- `AuthAdmin` component (`SessionRoleResolver` reads the logged-in user and ACL role)
- Dashboard views in `resources/views/dashboard/`
- Component registered in `config/components.php`

## Installation

```bash
php bin/console.php migrate
php bin/console.php db:seed -c "Concept\Components\Acl\Database\Seeders\AclSeeder"
php bin/console.php db:seed -c "Concept\Components\Acl\Database\Seeders\AclRouteRulesSeeder"
php bin/console.php db:seed -c "Concept\Components\Acl\Database\Seeders\AssignUserAclRolesSeeder"
```

Register the component:

```php
// config/components.php
AclComponent::class => AclComponent::class,
```

Register the route interceptor (application-level, not inside the component):

```php
// config/routes.php
use Concept\Components\Acl\Authorization\AclRouteAuthorization;

return [
    'routes' => [
        'interceptors' => [
            AclRouteAuthorization::class,
        ],
        // ...
    ],
];
```

Register the access-denied handler as the **first** global middleware:

```php
// routes/web.php
use Concept\Components\Acl\Middlewares\HandleAccessDeniedMiddleware;

$router->lazyMiddleware(HandleAccessDeniedMiddleware::class);
```

## How it works

ACL in this project has **two layers** that solve different problems:

| Layer | What it checks | Where it lives |
|-------|----------------|----------------|
| **ACL matrix** | Can role X access resource Y (with optional privilege)? | `acl_roles`, `acl_resources`, `acl_rules` |
| **Route rules** | Which resource/privilege does named route `admin.users` require? | `acl_route_rules` |

At runtime:

```
HTTP request
  → HandleAccessDeniedMiddleware     (catch AccessDeniedException → JSON 403 or redirect)
  → AuthMiddleware on /admin/*       (session check → redirect to login)
  → Router match
  → AclRouteAuthorization            (route interceptor)
       route name → acl_route_rules → AclGate::isAllowed()
  → Controller
```

**Important:** do not duplicate login checks in the interceptor. `AuthMiddleware` handles “is the user logged in?”; the interceptor handles “does this role have permission for this named route?”.

## Configuration

### Runtime settings — `config/acl.php`

```php
return [
    'acl' => [
        'storage' => 'database',          // 'database' | anything else → config file
        'default_role' => 'guest',        // role when nobody is logged in
        'default_user_role' => 'user',    // fallback for logged-in users without acl_role_id
        'role_resolver' => SessionRoleResolver::class,
    ],
];
```

| Key | Description |
|-----|-------------|
| `storage` | `database` — rules from DB (default, used with admin UI). Any other value — static definitions from `config/acl-definitions.php`. |
| `default_role` | Resolved role for guests |
| `default_user_role` | Resolved role when user has no `acl_role_id` and is not admin |
| `role_resolver` | Class implementing `RoleResolverInterface` |

### Static definitions — `config/acl-definitions.php`

Used **only** when `acl.storage !== 'database'` (tests, simple deploy without DB-backed ACL).

```php
'definitions' => [
    'roles' => [
        'guest' => null,
        'user' => 'guest',
        'editor' => 'user',
        'manager' => 'editor',
        'admin' => null,
    ],
    'resources' => ['cabinet', 'admin', 'admin.users', ...],
    'allow' => [
        ['role' => 'editor', 'resource' => 'admin.users', 'privilege' => 'view'],
    ],
    'deny' => [
        ['role' => 'editor', 'resource' => 'admin.users', 'privilege' => 'delete'],
    ],
],
```

`deny` rules override `allow` (Laminas ACL semantics).

When `storage` is `database`, this file is **ignored** — manage rules via the admin panel or seeders.

## Data model

### `acl_roles`

Hierarchical roles (`parent_id` → inheritance).

### `acl_resources`

Hierarchical resources (`admin.users` is a child of `admin`).

### `acl_rules`

Allow/deny rules linking role → resource → optional privilege.

Supported privileges (`AclPrivilege` enum): `view`, `create`, `update`, `delete`.  
`null` privilege on a rule means **all** privileges for that resource.

### `acl_route_rules`

Maps a **named route** to an ACL resource (and optional privilege):

| Column | Description |
|--------|-------------|
| `route_name` | Value from `->setName('admin.users')` |
| `resource_id` | FK to `acl_resources` |
| `privilege` | Optional (`view`, `create`, `update`, `delete`); `null` = all privileges |
| `redirect_route_name` | Optional redirect target on denial (read via `getRedirectRouteName()`, not an Eloquent accessor) |

Routes **without** a row in `acl_route_rules` are not checked by the interceptor (open by default).

### `users.acl_role_id`

FK to `acl_roles`. Assigned by `AssignUserAclRolesSeeder` or user management UI (`acl_role_id`).

## PHP usage

Inject `AclInterface` (resolved as `AclGate`):

```php
use Concept\Components\Acl\Contracts\AclInterface;

public function __construct(private readonly AclInterface $acl) {}

if ($this->acl->isAllowed('admin.users', 'update')) {
    // ...
}

$role = $this->acl->role(); // current role name
```

Use in controllers, services, or policies — independent of HTTP routing.

## Route authorization

### 1. Name your routes

```php
$route->get('/admin/users', [UserController::class, 'index'])->setName('admin.users');
```

### 2. Bind route → resource

Via admin UI (`/admin/acl/route-rules`) or seeder:

```php
['route' => 'admin.users', 'resource' => 'admin.users', 'privilege' => 'view'],
['route' => 'admin.user.edit', 'resource' => 'admin.users', 'privilege' => 'update'],
```

There is **no** resource suffix like `admin.users.view` — the resource is always the entity (`admin.users`), the action is the privilege.

### 3. Interceptor enforces on every match

`AclRouteAuthorization` implements `RouteInterceptorInterface`. On denial it throws `AccessDeniedException`.

### 4. Middleware formats the response

`HandleAccessDeniedMiddleware`:

- **JSON/AJAX** → `403` with error message
- **HTML** → redirect to `admin.dashboard`

## Twig

| Function | Description |
|----------|-------------|
| `acl_allowed(resource, privilege)` | Returns `bool` — hide UI elements the user cannot access |

```twig
{% if acl_allowed('admin.users', 'update') %}
    <a href="{{ uri('admin.user.edit', {id: user.id}) }}">Edit</a>
{% endif %}

{% if acl_allowed('admin.users', 'create') %}
    <a href="{{ uri('admin.user.create') }}">Create user</a>
{% endif %}

{% if has_component('Acl') %}
    {# sidebar ACL links #}
{% endif %}
```

Always pair UI checks with route rules — Twig hides buttons; the interceptor blocks direct URL access.

### Admin sidebar

When the `Acl` component is enabled, `resources/views/dashboard/partials/_sidebar.twig` shows menu items only if the current role is allowed:

| Menu item | ACL check |
|-----------|-----------|
| Dashboard | `acl_allowed('admin')` |
| Users | `acl_allowed('admin.users', 'view')` |
| Settings | `acl_allowed('admin.settings', 'view')` |
| ACL (entire submenu) | `acl_allowed('admin.acl')` |

If `Acl` is disabled, sidebar items fall back to component flags only (`has_component(...)`).

## Admin panel

All routes are protected by `AuthMiddleware`. Access to ACL management itself requires the `admin.acl` resource.

| Area | Base path | Route prefix |
|------|-----------|--------------|
| Matrix | `/admin/acl/matrix` | `admin.acl.matrix` |
| Roles | `/admin/acl/roles` | `admin.acl.roles` |
| Resources | `/admin/acl/resources` | `admin.acl.resources` |
| Rules | `/admin/acl/rules` | `admin.acl.rules` |
| Route rules | `/admin/acl/route-rules` | `admin.acl.route-rules` |

Sidebar links appear when `has_component('Acl')` is true.

### Matrix (`/admin/acl/matrix`)

Interactive grid: **roles × resources**. Each cell shows inherited/effective access and buttons:

| Button | Action |
|--------|--------|
| **A** | Explicit allow for the selected privilege (or all privileges when no filter) |
| **D** | Explicit deny |
| **—** | Remove direct rule (inherit from parent role / parent resource) |

- **Privilege filter** — matrix column `privilege` query param; use `View` to manage read-only access separately from `update` / `delete`.
- **Role and resource headers** link to the corresponding edit forms.
- After matrix changes, `AclMatrixService` invalidates the in-request ACL cache via `AclBuilder::invalidate()`.

**Role inheritance note:** roles in the `guest` → `user` → `editor` → `manager` chain inherit parent rules (Laminas ACL). A privilege-specific `DENY` on a parent role can block descendants when routes check `privilege = null`. When saving a deny in the matrix, descendant roles that still had access receive an explicit `ALLOW` with `null` privilege. The `admin` role is a **separate root role** (no parent) and does not inherit from `manager`.

**ACL management resource:** all ACL admin routes (matrix, roles, resources, rules, route rules) map to resource `admin.acl`, not separate resources per screen. Granting `admin.acl` opens the whole ACL section.

## Default seed data

### Roles

Inheritance chain for graded access: `guest` → `user` → `editor` → `manager`.

`admin` is a **root role** (`parent_id = null`) — full admin-panel access via an explicit rule on resource `admin` (covers `admin.*` children through resource inheritance). It does **not** inherit from `manager`.

| Role | Access |
|------|--------|
| `admin` | Full admin panel (`admin` resource and children) |
| `manager` | Admin panel per explicit rules (see seeder) |
| `editor` | `admin.content` only (pages/blog — future) |
| `user` | Frontend cabinet only (`cabinet` resource) |
| `guest` | Public cabinet views |

### Example rules (from `AclSeeder`)

| Role | Resource | Notes |
|------|----------|-------|
| `manager` | `admin`, `admin.settings`, `admin.content`, `admin.users` | allow (see seeder for current set) |
| `manager` | `admin.acl` | deny |
| `editor` | `admin.content` | allow |
| `editor` | `admin`, `admin.users`, … | deny |
| `admin` | `admin` | allow (explicit; all `admin.*` via resource tree) |

### Cookbook: manager — view-only Users

1. **ACL Matrix** → filter **View** → `manager` × `admin.users` → **A** (allow).
2. Matrix → filter **Update** / **Create** / **Delete** → `manager` × `admin.users` → **D** (deny), or remove broad **Allow** on all privileges if present.
3. Route rules already map list/show to `view`; edit/update routes require `update` — no extra route rules needed.

Result: `manager@example.com` can open `/admin/users` and user show pages, not create/edit/delete.

To allow **Matrix**: manager needs `ALLOW` on `admin.acl`. Default seeder sets **DENY** on `admin.acl` for manager — change to **Allow** in the matrix if needed.

### User → role mapping (`AssignUserAclRolesSeeder`)

| Email | ACL role |
|-------|----------|
| `admin@example.com` | `admin` |
| `manager@example.com` | `manager` |
| `editor@example.com` | `editor` |
| `user@example.com` | `user` |

## Role resolution

`SessionRoleResolver` (default):

1. No user → `acl.default_role` (`guest`)
2. User with `acl_role_id` → linked `acl_roles.name`
3. Otherwise → `acl.default_user_role` (`user`)

### Admin panel vs ACL — two separate concerns

| Layer | Field / mechanism | Question |
|-------|-------------------|----------|
| **Panel entry** | `users.is_admin` | May this user open `/admin` at all? (`AuthMiddleware`) |
| **Permissions** | `users.acl_role_id` + ACL rules | What routes and actions are allowed? (route interceptor) |

`is_admin` is a per-user flag (DB + checkbox in user form). It does not depend on ACL role names or resource prefixes.

`SessionRoleResolver` reads `acl_role_id` for permission checks — independent of `is_admin`.

Implement `RoleResolverInterface` for custom logic (API tokens, multi-tenant, etc.) and set `acl.role_resolver` in config.

## Storage modes

### Database (production)

```php
'storage' => 'database',
```

- Rules edited in admin UI or via seeders
- `DatabaseAclDefinitionSource` loads roles, resources, and rules from DB (one query per table, PHP maps)
- `AclEntityLookup` caches role/resource maps per request (shared by matrix, route rules, definition source)
- `AclRouteRulesService` caches route → resource map per request
- `AclBuilder` builds Laminas ACL once per request; call `AclBuilder::invalidate()` after programmatic rule changes
- `TwigExtension` resolves `AclInterface` lazily (only when `acl_allowed()` is used)

### Config file (development / tests)

```php
'storage' => 'config',
```

- Reads `config/acl-definitions.php`
- No admin CRUD persistence (DB tables may still exist but are not used as source of truth)
- Route rules (`acl_route_rules`) still apply for the interceptor

## Architecture

```
Acl/
├── Authorization/
│   ├── AclBuilder.php              # builds Laminas ACL from definition source
│   ├── AclGate.php                 # AclInterface implementation
│   ├── AclRouteAuthorization.php   # RouteInterceptorInterface
│   └── Exceptions/
├── Contracts/
│   ├── AclInterface.php
│   ├── AclDefinitionSourceInterface.php
│   └── RoleResolverInterface.php
├── Controllers/Admin/
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── DefinitionSources/
│   ├── ConfigAclDefinitionSource.php
│   └── DatabaseAclDefinitionSource.php
├── Enums/
├── Extensions/                     # TwigExtension (acl_allowed)
├── Middlewares/
│   └── HandleAccessDeniedMiddleware.php
├── Models/
├── Providers/
│   └── AclServiceProvider.php
├── Requests/
├── RoleResolvers/
├── Services/
│   ├── AclEntityLookup.php         # shared role/resource maps per request
│   ├── AclMatrixService.php        # matrix build + setAccess
│   └── AclRouteRulesService.php
├── Support/
│   └── NamedRouteCatalog.php
└── Views/
```

## Extending

### Add a new protected admin feature

1. Create resource in admin (`admin.reports`) or seeder
2. Add allow/deny rules for relevant roles
3. Name routes with `->setName('admin.reports')`
4. Add route rule: `admin.reports` → `admin.reports`
5. Protect route group with `AuthMiddleware`
6. Optionally hide UI with `acl_allowed('admin.reports')`

### Add a route rule for a new component

In your component seeder or via admin UI — do not hardcode checks in controllers if the interceptor can handle it.

### Custom definition source

Implement `AclDefinitionSourceInterface` and register it in `AclServiceProvider` based on your own config flag.

### Invalidate caches after programmatic updates

| Change | Call |
|--------|------|
| `acl_route_rules` | `AclRouteRulesService::invalidate()` |
| `acl_rules` / matrix | `AclBuilder::invalidate()` (also clears definition source cache) |

`AclServiceProvider` registers only interfaces and factories; concrete services (`AclMatrixService`, `AclEntityLookup`, controllers, …) are autowired via `ReflectionContainer`.

## Related docs

- [Route interceptors](https://php-concept.github.io/concept-docs/en/route-interceptors.html) — interceptor + middleware pattern
- `AuthAdmin` README — `AuthMiddleware`, session auth
- `config/acl.php`, `config/acl-definitions.php` — application config
