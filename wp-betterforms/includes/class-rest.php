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
$validation = Validator::validate_submission( $form, is_array( $data ) ? $data : [] );

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
}
