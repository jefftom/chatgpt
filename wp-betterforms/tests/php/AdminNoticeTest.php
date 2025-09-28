<?php

use PHPUnit\Framework\TestCase;
use WP_BetterForms\Admin\Admin_Notices;

final class AdminNoticeTest extends TestCase {
public function test_notice_links_to_dashboard_widget(): void {
$wpdb = new wpdb();
$wpdb->tables[ $wpdb->prefix . 'bf_logs' ] = [
[
'id'         => 1,
'form_id'    => 1,
'type'       => 'email',
'level'      => 'error',
'created_at' => gmdate( 'Y-m-d H:i:s' ),
],
[
'id'         => 2,
'form_id'    => 1,
'type'       => 'email',
'level'      => 'error',
'created_at' => gmdate( 'Y-m-d H:i:s' ),
],
[
'id'         => 3,
'form_id'    => 1,
'type'       => 'email',
'level'      => 'error',
'created_at' => gmdate( 'Y-m-d H:i:s' ),
],
];
$GLOBALS['wpdb'] = $wpdb;

$notices = Admin_Notices::get_instance();
ob_start();
$notices->render_notice();
$output = ob_get_clean();

$this->assertStringContainsString( 'index.php#bf-email-queue', $output );
$this->assertStringContainsString( 'email queue dashboard', strtolower( $output ) );
}
}
