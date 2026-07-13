# План впровадження Concept Stack

## Поточний стан (2026-07-13)

Реалізовано в `/var/www/concept-stack/src`:

| Capability | Builder | Options | StackProvider | Статус |
|------------|---------|---------|---------------|--------|
| `masking` | `MaskingBuilder` | `MaskingOptions` | `MaskingStackProvider` | ✅ |
| `logging` | `LoggingBuilder` | `LoggingOptions` | `LoggingStackProvider` | ✅ |
| `casting` | `CastingBuilder` | `CastingOptions` | `CastingStackProvider` | ✅ |
| `validation` | `ValidationBuilder` | `ValidationOptions` | `ValidationStackProvider` | ✅ |
| `http` | `HttpBuilder` | `HttpOptions` | `HttpStackProvider` | ✅ |
| `console` | `ConsoleBuilder` | `ConsoleOptions` | `ConsoleStackProvider` | ✅ |

Інфраструктура:

- `ConceptStack`, `StackBuilder`, `StackCapabilityBuilder` (`end()`)
- `CapabilityRegistry` — явні залежності між capabilities
- `Support/OptionalDependency` — lazy `Closure(): ?T` для optional cross-extension deps (masker тощо)

### Модель залежностей Logging (opt-in masking)

`masking` — **окрема capability** (`withMasking()`), що реєструє `DataMaskerServiceProvider`.
`logging` сам по собі пише лог без маскування. Маскування вмикається явно:

| Метод | Що додає | Залежність (`requires`) |
|-------|----------|-------------------------|
| `LoggingBuilder::withMasking()` | `dataMaskerFactory` у `LoggerMonologServiceProvider` | `masking` |

```php
ConceptStack::create()
    ->withMasking()
        ->keyPatterns(['/.*password.*/i', '/.*token.*/i'])
        ->patterns([...])
        ->rules([...])
        ->end()
    ->withLogging()
        ->file($root . '/storage/logs/app.log')
        ->level('debug')
        ->channel('app')
        ->withMasking()   // requires masking capability
        ->end()
    ->providers();
```

`withLogging()->withMasking()` без `withMasking()` → `Capability "logging" requires "masking" to be enabled.`
`withLogging()` без `file(...)` → `Capability "logging" requires option "file" to be set.`

Stack test profile: `bootstrap/providers-stack.php` + `routes/stack.php` + `StackTestController`.

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
        ->routes([$root . '/routes/stack.php'])
        ->withFormRequests()
        ->withTypedRouteParameters()
        ->end()
    ->providers();
```

Якщо `withFormRequests()` без `withValidation()` — `CapabilityRegistry` кидає:

`Capability "http" requires "validation" to be enabled.`

Casting і validation — **окремі capabilities**. Stack не реєструє їх автоматично.

**Порядок реєстрації:** залежності (`casting`, `validation`) мають бути викликані **перед** `withHttp()->end()`, бо provider boot order = registration order.

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
            ->file($root . '/storage/logs/app.log')
            ->level('debug')
            ->channel('app')
            ->maxFiles(14)
            ->end()
        ->withTelemetry()
            ->enabled(true)
            ->logs(true)
            ->dbQueries(true)
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
    ->withLogging()->file(...)->end()
    ->providers()
```

`Config` і `PathManager` — це **фіча застосунку для складних проєктів**, а не частина stack.
`path-map` — app-level convenience для таких проєктів. Skeleton може тримати `bootstrap/path-map.php`
і використовувати його у `FoundationLayerProvider`, але stack про це не знає.

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
- `FoundationLayerProvider` і будь-який foundation/bootstrap glue;
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
    new FoundationLayerProvider($root, $pathMap),
    ...ConceptStack::create()
        ->withLogging()
            ->file($resolvedLogPath)
            ->level($resolvedLogLevel)
            ->end()
        ->providers(),
];
```

Простий застосунок може взагалі не мати `FoundationLayerProvider`, config і path-map:

```php
return ConceptStack::create()
    ->withHttp()
        ->routes([$root . '/routes/api.php'])
        ->end()
    ->providers();
```

Тобто config/path — optional app infrastructure, stack їх не вимагає і не читає.

## Builder UX

Для configurable capabilities використовуємо nested builders з явним `end()`:

```php
->withConsole()
    ->name('Concept Skeleton')
    ->version('1.0.0')
    ->commands([...])
    ->end()
->withHttp()
    ->routes([...])
    ->end()
```

`withConsole()` повертає `ConsoleBuilder`. `ConsoleBuilder` змінює `ConsoleOptions` і через `end()` повертає
parent `StackBuilder`.

Для простих capabilities можна залишити короткий виклик, якщо немає параметрів:

```php
->withTelemetry()
->withSession()
```

Але якщо capability має параметри, основний стиль:

```php
->withDatabase()
    ->connection([...])
    ->migrations([...])
    ->seeders([...])
    ->end()
