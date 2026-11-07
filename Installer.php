<?php
/**
 * Activation, deactivation and versioned upgrades.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Activation;

use CarGawy\Core\Database\Schema;
use CarGawy\Core\Permissions\Roles;

defined( 'ABSPATH' ) || exit;

/**
 * Installs and upgrades the plugin's database schema and roles.
 *
 * Deactivation never removes data. Data is only removed by uninstall.php
 * when the "delete data on uninstall" setting is explicitly enabled.
 */
final class Installer {

	/**
	 * Bump when Schema::tables() or the migrations list changes.
	 */
	public const DB_VERSION = 1;

	public const DB_VERSION_OPTION = 'cargawy_db_version';

	/**
	 * Activation hook.
	 */
	public static function activate(): void {
		self::install();
		update_option( 'cargawy_activated_at', gmdate( 'Y-m-d H:i:s' ), false );
		cargawy_audit( 'plugin', 0, 'activated', null, CARGAWY_CORE_VERSION );
	}

	/**
	 * Deactivation hook. Clears scheduled events only; keeps all data and roles.
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'cargawy_daily_maintenance' );
		cargawy_audit( 'plugin', 0, 'deactivated', CARGAWY_CORE_VERSION, null );
	}

	/**
	 * Run upgrades when the stored DB version is behind the code.
	 */
	public static function maybe_upgrade(): void {
		if ( (int) get_option( self::DB_VERSION_OPTION, 0 ) < self::DB_VERSION
			|| (int) get_option( Roles::VERSION_OPTION, 0 ) < Roles::VERSION ) {
			self::install();
		}
	}

	/**
	 * Create or update tables, sync roles. Idempotent.
	 */
	public static function install(): void {
		$from = (int) get_option( self::DB_VERSION_OPTION, 0 );

		Schema::create_or_update();
		self::run_migrations( $from );
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );

		Roles::sync();
	}

	/**
	 * Data migrations that dbDelta cannot express (renames, backfills).
	 * Each step runs once, in order, for installs older than its version.
	 *
	 * @param int $from Previously installed DB version.
	 */
	private static function run_migrations( int $from ): void {
		$migrations = array(
			// Version => callable. Version 1 is the initial schema; nothing to migrate.
		);
		foreach ( $migrations as $version => $callback ) {
			if ( $from < $version ) {
				call_user_func( $callback );
			}
		}
	}
}
