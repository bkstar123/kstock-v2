# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

KStock is a Laravel 12 (PHP ^8.2, platform pinned to 8.4.8 in `composer.json`) application that pulls financial statements (balance sheet, income statement, cash flow) for Vietnamese stock market symbols from external API endpoints, and computes a large set of financial analysis ratios from them. On top of that statement/analysis core, it also provides a **company directory + per-company profile hub** (profile, fundamentals, OHLCV price charts), a **stock comparison tool** (`ComparisonController`, side-by-side ratios across symbols), and a **per-admin watchlist**. The app was originally Laravel 5.8 / PHP 7.1 and was upgraded in place to Laravel 12 / PHP 8 — see `deployment-local/README-LOCAL.md` (local-only, gitignored) for the local dev environment (Apache + mod_php + SQLite, started via `deployment-local/kstock-v2-up.sh`) that resulted from that migration.

The CMS/admin-panel layer (auth, roles/permissions, settings, flashing messages) is not part of `app/` — it lives under the `bkstar123/*` namespace, but as part of the PHP 8 upgrade those packages were **forked into `packages/`** and patched to run on PHP 8 / Laravel 12: `bkscms-admin-panel`, `bkscms-utilities`, `flashing`, `laratune`, `mysql-search`, `laravel-recaptcha`, `laravel-uploader`, `log-enhancer`. They are **no longer Composer dependencies** — they've been dissolved into the project as first-party source: the root `composer.json` maps each `Bkstar123\*` namespace straight to `packages/<pkg>/src/` under `autoload.psr-4` (plus admin-panel's `src/database/seeds` classmap and flashing's `src/Helpers/helpers.php` files entry), so there is **no path repository, no `require` on them, and no `vendor/bkstar123/*`**. Because they aren't Composer-installed, Laravel package auto-discovery does not apply — their 8 service providers are **registered manually** in the `providers` array of `config/app.php` (the "Package Service Providers..." section); their facade aliases self-register inside each provider's `register()`. Expect `Bkstar123\BksCMS\...` classes (e.g. `Admin`, `Role`) to come from `packages/`, autoloaded like `app/`. To add/remove one of these packages you edit `packages/`, the root `autoload` map, and the `config/app.php` provider list — not `composer require`.

## Common commands

```bash
composer install                 # install PHP deps for LOCAL/CI (includes phpunit, faker, mockery for testing)
composer install --no-dev --optimize-autoloader  # PRODUCTION ONLY — excludes dev dependencies (phpunit, faker, mockery, pail, collision)

npm install && npm run dev       # build frontend assets (Laravel Mix 4); may not build on newer Node — assets are already compiled under public/cms-assets/, so this is usually unnecessary (see deployment-local/README-LOCAL.md)
php artisan migrate              # run DB migrations
php artisan serve                # run the app locally (local dev instead uses Apache + mod_php + SQLite via deployment-local/kstock-v2-up.sh — see deployment-local/README-LOCAL.md)
php artisan queue:work           # process queued jobs (statement pulling/analysis run via ShouldQueue)

php artisan symbols:sync FPT VNM  # upsert the local `symbols` master table for the given tickers (scheduled daily 01:30 in Console/Kernel)
php artisan analysis:recompute    # re-run analysis (overwrite AnalysisReport) for all saved statements with current formulas; {ids?*} to scope, {--type=direct|indirect}

vendor/bin/phpunit                                  # run full test suite (local/CI only; never on production — see Common commands above)
vendor/bin/phpunit --testsuite Unit                 # unit tests only (tests/Unit)
vendor/bin/phpunit --testsuite Feature              # feature tests only (tests/Feature)
vendor/bin/phpunit --filter TestMethodOrClassName    # run a single test
```

There is no configured linter/formatter script in this repo (`.styleci.yml` runs style checks in CI, not locally).

## Architecture

### Domain flow: pulling and analyzing a financial statement

