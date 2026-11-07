<?php
/**
 * Capability catalogue and role → capability matrix.
 *
 * Mirrors docs/blueprint/CARGAWY_ROLES_PERMISSIONS.md §2. Ownership scoping
 * ("own car", "assigned inspection") is applied later through map_meta_cap;
 * this file only defines who holds which primitive capability.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Permissions;

defined( 'ABSPATH' ) || exit;

/**
 * Capability definitions.
 */
final class Capabilities {

	/**
	 * Every CarGawy capability with a short description (used on the admin roles screen).
	 */
	public const ALL = array(
		'cg_access_admin'          => 'Open the WordPress dashboard (staff only)',
		'cg_submit_car'            => 'Create own listings',
		'cg_edit_own_car'          => 'Edit own (or own dealer’s) listings',
		'cg_delete_own_car'        => 'Delete own draft or rejected listings',
		'cg_mark_sold_own_car'     => 'Mark own listings sold or reserved',
		'cg_manage_inventory'      => 'Manage CarGawy-owned inventory',
		'cg_publish_inventory'     => 'Publish CarGawy inventory without moderation',
		'cg_view_inventory_cost'   => 'See internal cost and purchase data',
		'cg_edit_others_cars'      => 'Edit any listing',
		'cg_moderate_listings'     => 'Approve, reject and suspend listings',
		'cg_feature_listings'      => 'Feature listings',
		'cg_verify_dealers'        => 'Verify or suspend dealerships',
		'cg_manage_dealer_profile' => 'Manage own dealership profile',
		'cg_manage_dealer_members' => 'Manage own dealership staff',
		'cg_request_inspection'    => 'Request a technical inspection',
		'cg_schedule_inspection'   => 'Schedule inspections and assign inspectors',
		'cg_perform_inspection'    => 'Fill in assigned inspections',
		'cg_approve_inspection'    => 'Approve inspection results (issues the badge)',
		'cg_request_legal_review'  => 'Request a document review',
		'cg_perform_legal_review'  => 'Perform document reviews',
		'cg_view_private_files'    => 'Open private documents in scope',
		'cg_view_all_leads'        => 'See all leads',
		'cg_view_own_leads'        => 'See leads for own listings',
		'cg_manage_leads'          => 'Change lead status, assign, add notes',
		'cg_view_reports'          => 'See reports (scoped by role)',
		'cg_manage_seo'            => 'Edit SEO fields, redirects and 404 log',
		'cg_view_audit_log'        => 'Read the audit log',
		'cg_manage_settings'       => 'Change CarGawy settings',
	);

	/**
	 * CarGawy roles: slug => [ label, capability list ].
	 *
	 * The administrator role is not listed; it receives every capability in ALL.
	 * "read" is added to every role. cg_editor additionally inherits the core
	 * editor capabilities (see Roles::sync()).
	 *
	 * @return array<string, array{0:string,1:string[]}>
	 */
	public static function matrix(): array {
		return array(
			'cg_manager'         => array(
				'CarGawy Manager',
				array(
					'cg_access_admin',
					'cg_submit_car',
					'cg_edit_own_car',
					'cg_delete_own_car',
					'cg_mark_sold_own_car',
					'cg_manage_inventory',
					'cg_publish_inventory',
					'cg_view_inventory_cost',
					'cg_edit_others_cars',
					'cg_moderate_listings',
					'cg_feature_listings',
					'cg_verify_dealers',
					'cg_manage_dealer_profile',
					'cg_manage_dealer_members',
					'cg_request_inspection',
					'cg_schedule_inspection',
					'cg_approve_inspection',
					'cg_request_legal_review',
					'cg_perform_legal_review',
					'cg_view_private_files',
					'cg_view_all_leads',
					'cg_view_own_leads',
					'cg_manage_leads',
					'cg_view_reports',
					'cg_manage_seo',
					'cg_view_audit_log',
					// Core user management, limited to members and dealers by UserManagementGuard.
					'list_users',
					'create_users',
					'edit_users',
					'promote_users',
					'upload_files',
				),
			),
			'cg_inventory_staff' => array(
				'CarGawy Inventory Staff',
				array(
					'cg_access_admin',
					'cg_submit_car',
					'cg_edit_own_car',
					'cg_delete_own_car',
					'cg_mark_sold_own_car',
					'cg_manage_inventory',
					'cg_publish_inventory',
					'cg_request_inspection',
					'cg_request_legal_review',
					'cg_view_all_leads',
					'cg_view_own_leads',
					'cg_manage_leads',
					'upload_files',
				),
			),
			'cg_inspector'       => array(
				'CarGawy Inspector',
				array(
					'cg_access_admin',
					'cg_perform_inspection',
					'cg_view_private_files',
					'upload_files',
				),
			),
			'cg_legal_reviewer'  => array(
				'CarGawy Legal Reviewer',
				array(
					'cg_access_admin',
					'cg_perform_legal_review',
					'cg_view_private_files',
					'upload_files',
				),
			),
			'cg_editor'          => array(
				'CarGawy Content Editor',
				array(
					'cg_access_admin',
					'cg_manage_seo',
				),
			),
			'cg_dealer_owner'    => array(
				'Dealer (owner)',
				array(
					'cg_submit_car',
					'cg_edit_own_car',
					'cg_delete_own_car',
					'cg_mark_sold_own_car',
					'cg_manage_dealer_profile',
					'cg_manage_dealer_members',
					'cg_request_inspection',
					'cg_request_legal_review',
					'cg_view_private_files',
					'cg_view_own_leads',
					'cg_manage_leads',
					'cg_view_reports',
				),
			),
			'cg_dealer_staff'    => array(
				'Dealer staff',
				array(
					'cg_submit_car',
					'cg_edit_own_car',
					'cg_mark_sold_own_car',
					'cg_request_inspection',
					'cg_request_legal_review',
					'cg_view_own_leads',
					'cg_manage_leads',
					'cg_view_reports',
				),
			),
			'cg_member'          => array(
				'Member (buyer & individual seller)',
				array(
					'cg_submit_car',
					'cg_edit_own_car',
					'cg_delete_own_car',
					'cg_mark_sold_own_car',
					'cg_request_inspection',
					'cg_request_legal_review',
					'cg_view_private_files',
					'cg_view_own_leads',
					'cg_view_reports',
				),
			),
		);
	}

	/**
	 * Roles that use the front-end account area instead of wp-admin.
	 *
	 * @return string[]
	 */
	public static function front_end_roles(): array {
		return array( 'cg_member', 'cg_dealer_owner', 'cg_dealer_staff' );
	}

	/**
	 * Roles a CarGawy Manager may create, edit or assign.
	 *
	 * @return string[]
	 */
	public static function manager_assignable_roles(): array {
		return self::front_end_roles();
	}
}
