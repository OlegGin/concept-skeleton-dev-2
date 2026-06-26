# SettingsManager

Centralized storage and retrieval of application settings in the database with cache-aside, typed values, and an admin UI.

## Requirements

- PHP 8.4+
- `AuthAdmin` component (admin routes are protected by `AuthMiddleware`)
- Component registered in `config/components.php`

## Installation

```bash
php bin/console.php migrate
php bin/console.php db:seed -c "Concept\Components\SettingsManager\Database\Seeders\SettingsSeeder"
php bin/console.php component:publish-assets
```

Register the component if it is not enabled yet:

```php
// config/components.php
SettingsManagerComponent::class => SettingsManagerComponent::class,
```

## Data model

Table `settings`:

| Column | Description |
|--------|-------------|
| `setting_key` | Setting key (unique **within** `setting_group`) |
| `setting_group` | Logical group (`general`, `mail`, `features`, …) |
| `setting_value` | Serialized value (stored as string) |
| `data_type` | `string`, `text`, `int`, `float`, `bool`, `json` |
| `description` | Optional note for the admin panel |

The same key may exist in different groups. Always pass the group when reading or writing.

## PHP usage

Inject `SettingsManagerInterface`:

```php
use Concept\Components\SettingsManager\Enums\SettingGroup;
use Concept\Components\SettingsManager\Services\Contracts\SettingsManagerInterface;

public function __construct(
    private readonly SettingsManagerInterface $settings,
) {}
```

### Read

```php
$name = $this->settings->get('app.name', 'Default', SettingGroup::GENERAL->value);
$enabled = $this->settings->get('app.maintenance', false, 'general');
$allMail = $this->settings->getGroup('mail');

if ($this->settings->has('retry_attempts', 'mail')) {
    // ...
}
```

### Write

```php
$this->settings->set('app.name', 'My App', 'general', 'string');
$this->settings->set('app.maintenance', true, 'general', 'bool');
$this->settings->set('features.enabled', ['users'], 'features'); // type auto-detected

$this->settings->delete('app.maintenance', 'general');
```

`set()` accepts an optional `$id` for admin updates when the key or group changes.

## Twig usage

The component registers `Extensions/TwigExtension` automatically.

| Function | Description |
|----------|-------------|
| `setting(key, default, group)` | Get a typed value |
| `has_setting(key, group)` | Check if a setting exists |
| `settings_group(group)` | Get all settings in a group as an array |
| `settings()` | Returns `SettingsManagerInterface` |

Examples:

```twig
<title>{{ setting('app.name', config('app.name')) }}</title>

{% if setting('app.maintenance', false, 'general') %}
    <div class="alert alert-warning">Maintenance mode is on.</div>
{% endif %}

{% if has_setting('from_address', 'mail') %}
    <a href="mailto:{{ setting('from_address', '', 'mail') }}">Contact</a>
{% endif %}

{% for key, value in settings_group('features') %}
    <li>{{ key }}: {{ value }}</li>
{% endfor %}
```

Default group is `general` when the third argument is omitted.

## Admin panel

| Route | Name |
|-------|------|
| `GET /admin/settings` | `admin.settings` |
| `GET /admin/settings/create` | `admin.settings.create` |
| `POST /admin/settings/store` | `admin.settings.store` |
| `GET /admin/settings/edit/{id}` | `admin.settings.edit` |
| `POST /admin/settings/update/{id}` | `admin.settings.update` |
| `POST /admin/settings/delete/{id}` | `admin.settings.destroy` |

The sidebar link appears when `has_component('SettingsManager')` is true.

## Groups and types

**Groups** — `Concept\Components\SettingsManager\Enums\SettingGroup`:

- `general`
- `mail`
- `features`

Add new cases to the enum when you need more groups.

**Data types** — `Concept\Components\SettingsManager\Enums\SettingDataType`:

- `string` — short single-line value
- `text` — long text (textarea in admin)
- `int`, `float`, `bool`, `json`

## Validation

Scoped uniqueness uses the shared `unique` rule:

```
unique:settings,setting_key,NULL,NULL,setting_group,general
unique:settings,setting_key,{id},id,setting_group,mail
```

See `Concept\Common\Validation\Rules\UniqueRule` for the full parameter list.

## Architecture

```
SettingsManager/
├── Cache/                  # cache-aside (in-memory)
├── Controllers/            # admin CRUD
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── Dto/
├── Enums/
├── Extensions/             # TwigExtension
├── Mappers/
├── Models/
├── Providers/              # DI registration
├── Requests/
├── Services/
│   └── Contracts/SettingsManagerInterface.php
├── Support/SettingValueCodec.php
└── Views/
```

## Cache

- Read: cache → database → cache
- Write/delete: invalidate `setting:{group}:{key}` and `group:{group}`
- `SettingsCacheInterface` can be replaced via the service provider for Redis or another backend
