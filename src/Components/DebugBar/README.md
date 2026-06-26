# DebugBar

Development debug panel for HTML responses. Injects [PHP Debug Bar](https://github.com/php-debugbar/php-debugbar) with custom data collectors.

## Requirements

- PHP 8.4+
- `php-debugbar/php-debugbar` (dev dependency)
- `APP_DEBUG=true` (or `app.debug` in config)

## Installation

Register the component (typically **dev only**):

```php
// config/dev/components.php
DebugBarComponent::class => DebugBarComponent::class,
```

Publish assets:

```bash
php bin/console.php component:publish-assets
```

Ensure debug mode is enabled:

```env
APP_DEBUG=true
```

## Behaviour

- `DebugBarMiddleware` is registered globally via component `routes.php`
- Active only when `app.debug` is `true`
- Injects toolbar into HTML responses (`</head>`, `</body>`)
- Skipped for JSON/API responses

## Data collectors

| Collector | Shows |
|-----------|-------|
| PHP Info | PHP version, extensions |
| Time | Request timing |
| Memory | Memory usage |
| Database | SQL queries (via telemetry) |
| Route | Matched route and parameters |
| Services | Resolved container services |
| Timeline | Framework telemetry events |
| Config | Application config (masked sensitive values) |
| Request | Request data |
| Messages | Log messages |

Sensitive config values are masked through `DataMaskerInterface`.

## DI

`DebugBarServiceProvider` registers:

- `CustomDebugBar` — main debug bar instance
- `JavascriptRenderer` — HTML/JS injection

Assets are served from `/components/debug_bar/dist/`.

## Architecture

```
DebugBar/
├── Middlewares/        # DebugBarMiddleware
├── Providers/          # DebugBarServiceProvider
├── Support/            # collectors, CustomDebugBar
└── routes.php          # lazy middleware registration
```

No migrations, seeders, or Twig views.

## Production

Do **not** register this component in production config. Keep it in `config/dev/components.php` (or equivalent) only.

If `app.debug` is accidentally enabled in production, the middleware still checks the flag and stays inactive when debug is off.

## Extending

Add custom collectors in `DebugBarServiceProvider`:

```php
$debugBar->addCollector(new MyCustomCollector($dependency));
```

Follow existing collectors in `Support/` as examples.
