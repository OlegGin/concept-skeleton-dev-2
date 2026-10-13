# Concept Skeleton — план чистого скелетона

> Рішення: пісочниця (`concept-skeleton-dev-2`) = повний фарш / проба пера.  
> Чистий skeleton = мінімальний web-додаток з формами (**FormSite-рівень**), без DB і Components.  
> Дата: 2026-10-10.

---

## 1. Мета

Skeleton показує **як збирати додаток** (bootstrap + ConceptStack + config + routes), а не все, що вміє платформа.

| Репозиторій / роль | Призначення |
|--------------------|-------------|
| **Sandbox** (цей проєкт) | Reference: усі capabilities, recipes, components, experiments |
| **Skeleton** (чистий) | Clone-стартова точка продукту |
| **Product app** | Skeleton + свідомі add-on (DB, Components, API…) |

---

## 2. Принцип

```
Foundation (завжди)
  → Canonical stack (мінімум Concept-app)
  → Add-on (лише в продукті, не в skeleton)
```

**Правило:** якщо capability можна додати одним блоком у `ApplicationStackBootstrap` за 15 хвилин — вона **не** входить у default skeleton.

---

## 3. Canonical stack

### Увімкнено

| Capability | Навіщо |
|------------|--------|
| Logging | Базовий runtime |
| **Casting** | URL vars завжди string; під `strict_types` `int $id` / `bool` / DTO потребують cast |
| Validation + FormRequests | Головний UX Concept (форми) |
| Session + CSRF (file handler) | Форми / flash |
| Http | `withFormRequests()` + `withTypedRouteParameters()` |
| View (Twig only) | Сторінки |
| ErrorHandling (Whoops + view renderer) | 404/500 + debug |
| Console (мінімум: `route:list`) | CLI-перевірка wiring |

**Чому Casting у canonical:** core `RouteParameterArgumentResolver` віддає raw string з path. Без Casting `function user(int $id)` → TypeError. Це базовий controller UX, не «просунута» фіча.

### Порядок bootstrap (без змін від sandbox)

```
EarlyErrorHandlingBootstrap
  → FoundationBootstrap          # PathManager + Config
  → ApplicationStackBootstrap    # ConceptStack recipe нижче
  → ApplicationRuntimeBootstrap  # timezone тощо
```

### Псевдокод stack recipe

```php
$stack->addLogging()->toRotatingFile(...);

$stack->addCasting()
    ->setCacheDir(...)
    ->setDebug($debug);

$stack->addValidation()
    ->setRules(...)
    ->setGlobalExcept(...);

$stack->addSession()
    ->setOptions(...)
    ->setHandlerFactory(fn() => new NativeFileSessionHandler())
    ->withCsrf();

$stack->addHttp()
    ->setRoutes(...)
    ->withFormRequests()
    ->withTypedRouteParameters();

$stack->addView()
    ->withTwig()
    ->setViewsPath(...)
    ->setCacheDir(...)
    ->setDebug($debug);

$stack->addConsole()
    ->setName(...)
    ->setCommands(...);

$stack->addErrorHandling()
    ->setDebug($debug, new PrettyPageHandler())
    ->setLogReporting()
    ->withViewRenderer(...);
```

Орієнтир у sandbox: `src/App/Bootstrap/Recipes/FormSiteStackBootstrap.php`.

### Не входить у skeleton

| Capability | Чому |
|------------|------|
| Database / Eloquent | Перший свідомий add-on продукту |
| Components (ACL, AuthAdmin, Settings, Health…) | Product scaffolds, не платформа |
| Events / Telemetry | Після того, як є що спостерігати |
| DataMasking | Secondary; додати `->withMasking()` пізніше |
| Cache / CacheProbe | Demo wiring |
| PDO session | Потребує DB |
| ViewPlates | Один engine — Twig |
| API route group + JSON middleware | Окремий recipe / add-on |
| Sandbox Recipes у `src/` | Docs/examples окремо |
| `*Backup.php`, DebugBar | Шум пісочниці |

---

## 4. Сторінки сайту

Мінімальна навчальна поверхня — не продукт і не адмінка.

