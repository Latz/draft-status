# SonarCloud Quality Gate Fix — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix 136 SonarCloud quality gate failures by correcting test exclusion globs and adding WordPress PHP stubs so Sonar knows the real function signatures.

**Architecture:** Two independent changes to `sonar-project.properties`: fix the `sonar.exclusions` glob pattern so the entire `tests/` tree is excluded, and add a `stubs/wordpress-stubs.php` file with correct WordPress function signatures to eliminate false-positive BUG reports in production code.

**Tech Stack:** SonarCloud, PHP, sonar-project.properties

---

## Chunk 1: Fix test exclusions and add WordPress stubs

### Task 1: Update `sonar-project.properties`

**Files:**
- Modify: `sonar-project.properties`

- [ ] **Step 1: Open and read the current file**

Read `sonar-project.properties`. It currently contains:

```
sonar.exclusions=**/node_modules/**,**/vendor/**,tests/**,memory/**,*.md,*.txt,*.sh,codereview.md,wp_report.md
```

- [ ] **Step 2: Update `sonar.exclusions`**

Replace the `tests/**` segment with `tests/**/*,tests/*` and append the stubs exclusion. Also add `sonar.test.inclusions` and `sonar.php.stubs`.

The final file should be:

```properties
sonar.projectKey=Latz_draft-status
sonar.organization=latz-1
sonar.host.url=https://sonarcloud.io

# Source files
sonar.sources=.

# Exclusions
sonar.exclusions=**/node_modules/**,**/vendor/**,tests/**/*,tests/*,stubs/**/*,stubs/*,memory/**,*.md,*.txt,*.sh,codereview.md,wp_report.md

# Test inclusions (marks tests/ as test code, distinct from exclusions)
sonar.test.inclusions=tests/**/*,tests/*

# WordPress function stubs — tells SonarCloud the real WP function signatures
sonar.php.stubs=stubs/wordpress-stubs.php
```

- [ ] **Step 3: Verify the diff looks correct**

Run:
```bash
git diff sonar-project.properties
```

Expected: `tests/**` replaced by `tests/**/*,tests/*`, new `stubs/**/*,stubs/*` added, new `sonar.test.inclusions` line, new `sonar.php.stubs` line.

---

### Task 2: Create `stubs/wordpress-stubs.php`

**Files:**
- Create: `stubs/wordpress-stubs.php`

- [ ] **Step 1: Create the stubs directory and file**

Create `stubs/wordpress-stubs.php` with the following content — global-scope function declarations only, no namespace, no logic, empty bodies:

```php
<?php
/**
 * WordPress function stubs for SonarCloud PHP analysis.
 *
 * These declarations tell SonarCloud the correct signatures for WordPress
 * functions that its built-in PHP stubs declare with zero parameters.
 * This file is excluded from analysis via sonar.exclusions.
 *
 * @see sonar-project.properties sonar.php.stubs
 */

/**
 * @param string   $tag
 * @param callable $function_to_remove
 * @param int      $priority
 * @return bool
 */
function remove_filter( $tag, $function_to_remove, $priority = 10 ) {}

/**
 * @param string $object_subtype
 * @param string $meta_key
 * @param array  $args
 * @return bool
 */
function register_post_meta( $object_subtype, $meta_key, $args ) {}

/**
 * @param string       $handle
 * @param string       $src
 * @param string[]     $deps
 * @param string|false $ver
 * @param string       $media
 */
function wp_enqueue_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) {}

/**
 * @param string       $handle
 * @param string       $src
 * @param string[]     $deps
 * @param string|false $ver
 * @param bool         $in_footer
 */
function wp_enqueue_script( $handle, $src = '', $deps = [], $ver = false, $in_footer = false ) {}

/**
 * @param string        $widget_id
 * @param string        $widget_name
 * @param callable      $callback
 * @param callable|null $control_callback
 * @param array|null    $callback_args
 */
function wp_add_dashboard_widget( $widget_id, $widget_name, $callback, $control_callback = null, $callback_args = null ) {}

/**
 * @param string          $id
 * @param string          $title
 * @param callable        $callback
 * @param string|null     $screen
 * @param string          $context
 * @param string          $priority
 * @param array|null      $callback_args
 */
function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {}
```

- [ ] **Step 2: Verify valid PHP syntax**

Run:
```bash
php -l stubs/wordpress-stubs.php
```
Expected: `No syntax errors detected in stubs/wordpress-stubs.php`

- [ ] **Step 3: Verify all 6 function stubs are present**

Run:
```bash
grep -c "^function " stubs/wordpress-stubs.php
```
Expected: `6`

---

### Task 3: Commit both changes

**Files:**
- `sonar-project.properties`
- `stubs/wordpress-stubs.php`

- [ ] **Step 1: Stage both files**

```bash
git add sonar-project.properties stubs/wordpress-stubs.php
```

- [ ] **Step 2: Verify staging**

```bash
git status
```
Expected: both files shown as staged (green).

- [ ] **Step 3: Commit**

```bash
git commit -m "fix: correct SonarCloud test exclusions and add WordPress PHP stubs"
```

- [ ] **Step 4: Verify commit contents**

```bash
git show --stat HEAD
```
Expected: shows `sonar-project.properties` and `stubs/wordpress-stubs.php` in the changed files list.

---

### Task 4: Push and verify quality gate

- [ ] **Step 1: Push to trigger SonarCloud analysis**

```bash
git push
```

- [ ] **Step 2: Wait for SonarCloud analysis to complete**

Go to https://sonarcloud.io and check the `Latz_draft-status` project. Analysis typically takes 1–3 minutes after the push.

- [ ] **Step 3: Download fresh report and verify**

The report script is at `/home/latz/draft-status/sonar-report.sh`. Verify it exists first, then run it:

```bash
ls /home/latz/draft-status/sonar-report.sh && bash /home/latz/draft-status/sonar-report.sh
```

If the script is absent, download the report manually. The token is the value from `sonar-report.sh` (variable `SONAR_TOKEN` at the top of that file), or retrieve it from SonarCloud → My Account → Security → Generate Token:
```bash
curl -s -u "YOUR_SONAR_TOKEN_HERE:" \
  "https://sonarcloud.io/api/issues/search?componentKeys=Latz_draft-status&resolved=false&ps=500&organization=latz-1" \
  | jq '.total'
```
Expected: `sonar-report.md` (or the jq output) shows 0 open issues.

- [ ] **Step 4: Confirm quality gate status**

Check SonarCloud dashboard — quality gate should show **Passed**.
