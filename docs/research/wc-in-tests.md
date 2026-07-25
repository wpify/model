# Research: WooCommerce in the wp-env test suite

Resolves [#12](https://github.com/wpify/model/issues/12). Part of [#10](https://github.com/wpify/model/issues/10). Companion to the wp-env + wp-phpunit harness research ([#11](https://github.com/wpify/model/issues/11)).

## What the library actually needs from WooCommerce

The WC-facing surface (`src/Order*.php`, `src/Product*.php`, `src/ProductCat*.php`) touches:

- **Orders**: `WC_Order` / `WC_Abstract_Order`, `wc_get_order()`, `wc_get_order_id_by_order_key()`, `wc_get_orders()`, `$order->save()` (`src/OrderRepository.php`, `src/Order.php`).
- **Order items**: `WC_Order_Item` (+ line/fee/shipping subclasses), item totals/taxes via `get_total()`, `get_total_tax()`, `get_order()->get_items('tax')` (`src/OrderItem*.php`).
- **Products**: `WC_Product`, `wc_get_product()`, `wc_get_products()`, `$product->save()`, `WC_Tax::find_rates()`, `wc_get_price_including_tax()` / `wc_get_price_excluding_tax()` (`src/ProductRepository.php`, `src/Product.php`).
- **Product categories**: the `product_cat` taxonomy only (`src/ProductCatRepository.php`) — this works with plain WP term APIs once WC has registered the taxonomy.

So the tests need a fully installed WooCommerce (tables, data stores, taxonomies, tax engine), but only a small slice of its API.

## 1. Installing WC into wp-env and loading it in the phpunit bootstrap

### wp-env side

`.wp-env.json` `plugins` entries accept remote ZIP URLs and are downloaded into `~/.wp-env` and mounted/activated ([wp-env docs](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/README.md)):

```json
{
	"core": null,
	"plugins": [
		".",
		"https://downloads.wordpress.org/plugin/woocommerce.10.9.4.zip"
	]
}
```

Notes:

- `wpify/model` is a composer library, not a plugin; the harness ticket (#11) covers how `"."` gets mounted and how the bootstrap requires the library's autoloader. Whatever shape that takes, **WooCommerce must be listed in the same config that drives the phpunit environment** so it exists under `WP_PLUGIN_DIR` in the container.
- wp-env behavior changed in 2026: since `@wordpress/env` 11.x the separate tests containers (`tests-wordpress`, `tests-cli`, port 8889) are being phased out — `env.tests` / `testsEnvironment` are deprecated in favor of running `wp-env run cli phpunit` against the main instance, or a second `--config .wp-env.tests.json` environment for isolation ([README](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/README.md), [CHANGELOG 11.0.0](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/CHANGELOG.md)). On older wp-env (≤ 10.x) the equivalent is `env.tests.plugins` + `wp-env run tests-cli phpunit`. Either way the WC zip entry is identical; follow whichever layout #11 lands on.
- wp-env ships the WP core PHPUnit test library in-container and exposes it as `WP_TESTS_DIR`, so `tests_add_filter()` etc. are available without installing `wp-phpunit/wp-phpunit` yourself.

### Bootstrap side — activation state does not carry over

The wp-phpunit suite installs a fresh WP into the (tests) database on every run; it does **not** read the site's `active_plugins` option. Plugins must be loaded explicitly in the bootstrap, and WC must additionally be *installed* (its ~50 tables, roles, taxonomies) before the first test. This is exactly what WooCommerce's own bootstrap does ([tests/legacy/bootstrap.php](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/legacy/bootstrap.php)):

```php
// tests/bootstrap.php (sketch — final shape belongs to #11)
require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function () {
	require WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
	// + require the wpify/model autoloader / test plugin here.
} );

// WC's own suite installs on 'setup_theme', i.e. during core test-suite install,
// before the first test runs.
tests_add_filter( 'setup_theme', function () {
	define( 'WP_UNINSTALL_PLUGIN', true );
	define( 'WC_REMOVE_ALL_DATA', true );
	include WP_PLUGIN_DIR . '/woocommerce/uninstall.php';

	WC_Install::install();

	// Reload capabilities after install, see woocommerce/woocommerce#38031 pattern.
	$GLOBALS['wp_roles'] = null;
	wp_roles();
} );

require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';
```

Key facts verified against WC's bootstrap:

- WC is loaded on `muplugins_loaded` via `tests_add_filter()`, not activated via option.
- `WC_Install::install()` on `setup_theme` creates all WC tables (including Action Scheduler) and default data. Tables created in the bootstrap are permanent; per-test data isolation still comes from the core suite's transaction rollback.
- The core test suite forces `CREATE TABLE` → `CREATE TEMPORARY TABLE` via `query` filters *inside test cases*; anything that must create real tables mid-test (like HPOS toggling, below) has to remove those filters first. Creating everything in the bootstrap avoids the problem.

## 2. WC test helpers/factories: not consumable — hand-roll fixtures

**Finding: the helpers are not shipped and not packaged; hand-rolled CRUD fixtures are the right call.**

- `WC_Unit_Test_Case`, `WC_Helper_Product`, `WC_Helper_Order`, `HPOSToggleTrait` etc. live in the monorepo under [`plugins/woocommerce/tests/legacy/framework`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/legacy/framework) and [`tests/php/helpers`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/php/helpers).
- **Not in the plugin zip**: verified by listing `woocommerce.10.9.4.zip` from downloads.wordpress.org — it contains no PHP test framework (only a few frontend JS `test/` assets). Consequently `wpackagist-plugin/woocommerce` (which mirrors the zip) doesn't have them either.
- **No composer package**: WooCommerce does not publish its PHP test framework to Packagist, and installing the monorepo from GitHub source is not practical for a consumer (the plugin requires a pnpm/composer build step and the helpers assume WC's own bootstrap and directory layout). Community copy-in packages exist (e.g. [level-level/wp-browser-woocommerce](https://github.com/level-level/wp-browser-woocommerce)) but are third-party, stale, and framework-specific.
- Copy-in of helper files is possible but they drag in WC-internal namespaces (`Automattic\WooCommerce\Enums\OrderStatus`, REST-API test helpers, shipping/customer helpers) and churn with WC releases — a maintenance liability for the ~2 fixture shapes this library needs.

What WC's own helpers do is a thin wrapper over public CRUD anyway ([WC_Helper_Order](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/legacy/framework/helpers/class-wc-helper-order.php)): `wc_create_order()` + `WC_Order_Item_Product::set_props()` + `$order->save()`. Equivalent fixtures for this suite are ~30 lines:

```php
// Product fixture.
$product = new WC_Product_Simple();
$product->set_props( array( 'name' => 'Test product', 'regular_price' => 10 ) );
$product->save();

// Order fixture.
$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 1 ) );
$order->add_product( $product, 2 );
$order->set_billing_email( 'test@example.com' );
$order->calculate_totals();
$order->save();

// Category fixture: plain WP.
wp_insert_term( 'Test cat', 'product_cat' );
```

**Recommendation**: hand-roll a small `tests/Fixtures/WcFactory` (or plain helper functions) on WC public CRUD. Do not vendor WC's framework.

## 3. HPOS vs legacy post storage

Background: [HPOS docs](https://developer.woocommerce.com/docs/features/high-performance-order-storage). HPOS (custom order tables) has been the default for new installs since WC 8.2 (Oct 2023); storage is controlled by the option `woocommerce_custom_orders_table_enabled` and read through `Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()`.

**This library is storage-agnostic by construction**: every order read/write goes through `wc_get_order()` / `wc_get_orders()` / `WC_Order` / `WC_Order_Item`, never through `WP_Query`/`get_post()`/postmeta for orders. WC's CRUD layer routes to whichever data store is active, so the same test assertions hold under either backend.

**Recommendation: run the suite against HPOS as the single primary storage.**

- HPOS is what new stores run and what WC's own CI treats as the default (its bootstrap enables HPOS unless the `DISABLE_HPOS` env var is set — see [tests/legacy/bootstrap.php](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/legacy/bootstrap.php)).
- A permanent legacy job is not worth the CI cost for a wrapper library: it would re-test WooCommerce's data-store abstraction, not wpify/model code. WC itself carries that burden.
- Cheap insurance instead: because the toggle is one option, keep the bootstrap toggle env-driven (mirror WC's `DISABLE_HPOS`) so a legacy run stays a one-flag manual/nightly option rather than a permanent matrix axis.

How to toggle in tests (verified against [HPOSToggleTrait](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/php/helpers/HPOSToggleTrait.php) and [OrderHelper](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/legacy/unit-tests/rest-api/Helpers/OrderHelper.php)):

```php
// Ensure HPOS tables exist (bootstrap; WC_Install::install() creates them on
// modern WC, this is the belt-and-braces check WC's helpers use):
$synchronizer = wc_get_container()->get( Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::class );
if ( ! $synchronizer->check_orders_table_exists() ) {
	$synchronizer->create_database_tables();
}

// Toggle usage:
wc_get_container()
	->get( Automattic\WooCommerce\Internal\Features\FeaturesController::class )
	->change_feature_enable( 'custom_order_tables', $enabled );
update_option( 'woocommerce_custom_orders_table_enabled', wc_bool_to_string( $enabled ) );
wp_cache_flush();
```

Gotcha: if toggling *inside a test case* (rather than the bootstrap), first `remove_filter( 'query', array( $this, '_create_temporary_tables' ) )` / `_drop_temporary_tables`, or the HPOS tables get created as TEMPORARY and vanish — this is exactly what WC's `HPOSToggleTrait::setup_cot()` does. Doing the setup once in the bootstrap sidesteps this entirely.

## 4. WC version pinning for CI

**Recommendation: pin an exact WC version in `.wp-env.json` and bump it deliberately.**

- Pin: `https://downloads.wordpress.org/plugin/woocommerce.10.9.4.zip` (current stable as of 2026-07; [release notes](https://developer.woocommerce.com/2026/07/07/woocommerce-10-9-4/)). An unpinned `woocommerce.latest-stable.zip` makes CI red on someone else's release day — WooCommerce ships monthly, so drift accumulates fast enough that a scheduled bump (Renovate/monthly chore issue) is better than surprise breakage. Optionally add a non-blocking scheduled job on `latest-stable` as an early-warning canary.
- Compatibility constraints (from [readme.txt @ 10.9.4](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/readme.txt) and wp.org plugin API):
  - WC 10.9.4 requires **WP ≥ 6.9**, tested up to WP 7.0; requires **PHP ≥ 7.4** (this repo already requires PHP ≥ 8.0, so no constraint in practice).
  - WooCommerce's WP core support policy is **L-1** (latest WP and the previous release) since WC 7.8 ([policy post](https://developer.woocommerce.com/2023/02/23/revisiting-wordpress-core-support-policy-for-woocommerce/)) — so in the CI matrix (#17), only pair the pinned WC with WP `latest` and `latest-1`, and let `core: null` (wp-env's "latest production WP") be the default cell. Don't run pinned-current WC against older WP; that combination is unsupported upstream.

## Recommended approach (summary)

1. Add `https://downloads.wordpress.org/plugin/woocommerce.<pinned>.zip` to the `plugins` list of the wp-env config that backs the phpunit environment (exact file layout per #11).
2. In `tests/bootstrap.php`: load `woocommerce/woocommerce.php` on `muplugins_loaded` via `tests_add_filter()`, run `WC_Install::install()` (+ roles reload) on `setup_theme`, and ensure HPOS is on (tables via `DataSynchronizer`, usage via `woocommerce_custom_orders_table_enabled`), with a `DISABLE_HPOS`-style env escape hatch.
3. Hand-roll fixtures with WC public CRUD (`new WC_Product_Simple()->save()`, `wc_create_order()` + `add_product()` + `save()`, `wp_insert_term(..., 'product_cat')`); do not attempt to consume `WC_Helper_*` / `WC_Unit_Test_Case` — they are not shipped in the zip nor on Packagist.
4. Run order tests against HPOS only; treat a legacy-storage run as an optional flag, not a matrix axis.
5. Pin the WC version; bump monthly; test only against WP latest and latest-1 per WooCommerce's L-1 policy.

## Sources

- wp-env docs (plugins sources, WP_TESTS_DIR, run/phpunit): https://github.com/WordPress/gutenberg/blob/trunk/packages/env/README.md
- wp-env changelog (tests-environment deprecation, `--config`): https://github.com/WordPress/gutenberg/blob/trunk/packages/env/CHANGELOG.md
- WooCommerce phpunit bootstrap: https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/legacy/bootstrap.php
- WC test helpers: https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/legacy/framework/helpers and https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/php/helpers
- HPOS toggling in tests: https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/php/helpers/HPOSToggleTrait.php and https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/legacy/unit-tests/rest-api/Helpers/OrderHelper.php
- HPOS feature docs: https://developer.woocommerce.com/docs/features/high-performance-order-storage
- WP core support policy (L-1): https://developer.woocommerce.com/2023/02/23/revisiting-wordpress-core-support-policy-for-woocommerce/
- WC 10.9.4 release notes: https://developer.woocommerce.com/2026/07/07/woocommerce-10-9-4/
- WC requirements: https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/readme.txt (Requires at least 6.9, Requires PHP 7.4, Stable 10.9.4; confirmed via api.wordpress.org plugin info)
- Distribution check: `woocommerce.10.9.4.zip` from downloads.wordpress.org contains no PHP test framework (verified by zip listing, 2026-07-25)
