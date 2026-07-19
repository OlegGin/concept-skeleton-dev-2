# План впровадження Concept Stack

## Поточний стан (2026-07-15)

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
| `view` | `ViewBuilder` | `ViewOptions` | `ViewStackProvider` | ✅ |
| `telemetry` | `TelemetryBuilder` | `TelemetryOptions` | `TelemetryStackProvider` | ✅ |
| `error-handling` | `ErrorHandlingBuilder` | `ErrorHandlingOptions` | `ErrorHandlingStackProvider` | ✅ recipes in stack |

Інфраструктура:

- `ConceptStack`, `StackBuilder`, `StackCapabilityBuilder` (`end()`)
- `CapabilityRegistry` — явні залежності між capabilities
- `Support/OptionalDependency` — lazy `Closure(): ?T` для optional cross-extension deps (masker тощо)

### Модель Logging (additive handlers)

`logging` приймає **один або кілька** Monolog handlers. Файл — лише один із варіантів, не єдина модель.

| Метод | Що додає |
|-------|----------|
| `toRotatingFile($path, maxFiles, level?)` | `RotatingFileHandler` |
| `toStderr(level?)` | `StreamHandler('php://stderr')` |
| `toHandler($handler)` | будь-який готовий `HandlerInterface` |
| `level()` / `channel()` | default level для helpers / Monolog channel name |
| `withMasking()` | `dataMaskerFactory` → requires `masking` |

```php
ConceptStack::create()
    ->withMasking()
        ->keyPatterns(['/.*password.*/i', '/.*token.*/i'])
        ->end()
    ->withLogging()
        ->level('debug')
        ->channel('app')
        ->toRotatingFile($root . '/storage/logs/app.log', maxFiles: 14)
        ->toStderr()
        ->withMasking()
        ->end()
    ->providers();
```

`withLogging()` без жодного `to*()` → `Capability "logging" requires at least one handler (...)`.
`withLogging()->withMasking()` без `withMasking()` → `Capability "logging" requires "masking" to be enabled.`

`LogHandlerRegistry` лишається для cross-extension additive sinks (напр. Telemetry) — handlers з registry додаються **після** explicit list.

### Модель Database (explicit connection + opt-in query log/telemetry)

`database` — окрема capability. Stack не будує connection/paths — передає готові absolute values.

| Метод | Що додає | Залежність |
|-------|----------|------------|
| `connection([...])` | Illuminate connection array | обов'язково |
| `migrations([...])` | absolute migration paths | — |
| `migrationsTable()` / `seeders()` | migrations table name, seeder classes | — |
| `withQueryLogging($path, maxFiles?)` | SQL query log (RotatingFile) | — |
| `withMasking()` | masker для query log | `masking` |
| `withEmitQueryEvents()` | `DatabaseQueryExecuted` events | `telemetry` |

```php
ConceptStack::create()
    ->withMasking()
        ->keyPatterns(['/.*password.*/i'])
        ->end()
    ->withDatabase()
        ->connection([
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
        ->migrations([$root . '/database/migrations'])
        ->seeders([PageSeeder::class])
        ->withQueryLogging($root . '/storage/logs/query.log')
        ->withMasking()
        ->end()
    ->providers();
```

DB console commands (`DbMigrateCommand`, …) реєструються явно в `withConsole()->commands([...])`, не автоматично stack-ом.

### Модель View (nested Twig / Plates)

`view` — одна capability. Engines — nested XOR під `withView()` (як CSRF під session): закривають перехресну залежність ViewRegistry ↔ ViewInterface всередині одного brick.

| Метод | Що задає |
|-------|----------|
| `paths` / `extensions` / `routeNamespace` | View registries |
| `withTwig()->viewsPath()->cacheDir()->debug()->end()` | Twig engine |
| `withPlates()->viewsPath()->end()` | Plates engine |

Requires `http`. Без engine → missing option `withTwig()/withPlates()`.

```php
$stack = ConceptStack::create();
$stack->withHttp()->routes([$root . '/routes/web.php']);
$stack->withView()
    ->paths(['frontend' => $root . '/resources/views/frontend'])
    ->withTwig()
    ->viewsPath($root . '/resources/views')
    ->cacheDir($root . '/storage/cache/views')
    ->debug(true);
return $stack->providers();
```

