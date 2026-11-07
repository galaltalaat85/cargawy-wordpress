<?php
/**
 * wp-admin menu for CarGawy staff.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Admin;

use CarGawy\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the CarGawy admin menu and its pages.
 */
final class Menu {

	public const SLUG = 'cargawy';

	/**
	 * Register hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Menu structure. Later phases add Vehicles, Dealers, Inspections, Leads, SEO.
	 */
	public function register_menu(): void {
		$health = new HealthPage();
		add_menu_page(
			__( 'CarGawy', 'cargawy-core' ),
			__( 'CarGawy', 'cargawy-core' ),
			'cg_access_admin',
			self::SLUG,
			array( $health, 'render' ),
			'dashicons-car',
			3
		);
		add_submenu_page( self::SLUG, __( 'System health', 'cargawy-core' ), __( 'System health', 'cargawy-core' ), 'cg_access_admin', self::SLUG, array( $health, 'render' ) );
		add_submenu_page( self::SLUG, __( 'Audit log', 'cargawy-core' ), __( 'Audit log', 'cargawy-core' ), 'cg_view_audit_log', 'cargawy-audit', array( new AuditLogPage(), 'render' ) );
		add_submenu_page( self::SLUG, __( 'Roles & permissions', 'cargawy-core' ), __( 'Roles & permissions', 'cargawy-core' ), 'cg_view_audit_log', 'cargawy-roles', array( new RolesPage(), 'render' ) );
		add_submenu_page( self::SLUG, __( 'Settings', 'cargawy-core' ), __( 'Settings', 'cargawy-core' ), 'cg_manage_settings', 'cargawy-settings', array( new SettingsPage(), 'render' ) );
	}

	/**
	 * Settings API registration.
	 */
	public function register_settings(): void {
		register_setting(
			'cargawy_settings_group',
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::defaults(),
				'show_in_rest'      => false,
			)
		);
		add_filter(
			'option_page_capability_cargawy_settings_group',
			static function () {
				return 'cg_manage_settings';
			}
		);
		( new SettingsPage() )->register_fields();
	}
}