| URL | Метод | Що перевіряє |
|-----|-------|--------------|
| `/` | GET | Home + форма (name, email) + flash / errors / old |
| `/echo` | POST | FormRequest + CSRF + validation redirect |
| `/hello/{name}` | GET | `string $name` + Twig |
| `/item/{id}` | GET | `int $id` — демо Casting (typed scalar) |
| (missing) | GET | 404 template |
| (uncaught) | — | 500 / Whoops (debug) |

### Acceptance (ручна перевірка після clone)

1. Відкрити `/`
2. Порожня форма → errors + old
3. Валідна форма → success flash на `/`
4. `/hello/Ada` → імʼя на сторінці
5. `/item/42` → id як int (не string)
6. `/missing` → 404

### Не класти сторінки

- `/test/boom`, `/test/db`, `/test/errors`, …
- `/api/*`
- Dashboard / Admin / Settings / ACL

---

## 5. Дерево файлів (ціль)

```
bootstrap/
  app.php
  path-map.php
config/
  app.php
  log.php
  routes.php
  session.php
  view.php
  validator.php
  form-request.php
  caster.php
  console.php
  # env overlays: config/dev/, config/production/ — мінімум
routes/
  web.php                    # тільки web
src/App/
  Bootstrap/
    EarlyErrorHandlingBootstrap.php
    FoundationBootstrap.php
    ApplicationStackBootstrap.php
    ApplicationRuntimeBootstrap.php
  Controllers/
    HomeController.php       # index, echo, hello, item
  Http/Requests/
    EchoRequest.php
  Middleware/
    ShareViewDataMiddleware.php
    HandleValidationExceptionMiddleware.php
    StorePreviousUrlMiddleware.php
  Foundation/
    ConfigKey.php
    PathName.php
    AppConfig.php            # session options (без dbConnection)
  View/Twig/
    TwigAppExtension.php
  Http/Error/Handlers/
    FallbackFileHandler.php
resources/views/
  layouts/base.twig
  home.twig
  hello.twig
  item.twig              # int $id demo
  errors/404.twig
  errors/500.twig
  errors/fallback/404.php
  errors/fallback/500.php
bin/
  boot-smoke.php
  console.php
public/
  index.php
.env.example
composer.json
phpstan.neon
.github/workflows/ci.yml
AGENTS.md                    # короткий, під skeleton
```

**Немає:** `src/Components/`, `src/Bricks/`, `src/App/Bootstrap/Recipes/`, dashboard views, database migrations product, ACL config.

---

## 6. Routes (контракт)

```php
$router->lazyMiddlewares([
    StorePreviousUrlMiddleware::class,
    HandleValidationExceptionMiddleware::class,
    HandleCsrfExceptionMiddleware::class,
    VerifyCsrfTokenMiddleware::class,
    ShareViewDataMiddleware::class,
]);

$router->get('/', [HomeController::class, 'index'])->setName('home');
$router->post('/echo', [HomeController::class, 'echo'])->setName('echo');
$router->get('/hello/{name}', [HomeController::class, 'hello'])->setName('hello');
$router->get('/item/{id}', [HomeController::class, 'item'])->setName('item');
```

Middleware лишаються в `routes/web.php`, не в stack/bootstrap.

---

## 7. Composer

### Модель залежностей

**ConceptStack уже містить** (transitive `require` у `php-concept/stack`) пакети canonical capabilities:

```
extension-logger-monolog
extension-http
extension-casting-valinor      # wired: addCasting + withTypedRouteParameters
extension-form-request
extension-validation-rakit
extension-session-symfony
extension-csrf
extension-view
extension-view-twig
extension-error-handler-whoops
extension-console-symfony
(+ core-2)
```

Skeleton **не дублює** цей список у `composer.json`. Увімкнення capability = рядок у `ApplicationStackBootstrap` (`addLogging()`, `addHttp()`, …), не окремий `composer require`.

**Foundation — поза stack** (glue читає Config/Path до ConceptStack):

```
php-concept/extension-config
php-concept/extension-path-manager
```

### Skeleton `require` (мінімум)

```
php-concept/stack              # + transitive extensions / core
php-concept/extension-config
php-concept/extension-path-manager
vlucas/phpdotenv
```

Опційно явно: `php-concept/core-2` (якщо хочемо pinned version у app-lock, не лише транзитивно).

### Важливо

