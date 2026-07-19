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

**Шляхи:** `PathManager` + `PathName` constants; `pathMap` у `bootstrap/path-map.php`.

**Логи:** config — `log.file` / `db.log_file` / `validator.log_file` (ім’я файлу під `PathName::LOGS`); glue → absolute шлях через `$pathManager->get(PathName::LOGS, $fileName)`.

**Reference-конфіги** старого додатку: `core-2/storage/config/` — довідник ключів/структури при переносі.

**Не робити:**
- не додавати `ConfigInterface` у core
- не підключати `storage/config/` старого ядра напряму
- не тягнути `ConfigInterface` всередину extension-класів (тільки glue)

## Збірка додатку — Bootstrap + Concept Stack (узгоджено)

> Мета: збирати додаток як конструктор. Stack — fluent capabilities з explicit values. Config/Path — optional app foundation, не частина stack.

### Артефакти

| Артефакт | Роль |
|----------|------|
| **`bootstrap/app.php`** | Entry: `App::create()` + один `registerServiceProviders([...])` |
| **`bootstrap/path-map.php`** | PathManager map keys → relative dirs |
| **`src/App/Bootstrap/*Bootstrap`** | Кроки зборки (`provides: false`): early Whoops, Foundation, Stack recipe, Components, Runtime |
| **`ConceptStack`** (`php-concept/stack`) | Fluent builders → list of extension `*ServiceProvider` |
| **`config/*.php`** | Data-only values; glue читає й передає в stack |
| **`routes/*.php`** | HTTP surface + middleware lists |

### Порядок bootstrap (контракт)

```
EarlyErrorHandlingBootstrap     → Whoops (до решти; APP_DEBUG з $_ENV)
FoundationBootstrap             → PathManager + Config у container
ApplicationStackBootstrap       → Config/Path → ConceptStack (включно з Components brick)
ApplicationRuntimeBootstrap     → timezone тощо
```

`withX()` на stack **одразу** реєструє capability; `end()` немає. Окремі гілки від `$stack`.

### Три рівні (зверху вниз)

```
bootstrap/app.php              → порядок Bootstrap steps
ApplicationStackBootstrap      → ЩО увімкнено в ConceptStack + ЯК з’єднано з config
config/                        → ЯК налаштовано (значення)
routes/*.php                   → ЩО обробляє HTTP (surface)
src/App/                       → бізнес-логіка, app middleware
Extensions (окремі repos)      → бібліотеки (без app/config)
Core                           → dispatch + routing contract
```

**Правило одного погляду:** щодня — **routes + controllers**; стек — **`ApplicationStackBootstrap`**; config — лише зміна значень.

#### `bootstrap/app.php`

| Дозволено | Заборонено |
|-----------|------------|
| Список `new *Bootstrap(...)` (порядок) | Читання повного config / збірка stack recipe |
| `$root`, `path-map.php`, early `$_ENV['APP_DEBUG']` | Business logic, middleware lists |
| Один виклик `registerServiceProviders([...])` | Умови «якщо prod — …» у entry |

`App::registerServiceProviders()` приймає `ServiceProviderInterface` або `callable(): ServiceProviderInterface`.

#### `config/*.php`

| Дозволено | Заборонено |
|-----------|------------|
| Дані: paths (відносні), flags, lists, імена файлів | `$container->get(...)` |
| `'routes.list' => ['routes/web.php']` | `new ServiceProvider(...)` |
| Env overlay `config/{APP_ENV}/` | Middleware class lists |
| Один файл або багато | Виклики PathManager |

Absolute paths будує **glue** (`ApplicationStackBootstrap`) через `PathManager`.

#### `*Bootstrap` (app glue)

| Дозволено | Заборонено |
|-----------|------------|
| `$config->get(ConfigKey::…)` → explicit stack / SP params | Routes, controllers |
| `$pathManager->rootList()` / `rootMap()` / `get(PathName::…)` | Business rules |
| `ContainerDependency::get($container, Interface::class)` | `$container->get()` + `@var` у glue |
| `ConceptStack` + `foreach ($stack->providers() as …)` | Middleware stack для web |
| Early Whoops handlers (app `FallbackFileHandler`) | Тягнути Config у Early (лише `$_ENV`) |

**Нова capability** — brick у stack + рядок у `ApplicationStackBootstrap`. **Зміна значення** — лише `config/`.

#### `routes/*.php`

| Дозволено | Заборонено |
|-----------|------------|
| `$router->lazyMiddlewares([...])` — явний список класів | Реєстрація service providers |
| `$router->get/post/...`, route names, groups | Wiring container |
| API vs web — різні файли та middleware | Inline closures з business logic |

