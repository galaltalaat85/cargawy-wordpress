<?php
/**
 * Audit log screen (read-only).
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Admin;

use CarGawy\Core\Audit\AuditLog;

defined( 'ABSPATH' ) || exit;

/**
 * Renders CarGawy → Audit log.
 */
final class AuditLogPage {

	private const PER_PAGE = 50;

	/**
	 * Render.
	 */
	public function render(): void {
		if ( ! current_user_can( 'cg_view_audit_log' ) ) {
			wp_die( esc_html__( 'You are not allowed to read the audit log.', 'cargawy-core' ), 403 );
		}

		// Read-only filters on a GET screen; no state change, so no nonce is needed.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$entity = isset( $_GET['entity_type'] ) ? sanitize_key( wp_unslash( $_GET['entity_type'] ) ) : '';
		$page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:enable

		$result = AuditLog::query(
			array(
				'entity_type' => $entity,
				'per_page'    => self::PER_PAGE,
				'page'        => $page,
			)
		);
		$pages  = (int) ceil( $result['total'] / self::PER_PAGE );

		echo '<div class="wrap"><h1>' . esc_html__( 'Audit log', 'cargawy-core' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Every sensitive action is recorded here. Entries cannot be edited or deleted.', 'cargawy-core' ) . '</p>';

		echo '<form method="get"><input type="hidden" name="page" value="cargawy-audit">';
		echo '<label for="cg-entity" class="screen-reader-text">' . esc_html__( 'Filter by type', 'cargawy-core' ) . '</label>';
		echo '<select id="cg-entity" name="entity_type"><option value="">' . esc_html__( 'All types', 'cargawy-core' ) . '</option>';
		foreach ( AuditLog::entity_types() as $type ) {
			printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $type ), selected( $entity, $type, false ) );
		}
		echo '</select> ';
		submit_button( __( 'Filter', 'cargawy-core' ), 'secondary', '', false );
		echo '</form><br>';

		echo '<table class="widefat striped"><thead><tr>';
		foreach ( array( __( 'When (UTC)', 'cargawy-core' ), __( 'Actor', 'cargawy-core' ), __( 'Entity', 'cargawy-core' ), __( 'Action', 'cargawy-core' ), __( 'From', 'cargawy-core' ), __( 'To', 'cargawy-core' ), __( 'Note', 'cargawy-core' ) ) as $h ) {
			echo '<th scope="col">' . esc_html( $h ) . '</th>';
		}
		echo '</tr></thead><tbody>';

		if ( ! $result['rows'] ) {
			echo '<tr><td colspan="7">' . esc_html__( 'No entries yet.', 'cargawy-core' ) . '</td></tr>';
		}
		foreach ( $result['rows'] as $row ) {
			$user  = $row->actor_id ? get_userdata( (int) $row->actor_id ) : null;
			$actor = $user ? $user->display_name . ' (' . $row->actor_role . ')' : __( 'System', 'cargawy-core' );
			printf(
				'<tr><td>%1$s</td><td>%2$s</td><td>%3$s #%4$d</td><td>%5$s</td><td>%6$s</td><td>%7$s</td><td>%8$s</td></tr>',
				esc_html( $row->created_at ),
				esc_html( $actor ),
				esc_html( $row->entity_type ),
				(int) $row->entity_id,
				esc_html( $row->action ),
				esc_html( (string) $row->from_value ),
				esc_html( (string) $row->to_value ),
				esc_html( (string) $row->note )
			);
		}
		echo '</tbody></table>';

		if ( $pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $page,
						'total'   => $pages,
					)
				)
			);
			echo '</div></div>';
		}
		echo '</div>';
	}
}
