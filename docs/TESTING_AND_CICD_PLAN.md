# NORMAN Database System — Testing & CI/CD Plan

**Date:** 2026-03-05
**Branch:** `development`
**Status:** Draft for review

---

## Table of Contents

1. [Critical Path — What to Do First](#1-critical-path--what-to-do-first)
2. [Current State Assessment](#2-current-state-assessment)
3. [Testing Strategy](#3-testing-strategy)
4. [Test Plan by Priority](#4-test-plan-by-priority)
5. [Factory & Seeder Prerequisites](#5-factory--seeder-prerequisites)
6. [GitHub Actions CI/CD Pipeline](#6-github-actions-cicd-pipeline)
7. [Implementation Roadmap](#7-implementation-roadmap)

---

## 1. Critical Path — What to Do First

The single most critical thing is **Phase 1: the CI pipeline + P0 Authorization & API tests**. Everything else builds on top of this. The recommended execution order is:

### Step 1 — Create `.github/workflows/ci.yml`

Even with just the existing 12 tests, this immediately provides an automated safety net. Nothing merges without passing. Every test written after this point is automatically enforced — without it, tests only work if someone remembers to run them locally. This is a single YAML file and takes minimal effort.

### Step 2 — Run `./vendor/bin/pint` (one-time cleanup)

One commit to fix all existing code style issues. After that, Pint enforcement in CI is free forever. This removes noise from future diffs and keeps the codebase consistent.

### Step 3 — Write `ModuleAccessTest.php` (~7 tests)

**This is the highest-risk area in the application.** The `CheckModuleAccess` middleware controls visibility of entire research datasets — some public, some restricted by role. A regression here means either:

- **Data leak** — private research data exposed to unauthorized users or the public internet
- **Access denial** — legitimate researchers locked out of modules they need

With 184 models, 90+ controllers, and multiple roles (`super_admin`, `admin`, plus per-module roles like `empodat_suspect`, `literature`, etc.), this is the most likely place for a silent regression. A single route missing its middleware or a `DatabaseEntity.is_public` flag changing unexpectedly could expose an entire module. These 7 tests cover the most dangerous failure mode in the system.

### Step 4 — Write `SubstanceApiTest.php` + `EmpodatApiTest.php` (~20 tests)

External systems already consume the `/api/v1/` endpoints. If a substance lookup response structure changes or the EMPODAT search breaks, downstream integrations fail silently — with no way for us to know until someone reports it. These tests lock down the external contract: response format, status codes, validation, pagination, and authentication requirements.

### Why this order matters

| Step | Effort | Risk Reduction |
|------|--------|----------------|
| CI pipeline | ~1 hour | Enforces all future tests automatically |
| Pint cleanup | ~10 minutes | Eliminates style noise permanently |
| Authorization tests | ~2-3 hours | Protects against data leaks (highest impact bug) |
| API tests | ~3-4 hours | Protects external integrations (hardest to debug when broken) |

**Total: ~1 day of work for protection against the two most damaging categories of bugs (unauthorized data access and broken API contracts).** Everything in Phases 2-4 adds value, but this foundation is non-negotiable for a production system.

---

## 2. Current State Assessment

### What exists today

| Area | Status |
|------|--------|
| Feature tests | 12 tests (Auth x6, BatchConversion, EmpodatSuspectSearch, Profile, Example) |
| Unit tests | 1 placeholder |
| Factories | 3 (User, Substance, DataNest) |
| CI/CD pipeline | None |
| Code formatting | Pint installed but not enforced |
| Static analysis | Not installed |

### Gaps

- **No tests** for: API endpoints, Livewire components, module access/authorization, CSV export jobs, backend admin, any search controllers, middleware, mail
- **No CI/CD** — no automated checks on push or PR
- **No static analysis** — PHPStan/Larastan not installed
- **Missing factories** — 184 models, only 3 factories

---

## 3. Testing Strategy

### Principles

1. **Test at boundaries first** — API endpoints, form requests, middleware, authorization
2. **Test business-critical paths** — search, export, substance CRUD, duplicate merging
3. **Don't test framework internals** — no testing that Eloquent queries work; test your logic
4. **Use RefreshDatabase** — every test gets a clean PostgreSQL state
5. **Feature tests over unit tests** — this is a data-heavy app with thin controllers; integration tests provide the most value

### Test categories (in priority order)

| Priority | Category | Why |
|----------|----------|-----|
| P0 | Authorization & Access Control | Security — module access, role checks, public vs private modules |
| P0 | API Endpoints | External contract — other systems depend on these |
| P1 | Livewire Search Components | Core user-facing functionality |
| P1 | Substance CRUD & Duplicate Merging | Data integrity of the substance database |
| P1 | CSV Export Jobs | Long-running background work that users wait for |
| P2 | Backend Admin Controllers | Admin data management |
| P2 | Module Home & Search Controllers | Page rendering, search filtering |
| P3 | Mail & Notifications | Communication correctness |
| P3 | Artisan Commands | Batch data operations |

---

## 4. Test Plan by Priority

### P0 — Authorization & Access Control

**File:** `tests/Feature/Authorization/ModuleAccessTest.php`

| Test | Description |
|------|-------------|
| `test_public_module_accessible_by_guest` | Guest can access modules where `database_entities.is_public = true` |
| `test_private_module_blocked_for_guest` | Guest gets 403 on private modules |
| `test_private_module_blocked_for_user_without_role` | Authenticated user without module role gets 403 |
| `test_private_module_accessible_by_user_with_role` | User with correct role can access private module |
| `test_admin_can_access_any_private_module` | Admin bypasses module restrictions |
| `test_super_admin_can_access_any_private_module` | Super admin bypasses module restrictions |
| `test_module_access_returns_403_for_unknown_module` | Unknown module code returns 403 |

**File:** `tests/Feature/Authorization/BackendAccessTest.php`

| Test | Description |
|------|-------------|
| `test_backend_requires_authentication` | Guest is redirected from `/backend/*` |
| `test_system_settings_requires_admin_role` | Regular user cannot access system settings |
| `test_server_payments_requires_payment_role` | Only payment admin/viewer can access |
| `test_user_management_accessible_by_admin` | Admin can list/edit users |

**File:** `tests/Feature/Authorization/RoleAssignmentTest.php`

| Test | Description |
|------|-------------|
| `test_admin_can_assign_roles_to_user` | Role assignment works correctly |
| `test_non_admin_cannot_assign_roles` | Regular users cannot modify roles |

### P0 — API Endpoints

**File:** `tests/Feature/Api/v1/SubstanceApiTest.php`

| Test | Description |
|------|-------------|
| `test_get_substance_by_valid_norman_code` | Returns substance data for valid NS code |
| `test_get_substance_by_invalid_norman_code_format` | Returns 422 for malformed code |
| `test_get_substance_by_nonexistent_code` | Returns 404 for unknown code |
| `test_get_substance_by_valid_inchikey` | Returns substance data for valid InChIKey |
| `test_get_substance_by_invalid_inchikey_format` | Returns 422 for malformed InChIKey |
| `test_get_substance_by_nonexistent_inchikey` | Returns 404 for unknown InChIKey |
| `test_substance_response_includes_categories_and_sources` | Response structure is correct |
| `test_merged_substance_returns_canonical_reference` | Merged substances resolve correctly |
| `test_substance_api_is_publicly_accessible` | No auth required for substance endpoints |

**File:** `tests/Feature/Api/v1/EmpodatApiTest.php`

| Test | Description |
|------|-------------|
| `test_empodat_search_requires_authentication` | Unauthenticated request returns 401 |
| `test_empodat_search_by_substance_ns_code` | Returns paginated results for valid substance |
| `test_empodat_search_by_substance_inchikey` | Search by InChIKey works |
| `test_empodat_search_by_country_code` | Returns results filtered by country |
| `test_empodat_search_nonexistent_substance` | Returns 404 |
| `test_empodat_search_nonexistent_country` | Returns 404 |
| `test_empodat_search_invalid_search_type` | Returns 404 (route constraint) |
| `test_empodat_pagination_respects_per_page` | Custom per_page parameter works |
| `test_empodat_pagination_max_per_page_is_1000` | Cannot exceed 1000 per page |
| `test_empodat_response_includes_matrix_data` | Matrix metadata is included |
| `test_empodat_response_includes_remapped_fields` | Analytical method and data source names are resolved |

### P1 — Livewire Search Components

**File:** `tests/Feature/Livewire/Susdat/SubstanceSearchTest.php`

| Test | Description |
|------|-------------|
| `test_component_renders` | Component mounts without error |
| `test_search_by_name_returns_results` | Typing a name filters substances |
| `test_search_by_cas_returns_results` | CAS number search works |
| `test_search_by_norman_code_returns_results` | NS code search works |
| `test_search_by_inchikey_returns_results` | InChIKey search works |
| `test_empty_search_returns_no_results` | Blank query handled gracefully |
| `test_search_dispatches_event_with_selected_substance` | Selection event fires correctly |

**File:** `tests/Feature/Livewire/Empodat/SubstanceSearchTest.php`

| Test | Description |
|------|-------------|
| `test_component_renders` | Component mounts |
| `test_search_filters_substances` | Search input triggers filtering |
| `test_selecting_substance_updates_property` | Selecting a result updates component state |

*Repeat similar patterns for:* `Ecotox/SubstanceSearch`, `Factsheet/SubstanceSearch`, `Backend/SubstanceSearch`, `Backend/UserSearch`

### P1 — Substance CRUD & Duplicate Merging

**File:** `tests/Feature/Susdat/SubstanceCrudTest.php`

| Test | Description |
|------|-------------|
| `test_substance_index_page_loads` | List page renders |
| `test_substance_create_requires_auth` | Guests cannot create |
| `test_substance_can_be_created_with_valid_data` | Full creation flow |
| `test_substance_creation_validates_required_fields` | Validation rules enforced |
| `test_substance_can_be_updated` | Edit and save |
| `test_substance_soft_delete` | Soft deletion works |
| `test_substance_audit_trail_created_on_update` | Owen-IT auditing records changes |

**File:** `tests/Feature/Susdat/DuplicateMergingTest.php`

| Test | Description |
|------|-------------|
| `test_duplicate_detection_page_loads` | Admin can access duplicate management |
| `test_merging_substances_updates_canonical_id` | Merge sets canonical_id and status |
| `test_merged_substance_retains_audit_trail` | Merge is auditable |
| `test_merge_updates_related_empodat_references` | Foreign references are updated |

### P1 — CSV Export Jobs

**File:** `tests/Feature/Jobs/EmpodatCsvExportJobTest.php`

| Test | Description |
|------|-------------|
| `test_job_creates_csv_file` | Export generates file on disk |
| `test_job_sends_email_notification_on_completion` | User receives email when done |
| `test_job_handles_empty_result_set` | No data produces empty CSV with headers |
| `test_job_records_export_download` | ExportDownload record is created |
| `test_job_disables_debugbar_and_query_log` | Memory optimization is applied |

**File:** `tests/Feature/Jobs/EmpodatSuspectCsvExportJobTest.php`

| Test | Description |
|------|-------------|
| `test_suspect_export_job_creates_csv` | Suspect data export works |
| `test_suspect_export_sends_notification` | Email sent on completion |

### P2 — Backend Admin Controllers

**File:** `tests/Feature/Backend/UserManagementTest.php`

| Test | Description |
|------|-------------|
| `test_user_index_page_loads_for_admin` | Admin sees user list |
| `test_user_creation_with_roles` | Create user with role assignment |
| `test_user_update` | Edit user data |
| `test_user_data_endpoint_returns_json` | AJAX data endpoint works |

**File:** `tests/Feature/Backend/ProjectManagementTest.php`

| Test | Description |
|------|-------------|
| `test_project_crud_operations` | Full CRUD cycle |
| `test_project_user_assignment` | Assign users to projects |

**File:** `tests/Feature/Backend/FileManagementTest.php`

| Test | Description |
|------|-------------|
| `test_file_index_page_loads` | File list renders |
| `test_file_upload_tracking` | Uploaded files are recorded |

**File:** `tests/Feature/Backend/DashboardTest.php`

| Test | Description |
|------|-------------|
| `test_dashboard_loads_for_authenticated_user` | Dashboard renders |
| `test_dashboard_redirects_unauthenticated` | Guest is redirected |

### P2 — Module Home & Search Controllers

**File:** `tests/Feature/Empodat/EmpodatSearchTest.php`

| Test | Description |
|------|-------------|
| `test_empodat_home_page_loads` | Home page renders |
| `test_empodat_search_page_loads` | Search form renders |
| `test_empodat_search_with_country_filter` | Filter by country returns results |
| `test_empodat_search_with_substance_filter` | Filter by substance returns results |
| `test_empodat_show_record` | Individual record detail page renders |
| `test_empodat_statistics_page_loads` | Statistics page renders |

*Repeat similar patterns for:* Literature, Ecotox, ARBG, Factsheet, Bioassay, Passive, SARS, Indoor, SLE, Prioritisation, EmpodatSuspect

### P3 — Mail & Notifications

**File:** `tests/Feature/Mail/CsvExportMailTest.php`

| Test | Description |
|------|-------------|
| `test_csv_export_ready_mail_contains_download_link` | Email body has link |
| `test_csv_export_ready_mail_has_correct_subject` | Subject line is correct |

### P3 — Artisan Commands

**File:** `tests/Feature/Commands/EmpodatCommandsTest.php`

| Test | Description |
|------|-------------|
| `test_merge_station_duplicates_command_runs` | Command executes without error |
| `test_refresh_suspect_filters_command_runs` | Filter refresh completes |
| `test_clear_logs_command_runs` | Log cleanup works |

---

## 5. Factory & Seeder Prerequisites

New factories required before tests can be written:

| Factory | Model | Notes |
|---------|-------|-------|
| `DatabaseEntityFactory` | `DatabaseEntity` | Needed for module access tests; states: `public()`, `private()` |
| `EmpodatMainFactory` | `EmpodatMain` | Needed for EMPODAT search/API tests |
| `EmpodatStationFactory` | `EmpodatStation` | Needed for station-related tests |
| `CountryFactory` | `Country` | Needed across many tests |
| `MatrixFactory` | `Matrix` | Needed for EMPODAT tests |
| `ProjectFactory` | `Project` | Needed for backend project tests |
| `FileFactory` | `File` | Needed for file management tests |
| `ExportDownloadFactory` | `ExportDownload` | Needed for export job tests |
| `NotificationFactory` | `Notification` | Needed for notification tests |

The existing `UserFactory` and `SubstanceFactory` are sufficient for user and substance tests.

---

## 6. GitHub Actions CI/CD Pipeline

### Pipeline Overview

```
PR / Push to development or main
    |
    +-- [Job 1] Code Style (Pint)            ~30s
    +-- [Job 2] Static Analysis (Larastan)    ~1-2min
    +-- [Job 3] Tests (PHPUnit + PostgreSQL)  ~3-5min
    +-- [Job 4] Frontend Build (Vite)         ~1min
    |
    (all pass)
    |
    +-- [Optional] Deploy to staging (on push to development)
    +-- [Optional] Deploy to production (on push to main, manual approval)
```

### Job 1 — Code Style

**File:** `.github/workflows/ci.yml` (part of unified workflow)

```yaml
lint:
  name: Code Style
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        tools: composer:v2
    - name: Install dependencies
      run: composer install --no-interaction --prefer-dist
    - name: Run Pint
      run: ./vendor/bin/pint --test
```

### Job 2 — Static Analysis

**Requires installing Larastan:**
```bash
composer require --dev larastan/larastan:^3.0
```

```yaml
static-analysis:
  name: Static Analysis
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        tools: composer:v2
    - name: Install dependencies
      run: composer install --no-interaction --prefer-dist
    - name: Run Larastan
      run: ./vendor/bin/phpstan analyse --memory-limit=512M
```

**Config file:** `phpstan.neon`
```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/
    level: 5
    ignoreErrors: []
    excludePaths: []
```

Start at **level 5** (moderate strictness). Increase to level 6-8 over time as existing issues are resolved.

### Job 3 — Tests

```yaml
tests:
  name: Tests
  runs-on: ubuntu-latest
  services:
    postgres:
      image: postgres:16
      env:
        POSTGRES_USER: norman_test
        POSTGRES_PASSWORD: password
        POSTGRES_DB: norman_testing
      ports:
        - 5432:5432
      options: >-
        --health-cmd pg_isready
        --health-interval 10s
        --health-timeout 5s
        --health-retries 5
  steps:
    - uses: actions/checkout@v4
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        extensions: pdo_pgsql, pgsql, mbstring, xml, bcmath
        tools: composer:v2
        coverage: xdebug
    - name: Install Composer dependencies
      run: composer install --no-interaction --prefer-dist
    - name: Prepare environment
      run: |
        cp .env.example .env
        php artisan key:generate
    - name: Configure test database
      run: |
        echo "DB_CONNECTION=pgsql" >> .env
        echo "DB_HOST=127.0.0.1" >> .env
        echo "DB_PORT=5432" >> .env
        echo "DB_DATABASE=norman_testing" >> .env
        echo "DB_USERNAME=norman_test" >> .env
        echo "DB_PASSWORD=password" >> .env
    - name: Run migrations
      run: php artisan migrate --force
    - name: Run tests
      run: php artisan test --parallel --coverage-clover=coverage.xml
    - name: Upload coverage report
      if: success()
      uses: actions/upload-artifact@v4
      with:
        name: coverage-report
        path: coverage.xml
```

### Job 4 — Frontend Build

```yaml
frontend:
  name: Frontend Build
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - name: Setup Node.js
      uses: actions/setup-node@v4
      with:
        node-version: '20'
        cache: 'npm'
    - name: Install dependencies
      run: npm ci
    - name: Build assets
      run: npm run build
```

### Complete Workflow File

**File:** `.github/workflows/ci.yml`

```yaml
name: CI

on:
  push:
    branches: [development, main]
  pull_request:
    branches: [development, main]

concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true

jobs:
  lint:
    name: Code Style
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          tools: composer:v2
      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          restore-keys: composer-
      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist
      - name: Run Pint
        run: ./vendor/bin/pint --test

  static-analysis:
    name: Static Analysis
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          tools: composer:v2
      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          restore-keys: composer-
      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist
      - name: Run Larastan
        run: ./vendor/bin/phpstan analyse --memory-limit=512M

  tests:
    name: Tests
    runs-on: ubuntu-latest
    needs: [lint]
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_USER: norman_test
          POSTGRES_PASSWORD: password
          POSTGRES_DB: norman_testing
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_pgsql, pgsql, mbstring, xml, bcmath
          tools: composer:v2
          coverage: xdebug
      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          restore-keys: composer-
      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist
      - name: Prepare environment
        run: |
          cp .env.example .env
          php artisan key:generate
      - name: Configure test database
        run: |
          echo "DB_CONNECTION=pgsql" >> .env
          echo "DB_HOST=127.0.0.1" >> .env
          echo "DB_PORT=5432" >> .env
          echo "DB_DATABASE=norman_testing" >> .env
          echo "DB_USERNAME=norman_test" >> .env
          echo "DB_PASSWORD=password" >> .env
          echo "TELESCOPE_ENABLED=false" >> .env
      - name: Run migrations
        run: php artisan migrate --force
      - name: Seed required reference data
        run: php artisan db:seed --class=RolesAndPermissionsSeeder --force
      - name: Run tests
        run: php artisan test --parallel --coverage-clover=coverage.xml
      - name: Upload coverage report
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: coverage-report
          path: coverage.xml

  frontend:
    name: Frontend Build
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
      - name: Install dependencies
        run: npm ci
      - name: Build assets
        run: npm run build
```

### Branch Protection Rules (GitHub Settings)

Configure on both `main` and `development` branches:

| Rule | Setting |
|------|---------|
| Require status checks to pass | Yes |
| Required checks | `Code Style`, `Tests`, `Frontend Build` |
| Require branches to be up to date | Yes |
| Require pull request reviews | 1 reviewer minimum (for `main`) |
| Dismiss stale reviews on new pushes | Yes |
| Restrict force pushes | Yes |
| Restrict deletions | Yes |

---

## 7. Implementation Roadmap

### Phase 1 — Foundation (Week 1)

- [ ] Install Larastan: `composer require --dev larastan/larastan:^3.0`
- [ ] Create `phpstan.neon` at level 5
- [ ] Run `./vendor/bin/pint` to fix all existing style issues in one commit
- [ ] Create `.github/workflows/ci.yml` with all 4 jobs
- [ ] Create `DatabaseEntityFactory` (required for authorization tests)
- [ ] Write **P0 Authorization tests** (~7 tests)
- [ ] Write **P0 API tests** (~20 tests)
- [ ] Set up branch protection rules on GitHub

### Phase 2 — Core Features (Week 2-3)

- [ ] Create remaining factories (EmpodatMain, EmpodatStation, Country, Matrix, etc.)
- [ ] Write **P1 Livewire component tests** (~20 tests)
- [ ] Write **P1 Substance CRUD & Duplicate tests** (~11 tests)
- [ ] Write **P1 CSV Export Job tests** (~7 tests)

### Phase 3 — Coverage Expansion (Week 3-4)

- [ ] Write **P2 Backend admin tests** (~12 tests)
- [ ] Write **P2 Module search controller tests** (~60+ tests across all modules)
- [ ] Write **P3 Mail and Command tests** (~8 tests)

### Phase 4 — Hardening (Ongoing)

- [ ] Increase Larastan level to 6, then 7
- [ ] Add coverage threshold enforcement (target: 60% initially, 80% goal)
- [ ] Add deployment jobs (staging on `development` push, production on `main` with manual approval)
- [ ] Consider adding browser tests with Laravel Dusk for critical flows (search, export)

### Estimated test count at completion

| Phase | Tests Added | Running Total |
|-------|-------------|---------------|
| Existing | 12 | 12 |
| Phase 1 | ~27 | ~39 |
| Phase 2 | ~38 | ~77 |
| Phase 3 | ~80 | ~157 |
| Phase 4 | Ongoing | 200+ |

---

## Notes

- **MariaDB secondary connection:** Tests should mock or skip MariaDB-dependent features (SLE, legacy SUSDAT). Add a `DB_MARIADB_ENABLED=false` env var for CI and use `markTestSkipped()` when the connection is unavailable.
- **Seeder dependency:** Some tests (authorization, EMPODAT search) require seeded reference data (roles, permissions, countries, matrices). Create a minimal `TestDatabaseSeeder` that seeds only the essentials.
- **Parallel testing:** `php artisan test --parallel` requires each test to use `RefreshDatabase` or `DatabaseTransactions`. Verify all tests are compatible before enabling.
- **Coverage:** Start measuring coverage from Phase 1. Don't enforce a threshold immediately — let it grow naturally as tests are added.
- **No SQLite:** The project uses PostgreSQL-specific features (jsonb, materialized views). Always test against PostgreSQL, never SQLite in-memory.
