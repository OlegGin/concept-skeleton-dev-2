# План впровадження Concept Stack

## Поточний стан (2026-07-19)

Реалізовано в `/var/www/concept-stack/src`:

| Capability | Builder | Options | StackProvider | Статус |
|------------|---------|---------|---------------|--------|
| `masking` | `MaskingBuilder` | `MaskingOptions` | `MaskingStackProvider` | ✅ |
| `logging` | `LoggingBuilder` | `LoggingOptions` | `LoggingStackProvider` | ✅ |
| `casting` | `CastingBuilder` | `CastingOptions` | `CastingStackProvider` | ✅ |
| `validation` | `ValidationBuilder` | `ValidationOptions` | `ValidationStackProvider` | ✅ |
| `database` | `DatabaseBuilder` | `DatabaseOptions` | `DatabaseStackProvider` | ✅ |
| `session` | `SessionBuilder` | `SessionOptions` | `SessionStackProvider` | ✅ |
| `http` | `HttpBuilder` | `HttpOptions` | `HttpStackProvider` | ✅ |
| `console` | `ConsoleBuilder` | `ConsoleOptions` | `ConsoleStackProvider` | ✅ |
| `components` | `ComponentsBuilder` | `ComponentsOptions` | `ComponentsStackProvider` | ✅ explicit DB/Console/HTTP/View integrations |
| `view` | `ViewBuilder` | `ViewOptions` | `ViewStackProvider` | ✅ |
| `events` | `EventsBuilder` | `EventsOptions` | `EventsStackProvider` | ✅ |
| `telemetry` | `TelemetryBuilder` | `TelemetryOptions` | `TelemetryStackProvider` | ✅ |
| `error-handling` | `ErrorHandlingBuilder` | `ErrorHandlingOptions` | `ErrorHandlingStackProvider` | ✅ recipes in stack |

Інфраструктура:

- `ConceptStack`, `StackBuilder` — `addX()` реєструє capability; `setX()` / `withX()` на brick. **`end()` немає**
- `CapabilityRegistry` — явні залежності між capabilities
- `Support/OptionalDependency` — lazy `Closure(): ?T` для optional cross-extension deps (masker тощо)

Skeleton glue (не stack):

```
bootstrap/app.php → EarlyErrorHandlingBootstrap → FoundationBootstrap → ApplicationStackBootstrap → Runtime
```

`ApplicationStackBootstrap` читає Config/Path і викликає `ConceptStack`; middleware лишаються в `Concept\App\Middleware`.

### Модель Logging (additive handlers)

`logging` приймає **один або кілька** Monolog handlers. Файл — лише один із варіантів, не єдина модель.

| Метод | Що додає |
|-------|----------|
| `toRotatingFile($path, maxFiles, level?)` | `RotatingFileHandler` |
| `toStderr(level?)` | `StreamHandler('php://stderr')` |
| `withHandler($handler)` | будь-який готовий `HandlerInterface` |
| `setLevel()` / `setChannel()` | default level для helpers / Monolog channel name |
| `withMasking()` | `dataMaskerFactory` → requires `masking` |

```php
$stack = ConceptStack::create();
$stack->addMasking()
    ->setKeyPatterns(['/.*password.*/i', '/.*token.*/i']);
$stack->addLogging()
    ->setLevel('debug')
    ->setChannel('app')
    ->toRotatingFile($root . '/storage/logs/app.log', maxFiles: 14)
    ->toStderr()
    ->withMasking();
return $stack->providers();
```

`addLogging()` без жодного `to*()` → `Capability "logging" requires at least one handler (...)`.
`addLogging()->withMasking()` без `addMasking()` → `Capability "logging" requires "masking" to be enabled.`

`LogHandlerRegistry` лишається для cross-extension additive sinks (напр. Telemetry) — handlers з registry додаються **після** explicit list.

### Модель Database (explicit connection + opt-in query log/events)

`database` — окрема capability. Stack не будує connection/paths — передає готові absolute values.

