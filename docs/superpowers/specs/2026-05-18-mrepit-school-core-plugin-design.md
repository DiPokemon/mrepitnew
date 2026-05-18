# MRepit School Core Plugin Design

## Goal

Move school business logic out of the `mrepitnew` Elementor theme into a standalone WordPress plugin named `mrepit-school-core`, while keeping the current admin workflows working during migration.

## Current Context

- WordPress root: `D:\OSPanel\domains\mrepit`
- Active theme: `mrepitnew`
- Parent theme: `hello-elementor`
- Active plugins: `elementor`, `elementor-pro`
- Theme Git repository: `wp-content/themes/mrepitnew`
- New plugin path: `wp-content/plugins/mrepit-school-core`
- New plugin Git repository: `wp-content/plugins/mrepit-school-core/.git`

## Architecture

The plugin owns school-domain behavior:

- roles and capabilities;
- school permission helpers;
- protected relationship meta;
- parent/student bidirectional sync;
- teacher user <-> teacher post linking;
- School admin menu;
- admin CRUD screens for teachers, students, and parents;
- user-level Carbon Fields used by school roles.

The theme keeps visual and marketing behavior:

- Elementor theme integration;
- CSS/JS assets;
- public CPT/meta definitions for `service`, `review`, and `teacher` until a later phase decides whether those also move;
- Telegram form endpoint until notifications become part of the platform core;
- Elementor dynamic tags.

## Migration Strategy

Use staged extraction. The first migration step used a temporary theme fallback; after plugin activation was verified, Phase 1 cleanup removed the fallback from the theme.

1. Create `mrepit-school-core` as a standalone plugin with its own Git repository.
2. Move the school user-control module from the theme to the plugin with the same function names and hooks.
3. Define `MREPIT_SCHOOL_CORE_LOADED` in the plugin.
4. Remove the theme bootstrap dependency on `includes/users_controls/users-controls.php` after plugin activation is verified.
5. Add static checks that prevent duplicate loading and verify plugin structure.
6. Verify syntax for both theme and plugin.

This kept rollback simple during the transition. Current Phase 1 status: the plugin is active and the theme no longer keeps a PHP fallback copy of the school module.

## Plugin File Structure

```text
wp-content/plugins/mrepit-school-core/
  .git/
  README.md
  mrepit-school-core.php
  includes/
    bootstrap.php
    common/
      admin-menu.php
      capabilities.php
      carbon-fields.php
      helpers.php
      security.php
      sync.php
      teacher-link.php
    parents/
      handlers.php
      list-table.php
      pages.php
    students/
      handlers.php
      list-table.php
      pages.php
    teachers/
      handlers.php
      list-table.php
      pages.php
  tests/
    static/
      plugin-structure.test.ps1
```

## Data Flow

WordPress loads active plugins before the active theme:

1. `mrepit-school-core.php` defines plugin constants and loads `includes/bootstrap.php`.
2. `bootstrap.php` loads common helpers, roles/caps, security, sync, teacher-link, admin menu, Carbon Fields user meta, and role CRUD modules.
3. Theme `functions.php` loads only theme-owned presentation integrations; school user-management code is loaded by the plugin.
4. Existing admin URLs and handler actions stay unchanged, so managers keep using the same School UI.

## Error Handling

- Plugin files guard against direct access with `ABSPATH`.
- Plugin bootstrap loads files only once.
- The plugin does not delete data on deactivation.
- Existing hooks and function names remain stable to reduce migration risk.
- If the plugin is disabled, public theme rendering remains available, but the custom School admin module is unavailable.

## Testing

Static checks:

- plugin main file has a valid WordPress plugin header;
- plugin defines `MREPIT_SCHOOL_CORE_LOADED`, path, URL, and version constants;
- plugin bootstrap includes expected common and role modules;
- theme `functions.php` has a guard around `users-controls.php`;
- no mojibake markers;
- Telegram webhook security checks still pass;
- school users security checks still pass;
- parent/student sync checks still pass.

Syntax checks:

- all PHP files in the theme excluding `vendor`;
- all PHP files in the plugin.

Manual WordPress check after activation:

- activate `mrepit-school-core`;
- open admin menu `School`;
- verify lists for teachers, students, parents;
- create/edit one test user per role on a disposable local DB snapshot.

## Scope Boundary

Phase 1 does not add LMS, scheduling, billing, homework, custom tables, Action Scheduler, or frontend dashboards. Those remain later phases after school-core is independent from the theme.
