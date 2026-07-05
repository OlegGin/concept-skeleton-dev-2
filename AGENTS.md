# Concept Framework — інструкції для агента (session recovery)

> Читай цей файл на початку сесії, якщо немає контексту попередньої розмови.
> Оновлюй секцію «Поточний стан» після кожного завершеного кроку міграції.

## Проєкти та зв'язок

| Проєкт | Шлях | Git | Роль |
|--------|------|-----|------|
| **Skeleton** | `/var/www/concept-skeleton-dev-2` | окремий repo | «Клей»: bootstrap, routes, controllers, extensions wiring |
| **Core** | `/var/www/concept-core-2` | окремий repo | Тонке ядро: container + routing |
| **Symlink** | `skeleton/vendor/php-concept/core-2` → `/var/www/concept-core-2/` | — | Зміни в core через symlink = зміни в реальному проєкті ядра |

Composer skeleton підключає core через path repository:

```json
"repositories": [{ "type": "path", "url": "/var/www/concept-core-2", "options": { "symlink": true } }]
```

**Можна змінювати обидва проєкти в одній сесії.**

## Архітектурна ціль

Переходимо від **товстого ядра** (старий код) до **тонкого ядра** + **Extensions**.

```
Старе ядро (reference only):  core-2/storage/src/
Старі конфіги (reference):    core-2/storage/config/
Нове ядро (active):            core-2/src/
Розширення (skeleton):         skeleton/src/Extensions/
Клей (skeleton):               skeleton/bootstrap/, skeleton/src/App/
```

### Що лишається в ядрі

- `App` — League Container + ReflectionContainer delegate, dispatch router
- `HttpKernelServiceProvider` — ServerRequest, Router, завантаження route files
- `RouteStrategy` — invoke handler, interceptors, **chain of ArgumentResolvers**
- Контракти: `ArgumentResolverInterface`, `RouteInterceptorInterface`
- Дефолтні резолвери (класи в core, **порядок задає skeleton**):
  - `ServerRequestArgumentResolver` — PSR-7 request injection
  - `RouteParameterArgumentResolver` — raw route vars by param name

### Конфігурація — glue читає, extensions отримують параметри

**Ядро не знає про Config.** Extensions **не читають** `ConfigInterface` — лише skeleton glue.

| Шар | Підхід |
|-----|--------|
| **Core** | Без config; тільки явні параметри provider-ів |
| **Extensions** | Constructor params у `*ServiceProvider`; сервіси — через конструктор, не через `$container->get()` чужих типів |
| **Skeleton glue** | Читає `ConfigInterface` (див. порядок merge нижче) → передає значення в providers/extensions |

**Порядок merge конфігурації** (`ConfigServiceProvider`):

```
1. config/*.php              — базові значення (production-safe defaults)
2. config/{APP_ENV}/*.php    — env overlay (dev/, production/, …)
3. .env / getenv             — найвищий пріоритет (DB_*, APP_DEBUG, …)
```

`APP_ENV` для кроку 2 береться **лише з `.env`/environment** (до merge PHP-конфігів у dot-keys). Тобто `config/dev/` підвантажиться, коли в `.env` є `APP_ENV=dev`. Без `.env` — тільки базовий `config/`.

Приклад skeleton:
- `config/app.php` — `debug => false`
- `config/dev/app.php` — `debug => true` (при `APP_ENV=dev`)
- `.env` — `APP_DEBUG=true`, `DB_HOST=…` перекриває все інше

**Шляхи:** `PathManager` + `PathName` constants; `pathMap` у `bootstrap/shared/path-map.php` (full profile).

**Логи:** config — `log.file` / `db.log_file` / `validator.log_file` (ім’я файлу під `PathName::LOGS`); glue → `logFilePath` (absolute шлях до файлу для Monolog).

**Reference-конфіги** старого додатку: `core-2/storage/config/` — довідник ключів/структури при переносі.

**Не робити:**
- не додавати `ConfigInterface` у core
- не підключати `storage/config/` старого ядра напряму
- не тягнути `ConfigInterface` всередину extension-класів (тільки glue)

## Збірка додатку — модель «конструктора» (узгоджено)

> Мета: збирати додаток як конструктор — «хочу логування → додаю provider», «хочу Twig + розширення → передаю в constructor». Крок за кроком від мінімального стеку до повного.

### Стратегія: incremental build, не big-bang

