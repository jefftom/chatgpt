<?php
/**
 * Database schema helpers.
 */

namespace WP_BetterForms\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Class Schema
 */
final class Schema {
public const VERSION = '1.0.1';

/**
 * Run migrations using dbDelta.
 */
public static function migrate(): void {
global $wpdb;

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

$charset_collate = $wpdb->get_charset_collate();
$forms_table     = $wpdb->prefix . 'bf_forms';
$entries_table   = $wpdb->prefix . 'bf_entries';
$entry_meta      = $wpdb->prefix . 'bf_entry_meta';
$logs_table      = $wpdb->prefix . 'bf_logs';
$email_queue     = $wpdb->prefix . 'bf_email_queue';

$forms_sql = "CREATE TABLE $forms_table (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
title VARCHAR(191) NOT NULL,
slug VARCHAR(191) NOT NULL,
schema_json LONGTEXT NOT NULL,
styles_json LONGTEXT NULL,
status VARCHAR(20) NOT NULL DEFAULT 'draft',
created_at DATETIME NOT NULL,
updated_at DATETIME NOT NULL,
PRIMARY KEY (id),
UNIQUE KEY slug (slug)
) $charset_collate;";

$entries_sql = "CREATE TABLE $entries_table (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
form_id BIGINT UNSIGNED NOT NULL,
user_id BIGINT UNSIGNED NULL,
ip VARBINARY(16) NULL,
ua VARCHAR(255) NULL,
status VARCHAR(20) NOT NULL DEFAULT 'pending',
created_at DATETIME NOT NULL,
PRIMARY KEY (id),
KEY form_id (form_id),
KEY created_at (created_at)
) $charset_collate;";

$entry_meta_sql = "CREATE TABLE $entry_meta (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
entry_id BIGINT UNSIGNED NOT NULL,
field_key VARCHAR(191) NOT NULL,
value_longtext LONGTEXT NULL,
value_indexed VARCHAR(191) NULL,
created_at DATETIME NOT NULL,
PRIMARY KEY (id),
KEY entry_field (entry_id, field_key),
KEY value_indexed (value_indexed)
) $charset_collate;";

$logs_sql = "CREATE TABLE $logs_table (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
form_id BIGINT UNSIGNED NULL,
type VARCHAR(20) NOT NULL,
level VARCHAR(20) NOT NULL,
message TEXT NOT NULL,
context_json LONGTEXT NULL,
created_at DATETIME NOT NULL,
PRIMARY KEY (id),
KEY form_type (form_id, type),
KEY created_at (created_at)
) $charset_collate;";

$email_queue_sql = "CREATE TABLE $email_queue (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
form_id BIGINT UNSIGNED NOT NULL,
entry_id BIGINT UNSIGNED NULL,
to_email VARCHAR(191) NOT NULL,
subject VARCHAR(255) NOT NULL,
body_html LONGTEXT NOT NULL,
status VARCHAR(20) NOT NULL DEFAULT 'pending',
attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
last_error TEXT NULL,
scheduled_at DATETIME NULL,
sent_at DATETIME NULL,
created_at DATETIME NOT NULL,
updated_at DATETIME NULL,
PRIMARY KEY (id),
KEY status (status),
KEY scheduled_at (scheduled_at)
) $charset_collate;";

dbDelta( $forms_sql );
dbDelta( $entries_sql );
dbDelta( $entry_meta_sql );
dbDelta( $logs_sql );
dbDelta( $email_queue_sql );

update_option( 'wp_betterforms_schema_version', self::VERSION );
}
}