### Модель залежностей Logging (opt-in masking)

`masking` — **окрема capability** (`withMasking()`), що реєструє `DataMaskerServiceProvider`.
`logging` сам по собі пише лог без маскування. Маскування вмикається явно:

| Метод | Що додає | Залежність (`requires`) |
|-------|----------|-------------------------|
| `LoggingBuilder::withMasking()` | `dataMaskerFactory` у `LoggerMonologServiceProvider` | `masking` |

Stack smoke — через full skeleton (`routes/web.php`, `routes/api.php`), окремий stack profile прибрано.

### Модель залежностей HTTP (opt-in)

`withHttp()` сам по собі дає тільки базовий HTTP:

- `ServerRequestArgumentResolver`
- `RouteParameterArgumentResolver`

Opt-in розширення HTTP:

| Метод | Що додає | Залежність (`requires`) |
|-------|----------|-------------------------|
| `withFormRequests()` | `FormRequestArgumentResolver` (перший у chain) | `validation` |
| `withTypedRouteParameters()` | `TypedRouteParameterArgumentResolver` | `casting` |

Приклад:

```php
ConceptStack::create()
    ->withCasting()
        ->cacheDir($root . '/storage/cache/casting')
        ->debug(true)
        ->end()
    ->withValidation()
        ->globalExcept(['password'])
        ->end()
    ->withHttp()
        ->routes([$root . '/routes/web.php', $root . '/routes/api.php'])
        ->withFormRequests()
        ->withTypedRouteParameters()
        ->end()
    ->providers();
```

Якщо `withFormRequests()` без `withValidation()` — `CapabilityRegistry` кидає:

`Capability "http" requires "validation" to be enabled.`

Casting і validation — **окремі capabilities**. Stack не реєструє їх автоматично.

**Порядок реєстрації:** залежності (`casting`, `validation`) мають бути викликані **перед** `withHttp()->end()`, бо provider boot order = registration order.

### Модель Session (opt-in CSRF)

`session` — окрема capability (`withSession()`). CSRF **не** окремий top-level brick: це opt-in на session, бо CSRF не має власних options і без session безглуздий.

| Метод | Що додає | Залежність |
|-------|----------|------------|
| `SessionBuilder::withCsrf()` | `CsrfServiceProvider` (sessionFactory → `SessionInterface`) | частина session |

```php
ConceptStack::create()
    ->withSession()
        ->options([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ])
        ->handler($handler) // optional; default NativeFileSessionHandler()
        ->withCsrf()        // opt-in CSRF token manager
        ->end()
    ->providers();
```

`withSession()` без `withCsrf()` — лише session/flash. Middleware (`VerifyCsrfTokenMiddleware`, `HandleCsrfExceptionMiddleware`) лишаються в routes — stack їх не реєструє.

## Ціль

Створити окремий пакет `php-concept/stack` у `/var/www/concept-stack/src`, який дає зручний UX для
побудови застосунку через fluent API.

Stack не має знати про `ConfigInterface`, `PathManager`, `ConfigKey`, `PathName` або структуру конкретного
застосунку. Це суто builder/recipe layer: розробник явно передає потрібні параметри, а stack на виході повертає
`list<ServiceProviderInterface>`.

Простий застосунок має мати можливість підключити тільки те, що йому потрібно:

```php
return function(): array {
    return ConceptStack::create()
        ->withHttp()
            ->routes([__DIR__ . '/../../routes/api.php'])
            ->end()
        ->providers();
};
```

Повніший застосунок може виглядати так:

```php
return function(string $root): array {
    return ConceptStack::create()
        ->withLogging()
            ->level('debug')
            ->channel('app')
            ->toRotatingFile($root . '/storage/logs/app.log', maxFiles: 14)
            ->end()
        ->withTelemetry()
            ->enabled(true)
            ->logs(true)
            ->eventName('log.recorded')
            ->end()
        ->withFlashValidation()
            ->rules([])
            ->logFile($root . '/storage/logs/validation.log')
            ->globalExcept(['password'])
            ->end()
        ->withHttp()
            ->routes([$root . '/routes/web.php', $root . '/routes/api.php'])
            ->end()
        ->withConsole()
            ->name('Concept Skeleton')
            ->version('1.0.0')
            ->commands([
                DbMigrateCommand::class,
                DbMigrationListCommand::class,
                DbMigrationPathsCommand::class,
                DbRollbackCommand::class,
                DbSeedCommand::class,
                DbSeederListCommand::class,
                RouteListCommand::class,
                ViewClearCommand::class,
            ])
            ->end()
        ->providers();
};
```

