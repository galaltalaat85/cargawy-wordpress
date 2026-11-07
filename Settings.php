<?php
/**
 * CarGawy settings: definitions, defaults, validation and storage.
 *
 * All settings live in one option, "cargawy_settings". Contact details are
 * empty by default: nothing is shown on the site until the owner fills them.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings store.
 */
final class Settings {

	public const OPTION = 'cargawy_settings';

	/**
	 * Section slug => label.
	 *
	 * @return array<string,string>
	 */
	public static function sections(): array {
		return array(
			'contact'   => __( 'Contact details', 'cargawy-core' ),
			'listings'  => __( 'Listings & verification', 'cargawy-core' ),
			'analytics' => __( 'Analytics & Search Console', 'cargawy-core' ),
			'privacy'   => __( 'Data & privacy', 'cargawy-core' ),
		);
	}

	/**
	 * Field definitions.
	 *
	 * Keys: section, type, default, label, help, and min/max for ints.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields(): array {
		return array(
			'contact_phone'                  => array(
				'section' => 'contact',
				'type'    => 'phone',
				'default' => '',
				'label'   => __( 'Phone', 'cargawy-core' ),
				'help'    => __( 'Egyptian number, e.g. 01012345678 or +201012345678.', 'cargawy-core' ),
			),
			'contact_whatsapp'               => array(
				'section' => 'contact',
				'type'    => 'phone',
				'default' => '',
				'label'   => __( 'WhatsApp', 'cargawy-core' ),
				'help'    => __( 'Used for CarGawy inventory listings.', 'cargawy-core' ),
			),
			'contact_email'                  => array(
				'section' => 'contact',
				'type'    => 'email',
				'default' => '',
				'label'   => __( 'Email', 'cargawy-core' ),
				'help'    => '',
			),
			'contact_address'                => array(
				'section' => 'contact',
				'type'    => 'textarea',
				'default' => '',
				'label'   => __( 'Address', 'cargawy-core' ),
				'help'    => '',
			),
			'working_hours'                  => array(
				'section' => 'contact',
				'type'    => 'text',
				'default' => '',
				'label'   => __( 'Working hours', 'cargawy-core' ),
				'help'    => '',
			),
			'social_facebook'                => array(
				'section' => 'contact',
				'type'    => 'url',
				'default' => '',
				'label'   => __( 'Facebook page URL', 'cargawy-core' ),
				'help'    => '',
			),
			'social_instagram'               => array(
				'section' => 'contact',
				'type'    => 'url',
				'default' => '',
				'label'   => __( 'Instagram URL', 'cargawy-core' ),
				'help'    => '',
			),
			'social_tiktok'                  => array(
				'section' => 'contact',
				'type'    => 'url',
				'default' => '',
				'label'   => __( 'TikTok URL', 'cargawy-core' ),
				'help'    => '',
			),
			'max_active_listings_individual' => array(
				'section' => 'listings',
				'type'    => 'int',
				'default' => 3,
				'min'     => 1,
				'max'     => 100,
				'label'   => __( 'Max active listings per individual seller', 'cargawy-core' ),
				'help'    => __( 'Default 3 until the owner decides.', 'cargawy-core' ),
			),
			'listing_expiry_days'            => array(
				'section' => 'listings',
				'type'    => 'int',
				'default' => 60,
				'min'     => 7,
				'max'     => 365,
				'label'   => __( 'Expire listings after (days without update)', 'cargawy-core' ),
				'help'    => '',
			),
			'sold_noindex_days'              => array(
				'section' => 'listings',
				'type'    => 'int',
				'default' => 30,
				'min'     => 0,
				'max'     => 365,
				'label'   => __( 'Keep sold listings indexable for (days)', 'cargawy-core' ),
				'help'    => '',
			),
			'inspection_validity_days'       => array(
				'section' => 'listings',
				'type'    => 'int',
				'default' => 90,
				'min'     => 7,
				'max'     => 730,
				'label'   => __( 'Inspection badge validity (days)', 'cargawy-core' ),
				'help'    => __( 'The "Checked by CarGawy" badge is removed after this period.', 'cargawy-core' ),
			),
			'min_listing_photos'             => array(
				'section' => 'listings',
				'type'    => 'int',
				'default' => 4,
				'min'     => 1,
				'max'     => 20,
				'label'   => __( 'Minimum photos per listing', 'cargawy-core' ),
				'help'    => '',
			),
			'ga4_id'                         => array(
				'section' => 'analytics',
				'type'    => 'ga4',
				'default' => '',
				'label'   => __( 'Google Analytics 4 measurement ID', 'cargawy-core' ),
				'help'    => __( 'Format G-XXXXXXXXXX. Loaded only after visitor consent.', 'cargawy-core' ),
			),
			'meta_pixel_id'                  => array(
				'section' => 'analytics',
				'type'    => 'pixel',
				'default' => '',
				'label'   => __( 'Meta Pixel ID', 'cargawy-core' ),
				'help'    => __( 'Digits only.', 'cargawy-core' ),
			),
			'gads_id'                        => array(
				'section' => 'analytics',
				'type'    => 'gads',
				'default' => '',
				'label'   => __( 'Google Ads conversion ID', 'cargawy-core' ),
				'help'    => __( 'Format AW-XXXXXXXXX.', 'cargawy-core' ),
			),
			'gsc_verification'               => array(
				'section' => 'analytics',
				'type'    => 'token',
				'default' => '',
				'label'   => __( 'Google Search Console verification code', 'cargawy-core' ),
				'help'    => __( 'Only the content value of the meta tag.', 'cargawy-core' ),
			),
			'lead_retention_months'          => array(
				'section' => 'privacy',
				'type'    => 'int',
				'default' => 24,
				'min'     => 1,
				'max'     => 120,
				'label'   => __( 'Anonymize leads after (months)', 'cargawy-core' ),
				'help'    => __( 'Confirm with legal counsel.', 'cargawy-core' ),
			),
			'delete_data_on_uninstall'       => array(
				'section' => 'privacy',
				'type'    => 'bool',
				'default' => false,
				'label'   => __( 'Delete all CarGawy data when the plugin is deleted', 'cargawy-core' ),
				'help'    => __( 'Leave off. When on, deleting the plugin permanently removes all listings data, leads, inspections and the audit log.', 'cargawy-core' ),
			),
		);
	}

	/**
	 * Register hooks.
	 */
	public static function register_hooks(): void {
		add_action( 'update_option_' . self::OPTION, array( self::class, 'audit_change' ), 10, 2 );
		add_action(
			'add_option_' . self::OPTION,
			static function ( $option, $value ) {
				self::audit_change( array(), $value );
			},
			10,
			2
		);
	}

