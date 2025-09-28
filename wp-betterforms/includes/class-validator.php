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
public static function validate_submission( array $form, array $data ): bool|WP_Error {
$schema = $form['schema'] ?? [];
$errors = [];

foreach ( $schema['fields'] ?? [] as $field ) {
self::validate_field( $errors, $field, $data, $form );
}

$errors = apply_filters( 'wp_betterforms/validation_errors', $errors, $form, $data );

if ( ! empty( $errors ) ) {
return new WP_Error(
'bf_validation_failed',
__( 'Validation failed.', 'wp-betterforms' ),
[
'status' => 422,
'errors' => $errors,
]
);
}

return true;
}

/**
 * Validate an individual field, including repeater children.
 */
private static function validate_field( array &$errors, array $field, array $submission, array $form, string $path = '' ): void {
$key      = $field['key'] ?? '';
$type     = $field['type'] ?? 'text';
$path     = $path ?: $key;
$visible  = self::field_is_visible( $field, $submission );
$required = ! empty( $field['required'] );

if ( 'repeater' === $type ) {
self::validate_repeater_field( $errors, $field, $submission, $form, $path, $visible );

return;
}

$value = $submission[ $key ] ?? null;

if ( ! $visible ) {
return;
}

if ( $required && self::is_empty( $value ) ) {
self::add_error(
$errors,
$path,
'required',
$field['label'] ?? __( 'This field is required.', 'wp-betterforms' )
);

return;
}

if ( self::is_empty( $value ) ) {
return;
}

switch ( $type ) {
case 'email':
if ( ! is_email( $value ) ) {
self::add_error( $errors, $path, 'invalid_email', __( 'Enter a valid email address.', 'wp-betterforms' ) );
}
break;
case 'number':
if ( ! is_numeric( $value ) ) {
self::add_error( $errors, $path, 'invalid_number', __( 'Enter a valid number.', 'wp-betterforms' ) );
}
break;
case 'phone':
if ( ! preg_match( '/^[0-9\-\+\s\(\)]+$/', (string) $value ) ) {
self::add_error( $errors, $path, 'invalid_phone', __( 'Enter a valid phone number.', 'wp-betterforms' ) );
}
break;
}

self::validate_inventory( $errors, $field, $value, $submission, $form, $path );
self::validate_calculation( $errors, $field, $value, $submission, $form, $path );
self::validate_unique_id( $errors, $field, $value, $submission, $form, $path );

$filtered_error = apply_filters( 'wp_betterforms/validate_field', null, $field, $value, $submission, $form, $path );

if ( is_wp_error( $filtered_error ) ) {
self::add_error( $errors, $path, $filtered_error->get_error_code(), $filtered_error->get_error_message(), $filtered_error->get_error_data() );
} elseif ( is_array( $filtered_error ) && isset( $filtered_error['message'], $filtered_error['code'] ) ) {
self::add_error( $errors, $path, (string) $filtered_error['code'], (string) $filtered_error['message'], (array) ( $filtered_error['meta'] ?? [] ) );
} elseif ( is_string( $filtered_error ) ) {
self::add_error( $errors, $path, 'validation_failed', $filtered_error );
}
}

/**
 * Validate repeater field entries.
 */
private static function validate_repeater_field( array &$errors, array $field, array $submission, array $form, string $path, bool $visible ): void {
$key      = $field['key'] ?? '';
$rows     = $submission[ $key ] ?? [];
$required = ! empty( $field['required'] );

if ( ! $visible ) {
return;
}

if ( $required && ( ! is_array( $rows ) || empty( $rows ) ) ) {
self::add_error( $errors, $path, 'required', $field['label'] ?? __( 'This field is required.', 'wp-betterforms' ) );

return;
}

if ( empty( $rows ) ) {
return;
}

if ( ! is_array( $rows ) ) {
self::add_error( $errors, $path, 'invalid_repeater', __( 'Provide valid repeater data.', 'wp-betterforms' ) );

return;
}

foreach ( $rows as $index => $row ) {
$row_data = is_array( $row ) ? $row : [];

foreach ( $field['fields'] ?? [] as $child ) {
$child_key  = $child['key'] ?? '';
$child_path = implode(
 '.',
 array_filter(
 [ $path, (string) $index, $child_key ],
 static fn( $segment ): bool => '' !== $segment && null !== $segment
 )
);
self::validate_field( $errors, $child, $row_data, $form, $child_path );
}
}
}