| Артефакт | Роль |
|----------|------|
| **`src/App/Providers/Layers/*LayerProvider.php`** | Full profile glue — один layer = один домен (Foundation, Logging, Http, View, …) |
| **`bootstrap/app.php`** | `APP_PROFILE` constant → `bootstrap/profiles/{profile}/providers.php` |
| **`bootstrap/profiles/full/`** | Повна збірка (Config, PathManager, усі extensions) |
| **`bootstrap/profiles/minimal/`** | API-only: HttpKernel + Http, без Config/PathManager |
| **`bootstrap/shared/path-map.php`** | Спільний pathMap для профілів, що використовують PathManager |

**Порядок роботи:** layer providers у `bootstrap/profiles/full/providers.php`. «Хочу X → додаєш/редагуєш відповідний `*LayerProvider`».

### Три шари збірки

```
bootstrap/app.php                    → APP_PROFILE, завантаження profile providers
bootstrap/profiles/{name}/providers.php
bootstrap/shared/path-map.php        → pathMap для full profile
FoundationLayerProvider              → PathManager + Config
LoggingLayerProvider                 → DataMasker + LoggerMonolog (`DataMaskerFactory::fromContainer`)
ErrorHandlingLayerProvider           → Whoops + skeleton error renderers
ValidationLayerProvider              → ValidationRakit + FormRequest
DatabaseLayerProvider                → DatabaseEloquent + PaginationConfigurator
HttpLayerProvider                    → Casting, Session, CSRF, HttpKernel, Http extension
ConsoleLayerProvider                 → ConsoleSymfony
ViewLayerProvider                    → View + Twig
ApplicationRuntimeServiceProvider    → post-config runtime (timezone, …)
ApplicationComponentsServiceProvider → feature components (manifest modules)
```

**Glue** = конструктор. **Extension ServiceProvider** = блок конструктора з явними параметрами. **Component** = plug-in module (routes, views, migrations, …) поверх уже зібраного стеку.

### Розподіл glue: layer vs config vs routes

> Glue переїхав з core у skeleton — це ціна гнучкості. Правила нижче тримають його передбачуваним; app code лишається тонким.

**Три рівні (зверху вниз):**

```
providers.php     → ЩО увімкнено (стек)
config/           → ЯК налаштовано (значення)
LayerProvider     → ЯК з’єднано (wiring)
routes/*.php      → ЩО обробляє HTTP (surface)
src/App/          → бізнес-логіка додатку
Extensions        → бібліотеки (без app/config)
Core              → dispatch + routing contract
```

**Правило одного погляду:** щодня — **routes + controllers**; стек — **`providers.php`**; layer/config — лише при зміні capability.

#### `bootstrap/profiles/{profile}/providers.php`

| Дозволено | Заборонено |
|-----------|------------|
| Список `new *LayerProvider(...)` (instances) | Читання config |
| Порядок layer-ів (за потреби) | `new *ServiceProvider(...)` extension напряму |
| `$root`, `path-map.php` для Foundation | Business logic, middleware lists |
| `ApplicationRuntimeServiceProvider` | Умови «якщо prod — …» |

`App::registerServiceProviders()` приймає `ServiceProviderInterface` або `callable(): ServiceProviderInterface` (factory без container arg).

Новий продукт = новий **profile** (інший список layers), не fork extensions.

#### `config/*.php`

| Дозволено | Заборонено |
|-----------|------------|
| Дані: paths (відносні), flags, lists, імена файлів | `$container->get(...)` |
| `'routes.list' => ['routes/web.php']` | `new ServiceProvider(...)` |
| Env overlay `config/{APP_ENV}/` | Middleware class lists |
| Один файл або багато — без різниці для правил | Виклики PathManager |

Absolute paths будує **layer** через `PathManager` (`rootList`, `rootMap`, `get(PathName::…)`).

#### `*LayerProvider`

| Дозволено | Заборонено |
|-----------|------------|
| `$config->get(ConfigKey::…)` → constructor extension provider | Routes, controllers |
| `$pathManager->rootList()` / `rootMap()` / `get(PathName::LOGS, …)` | Business rules |
| `ContainerDependency::get($container, Interface::class)` — typed resolve у `boot()` і factory closures | `$container->get()` + `@var` у glue |
| `new XxxServiceProvider(...)` з явними args | Middleware stack для web |
| App glue: error renderers, resolver chain, `DataMaskerFactory` | «God layer» (новий domain без нового layer) |
| Один domain на layer | `$container->get()` cross-extension у extension-класах |

