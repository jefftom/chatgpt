<?php
/**
 * PHPUnit bootstrap.
 */

if ( ! defined( 'ABSPATH' ) ) {
define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-validator.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-logger.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-mailer.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-scheduler.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-admin-notices.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-rest.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-form-repository.php';

define( 'WP_BETTERFORMS_TESTING', true );

global $wp_filter;
if ( ! is_array( $wp_filter ) ) {
$wp_filter = [];
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'ARRAY_A' ) ) {
define( 'ARRAY_A', 'ARRAY_A' );
}

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

if ( ! function_exists( '_x' ) ) {
function _x( string $text ): string {
return $text;
}
}

if ( ! function_exists( 'esc_html__' ) ) {
function esc_html__( string $text ): string {
return $text;
}
}

if ( ! function_exists( 'esc_html' ) ) {
function esc_html( string $text ): string {
return $text;
}
}

if ( ! function_exists( 'esc_attr' ) ) {
function esc_attr( string $text ): string {
return $text;
}
}

if ( ! function_exists( 'esc_url' ) ) {
function esc_url( string $url ): string {
return $url;
}
}

if ( ! function_exists( 'esc_js' ) ) {
function esc_js( string $text ): string {
return $text;
}
}

if ( ! function_exists( 'admin_url' ) ) {
function admin_url( string $path = '' ): string {
return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
}
}

if ( ! function_exists( 'is_email' ) ) {
function is_email( $email ): bool {
return false !== filter_var( $email, FILTER_VALIDATE_EMAIL );
}
}

if ( ! function_exists( 'sanitize_email' ) ) {
function sanitize_email( string $email ): string {
return strtolower( trim( $email ) );
}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
function sanitize_text_field( $text ): string {
return is_scalar( $text ) ? trim( (string) $text ) : '';
}
}

if ( ! function_exists( 'sanitize_title' ) ) {
function sanitize_title( string $title ): string {
return strtolower( preg_replace( '/[^a-z0-9-]/', '-', $title ) ?? '' );
}
}

if ( ! function_exists( 'absint' ) ) {
function absint( $maybeint ): int {
return abs( (int) $maybeint );
}
}

if ( ! function_exists( 'sanitize_key' ) ) {
function sanitize_key( string $key ): string {
return strtolower( preg_replace( '/[^a-z0-9_]/', '', $key ) ?? '' );
}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
function wp_json_encode( $data ): string {
return json_encode( $data );
}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
function wp_kses_post( $content ) {
return $content;
}
}

if ( ! function_exists( 'maybe_serialize' ) ) {
function maybe_serialize( $data ) {
return is_array( $data ) || is_object( $data ) ? serialize( $data ) : $data;
}
}

if ( ! function_exists( 'maybe_unserialize' ) ) {
function maybe_unserialize( $data ) {
$unserialized = @unserialize( $data );
return false === $unserialized && 'b:0;' !== $data ? $data : $unserialized;
}
}

if ( ! function_exists( 'current_time' ) ) {
function current_time( string $type ): string {
if ( 'mysql' === $type ) {
return gmdate( 'Y-m-d H:i:s' );
}

return (string) time();
}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
function wp_create_nonce( string $action = '' ): string {
return 'nonce-' . $action;
}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
function wp_verify_nonce( string $nonce, string $action ): bool {
return $nonce === 'nonce-' . $action || 'valid' === $nonce;
}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
function get_current_user_id(): int {
return 1;
}
}

if ( ! function_exists( 'current_user_can' ) ) {
function current_user_can( string $cap ): bool {
return true;
}
}

if ( ! function_exists( 'is_wp_error' ) ) {
function is_wp_error( $thing ): bool {
return $thing instanceof WP_Error;
}
}

if ( ! function_exists( 'shortcode_atts' ) ) {
function shortcode_atts( array $pairs, array $atts ): array {
return array_merge( $pairs, $atts );
}
}

if ( ! function_exists( 'rest_url' ) ) {
function rest_url( string $path = '' ): string {
return 'https://example.com/wp-json/' . ltrim( $path, '/' );
}
}

if ( ! function_exists( 'get_option' ) ) {
function get_option( string $name, $default = false ) {
return $GLOBALS['bf_test_options'][ $name ] ?? ( $default ?: 'admin@example.com' );
}
}

if ( ! function_exists( 'update_option' ) ) {
function update_option( string $name, $value ): bool {
$GLOBALS['bf_test_options'][ $name ] = $value;

return true;
}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
function esc_url_raw( string $url ): string {
return $url;
}
}

if ( ! function_exists( 'wp_unique_id' ) ) {
function wp_unique_id( string $prefix = '' ): string {
static $i = 0;
$i++;
return $prefix . $i;
}
}

