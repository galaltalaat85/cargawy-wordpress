<?php
/**
 * Audit log.
 *
 * @package CarGawy\Core\Tests
 */

namespace CarGawy\Core\Tests;

use CarGawy\Core\Audit\AuditLog;

/**
 * @covers \CarGawy\Core\Audit\AuditLog
 */
class AuditLogTest extends TestCase {

	public function tear_down() {
		unset( $_SERVER['REMOTE_ADDR'] );
		parent::tear_down();
	}

	public function test_records_actor_role_and_hashed_ip_never_raw_ip() {
		$manager = $this->user( 'cg_manager' );
		wp_set_current_user( $manager );
		$_SERVER['REMOTE_ADDR'] = '197.34.10.5';

		$id  = cargawy_audit( 'listing', 42, 'transition', 'pending', 'publish', 'Looks good' );
		$row = $this->last_audit();

		$this->assertSame( $id, (int) $row->id );
		$this->assertSame( $manager, (int) $row->actor_id );
		$this->assertSame( 'cg_manager', $row->actor_role );
		$this->assertSame( 'pending', $row->from_value );
		$this->assertSame( 'publish', $row->to_value );
		$this->assertSame( 64, strlen( $row->ip_hash ) );
		$this->assertStringNotContainsString( '197.34', wp_json_encode( $row ) );
	}

	public function test_system_actions_have_actor_zero() {
		wp_set_current_user( 0 );
		cargawy_audit( 'cron', 0, 'expired', 'publish', 'cg_expired' );
		$this->assertSame( 0, (int) $this->last_audit()->actor_id );
		$this->assertNull( $this->last_audit()->ip_hash );
	}

	public function test_values_are_sanitized_and_clipped() {
		cargawy_audit( 'Listing Type!', 1, 'Some Action', '<script>x</script>', str_repeat( 'a', 500 ) );
		$row = $this->last_audit();
		$this->assertSame( 'listingtype', $row->entity_type );
		$this->assertStringNotContainsString( '<script>', (string) $row->from_value );
		$this->assertSame( 190, mb_strlen( $row->to_value ) );
	}

	public function test_query_filters_and_paginates() {
		for ( $i = 1; $i <= 5; $i++ ) {
			cargawy_audit( 'inspection', 7, 'transition', null, "s{$i}" );
		}
		cargawy_audit( 'listing', 9, 'transition' );

		$r = AuditLog::query(
			array(
				'entity_type' => 'inspection',
				'entity_id'   => 7,
				'per_page'    => 2,
				'page'        => 1,
			)
		);
		$this->assertSame( 5, $r['total'] );
		$this->assertCount( 2, $r['rows'] );
		$this->assertSame( 's5', $r['rows'][0]->to_value );

		$page3 = AuditLog::query(
			array(
				'entity_type' => 'inspection',
				'per_page'    => 2,
				'page'        => 3,
			)
		);
		$this->assertCount( 1, $page3['rows'] );
	}

	public function test_repository_exposes_no_update_or_delete() {
		$methods = get_class_methods( AuditLog::class );
		foreach ( $methods as $m ) {
			$this->assertDoesNotMatchRegularExpression( '/update|delete|remove|purge/i', $m );
		}
	}
}
