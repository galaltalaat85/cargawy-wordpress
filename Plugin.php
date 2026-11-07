<?php
/**
 * Plugin bootstrap: wires modules to WordPress hooks.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core;

use CarGawy\Core\Activation\Installer;
use CarGawy\Core\Admin\Menu;
use CarGawy\Core\Permissions\AdminAccess;
use CarGawy\Core\Permissions\UserManagementGuard;
use CarGawy\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class (single instance).
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Whether boot() already ran.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Get the instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks. Safe to call more than once.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( Installer::class, 'maybe_upgrade' ) );

		Settings::register_hooks();
		UserManagementGuard::register_hooks();
		AdminAccess::register_hooks();

		if ( is_admin() ) {
			( new Menu() )->register_hooks();
		}
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'cargawy-core', false, dirname( plugin_basename( CARGAWY_CORE_FILE ) ) . '/languages' );
	}
}
