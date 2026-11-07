<?php
/**
 * Activation, upgrade and deactivation.
 *
 * @package CarGawy\Core\Tests
 */

namespace CarGawy\Core\Tests;

use CarGawy\Core\Activation\Installer;
use CarGawy\Core\Database\Schema;
use CarGawy\Core\Permissions\Roles;

/**
 * @covers \CarGawy\Core\Activation\Installer
 */
class InstallerTest extends TestCase {

	public function test_all_tables_exist_after_activation() {
		$this->assertSame( array(), Schema::missing_tables() );
		$this->assertCount( 15, Schema::TABLES );
	}

	public function test_versions_are_recorded() {
		$this->assertSame( Installer::DB_VERSION, (int) get_option( Installer::DB_VERSION_OPTION ) );
		$this->assertSame( Roles::VERSION, (int) get_option( Roles::VERSION_OPTION ) );
	}

	public function test_install_is_idempotent() {
		Installer::install();
		Installer::install();
		$this->assertSame( array(), Schema::missing_tables() );
		$this->assertNotNull( get_role( 'cg_member' ) );
	}

	public function test_maybe_upgrade_resyncs_when_roles_version_is_behind() {
		update_option( Roles::VERSION_OPTION, 0 );
		remove_role( 'cg_inspector' );
		Installer::maybe_upgrade();
		$this->assertNotNull( get_role( 'cg_inspector' ) );
		$this->assertSame( Roles::VERSION, (int) get_option( Roles::VERSION_OPTION ) );
	}

	public function test_deactivation_keeps_tables_roles_and_settings() {
		update_option( 'cargawy_settings', array( 'contact_email' => 'info@example.org' ) );
		Installer::deactivate();
		$this->assertSame( array(), Schema::missing_tables() );
		$this->assertNotNull( get_role( 'cg_manager' ) );
		$this->assertSame( 'info@example.org', cargawy_setting( 'contact_email' ) );
		$this->assertSame( 'deactivated', $this->last_audit()->action );
	}

	public function test_vehicle_table_has_search_indexes() {
		global $wpdb;
		$table   = Schema::table( 'cg_vehicles' );
		$indexes = wp_list_pluck( $wpdb->get_results( "SHOW INDEX FROM {$table}" ), 'Key_name' ); // phpcs:ignore
		foreach ( array( 'make_model_year', 'price_egp', 'mileage_km', 'location', 'badge_inspection_id' ) as $key ) {
			$this->assertContains( $key, $indexes );
		}
	}
}