if ( ! function_exists( 'add_filter' ) ) {
function add_filter( string $tag, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
global $wp_filter;
$wp_filter[ $tag ][ $priority ][] = [ 'function' => $callback, 'accepted_args' => $accepted_args ];
}
}

if ( ! function_exists( 'add_action' ) ) {
function add_action( string $tag, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
add_filter( $tag, $callback, $priority, $accepted_args );
}
}

if ( ! function_exists( 'remove_all_filters' ) ) {
function remove_all_filters( string $tag ): void {
global $wp_filter;
unset( $wp_filter[ $tag ] );
}
}

if ( ! function_exists( 'apply_filters' ) ) {
function apply_filters( string $tag, $value, ...$args ) {
global $wp_filter;

if ( empty( $wp_filter[ $tag ] ) ) {
return $value;
}

ksort( $wp_filter[ $tag ] );

foreach ( $wp_filter[ $tag ] as $callbacks ) {
foreach ( $callbacks as $callback ) {
$function      = $callback['function'];
$accepted_args = $callback['accepted_args'];
$value         = $function( $value, ...array_slice( $args, 0, $accepted_args - 1 ) );
}
}

return $value;
}
}

if ( ! function_exists( 'do_action' ) ) {
function do_action( string $tag, ...$args ): void {
global $wp_filter;

if ( empty( $wp_filter[ $tag ] ) ) {
return;
}

ksort( $wp_filter[ $tag ] );

foreach ( $wp_filter[ $tag ] as $callbacks ) {
foreach ( $callbacks as $callback ) {
$function      = $callback['function'];
$accepted_args = $callback['accepted_args'];
$function( ...array_slice( $args, 0, $accepted_args ) );
}
}
}
}

if ( ! function_exists( 'wp_add_dashboard_widget' ) ) {
function wp_add_dashboard_widget( string $widget_id, string $title, callable $callback ): void {
// no-op for tests
}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
function wp_nonce_field() {}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
function wp_enqueue_script() {}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
function wp_enqueue_style() {}
}

if ( ! function_exists( 'wp_localize_script' ) ) {
function wp_localize_script() {}
}

if ( ! function_exists( 'get_transient' ) ) {
function get_transient( string $key ) {
return $GLOBALS['bf_test_transients'][ $key ]['value'] ?? false;
}
}

if ( ! function_exists( 'set_transient' ) ) {
function set_transient( string $key, $value, int $expiration ): void {
$GLOBALS['bf_test_transients'][ $key ] = [ 'value' => $value, 'expires' => time() + $expiration ];
}
}

if ( ! function_exists( 'delete_transient' ) ) {
function delete_transient( string $key ): void {
unset( $GLOBALS['bf_test_transients'][ $key ] );
}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
function wp_next_scheduled( string $hook ) {
return $GLOBALS['bf_test_cron'][ $hook ] ?? false;
}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
function wp_schedule_event( int $timestamp, string $recurrence, string $hook ): void {
$GLOBALS['bf_test_cron'][ $hook ] = $timestamp;
}
}

if ( ! function_exists( 'wp_unschedule_event' ) ) {
function wp_unschedule_event( $timestamp, string $hook ): void {
unset( $GLOBALS['bf_test_cron'][ $hook ] );
}
}

if ( ! function_exists( 'wp_mail' ) ) {
function wp_mail( string $to, string $subject, string $message, array $headers = [] ) {
if ( ! isset( $GLOBALS['bf_test_wp_mail_results'] ) ) {
return true;
}

$result = array_shift( $GLOBALS['bf_test_wp_mail_results'] );

return null === $result ? true : (bool) $result;
}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
function wp_remote_post() {
return [ 'body' => '{}', 'response' => [ 'code' => 200 ] ];
}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
class WP_REST_Request {
private array $params = [];
private array $json_params = [];
private array $headers = [];

public function __construct( array $params = [], array $json_params = [], array $headers = [] ) {
$this->params      = $params;
$this->json_params = $json_params;
$this->headers     = $headers;
}

public function get_param( string $key ) {
return $this->params[ $key ] ?? null;
}

public function set_param( string $key, $value ): void {
$this->params[ $key ] = $value;
}

public function get_json_params(): array {
return $this->json_params;
}

public function set_json_params( array $params ): void {
$this->json_params = $params;
}

public function get_header( string $key ): ?string {
return $this->headers[ $key ] ?? null;
}

public function set_header( string $key, string $value ): void {
$this->headers[ $key ] = $value;
}
}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
class WP_REST_Response {
private array $data;

public function __construct( array $data ) {
$this->data = $data;
}

public function get_data(): array {
return $this->data;
}
}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
class WP_REST_Server {
public const READABLE = 'GET';
public const CREATABLE = 'POST';
}
}

if ( ! function_exists( '__return_true' ) ) {
function __return_true(): bool {
return true;
}
}

