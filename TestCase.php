<?php
/**
 * Shared test helpers.
 *
 * @package CarGawy\Core\Tests
 */

namespace CarGawy\Core\Tests;

/**
 * Base test case.
 */
abstract class TestCase extends \WP_UnitTestCase {

	/**
	 * Create a user with a role and return the ID.
	 *
	 * @param string $role Role slug.
	 */
	protected function user( string $role ): int {
		return self::factory()->user->create( array( 'role' => $role ) );
	}

	/**
	 * Latest audit row.
	 */
	protected function last_audit(): ?object {
		global $wpdb;
		$table = \CarGawy\Core\Database\Schema::table( 'cg_audit_log' );
		return $wpdb->get_row( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 1" ); // phpcs:ignore
	}
}