## Архітектурна межа

`Concept Stack` має бути не новим application layer, а зручним конструктором providers.

### Два рівні застосунку

```text
Простий застосунок:
  ConceptStack::create()
    ->withHttp()->routes([...])->end()
    ->providers()

Складний застосунок (опційно):
  app glue: Config + PathManager + path-map
  ConceptStack::create()
    ->withLogging()->toRotatingFile(...)->end()
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

return [
    new FoundationBootstrap($root, $pathMap),
    ...ConceptStack::create()
        ->withLogging()
            ->level($resolvedLogLevel)
            ->toRotatingFile($resolvedLogPath)
            ->end()
        ->providers(),
];
```

Простий застосунок може взагалі не мати `FoundationBootstrap`, config і path-map:

```php
return ConceptStack::create()
    ->withHttp()
        ->routes([$root . '/routes/api.php'])
        ->end()
    ->providers();
```

Тобто config/path — optional app infrastructure, stack їх не вимагає і не читає.

## Builder UX

`withX()` **одразу** реєструє capability і повертає свій builder. Options mutable далі. `end()` немає.

```php
$stack = ConceptStack::create();

$stack->withMasking()
    ->keyPatterns(['/.*password.*/i']);

$stack->withLogging()
    ->toRotatingFile($root . '/storage/logs/app.log')
    ->withMasking();

$stack->withHttp()
    ->routes([$root . '/routes/web.php']);

return $stack->providers(); // валідація options + CapabilityRegistry requires
```

Кожна гілка — окремий `withX()` від `$stack`. Довгу «ковбасу» з поверненням на parent не будуємо.
Opt-in на builder (напр. `LoggingBuilder::withMasking()`, `HttpBuilder::withFormRequests()`) лише
додає `requires` у реєстр і мутує options.

Nested engines:

```php
$stack->withView()
    ->paths(['frontend' => $root . '/resources/views/frontend'])
    ->withTwig()
    ->viewsPath($root . '/resources/views')
    ->debug(true);
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
│   └── StackBuilder.php            ✅ (root fluent API; withX registers immediately)
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
return ConceptStack::create()
    ->withHttp()
        ->routes([$root . '/routes/web.php'])
        ->end()
    ->addProvider(new ApplicationRuntimeBootstrap())
    ->providers();
```

## Перенесення поточних layers

1. `FoundationBootstrap`
   - Не переносити в stack.
   - Config/PathManager лишаються окремою можливістю застосунку, якщо вона потрібна.

2. `LoggingLayerProvider` -> `LoggingStackProvider` + `MaskingStackProvider` ✅
   - Не читає config.
   - **Розділено на дві capabilities:**
     - `masking` (`withMasking()`): patterns, keyPatterns, rules → `DataMaskerServiceProvider`.
     - `logging` (`withLogging()`): channel + additive handlers (`toRotatingFile` / `toStderr` / `toHandler`) → `LoggerMonologServiceProvider`.
   - Маскування логів — opt-in через `LoggingBuilder::withMasking()` + `requires masking`.
   - Masker передається через `OptionalDependency::factory` лише коли увімкнено.
   - Extension: handlers list замість hardcoded RotatingFile; `LogHandlerRegistry` для extras (Telemetry).

3. `TelemetryLayerProvider` -> `TelemetryStackProvider` ✅
   - Не читає config.
   - Options: `enabled`, `logs`, `eventName`, `subscribers`.
   - `logs(true)` → `TelemetryLogHandler` + `LogHandlerRegistry` (requires `logging`).
   - DB query events — лише `DatabaseBuilder::withEmitQueryEvents()` (requires `telemetry`).
   - `subscribers` → `EventServiceProvider` when non-empty and enabled.
   - Event names — explicit strings з app glue (не `Concept\App` у stack).

