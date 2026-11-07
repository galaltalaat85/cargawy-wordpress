<?php
/**
 * Uninstall only removes data when explicitly enabled.
 *
 * DROP TABLE commits the test transaction, so this test restores the
 * schema and roles itself.
 *
 * @package CarGawy\Core\Tests
 */

namespace CarGawy\Core\Tests;

use CarGawy\Core\Activation\Installer;
use CarGawy\Core\Activation\Uninstaller;
use CarGawy\Core\Database\Schema;
use CarGawy\Core\Settings\Settings;

/**
 * @covers \CarGawy\Core\Activation\Uninstaller
 */
class UninstallerTest extends TestCase {

	public function test_default_uninstall_keeps_everything() {
		update_option( Settings::OPTION, Settings::defaults() );
		$this->assertFalse( Uninstaller::run() );
		$this->assertSame( array(), Schema::missing_tables() );
		$this->assertNotNull( get_role( 'cg_member' ) );
	}

	public function test_uninstall_with_opt_in_removes_tables_roles_and_options() {
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		$settings                             = Settings::defaults();
		$settings['delete_data_on_uninstall'] = true;
		update_option( Settings::OPTION, $settings );

		$this->assertTrue( Uninstaller::run() );
		$this->assertSame( Schema::TABLES, Schema::missing_tables() );
		$this->assertNull( get_role( 'cg_member' ) );
		$this->assertFalse( user_can( $this->user( 'administrator' ), 'cg_view_audit_log' ) );
		$this->assertFalse( get_option( Settings::OPTION ) );

		// Restore for the rest of the suite.
		Installer::install();
		$this->assertSame( array(), Schema::missing_tables() );
	}
}
