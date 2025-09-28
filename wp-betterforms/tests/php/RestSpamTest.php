<?php

use PHPUnit\Framework\TestCase;
use WP_BetterForms\Rest\Rest_Controller;

final class RestSpamTest extends TestCase {
protected function setUp(): void {
parent::setUp();
remove_all_filters( 'wp_betterforms/honeypot_min_time' );
remove_all_filters( 'wp_betterforms/rate_limit_window' );
remove_all_filters( 'wp_betterforms/rate_limit_max' );
remove_all_filters( 'wp_betterforms/check_spam' );
$GLOBALS['bf_test_transients'] = [];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

private function seed_form(): void {
$schema = [
'fields' => [
[
'key'      => 'email',
'label'    => 'Email',
'type'     => 'email',
'required' => true,
],
],
];

$wpdb = new wpdb();
$wpdb->tables[ $wpdb->prefix . 'bf_forms' ] = [
[
'id'          => 1,
'title'       => 'Test',
'slug'        => 'test',
'schema_json' => json_encode( $schema ),
'styles_json' => '{}',
],
];
$wpdb->tables[ $wpdb->prefix . 'bf_email_queue' ] = [];
$wpdb->tables[ $wpdb->prefix . 'bf_logs' ]        = [];
$GLOBALS['wpdb'] = $wpdb;
}

private function make_request( array $data ): WP_Error|WP_REST_Response {
$request = new WP_REST_Request();
$request->set_param( 'form_id', 1 );
$request->set_header( 'X-WP-Nonce', 'nonce-wp_rest' );
$request->set_json_params( $data );

return Rest_Controller::get_instance()->submit_form( $request );
}

public function test_honeypot_blocks_submission(): void {
$this->seed_form();

$result = $this->make_request( [
'bf_hp'         => 'bot',
'bf_rendered_at' => time() - 10,
'email'         => 'human@example.com',
] );

$this->assertInstanceOf( WP_Error::class, $result );
$this->assertSame( 'bf_honeypot', $result->get_error_code() );
}

public function test_rate_limit_blocks_after_threshold(): void {
$this->seed_form();
add_filter( 'wp_betterforms/rate_limit_max', static fn() => 1 );
set_transient( 'bf_rl_' . md5( '1|127.0.0.1' ), 1, 300 );

$result = $this->make_request( [
'bf_rendered_at' => time() - 10,
'email'         => 'human@example.com',
] );

$this->assertInstanceOf( WP_Error::class, $result );
$this->assertSame( 'bf_rate_limited', $result->get_error_code() );
}

public function test_spam_filter_blocks_submission(): void {
$this->seed_form();
add_filter(
'wp_betterforms/check_spam',
static fn() => true
);

$result = $this->make_request( [
'bf_rendered_at' => time() - 10,
'email'         => 'human@example.com',
] );

$this->assertInstanceOf( WP_Error::class, $result );
$this->assertSame( 'bf_spam_detected', $result->get_error_code() );
}
}