| Метод | Що додає | Залежність |
|-------|----------|------------|
| `setConnection([...])` | Illuminate connection array | обов'язково |
| `setMigrations([...])` | absolute migration paths | — |
| `setMigrationsTable()` / `setSeeders()` | migrations table name, seeder classes | — |
| `withQueryLogging($path, maxFiles?)` | SQL query log (RotatingFile) | — |
| `withMasking()` | masker для query log | `masking` |
| `withEmitQueryEvents()` | `DatabaseQueryExecuted` events | `events` |

```php
$stack = ConceptStack::create();
$stack->addMasking()
    ->setKeyPatterns(['/.*password.*/i']);
$stack->addDatabase()
    ->setConnection([
        'driver' => 'mysql',
        'host' => 'db',
        'port' => 3306,
        'database' => 'app',
        'username' => 'root',
        'password' => 'secret',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ])
    ->setMigrations([$root . '/database/migrations'])
    ->setSeeders([PageSeeder::class])
    ->withQueryLogging($root . '/storage/logs/query.log')
    ->withMasking();
return $stack->providers();
```

DB console commands (`DbMigrateCommand`, …) реєструються явно в `addConsole()->setCommands([...])`, не автоматично stack-ом.

### Модель View (nested Twig / Plates)

`view` — одна capability. Engines — nested XOR під `addView()` (як CSRF під session): закривають перехресну залежність ViewRegistry ↔ ViewInterface всередині одного brick.

| Метод | Що задає |
|-------|----------|
| `setPaths` / `setExtensions` / `setRouteNamespaces` | View registries |
| `withTwig()->setViewsPath()->setCacheDir()->setDebug()` | Twig engine |
| `withPlates()->setViewsPath()` | Plates engine |

Requires `http`. Без engine → missing option `withTwig()/withPlates()`.

```php
$stack = ConceptStack::create();
$stack->addHttp()->setRoutes([$root . '/routes/web.php']);
$stack->addView()
    ->setPaths(['frontend' => $root . '/resources/views/frontend'])
    ->withTwig()
    ->setViewsPath($root . '/resources/views')
    ->setCacheDir($root . '/storage/cache/views')
    ->setDebug(true);
return $stack->providers();
```

### Модель залежностей Logging (opt-in masking)

`masking` — **окрема capability** (`addMasking()`), що реєструє `DataMaskerServiceProvider`.
`logging` сам по собі пише лог без маскування. Маскування вмикається явно:

| Метод | Що додає | Залежність (`requires`) |
|-------|----------|-------------------------|
| `LoggingBuilder::withMasking()` | `dataMaskerFactory` у `LoggerMonologServiceProvider` | `masking` |

Stack smoke — через full skeleton (`routes/web.php`, `routes/api.php`). Окремий шар profiles не потрібен: recipe = `ApplicationStackBootstrap`.

### Модель залежностей HTTP (opt-in)

`addHttp()` сам по собі дає тільки базовий HTTP:

- `ServerRequestArgumentResolver`
- `RouteParameterArgumentResolver`

Opt-in розширення HTTP:

| Метод | Що додає | Залежність (`requires`) |
|-------|----------|-------------------------|
| `withFormRequests()` | `FormRequestArgumentResolver` (перший у chain) | `validation` |
| `withTypedRouteParameters()` | `TypedRouteParameterArgumentResolver` | `casting` |

Приклад:

```php
$stack = ConceptStack::create();

$stack->addCasting()
    ->setCacheDir($root . '/storage/cache/casting')
    ->setDebug(true);

$stack->addValidation()
    ->setGlobalExcept(['password']);

$stack->addHttp()
    ->setRoutes([$root . '/routes/web.php', $root . '/routes/api.php'])
    ->withFormRequests()
    ->withTypedRouteParameters();

return $stack->providers();
```

Якщо `withFormRequests()` без `addValidation()` — `CapabilityRegistry` кидає:

`Capability "http" requires "validation" to be enabled.`

Casting і validation — **окремі capabilities**. Stack не реєструє їх автоматично.


### Модель Session (opt-in CSRF)

`session` — окрема capability (`addSession()`). CSRF **не** окремий top-level brick: це opt-in на session, бо CSRF не має власних options і без session безглуздий.

