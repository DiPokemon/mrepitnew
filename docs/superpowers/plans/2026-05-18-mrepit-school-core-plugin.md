# MRepit School Core Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract school user-management business logic from the Elementor theme into a standalone WordPress plugin with its own Git repository.

**Architecture:** The plugin loads the existing school module independently of the theme and defines `MREPIT_SCHOOL_CORE_LOADED`. The theme keeps a temporary fallback guard so the old module loads only when the plugin is inactive. Existing hooks, admin pages, and function names stay stable during Phase 1.

**Tech Stack:** WordPress 6.9.4, PHP 8.1/8.3 syntax checks, PowerShell static tests, Git, Carbon Fields via the active theme for this phase.

---

## Files

Create in plugin:

- `D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core\mrepit-school-core.php`
- `D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core\README.md`
- `D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core\includes\bootstrap.php`
- `D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core\includes\common\*.php`
- `D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core\includes\parents\*.php`
- `D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core\includes\students\*.php`
- `D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core\includes\teachers\*.php`
- `D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core\tests\static\plugin-structure.test.ps1`

Modify in theme:

- `D:\OSPanel\domains\mrepitnew\wp-content\themes\mrepitnew\functions.php`
- `D:\OSPanel\domains\mrepitnew\wp-content\themes\mrepitnew\docs\online-school-work-plan.md`
- `D:\OSPanel\domains\mrepitnew\wp-content\themes\mrepitnew\README.md`

## Task 1: Plugin Scaffold and Static Test

- [ ] **Step 1: Create failing plugin-structure test**

Create `wp-content/plugins/mrepit-school-core/tests/static/plugin-structure.test.ps1`:

```powershell
$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..\..')
$main = Join-Path $root 'mrepit-school-core.php'
$bootstrap = Join-Path $root 'includes\bootstrap.php'

if (!(Test-Path $main)) {
    Write-Error 'Plugin main file is missing.'
}

if (!(Test-Path $bootstrap)) {
    Write-Error 'Plugin bootstrap file is missing.'
}

$mainSource = Get-Content -Raw -Encoding UTF8 $main
$bootstrapSource = Get-Content -Raw -Encoding UTF8 $bootstrap

foreach ($needle in @(
    'Plugin Name: MRepit School Core',
    'MREPIT_SCHOOL_CORE_LOADED',
    'MREPIT_SCHOOL_CORE_PATH',
    'MREPIT_SCHOOL_CORE_VERSION'
)) {
    if (!$mainSource.Contains($needle)) {
        Write-Error "Plugin main file must contain '$needle'."
    }
}

foreach ($needle in @(
    "common/helpers.php",
    "common/capabilities.php",
    "common/security.php",
    "common/sync.php",
    "common/teacher-link.php",
    "common/admin-menu.php",
    "common/carbon-fields.php",
    "parents/list-table.php",
    "parents/pages.php",
    "parents/handlers.php",
    "students/list-table.php",
    "students/pages.php",
    "students/handlers.php",
    "teachers/list-table.php",
    "teachers/pages.php",
    "teachers/handlers.php"
)) {
    if (!$bootstrapSource.Contains($needle)) {
        Write-Error "Plugin bootstrap must load '$needle'."
    }
}

Write-Output 'MRepit School Core plugin structure checks passed.'
```

- [ ] **Step 2: Run test to verify it fails**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core\tests\static\plugin-structure.test.ps1
```

Expected: FAIL because plugin files do not exist yet.

- [ ] **Step 3: Create minimal plugin files**

Create `mrepit-school-core.php`:

```php
<?php
/**
 * Plugin Name: MRepit School Core
 * Description: Core school management module for MRepit.
 * Version: 0.1.0
 * Author: MRepit
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MREPIT_SCHOOL_CORE_VERSION', '0.1.0');
define('MREPIT_SCHOOL_CORE_LOADED', true);
define('MREPIT_SCHOOL_CORE_PATH', plugin_dir_path(__FILE__));
define('MREPIT_SCHOOL_CORE_URL', plugin_dir_url(__FILE__));

require_once MREPIT_SCHOOL_CORE_PATH . 'includes/bootstrap.php';
```

Create `includes/bootstrap.php` with required file loading.

- [ ] **Step 4: Run test to verify it passes**

Run:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core\tests\static\plugin-structure.test.ps1
```

Expected: PASS.

## Task 2: Move School Module Files

- [ ] **Step 1: Copy school module files into plugin**

Copy:

- `themes\mrepitnew\includes\users_controls\common\*.php` to `plugins\mrepit-school-core\includes\common\`
- `parents_controls\list-table.php`, `pages.php`, `handlers.php` to `includes\parents\`
- `students_controls\list-table.php`, `pages.php`, `handlers.php` to `includes\students\`
- `teachers_controls\list-table.php`, `pages.php`, `handlers.php` to `includes\teachers\`

- [ ] **Step 2: Update bootstrap paths**

Use plugin paths:

```php
$base = MREPIT_SCHOOL_CORE_PATH . 'includes/';
require_once $base . 'common/capabilities.php';
```

- [ ] **Step 3: Run plugin syntax check**

Run:

```powershell
$files = rg --files D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core -g "*.php"; foreach ($file in $files) { D:\OSPanel\modules\php\PHP_8.1\php.exe -l $file }
```

Expected: no syntax errors.

## Task 3: Theme Fallback Guard

- [ ] **Step 1: Add failing static test to theme**

Extend `tests/static/school-users-security.test.ps1` or create a new test that requires `functions.php` to check `MREPIT_SCHOOL_CORE_LOADED` before loading `includes/users_controls/users-controls.php`.

- [ ] **Step 2: Run test to verify it fails**

Expected: FAIL because theme currently loads users controls unconditionally.

- [ ] **Step 3: Update `functions.php`**

Replace:

```php
require_once __DIR__ . '/includes/users_controls/users-controls.php';
```

with:

```php
if (!defined('MREPIT_SCHOOL_CORE_LOADED')) {
    require_once __DIR__ . '/includes/users_controls/users-controls.php';
}
```

- [ ] **Step 4: Run test to verify it passes**

Expected: PASS.

## Task 4: Plugin Git Repository

- [ ] **Step 1: Initialize plugin Git repository**

Run in `wp-content/plugins/mrepit-school-core`:

```powershell
git init
```

- [ ] **Step 2: Add plugin files**

Run:

```powershell
git add .
git commit -m "feat: scaffold school core plugin"
```

Expected: plugin has its own initial commit.

## Task 5: Documentation and Verification

- [ ] **Step 1: Update theme README**

Document that school business logic is migrating to `mrepit-school-core` and that the theme has a temporary fallback guard.

- [ ] **Step 2: Update work plan**

Mark completed Phase 1 tasks in `docs/online-school-work-plan.md`.

- [ ] **Step 3: Run all checks**

Run theme static tests:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tests\static\no-mojibake.test.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File tests\static\telegram-webhook-security.test.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File tests\static\school-users-security.test.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File tests\static\school-sync-security.test.ps1
```

Run plugin static test:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File D:\OSPanel\domains\mrepitnew\wp-content\plugins\mrepit-school-core\tests\static\plugin-structure.test.ps1
```

Run syntax checks for theme and plugin.

- [ ] **Step 4: Commit theme documentation/fallback changes**

Run in theme repository:

```powershell
git add README.md docs functions.php tests
git commit -m "chore: prepare theme for school core plugin"
```

Expected: theme has a commit documenting and supporting the plugin extraction.
