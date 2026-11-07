<?php
/**
 * PSR-4 autoloader for the CarGawy\Core namespace.
 *
 * Production servers do not need Composer: this maps
 * CarGawy\Core\Foo\Bar to includes/Foo/Bar.php.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal PSR-4 autoloader.
 */
final class Autoloader {

	private const PREFIX = 'CarGawy\\Core\\';

	/**
	 * Register the autoloader.
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Load a class file.
	 *
	 * @param string $class_name Fully qualified class name.
	 */
	public static function load( string $class_name ): void {
		if ( 0 !== strpos( $class_name, self::PREFIX ) ) {
			return;
		}
		$relative = substr( $class_name, strlen( self::PREFIX ) );
		$file     = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
