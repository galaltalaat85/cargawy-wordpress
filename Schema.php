<?php
/**
 * Custom table definitions (see docs/blueprint/CARGAWY_DATABASE_SCHEMA.md).
 *
 * Formatting follows dbDelta() rules: one column per line, two spaces after
 * PRIMARY KEY, KEY instead of INDEX.
 *
 * @package CarGawy\Core
 */

namespace CarGawy\Core\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Schema manager.
 */
final class Schema {

	/**
	 * Short table names (without the WordPress prefix).
	 */
	public const TABLES = array(
		'cg_vehicles',
		'cg_dealer_members',
		'cg_inspections',
		'cg_inspection_items',
		'cg_legal_reviews',
		'cg_files',
		'cg_leads',
		'cg_lead_notes',
		'cg_favorites',
		'cg_car_stats_daily',
		'cg_events',
		'cg_audit_log',
		'cg_moderation',
		'cg_redirects',
		'cg_404_log',
	);

	/**
	 * Full table name with prefix.
	 *
	 * @param string $short Short name, e.g. "cg_audit_log".
	 */
	public static function table( string $short ): string {
		global $wpdb;
		return $wpdb->prefix . $short;
	}

	/**
	 * Create or update all tables.
	 */
	public static function create_or_update(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( self::definitions() as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Tables that are missing in the database.
	 *
	 * @return string[] Short names.
	 */
	public static function missing_tables(): array {
		global $wpdb;
		$missing = array();
		foreach ( self::TABLES as $short ) {
			$name = self::table( $short );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- schema check.
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $name ) ) );
			if ( $found !== $name ) {
				$missing[] = $short;
			}
		}
		return $missing;
	}

	/**
	 * Drop all CarGawy tables. Only called from uninstall when explicitly enabled.
	 */
	public static function drop_all(): void {
		global $wpdb;
		foreach ( self::TABLES as $short ) {
			$name = self::table( $short );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is built from a constant list.
			$wpdb->query( "DROP TABLE IF EXISTS `{$name}`" );
		}
	}

	/**
	 * CREATE TABLE statements.
	 *
	 * @return string[]
	 */
	public static function definitions(): array {
		global $wpdb;
		$c = $wpdb->get_charset_collate();
		$t = static function ( $short ) {
			return self::table( $short );
		};

		$sql = array();

		$sql[] = "CREATE TABLE {$t('cg_vehicles')} (
  post_id bigint(20) unsigned NOT NULL,
  listing_source varchar(20) NOT NULL DEFAULT 'individual_seller',
  dealer_id bigint(20) unsigned DEFAULT NULL,
  make_term_id bigint(20) unsigned DEFAULT NULL,
  model_term_id bigint(20) unsigned DEFAULT NULL,
  trim varchar(80) NOT NULL DEFAULT '',
  model_year smallint(5) unsigned DEFAULT NULL,
  mileage_km int(10) unsigned DEFAULT NULL,
  price_egp int(10) unsigned DEFAULT NULL,
  negotiable tinyint(1) NOT NULL DEFAULT 0,
  body_term_id bigint(20) unsigned DEFAULT NULL,
  fuel_term_id bigint(20) unsigned DEFAULT NULL,
  transmission_term_id bigint(20) unsigned DEFAULT NULL,
  engine_cc smallint(5) unsigned DEFAULT NULL,
  engine_hp smallint(5) unsigned DEFAULT NULL,
  cylinders tinyint(3) unsigned DEFAULT NULL,
  drivetrain varchar(4) DEFAULT NULL,
  color_ext varchar(40) NOT NULL DEFAULT '',
  color_int varchar(40) NOT NULL DEFAULT '',
  seats tinyint(3) unsigned DEFAULT NULL,
  doors tinyint(3) unsigned DEFAULT NULL,
  condition_overall varchar(12) DEFAULT NULL,
  condition_ext varchar(12) DEFAULT NULL,
  condition_int varchar(12) DEFAULT NULL,
  paint_status varchar(20) NOT NULL DEFAULT 'unknown',
  accident_declared varchar(10) NOT NULL DEFAULT 'unknown',
  service_records varchar(10) DEFAULT NULL,
  owners_count tinyint(3) unsigned DEFAULT NULL,
  condition_notes text NULL,
  gov_term_id bigint(20) unsigned DEFAULT NULL,
  city_term_id bigint(20) unsigned DEFAULT NULL,
  availability varchar(10) NOT NULL DEFAULT 'available',
  is_featured tinyint(1) NOT NULL DEFAULT 0,
  featured_until datetime DEFAULT NULL,
  inspection_status varchar(32) NOT NULL DEFAULT 'not_requested',
  badge_inspection_id bigint(20) unsigned DEFAULT NULL,
  legal_status varchar(32) NOT NULL DEFAULT 'not_requested',
  vin_last6 char(6) DEFAULT NULL,
  contact_phone varchar(20) DEFAULT NULL,
  cost_egp int(10) unsigned DEFAULT NULL,
  purchase_date date DEFAULT NULL,
  published_at datetime DEFAULT NULL,
  expires_at datetime DEFAULT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (post_id),
  KEY make_model_year (make_term_id,model_term_id,model_year),
  KEY price_egp (price_egp),
  KEY mileage_km (mileage_km),
  KEY location (gov_term_id,city_term_id),
  KEY listing_source (listing_source),
  KEY availability_featured (availability,is_featured),
  KEY badge_inspection_id (badge_inspection_id),
  KEY dealer_id (dealer_id),
  KEY vin_last6 (vin_last6)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_dealer_members')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  dealer_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  member_role varchar(10) NOT NULL DEFAULT 'staff',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY dealer_user (dealer_id,user_id),
  KEY user_id (user_id)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_inspections')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  car_id bigint(20) unsigned NOT NULL,
  requested_by bigint(20) unsigned NOT NULL,
  inspector_id bigint(20) unsigned DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'requested',
  outcome varchar(30) DEFAULT NULL,
  scheduled_at datetime DEFAULT NULL,
  completed_at datetime DEFAULT NULL,
  location_text varchar(190) NOT NULL DEFAULT '',
  mileage_at_inspection int(10) unsigned DEFAULT NULL,
  summary_notes text NULL,
  report_file_id bigint(20) unsigned DEFAULT NULL,
  approved_by bigint(20) unsigned DEFAULT NULL,
  approved_at datetime DEFAULT NULL,
  valid_until date DEFAULT NULL,
  checklist_version smallint(5) unsigned NOT NULL DEFAULT 1,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY car_id (car_id),
  KEY inspector_status (inspector_id,status),
  KEY status (status)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_inspection_items')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  inspection_id bigint(20) unsigned NOT NULL,
  category varchar(20) NOT NULL,
  item_key varchar(60) NOT NULL,
  result varchar(12) NOT NULL DEFAULT 'not_checked',
  note text NULL,
  evidence_file_ids longtext NULL,
  PRIMARY KEY  (id),
  KEY inspection_id (inspection_id)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_legal_reviews')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  car_id bigint(20) unsigned NOT NULL,
  requested_by bigint(20) unsigned NOT NULL,
  reviewer_id bigint(20) unsigned DEFAULT NULL,
  status varchar(24) NOT NULL DEFAULT 'documents_requested',
  documents_received longtext NULL,
  documents_missing longtext NULL,
  result_notes text NULL,
  public_summary text NULL,
  reviewed_at datetime DEFAULT NULL,
  valid_until date DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY car_id (car_id),
  KEY reviewer_status (reviewer_id,status)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_files')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  uuid char(36) NOT NULL,
  owner_type varchar(20) NOT NULL,
  owner_id bigint(20) unsigned NOT NULL,
  uploaded_by bigint(20) unsigned NOT NULL,
  stored_path varchar(255) NOT NULL,
  mime varchar(100) NOT NULL,
  bytes bigint(20) unsigned NOT NULL DEFAULT 0,
  sha256 char(64) NOT NULL,
  original_name varchar(190) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  deleted_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY uuid (uuid),
  KEY owner (owner_type,owner_id)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_leads')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  car_id bigint(20) unsigned NOT NULL,
  owner_user_id bigint(20) unsigned NOT NULL,
  dealer_id bigint(20) unsigned DEFAULT NULL,
  buyer_user_id bigint(20) unsigned DEFAULT NULL,
  buyer_name varchar(100) DEFAULT NULL,
  buyer_phone varchar(20) DEFAULT NULL,
  buyer_email varchar(190) DEFAULT NULL,
  message text NULL,
  channel varchar(16) NOT NULL,
  utm_source varchar(100) DEFAULT NULL,
  utm_medium varchar(100) DEFAULT NULL,
  utm_campaign varchar(100) DEFAULT NULL,
  status varchar(12) NOT NULL DEFAULT 'new',
  assigned_to bigint(20) unsigned DEFAULT NULL,
  consent_at datetime DEFAULT NULL,
  ip_hash char(64) DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  anonymized_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY car_id (car_id),
  KEY owner_status (owner_user_id,status),
  KEY dealer_id (dealer_id),
  KEY created_at (created_at)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_lead_notes')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lead_id bigint(20) unsigned NOT NULL,
  author_id bigint(20) unsigned NOT NULL,
  note text NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY lead_id (lead_id)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_favorites')} (
  user_id bigint(20) unsigned NOT NULL,
  car_id bigint(20) unsigned NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (user_id,car_id),
  KEY car_id (car_id)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_car_stats_daily')} (
  car_id bigint(20) unsigned NOT NULL,
  day date NOT NULL,
  views int(10) unsigned NOT NULL DEFAULT 0,
  wa_clicks int(10) unsigned NOT NULL DEFAULT 0,
  call_clicks int(10) unsigned NOT NULL DEFAULT 0,
  phone_reveals int(10) unsigned NOT NULL DEFAULT 0,
  inquiries int(10) unsigned NOT NULL DEFAULT 0,
  favorites int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (car_id,day),
  KEY day (day)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_events')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  event varchar(40) NOT NULL,
  car_id bigint(20) unsigned DEFAULT NULL,
  user_id bigint(20) unsigned DEFAULT NULL,
  session_hash char(64) DEFAULT NULL,
  meta longtext NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY event_created (event,created_at),
  KEY car_id (car_id)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_audit_log')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
  actor_role varchar(40) NOT NULL DEFAULT '',
  entity_type varchar(30) NOT NULL,
  entity_id bigint(20) unsigned NOT NULL DEFAULT 0,
  action varchar(40) NOT NULL,
  from_value varchar(190) DEFAULT NULL,
  to_value varchar(190) DEFAULT NULL,
  note text NULL,
  ip_hash char(64) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY entity (entity_type,entity_id),
  KEY actor_id (actor_id),
  KEY created_at (created_at)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_moderation')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  car_id bigint(20) unsigned NOT NULL,
  action varchar(12) NOT NULL,
  reason_code varchar(30) DEFAULT NULL,
  note text NULL,
  actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY car_id (car_id),
  KEY action_created (action,created_at)
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_redirects')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  source_path varchar(255) NOT NULL,
  target varchar(255) NOT NULL,
  code smallint(3) unsigned NOT NULL DEFAULT 301,
  origin varchar(20) NOT NULL DEFAULT 'manual',
  object_type varchar(20) DEFAULT NULL,
  object_id bigint(20) unsigned DEFAULT NULL,
  hits int(10) unsigned NOT NULL DEFAULT 0,
  last_hit_at datetime DEFAULT NULL,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY source_path (source_path(191))
) $c;";

		$sql[] = "CREATE TABLE {$t('cg_404_log')} (
  path_hash char(40) NOT NULL,
  path varchar(255) NOT NULL,
  hits int(10) unsigned NOT NULL DEFAULT 1,
  first_seen datetime NOT NULL,
  last_seen datetime NOT NULL,
  referrer_sample varchar(255) DEFAULT NULL,
  PRIMARY KEY  (path_hash),
  KEY last_seen (last_seen)
) $c;";

		return $sql;
	}
}
