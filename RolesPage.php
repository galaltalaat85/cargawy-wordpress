<?php
/**
 * Read-only view of the role → capability matrix.
 *
 * Roles are defined in code (Capabilities::matrix) so they are versioned and
 * tested; this screen lets managers check who can do what.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Admin;

use CarGawy\Core\Permissions\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Renders CarGawy → Roles & permissions.
 */
final class RolesPage {

	/**
	 * Render.
	 */
	public function render(): void {
		if ( ! current_user_can( 'cg_view_audit_log' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'cargawy-core' ), 403 );
		}
		$roles = array( 'administrator' => __( 'Administrator', 'cargawy-core' ) );
		foreach ( Capabilities::matrix() as $slug => $def ) {
			$roles[ $slug ] = $def[0];
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Roles & permissions', 'cargawy-core' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Defined in code and versioned. To change a permission, update the matrix in cargawy-core and deploy.', 'cargawy-core' ) . '</p>';
		echo '<div style="overflow-x:auto"><table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Capability', 'cargawy-core' ) . '</th>';
		foreach ( $roles as $slug => $label ) {
			$count = count(
				get_users(
					array(
						'role'   => $slug,
						'fields' => 'ID',
					)
				)
			); // phpcs:ignore WordPress.DB.SlowDBQuery -- small admin page.
			printf( '<th scope="col">%s<br><span class="description">%d</span></th>', esc_html( $label ), (int) $count );
		}
		echo '</tr></thead><tbody>';
		foreach ( Capabilities::ALL as $cap => $desc ) {
			printf( '<tr><th scope="row"><code>%1$s</code><br><span class="description">%2$s</span></th>', esc_html( $cap ), esc_html( $desc ) );
			foreach ( array_keys( $roles ) as $slug ) {
				$role = get_role( $slug );
				$has  = $role && $role->has_cap( $cap );
				printf( '<td aria-label="%1$s">%2$s</td>', esc_attr( $has ? __( 'Yes', 'cargawy-core' ) : __( 'No', 'cargawy-core' ) ), $has ? '✔' : '—' );
			}
			echo '</tr>';
		}
		echo '</tbody></table></div></div>';
	}
}
