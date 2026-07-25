# Research: wp-env + wp-phpunit harness for a composer library

Resolves [#11](https://github.com/wpify/model/issues/11). Researched 2026-07-25 against the wp-env README/source (Gutenberg trunk), wp-phpunit docs, and Make WordPress Core posts. Sources linked inline; caveats at the end.

## TL;DR recommendation

Use **wp-env's built-in WordPress PHPUnit test library** (mounted at `/wordpress-phpunit`, exposed as `WP_TESTS_DIR`, version-matched to the installed WordPress and pre-wired to the tests database) rather than managing `wp-phpunit/wp-phpunit` + a hand-written `wp-tests-config.php` ourselves. No plugin shim is needed: mount the repo into the tests instance via `mappings`, load the library through the composer autoloader in the test bootstrap, and run PHPUnit inside the **`tests-cli`** container. Pin **PHPUnit ^9.6** + **yoast/phpunit-polyfills ^1.1**. Coverage via **Xdebug** (`wp-env start --xdebug=coverage`); pcov is not available in wp-env images. Vary PHP per CI job with **`WP_ENV_PHP_VERSION`**.

## 1. Why no plugin shim is required

The WordPress core test bootstrap (`$WP_TESTS_DIR/includes/bootstrap.php`) loads a full WordPress with a throwaway database; "code under test" is whatever you `require` before/while it boots. For a plugin you'd hook `tests_add_filter( 'muplugins_loaded', ... )` to load the plugin main file; for a pure composer library there is no entrypoint to load — requiring `vendor/autoload.php` in the test bootstrap is sufficient. The wp-phpunit project itself demonstrates this with a library-style example project ([wp-phpunit docs](https://github.com/wp-phpunit/docs), [example-project](https://github.com/wp-phpunit/example-project)).

The only reason to add a shim (a one-file mu-plugin dropped via `mappings` to `wp-content/mu-plugins/`) is if the library must be active during *other* plugins' load sequence (e.g. registering things before WooCommerce init). For wpify/model's own suite, hooking `muplugins_loaded`/`plugins_loaded` from the bootstrap covers that.

## 2. How wp-env provides the test library (current guidance)

- wp-env **v5.0.0 removed the dedicated `phpunit` (and `composer`) containers**. Migration per changelog: "If you are currently using the `run composer` or `run phpunit` command you can migrate to `run cli composer` or `run tests-cli phpunit` respectively." It also removed the `WP_PHPUNIT__TESTS_CONFIG` env var that the old container set. ([wp-env CHANGELOG](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/CHANGELOG.md))
- Since v5, wp-env reads the installed WordPress version, downloads the matching core PHPUnit test files, mounts them at **`/wordpress-phpunit`**, sets **`WP_TESTS_DIR=/wordpress-phpunit`** in all containers, and writes a ready `wp-tests-config.php` into that directory (ABSPATH `/var/www/html/`, tests DB credentials for the dedicated `tests-mysql` service). ([CHANGELOG 5.0.0](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/CHANGELOG.md), [build-docker-compose-config.js](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/lib/runtime/docker/build-docker-compose-config.js), [wordpress.js](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/lib/runtime/docker/wordpress.js), [Gutenberg PR #41852](https://github.com/WordPress/gutenberg/pull/41852))
- Since v7.0.0, `composer` and a compatible `phpunit` are installed globally in every container, but a project should run its **own `vendor/bin/phpunit`** so the version is pinned by composer. ([CHANGELOG](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/CHANGELOG.md))
- Tests run in the **tests instance** (`tests-wordpress` / `tests-cli`, port 8889, own DB) so the dev instance (port 8888) stays clean. Command shape: `wp-env run tests-cli --env-cwd=<path in container> vendor/bin/phpunit`. ([wp-env README](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/README.md))

Consequence: under wp-env, the composer package `wp-phpunit/wp-phpunit` is **redundant** — wp-env ships the same core test library, already version-matched and DB-configured. Keep `wp-phpunit/wp-phpunit` only if you also want to run the suite outside Docker against a local MySQL (the bootstrap below supports both via `WP_TESTS_DIR` -> `WP_PHPUNIT__DIR` fallback).

## 3. PHPUnit versions and polyfills

- The core test library supports **PHPUnit 5.7.21–9.x**; WordPress itself runs on PHPUnit 9.6. **PHPUnit 10 is skipped**; compatibility work targets PHPUnit 11.1+/12 but has not shipped for the stable test library as of mid-2026 — treat PHPUnit 10/11 as unsupported. ([Make WordPress Core, 2021-09-27](https://make.wordpress.org/core/2021/09/27/changes-to-the-wordpress-core-php-test-suite/), [Trac #59486](https://core.trac.wordpress.org/ticket/59486), [Trac #62004](https://core.trac.wordpress.org/ticket/62004))
- **`yoast/phpunit-polyfills` is required** by the core bootstrap (minimum 1.1.0). It must be autoloadable before the core bootstrap runs — requiring `vendor/autoload.php` first satisfies this; defensively you can also `define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', ... )`. Test classes extend `WP_UnitTestCase` and use snake_case fixtures (`set_up()`/`tear_down()`, not `setUp()`). ([Make WordPress Core post](https://make.wordpress.org/core/2021/09/27/changes-to-the-wordpress-core-php-test-suite/))
- On PHP 8.0–8.4 use **PHPUnit ^9.6** (PHP 8.1/8.2+ support arrived in wp-env 5.11.0's phpunit handling; PHPUnit 9.6 itself runs fine on 8.4, with noise possible from deprecations — see caveats).

## 4. Recommended wiring, file by file

### `.wp-env.json`

```json
{
	"core": null,
	"phpVersion": "8.2",
	"plugins": [],
	"mappings": {
		"wp-content/wpify-model": "."
	},
	"config": {
		"WP_DEBUG": true
	}
}
```

- `core: null` = latest stable WordPress; pin e.g. `"WordPress/WordPress#6.8"` for reproducible CI.
- The repo is not a plugin, so it goes in via `mappings` (mounted into both dev and tests instances), not `plugins`. Path inside container: `/var/www/html/wp-content/wpify-model`.
- For WooCommerce-dependent tests add `"plugins": [ "https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip" ]` and load WC in the bootstrap filter below.

### `composer.json` (dev additions)

```json
{
	"require-dev": {
		"phpunit/phpunit": "^9.6",
		"yoast/phpunit-polyfills": "^1.1"
	},
	"autoload-dev": {
		"psr-4": { "Wpify\\Model\\Tests\\": "tests/" }
	},
	"scripts": {
		"test": "wp-env run tests-cli --env-cwd=wp-content/wpify-model vendor/bin/phpunit",
		"test:coverage": "wp-env run tests-cli --env-cwd=wp-content/wpify-model vendor/bin/phpunit --coverage-html coverage"
	}
}
```

(`wp-phpunit/wp-phpunit: ^6.0` optional, only for docker-less runs.)

### `phpunit.xml.dist`

```xml
<?xml version="1.0"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.6/phpunit.xsd"
	bootstrap="tests/bootstrap.php"
	colors="true">
	<testsuites>
		<testsuite name="integration">
			<directory>tests/integration</directory>
		</testsuite>
	</testsuites>
	<coverage>
		<include>
			<directory suffix=".php">src</directory>
		</include>
	</coverage>
</phpunit>
```

### `tests/bootstrap.php`

```php
<?php
// Composer autoloader first: makes the library + polyfills available.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// wp-env sets WP_TESTS_DIR=/wordpress-phpunit (config pre-generated).
// WP_PHPUNIT__DIR is the fallback for docker-less runs via wp-phpunit/wp-phpunit.
$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: getenv( 'WP_PHPUNIT__DIR' );

if ( ! $_tests_dir || ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "Could not find WordPress test library. Run inside wp-env: wp-env run tests-cli ...\n" );
	exit( 1 );
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', static function () {
	// Library is already autoloaded; do per-suite setup here,
	// e.g. nothing for core models. WooCommerce, if mapped as a plugin,
	// is activated by wp-env and loads normally.
} );

require $_tests_dir . '/includes/bootstrap.php';
```

Bootstrap shape per [wp-phpunit docs](https://github.com/wp-phpunit/docs); under wp-env no `WP_PHPUNIT__TESTS_CONFIG`/`wp-tests-config.php` of our own is needed because wp-env generates one inside `WP_TESTS_DIR`.

### Commands

```sh
npm -g i @wordpress/env        # or use npx wp-env
wp-env start                   # boots dev (8888) + tests (8889) instances
wp-env run tests-cli --env-cwd=wp-content/wpify-model composer install
wp-env run tests-cli --env-cwd=wp-content/wpify-model vendor/bin/phpunit
```

Running `composer install` *inside* the container guarantees platform-check consistency with the container PHP; since the repo is bind-mounted, host-side `composer install` also works if host PHP satisfies the constraints.

## 5. Code coverage

- wp-env images install **Xdebug via pecl** (3.1.6 pinned on PHP 7.x, latest otherwise); **pcov is not installed** and wp-env has no supported hook for adding PHP extensions to its generated images. ([docker-config.js](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/lib/runtime/docker/docker-config.js))
- Xdebug is only enabled when the environment is started with the flag; coverage mode: `wp-env start --xdebug=coverage` (modes are comma-separable, e.g. `--xdebug=develop,coverage`; requires PHP >= 7.2). ([wp-env README](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/README.md))
- Then: `wp-env run tests-cli --env-cwd=wp-content/wpify-model vendor/bin/phpunit --coverage-clover coverage.xml` (the clover file lands in the repo via the bind mount).
- Trade-off: Xdebug coverage is markedly slower than pcov. If pcov is a hard requirement, the harness would have to leave wp-env for the coverage job (e.g. shivammathur/setup-php + wp-phpunit + MySQL service), which is why the composer fallback path in the bootstrap is worth keeping.

## 6. PHP version matrix in GitHub Actions

- Per-job PHP selection: `phpVersion` in `.wp-env.json`, overridable without file edits via the **`WP_ENV_PHP_VERSION`** environment variable (also `.wp-env.override.json`). `null`/unset = the default PHP of the WordPress release. ([wp-env README](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/README.md))
- Under the hood the images are the official Docker Hub tags `wordpress:php{X.Y}` / `wordpress:cli-php{X.Y}`, so available PHP versions = available official tags. Verified pullable today: `php8.0`, `php8.1`, `php8.2`, `php8.3`, `php8.4` (+ matching `cli-` tags). Caveat: the `php8.0` tag is frozen (official images stopped rebuilding it when PHP 8.0 went EOL); wp-env mounts its own downloaded WordPress over `/var/www/html`, so the stale bundled WP doesn't matter, but the PHP 8.0 runtime itself no longer receives patch updates. ([docker-config.js](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/lib/runtime/docker/docker-config.js), [Docker Hub wordpress tags](https://hub.docker.com/_/wordpress/tags))

```yaml
name: Integration tests
on: [push, pull_request]
jobs:
  phpunit:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: [ '8.0', '8.2', '8.4' ]
    env:
      WP_ENV_PHP_VERSION: ${{ matrix.php }}
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: 20 }
      - run: npm -g i @wordpress/env
      - run: wp-env start
      - run: wp-env run tests-cli --env-cwd=wp-content/wpify-model composer install --no-interaction
      - run: wp-env run tests-cli --env-cwd=wp-content/wpify-model vendor/bin/phpunit
```

For the coverage job, replace `wp-env start` with `wp-env start --xdebug=coverage` and add `--coverage-clover coverage.xml` to the phpunit call.

## 7. Caveats / unknowns

1. **PHPUnit 9.6 on PHP 8.4** emits deprecation noise from PHPUnit internals in some setups; it runs, but if it becomes a problem the escape hatch is waiting for the core test library's PHPUnit 11+ support ([Trac #62004](https://core.trac.wordpress.org/ticket/62004)) — do not jump to PHPUnit 10/11 yet.
2. **Polyfills 2.x/3.x**: core's bootstrap enforces only a minimum (1.1.0), so newer majors generally pass, but `^1.1` is the constraint with the widest verified WP-version compatibility; re-verify before widening the constraint.
3. **Xdebug in tests containers**: the `--xdebug` flag historically documented against "the development environment"; the Dockerfile generator installs Xdebug into all four service images, and community usage runs coverage in `tests-cli`, but this exact combination should be smoke-tested first (`wp-env run tests-cli php -m | grep xdebug` after `wp-env start --xdebug=coverage`).
4. **Old WordPress versions**: if the matrix later adds old WP cores (e.g. WP 6.2 on PHP 8.4), expect core-side PHP deprecation warnings converted to test failures; pin sensible WP/PHP pairs.
5. **wp-env is Node tooling**: CI needs Node + Docker (both present on `ubuntu-latest`); local contributors need Docker running.

## Sources

- [wp-env README (@wordpress/env, Gutenberg trunk)](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/README.md)
- [wp-env CHANGELOG (5.0.0 phpunit-container removal, 7.0.0 global composer/phpunit)](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/CHANGELOG.md)
- [wp-env docker-config.js (base images, Xdebug install, phpunit install)](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/lib/runtime/docker/docker-config.js)
- [wp-env build-docker-compose-config.js (WP_TESTS_DIR, /wordpress-phpunit mount)](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/lib/runtime/docker/build-docker-compose-config.js)
- [Gutenberg PR #41852 — utilize WP PHPUnit test library included by wp-env](https://github.com/WordPress/gutenberg/pull/41852)
- [wp-phpunit/wp-phpunit](https://github.com/wp-phpunit/wp-phpunit) and [wp-phpunit docs](https://github.com/wp-phpunit/docs)
- [Make WordPress Core: Changes to the WordPress Core PHP Test Suite (2021-09-27)](https://make.wordpress.org/core/2021/09/27/changes-to-the-wordpress-core-php-test-suite/)
- [Trac #59486 — PHPUnit 10/11 compatibility](https://core.trac.wordpress.org/ticket/59486), [Trac #62004 — PHPUnit 10/11/12 readiness](https://core.trac.wordpress.org/ticket/62004)
- [Docker Hub official wordpress image tags](https://hub.docker.com/_/wordpress/tags)
