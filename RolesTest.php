<?php
/**
 * Roles and the capability matrix.
 *
 * @package CarGawy\Core\Tests
 */

namespace CarGawy\Core\Tests;

use CarGawy\Core\Permissions\AdminAccess;
use CarGawy\Core\Permissions\Capabilities;
use CarGawy\Core\Permissions\Roles;

/**
 * @covers \CarGawy\Core\Permissions\Roles
 * @covers \CarGawy\Core\Permissions\AdminAccess
 */
class RolesTest extends TestCase {

	public function test_every_role_matches_the_matrix_exactly() {
		foreach ( Capabilities::matrix() as $slug => $def ) {
			$role = get_role( $slug );
			$this->assertNotNull( $role, $slug );
			$granted = array_keys( array_filter( $role->capabilities ) );
			$cg_caps = array_values( array_filter( $granted, fn( $c ) => 0 === strpos( $c, 'cg_' ) ) );
			$expect  = array_values( array_filter( $def[1], fn( $c ) => 0 === strpos( $c, 'cg_' ) ) );
			sort( $cg_caps );
			sort( $expect );
			$this->assertSame( $expect, $cg_caps, "CarGawy caps for {$slug}" );
			$this->assertContains( 'read', $granted );
		}
	}

	public function test_every_matrix_cap_is_catalogued() {
		foreach ( Capabilities::matrix() as $slug => $def ) {
			foreach ( $def[1] as $cap ) {
				if ( 0 === strpos( $cap, 'cg_' ) ) {
					$this->assertArrayHasKey( $cap, Capabilities::ALL, "{$slug}: {$cap}" );
				}
			}
		}
	}

	public function test_administrator_has_every_cargawy_cap() {
		$admin = $this->user( 'administrator' );
		foreach ( array_keys( Capabilities::ALL ) as $cap ) {
			$this->assertTrue( user_can( $admin, $cap ), $cap );
		}
	}

	public function test_only_admin_manages_settings() {
		foreach ( array_keys( Capabilities::matrix() ) as $slug ) {
			$this->assertFalse( user_can( $this->user( $slug ), 'cg_manage_settings' ), $slug );
		}
	}

	public function test_inspector_cannot_approve_listings_or_inspections_or_do_legal() {
		$u = $this->user( 'cg_inspector' );
		$this->assertTrue( user_can( $u, 'cg_perform_inspection' ) );
		foreach ( array( 'cg_moderate_listings', 'cg_approve_inspection', 'cg_perform_legal_review', 'cg_schedule_inspection' ) as $cap ) {
			$this->assertFalse( user_can( $u, $cap ), $cap );
		}
	}

	public function test_legal_reviewer_has_no_inspection_powers() {
		$u = $this->user( 'cg_legal_reviewer' );
		$this->assertTrue( user_can( $u, 'cg_perform_legal_review' ) );
		foreach ( array( 'cg_perform_inspection', 'cg_approve_inspection', 'cg_moderate_listings' ) as $cap ) {
			$this->assertFalse( user_can( $u, $cap ), $cap );
		}
	}

	public function test_dealers_cannot_touch_cargawy_inventory() {
		foreach ( array( 'cg_dealer_owner', 'cg_dealer_staff', 'cg_member' ) as $slug ) {
			$u = $this->user( $slug );
			foreach ( array( 'cg_manage_inventory', 'cg_view_inventory_cost', 'cg_edit_others_cars', 'cg_moderate_listings', 'cg_view_all_leads', 'upload_files' ) as $cap ) {
				$this->assertFalse( user_can( $u, $cap ), "{$slug}: {$cap}" );
			}
		}
	}

	public function test_only_moderators_can_approve_external_listings() {
		$allowed = array( 'cg_manager' );
		foreach ( array_keys( Capabilities::matrix() ) as $slug ) {
			$this->assertSame( in_array( $slug, $allowed, true ), user_can( $this->user( $slug ), 'cg_moderate_listings' ), $slug );
		}
	}

	public function test_content_editor_can_publish_posts_but_not_moderate() {
		$u = $this->user( 'cg_editor' );
		$this->assertTrue( user_can( $u, 'publish_posts' ) );
		$this->assertTrue( user_can( $u, 'cg_manage_seo' ) );
		$this->assertFalse( user_can( $u, 'cg_moderate_listings' ) );
		$this->assertFalse( user_can( $u, 'list_users' ) );
	}

	public function test_sync_removes_capabilities_dropped_from_matrix() {
		get_role( 'cg_member' )->add_cap( 'cg_moderate_listings' );
		Roles::sync();
		$this->assertFalse( get_role( 'cg_member' )->has_cap( 'cg_moderate_listings' ) );
	}

	public function test_front_end_roles_are_kept_out_of_wp_admin() {
		foreach ( array( 'cg_member', 'cg_dealer_owner', 'cg_dealer_staff' ) as $slug ) {
			$this->assertFalse( AdminAccess::can_access_admin( $this->user( $slug ) ), $slug );
		}
		foreach ( array( 'administrator', 'cg_manager', 'cg_inventory_staff', 'cg_inspector', 'cg_legal_reviewer', 'cg_editor', 'subscriber' ) as $slug ) {
			$this->assertTrue( AdminAccess::can_access_admin( $this->user( $slug ) ), $slug );
		}
	}

	public function test_admin_bar_hidden_for_members() {
		wp_set_current_user( $this->user( 'cg_member' ) );
		$this->assertFalse( AdminAccess::filter_admin_bar( true ) );
		wp_set_current_user( $this->user( 'cg_manager' ) );
		$this->assertTrue( AdminAccess::filter_admin_bar( true ) );
	}
}