/**
 * Determine if the field should run validation based on conditional logic.
 */
private static function field_is_visible( array $field, array $submission ): bool {
$conditions = $field['conditions'] ?? $field['logic'] ?? [];

if ( empty( $conditions ) || empty( $conditions['rules'] ) || empty( $conditions['enabled'] ) ) {
return true;
}

$type   = strtolower( $conditions['type'] ?? 'all' );
$action = strtolower( $conditions['action'] ?? 'show' );
$rules  = $conditions['rules'];

$results = array_map(
static function ( array $rule ) use ( $submission ): bool {
$field_key = $rule['field'] ?? '';
$operator  = strtolower( $rule['operator'] ?? 'equals' );
$expected  = $rule['value'] ?? '';
$actual    = $submission[ $field_key ] ?? null;

return self::evaluate_condition( $actual, $operator, $expected );
},
$rules
);

$matched = 'all' === $type ? ! in_array( false, $results, true ) : in_array( true, $results, true );

return 'show' === $action ? $matched : ! $matched;
}

/**
 * Evaluate a single conditional rule.
 */
private static function evaluate_condition( $actual, string $operator, $expected ): bool {
switch ( $operator ) {
case 'not_equals':
case '!=':
return $actual != $expected;
case 'contains':
return is_string( $actual ) && str_contains( $actual, (string) $expected );
case 'starts_with':
return is_string( $actual ) && str_starts_with( $actual, (string) $expected );
case 'ends_with':
return is_string( $actual ) && str_ends_with( $actual, (string) $expected );
case 'greater_than':
case '>':
return (float) $actual > (float) $expected;
case 'less_than':
case '<':
return (float) $actual < (float) $expected;
case 'in':
$expected_values = is_array( $expected ) ? $expected : explode( ',', (string) $expected );

return in_array( $actual, array_map( 'trim', $expected_values ), true );
case 'not_in':
$expected_values = is_array( $expected ) ? $expected : explode( ',', (string) $expected );

return ! in_array( $actual, array_map( 'trim', $expected_values ), true );
case 'empty':
return self::is_empty( $actual );
case 'not_empty':
return ! self::is_empty( $actual );
case 'equals':
case '=':
default:
return $actual == $expected;
}
}

/**
 * Validate inventory limits.
 */
private static function validate_inventory( array &$errors, array $field, $value, array $submission, array $form, string $path ): void {
$inventory = $field['inventory'] ?? [];

if ( empty( $inventory ) || empty( $inventory['enabled'] ) ) {
return;
}

$requested = is_array( $value ) ? count( $value ) : ( is_numeric( $value ) ? (int) $value : 1 );

$remaining = apply_filters( 'wp_betterforms/inventory_remaining', null, $field, $requested, $submission, $form );

if ( null === $remaining ) {
if ( isset( $inventory['remaining'] ) ) {
$remaining = (int) $inventory['remaining'];
} elseif ( isset( $inventory['limit'] ) ) {
$consumed  = (int) ( $inventory['consumed'] ?? 0 );
$remaining = (int) $inventory['limit'] - $consumed;
} else {
return;
}
}

if ( $requested > (int) $remaining ) {
self::add_error(
$errors,
$path,
'inventory_exhausted',
__( 'Not enough inventory remaining for this selection.', 'wp-betterforms' ),
[
'requested' => $requested,
'remaining' => (int) $remaining,
]
);
}
}

/**
 * Validate calculation fields against trusted expressions.
 */
private static function validate_calculation( array &$errors, array $field, $value, array $submission, array $form, string $path ): void {
$calculation = $field['calculation'] ?? [];

if ( empty( $calculation ) || empty( $calculation['enabled'] ) ) {
return;
}

$expected = self::evaluate_calculation( (string) ( $calculation['formula'] ?? '' ), $submission );

if ( null === $expected ) {
return;
}

$tolerance = isset( $calculation['tolerance'] ) ? (float) $calculation['tolerance'] : 0.01;

if ( ! is_numeric( $value ) || abs( (float) $value - $expected ) > $tolerance ) {
self::add_error(
$errors,
$path,
'calculation_mismatch',
__( 'Calculated value does not match expected result.', 'wp-betterforms' ),
[
'expected'  => $expected,
'provided'  => is_numeric( $value ) ? (float) $value : $value,
'tolerance' => $tolerance,
]
);
}
}

