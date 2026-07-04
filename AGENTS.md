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
| **Skeleton glue** | Читає `config/` (+ `.env` через Config extension) → передає значення в providers/extensions |

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
| **`ApplicationServiceProvider.php`** (active) | **Мінімальна проба** — перевірка передачі залежностей, один extension за раз |
| **`ApplicationServiceProvider1.php`** | **Reference full stack** — працююча повна збірка, але занадто велика для огляду контрактів |
| **`providers.php`** | Active entry point (мінімальний профіль) |
| **`providers1.php`** | Reference entry point (повний профіль + Config + Components) |

**Порядок роботи:** стартуємо з мінімального `ApplicationServiceProvider`, додаємо extension лише коли його контракт (constructor params, lazy wiring, залежності) зрозумілий. Кожен крок — «розробник хоче X → додає один блок у glue». Повна збірка в `ApplicationServiceProvider1` — **ціль і чеклист**, не джерело для сліпого копіювання.

### Три шари збірки

```
bootstrap/providers.php          → список app-level providers (glue entry)
ApplicationServiceProvider       → infrastructure extensions (constructor blocks, lazy resolve)
ApplicationComponentsServiceProvider → feature components (manifest modules)
ApplicationRuntimeServiceProvider    → post-config runtime (timezone, …)
```

**Glue** = конструктор. **Extension ServiceProvider** = блок конструктора з явними параметрами. **Component** = plug-in module (routes, views, migrations, …) поверх уже зібраного стеку.

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
| Логування помилок | `new LoggerMonologServiceProvider(path:, level:, …)` → glue передає `ExceptionReporterInterface` у `ErrorHandlerWhoopsServiceProvider` |
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

При додаванні extension з `ApplicationServiceProvider1` у мінімальну збірку перевіряти:

- `$container->get()` на типи інших extensions без явного контракту в constructor provider-а
- `EventDispatcherResolver::optional($container)` — допустимо для optional telemetry, але не заміна явної залежності для critical path
- Resolvers з `$container` у constructor (`FormRequestArgumentResolver`, `TypedRouteParameterArgumentResolver`) — glue **передає** їх у `HttpKernelServiceProvider`, не core
- Hardcoded assumptions «ViewRegistry вже є», «Router вже зареєстрований» — документувати в boot order

### Профілі збірки (майбутнє)

Замість дублікатів `ApplicationServiceProvider2.php`, `providers2.php` — один provider + named profiles (`minimal`, `full`) або окремі методи/класи glue за шарами. `ApplicationServiceProvider1` лишається reference до повного профілю.

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

- Реєструє providers у `bootstrap/providers.php`
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
Skeleton (`bootstrap/providers.php`) описує повний ланцюжок явно.

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
bootstrap/app.php          → App::create(), register providers
bootstrap/providers.php    → HttpKernelServiceProvider + app providers
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
- [x] `HandleHttpErrorMiddleware` — `ValidationException` → 422 JSON (Accept) або HTML renderer
- [x] Тест: `POST /test/echo` + `TestEchoRequest` (name, email)
- [x] **Console extension**: `ConsoleSymfonyServiceProvider`, `route:list`
- [x] **Session + CSRF**: `SessionServiceProvider`, `CsrfServiceProvider`, middleware chain у `web.php`
- [x] **Validation redirect flow**: `HandleValidationExceptionMiddleware` + flash + форма на `/` з errors/old
- [x] **Json middleware**: `routes/api.php`, `ParseJsonBodyMiddleware`, `ForceJsonResponseMiddleware`; CSRF/session middleware лише на web group
- [x] **DataMasker extension**: glue → `dataMaskerFactory` у log-related providers (не `$container->has()` у extension)
- [x] `ApplicationServiceProvider` — glue для extensions і resolver chain
- [x] Skeleton bootstrap працює з core через symlink
- [x] `IndexController::index()` — повертає `Response`, не `int` від `write()`

### 🔲 Наступні кроки (constructor build — порядок роботи)

> **Components** — відкладено до повного проходження всіх extensions (не чіпати зараз).

1. **Config + PathManager** — замінити hardcoded consts у glue
2. **Інші extensions** — Event, Database, … по одному з smoke-тестом
4. **Розбити glue** — окремі providers за шарами
5. **Profiles** — formalize `minimal` / `full` замість дублікатів `*1.php`
6. **Boot validation** — dev/CLI smoke після зборки

Reference full stack: `ApplicationServiceProvider1.php` + `providers1.php`.

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
- **Літерали в app glue** (`ApplicationServiceProvider`, `bootstrap/*.php`) — рядкові magic values (шляхи, імена файлів, дефолти) виносити в `private const string` класу glue; не розкидати `'valinor'`, `'app.log'`, `'config'` inline у `boot()`. Приклад: `CACHE_VALINOR_DIR`, `LOG_APP_FILE`, `DEFAULT_DB_DRIVER` у `ApplicationServiceProvider`. Контрактні ключі масивів (`driver`, `host` у connection) — не константи, це API Eloquent/бібліотеки.
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
