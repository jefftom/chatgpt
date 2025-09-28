<?php
/**
 * Mailer wrapper and queueing.
 */

namespace WP_BetterForms\Mailing;

defined( 'ABSPATH' ) || exit;

use WP_BetterForms\Logging\Logger;

/**
 * Class Mailer
 */
final class Mailer {
public static function queue_submission_email( array $form, array $data, int $entry_id ): void {
$email = self::prepare_submission_email( $form, $data );

self::enqueue_email( [
'form_id'   => (int) $form['id'],
'entry_id'  => $entry_id,
'to_email'  => $email['to'],
'subject'   => $email['subject'],
'body_html' => $email['body'],
] );
}

public static function enqueue_email( array $payload ): void {
global $wpdb;

$table = $wpdb->prefix . 'bf_email_queue';

$wpdb->insert(
$table,
[
'form_id'   => $payload['form_id'],
'entry_id'  => $payload['entry_id'] ?? null,
'to_email'  => sanitize_email( $payload['to_email'] ),
'subject'   => sanitize_text_field( $payload['subject'] ),
'body_html' => wp_kses_post( $payload['body_html'] ),
'created_at'=> current_time( 'mysql' ),
'updated_at'=> current_time( 'mysql' ),
]
);

do_action( 'wp_betterforms/email_enqueued', (int) $wpdb->insert_id, $payload );
}

public static function send_now( array $record ): bool {
$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
$sent    = wp_mail( $record['to_email'], $record['subject'], $record['body_html'], $headers );

Logger::log( 'email', $sent ? 'info' : 'error', 'Submission email processed.', [ 'record' => $record, 'sent' => $sent ], (int) ( $record['form_id'] ?? 0 ) );

return $sent;
}

private static function prepare_submission_email( array $form, array $data ): array {
$admin_email = get_option( 'admin_email' );
$subject     = sprintf( /* translators: %s is form title */ __( 'New submission for %s', 'wp-betterforms' ), $form['title'] ?? __( 'Better Form', 'wp-betterforms' ) );
$body_lines  = [];

foreach ( $data as $key => $value ) {
$body_lines[] = '<p><strong>' . esc_html( (string) $key ) . ':</strong> ' . esc_html( is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) ) . '</p>';
}

$body = '<h2>' . esc_html( $form['title'] ?? __( 'Better Form', 'wp-betterforms' ) ) . '</h2>' . implode( '', $body_lines );

return [
'to'      => $admin_email,
'subject' => $subject,
'body'    => $body,
];
}
}
