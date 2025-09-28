<?php
/**
 * Submission validation.
 */

namespace WP_BetterForms\Validation;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Class Validator
 */
final class Validator {
/**
 * Validate submission payload.
 */
public static function validate_submission( array $form, array $data ): true|WP_Error {
$schema = $form['schema'] ?? [];
$errors = [];

foreach ( $schema['fields'] ?? [] as $field ) {
$key      = $field['key'] ?? '';
$required = ! empty( $field['required'] );
$type     = $field['type'] ?? 'text';

$value = $data[ $key ] ?? null;

if ( $required && ( null === $value || '' === $value ) ) {
$errors[ $key ] = $field['label'] ?? __( 'This field is required.', 'wp-betterforms' );
continue;
}

if ( null === $value || '' === $value ) {
continue;
}

switch ( $type ) {
case 'email':
if ( ! is_email( $value ) ) {
$errors[ $key ] = __( 'Enter a valid email address.', 'wp-betterforms' );
}
break;
case 'number':
if ( ! is_numeric( $value ) ) {
$errors[ $key ] = __( 'Enter a valid number.', 'wp-betterforms' );
}
break;
case 'phone':
if ( ! preg_match( '/^[0-9\-\+\s\(\)]+$/', (string) $value ) ) {
$errors[ $key ] = __( 'Enter a valid phone number.', 'wp-betterforms' );
}
break;
default:
// Extend via filters.
$filtered_error = apply_filters( 'wp_betterforms/validate_field', null, $field, $value, $data, $form );
if ( is_string( $filtered_error ) ) {
$errors[ $key ] = $filtered_error;
}
}
}

if ( ! empty( $errors ) ) {
return new WP_Error( 'bf_validation_failed', __( 'Validation failed.', 'wp-betterforms' ), [ 'errors' => $errors, 'status' => 422 ] );
}

return true;
}
}