1. **`SymbolController::pullFinancialStatement`** (`app/Http/Controllers/SymbolController.php`) validates input (symbol/year/quarter), creates a `FinancialStatement` record, and dispatches `PullFinancialStatement` as a queued job. Requests are rate-limited per-user via the `RequestByUserThrottling` trait (`app/Http/Components`).
2. **`PullFinancialStatement` job** (`app/Jobs/PullFinancialStatement.php`) calls the external `Symbols` service (bound via `App\Services\Contracts\Symbols` interface → `App\Services\Symbols`, registered as a singleton in `AppServiceProvider`) to fetch raw balance/income/cash-flow statement JSON, validates it covers the requested period, and persists it as `BalanceStatement`/`IncomeStatement`/`CashFlowStatement` rows (raw JSON in a `content` column). It then dispatches `AnalyzeFinancialStatement`.
3. **`AnalyzeFinancialStatement` job** (`app/Jobs/AnalyzeFinancialStatement.php`) loads the `FinancialStatement` (with its three statement relations eager-loaded via `$with` on the model) and runs it through ~15 Calculator/Writer pairs (Z-Score, M-Score, profitability, liquidity, cash flow, capex, operating effectiveness, financial leverage, cost structure, current/long-term asset structure, profit structure, growth, Dupont), then saves the combined result as a single `AnalysisReport` row (JSON `content`).
4. Both jobs dispatch broadcast events (`PullFinancialStatementCompleted`, `AnalyzeFinancialStatementCompleted`) on success, or `JobFailing` on `failed()`, for real-time (Pusher) UI notifications scoped to the requesting admin's private channel.

### Calculator / Writer pattern (`app/Jobs/Financials/`)

This is the core, and largest, part of the codebase — read here before adding new ratios.

- **Calculators** (`Calculators/*Calculator.php`) extend `BaseCalculator`. Each public property holds one computed ratio; each `calculate*($year, $quarter)` method computes one ratio from the statement items and returns `$this` (fluent). `BaseCalculator::execute()` auto-discovers and runs every `calculate*` method via reflection — used when you want all ratios in one calculator at once, but `AnalyzeFinancialStatement` mostly calls individual `calculate*` methods on demand instead.
- **Writers** (`Writers/*Writer.php`) are traits (not classes) mixed into `AnalyzeFinancialStatement`. Each `write*` method calls the matching calculator method across a rolling window of `config('settings.limits')` historical periods (walking backwards via `getPreviousPeriod()`), and appends a structured entry (`name`, `alias`, `group`, `unit`, HTML `description` in Vietnamese, `values`) to `$this->content` on the job. `AnalysisReport::content` is just the JSON-encoded accumulation of all these entries.
- To add a new ratio: add a `calculate*` method to the relevant `*Calculator`, add a matching `write*` method to the relevant `*Writer` trait, then call it from `AnalyzeFinancialStatement::handle()`.
- Financial statement line items are looked up by **numeric string IDs** (e.g. `getItem('21')` = net profit of parent shareholders, `getItem('2')` = total assets, `getItem('302')` = equity) via `StatementItem` (`app/ContentObjects/StatementItem.php`), which wraps a single line item's time series and exposes `getValue`, `getAverageValue`, `getDifferentialValueFromPastPeriod`, `getAccumulatedValueFromPastPeriod`. These IDs come from the shape of the external API's JSON and are not documented in code — cross-reference existing calculator usages when unsure what an ID means.
- `BalanceStatement`/`IncomeStatement`/`CashFlowStatement` all extend `BaseStatement`, which uses the `StatementRepository` trait (`app/Models/Behaviors/StatementRepository.php`) to decode the `content` JSON into `StatementItem` objects.
- Period arithmetic (`getPreviousPeriod`, `getLastYearSamePeriod`) lives in global helpers in `app/Functions/kstock_helpers.php` (autoloaded via composer `files`). Quarter `0` denotes an annual (non-quarterly) period.

### External API and the Symbols service

