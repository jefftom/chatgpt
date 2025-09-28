<?php
/**
 * Admin notices for email failures.
 */

namespace WP_BetterForms\Admin;

defined( 'ABSPATH' ) || exit;

use WP_BetterForms\Logging\Logger;

/**
 * Class Admin_Notices
 */
final class Admin_Notices {
private static ?self $instance = null;

private function __construct() {
add_action( 'admin_notices', [ $this, 'render_notice' ] );
}

public static function get_instance(): self {
if ( null === self::$instance ) {
self::$instance = new self();
}

return self::$instance;
}

public function render_notice(): void {
if ( ! current_user_can( 'manage_options' ) ) {
return;
}

$failures = $this->get_recent_failures();

if ( $failures < 3 ) {
return;
}

echo '<div class="notice notice-error"><p>' . esc_html__( 'WP Better Forms detected repeated email delivery issues in the last hour. Please review the email queue.', 'wp-betterforms' ) . '</p></div>';
}

private function get_recent_failures(): int {
global $wpdb;

$table = $wpdb->prefix . 'bf_logs';
$sql   = $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE type = %s AND level = %s AND created_at >= DATE_SUB( NOW(), INTERVAL 1 HOUR )", 'email', 'error' );

return (int) $wpdb->get_var( $sql );
}
}
