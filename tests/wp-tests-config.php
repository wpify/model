<?php
/**
 * Test-suite configuration for docker-less runs (wp-phpunit + local MySQL).
 * Under wp-env this file is unused — wp-env generates its own config inside
 * WP_TESTS_DIR. All values can be overridden with environment variables.
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/wordpress/' );

define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ?: 'wp_tests' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) ?: 'root' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ?: '127.0.0.1:3307' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WP_DEBUG', true );
