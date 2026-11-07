<?php
/**
 * Managers can manage members and dealers only.
 *
 * @package CarGawy\Core\Tests
 */

namespace CarGawy\Core\Tests;

/**
 * @covers \CarGawy\Core\Permissions\UserManagementGuard
 */
class UserManagementGuardTest extends TestCase {

	public function test_manager_can_edit_members_and_dealers() {
		$manager = $this->user( 'cg_manager' );
		foreach ( array( 'cg_member', 'cg_dealer_owner', 'cg_dealer_staff' ) as $slug ) {
			$this->assertTrue( user_can( $manager, 'edit_user', $this->user( $slug ) ), $slug );
		}
	}

	public function test_manager_cannot_edit_or_delete_staff_or_admins() {
		$manager = $this->user( 'cg_manager' );
		foreach ( array( 'administrator', 'cg_manager', 'cg_inspector', 'cg_legal_reviewer', 'cg_inventory_staff', 'cg_editor' ) as $slug ) {
			$target = $this->user( $slug );
			$this->assertFalse( user_can( $manager, 'edit_user', $target ), "edit {$slug}" );
			$this->assertFalse( user_can( $manager, 'delete_user', $target ), "delete {$slug}" );
			$this->assertFalse( user_can( $manager, 'promote_user', $target ), "promote {$slug}" );
		}
	}

	public function test_manager_can_only_assign_front_end_roles() {
		wp_set_current_user( $this->user( 'cg_manager' ) );
		$roles = array_keys( get_editable_roles() );
		sort( $roles );
		$this->assertSame( array( 'cg_dealer_owner', 'cg_dealer_staff', 'cg_member' ), $roles );
	}

	public function test_admin_is_unrestricted() {
		$admin = $this->user( 'administrator' );
		wp_set_current_user( $admin );
		$this->assertTrue( user_can( $admin, 'edit_user', $this->user( 'cg_manager' ) ) );
		$this->assertArrayHasKey( 'administrator', get_editable_roles() );
	}
}
