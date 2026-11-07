<?php
/**
 * Uninstall logic (separate from uninstall.php so it can be tested).
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Activation;

use CarGawy\Core\Database\Schema;
use CarGawy\Core\Permissions\Roles;
use CarGawy\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Removes CarGawy data only when explicitly enabled.
 */
final class Uninstaller {

	/**
	 * Run.
	 *
	 * @return bool True if data was removed.
	 */
	public static function run(): bool {
		$settings = get_option( Settings::OPTION, array() );
		if ( empty( $settings['delete_data_on_uninstall'] ) ) {
			return false;
		}

		Schema::drop_all();
		Roles::remove_all();
		foreach ( array( Settings::OPTION, Installer::DB_VERSION_OPTION, 'cargawy_activated_at' ) as $option ) {
			delete_option( $option );
		}
		wp_clear_scheduled_hook( 'cargawy_daily_maintenance' );
		return true;
	}
}