| Питання | Відповідь |
|---------|-----------|
| Чи stack тягне зайве (DB, Components, Telemetry…)? | Так, зараз у `stack/composer.json` є повний фарш пакетів. Для skeleton це **не означає** «увімкнено»: увімкнено лише те, що викликано в recipe. |
| Чи чистити stack require під FormSite? | Окреме рішення (optional / split meta-packages). Не блокер skeleton v1. |
| Що skeleton **не** додає сам | `extension-json`, `laminas-acl`, `symfony/cache`, `php-debugbar`, app-only deps |

Dev: phpstan, phpunit (мінімум).

Scripts: `phpstan`, `boot-smoke`, `ci` = phpstan + boot-smoke, `console`.

---

## 8. Quality gate (з дня 0)

| Перевірка | Очікування |
|-----------|------------|
| `composer phpstan` | level 9, green |
| `composer boot-smoke` | bootstrap + PathManager + Config + Router + route `home` |
| GitHub Actions `ci.yml` | на push/PR |
| Тести | опційно: 3–5 HTTP smoke пізніше; не блокер v1 |

Не merge / не tag без green `composer ci`.

---

## 9. Звʼязок з пісочницею

| Пісочниця (залишити) | Skeleton (новий / clean) |
|----------------------|--------------------------|
| Повний `ApplicationStackBootstrap` | FormSite-рівень |
| Recipes як sandbox reference | Не копіювати в `src/` |
| Components (ACL, AuthAdmin, …) | Документувати: «як додати» |
| CacheProbe, Telemetry, Events | Add-on examples у docs / sandbox |
| Backup files | Не переносити |

Пісочниця лишається **довідником рішень**. Skeleton — **стартовий clone**.

---

## 10. Етапи реалізації

### Phase 0 — Рішення (готово)

- [x] Canonical = FormSite, не full фарш і не StaticSite
- [x] Сторінки: `/`, `POST /echo`, `/hello/{name}`, `/item/{id}` (int), errors
- [x] Цей документ

### Phase 1 — Scaffold

1. Новий repo (або порожня гілка) `concept-skeleton`
2. Bootstrap chain + Path/Config
3. `ApplicationStackBootstrap` лише з canonical capabilities
4. Мінімальний `composer.json` (секція 7)
5. `.env.example`, `path-map.php`, config files

### Phase 2 — HTTP surface

6. `routes/web.php` + `HomeController` + `EchoRequest`
7. Twig layout + `home` / `hello` / errors
8. Middleware chain (validation, CSRF, share view, previous URL)

### Phase 3 — Safety

9. `bin/boot-smoke.php`
10. PHPStan green
11. `.github/workflows/ci.yml`
12. Короткий `AGENTS.md` / README (setup, sibling repos, `composer ci`)

### Phase 4 — Docs add-on (після v1)

13. Короткий how-to: «додати Database»
14. How-to: «додати Components»
15. How-to: «API group» (json middleware)
16. Посилання на sandbox як full reference

---

## 11. Add-on roadmap (не в v1)

Порядок нарощування в продукті:

```
1. Database (+ migrations CLI)
2. Masking на logging/validation/db
3. API routes + Json extension
4. Components brick + конкретні components
5. Events + Telemetry
6. Cache (PSR-16 brick)
```

Casting уже в canonical (typed scalars / optional `toDto()`).

Кожен крок = зміна в `ApplicationStackBootstrap` + config + (за потреби) composer require. Без fork skeleton.

---

## 12. Анти-цілі

- Не робити skeleton «міні-Laravel з адмінкою»
- Не класти 10 Recipes у основне дерево
- Не тримати Config у extensions
- Не тягнути middleware lists у stack
- Не стартувати з Components «на всяк випадок»

---

## 13. Критерій готовності v1

Skeleton готовий, коли:

1. Clone + `composer install` + `.env` → `composer ci` green  
2. Браузер: 5 кроків з секції 4 проходять  
3. `bin/console.php route:list` показує `home`, `echo`, `hello`, `item`  

4. У дереві немає DB/Components/Telemetry/CacheProbe/dashboard  

---

*Орієнтир у sandbox: `FormSiteStackBootstrap`. Повний фарш — лише `ApplicationStackBootstrap` пісочниці.*
