<?php
/**
 * Settings screen.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Admin;

use CarGawy\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Renders CarGawy → Settings.
 */
final class SettingsPage {

	private const PAGE = 'cargawy-settings';

	/**
	 * Register sections and fields with the Settings API.
	 */
	public function register_fields(): void {
		foreach ( Settings::sections() as $id => $label ) {
			add_settings_section( 'cg_' . $id, $label, '__return_false', self::PAGE );
		}
		foreach ( Settings::fields() as $key => $f ) {
			add_settings_field(
				'cg_' . $key,
				esc_html( $f['label'] ),
				array( $this, 'render_field' ),
				self::PAGE,
				'cg_' . $f['section'],
				array(
					'key'       => $key,
					'field'     => $f,
					'label_for' => 'cg-' . $key,
				)
			);
		}
	}

	/**
	 * Render one field.
	 *
	 * @param array $args Field args.
	 */
	public function render_field( array $args ): void {
		$key   = $args['key'];
		$f     = $args['field'];
		$value = Settings::get( $key );
		$name  = Settings::OPTION . '[' . $key . ']';
		$id    = 'cg-' . $key;

		switch ( $f['type'] ) {
			case 'bool':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( (bool) $value, true, false ),
					esc_html__( 'Enabled', 'cargawy-core' )
				);
				break;
			case 'textarea':
				printf( '<textarea class="large-text" rows="3" id="%1$s" name="%2$s">%3$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( (string) $value ) );
				break;
			case 'int':
				printf(
					'<input type="number" class="small-text" id="%1$s" name="%2$s" value="%3$s" min="%4$d" max="%5$d" step="1">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					(int) $f['min'],
					(int) $f['max']
				);
				break;
			default:
				$type = array(
					'email' => 'email',
					'url'   => 'url',
					'phone' => 'tel',
				)[ $f['type'] ] ?? 'text';
				$dir  = in_array( $f['type'], array( 'phone', 'email', 'url', 'ga4', 'gads', 'pixel', 'token' ), true ) ? ' dir="ltr"' : '';
				printf(
					'<input type="%1$s" class="regular-text" id="%2$s" name="%3$s" value="%4$s"%5$s>',
					esc_attr( $type ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					$dir // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal.
				);
		}
		if ( ! empty( $f['help'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $f['help'] ) );
		}
	}

	/**
	 * Render the page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'cg_manage_settings' ) ) {
			wp_die( esc_html__( 'You are not allowed to change CarGawy settings.', 'cargawy-core' ), 403 );
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'CarGawy settings', 'cargawy-core' ) . '</h1>';
		settings_errors( Settings::OPTION );
		echo '<form method="post" action="options.php">';
		settings_fields( 'cargawy_settings_group' );
		do_settings_sections( self::PAGE );
		submit_button();
		echo '</form></div>';
	}
}