| Метод | Що додає | Залежність |
|-------|----------|------------|
| `SessionBuilder::withCsrf()` | `CsrfServiceProvider` (sessionFactory → `SessionInterface`) | частина session |

```php
$stack = ConceptStack::create();

$stack->addSession()
    ->setOptions([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ])
    ->setHandler($handler) // optional; default NativeFileSessionHandler()
    ->withCsrf();        // opt-in CSRF token manager

return $stack->providers();
```

`addSession()` без `withCsrf()` — лише session/flash. Middleware (`VerifyCsrfTokenMiddleware`, `HandleCsrfExceptionMiddleware`) лишаються в routes — stack їх не реєструє.

## Ціль

Створити окремий пакет `php-concept/stack` у `/var/www/concept-stack/src`, який дає зручний UX для
побудови застосунку через fluent API.

Stack не має знати про `ConfigInterface`, `PathManager`, `ConfigKey`, `PathName` або структуру конкретного
застосунку. Це суто builder/recipe layer: розробник явно передає потрібні параметри, а stack на виході повертає
`list<ServiceProviderInterface>`.

Простий застосунок має мати можливість підключити тільки те, що йому потрібно:

```php
return function(): array {
    $stack = ConceptStack::create();
    $stack->addHttp()
        ->setRoutes([__DIR__ . '/../../routes/api.php']);

    return $stack->providers();
};
```

Повніший застосунок може виглядати так:

```php
return function(string $root): array {
    $stack = ConceptStack::create();

    $stack->addLogging()
        ->setLevel('debug')
        ->setChannel('app')
        ->toRotatingFile($root . '/storage/logs/app.log', maxFiles: 14);

    $stack->addTelemetry()
        ->setEnabled(true)
        ->setLogs(true)
        ->setEventName('log.recorded');

    $stack->addValidation()
        ->setRules([])
        ->withLogging($root . '/storage/logs/validation.log')
        ->setGlobalExcept(['password']);

    $stack->addHttp()
        ->setRoutes([$root . '/routes/web.php', $root . '/routes/api.php']);

    $stack->addConsole()
        ->setName('Concept Skeleton')
        ->setVersion('1.0.0')
        ->setCommands([
            DbMigrateCommand::class,
            DbMigrationListCommand::class,
            DbMigrationPathsCommand::class,
            DbRollbackCommand::class,
            DbSeedCommand::class,
            DbSeederListCommand::class,
            RouteListCommand::class,
            ViewClearCommand::class,
        ]);

    return $stack->providers();
};
```

## Архітектурна межа

`Concept Stack` має бути не новим application layer, а зручним конструктором providers.

### Два рівні застосунку

```text
Простий застосунок:
  ConceptStack::create()
    ->addHttp()->setRoutes([...])
    ->providers()

Складний застосунок (опційно):
  app glue: Config + PathManager + path-map
  ConceptStack::create()
    ->addLogging()->toRotatingFile(...)
    ->providers()
```

`Config` і `PathManager` — це **фіча застосунку для складних проєктів**, а не частина stack.
`path-map` — app-level convenience для таких проєктів. Skeleton може тримати `bootstrap/path-map.php`
і використовувати його у `FoundationBootstrap`, але stack про це не знає.

Stack працює тільки з explicit values: absolute paths, connection arrays, command lists, flags.

У stack переїжджає:

- fluent builders для capabilities;
- option objects, які зберігають параметри capability до виклику `providers()`;
- реєстрація extension service providers з уже готовими explicit параметрами;
- складання HTTP resolver/interceptor chain;
- generic wiring між extensions через lazy factories;
- `DataMaskerFactory` та інші generic support-класи, які не залежать від skeleton.

У stack не переїжджає:

- `ConfigInterface` і `ConfigServiceProvider`;
- `PathManager` і `PathManagerServiceProvider`;
- `path-map`, `ConfigKey`, `PathName`;
- `FoundationBootstrap` і будь-який foundation/bootstrap glue;
- побудова absolute paths з project root;
- route-level middleware lists;
- controllers, app middleware, runtime і components;
- будь-яка залежність від `Concept\App`.

Якщо складному застосунку потрібні config/path, це окремий app glue **перед** stack.
Він читає config/path-map і передає в stack уже готові значення:

