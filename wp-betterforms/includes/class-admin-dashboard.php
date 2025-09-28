<?php
/**
 * Dashboard widget for queue visibility.
 */

namespace WP_BetterForms\Admin;

use WP_BetterForms\Rest\Rest_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin_Dashboard
 */
final class Admin_Dashboard {
private static ?self $instance = null;
private static bool $script_rendered = false;

private function __construct() {
add_action( 'wp_dashboard_setup', [ $this, 'register_widget' ] );
}

public static function get_instance(): self {
if ( null === self::$instance ) {
self::$instance = new self();
}

return self::$instance;
}

public function register_widget(): void {
if ( ! current_user_can( 'manage_options' ) ) {
return;
}

wp_add_dashboard_widget(
'wp_betterforms_email_queue',
__( 'Better Forms Email Queue', 'wp-betterforms' ),
[ $this, 'render_widget' ]
);
}

public function render_widget(): void {
$failures = $this->get_failures();
$rest_base = rest_url( Rest_Controller::NAMESPACE . '/email-queue/failures' );
$resend   = rest_url( Rest_Controller::NAMESPACE . '/email-queue/__id__/resend' );
$test     = rest_url( Rest_Controller::NAMESPACE . '/email-queue/test' );
$nonce    = wp_create_nonce( 'wp_rest' );

echo '<div id="bf-email-queue" class="bf-email-queue-widget" data-endpoint="' . esc_attr( $rest_base ) . '" data-resend="' . esc_attr( $resend ) . '" data-test="' . esc_attr( $test ) . '" data-nonce="' . esc_attr( $nonce ) . '">';

echo '<p class="bf-email-queue-widget__intro">' . esc_html__( 'Monitor recent email queue failures and trigger retries directly from the dashboard.', 'wp-betterforms' ) . '</p>';

echo '<div class="bf-email-queue-widget__actions">';
echo '<label class="screen-reader-text" for="bf-email-test-address">' . esc_html__( 'Send test email to', 'wp-betterforms' ) . '</label>';
echo '<input type="email" id="bf-email-test-address" placeholder="' . esc_attr( __( 'you@example.com', 'wp-betterforms' ) ) . '" />';
echo '<button type="button" class="button bf-email-queue-widget__test" data-action="test">' . esc_html__( 'Send Test Email', 'wp-betterforms' ) . '</button>';
echo '</div>';

echo '<div class="bf-email-queue-widget__list" data-role="list">';
if ( empty( $failures ) ) {
    echo '<p class="bf-email-queue-widget__empty">' . esc_html__( 'No recent failures detected.', 'wp-betterforms' ) . '</p>';
} else {
    echo $this->render_failures_table( $failures );
}
echo '</div>';

echo '</div>';

if ( ! self::$script_rendered ) {
self::$script_rendered = true;
$this->render_widget_script();
}
}

private function render_failures_table( array $failures ): string {
$rows = array_map(
static function ( array $failure ): string {
$retry = '<button type="button" class="button-link" data-action="resend" data-id="' . esc_attr( (string) $failure['id'] ) . '">' . esc_html__( 'Retry', 'wp-betterforms' ) . '</button>';
$details = esc_html( sprintf( '%s (%s)', $failure['to_email'], $failure['subject'] ) );
$error = esc_html( $failure['last_error'] ?? __( 'Unknown error', 'wp-betterforms' ) );
$attempts = esc_html( (string) $failure['attempts'] );
$time = esc_html( $failure['updated_at'] );

return '<tr><td>' . $details . '</td><td>' . $error . '</td><td>' . $attempts . '</td><td>' . $time . '</td><td>' . $retry . '</td></tr>';
},
$failures
);

$header = '<tr><th>' . esc_html__( 'Email', 'wp-betterforms' ) . '</th><th>' . esc_html__( 'Last Error', 'wp-betterforms' ) . '</th><th>' . esc_html__( 'Attempts', 'wp-betterforms' ) . '</th><th>' . esc_html__( 'Updated', 'wp-betterforms' ) . '</th><th>' . esc_html__( 'Actions', 'wp-betterforms' ) . '</th></tr>';

return '<table class="widefat fixed"><thead>' . $header . '</thead><tbody>' . implode( '', $rows ) . '</tbody></table>';
}

private function render_widget_script(): void {
$labels = [
'empty'    => esc_js( __( 'No recent failures detected.', 'wp-betterforms' ) ),
'retry'    => esc_js( __( 'Retry', 'wp-betterforms' ) ),
'unknown'  => esc_js( __( 'Unknown error', 'wp-betterforms' ) ),
'email'    => esc_js( __( 'Email', 'wp-betterforms' ) ),
'error'    => esc_js( __( 'Last Error', 'wp-betterforms' ) ),
'attempts' => esc_js( __( 'Attempts', 'wp-betterforms' ) ),
'updated'  => esc_js( __( 'Updated', 'wp-betterforms' ) ),
'actions'  => esc_js( __( 'Actions', 'wp-betterforms' ) ),
'failed'   => esc_js( __( 'Unable to load failures.', 'wp-betterforms' ) ),
];

$labels_json = wp_json_encode( $labels );

$script  = '<script>(function(){';
$script .= 'const widget=document.getElementById("bf-email-queue");';
$script .= 'if(!widget){return;}';
$script .= 'const listEl=widget.querySelector("[data-role=\\"list\\"]");';
$script .= 'const nonce=widget.dataset.nonce;';
$script .= 'const endpoints={list:widget.dataset.endpoint,resend:widget.dataset.resend,test:widget.dataset.test};';
$script .= 'const labels=' . $labels_json . ';';
$script .= 'const renderEmpty=function(){listEl.innerHTML="<p class=\\"bf-email-queue-widget__empty\\">"+labels.empty+"</p>";};';
$script .= 'const fetchFailures=function(){fetch(endpoints.list,{headers:{"X-WP-Nonce":nonce}})';
$script .= '.then(function(response){return response.json();})';
$script .= '.then(function(payload){if(!payload||!Array.isArray(payload.failures)||!payload.failures.length){renderEmpty();return;}';
$script .= 'const rows=payload.failures.map(function(item){';
$script .= 'const retry="<button type=\\"button\\" class=\\"button-link\\" data-action=\\"resend\\" data-id=\\""+item.id+"\\">"+labels.retry+"</button>";';
$script .= 'const lastError=item.last_error||labels.unknown;';
$script .= 'return "<tr><td>"+item.to_email+" ("+item.subject+")</td><td>"+lastError+"</td><td>"+item.attempts+"</td><td>"+item.updated_at+"</td><td>"+retry+"</td></tr>";';
$script .= '}).join("" );';
$script .= 'listEl.innerHTML="<table class=\\"widefat fixed\\"><thead><tr><th>"+labels.email+"</th><th>"+labels.error+"</th><th>"+labels.attempts+"</th><th>"+labels.updated+"</th><th>"+labels.actions+"</th></tr></thead><tbody>"+rows+"</tbody></table>";';
$script .= '})';
$script .= '.catch(function(){listEl.innerHTML="<p class=\\"bf-email-queue-widget__empty\\">"+labels.failed+"</p>";});};';
$script .= 'widget.addEventListener("click",function(event){const target=event.target.closest("button[data-action]");if(!target){return;}';
$script .= 'if(target.dataset.action==="test"){const emailInput=widget.querySelector("#bf-email-test-address");const email=emailInput?emailInput.value:"";fetch(endpoints.test,{method:"POST",headers:{"Content-Type":"application/json","X-WP-Nonce":nonce},body:JSON.stringify({to:email})}).then(fetchFailures);return;}';
$script .= 'if(target.dataset.action==="resend"){const queueId=target.dataset.id;if(!queueId){return;}const endpoint=endpoints.resend.replace("__id__",queueId);fetch(endpoint,{method:"POST",headers:{"X-WP-Nonce":nonce}}).then(fetchFailures);}});';
$script .= 'fetchFailures();})();</script>';

echo $script;
}

private function get_failures(): array {
global $wpdb;

$table = $wpdb->prefix . 'bf_email_queue';
$rows  = $wpdb->get_results( "SELECT id, form_id, to_email, subject, last_error, attempts, updated_at FROM $table WHERE status = 'failed' ORDER BY updated_at DESC LIMIT 5", ARRAY_A );

return array_map(
static function ( array $row ): array {
return [
'id'         => (int) $row['id'],
'form_id'    => (int) ( $row['form_id'] ?? 0 ),
'to_email'   => $row['to_email'],
'subject'    => $row['subject'],
'last_error' => $row['last_error'],
'attempts'   => (int) $row['attempts'],
'updated_at' => $row['updated_at'],
];
},
$rows
);
}
}
