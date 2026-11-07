<?php
/**
 * Plugin Name:       CarGawy Core
 * Plugin URI:        https://cargawy.com
 * Description:       Business logic for the CarGawy used-car marketplace: data model, roles and capabilities, workflows, audit log and settings.
 * Version:           0.2.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            CarGawy
 * Text Domain:       cargawy-core
 * Domain Path:       /languages
 * License:           Proprietary
 *
 * @package CarGawy\Core
 */

defined( 'ABSPATH' ) || exit;

define( 'CARGAWY_CORE_VERSION', '0.2.0' );
define( 'CARGAWY_CORE_FILE', __FILE__ );
define( 'CARGAWY_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CARGAWY_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once CARGAWY_CORE_DIR . 'includes/Autoloader.php';
\CarGawy\Core\Autoloader::register();

register_activation_hook( __FILE__, array( \CarGawy\Core\Activation\Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \CarGawy\Core\Activation\Installer::class, 'deactivate' ) );

\CarGawy\Core\Plugin::instance()->boot();

/**
 * Record an entry in the CarGawy audit log.
 *
 * @param string       $entity_type Entity type, e.g. "listing", "inspection", "settings".
 * @param int      $entity_id   Entity ID (0 when not applicable).
 * @param string       $action      Short action key, e.g. "transition", "updated".
 * @param string|null  $from_value  Previous value.
 * @param string|null  $to_value    New value.
 * @param string       $note        Optional note.
 * @param int|null $actor_id    Acting user ID; defaults to the current user.
 * @return int|false Inserted row ID or false on failure.
 */
function cargawy_audit( $entity_type, $entity_id, $action, $from_value = null, $to_value = null, $note = '', $actor_id = null ) {
	return \CarGawy\Core\Audit\AuditLog::record( $entity_type, $entity_id, $action, $from_value, $to_value, $note, $actor_id );
}

/**
 * Read a CarGawy setting.
 *
 * @param string $key Setting key.
 * @return mixed
 */
function cargawy_setting( $key ) {
	return \CarGawy\Core\Settings\Settings::get( $key );
}