/**
 * Evaluate calculation expression safely.
 */
private static function evaluate_calculation( string $formula, array $submission ): ?float {
if ( '' === trim( $formula ) ) {
return null;
}

$expression = preg_replace_callback(
'~\{\{\s*([a-zA-Z0-9_\-\.]+)\s*\}\}~',
static function ( array $matches ) use ( $submission ): string {
$key   = $matches[1];
$value = $submission[ $key ] ?? 0;

return (string) ( is_numeric( $value ) ? (float) $value : 0 );
},
$formula
);

$expression = preg_replace( '/\s+/', '', (string) $expression );

if ( preg_match( '/[^0-9+\-*\/\.\(\)]/', $expression ) ) {
return null;
}

try {
// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_eval
$result = eval( 'return ' . $expression . ';' );
} catch ( \Throwable ) {
return null;
}

return is_numeric( $result ) ? (float) $result : null;
}

/**
 * Validate server-trusted unique identifiers.
 */
private static function validate_unique_id( array &$errors, array $field, $value, array $submission, array $form, string $path ): void {
$unique_config = $field['uniqueId'] ?? $field['unique_id'] ?? [];
$type          = $field['type'] ?? '';

if ( 'unique_id' === $type && self::is_empty( $value ) ) {
self::add_error( $errors, $path, 'required', __( 'Unique identifier missing.', 'wp-betterforms' ) );

return;
}

if ( empty( $unique_config ) && 'unique_id' !== $type ) {
return;
}

$expected = apply_filters( 'wp_betterforms/unique_id_expected', null, $field, $submission, $form );

if ( is_string( $expected ) && $expected !== '' ) {
if ( (string) $value !== $expected ) {
self::add_error( $errors, $path, 'unique_id_mismatch', __( 'Unique identifier mismatch.', 'wp-betterforms' ) );
}

return;
}

$mode = strtolower( $unique_config['mode'] ?? ( 'unique_id' === $type ? 'uuid' : '' ) );

switch ( $mode ) {
case 'uuid':
if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', (string) $value ) ) {
self::add_error( $errors, $path, 'invalid_uuid', __( 'Unique identifier must be a valid UUID.', 'wp-betterforms' ) );
}
break;
case 'prefix':
$prefix = (string) ( $unique_config['prefix'] ?? '' );
$length = (int) ( $unique_config['length'] ?? 8 );

if ( ! str_starts_with( (string) $value, $prefix ) || strlen( (string) $value ) < strlen( $prefix ) + $length ) {
self::add_error( $errors, $path, 'invalid_unique_id', __( 'Unique identifier is not in the expected format.', 'wp-betterforms' ), [ 'prefix' => $prefix, 'length' => $length ] );
}
break;
case 'hash':
if ( ! preg_match( '/^[0-9a-f]{32}$/i', (string) $value ) ) {
self::add_error( $errors, $path, 'invalid_hash', __( 'Unique identifier must be a valid hash.', 'wp-betterforms' ) );
}
break;
default:
if ( self::is_empty( $value ) ) {
self::add_error( $errors, $path, 'invalid_unique_id', __( 'Unique identifier is missing or malformed.', 'wp-betterforms' ) );
}
}
}

/**
 * Add a structured error entry.
 */
private static function add_error( array &$errors, string $path, string $code, string $message, array $meta = [] ): void {
if ( ! isset( $errors[ $path ] ) ) {
$errors[ $path ] = [];
}

$errors[ $path ][] = [
'field'   => $path,
'code'    => $code,
'message' => $message,
'meta'    => $meta,
];
}

/**
 * Determine if a value is considered empty for validation.
 */
private static function is_empty( $value ): bool {
if ( null === $value ) {
return true;
}

if ( is_string( $value ) ) {
return '' === trim( $value );
}

if ( is_array( $value ) ) {
return empty( $value );
}

return false;
}
}
