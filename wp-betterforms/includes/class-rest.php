<?php
/**
 * REST API controller.
 */

namespace WP_BetterForms\Rest;

defined( 'ABSPATH' ) || exit;

use WP_BetterForms\Form_Repository;
use WP_BetterForms\Rendering\Renderer;
use WP_BetterForms\Validation\Validator;
use WP_BetterForms\Mailing\Mailer;
use WP_BetterForms\DB\Migrations;
use WP_BetterForms\Logging\Logger;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Rest_Controller
 */
final class Rest_Controller {
public const NAMESPACE = 'bf/v1';

private static ?self $instance = null;

private function __construct() {}

public static function get_instance(): self {
if ( null === self::$instance ) {
self::$instance = new self();
}

return self::$instance;
}

public function register_routes(): void {
Migrations::run();

register_rest_route(
self::NAMESPACE,
'/forms',
[
[
'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
'callback'            => [ $this, 'list_forms' ],
'methods'             => WP_REST_Server::READABLE,
],
[
'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
'callback'            => [ $this, 'save_form' ],
'methods'             => WP_REST_Server::CREATABLE,
],
]
);

register_rest_route(
self::NAMESPACE,
'/forms/(?P<form_id>\d+)',
[
'methods'             => WP_REST_Server::READABLE,
'callback'            => [ $this, 'get_form' ],
'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
'args'                => [
'form_id' => [
'validate_callback' => static fn( $param ): bool => is_numeric( $param ),
],
],
]
);

register_rest_route(
self::NAMESPACE,
'/submit/(?P<form_id>\d+)',
[
'methods'             => WP_REST_Server::CREATABLE,
'callback'            => [ $this, 'submit_form' ],
'permission_callback' => '__return_true',
'args'                => [
'form_id' => [
'validate_callback' => static fn( $param ): bool => is_numeric( $param ),
],
],
]
);

register_rest_route(
self::NAMESPACE,
'/email-queue/failures',
[
'methods'             => WP_REST_Server::READABLE,
'callback'            => [ $this, 'list_email_failures' ],
'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
]
);

register_rest_route(
self::NAMESPACE,
'/email-queue/(?P<queue_id>\d+)/resend',
[
'methods'             => WP_REST_Server::CREATABLE,
'callback'            => [ $this, 'resend_email' ],
'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
'args'                => [
'queue_id' => [
'validate_callback' => static fn( $param ): bool => is_numeric( $param ),
],
],
]
);

register_rest_route(
self::NAMESPACE,
'/email-queue/test',
[
'methods'             => WP_REST_Server::CREATABLE,
'callback'            => [ $this, 'send_test_email' ],
'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
]
);
}

public function list_forms( WP_REST_Request $request ): WP_REST_Response {
return new WP_REST_Response( [ 'forms' => Form_Repository::list_forms() ] );
}

public function get_form( WP_REST_Request $request ): WP_REST_Response|WP_Error {
$form_id = (int) $request->get_param( 'form_id' );
$form    = Form_Repository::get_form( $form_id );

if ( ! $form ) {
return new WP_Error( 'bf_form_not_found', __( 'Form not found.', 'wp-betterforms' ), [ 'status' => 404 ] );
}

return new WP_REST_Response( [ 'form' => $form ] );
}

public function save_form( WP_REST_Request $request ): WP_REST_Response|WP_Error {
$params = $request->get_json_params();

if ( ! is_array( $params ) ) {
return new WP_Error( 'bf_invalid_payload', __( 'Invalid payload.', 'wp-betterforms' ), [ 'status' => 400 ] );
}

$form_id = Form_Repository::save_form( $params );
$form    = Form_Repository::get_form( $form_id );

return new WP_REST_Response( [ 'form' => $form ] );
}

public function submit_form( WP_REST_Request $request ): WP_REST_Response|WP_Error {
$nonce = $request->get_header( 'X-WP-Nonce' );
if ( ! wp_verify_nonce( $nonce ?? '', 'wp_rest' ) ) {
return new WP_Error( 'bf_invalid_nonce', __( 'Security check failed.', 'wp-betterforms' ), [ 'status' => 403 ] );
}

$form_id = (int) $request->get_param( 'form_id' );
$form    = Form_Repository::get_form( $form_id );

if ( ! $form ) {
return new WP_Error( 'bf_form_not_found', __( 'Form not found.', 'wp-betterforms' ), [ 'status' => 404 ] );
}

$data       = $request->get_json_params();
$data       = is_array( $data ) ? $data : [];

$honeypot_error = $this->check_honeypot( $data, $form, $request );
if ( is_wp_error( $honeypot_error ) ) {
return $honeypot_error;
}

$rate_limit = $this->enforce_rate_limit( $request, $form );
if ( is_wp_error( $rate_limit ) ) {
return $rate_limit;
}

$captcha = $this->verify_captcha( $data, $form, $request );
if ( is_wp_error( $captcha ) ) {
return $captcha;
}

$spam_check = $this->check_spam_filters( $data, $form, $request );
if ( is_wp_error( $spam_check ) ) {
return $spam_check;
}

$validation = Validator::validate_submission( $form, $data );

if ( is_wp_error( $validation ) ) {
return $validation;
}

$entry_id = Renderer::store_submission( $form, $data ?? [] );

Mailer::queue_submission_email( $form, $data ?? [], $entry_id );

return new WP_REST_Response(
[
'success'  => true,
'entry_id' => $entry_id,
'message'  => __( 'Form submitted successfully.', 'wp-betterforms' ),
]
);
}

public function list_email_failures(): WP_REST_Response {
global $wpdb;

$table = $wpdb->prefix . 'bf_email_queue';
$rows  = $wpdb->get_results( "SELECT id, form_id, to_email, subject, attempts, last_error, updated_at FROM $table WHERE status = 'failed' ORDER BY updated_at DESC LIMIT 20", ARRAY_A );

$failures = array_map(
static function ( array $row ): array {
return [
'id'         => (int) $row['id'],
'form_id'    => (int) $row['form_id'],
'to_email'   => $row['to_email'],
'subject'    => $row['subject'],
'attempts'   => (int) $row['attempts'],
'last_error' => $row['last_error'],
'updated_at' => $row['updated_at'],
];
},
$rows
);

return new WP_REST_Response( [ 'failures' => $failures ] );
}

public function resend_email( WP_REST_Request $request ): WP_REST_Response|WP_Error {
global $wpdb;

$queue_id = (int) $request->get_param( 'queue_id' );
$table    = $wpdb->prefix . 'bf_email_queue';
$record   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $queue_id ), ARRAY_A );