```php
// app glue (складний застосунок) — не частина stack
$pathMap = require __DIR__ . '/path-map.php';

$stack = ConceptStack::create();
$stack->addLogging()
    ->setLevel($resolvedLogLevel)
    ->toRotatingFile($resolvedLogPath);

return [
    new FoundationBootstrap($root, $pathMap),
    ...$stack->providers(),
];
```

Простий застосунок може взагалі не мати `FoundationBootstrap`, config і path-map:

```php
$stack = ConceptStack::create();
$stack->addHttp()
    ->setRoutes([$root . '/routes/api.php']);

return $stack->providers();
```

Тобто config/path — optional app infrastructure, stack їх не вимагає і не читає.

## Builder UX

`addX()` на `StackBuilder` реєструє capability і повертає brick builder. `setX()` / `withX()` — на brick. **`end()` немає**.

```php
$stack = ConceptStack::create();

$stack->addMasking()
    ->setKeyPatterns(['/.*password.*/i']);

$stack->addLogging()
    ->toRotatingFile($root . '/storage/logs/app.log')
    ->withMasking();

$stack->addHttp()
    ->setRoutes([$root . '/routes/web.php']);

return $stack->providers(); // валідація options + CapabilityRegistry requires
```

Кожна гілка — окремий `$stack->addX()`. Довгу «ковбасу» з поверненням на parent не будуємо.
Opt-in на builder (напр. `LoggingBuilder::withMasking()`, `HttpBuilder::withFormRequests()`) лише
додає `requires` у реєстр і мутує options.

Nested engines:

```php
$stack->addView()
    ->setPaths(['frontend' => $root . '/resources/views/frontend'])
    ->withTwig()
    ->setViewsPath($root . '/resources/views')
    ->setDebug(true);
```

Callback-style не робимо основним UX.

## Пропонована структура `/var/www/concept-stack/src`

```text
src/
├── ConceptStack.php
├── Capability/
│   ├── Capability.php
│   └── CapabilityRegistry.php
├── Builder/
│   └── StackBuilder.php            ✅ (root fluent API; addX registers immediately)
├── Bricks/                         ✅ один brick = capability (Builder + Options + StackProvider поруч)
│   ├── Masking/                    ✅ MaskingBuilder, MaskingOptions, MaskingStackProvider
│   ├── Logging/                    ✅ LoggingBuilder, LoggingOptions, LoggingStackProvider
│   ├── Casting/                    ✅ CastingBuilder, CastingOptions, CastingStackProvider
│   ├── Validation/                 ✅ ValidationBuilder, ValidationOptions, ValidationStackProvider
│   ├── Http/                       ✅ HttpBuilder, HttpOptions, HttpStackProvider
│   ├── Console/                    ✅ ConsoleBuilder, ConsoleOptions, ConsoleStackProvider
│   ├── Session/                    ✅ SessionBuilder, SessionOptions, SessionStackProvider (opt-in CSRF)
│   ├── Database/                   ✅ DatabaseBuilder, DatabaseOptions, DatabaseStackProvider
│   ├── View/                       ✅ ViewBuilder + nested ViewTwigBuilder / ViewPlatesBuilder (requires http)
│   ├── Telemetry/                  ✅ TelemetryBuilder, TelemetryOptions, TelemetryStackProvider
│   └── ErrorHandling/              ✅ ErrorHandlingBuilder, ErrorHandlingOptions, ErrorHandlingStackProvider
├── Support/
│   └── OptionalDependency.php      ✅
└── Exceptions/
    ├── ConceptStackException.php
    ├── MissingCapabilityDependencyException.php
    └── InvalidCapabilityOptionsException.php
```

`FoundationStackProvider`, `ConfigKey`, `PathName`, `StackContext`, `paths()` не потрібні.

## Entry Point

```php
final class ConceptStack
{
    public static function create(): StackBuilder;
}
```

`StackBuilder`:

```php
/**
 * @return list<ServiceProviderInterface>
 */
public function providers(): array;
```

Також потрібен escape hatch:

```php
public function addProvider(ServiceProviderInterface $provider): self;
```

