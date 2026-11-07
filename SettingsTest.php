<?php
/**
 * Settings validation and storage.
 *
 * @package CarGawy\Core\Tests
 */

namespace CarGawy\Core\Tests;

use CarGawy\Core\Settings\Settings;

/**
 * @covers \CarGawy\Core\Settings\Settings
 */
class SettingsTest extends TestCase {

	public function test_contact_details_are_empty_by_default() {
		delete_option( Settings::OPTION );
		foreach ( array( 'contact_phone', 'contact_whatsapp', 'contact_email', 'contact_address' ) as $k ) {
			$this->assertSame( '', cargawy_setting( $k ), $k );
		}
		$this->assertFalse( cargawy_setting( 'delete_data_on_uninstall' ) );
		$this->assertSame( 3, cargawy_setting( 'max_active_listings_individual' ) );
		$this->assertSame( 90, cargawy_setting( 'inspection_validity_days' ) );
	}

	/**
	 * @dataProvider phones
	 */
	public function test_egyptian_phone_normalisation( $raw, $expected ) {
		$this->assertSame( $expected, Settings::normalize_egypt_phone( $raw ) );
	}

	public function phones() {
		return array(
			array( '01012345678', '+201012345678' ),
			array( '010 1234 5678', '+201012345678' ),
			array( '+20 112 345 6789', '+201123456789' ),
			array( '00201551234567', '+201551234567' ),
			array( '0223456789', '+20223456789' ),
			array( '01312345678', null ),
			array( '12345', null ),
		);
	}

	public function test_validate_keeps_previous_value_on_error() {
		update_option( Settings::OPTION, array( 'listing_expiry_days' => 45 ) );
		$r = Settings::validate(
			array(
				'listing_expiry_days' => '2',
				'ga4_id'              => 'UA-123',
				'contact_email'       => 'sales@cargawy.com',
				'social_facebook'     => 'http://facebook.com/cargawy',
			)
		);
		$this->assertSame( 45, $r['values']['listing_expiry_days'] );
		$this->assertArrayHasKey( 'listing_expiry_days', $r['errors'] );
		$this->assertArrayHasKey( 'ga4_id', $r['errors'] );
		$this->assertArrayHasKey( 'social_facebook', $r['errors'], 'only https URLs' );
		$this->assertSame( 'sales@cargawy.com', $r['values']['contact_email'] );
	}

	public function test_validate_accepts_good_ids() {
		$r = Settings::validate(
			array(
				'ga4_id'        => 'g-abc123xyz',
				'gads_id'       => 'AW-123456789',
				'meta_pixel_id' => '1234567890123',
				'contact_phone' => '01012345678',
			)
		);
		$this->assertSame( array(), $r['errors'] );
		$this->assertSame( 'G-ABC123XYZ', $r['values']['ga4_id'] );
		$this->assertSame( '+201012345678', $r['values']['contact_phone'] );
	}

	public function test_unknown_keys_are_dropped() {
		$r = Settings::validate( array( 'evil' => 'x' ) );
		$this->assertArrayNotHasKey( 'evil', $r['values'] );
	}

	public function test_changes_are_audited_by_key() {
		update_option( Settings::OPTION, Settings::defaults() );
		$new                        = Settings::defaults();
		$new['contact_email']       = 'info@cargawy.com';
		$new['listing_expiry_days'] = 30;
		update_option( Settings::OPTION, $new );
		$row = $this->last_audit();
		$this->assertSame( 'settings', $row->entity_type );
		$this->assertSame( 'contact_email, listing_expiry_days', $row->note );
	}

	public function test_first_save_is_audited_too() {
		delete_option( Settings::OPTION );
		add_option( Settings::OPTION, array( 'contact_phone' => '+201012345678' ) );
		$row = $this->last_audit();
		$this->assertSame( 'settings', $row->entity_type );
		$this->assertSame( 'contact_phone', $row->note );
	}
}