if ( ! $record ) {
return new WP_Error( 'bf_queue_not_found', __( 'Queue item not found.', 'wp-betterforms' ), [ 'status' => 404 ] );
}

$wpdb->update(
$table,
[
'status'     => 'pending',
'updated_at' => current_time( 'mysql' ),
'last_error' => null,
],
[
'id' => $queue_id,
]
);

Logger::log( 'email', 'warning', 'Email queue item requeued from dashboard.', [ 'queue_id' => $queue_id ], (int) ( $record['form_id'] ?? 0 ) );

return new WP_REST_Response( [ 'success' => true ] );
}

public function send_test_email( WP_REST_Request $request ): WP_REST_Response|WP_Error {
$params = $request->get_json_params();
$to     = sanitize_email( (string) ( $params['to'] ?? get_option( 'admin_email' ) ) );

if ( ! is_email( $to ) ) {
return new WP_Error( 'bf_invalid_email', __( 'Provide a valid email address for the test.', 'wp-betterforms' ), [ 'status' => 400 ] );
}

$subject = sanitize_text_field( $params['subject'] ?? __( 'WP Better Forms Test Email', 'wp-betterforms' ) );
$body    = wp_kses_post( $params['body'] ?? __( 'This is a test email from WP Better Forms.', 'wp-betterforms' ) );

Mailer::enqueue_email(
[
'form_id'   => 0,
'entry_id'  => null,
'to_email'  => $to,
'subject'   => $subject,
'body_html' => $body,
]
);

Logger::log( 'email', 'info', 'Dashboard test email queued.', [ 'to' => $to ] );

return new WP_REST_Response( [ 'success' => true ] );
}