Це дозволяє застосунку змішувати stack capabilities зі своїми providers:

```php
$stack = ConceptStack::create();
$stack->addHttp()
    ->setRoutes([$root . '/routes/web.php']);
$stack->addProvider(new ApplicationRuntimeBootstrap());

return $stack->providers();
```

## Перенесення поточних layers

1. `FoundationBootstrap`
   - Не переносити в stack.
   - Config/PathManager лишаються окремою можливістю застосунку, якщо вона потрібна.

2. `LoggingLayerProvider` -> `LoggingStackProvider` + `MaskingStackProvider` ✅
   - Не читає config.
   - **Розділено на дві capabilities:**
     - `masking` (`addMasking()`): patterns, keyPatterns, rules → `DataMaskerServiceProvider`.
     - `logging` (`addLogging()`): channel + additive handlers (`toRotatingFile` / `toStderr` / `withHandler`) → `LoggerMonologServiceProvider`.
   - Маскування логів — opt-in через `LoggingBuilder::withMasking()` + `requires masking`.
   - Masker передається через `OptionalDependency::factory` лише коли увімкнено.
   - Extension: handlers list замість hardcoded RotatingFile; `LogHandlerRegistry` для extras (Telemetry).

3. `TelemetryLayerProvider` -> `TelemetryStackProvider` ✅
   - Не читає config.
   - Options: `enabled`, `logs`, `eventName`.
   - `logs(true)` → `TelemetryLogHandler` + `LogHandlerRegistry` (requires `logging`).
   - DB query events — лише `DatabaseBuilder::withEmitQueryEvents()` (requires `events`).
   - Event dispatcher/subscribers — окрема capability `addEvents()`.
   - Event names — explicit strings з app glue (не `Concept\App` у stack).

4. `ValidationLayerProvider` -> `ValidationStackProvider` ✅
   - Options: custom rules, log file/enabled/max files, global except.
   - Реєструє `ValidationServiceProvider` і `FormRequestServiceProvider`.
   - Caster/logger/masker — lazy optional factories через container (без hard dependency на casting/logging).

5. `Casting` (нова окрема capability, не частина HTTP) ✅
   - `CastingBuilder`: `setCacheDir()`, `setTransformers()`, `setDebug()`.
   - `CastingStackProvider` реєструє `CastingServiceProvider`.
   - HTTP отримує casting лише через `withTypedRouteParameters()` + `requires casting`.

6. `DatabaseLayerProvider` -> `DatabaseStackProvider` ✅
   - Options: connection array, migration paths, migrations table, seeders, query logging, query events.
   - Усі paths передаються вже готовими absolute або application-resolved paths.
   - Opt-in: `withQueryLogging()`, `withMasking()` (requires masking), `withEmitQueryEvents()` (requires events).
   - Реєструє `PaginationConfiguratorServiceProvider` + `DatabaseEloquentServiceProvider`.

7. `SessionLayerProvider` -> `SessionStackProvider` ✅
   - Options: session options, optional `SessionHandlerInterface`.
   - Stack не будує file session path.
   - Opt-in CSRF: `SessionBuilder::withCsrf()` → `CsrfServiceProvider` (не окремий top-level brick).
   - CSRF/session middleware — у routes, не в stack.

8. `HttpLayerProvider` -> `HttpStackProvider` ✅
   - Options: absolute route paths, interceptors, not-found middleware.
   - Opt-in: `withFormRequests()` (requires validation), `withTypedRouteParameters()` (requires casting).
   - Реєструє `HttpKernelServiceProvider`, `HttpServiceProvider`.
   - Resolver order (повний):
     `FormRequestArgumentResolver` (opt-in),
     `ServerRequestArgumentResolver`,
     `TypedRouteParameterArgumentResolver` (opt-in),
     `RouteParameterArgumentResolver`.

9. `ConsoleLayerProvider` -> `ConsoleStackProvider` ✅
   - Options: app name, app version, command classes.
   - Основний UX через `ConsoleBuilder`.

10. `ViewLayerProvider` -> `ViewStackProvider` ✅
   - Options: paths, routeNamespace, extensions.
   - Nested XOR engines: `withTwig()` / `withPlates()` (закривають ViewRegistry ↔ ViewInterface).
   - Requires `http`. Absolute paths only.

