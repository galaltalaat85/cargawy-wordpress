<?php
/**
 * System health: plugin status and the Hostinger compatibility checklist,
 * checked live on the server.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Admin;

use CarGawy\Core\Activation\Installer;
use CarGawy\Core\Database\Schema;
use CarGawy\Core\Permissions\Roles;

defined( 'ABSPATH' ) || exit;

/**
 * Renders CarGawy → System health.
 */
final class HealthPage {

	/**
	 * Run all checks.
	 *
	 * @return array<int, array{label:string, value:string, status:string, fix:string}>
	 *         status is one of: ok, warn, fail.
	 */
	public static function checks(): array {
		global $wpdb;
		$checks = array();

		$missing  = Schema::missing_tables();
		$checks[] = array(
			'label'  => __( 'CarGawy database tables', 'cargawy-core' ),
			'value'  => $missing ? implode( ', ', $missing ) . ' ' . __( 'missing', 'cargawy-core' ) : sprintf( '%d / %d', count( Schema::TABLES ), count( Schema::TABLES ) ),
			'status' => $missing ? 'fail' : 'ok',
			'fix'    => __( 'Deactivate and reactivate the plugin.', 'cargawy-core' ),
		);
		$dbv      = (int) get_option( Installer::DB_VERSION_OPTION, 0 );
		$checks[] = array(
			'label'  => __( 'Schema version', 'cargawy-core' ),
			'value'  => $dbv . ' / ' . Installer::DB_VERSION,
			'status' => Installer::DB_VERSION === $dbv ? 'ok' : 'fail',
			'fix'    => '',
		);
		$rv       = (int) get_option( Roles::VERSION_OPTION, 0 );
		$checks[] = array(
			'label'  => __( 'Roles version', 'cargawy-core' ),
			'value'  => $rv . ' / ' . Roles::VERSION,
			'status' => Roles::VERSION === $rv ? 'ok' : 'fail',
			'fix'    => '',
		);

		$checks[] = self::min_check( __( 'PHP version', 'cargawy-core' ), PHP_VERSION, '8.1', '8.2', __( 'hPanel → Advanced → PHP Configuration.', 'cargawy-core' ) );

		$server_info = (string) $wpdb->db_server_info();
		$is_maria    = false !== stripos( $server_info, 'mariadb' );
		$db_version  = preg_replace( '/[^0-9.].*$/', '', (string) $wpdb->db_version() );
		$checks[]    = self::min_check( $is_maria ? 'MariaDB' : 'MySQL', (string) $db_version, $is_maria ? '10.6' : '8.0', $is_maria ? '10.6' : '8.0', '' );

		foreach ( array( 'mysqli', 'intl', 'mbstring', 'zip', 'curl', 'fileinfo', 'openssl' ) as $ext ) {
			$ok       = extension_loaded( $ext );
			$checks[] = array(
				// translators: %s: PHP extension name.
				'label'  => sprintf( __( 'PHP extension: %s', 'cargawy-core' ), $ext ),
				'value'  => $ok ? __( 'loaded', 'cargawy-core' ) : __( 'missing', 'cargawy-core' ),
				'status' => $ok ? 'ok' : 'fail',
				'fix'    => __( 'hPanel → PHP Configuration → PHP extensions.', 'cargawy-core' ),
			);
		}
		$webp     = ( extension_loaded( 'gd' ) && function_exists( 'imagewebp' ) ) || ( class_exists( '\Imagick' ) && in_array( 'WEBP', \Imagick::queryFormats( 'WEBP' ), true ) );
		$checks[] = array(
			'label'  => __( 'WebP image support (gd or imagick)', 'cargawy-core' ),
			'value'  => $webp ? __( 'available', 'cargawy-core' ) : __( 'not available', 'cargawy-core' ),
			'status' => $webp ? 'ok' : 'warn',
			'fix'    => __( 'Enable imagick or gd with WebP.', 'cargawy-core' ),
		);

		$upload    = wp_max_upload_size();
		$checks[]  = array(
			'label'  => __( 'Max upload size', 'cargawy-core' ),
			'value'  => size_format( $upload ),
			'status' => $upload >= 16 * MB_IN_BYTES ? 'ok' : 'warn',
			'fix'    => __( 'Set upload_max_filesize ≥ 16M and post_max_size ≥ 64M.', 'cargawy-core' ),
		);
		$memory    = wp_convert_hr_to_bytes( (string) ini_get( 'memory_limit' ) );
		$checks[]  = array(
			'label'  => __( 'PHP memory_limit', 'cargawy-core' ),
			'value'  => (string) ini_get( 'memory_limit' ),
			'status' => ( $memory < 0 || $memory >= 256 * MB_IN_BYTES ) ? 'ok' : 'warn',
			'fix'    => __( 'Set memory_limit to 256M or more.', 'cargawy-core' ),
		);
		$checks[]  = array(
			'label'  => __( 'HTTPS', 'cargawy-core' ),
			'value'  => wp_is_using_https() ? __( 'site URL uses https', 'cargawy-core' ) : __( 'site URL uses http', 'cargawy-core' ),
			'status' => wp_is_using_https() ? 'ok' : 'warn',
			'fix'    => __( 'Enable SSL in hPanel and update Site Address.', 'cargawy-core' ),
		);
		$real_cron = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
		$checks[]  = array(
			'label'  => __( 'Server cron', 'cargawy-core' ),
			'value'  => $real_cron ? __( 'DISABLE_WP_CRON is on (server cron expected)', 'cargawy-core' ) : __( 'WordPress pseudo-cron', 'cargawy-core' ),
			'status' => $real_cron ? 'ok' : 'warn',
			'fix'    => __( 'Add an hPanel cron job calling wp-cron.php every 5 minutes, then set DISABLE_WP_CRON.', 'cargawy-core' ),
		);
		$private   = self::private_dir_check();
		$checks[]  = $private;

		return $checks;
	}