```

Callback-style не робимо основним UX, щоб bootstrap застосунку не був засмічений closures.

## Пропонована структура `/var/www/concept-stack/src`

```text
src/
├── ConceptStack.php
├── Capability/
│   ├── Capability.php
│   └── CapabilityRegistry.php
├── Builder/
│   ├── StackBuilder.php            ✅ (root fluent API)
│   └── Contracts/
│       └── StackCapabilityBuilder.php  ✅
├── Bricks/                         ✅ один brick = capability (Builder + Options + StackProvider поруч)
│   ├── Masking/                    ✅ MaskingBuilder, MaskingOptions, MaskingStackProvider
│   ├── Logging/                    ✅ LoggingBuilder, LoggingOptions, LoggingStackProvider
│   ├── Casting/                    ✅ CastingBuilder, CastingOptions, CastingStackProvider
│   ├── Validation/                 ✅ ValidationBuilder, ValidationOptions, ValidationStackProvider
│   ├── Http/                       ✅ HttpBuilder, HttpOptions, HttpStackProvider
│   ├── Console/                    ✅ ConsoleBuilder, ConsoleOptions, ConsoleStackProvider
│   ├── Telemetry/                  🔲
│   ├── Database/                   🔲
│   ├── Session/                    🔲
│   ├── View/                       🔲
│   └── ErrorHandling/              🔲
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
    ->addProvider(new ApplicationRuntimeServiceProvider())
    ->providers();
```

## Перенесення поточних layers

1. `FoundationLayerProvider`
   - Не переносити в stack.
   - Config/PathManager лишаються окремою можливістю застосунку, якщо вона потрібна.

2. `LoggingLayerProvider` -> `LoggingStackProvider` + `MaskingStackProvider` ✅
   - Не читає config.
   - **Розділено на дві capabilities:**
     - `masking` (`withMasking()`): patterns, keyPatterns, rules → `DataMaskerServiceProvider`.
     - `logging` (`withLogging()`): file, level, maxFiles, channel → `LoggerMonologServiceProvider`.
   - Маскування логів — opt-in через `LoggingBuilder::withMasking()` + `requires masking`.
   - Masker передається через `OptionalDependency::factory` лише коли увімкнено.

3. `TelemetryLayerProvider` -> `TelemetryStackProvider`
   - Не читає config.
   - Options: `enabled`, `logs`, `dbQueries`, `eventName`, `subscribers`.
   - Event names не мають залежати від `Concept\App`.

4. `ValidationLayerProvider` -> `ValidationStackProvider` ✅
   - Options: custom rules, log file/enabled/max files, global except.
   - Реєструє `ValidationServiceProvider` і `FormRequestServiceProvider`.
   - Caster/logger/masker — lazy optional factories через container (без hard dependency на casting/logging).

5. `Casting` (нова окрема capability, не частина HTTP) ✅
   - `CastingBuilder`: `cacheDir()`, `transformers()`, `debug()`.
   - `CastingStackProvider` реєструє `CastingServiceProvider`.
   - HTTP отримує casting лише через `withTypedRouteParameters()` + `requires casting`.

6. `DatabaseLayerProvider` -> `DatabaseStackProvider`
   - Options: connection array, migration paths, migrations table, seeders, logging, query telemetry.
   - Усі paths передаються вже готовими absolute або application-resolved paths.

7. `SessionLayerProvider` -> `SessionStackProvider`
   - Options: session options, optional `SessionHandlerInterface`.
   - Stack не будує file session path.

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

10. `ViewLayerProvider` -> `ViewStackProvider`
   - Options: view paths, route namespace, extensions, twig views path, cache dir, debug.
   - Stack не вираховує paths.

11. Error handling providers
    - Перенести тільки generic частину.
    - Renderer/reporter або стають stack-generic, або передаються explicit factories.
    - Не тягнути `Concept\App\Http\Error`.

## Міграція skeleton

Профілі прибрано. Єдиний entry point — `bootstrap/providers.php`.

Складний skeleton (поточний) може виглядати так:

```php
return function(string $root): array {
    $pathMap = require __DIR__ . '/path-map.php';

    return [
        new FoundationLayerProvider($root, $pathMap), // app glue, не stack
        ...ConceptStack::create()
            ->withLogging()
                ->file($root . '/storage/logs/app.log')
                ->level('debug')
                ->channel('app')
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
        new ApplicationComponentsServiceProvider(),
        new ApplicationRuntimeServiceProvider(),
    ];
};
```

`FoundationLayerProvider` лишається в skeleton як optional app feature.
Stack від нього не залежить.

## Порядок робіт

1. ✅ `ConceptStack`, `StackBuilder`, базовий contract для child builders з `end()`.
2. ✅ `Options/*Options.php` — частково (`Casting`, `Validation`, `Http`, `Console`).
3. ✅ `ConsoleBuilder` як еталон nested builder UX.
4. ✅ `ConsoleStackProvider`.
5. ✅ `CastingBuilder` / `CastingStackProvider` — окрема capability (не в HTTP).
6. ✅ `HttpBuilder` / `HttpStackProvider` — opt-in form requests і typed route params.
7. ✅ `ValidationBuilder` / `ValidationStackProvider` + `OptionalDependency`.
8. ✅ `MaskingBuilder` / `MaskingStackProvider` + `LoggingBuilder` / `LoggingStackProvider` (opt-in masking).
9. 🔲 database / session / view / telemetry providers з explicit options.
10. 🔲 error handling через explicit factories або generic stack renderers.
11. 🔲 Перевести `bootstrap/providers.php` на stack, залишивши config/path glue в skeleton.
12. 🔲 Після parity видалити або deprecated-нути старі `src/App/Providers/Layers`.

## Перевірки

Після кожного великого кроку:

```bash
cd /var/www/concept-skeleton-dev-2
vendor/bin/phpstan
```

Додаткові smoke checks (stack profile):

- `GET /stack`, `/stack/ping`, `/stack/hello/{name}`, `/stack/user/{id}`
- `POST /stack/echo` (FormRequest + validation)

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
