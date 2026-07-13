# План впровадження Concept Stack

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
├── Builder/
│   ├── StackBuilder.php
│   ├── LoggingBuilder.php
│   ├── TelemetryBuilder.php
│   ├── ValidationBuilder.php
│   ├── DatabaseBuilder.php
│   ├── SessionBuilder.php
│   ├── HttpBuilder.php
│   ├── ConsoleBuilder.php
│   ├── ViewBuilder.php
│   └── ErrorHandlingBuilder.php
├── Options/
│   ├── LoggingOptions.php
│   ├── TelemetryOptions.php
│   ├── ValidationOptions.php
│   ├── DatabaseOptions.php
│   ├── SessionOptions.php
│   ├── HttpOptions.php
│   ├── ConsoleOptions.php
│   ├── ViewOptions.php
│   └── ErrorHandlingOptions.php
├── Providers/
│   ├── LoggingStackProvider.php
│   ├── TelemetryStackProvider.php
│   ├── ValidationStackProvider.php
│   ├── DatabaseStackProvider.php
│   ├── SessionStackProvider.php
│   ├── HttpStackProvider.php
│   ├── ConsoleStackProvider.php
│   ├── ViewStackProvider.php
│   └── ErrorHandlingStackProvider.php
└── Support/
    └── DataMaskerFactory.php
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

2. `LoggingLayerProvider` -> `LoggingStackProvider`
   - Не читає config.
   - Отримує explicit options: `file`, `level`, `maxFiles`, `channel`, masking patterns/rules.
   - Реєструє `DataMaskerServiceProvider` і `LoggerMonologServiceProvider`.

3. `TelemetryLayerProvider` -> `TelemetryStackProvider`
   - Не читає config.
   - Options: `enabled`, `logs`, `dbQueries`, `eventName`, `subscribers`.
   - Event names не мають залежати від `Concept\App`.

4. `ValidationLayerProvider` -> `ValidationStackProvider`
   - Options: custom rules, log enabled/file/max files, global except.
   - Реєструє `ValidationServiceProvider` і `FormRequestServiceProvider`.
   - Caster/logger dependencies лишаються lazy optional factories через container.

5. `DatabaseLayerProvider` -> `DatabaseStackProvider`
   - Options: connection array, migration paths, migrations table, seeders, logging, query telemetry.
   - Усі paths передаються вже готовими absolute або application-resolved paths.

6. `SessionLayerProvider` -> `SessionStackProvider`
   - Options: session options, optional `SessionHandlerInterface`.
   - Stack не будує file session path.

7. `HttpLayerProvider` -> `HttpStackProvider`
   - Options: absolute route paths, interceptors, caster cache directory, transformer classes, debug.
   - Реєструє `CastingServiceProvider`, `HttpKernelServiceProvider`, `HttpServiceProvider`.
   - Resolver order лишається:
     `FormRequestArgumentResolver`, `ServerRequestArgumentResolver`,
     `TypedRouteParameterArgumentResolver`, `RouteParameterArgumentResolver`.

8. `ConsoleLayerProvider` -> `ConsoleStackProvider`
   - Options: app name, app version, command classes.
   - Основний UX через `ConsoleBuilder` і `end()`.

9. `ViewLayerProvider` -> `ViewStackProvider`
   - Options: view paths, route namespace, extensions, twig views path, cache dir, debug.
   - Stack не вираховує paths.

10. Error handling providers
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

1. Додати `ConceptStack`, `StackBuilder`, базовий contract для child builders з `end()`.
2. Додати `Options/*Options.php` для кожної capability.
3. Додати `ConsoleBuilder` першим як еталон nested builder UX.
4. Реалізувати `ConsoleStackProvider`, бо він найпростіший і вже показує потрібний API.
5. Реалізувати `HttpBuilder` і `HttpStackProvider`, зберігши resolver chain.
6. Реалізувати `ValidationBuilder` / `ValidationStackProvider`.
7. Реалізувати `LoggingBuilder` / `LoggingStackProvider` і перенести `DataMaskerFactory`.
8. Реалізувати database/session/view/telemetry providers з explicit options.
9. Окремо вирішити error handling через explicit factories або generic stack renderers.
10. Перевести `bootstrap/providers.php` на stack, залишивши config/path glue в skeleton.
11. Після parity видалити або deprecated-нути старі `src/App/Providers/Layers`, які повністю замінені stack.

## Перевірки

Після кожного великого кроку:

```bash
cd /var/www/concept-skeleton-dev-2
vendor/bin/phpstan
```

Додаткові smoke checks:

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
