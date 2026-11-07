<?php
/**
 * Request helpers.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Request utilities.
 */
final class Request {

	/**
	 * Salted hash of the client IP. Raw IPs are never stored.
	 *
	 * Uses REMOTE_ADDR only. If the site sits behind a trusted proxy/CDN, a
	 * filter can supply the real client IP.
	 *
	 * @return string|null 64-char hex hash, or null outside a web request.
	 */
	public static function ip_hash(): ?string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip = (string) apply_filters( 'cargawy/client_ip', $ip );
		if ( '' === $ip || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return null;
		}
		return hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
	}
}