private function check_honeypot( array $data, array $form, WP_REST_Request $request ): true|WP_Error {
if ( ! empty( $data['bf_hp'] ) ) {
Logger::log( 'spam', 'warning', 'Honeypot field triggered.', [ 'form_id' => $form['id'] ?? null ] );

return new WP_Error( 'bf_honeypot', __( 'Spam protection triggered.', 'wp-betterforms' ), [ 'status' => 400 ] );
}

$elapsed_seconds = null;

if ( isset( $data['bf_elapsed'] ) ) {
$elapsed_seconds = max( 0, (float) $data['bf_elapsed'] );
} elseif ( isset( $data['bf_rendered_at'] ) ) {
$elapsed_seconds = max( 0, time() - (int) $data['bf_rendered_at'] );
}

$min_delay = (float) apply_filters( 'wp_betterforms/honeypot_min_time', 2.0, $form, $request );

if ( null !== $elapsed_seconds && $elapsed_seconds < $min_delay ) {
Logger::log(
'spam',
'warning',
'Timed honeypot threshold not met.',
[
'form_id'  => $form['id'] ?? null,
'elapsed'  => $elapsed_seconds,
'required' => $min_delay,
]
);

return new WP_Error( 'bf_fast_submission', __( 'Form submitted too quickly.', 'wp-betterforms' ), [ 'status' => 400 ] );
}

return true;
}

private function enforce_rate_limit( WP_REST_Request $request, array $form ): true|WP_Error {
$ip = $this->get_client_ip( $request );

if ( ! $ip ) {
return true;
}

$window = (int) apply_filters( 'wp_betterforms/rate_limit_window', MINUTE_IN_SECONDS * 5, $form, $request );
$limit  = (int) apply_filters( 'wp_betterforms/rate_limit_max', 10, $form, $request );
$key    = 'bf_rl_' . md5( (string) ( $form['id'] ?? 0 ) . '|' . $ip );
$count  = (int) get_transient( $key );

if ( $count >= $limit ) {
Logger::log( 'spam', 'warning', 'Rate limit exceeded for submission.', [ 'ip' => $ip, 'form_id' => $form['id'] ?? null ] );

return new WP_Error( 'bf_rate_limited', __( 'Too many submissions. Please try again later.', 'wp-betterforms' ), [ 'status' => 429 ] );
}

set_transient( $key, $count + 1, $window );

return true;
}

private function verify_captcha( array $data, array $form, WP_REST_Request $request ): true|WP_Error {
$captcha_meta = $data['_captcha'] ?? [];
$provider     = $captcha_meta['provider'] ?? $form['integrations']['captcha']['provider'] ?? null;
$token        = $captcha_meta['token'] ?? null;

if ( ! $provider || ! $token ) {
return true;
}

$filter  = 'wp_betterforms/verify_' . strtolower( (string) $provider );
$verified = apply_filters( $filter, null, $token, $request, $form, $data );

if ( $verified instanceof WP_Error ) {
Logger::log( 'spam', 'warning', 'Captcha validation failed with error.', [ 'form_id' => $form['id'] ?? null, 'provider' => $provider ] );

return $verified;
}

if ( false === $verified ) {
Logger::log( 'spam', 'warning', 'Captcha validation rejected submission.', [ 'form_id' => $form['id'] ?? null, 'provider' => $provider ] );

return new WP_Error( 'bf_captcha_failed', __( 'Captcha validation failed. Please try again.', 'wp-betterforms' ), [ 'status' => 400 ] );
}

return true;
}

private function check_spam_filters( array $data, array $form, WP_REST_Request $request ): true|WP_Error {
$akismet_payload = apply_filters(
'wp_betterforms/akismet_payload',
[
'comment_content' => wp_json_encode( $data ),
'user_ip'         => $this->get_client_ip( $request ),
'user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? '',
],
$form,
$data,
$request
);

$spam_result = apply_filters( 'wp_betterforms/check_spam', null, $akismet_payload, $form, $data, $request );

if ( $spam_result instanceof WP_Error ) {
Logger::log( 'spam', 'warning', 'Spam filter blocked submission.', [ 'form_id' => $form['id'] ?? null ] );

return $spam_result;
}

if ( true === $spam_result || 'spam' === $spam_result ) {
Logger::log( 'spam', 'warning', 'Spam filter flagged submission.', [ 'form_id' => $form['id'] ?? null ] );

return new WP_Error( 'bf_spam_detected', __( 'Submission was flagged as spam.', 'wp-betterforms' ), [ 'status' => 400 ] );
}

return true;
}

private function get_client_ip( WP_REST_Request $request ): ?string {
$headers = [ 'X-Forwarded-For', 'CF-Connecting-IP', 'X-Real-IP' ];

foreach ( $headers as $header ) {
$value = $request->get_header( $header );
if ( $value ) {
$parts = explode( ',', $value );

return trim( $parts[0] );
}
}

return $_SERVER['REMOTE_ADDR'] ?? null;
}
}
