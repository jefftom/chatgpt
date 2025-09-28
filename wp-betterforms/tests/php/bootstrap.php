<?php
/**
 * PHPUnit bootstrap.
 */

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-validator.php';

define( 'WP_BETTERFORMS_TESTING', true );

if ( ! class_exists( 'WP_Error' ) ) {
class WP_Error {
private string $code;
private string $message;
private array $data;

public function __construct( string $code = '', string $message = '', $data = null ) {
$this->code    = $code;
$this->message = $message;
$this->data    = (array) $data;
}

public function get_error_code(): string {
return $this->code;
}

public function get_error_message(): string {
return $this->message;
}

public function get_error_data(): array {
return $this->data;
}
}
}

if ( ! function_exists( '__' ) ) {
function __( string $text ): string {
return $text;
}
}

if ( ! function_exists( 'esc_html__' ) ) {
function esc_html__( string $text ): string {
return $text;
}
}

if ( ! function_exists( 'is_email' ) ) {
function is_email( $email ): bool {
return false !== filter_var( $email, FILTER_VALIDATE_EMAIL );
}
}

if ( ! function_exists( 'apply_filters' ) ) {
function apply_filters( string $tag, $value ) {
return $value;
}
}
