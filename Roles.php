<?php
/**
 * Creates and synchronises CarGawy roles.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Permissions;

defined( 'ABSPATH' ) || exit;

/**
 * Role manager.
 */
final class Roles {

	/**
	 * Bump whenever Capabilities::matrix() changes so existing sites re-sync.
	 */
	public const VERSION = 1;

	public const VERSION_OPTION = 'cargawy_roles_version';

	/**
	 * Re-create every CarGawy role from the matrix and give administrators all CarGawy caps.
	 *
	 * Re-creating (rather than adding) removes capabilities that were dropped
	 * from the matrix. Users keep their role assignment.
	 */
	public static function sync(): void {
		$editor      = get_role( 'editor' );
		$editor_caps = $editor ? array_keys( array_filter( $editor->capabilities ) ) : array();

		foreach ( Capabilities::matrix() as $slug => $def ) {
			list( $label, $caps ) = $def;
			if ( 'cg_editor' === $slug ) {
				$caps = array_merge( $editor_caps, $caps );
			}
			$caps   = array_unique( array_merge( array( 'read' ), $caps ) );
			$grants = array_fill_keys( $caps, true );

			remove_role( $slug );
			add_role( $slug, $label, $grants );
		}

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( array_keys( Capabilities::ALL ) as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		update_option( self::VERSION_OPTION, self::VERSION, false );
	}

	/**
	 * Remove CarGawy roles and capabilities (uninstall only).
	 */
	public static function remove_all(): void {
		foreach ( array_keys( Capabilities::matrix() ) as $slug ) {
			remove_role( $slug );
		}
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( array_keys( Capabilities::ALL ) as $cap ) {
				$admin->remove_cap( $cap );
			}
		}
		delete_option( self::VERSION_OPTION );
	}
}
