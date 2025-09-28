<?php
/**
 * Scheduler for email retries.
 */

namespace WP_BetterForms\Scheduler;

defined( 'ABSPATH' ) || exit;

use WP_BetterForms\Mailing\Mailer;
use WP_BetterForms\Logging\Logger;

/**
 * Class Scheduler
 */
final class Scheduler {
private const CRON_HOOK = 'wp_betterforms/process_email_queue';

private static ?self $instance = null;

private function __construct() {
add_filter( 'cron_schedules', [ $this, 'register_schedule' ] );
add_action( self::CRON_HOOK, [ $this, 'process_queue' ] );

if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
wp_schedule_event( time() + MINUTE_IN_SECONDS, 'five_minutes', self::CRON_HOOK );
}
}

public static function get_instance(): self {
if ( null === self::$instance ) {
self::$instance = new self();
}

return self::$instance;
}

public function register_schedule( array $schedules ): array {
$schedules['five_minutes'] = [
'interval' => 5 * MINUTE_IN_SECONDS,
'display'  => __( 'Every Five Minutes', 'wp-betterforms' ),
];

return $schedules;
}

public function process_queue(): void {
global $wpdb;

$table = $wpdb->prefix . 'bf_email_queue';
$rows  = $wpdb->get_results( "SELECT * FROM $table WHERE status = 'pending' ORDER BY created_at ASC LIMIT 5", ARRAY_A );

foreach ( $rows as $row ) {
$sent = Mailer::send_now( $row );

$wpdb->update(
$table,
[
'updated_at' => current_time( 'mysql' ),
'last_error' => $sent ? null : __( 'Email send failed.', 'wp-betterforms' ),
'status'     => $sent ? 'sent' : 'failed',
'sent_at'    => $sent ? current_time( 'mysql' ) : null,
'attempts'   => (int) $row['attempts'] + 1,
],
[ 'id' => $row['id'] ]
);

if ( ! $sent ) {
Logger::log( 'email', 'error', 'Email queue item failed to send.', $row, (int) $row['form_id'] );
}
}
}

public function unschedule_events(): void {
$timestamp = wp_next_scheduled( self::CRON_HOOK );
if ( $timestamp ) {
wp_unschedule_event( $timestamp, self::CRON_HOOK );
}
}
}