	/**
	 * Defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array_map(
			static function ( $f ) {
				return $f['default'];
			},
			self::fields()
		);
	}

	/**
	 * All settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $stored ) ? array_intersect_key( $stored, self::fields() ) : array() );
	}

	/**
	 * One setting.
	 *
	 * @param string $key Key.
	 * @return mixed Null for unknown keys.
	 */
	public static function get( string $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	/**
	 * Validate and normalise raw input. Invalid values fall back to the current value
	 * and produce an error message.
	 *
	 * @param mixed $input Raw input.
	 * @return array{values: array<string, mixed>, errors: array<string, string>}
	 */
	public static function validate( $input ): array {
		$input   = is_array( $input ) ? $input : array();
		$current = self::all();
		$values  = array();
		$errors  = array();

		foreach ( self::fields() as $key => $f ) {
			if ( 'bool' !== $f['type'] && ! array_key_exists( $key, $input ) ) {
				// Not submitted: keep as is.
				$values[ $key ] = $current[ $key ];
				continue;
			}
			$raw = $input[ $key ] ?? null;

			if ( 'bool' === $f['type'] ) {
				$values[ $key ] = ! empty( $raw );
				continue;
			}
			$raw = is_scalar( $raw ) ? trim( (string) $raw ) : '';

			if ( '' === $raw && 'int' !== $f['type'] ) {
				$values[ $key ] = '';
				continue;
			}

			$clean = self::clean( $f, $raw );
			if ( null === $clean ) {
				// translators: %s: field label.
				$errors[ $key ] = sprintf( __( '%s: invalid value, the previous value was kept.', 'cargawy-core' ), $f['label'] );
				$values[ $key ] = $current[ $key ];
			} else {
				$values[ $key ] = $clean;
			}
		}

		return array(
			'values' => $values,
			'errors' => $errors,
		);
	}

	/**
	 * Sanitize callback for register_setting().
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ): array {
		$result = self::validate( $input );
		foreach ( $result['errors'] as $key => $message ) {
			add_settings_error( self::OPTION, 'cg_' . $key, $message );
		}
		return $result['values'];
	}

	/**
	 * Normalise one value; null when invalid.
	 *
	 * @param array  $f   Field definition.
	 * @param string $raw Raw value.
	 * @return mixed
	 */
	private static function clean( array $f, string $raw ) {
		switch ( $f['type'] ) {
			case 'int':
				if ( '' === $raw || ! preg_match( '/^\d+$/', $raw ) ) {
					return null;
				}
				$n = (int) $raw;
				return ( $n < $f['min'] || $n > $f['max'] ) ? null : $n;
			case 'phone':
				return self::normalize_egypt_phone( $raw );
			case 'email':
				$e = sanitize_email( $raw );
				return is_email( $e ) ? $e : null;
			case 'url':
				$u = esc_url_raw( $raw, array( 'https' ) );
				return '' === $u ? null : $u;
			case 'ga4':
				return preg_match( '/^G-[A-Z0-9]{4,15}$/', strtoupper( $raw ) ) ? strtoupper( $raw ) : null;
			case 'gads':
				return preg_match( '/^AW-\d{6,15}$/', strtoupper( $raw ) ) ? strtoupper( $raw ) : null;
			case 'pixel':
				return preg_match( '/^\d{10,20}$/', $raw ) ? $raw : null;
			case 'token':
				return preg_match( '/^[A-Za-z0-9_\-]{10,100}$/', $raw ) ? $raw : null;
			case 'textarea':
				return sanitize_textarea_field( $raw );
			default:
				return sanitize_text_field( $raw );
		}
	}

	/**
	 * Normalise an Egyptian mobile or landline to E.164 (+20…).
	 *
	 * Accepts 01XXXXXXXXX, +201XXXXXXXXX, 00201XXXXXXXXX and Cairo/Giza/Alex
	 * landlines such as 0223456789. Returns null when not recognisable.
	 *
	 * @param string $raw Raw number.
	 */
	public static function normalize_egypt_phone( string $raw ): ?string {
		$digits = preg_replace( '/[^\d+]/', '', $raw );
		$digits = preg_replace( '/^(\+|00)20/', '0', (string) $digits );
		if ( preg_match( '/^01[0125]\d{8}$/', $digits ) ) {
			return '+20' . substr( $digits, 1 );
		}
		if ( preg_match( '/^0[2-9]\d{7,8}$/', $digits ) ) {
			return '+20' . substr( $digits, 1 );
		}
		return null;
	}

	/**
	 * Audit which keys changed (values of contact fields are personal data of the business only).
	 *
	 * @param mixed $old_value Old.
	 * @param mixed $new_value New.
	 */
	public static function audit_change( $old_value, $new_value ): void {
		$old     = is_array( $old_value ) ? $old_value : array();
		$new     = is_array( $new_value ) ? $new_value : array();
		$changed = array();
		foreach ( array_keys( self::fields() ) as $key ) {
			if ( ( $old[ $key ] ?? null ) !== ( $new[ $key ] ?? null ) ) {
				$changed[] = $key;
			}
		}
		if ( $changed ) {
			cargawy_audit( 'settings', 0, 'updated', null, null, implode( ', ', $changed ) );
		}
	}
}
