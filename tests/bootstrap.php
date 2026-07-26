<?php
/**
 * PHPUnit bootstrap for the wpify/model integration suite.
 *
 * Runs inside wp-env's tests-cli container (WP_TESTS_DIR is set by wp-env),
 * with a docker-less fallback via wp-phpunit/wp-phpunit (WP_PHPUNIT__DIR).
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: getenv( 'WP_PHPUNIT__DIR' );

if ( ! $_tests_dir || ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "Could not find the WordPress test library. Run inside wp-env:\n" );
	fwrite( STDERR, "  npx wp-env start\n" );
	fwrite( STDERR, "  npx wp-env run tests-cli --env-cwd=wp-content/wpify-model vendor/bin/phpunit\n" );
	exit( 1 );
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load WooCommerce. The test suite installs a fresh WordPress on every run and
 * ignores the active_plugins option, so the plugin must be required explicitly.
 */
tests_add_filter( 'muplugins_loaded', static function () {
	require_once WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
} );

/**
 * Install WooCommerce (tables, roles, taxonomies) before the first test, the
 * same way WooCommerce's own bootstrap does. Tables created here are permanent;
 * per-test isolation stays transactional.
 */
tests_add_filter( 'setup_theme', static function () {
	define( 'WP_UNINSTALL_PLUGIN', true );
	define( 'WC_REMOVE_ALL_DATA', true );
	include WP_PLUGIN_DIR . '/woocommerce/uninstall.php';

	WC_Install::install();

	// Reload capabilities after install.
	$GLOBALS['wp_roles'] = null;
	wp_roles();

	// Orders run against HPOS unless explicitly disabled (DISABLE_HPOS=1).
	if ( ! getenv( 'DISABLE_HPOS' ) ) {
		$synchronizer = wc_get_container()->get( Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::class );

		if ( ! $synchronizer->check_orders_table_exists() ) {
			$synchronizer->create_database_tables();
		}

		wc_get_container()
			->get( Automattic\WooCommerce\Internal\Features\FeaturesController::class )
			->change_feature_enable( 'custom_order_tables', true );
		update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );
		wp_cache_flush();
	}
} );

require $_tests_dir . '/includes/bootstrap.php';