- **`App\Services\Base`** builds a Guzzle client from `config('settings.api_endpoint')` + Bearer `config('settings.api_token')`. `getDecoded($path)` GETs a path and returns a JSON-decoded array or `null` on any failure/non-200 (swallows exceptions).
- **`App\Services\Symbols`** (bound to the `App\Services\Contracts\Symbols` interface, singleton in `AppServiceProvider`) is split into two generations of methods:
  - **Legacy** (`getFullFinancialStatement`, `getFundamentals`) return the **raw body string** and feed the statement-pulling flow.
  - **Newer array methods** (`getSymbol`, `getProfile`, `getFundamentalsData`, `getHistoricalQuotes`, `getLatestQuote`, `getHolders`, `getDividends`) hit the external API endpoints under `/symbols/{symbol}/...` and go through `cachedGet($key, $path, $ttl)`, which caches via the Laravel `Cache` facade but **never caches a `null`** (so a transient API error isn't pinned for the whole TTL). TTLs: master/profile/holders/dividends `43200`s, fundamentals/quotes `900`s. `getLatestQuote` is derived (fetch last ~12 days of quotes, take the newest).
- **`App\Services\SymbolCatalog`** keeps the local `symbols` master table in sync. The external API (with the current token) has **no full-universe list endpoint**, so the catalog is populated **on demand**: `remember($code)` returns the local row or fetches+upserts it; `sync($code)` always refetches and upserts. Anything that views/watchlists/syncs a ticker goes through this.

### Models

- `FinancialStatement` is the aggregate root: belongs to an `admin` (from bkscms-admin-panel), and `hasOne` each of `balance_statement`, `cash_flow_statement`, `income_statement`, `analysis_report` — all eager-loaded by default (`$with`). Symbol is always normalized to uppercase via a mutator.
- Search on `FinancialStatement` (by `symbol`) uses the `MySqlSearch` trait from `bkstar123/mysql-search`.
- `Symbol` is the local master row (`code`, `name`, `exchange`, `company_type`, `industry_code`, `icb_code`, `is_active`), upper-cases `code` via a mutator, and `hasMany` `FinancialStatement` cross-linked by the `symbol` string column. Its `scopeSearch` is a **portable** `LIKE` search (code prefix / name contains) that deliberately does **not** use the `MySqlSearch` fulltext trait, so it works on SQLite too.
- `Watchlist` is a per-admin followed ticker (`admin_id` + upper-cased `symbol_code`); `belongsTo` `Admin` and `belongsTo` `Symbol` (on `symbol_code` → `code`). Populated via `firstOrCreate`.
- Migrations for `symbols` and `watchlists` are the `2026_07_02_*` files.

### Config

- `config('settings.*')` (e.g. `api_endpoint`, `api_token`, `limits`) is application-specific config expected to be provided by the bkscms settings mechanism/DB-backed settings, not a file present in this repo's `config/`. It's populated at runtime by the `packages/laratune` package: `LaraTuneServiceProvider` (registered in `config/app.php`) loads all rows from the `settings` DB table and `Config::set("settings.{$setting->key}", ...)` for each one (see `packages/laratune/src/LaraTuneServiceProvider.php` and `Services/Setting.php`).
- `config('bkstar123_bkscms_adminpanel.php')` holds pagination size, login throttling, and the CMS permission catalog (permission list here is currently limited to `admins.*` — the financial-statement routes reference permissions like `financial.statements.show` that must be registered/granted via the admin panel package's own mechanisms).

### Routes

All app routes are under the `cms/` prefix and gated by `bkscms-auth:admins` middleware; most also carry per-route `can:` permission middleware (see `routes/web.php`). Route groups: financial statements (index/pull/show/destroy/massiveDestroy), **companies** (`companies` directory, `companies/{code}` profile hub, `companies/{code}/price-history` which returns Highcharts-ready OHLC+volume JSON via `CompanyController::priceHistory`), **compare** (`ComparisonController@index` at `cms/compare` — pulls the latest `AnalysisReport` per symbol plus live fundamentals from the `Symbols` service to compare ratios side by side across tickers), and **watchlist** (index/store/destroy). Note the company/compare/watchlist routes are **not** all permission-gated the way the statement routes are — they rely on `bkscms-auth:admins` + the current admin's id (`auth()->guard('admins')`). `routes/api.php` is still just the default stub — the price-history JSON lives under the `web`/`cms` group, not a separate API surface.

### Frontend / theme

- `resources/views/cms/layouts/master.blade.php` is the single master layout (shared `<head>` for both guest/login and authenticated pages). It loads AdminLTE 3 / Bootstrap 4 via `app.css`, then a pure-CSS **"Dark Pro Dashboard" theme overlay** `public/cms-assets/css/modern.css` (cache-busted with a `?v=N` query — bump `N` when you edit it) that overrides AdminLTE; remove that `<link>` to revert. No build step for the overlay.
- The favicon is a generated icon set (`public/favicon.ico` multi-res + `public/images/favicon.svg` + `favicon-16/32.png` + `apple-touch-icon.png`, an ascending-bars/upward-trend mark) wired via `<link rel="icon">` tags in the master `<head>`.