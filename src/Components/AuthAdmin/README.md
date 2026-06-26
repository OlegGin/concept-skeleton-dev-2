# AuthAdmin

Admin authentication, dashboard shell, and user management (CRUD).

## Requirements

- PHP 8.4+
- Dashboard views in `resources/views/dashboard/`
- Component registered in `config/components.php`

## Installation

```bash
php bin/console.php migrate
php bin/console.php db:seed -c "Concept\Components\AuthAdmin\Database\Seeders\UserSeeder"
php bin/console.php component:publish-assets
```

Register the component:

```php
// config/components.php
AuthAdminComponent::class => AuthAdminComponent::class,
```

## Default users (seeder)

| Email | Password | ACL role | `is_admin` | Status |
|-------|----------|----------|------------|--------|
| `admin@example.com` | `admin_password` | admin | yes | active |
| `manager@example.com` | `manager_password` | manager | yes | active |
| `editor@example.com` | `editor_password` | editor | yes | active |
| `user@example.com` | `user_password` | user | no | active |

Run `AssignUserAclRolesSeeder` after `UserSeeder` to link users to ACL roles.

Only **active** users with `is_admin = true` can enter the admin panel (`AuthMiddleware`).  
What they can do inside is enforced by ACL (`acl_role_id` + route interceptor).

## Authentication

Session-based auth via `AuthService`:

```php
use Concept\Components\AuthAdmin\Services\AuthService;

public function __construct(private readonly AuthService $auth) {}

$this->auth->attempt($email, $password);
$user = $this->auth->user();
$this->auth->check();
$this->auth->logout();
```

`AuthMiddleware` protects route groups. It redirects guests to `admin.login` and rejects non-admin or non-active users.

Other admin components (e.g. `SettingsManager`) reuse `AuthMiddleware` on their routes.

## Twig

| Function | Description |
|----------|-------------|
| `auth()` | Returns `AuthService` |

```twig
{% set user = auth().user() %}
{% if user %}
    {{ user.name }}
{% endif %}
```

## Routes

### Public

| Method | Path | Name |
|--------|------|------|
| GET | `/admin/login` | `admin.login` |
| POST | `/admin/login` | `admin.login.submit` |

### Protected (`AuthMiddleware`)

| Method | Path | Name |
|--------|------|------|
| GET | `/admin`, `/admin/dashboard` | `admin.dashboard` |
| GET | `/admin/logout` | `admin.logout` |
| GET | `/admin/users` | `admin.users` |
| GET | `/admin/users/create` | `admin.user.create` |
| POST | `/admin/users/store` | `admin.user.store` |
| GET | `/admin/users/show/{id}` | `admin.user.show` |
| GET | `/admin/users/edit/{id}` | `admin.user.edit` |
| POST | `/admin/users/update/{id}` | `admin.user.update` |
| POST | `/admin/users/password/{id}` | `admin.user.password` |
| POST | `/admin/users/delete/{id}` | `admin.user.destroy` |
| GET | `/admin/users/generate-token-api` | `admin.users.generate-token-api` |

## User CRUD flow

```
Form → FormRequest → DTO → UserAttributesMapper → UserModel
```

- DTOs: `StoreUserDto`, `UpdateUserDto`, `UpdateUserPasswordDto`
- HTML form normalization (checkboxes, empty strings) lives in `UserAttributesMapper` + `FormValueNormalizer`
- Views namespace: `@auth-admin/...`
- Admin layout: `@dashboard/layouts/base.twig`

## CLI

```bash
php bin/console.php user:list
php bin/console.php user:list --limit=20
```

## Data model

Table `users` — see migration `V2026_03_24_120000_CreateUsersTable.php`.

Key fields: `name`, `email`, `password`, `status` (`pending` | `active` | `blocked`), `is_admin`, soft deletes.

`UserStatus` enum: `Concept\Components\AuthAdmin\Enums\UserStatus`.

## Architecture

```
AuthAdmin/
├── Commands/           # user:list
├── Constants/          # RouteName, ViewName
├── Controllers/        # AdminController, UserController
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── Dto/
├── Enums/
├── Extensions/         # TwigExtension (auth)
├── Mappers/
├── Middlewares/        # AuthMiddleware
├── Models/
├── Requests/
├── Services/           # AuthService
├── Views/
└── Assets/             # admin-tokens.js (token generator on forms)
```

## Extending

- Add admin routes in your component and attach `AuthMiddleware::class`
- Map `/admin` to `dashboard` view context if using `@dashboard` layouts
- Use `has_component('AuthAdmin')` in Twig to conditionally show admin features
