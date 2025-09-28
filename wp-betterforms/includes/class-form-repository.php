<?php
/**
 * Form persistence helpers.
 */

namespace WP_BetterForms;

defined( 'ABSPATH' ) || exit;

use wpdb;

/**
 * Class Form_Repository
 */
final class Form_Repository {
/**
 * Fetch a form by ID.
 */
public static function get_form( int $form_id ): ?array {
global $wpdb;

$table = $wpdb->prefix . 'bf_forms';
$form  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $form_id ), ARRAY_A );

if ( ! $form ) {
return null;
}

$form['schema'] = json_decode( (string) $form['schema_json'], true ) ?: [];
$form['styles'] = json_decode( (string) $form['styles_json'], true ) ?: [];

return $form;
}

/**
 * Save a form.
 */
public static function save_form( array $data ): int {
global $wpdb;

$table = $wpdb->prefix . 'bf_forms';

$payload = [
'title'       => sanitize_text_field( $data['title'] ?? __( 'Untitled form', 'wp-betterforms' ) ),
'slug'        => sanitize_title( $data['slug'] ?? ( $data['title'] ?? uniqid( 'form-' ) ) ),
'schema_json' => wp_json_encode( $data['schema'] ?? [] ),
'styles_json' => wp_json_encode( $data['styles'] ?? [] ),
'status'      => sanitize_text_field( $data['status'] ?? 'draft' ),
'updated_at'  => current_time( 'mysql' ),
];

$existing_id = absint( $data['id'] ?? 0 );

if ( $existing_id ) {
$wpdb->update( $table, $payload, [ 'id' => $existing_id ] );

return $existing_id;
}

$payload['created_at'] = current_time( 'mysql' );
$wpdb->insert( $table, $payload );

return (int) $wpdb->insert_id;
}

/**
 * List forms for admin.
 */
public static function list_forms(): array {
global $wpdb;

$table = $wpdb->prefix . 'bf_forms';

$rows = $wpdb->get_results( "SELECT id, title, slug, status, created_at, updated_at FROM $table ORDER BY updated_at DESC", ARRAY_A );

return array_map(
static fn( array $row ): array => [
'id'         => (int) $row['id'],
'title'      => $row['title'],
'slug'       => $row['slug'],
'status'     => $row['status'],
'created_at' => $row['created_at'],
'updated_at' => $row['updated_at'],
],
$rows
);
}
}
