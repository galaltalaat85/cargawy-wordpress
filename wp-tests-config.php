<?php
/**
 * WordPress test-suite configuration. Values come from environment variables
 * so CI and local machines can differ; defaults match bin/setup-local.sh.
 *
 * @package CarGawy\Core\Tests
 */

define( 'ABSPATH', dirname( __DIR__, 4 ) . '/vendor/roots/wordpress-no-content/' );
define( 'WP_DEFAULT_THEME', 'default' );
define( 'WP_DEBUG', true );

define( 'DB_NAME', ( false !== getenv( 'CG_TEST_DB_NAME' ) ? getenv( 'CG_TEST_DB_NAME' ) : 'cargawy_test' ) );
define( 'DB_USER', ( false !== getenv( 'CG_TEST_DB_USER' ) ? getenv( 'CG_TEST_DB_USER' ) : 'cg' ) );
define( 'DB_PASSWORD', ( false !== getenv( 'CG_TEST_DB_PASSWORD' ) ? getenv( 'CG_TEST_DB_PASSWORD' ) : 'cg_local_pw' ) );
define( 'DB_HOST', ( false !== getenv( 'CG_TEST_DB_HOST' ) ? getenv( 'CG_TEST_DB_HOST' ) : 'localhost' ) );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'CarGawy Test' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
