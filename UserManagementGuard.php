<?php
/**
 * Limits what non-administrators can do to other user accounts.
 *
 * A CarGawy Manager may list, create and edit members and dealers, but must
 * never edit, delete or promote staff accounts or administrators.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Permissions;

defined( 'ABSPATH' ) || exit;

/**
 * User management restrictions.
 */
final class UserManagementGuard {

	/**
	 * Register hooks.
	 */
	public static function register_hooks(): void {
		add_filter( 'editable_roles', array( self::class, 'filter_editable_roles' ) );
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 4 );
	}

	/**
	 * Only administrators can assign any role; others only member/dealer roles.
	 *
	 * @param array $roles Roles keyed by slug.
	 * @return array
	 */
	public static function filter_editable_roles( $roles ) {
		if ( current_user_can( 'manage_options' ) ) {
			return $roles;
		}
		return array_intersect_key( $roles, array_flip( Capabilities::manager_assignable_roles() ) );
	}

	/**
	 * Deny edit/delete/promote on users outside the assignable roles for non-admins.
	 *
	 * @param string[] $caps    Primitive caps required.
	 * @param string   $cap     Meta cap being checked.
	 * @param int  $user_id Acting user.
	 * @param array    $args    Extra args; $args[0] is the target user ID.
	 * @return string[]
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( ! in_array( $cap, array( 'edit_user', 'delete_user', 'promote_user', 'remove_user' ), true ) ) {
			return $caps;
		}
		if ( empty( $args[0] ) || (int) $args[0] === (int) $user_id ) {
			// Editing yourself follows core rules.
			return $caps;
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
			// Administrators are unrestricted.
			return $caps;
		}
		$target = get_userdata( (int) $args[0] );
		if ( ! $target ) {
			return $caps;
		}
		$allowed = Capabilities::manager_assignable_roles();
		foreach ( (array) $target->roles as $role ) {
			if ( ! in_array( $role, $allowed, true ) ) {
				return array( 'do_not_allow' );
			}
		}
		return $caps;
	}
}