4. `ValidationLayerProvider` -> `ValidationStackProvider` ✅
   - Options: custom rules, log file/enabled/max files, global except.
   - Реєструє `ValidationServiceProvider` і `FormRequestServiceProvider`.
   - Caster/logger/masker — lazy optional factories через container (без hard dependency на casting/logging).

5. `Casting` (нова окрема capability, не частина HTTP) ✅
   - `CastingBuilder`: `cacheDir()`, `transformers()`, `debug()`.
   - `CastingStackProvider` реєструє `CastingServiceProvider`.
   - HTTP отримує casting лише через `withTypedRouteParameters()` + `requires casting`.

6. `DatabaseLayerProvider` -> `DatabaseStackProvider` ✅
   - Options: connection array, migration paths, migrations table, seeders, query logging, query telemetry.
   - Усі paths передаються вже готовими absolute або application-resolved paths.
   - Opt-in: `withQueryLogging()`, `withMasking()` (requires masking), `withEmitQueryEvents()` (requires telemetry).
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
   - Основний UX через `ConsoleBuilder` і `end()`.

10. `ViewLayerProvider` -> `ViewStackProvider` ✅
   - Options: paths, routeNamespace, extensions.
   - Nested XOR engines: `withTwig()` / `withPlates()` (закривають ViewRegistry ↔ ViewInterface).
   - Requires `http`. Absolute paths only.

11. Error handling providers ✅
    - Generic Whoops awake chain у `ErrorHandlingStackProvider`.
    - Contracts + Whoops handlers — у `extension-error-handler-whoops` (тонкий).
    - Recipes у stack: `Reporting\LoggerExceptionReporter`, `Reporting\PhpErrorLogReporter`, `Rendering\ViewHttpErrorRenderer`, `Rendering\JsonHttpErrorRenderer`.
    - Recipe-методи (`reportToLog`, `renderHtmlErrorPage`, `showDebugExceptionPage`, …) явно записують factory в options; provider без default-гілок.
    - Early bootstrap → `PhpErrorLogReporter` + `FallbackFileHandler`.

## Міграція skeleton

Профілі прибрано. Єдиний entry point — `bootstrap/providers.php`.

Складний skeleton (поточний) може виглядати так:

```php
return function(string $root): array {
    $pathMap = require __DIR__ . '/path-map.php';

    return [
        new FoundationBootstrap($root, $pathMap), // app glue, не stack
        ...ConceptStack::create()
            ->withLogging()
                ->level('debug')
                ->channel('app')
                ->toRotatingFile($root . '/storage/logs/app.log')
                ->end()
            ->withValidation()
                ->logFile($root . '/storage/logs/validation.log')
                ->end()
            ->withHttp()
                ->routes([$root . '/routes/web.php', $root . '/routes/api.php'])
                ->end()
            ->withConsole()
                ->name('Concept Skeleton')
                ->version('1.0.0')
                ->commands([...])
                ->end()
            ->providers(),
        new ApplicationComponentsBootstrap(),
        new ApplicationRuntimeBootstrap(),
    ];
};
```

`FoundationBootstrap` лишається в skeleton як optional app feature.
Stack від нього не залежить.

## Порядок робіт

1. ✅ `ConceptStack`, `StackBuilder` — `withX()` реєструє одразу, без `end()`.
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
12. ✅ telemetry providers з explicit options.
13. ✅ error handling через explicit factories або generic stack renderers.
14. ✅ `bootstrap/providers.php` на stack (explicit values; Config/PathManager — optional app glue, поки не в stack wiring).
15. ✅ Видалені застарілі `src/App/Providers/Layers/*`; app glue — `src/App/Bootstrap/*Bootstrap`.

## Перевірки

Після кожного великого кроку:

```bash
cd /var/www/concept-skeleton-dev-2
vendor/bin/phpstan
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
- Якщо `withFlashValidation()` сховає middleware chain, routes втратять явність web/api surface.
- Error handling зараз найбільш app-specific частина, тому його краще переносити після базових capabilities.
- Components мають eager registrars, тому їх не варто переносити разом з першим stack pass.