	/**
	 * Version check with a minimum and a recommended value.
	 *
	 * @param string $label       Label.
	 * @param string $value       Current version.
	 * @param string $min         Minimum.
	 * @param string $recommended Recommended.
	 * @param string $fix         How to fix.
	 */
	private static function min_check( string $label, string $value, string $min, string $recommended, string $fix ): array {
		$status = version_compare( $value, $min, '<' ) ? 'fail' : ( version_compare( $value, $recommended, '<' ) ? 'warn' : 'ok' );
		return array(
			'label'  => $label,
			'value'  => $value,
			'status' => $status,
			'fix'    => $fix,
		);
	}

	/**
	 * Is there a writable directory outside the web root for private documents?
	 */
	private static function private_dir_check(): array {
		$candidate = (string) apply_filters( 'cargawy/private_dir', dirname( untrailingslashit( ABSPATH ) ) . '/cargawy-private' );
		$inside    = 0 === strpos( wp_normalize_path( $candidate ), wp_normalize_path( ABSPATH ) );
		$writable  = is_dir( $candidate ) ? wp_is_writable( $candidate ) : wp_is_writable( dirname( $candidate ) );
		$ok        = $writable && ! $inside;
		return array(
			'label'  => __( 'Private document storage outside public_html', 'cargawy-core' ),
			'value'  => $candidate . ' — ' . ( $ok ? __( 'writable', 'cargawy-core' ) : ( $inside ? __( 'inside web root', 'cargawy-core' ) : __( 'not writable', 'cargawy-core' ) ) ),
			'status' => $ok ? 'ok' : 'warn',
			'fix'    => __( 'Needed from Phase 8. Create the folder next to public_html via hPanel File Manager.', 'cargawy-core' ),
		);
	}

	/**
	 * Render.
	 */
	public function render(): void {
		if ( ! current_user_can( 'cg_access_admin' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'cargawy-core' ), 403 );
		}
		$labels = array(
			'ok'   => __( 'OK', 'cargawy-core' ),
			'warn' => __( 'Check', 'cargawy-core' ),
			'fail' => __( 'Problem', 'cargawy-core' ),
		);
		$colors = array(
			'ok'   => '#17794F',
			'warn' => '#8A5200',
			'fail' => '#B42318',
		);

		echo '<div class="wrap"><h1>' . esc_html__( 'CarGawy — System health', 'cargawy-core' ) . '</h1>';
		// translators: %s: plugin version.
		echo '<p>' . esc_html( sprintf( __( 'CarGawy Core %s. Server checks below follow the Hostinger Business compatibility checklist.', 'cargawy-core' ), CARGAWY_CORE_VERSION ) ) . '</p>';
		echo '<table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Check', 'cargawy-core' ) . '</th><th scope="col">' . esc_html__( 'Value', 'cargawy-core' ) . '</th><th scope="col">' . esc_html__( 'Status', 'cargawy-core' ) . '</th><th scope="col">' . esc_html__( 'How to fix', 'cargawy-core' ) . '</th></tr></thead><tbody>';
		foreach ( self::checks() as $c ) {
			printf(
				'<tr><td>%1$s</td><td><code>%2$s</code></td><td><strong style="color:%3$s">%4$s</strong></td><td>%5$s</td></tr>',
				esc_html( $c['label'] ),
				esc_html( $c['value'] ),
				esc_attr( $colors[ $c['status'] ] ),
				esc_html( $labels[ $c['status'] ] ),
				'ok' === $c['status'] ? '' : esc_html( $c['fix'] )
			);
		}
		echo '</tbody></table></div>';
	}
}
