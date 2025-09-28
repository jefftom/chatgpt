<?php
/**
 * Centralized logging.
 */

namespace WP_BetterForms\Logging;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 */
final class Logger {
public static function log( string $type, string $level, string $message, array $context = [], ?int $form_id = null ): void {
global $wpdb;

$table = $wpdb->prefix . 'bf_logs';

$wpdb->insert(
$table,
[
'form_id'      => $form_id,
'type'         => $type,
'level'        => $level,
'message'      => $message,
'context_json' => wp_json_encode( $context ),
'created_at'   => current_time( 'mysql' ),
]
);
}
}
