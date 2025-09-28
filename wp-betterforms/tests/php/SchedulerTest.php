<?php

use PHPUnit\Framework\TestCase;
use WP_BetterForms\Scheduler\Scheduler;

final class SchedulerTest extends TestCase {
protected function setUp(): void {
parent::setUp();
$GLOBALS['bf_test_wp_mail_results'] = [];
}

public function test_process_queue_updates_statuses_and_logs_failures(): void {
$wpdb = new wpdb();
$wpdb->tables[ $wpdb->prefix . 'bf_email_queue' ] = [
[
'id'         => 1,
'form_id'    => 1,
'entry_id'   => 1,
'to_email'  => 'fail@example.com',
'subject'    => 'Failure',
'body_html'  => '<p>Test</p>',
'status'     => 'pending',
'attempts'   => 0,
'created_at' => '2024-01-01 00:00:00',
'updated_at' => '2024-01-01 00:00:00',
],
[
'id'         => 2,
'form_id'    => 1,
'entry_id'   => 2,
'to_email'  => 'pass@example.com',
'subject'    => 'Success',
'body_html'  => '<p>Test</p>',
'status'     => 'pending',
'attempts'   => 0,
'created_at' => '2024-01-01 00:01:00',
'updated_at' => '2024-01-01 00:01:00',
],
];
$wpdb->tables[ $wpdb->prefix . 'bf_logs' ] = [];
$GLOBALS['wpdb'] = $wpdb;
$GLOBALS['bf_test_wp_mail_results'] = [ false, true ];

Scheduler::get_instance()->process_queue();

$queue = $wpdb->tables[ $wpdb->prefix . 'bf_email_queue' ];
$this->assertSame( 'failed', $queue[0]['status'] );
$this->assertSame( 'sent', $queue[1]['status'] );
$this->assertSame( 1, $queue[0]['attempts'] );
$this->assertSame( 1, $queue[1]['attempts'] );

$logs = $wpdb->tables[ $wpdb->prefix . 'bf_logs' ];
$this->assertNotEmpty( $logs );
$this->assertSame( 'error', $logs[0]['level'] );
$this->assertSame( 'email', $logs[0]['type'] );
}
}