**Новий layer** — новий **infrastructure domain** (Telemetry, Locale, Components). **Не новий layer** — зміна значення (log level, db host) → лише config.

#### `routes/*.php`

| Дозволено | Заборонено |
|-----------|------------|
| `$router->lazyMiddlewares([...])` — явний список класів | Реєстрація service providers |
| `$router->get/post/...`, route names, groups | Wiring container |
| API vs web — різні файли та middleware | Inline closures з business logic |

Session/CSRF middleware — у **web** routes, не в api. Приклад admin-web surface: `routes/web1.php` (middleware chain + login/home).

#### `src/App/` (controllers, middleware, requests)

| Дозволено | Заборонено |
|-----------|------------|
| Controllers, FormRequests, app middleware | `addServiceProvider` |
| App-specific middleware (`ShareViewDataMiddleware`) | Дублювати wiring з layers |
| Domain services | Читати config напряму (краще inject) |

Reusable middleware (`VerifyCsrfTokenMiddleware`) — у extension; app-specific — у `Concept\App\Middleware\`.

#### Extensions vs Components

| | Extension | Component |
|--|-----------|-----------|
| Що | Generic library (Http, Validation, CSRF) | Feature module (ACL, Admin) |
| Config | Ні — лише constructor params | Manifest + свої routes/views |
| Glue | `*LayerProvider` | `ComponentsLayerProvider` (майбутнє) або profile |
| Приклад | `ValidationServiceProvider` | ACL + `HandleAccessDeniedMiddleware` у routes |

#### Decision tree

```
Змінюю значення (host, log file, debug)?     → config/
Змінюю стек (додати DB, прибрати View)?     → providers.php (± layer)
Змінюю wiring extension (paths, deps)?      → LayerProvider (один domain)
Змінюю URL / middleware на маршрутах?     → routes/
Змінюю поведінку endpoint?                  → Controller / FormRequest / app middleware
Нова reusable бібліотека?                   → Extension (+ layer glue)
Нова feature area (admin, billing)?         → Component (+ routes)
```

#### Anti-patterns

| ❌ | ✅ |
|----|-----|
| Middleware list у `HttpLayerProvider` | Middleware у `routes/web.php` |
| `DatabaseLayerProvider` знає про ACL | ACL middleware у routes + Component |
| Config містить middleware stacks | Middleware у routes; interceptors у config — лише якщо layer документує |
| Новий проєкт копіює 8 layers | Новий profile з існуючих layers |
| Extension читає `ConfigInterface` | Layer читає config → передає params |
| Monolith `ApplicationServiceProvider` | Розбиті layers + profile |

#### Map: full profile layers

| Layer | Config (приклад) | Не його зона |
|-------|------------------|--------------|
| Foundation | path-map, config dir | routes |
| Logging | `masking.*`, `log.*` | validation rules |
| ErrorHandling | — (app renderers у glue) | middleware stack |
| Validation | `validator.*`, `form-request.*` | twig paths |
| Database | `db.*`, `migrations.*` | session |
| Http | `caster.*`, `session.*`, `routes.*` | view extensions |
| Console | `console.*`, `commands` | HTTP middleware |
| View | `view.*` | DB connection |

#### Profiles (recipes)

| Profile | Layers (ідея) | Routes |
|---------|---------------|--------|
| `minimal` | Http only (hardcoded glue) | `api.php` |
| `api` | Foundation, Logging, Error, Validation, Database, Http (без Session) | `api.php` |
| `admin` | … + Session/CSRF, View, Components | `web.php` + ACL middleware |
| `full` | усі layers (див. `profiles/full/providers.php`) | `web.php` + `api.php` |

Profile відрізняється **manifest + routes**, не fork extensions.

#### Чеклист PR (glue review)

- [ ] Wiring не з’явився у controller/route?
- [ ] Config лишився data-only?
- [ ] Немає cross-extension `$container->get()` у extension-класах?
- [ ] Зміна стеку = ±1 рядок у `providers.php`?
- [ ] Web/api middleware — у правильному route file?

### Правила залежностей extensions (цільовий стан)

Старе товсте ядро мало **жорсткі зв'язки** між частинами; після декомпозиції артефакти лишаються — extension тягне з container «чужі» сервіси. **При нарощенні мінімального стеку — виправляти.**

| Дозволено | Заборонено (ціль) |
|-----------|-------------------|
| Provider constructor: paths, flags, arrays, closures, готові instances | Extension-клас `$container->get(ForeignExtension\Service::class)` |
| Provider `register()`: реєструє **власні** контракти extension | Extension читає `ConfigInterface` |
| `$container->get()` **лише власних** контрактів extension або **core/PSR** контрактів, без яких extension не може існувати | `$container->has(ForeignInterface)` + optional get (service locator) |
| Glue передає **optional cross-extension** deps через `?Closure $…Factory` у constructor provider-а (напр. `dataMaskerFactory`) | Extension знає про skeleton app-класи |

**ServiceProvider `register()` і container lookup:**

- **Constructor params** — primary спосіб конфігурації extension (paths, debug, lists, handlers).
- **`$container->get()` у factory closure provider-а** — допустимо для **відомих контрактів** того ж extension або стабільних core/PSR типів (`Router`, `RequestContextInterface`), якщо це wiring всередині provider-а, а не business-logic класу.
- **Сервіси extension** — залежності через **constructor**, не через `$container->get()` у методах.

**App-specific glue** (приклад: `AppExceptionReporter`, `TwigHttpErrorRenderer`) — у skeleton (`Concept\App\`), не в extension. Extension отримує `ExceptionReporterInterface` / `HttpErrorRendererInterface` через constructor provider-а або closure з glue.

### Приклади «конструктора» (mental model)

| «Хочу…» | Glue робить |
|---------|---------------|
| Логування помилок | `new LoggerMonologServiceProvider(logFilePath:, level:, …)` → glue передає `ExceptionReporterInterface` у `ErrorHandlerWhoopsServiceProvider` |
| Гарні сторінки помилок | skeleton: `TwigHttpErrorRenderer` → glue: `httpErrorRenderer: fn() => …` у Whoops provider (lazy closures) |
| Шаблонізатор | `ViewServiceProvider(paths:, extensions:)` + `TwigViewServiceProvider(viewsPath:, cacheDir:, debug:)` |
| Розширення Twig | `extensions: [TwigAppExtension::class, …]` у `ViewServiceProvider` constructor |
| FormRequest + validation | glue збирає resolver chain + `ValidationServiceProvider` + `FormRequestServiceProvider`; instances/resolvers передає в `HttpKernelServiceProvider` |
| Маскування логів | `DataMaskerServiceProvider(patterns:, …)` + `dataMaskerFactory: fn() => …` у `LoggerMonologServiceProvider` / `ValidationServiceProvider` |

### Lazy-first (узгоджено)

**Ціль:** glue лише реєструє definitions; сервіси **не прокидаються наперед**. Resolve — при першому `get()` (request, exception, CLI entry).

| Шар | Що робить |
|-----|-----------|
| **`boot()`** | `$container->add(...)`, `addServiceProvider(...)`, порожні registries, handler chains з closures. **Без** `$container->get()` чужих сервісів |
| **`register()`** | `$container->add(..., fn() => ...)` — lazy factories |
| **Resolve** | Перший реальний `get()` — wiring залежностей у closure |

**Порядок `addServiceProvider()` у glue не є контрактом**, якщо дотримано lazy: усі блоки додані до першого resolve.

**Коли порядок / eager все ж потрібен:**

- `boot()` одразу робить `$container->get()` (напр. `ComponentsServiceProvider` → `Router`, `ViewRegistry`)
- glue між блоками робить eager `get()` (напр. append у registry після попереднього provider)
- runtime-порядок у масивах: resolver chain, middleware/interceptors
- **early bootstrap** окремо: `EarlyWhoopsBootstrap` у `bootstrap/app.php` до повного container

**Ціна lazy:** помилки конфігурації — fail late (на першому використанні). Компенсація: dev/CLI smoke після змін glue (див. MIGRATION_BACKLOG P0).

**Шари glue (для читабельності, не жорсткий порядок):** Config → Logger/Event → infrastructure extensions → app error glue → Console/Components → HttpKernel (resolvers/interceptors як constructor params).

### Legacy coupling — що шукати при нarощенні

При додаванні extension у full glue перевіряти:

- `$container->get()` на типи інших extensions без явного контракту в constructor provider-а
- `EventDispatcherResolver::optional($container)` — допустимо для optional telemetry, але не заміна явної залежності для critical path
- Resolvers з `$container` у constructor (`FormRequestArgumentResolver`, `TypedRouteParameterArgumentResolver`) — glue **передає** їх у `HttpKernelServiceProvider`, не core
- Hardcoded assumptions «ViewRegistry вже є», «Router вже зареєстрований» — документувати в boot order

### Профілі збірки

Named profiles (`minimal`, `full`, майбутні `api` / `admin`) + layer providers. Див. таблицю **Profiles (recipes)** у «Розподіл glue». Legacy `ApplicationServiceProvider1`, `providers1.php` — видалено.

### Що виноситься в Extensions (skeleton `src/Extensions/`)

| Extension | Відповідальність | Статус |
|-----------|------------------|--------|
| **Http** | `Protocol/*`, `RequestFormat`, `UrlGenerator`, `ResponseFactory`, `RouteDescriptor` | ✅ базовий шар |
| **FormRequest** | `FormRequestInterface`, factory, `FormRequestArgumentResolver` | ✅ |
| **Validation** | `magewirephp/validation`, `Rule`/`RuleInterface`, exceptions | ✅ |
| **Casting** | Valinor `Caster`, `DtoInterface`, `Dto`, `TypedRouteParameterArgumentResolver` | ✅ |
| **View** | `ViewResponseFactory`, Twig/Plates | — |
| **Console** | CLI commands (`route:list`, db, …) | — |
| **Config** (фінальний етап) | читання конфігів, прокидання параметрів | — |
| (майбутні) | Session, Database, … | з `storage/src/` |

### Структура extension (composer package)

Кожне розширення — **окремий composer-пакет** `php-concept/extension-{name}`.
У skeleton — path repository до `src/Extensions/{Name}/`.

```
src/Extensions/{Name}/              # корінь пакета (майбутній окремий repo)
├── composer.json                   # name: php-concept/extension-{name}
└── src/                            # PSR-4 root: Concept\Extensions\{Name}\
    ├── {Name}ServiceProvider.php   # єдиний entry point (у корені src/, не в Providers/)
    ├── Contracts/                  # ВСІ публічні інтерфейси extension
    ├── {Feature}/                  # реалізації по доменах (Routing, Response, …)
    │   └── ...
    ├── Protocol/                   # константи, enums (за потреби)
    └── ...
```

**Правила:**

| Що | Де |
|----|-----|
| Service provider | `src/{Name}ServiceProvider.php` — namespace `Concept\Extensions\{Name}` |
| Інтерфейси | `src/Contracts/` — **не** розкидати по `Routing/Contracts/` |
| Реалізації | `src/{Feature}/` — `Routing/`, `Response/`, `Requests/`, … |
| Класи в корені `src/` | тільки provider — **не** фабрики, не сервіси |

**Skeleton підключення** (`composer-dev.json`):

```json
"repositories": [
  { "type": "path", "url": "src/Extensions/Http", "options": { "symlink": true } }
],
"require": {
  "php-concept/extension-http": "@dev"
}
```

**Bootstrap** — реєструє `Concept\Extensions\{Name}\{Name}ServiceProvider` після core providers.

**FormRequest** залежить від **Validation** (`php-concept/extension-validation`); skeleton реєструє Validation **перед** FormRequest.

### Validation extension (`php-concept/extension-validation`)

Namespace: `Concept\Extensions\ValidationRakit\` → `src/Extensions/ValidationRakit/src/`

```
Validation/
├── composer.json
└── src/
    ├── ValidationServiceProvider.php
    ├── Contracts/         ValidatorInterface, ValidationInterface, RuleInterface
    ├── Rules/             Rule (abstract base for custom rules)
    ├── Adapters/          ValidationAdapter, RuleAdapter (Rakit\\Validation namespace)
    ├── Validator.php
    └── Exceptions/        ValidationException, ValidationLogicException, ValidationCastException
```

### FormRequest extension (`php-concept/extension-form-request`)

Namespace: `Concept\Extensions\FormRequest\` → `src/Extensions/FormRequest/src/`

```
FormRequest/
├── composer.json
└── src/
    ├── FormRequestServiceProvider.php
    ├── Contracts/         FormRequestInterface, FormRequestFactoryInterface
    ├── Requests/          FormRequest (abstract base)
    ├── Factory/           FormRequestFactory
    └── Routing/           FormRequestArgumentResolver
```

Залежить від `extension-validation` та `extension-casting` (`DtoInterface` для `FormRequest::toDto()`).

### Casting extension (`php-concept/extension-casting`)

Namespace: `Concept\Extensions\CastingValinor\` → `src/Extensions/CastingValinor/src/`

```
Casting/
├── composer.json
└── src/
    ├── CastingServiceProvider.php
    ├── Contracts/         CasterInterface, DtoInterface
    ├── Caster.php
    ├── Dto/               Dto (base class for mapped objects)
    ├── Exceptions/
    └── Routing/           TypedRouteParameterArgumentResolver
```

### Http extension (`php-concept/extension-http`)

Namespace: `Concept\Extensions\Http\` → `src/Extensions/Http/src/`

```
Http/
├── composer.json
└── src/
    ├── HttpServiceProvider.php
    ├── Contracts/
    │   ├── ResponseFactoryInterface.php
    │   └── UrlGeneratorInterface.php
    ├── Protocol/          HttpHeader, HttpStatusCode, HttpValue, HttpMethod, UrlComponent
    ├── Requests/          RequestAttribute, RequestFormat
    ├── Routing/           UrlGenerator, RouteDescriptor
    └── Response/          ResponseFactory
```

**Споживачі `RouteDescriptor`:** Console (`route:list`), Admin, DebugBar.

**Request у методах:** `ResponseFactory::back($request)`, `UrlGenerator::url($request, …)` — без container lookup.

Provider: `Concept\Extensions\Http\HttpServiceProvider` (core kernel — `Concept\Core\Providers\Http\HttpKernelServiceProvider`).

### Що робить skeleton («клей»)

- Реєструє providers у `bootstrap/profiles/{profile}/providers.php`
- Підключає extensions і передає `$resolvers` / `$interceptors` у `HttpKernelServiceProvider`
- Містить app code: controllers, routes, app-specific providers

## Argument resolving — узгоджена модель

`RouteStrategy::resolveParameter()` — **тільки chain**, без monolithic switch:

```php
foreach ($this->resolvers as $resolver) {
    if ($resolver->supports($parameter, $vars)) {
        return $resolver->resolve($parameter, $request, $vars);
    }
}
// fallback: default value → null
```

### Порядок резолверів (важливо!)

**Ядро не збирає chain** — лише приймає `$resolvers` у `HttpKernelServiceProvider` у заданому порядку.
Skeleton (`bootstrap/profiles/full/providers.php` або minimal) описує повний ланцюжок явно.

Рекомендований порядок (skeleton glue):

1. `FormRequestArgumentResolver` — extension FormRequest (коли підключено)
2. `ServerRequestArgumentResolver` — core class
3. `TypedRouteParameterArgumentResolver` — extension Casting
4. `RouteParameterArgumentResolver` — core class
5. fallback у RouteStrategy — default value / null

Потрібен resolver після `RouteParameter` — додаєш у масив після нього, без змін ядра.

### Архітектурні рішення (узгоджені з користувачем)

- **FormRequest** → extension, ядро не знає про `FormRequestInterface`
- **ServerRequest** → resolver-клас у core, реєструється `HttpKernelServiceProvider` (не hardcode в RouteStrategy)
- **castValue / typed route params** → extension Casting, базовий route param без casting — у core
- **Config** → не використовувати `ConfigInterface` під час міграції; явні параметри; Config extension — в кінці
- **Enriched request** → лише через resolvers/параметри handler-а; `prepareRequest` додає route vars як attributes, **без** `container->add(ServerRequestInterface)`. Container request — тільки entry point для `App::handle()`. Не використовувати `RequestProxy` у core.

## Старе ядро та конфіги (reference)

**Код:** `/var/www/concept-core-2/storage/src/`

**Конфіги:** `/var/www/concept-core-2/storage/config/` — PHP-масиви старого додатку (`app`, `db`, `session`, `view`, `routes`, … + `dev/`, `production/`)

Ключовий файл для argument resolving: `storage/src/Http/Routing/RouteStrategy.php`

Там monolithic `resolveParameter()` з:
1. FormRequest + validate
2. ServerRequest
3. Route vars + `CasterInterface::cast()`
4. Default value

**Не копіювати 1:1** — переносити логіку в окремі `ArgumentResolverInterface` implementations.

## Нове ядро (active) — поточні файли

```
core-2/src/
├── App.php
├── Http/
│   ├── Contracts/
│   │   ├── ArgumentResolverInterface.php
│   │   └── RouteInterceptorInterface.php
│   └── Routing/
│       ├── RouteStrategy.php
│       └── Resolvers/
│           ├── ServerRequestArgumentResolver.php
│           └── RouteParameterArgumentResolver.php
└── Providers/
    └── Http/
        └── HttpKernelServiceProvider.php    # routePaths, $resolvers, $interceptors — явні параметри
```

## Skeleton — поточні файли

```
bootstrap/app.php          → APP_PROFILE, register profile providers
bootstrap/profiles/full/providers.php
src/App/Providers/Layers/  → Foundation, Logging, ErrorHandling, Validation, Database, Http, Console, View
routes/web.php             → GET / → IndexController::index
src/App/Controllers/IndexController.php
src/App/Providers/TestServiceProvider.php
src/Extensions/
  Http/                 → ✅ Protocol, UrlGenerator, ResponseFactory, RequestFormat
  Validation/           → ✅ magewirephp/validation, Rule, exceptions
  FormRequest/          → ✅ factory, resolver, abstract FormRequest
  Casting/              → ✅ Caster, DtoInterface, Dto, TypedRouteParameterArgumentResolver
config/routes.php          → skeleton config (поки не використовується; route paths — явно в providers.php)
```

**Autoload:** extensions — окремі composer-пакети в `src/Extensions/{Name}/`, підключені через path repository у `composer-dev.json`.

## Поточний стан міграції

### ✅ Зроблено

- [x] Тонке ядро: `App`, `HttpKernelServiceProvider`, `RouteStrategy` з chain resolvers
- [x] Контракт `ArgumentResolverInterface` (`supports` отримує `$vars`)
- [x] `ServerRequestArgumentResolver` + `RouteParameterArgumentResolver` у core
- [x] Дефолтні resolvers підключені в `HttpKernelServiceProvider`
- [x] **Http extension**: Protocol, RequestFormat, UrlGenerator, ResponseFactory, RouteDescriptor + provider
- [x] **Casting extension**: Caster (Valinor), DtoInterface, Dto, TypedRouteParameterArgumentResolver
- [x] **Validation extension**: magewirephp/validation, Rule, exceptions
- [x] **FormRequest extension**: factory, resolver, abstract FormRequest
- [x] **Validation + FormRequest у glue**: `ValidationServiceProvider`, `FormRequestServiceProvider`, `new FormRequestArgumentResolver($container)` у resolver chain
- [x] `HandleHttpErrorMiddleware` — 404, `HttpErrorException`, 500 (без `ValidationException`)
- [x] `HandleValidationExceptionMiddleware` — `ValidationException` → 422 JSON або redirect + flash (web)
- [x] Тест: `POST /test/echo` + `TestEchoRequest` (name, email)
- [x] **Console extension**: `ConsoleSymfonyServiceProvider`, `route:list`
- [x] **Session + CSRF**: `SessionServiceProvider`, `CsrfServiceProvider`, middleware chain у `web.php`
- [x] **Validation redirect flow**: `HandleValidationExceptionMiddleware` + flash + форма на `/` з errors/old
- [x] **Json middleware**: `routes/api.php`, `ParseJsonBodyMiddleware`, `ForceJsonResponseMiddleware`; CSRF/session middleware лише на web group
- [x] **DataMasker extension**: glue → `dataMaskerFactory` у log-related providers (не `$container->has()` у extension)
- [x] **Database extension**: `DatabaseEloquentServiceProvider`, `PaginationConfiguratorServiceProvider`, db CLI, `GET /test/db`
- [x] **Config extension**: `ConfigServiceProvider` + `config/` → glue
- [x] **Profiles**: `APP_PROFILE` у `bootstrap/app.php`, `minimal` / `full` у `bootstrap/profiles/`
- [x] **Layer glue (full profile)**: Foundation, Logging, ErrorHandling, Validation, Database, Http, Console, View — monolith `ApplicationServiceProvider` видалено
- [x] **`DataMaskerFactory`**: спільна factory для Validation/DB/Logger glue
- [x] Skeleton bootstrap працює з core через symlink
- [x] `IndexController::index()` — повертає `Response`, не `int` від `write()`

### 🔲 Наступні кроки (constructor build — порядок роботи)

> **Components** — відкладено до повного проходження всіх extensions (не чіпати зараз).

1. **Event / Telemetry** — окремий layer, не в full поки не готові
2. **Profiles** — `api` та інші між minimal і full
3. **Boot validation** — dev/CLI smoke після зборки

## Команди

```bash
# Skeleton
cd /var/www/concept-skeleton-dev-2
composer install --no-interaction   # якщо потрібно (composer-dev.json → composer.json)
vendor/bin/phpstan

# Core (окремий vendor)
cd /var/www/concept-core-2
vendor/bin/phpstan

# Перевірити symlink
readlink -f /var/www/concept-skeleton-dev-2/vendor/php-concept/core-2
```

## Конвенції коду

- PHP 8.4, `declare(strict_types=1);`
- Namespace core: `Concept\Core\`
- Namespace skeleton app: `Concept\App\`
- Namespace extensions: `Concept\Extensions\{ExtensionName}\`
- **Нові extensions** — composer-пакети в `src/Extensions/{Name}/` (див. «Структура extension»)
- PHPStan level 9
- Мінімальний diff — не переносити зайве зі старого ядра
- Ядро **не залежить** від extensions (тільки PSR + League + Laminas)
- Extensions залежать від core contracts
- **Config читає лише glue** — extensions отримують значення через constructor provider-ів, не `ConfigInterface` всередині extension
- **Повідомлення винятків** — текст у `private const string ERR_*` класу; виняток через `use` + `throw new RuntimeException(...)`, не `throw new \RuntimeException('...')` inline. Якщо рядок константи вміщується в **120 символів** — оголошувати в один рядок, без переносу після `=`
- **Імена класів** — без префікса `Container` (`FormRequestFactory`, `FormRequestArgumentResolver`, `TypedRouteParameterArgumentResolver`); lazy-отримання залежностей — всередині класу, не в назві
- **Arrow functions** — без пробілу після `fn`: `fn()`, `fn(): Type`, `fn($x): Type`, `static fn(Route $a, Route $b): int`. Не `fn ()`, не `fn ($x)`.
- **Anonymous functions** — без пробілу після `function`: `function()`, `function($x)`, `function() use ($c): Type`. Не `function ()`, не `function ($x)`.
- **Літерали в app glue** (layer providers, `bootstrap/*.php`) — bootstrap-only values (pathMap keys); cache/log file names та подібне — у `config/` + `ConfigKey`, не `private const` у glue
- **Container resolve у glue** — `ContainerDependency::get($container, Interface::class)` замість `$container->get()` + `@var` у layer providers і factory closures
- Не комітити без явного запиту користувача
- Не створювати markdown/docs без запиту (крім цього AGENTS.md)

## Entry point (request lifecycle)

```
public/index.php
  → bootstrap/app.php (App + providers)
  → App::handle()
    → container request (globals) → Router::dispatch($request)
      → RouteStrategy::invokeRouteCallable()
        → interceptors (request без route attributes; є $route)
        → prepareRequest → enriched request (attributes only, not in container)
        → resolveArguments (enriched request → resolvers → handler params)
        → invoke controller
  → SapiEmitter
```

### Request: container vs enriched

| Request | Джерело | Route attributes |
|---------|---------|------------------|
| Container `ServerRequestInterface` | `HttpKernelServiceProvider`, globals | Ні — лише для `App::handle()` entry |
| Enriched | `prepareRequest()` → resolvers → handler | Так |

Extensions (FormRequest, ResponseFactory): отримують `$request` **явно** з resolver/параметра методу, не `$container->get(ServerRequestInterface::class)`.

## Ключові контракти

### ArgumentResolverInterface

```php
public function supports(ReflectionParameter $parameter, array $vars): bool;
public function resolve(ReflectionParameter $parameter, ServerRequestInterface $request, array $vars): mixed;
```

### RouteStrategy constructor

```php
public function __construct(
    private readonly array $resolvers = [],
    private readonly array $interceptors = [],
) {}
```

Resolvers передаються з skeleton у `HttpKernelServiceProvider` як **повний упорядкований** масив.

---

*Останнє оновлення: 2026-07-04 — lazy-first, модель «конструктора», incremental build.*
