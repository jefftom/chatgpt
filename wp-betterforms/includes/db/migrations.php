<?php
/**
 * Migration registry placeholder.
 */

namespace WP_BetterForms\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migrations
 */
final class Migrations {
/**
 * Run pending migrations.
 */
public static function run(): void {
$current = get_option( 'wp_betterforms_schema_version' );
if ( version_compare( (string) $current, Schema::VERSION, '<' ) ) {
Schema::migrate();
}
}
}
