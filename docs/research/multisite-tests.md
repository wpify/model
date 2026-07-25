# Research: multisite wiring for the test suite

Resolves [#13](https://github.com/wpify/model/issues/13). Assumes the integration-first
wp-env + wp-phpunit base wiring researched in the sibling ticket.

## Why multisite is needed at all

Only one model pair depends on multisite APIs:

- `src/Site.php` — models `WP_Site` fields (`blog_id`, `domain`, `path`, …) plus site meta.
- `src/SiteRepository.php` — calls `get_site()`, `get_sites()`, `wp_delete_site()`, and
  type-checks against `WP_Site`.

All of these exist only when `is_multisite()` is true (site meta additionally requires the
`blogmeta` table, created on multisite installs since WP 5.1). Everything else in the
library is single-site-safe.

> Side finding for the eventual test ticket: `SiteRepository::save()` calls
> `wp_update_comment()` / `wp_insert_comment()` and refreshes from `get_user_by()` — an
> obvious copy-paste bug. The first multisite save test will fail on it; that is a bug to
> fix, not a test to accommodate.

## How multisite test runs actually work (primary sources)

### The bootstrap decides per run

Core's test bootstrap (mirrored verbatim by the `wp-phpunit/wp-phpunit` composer package)
determines multisite mode like this
([bootstrap.php](https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/includes/bootstrap.php)):

```php
$multisite = ( '1' === getenv( 'WP_MULTISITE' ) );
$multisite = $multisite || ( defined( 'WP_TESTS_MULTISITE' ) && WP_TESTS_MULTISITE );
$multisite = $multisite || ( defined( 'MULTISITE' ) && MULTISITE );
```

So **either** the `WP_MULTISITE=1` environment variable **or** a `WP_TESTS_MULTISITE`
constant (typically set via `<php><const …>` in a phpunit config) flips the mode. Core's
own convention is the constant, via a second config file
([multisite.xml](https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/multisite.xml)),
invoked as `phpunit -c tests/phpunit/multisite.xml`
([core handbook](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)).

Crucially, the bootstrap **reinstalls the tests install on every run** and passes the
multisite flag into that install:

```php
if ( '1' !== getenv( 'WP_TESTS_SKIP_INSTALL' ) ) {
    system( WP_PHP_BINARY . ' ' . escapeshellarg( __DIR__ . '/install.php' )
        . ' ' . escapeshellarg( $config_file_path ) . ' ' . $ms_tests . ' ' . $core_tests, $retval );
}
```

The same database (same `wptests_` table prefix) is therefore installed fresh as
single-site or multisite depending on the flag of *that* run. Nothing about the multisite
decision is baked into the environment.

### `ms-required` / `ms-excluded` are pure config conventions

Nothing in the bootstrap or `WP_UnitTestCase` skips these groups automatically. The
mechanism is entirely `<groups><exclude>` in the two phpunit configs:

- Core's default
  [phpunit.xml.dist](https://github.com/WordPress/wordpress-develop/blob/trunk/phpunit.xml.dist)
  (single-site) excludes group `ms-required` (alongside `ajax`, `ms-files`, …).
- Core's [multisite.xml](https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/multisite.xml)
  sets `<const name="WP_TESTS_MULTISITE" value="1"/>` and excludes group `ms-excluded`.

As belt-and-braces, `WP_UnitTestCase` offers runtime helpers
(`skipWithoutMultisite()` / `skipWithMultisite()`,
[abstract-testcase.php](https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/includes/abstract-testcase.php))
"for use in conjunction with" those groups — useful so a multisite-only test degrades to
*skipped* instead of *fatal* if someone runs it outside the right config.

## Can one wp-env instance serve both runs? Yes.

- wp-env's tests environment ships the core PHPUnit scaffolding and exposes
  `WP_TESTS_DIR`; commands run inside it via `wp-env run tests-cli …`
  ([wp-env docs](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)).
- The `.wp-env.json` `"multisite": true` option only converts the *browsable* dev/tests
  sites to multisite. It is **irrelevant to phpunit**: the tests install is created by
  `install.php` on each run, in its own tables, with multisite-ness decided solely by the
  env var/constant at bootstrap time (see above). Do not set it for this.
- Therefore: **one wp-env instance, two sequential phpunit invocations** (single-site,
  then multisite) is the supported pattern. No second instance, no re-provisioning.
  Precedent: Gutenberg runs both `phpunit -c phpunit.xml.dist` and
  `phpunit -c phpunit/multisite.xml` against the same wp-env stack
  ([package.json](https://github.com/WordPress/gutenberg/blob/trunk/package.json),
  scripts `test:unit:php:base` / `test:unit:php:multisite:base`).

The only constraint: the two runs cannot execute concurrently against the same tests
database, since each reinstalls the same tables. Sequential is fine.

## Recommended layout

Follow core's two-config convention, scaled down. One test tree, groups for the split,
a tiny second config as a second command.

```
tests/
  bootstrap.php               # shared: requires wp-phpunit bootstrap
  Integration/
    ...                       # all single-site tests
    SiteRepositoryTest.php    # #[Group('ms-required')] + skipWithoutMultisite() guard
phpunit.xml.dist              # default run: <exclude><group>ms-required</group></exclude>
phpunit-multisite.xml         # <const name="WP_TESTS_MULTISITE" value="1"/>,
                              # runs only <group>ms-required</group>
```

Key points:

1. **Separate config invoked as a second command — not groups within one config.**
   A phpunit config cannot vary constants per group; multisite-ness is a bootstrap-time,
   whole-process decision, so a second invocation is unavoidable. Two small XML files
   (the multisite one ~15 lines) is exactly how core and Gutenberg model this.
2. **Multisite config runs only the `ms-required` group.** Core re-runs its whole suite
   under multisite, but for this library the multisite surface is one small model pair;
   re-running everything would double CI time for near-zero signal. If a future model
   grows multisite-sensitive behaviour, flip the multisite config to "whole suite minus
   `ms-excluded`" then (Rule of Three).
3. **Tag + guard the Site tests.** `#[Group('ms-required')]` (or `@group ms-required`)
   for the config-level split, plus `$this->skipWithoutMultisite();` in `set_up()` so a
   bare `vendor/bin/phpunit` without the exclusion never fatals on `get_sites()`.
4. **Composer scripts** so the commands are one word each:

   ```json
   "scripts": {
     "test":           "wp-env run tests-cli --env-cwd=wp-content/plugins/wpify-model vendor/bin/phpunit",
     "test:multisite": "wp-env run tests-cli --env-cwd=wp-content/plugins/wpify-model vendor/bin/phpunit -c phpunit-multisite.xml"
   }
   ```

   (Exact `--env-cwd` mapping comes from the base-wiring ticket; the shape is what
   matters. `WP_MULTISITE=1 vendor/bin/phpunit` via `bash -c` would also work, but the
   config file is declarative, carries the group filter, and matches core convention.)

## CI shape

Do not double the matrix. Multisite mode exercises the WP bootstrap path, not PHP
language behaviour, so one PHP version gives full signal:

```yaml
jobs:
  test:                    # single-site, full matrix
    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3', '8.4']
    steps: [..., composer test]

  test-multisite:          # one version only
    steps: [..., composer test, composer test:multisite]   # php 8.4 (or lowest supported)
```

Either a standalone `test-multisite` job on the newest (or lowest-supported) PHP, or a
single `multisite: true` include-entry appended to the matrix that adds the
`composer test:multisite` step. Both runs share one wp-env boot per job; the multisite
job costs only the extra seconds of the small `ms-required` group.

## Sources

- [Core handbook: PHPUnit tests](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/) — `phpunit -c tests/phpunit/multisite.xml`
- [wordpress-develop `tests/phpunit/multisite.xml`](https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/multisite.xml) — `WP_TESTS_MULTISITE` const, `ms-excluded` exclusion
- [wordpress-develop `phpunit.xml.dist`](https://github.com/WordPress/wordpress-develop/blob/trunk/phpunit.xml.dist) — `ms-required` excluded in single-site runs
- [wordpress-develop `tests/phpunit/includes/bootstrap.php`](https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/includes/bootstrap.php) — `WP_MULTISITE` env var / `WP_TESTS_MULTISITE` detection, per-run `install.php` with multisite flag
- [wordpress-develop `tests/phpunit/includes/abstract-testcase.php`](https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/includes/abstract-testcase.php) — `skipWithoutMultisite()` / `skipWithMultisite()`
- [wp-phpunit](https://github.com/wp-phpunit/wp-phpunit) + [docs](https://github.com/wp-phpunit/docs) — composer mirror of the core test library; config via `WP_PHPUNIT__TESTS_CONFIG`
- [wp-env docs](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) — tests environment, `WP_TESTS_DIR`, `multisite` config option (dev sites only)
- [Gutenberg `package.json`](https://github.com/WordPress/gutenberg/blob/trunk/package.json) — `test:unit:php:base` vs `test:unit:php:multisite:base` against one wp-env stack
