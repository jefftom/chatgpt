<?php
/**
 * Activation handler.
 */

namespace WP_BetterForms;

defined( 'ABSPATH' ) || exit;

use WP_BetterForms\DB\Schema;

/**
 * Class Activator
 */
final class Activator {
/**
 * Run on plugin activation.
 */
public static function activate(): void {
self::ensure_requirements();
Schema::migrate();
do_action( 'wp_betterforms/activated' );
}

/**
 * Ensure environment meets plugin requirements.
 */
private static function ensure_requirements(): void {
global $wp_version;

if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
wp_die( esc_html__( 'WP Better Forms requires PHP 8.0 or newer.', 'wp-betterforms' ) );
}

if ( version_compare( (string) $wp_version, '6.5', '<' ) ) {
wp_die( esc_html__( 'WP Better Forms requires WordPress 6.5 or newer.', 'wp-betterforms' ) );
}
}
}
