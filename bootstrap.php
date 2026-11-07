<?php
/**
 * PHPUnit bootstrap: boots WordPress with cargawy-core loaded and activated.
 *
 * @package CarGawy\Core\Tests
 */

$root = dirname( __DIR__, 4 );
require_once $root . '/vendor/autoload.php';

putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $root . '/vendor/yoast/phpunit-polyfills' );

$wp_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
require_once $wp_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__ ) . '/cargawy-core.php';
	}
);

require $wp_tests_dir . '/includes/bootstrap.php';

// Create tables and roles once, outside test transactions.
\CarGawy\Core\Activation\Installer::activate();

require_once __DIR__ . '/TestCase.php';
