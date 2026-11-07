<?php
/**
 * Keeps members and dealers out of wp-admin.
 *
 * They manage everything from the front-end account area (/account/, built
 * in Phase 4). AJAX and REST requests are not affected.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Permissions;

defined( 'ABSPATH' ) || exit;

/**
 * wp-admin gate.
 */
final class AdminAccess {

	/**
	 * Register hooks.
	 */
	public static function register_hooks(): void {
		add_action( 'admin_init', array( self::class, 'maybe_redirect' ), 1 );
		add_filter( 'show_admin_bar', array( self::class, 'filter_admin_bar' ) );
	}

	/**
	 * Whether a user may open wp-admin.
	 *
	 * Users without any CarGawy role (e.g. a plain subscriber) keep core behaviour.
	 *
	 * @param int $user_id User ID.
	 */
	public static function can_access_admin( int $user_id ): bool {
		if ( user_can( $user_id, 'cg_access_admin' ) || user_can( $user_id, 'manage_options' ) ) {
			return true;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}
		return ! array_intersect( (array) $user->roles, Capabilities::front_end_roles() );
	}

	/**
	 * Front-end account URL (filterable until Phase 4 registers the endpoint).
	 */
	public static function account_url(): string {
		return (string) apply_filters( 'cargawy/account_url', home_url( '/account/' ) );
	}

	/**
	 * Redirect front-end users away from wp-admin.
	 */
	public static function maybe_redirect(): void {
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_user_logged_in() ) {
			return;
		}
		if ( self::can_access_admin( get_current_user_id() ) ) {
			return;
		}
		wp_safe_redirect( self::account_url() );
		exit;
	}

	/**
	 * Hide the admin bar for front-end users.
	 *
	 * @param bool $show Current value.
	 */
	public static function filter_admin_bar( $show ) {
		if ( is_user_logged_in() && ! self::can_access_admin( get_current_user_id() ) ) {
			return false;
		}
		return $show;
	}
}