11. Error handling providers ✅
    - Generic Whoops awake chain у `ErrorHandlingStackProvider`.
    - Contracts + Whoops handlers — у `extension-error-handler-whoops` (тонкий).
    - Recipes у stack: `Reporting\LoggerExceptionReporter`, `Reporting\PhpErrorLogReporter`, `Rendering\ViewHttpErrorRenderer`, `Rendering\JsonHttpErrorRenderer`.
    - Recipe-методи (`reportToLogs`, `renderView`, `debug(..., handler)`, …) явно записують factory в options; provider без default-гілок.
    - Early bootstrap → `PhpErrorLogReporter` + `FallbackFileHandler`.

## Міграція skeleton

Окремий шар profiles не потрібен: `ApplicationStackBootstrap` — recipe/profile додатку. Єдиний entry point — `bootstrap/app.php` (один `registerServiceProviders`).

Складний skeleton (поточний):

```php
$app->registerServiceProviders([
    new EarlyErrorHandlingBootstrap(
        debug: ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        fallbackFilePath: $root . '/resources/views/errors/fallback/500.php',
    ),
    new FoundationBootstrap($root, $pathMap),
    new ApplicationStackBootstrap(), // Config/Path → ConceptStack + Components brick
    new ApplicationRuntimeBootstrap(),
]);
```

`FoundationBootstrap` — optional app feature. Stack від нього не залежить (лише від explicit values у brick options).

## Порядок робіт

1. ✅ `ConceptStack`, `StackBuilder` — `addX()` реєструє capability, без `end()`.
2. ✅ `Options/*Options.php` — частково (`Casting`, `Validation`, `Http`, `Console`).
3. ✅ `ConsoleBuilder` як еталон nested builder UX.
4. ✅ `ConsoleStackProvider`.
5. ✅ `CastingBuilder` / `CastingStackProvider` — окрема capability (не в HTTP).
6. ✅ `HttpBuilder` / `HttpStackProvider` — opt-in form requests і typed route params.
7. ✅ `ValidationBuilder` / `ValidationStackProvider` + `OptionalDependency`.
8. ✅ `MaskingBuilder` / `MaskingStackProvider` + `LoggingBuilder` / `LoggingStackProvider` (opt-in masking).
9. ✅ `SessionBuilder` / `SessionStackProvider` (opt-in `withCsrf()`).
10. ✅ `DatabaseBuilder` / `DatabaseStackProvider` (opt-in query log/masking/telemetry).
11. ✅ `ViewBuilder` / `ViewStackProvider` + nested `withTwig()` / `withPlates()`.
12. ✅ events + telemetry providers з explicit options.
13. ✅ error handling через explicit factories або generic stack renderers.
14. ✅ `bootstrap/app.php` + `ApplicationStackBootstrap` (Config/Path → stack; middleware в App).
15. ✅ App glue — `src/App/Bootstrap/*Bootstrap` (не LayerProvider).
16. ✅ Components brick + stable topological ordering (`requires` dependencies boot before consumers).

## Перевірки

Після кожного великого кроку:

```bash
cd /var/www/concept-skeleton-dev-2
vendor/bin/phpstan
composer boot-smoke
```

Додаткові smoke checks (full skeleton):

- перевірити головну сторінку і validation redirect;
- `GET /api/ping`, `POST /api/echo`;
- `GET /test/db`;
- `route:list`;
- console db commands.

## Ризики

- Якщо stack почне читати `ConfigInterface`, він стане залежним від конкретної моделі застосунку.
- Якщо stack почне використовувати `PathManager`, прості застосунки отримають непотрібну обов'язкову інфраструктуру.
- Якщо stack почне залежати від `Concept\App`, він стане перенесеним skeleton layer, а не reusable package.
- Якщо validation brick сховає middleware chain, routes втратять явність web/api surface.
- Error handling зараз найбільш app-specific частина, тому його краще переносити після базових capabilities.
- Components registrars eager; тому stack топологічно впорядковує capability providers за `requires`.