if ( ! function_exists( 'wp_add_inline_script' ) ) {
function wp_add_inline_script() {}
}

if ( ! function_exists( 'wp_add_inline_style' ) ) {
function wp_add_inline_style() {}
}

if ( ! function_exists( 'wp_dashboard_setup' ) ) {
function wp_dashboard_setup() {}
}

if ( ! class_exists( 'wpdb' ) ) {
class wpdb {
public string $prefix = 'wp_';
public int $insert_id = 0;
public array $tables = [];
private array $auto_ids = [];

public function prepare( string $query, ...$args ): string {
$query = str_replace( [ '%s', '%d', '%f' ], [ "'%s'", '%d', '%F' ], $query );

return vsprintf( $query, $args );
}

public function insert( string $table, array $data ): void {
$table_key = $this->normalize_table( $table );

if ( ! isset( $this->auto_ids[ $table_key ] ) ) {
$this->auto_ids[ $table_key ] = 1;
}

if ( empty( $data['id'] ) ) {
$data['id'] = $this->auto_ids[ $table_key ]++;
}

$this->insert_id            = $data['id'];
$this->tables[ $table_key ][] = $data;
}

public function update( string $table, array $data, array $where ): void {
$table_key = $this->normalize_table( $table );

foreach ( $this->tables[ $table_key ] ?? [] as $index => $row ) {
$match = true;
foreach ( $where as $key => $value ) {
if ( ( $row[ $key ] ?? null ) != $value ) {
$match = false;
break;
}
}

if ( $match ) {
$this->tables[ $table_key ][ $index ] = array_merge( $row, $data );
}
}
}

public function get_results( string $query, string $output = ARRAY_A ): array {
$table_key = $this->parse_table( $query );
$rows      = $this->tables[ $table_key ] ?? [];

if ( preg_match( "/WHERE\\s+status\\s*=\\s*'([^']+)'/i", $query, $status_match ) ) {
$status = $status_match[1];
$rows   = array_values(
array_filter(
$rows,
static fn( array $row ): bool => ( $row['status'] ?? null ) === $status
)
);
}

if ( str_contains( strtolower( $query ), 'order by created_at asc' ) ) {
usort(
$rows,
static fn( array $a, array $b ): int => strcmp( $a['created_at'] ?? '', $b['created_at'] ?? '' )
);
}

if ( str_contains( strtolower( $query ), 'order by updated_at desc' ) ) {
usort(
$rows,
static fn( array $a, array $b ): int => strcmp( $b['updated_at'] ?? '', $a['updated_at'] ?? '' )
);
}

if ( preg_match( '/limit\s+(\d+)/i', $query, $limit_match ) ) {
$rows = array_slice( $rows, 0, (int) $limit_match[1] );
}

return $rows;
}

public function get_row( string $query, string $output = ARRAY_A ) {
$table_key = $this->parse_table( $query );
$rows      = $this->tables[ $table_key ] ?? [];

if ( preg_match( "/where\\s+id\\s*=\\s*(\d+)/i", $query, $id_match ) ) {
$id = (int) $id_match[1];

foreach ( $rows as $row ) {
if ( (int) ( $row['id'] ?? 0 ) === $id ) {
return $row;
}
}

return null;
}

return $rows[0] ?? null;
}

public function get_var( string $query ) {
$table_key = $this->parse_table( $query );
$rows      = $this->tables[ $table_key ] ?? [];

if ( preg_match( "/type\\s*=\\s*'([^']+)'/i", $query, $type_match ) ) {
$type = $type_match[1];
$rows = array_filter(
$rows,
static fn( array $row ): bool => ( $row['type'] ?? null ) === $type
);
}

if ( preg_match( "/level\\s*=\\s*'([^']+)'/i", $query, $level_match ) ) {
$level = $level_match[1];
$rows  = array_filter(
$rows,
static fn( array $row ): bool => ( $row['level'] ?? null ) === $level
);
}

if ( preg_match( "/created_at >= date_sub\( now\(\), interval (\d+) hour \)/i", strtolower( $query ), $hours_match ) ) {
$threshold = time() - ( (int) $hours_match[1] * HOUR_IN_SECONDS );
$rows      = array_filter(
$rows,
static function ( array $row ) use ( $threshold ): bool {
return strtotime( $row['created_at'] ?? '' ) >= $threshold;
}
);
}

return count( $rows );
}

private function parse_table( string $query ): string {
if ( preg_match( '/from\s+([a-z0-9_]+)/i', $query, $matches ) ) {
return $this->normalize_table( $matches[1] );
}

return $this->normalize_table( 'wp_bf_forms' );
}

private function normalize_table( string $table ): string {
return str_contains( $table, $this->prefix ) ? $table : $table;
}
}
}

if ( ! isset( $GLOBALS['wpdb'] ) ) {
$GLOBALS['wpdb'] = new wpdb();
}
