<?php
/**
 * Append-only audit log.
 *
 * There is deliberately no update or delete method: rows are only removed by
 * uninstall (when explicitly enabled) or a future retention job.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Audit;

use CarGawy\Core\Database\Schema;
use CarGawy\Core\Support\Request;

defined( 'ABSPATH' ) || exit;

/**
 * Audit log repository.
 */
final class AuditLog {

	/**
	 * Insert an audit row.
	 *
	 * @param string       $entity_type Entity type.
	 * @param int      $entity_id   Entity ID.
	 * @param string       $action      Action key.
	 * @param string|null  $from_value  Previous value.
	 * @param string|null  $to_value    New value.
	 * @param string       $note        Note.
	 * @param int|null $actor_id    Actor; defaults to the current user (0 = system).
	 * @return int|false
	 */
	public static function record( $entity_type, $entity_id, $action, $from_value = null, $to_value = null, $note = '', $actor_id = null ) {
		global $wpdb;

		$actor_id = null === $actor_id ? get_current_user_id() : (int) $actor_id;
		$role     = '';
		if ( $actor_id ) {
			$user = get_userdata( $actor_id );
			$role = $user && $user->roles ? (string) reset( $user->roles ) : '';
		}

		$row = array(
			'actor_id'    => $actor_id,
			'actor_role'  => substr( $role, 0, 40 ),
			'entity_type' => substr( sanitize_key( $entity_type ), 0, 30 ),
			'entity_id'   => absint( $entity_id ),
			'action'      => substr( sanitize_key( $action ), 0, 40 ),
			'from_value'  => null === $from_value ? null : self::clip( (string) $from_value ),
			'to_value'    => null === $to_value ? null : self::clip( (string) $to_value ),
			'note'        => '' === $note ? null : sanitize_textarea_field( $note ),
			'ip_hash'     => Request::ip_hash(),
			'created_at'  => current_time( 'mysql', true ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom table write.
		$ok = $wpdb->insert( Schema::table( 'cg_audit_log' ), $row );
		return $ok ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Query rows, newest first.
	 *
	 * @param array $args { entity_type?: string, entity_id?: int, actor_id?: int, per_page?: int, page?: int }.
	 * @return array{rows: array<int, object>, total: int}
	 */
	public static function query( array $args = array() ): array {
		global $wpdb;
		$table    = Schema::table( 'cg_audit_log' );
		$where    = array( '1=1' );
		$params   = array();
		$per_page = max( 1, min( 200, (int) ( $args['per_page'] ?? 50 ) ) );
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );

		if ( ! empty( $args['entity_type'] ) ) {
			$where[]  = 'entity_type = %s';
			$params[] = sanitize_key( $args['entity_type'] );
		}
		if ( ! empty( $args['entity_id'] ) ) {
			$where[]  = 'entity_id = %d';
			$params[] = (int) $args['entity_id'];
		}
		if ( ! empty( $args['actor_id'] ) ) {
			$where[]  = 'actor_id = %d';
			$params[] = (int) $args['actor_id'];
		}
		$where_sql = implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.NotPrepared -- table name is internal; values are prepared.
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$total     = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) );
		$rows      = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
				array_merge( $params, array( $per_page, ( $page - 1 ) * $per_page ) )
			)
		);
		// phpcs:enable

		return array(
			'rows'  => $rows ? $rows : array(),
			'total' => $total,
		);
	}

	/**
	 * Distinct entity types present in the log (for the admin filter).
	 *
	 * @return string[]
	 */
	public static function entity_types(): array {
		global $wpdb;
		$table = Schema::table( 'cg_audit_log' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- internal table name.
		return (array) $wpdb->get_col( "SELECT DISTINCT entity_type FROM {$table} ORDER BY entity_type" );
	}

	/**
	 * Limit a value to the column length.
	 *
	 * @param string $value Value.
	 */
	private static function clip( string $value ): string {
		return mb_substr( sanitize_text_field( $value ), 0, 190 );
	}
}
