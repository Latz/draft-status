# SonarCloud Quality Gate Fix — Design Spec

_Date: 2026-03-16_

## Problem

The SonarCloud quality gate fails with 136 open issues:

- 126 issues in `tests/bootstrap.php` (unused stub parameters, naming violations, empty bodies) — caused by SonarCloud analyzing test infrastructure despite `tests/**` exclusion
- 2 issues in `tests/Integration/` files — MINOR naming violations on fields `$post_ids` and `$editor_id`
- 2 MAJOR issues in `tests/wp-tests-config.php` — commented-out code
- 8 MAJOR BUG issues in `draft-status.php` and `class-draft-status-renderer.php` — false positives where SonarCloud's built-in PHP stubs declare WordPress functions with 0 parameters, causing it to flag correct WP API calls as "too many arguments"

## Solution

Two independent changes:

### 1. Fix test exclusions in `sonar-project.properties`

**Change `sonar.exclusions`** from `tests/**` to `tests/**/*,tests/*`. The current `tests/**` glob in SonarCloud's dialect matches only one level deep, missing both `tests/bootstrap.php` (root) and `tests/Integration/*.php` (two levels deep). The combined pattern covers both cases.

**Add `sonar.test.inclusions=tests/**/*,tests/*`** to explicitly declare test code. This is distinct from exclusions and is the standard SonarCloud mechanism for marking test directories.

### 2. Add WordPress PHP stubs

**Create `stubs/wordpress-stubs.php`** — a new file containing only function signature declarations (empty bodies) for the WordPress functions that Sonar incorrectly treats as zero-argument functions:

- `remove_filter($tag, $function_to_remove, $priority = 10)`
- `register_post_meta($object_subtype, $meta_key, $args)`
- `wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all')`
- `wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false)`
- `wp_add_dashboard_widget($widget_id, $widget_name, $callback, $control_callback = null, $callback_args = null)`
- `add_meta_box($id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null)`

**Add `sonar.php.stubs=stubs/wordpress-stubs.php`** to `sonar-project.properties` so Sonar loads these signatures before analyzing production code.

**Add `stubs/**/*,stubs/*` to `sonar.exclusions`** so the stubs file itself is not analyzed as production code.

The stubs file must declare all functions at global scope with no namespace wrapping — SonarCloud PHP stubs require plain global-scope declarations.

## Files Changed

| File | Change |
|------|--------|
| `sonar-project.properties` | Update exclusions, add test.inclusions, add php.stubs |
| `stubs/wordpress-stubs.php` | New file — WP function signatures only |

## Expected Outcome

- 126 `tests/bootstrap.php` issues: resolved by exclusion
- 2 `tests/Integration/` naming issues: resolved by exclusion
- 2 `tests/wp-tests-config.php` commented-code issues: resolved by exclusion
- 8 production BUG issues: resolved by stubs
- Quality gate: passes

## Non-Goals

- No changes to `tests/bootstrap.php` (excluded, not rewritten)
- No `// NOSONAR` comments in production code
- No changes to test logic or plugin functionality