Session/CSRF middleware — у **web** routes, не в api.

#### `src/App/` (controllers, middleware, requests)

| Дозволено | Заборонено |
|-----------|------------|
| Controllers, FormRequests, app middleware | `addServiceProvider` |
| App-specific middleware (`ShareViewDataMiddleware`) | Дублювати wiring з Bootstrap/stack |
| Domain services | Читати config напряму (краще inject) |

Reusable middleware (`VerifyCsrfTokenMiddleware`) — у extension; app-specific — у `Concept\App\Middleware\` (лишаються в додатку).

#### Extensions vs Components

| | Extension | Component |
|--|-----------|-----------|
| Що | Generic library (Http, Validation, CSRF) | Feature module (ACL, Admin) |
| Config | Ні — лише constructor params | Ideal: explicit params; зараз частина ще читає Config (борг) |
| Glue | `ApplicationStackBootstrap` / stack brick | Components brick (`withComponents`) |
| Приклад | `ValidationServiceProvider` | ACL + middleware у routes |

#### Decision tree

```
Змінюю значення (host, log file, debug)?     → config/
Змінюю стек (додати DB, прибрати View)?     → ApplicationStackBootstrap
Змінюю порядок зборки / early Whoops?       → bootstrap/app.php (± Bootstrap)
Змінюю URL / middleware на маршрутах?       → routes/
Змінюю поведінку endpoint?                  → Controller / FormRequest / app middleware
Нова reusable бібліотека?                   → Extension (+ stack brick)
Нова feature area (admin, billing)?         → Component (+ routes)
```

#### Anti-patterns

| ❌ | ✅ |
|----|-----|
| Middleware list у stack / Bootstrap | Middleware у `routes/web.php` |
| Stack читає `ConfigInterface` | Glue читає config → explicit stack params |
| Extension читає `ConfigInterface` | Constructor params з glue |
| `ConfigFactory` + окремий early config instance | Один Config у container через Foundation |
| Два `registerServiceProviders` «бо Foundation перший» | Один список: Early → Foundation → Stack → … |

#### Profiles (майбутнє, за потреби)

Новий продукт = інший список Bootstrap / інший stack recipe, не fork extensions. Зараз — один full entry у `bootstrap/app.php`.

### Проєкти extensions / stack

| Проєкт | Шлях | Роль |
|--------|------|------|
| **Stack** | `/var/www/concept-stack` | Fluent ConceptStack + bricks |
| **Extensions** | `/var/www/concept-extensions/extension-*` | Окремі composer-пакети |

Skeleton підключає їх через path repositories у `composer-dev.json` (symlink).

**Glue** = конструктор. **Extension ServiceProvider** = блок з явними параметрами. **Component** = plug-in module поверх стеку.

#### Чеклист PR (glue review)

- [ ] Wiring не з’явився у controller/route?
- [ ] Config лишився data-only?
- [ ] Немає cross-extension `$container->get()` у extension-класах?
- [ ] Зміна стеку = зміна в `ApplicationStackBootstrap` / brick, не в core?
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

**App-specific glue** (приклад: early `FallbackFileHandler`) — у skeleton (`Concept\App\`). Stack recipes для reporter/renderer — у `Concept\Stack\Bricks\ErrorHandling\`; Whoops extension отримує лише контракти через stack wiring.

### Приклади «конструктора» (mental model)

| «Хочу…» | Glue робить |
|---------|---------------|
| Логування помилок | `withErrorHandling()->reportToLog()` → `LoggerExceptionReporter` |
| HTTP error pages (production) | `renderHtmlErrorPage($path)` → `ViewHttpErrorRenderer` (JSON по Accept) або `renderJson()` |
| Debug uncaught | `showDebugExceptionPage()` + `debug(true)` |
| Route not found (404) | `HandleNotFoundMiddleware` → `ExceptionReporter` + renderer з stack |
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
- **early bootstrap** окремо: `EarlyErrorHandlingBootstrap` першим у `registerServiceProviders` (до Foundation/stack)

**Ціна lazy:** помилки конфігурації — fail late (на першому використанні). Компенсація: `composer boot-smoke` / `php bin/boot-smoke.php` після змін glue.

**Шари glue (для читабельності):** Early Whoops → Foundation (Config/Path) → ApplicationStack (ConceptStack + Components brick) → Runtime.

### Legacy coupling — що шукати при нarощенні

При додаванні extension у full glue перевіряти:

- `$container->get()` на типи інших extensions без явного контракту в constructor provider-а
- `EventDispatcherResolver::optional($container)` — допустимо для optional telemetry, але не заміна явної залежності для critical path
- Resolvers з Closure factories (`FormRequestArgumentResolver`, `TypedRouteParameterArgumentResolver`) — glue **передає** їх у `HttpKernelServiceProvider`, не core
- Hardcoded assumptions «ViewRegistry вже є», «Router вже зареєстрований» — документувати в boot order

### Профілі збірки

Зараз один full entry (`bootstrap/app.php`). Окремі recipes (`api` / `admin`) — за потреби, інший Bootstrap/stack list, не fork extensions.

### Що виноситься в Extensions (`/var/www/concept-extensions/extension-*`)

| Extension | Відповідальність | Статус |
|-----------|------------------|--------|
| **Http** | `Protocol/*`, `RequestFormat`, `UrlGenerator`, `ResponseFactory`, `RouteDescriptor` | ✅ |
| **FormRequest** | `FormRequestInterface`, factory, `FormRequestArgumentResolver` | ✅ |
| **Validation** | Rakit adapter, `Rule`/`RuleInterface`, exceptions | ✅ |
| **Casting** | Valinor `Caster`, `DtoInterface`, `Dto`, typed route resolver | ✅ |
| **View** / **ViewTwig** / **ViewPlates** | View registry + engines | ✅ |
| **Console** | CLI (`route:list`, db, …) | ✅ |
| **Config** / **PathManager** | config merge + paths | ✅ |
| **Session** / **Csrf** / **Json** / **DataMasker** / **Logger** / **Database** / **Event** / **Telemetry** / **Components** / **Whoops** | ✅ |

### Структура extension (composer package)

Кожне розширення — **окремий composer-пакет** `php-concept/extension-{name}` у `/var/www/concept-extensions/extension-{name}/`.

```
extension-{name}/                   # /var/www/concept-extensions/extension-{name}
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
  { "type": "path", "url": "../concept-extensions/extension-http", "options": { "symlink": true } },
  { "type": "path", "url": "../concept-stack", "options": { "symlink": true } }
],
"require": {
  "php-concept/extension-http": "@dev",
  "php-concept/stack": "@dev"
}
```

**Bootstrap** — `ApplicationStackBootstrap` реєструє extension providers через ConceptStack.

**FormRequest** залежить від **Validation**; stack Http brick підключає їх у правильному порядку (casting/validation перед http resolvers).

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

- Реєструє Bootstrap steps у `bootstrap/app.php`
- `ApplicationStackBootstrap` збирає `ConceptStack` з Config/Path і реєструє extension providers
- Містить app code: controllers, routes, `Concept\App\Middleware`, Components

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
Skeleton (`ApplicationStackBootstrap` / stack Http brick) описує повний ланцюжок явно.

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
bootstrap/app.php              → App + registerServiceProviders([Bootstrap…])
bootstrap/path-map.php         → PathManager map
src/App/Bootstrap/             → Early, Foundation, Stack, Components, Runtime
src/App/Middleware/            → app HTTP middleware (залишаються в додатку)
routes/web.php, api.php, …     → HTTP surface
config/*.php                   → data-only; glue → stack params
```

**Autoload:** extensions і stack — path repositories у `composer-dev.json` → `/var/www/concept-extensions/*`, `/var/www/concept-stack`.

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
- [x] **Validation + FormRequest у glue**: `ValidationServiceProvider`, `FormRequestServiceProvider`; `FormRequestArgumentResolver` через closures (factory + optional dispatcher), як Casting resolver
- [x] **Error handling**: Whoops-centric (report + PrettyPage debug / HttpErrorRenderer prod); `HandleNotFoundMiddleware` для route 404
- [x] `HandleValidationExceptionMiddleware` — `ValidationException` → 422 JSON або redirect + flash (web)
- [x] Тест: `POST /test/echo` + `TestEchoRequest` (name, email)
- [x] **Console extension**: `ConsoleSymfonyServiceProvider`, `route:list`
- [x] **Session + CSRF**: stack `withSession()->withCsrf()`; middleware chain у `web.php`
- [x] **Validation redirect flow**: `HandleValidationExceptionMiddleware` + flash + форма на `/` з errors/old
- [x] **Json middleware**: `routes/api.php`, `ParseJsonBodyMiddleware`, `ForceJsonResponseMiddleware`; CSRF/session middleware лише на web group
- [x] **DataMasker extension**: stack `withMasking()` + opt-in `LoggingBuilder::withMasking()` / validation / db
- [x] **Database extension**: `DatabaseEloquentServiceProvider`, `PaginationConfiguratorServiceProvider`, db CLI, `GET /test/db`
- [x] **Config extension**: `ConfigServiceProvider` + `config/` → Foundation (optional app glue; stack explicit values)
- [x] **Concept Stack** + `src/App/Bootstrap/*` (Early → Foundation → Stack з Components brick → Runtime)
- [x] Skeleton bootstrap працює з core через symlink
- [x] `IndexController::index()` — повертає `Response`, не `int` від `write()`
- [x] **Boot smoke** — `php bin/boot-smoke.php` / `composer boot-smoke` (container + Router, без DB)
- [x] **CI** — GitHub Actions (`.github/workflows/ci.yml`) + `composer ci` (phpstan + boot-smoke)

### 🔲 Наступні кроки

1. Components без прямого `ConfigInterface` (explicit params з glue)
2. Profiles / recipes поверх stack (за потреби)

## Команди

```bash
# Skeleton
cd /var/www/concept-skeleton-dev-2
composer install --no-interaction   # якщо потрібно (composer-dev.json → composer.json)
vendor/bin/phpstan
composer boot-smoke                 # або: php bin/boot-smoke.php
composer ci                         # phpstan + boot-smoke (локально / GitHub Actions)

# GitHub Actions: потрібен secret CI_PAT (read access до OlegGin/concept-core-2 і php-concept/*),
# якщо sibling-репозиторії приватні.

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
- **App Bootstrap vs ServiceProvider** — `Concept\App\Bootstrap\*Bootstrap`: кроки зборки (`provides: false`, side effects / реєстрація інших SP). `*ServiceProvider` — extension/core: надають контракти. Обидва йдуть через `App::registerServiceProviders()`.
- Namespace extensions: `Concept\Extensions\{ExtensionName}\`
- **Нові extensions** — composer-пакети в `/var/www/concept-extensions/extension-{name}/`
- PHPStan level 9
- Мінімальний diff — не переносити зайве зі старого ядра
- Ядро **не залежить** від extensions (тільки PSR + League + Laminas)
- Extensions залежать від core contracts
- **Config читає лише glue** — extensions отримують значення через constructor provider-ів, не `ConfigInterface` всередині extension
- **Повідомлення винятків** — текст у `private const string ERR_*` класу; виняток через `use` + `throw new RuntimeException(...)`, не `throw new \RuntimeException('...')` inline. Якщо рядок константи вміщується в **120 символів** — оголошувати в один рядок, без переносу після `=`
- **Імена класів** — без префікса `Container` (`FormRequestFactory`, `FormRequestArgumentResolver`, `TypedRouteParameterArgumentResolver`); lazy-отримання залежностей — всередині класу, не в назві
- **Arrow functions** — без пробілу після `fn`: `fn()`, `fn(): Type`, `fn($x): Type`, `static fn(Route $a, Route $b): int`. Не `fn ()`, не `fn ($x)`.
- **Anonymous functions** — без пробілу після `function`: `function()`, `function($x)`, `function() use ($c): Type`. Не `function ()`, не `function ($x)`.
- **Літерали в app glue** (`Bootstrap/*`, `bootstrap/*.php`) — bootstrap-only values (pathMap keys, early fallback path); cache/log file names — у `config/` + `ConfigKey`
- **Container resolve у glue** — `ContainerDependency::get($container, Interface::class)` замість `$container->get()` + `@var`
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

## Error handling (узгоджена модель)

**Правило:** необроблений `Throwable` → Whoops. Route 404 (League quirk) → `HandleNotFoundMiddleware`. Domain exceptions (Validation, CSrf) → свої middleware.

```
EarlyErrorHandlingBootstrap (перший у registerServiceProviders)
  → fatals під час подальшого boot

HTTP request:
  domain middleware (Validation, Csrf, …)
  → controller
  → необроблений Throwable → Whoops

  Route not found (League prepend):
    HandleNotFoundMiddleware → ExceptionReporter + ViewHttpErrorRenderer (stack recipe)

Whoops awake (ErrorHandlerWhoopsServiceProvider):
  1. ExceptionReporter
  2. debug → PrettyPage (web) / PlainText (cli) — renderer НЕ реєструється
  3. production web → HttpErrorRendererHandler → ViewHttpErrorRenderer
```

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

*Останнє оновлення: 2026-07-19 — Bootstrap + ConceptStack, без LayerProvider/profiles; boot-smoke.*
