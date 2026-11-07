<?php
/**
 * Runs when the plugin is deleted from wp-admin.
 *
 * Data is removed ONLY if "Delete all CarGawy data when the plugin is deleted"
 * is enabled in CarGawy → Settings. Default is off: deleting the plugin
 * files then leaves every table, option and role in place.
 *
 * @package CarGawy\Core
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/Autoloader.php';
\CarGawy\Core\Autoloader::register();

\CarGawy\Core\Activation\Uninstaller::run();
