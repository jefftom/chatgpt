<?php
/**
 * Deactivation handler.
 */

namespace WP_BetterForms;

defined( 'ABSPATH' ) || exit;

use WP_BetterForms\Scheduler\Scheduler;

/**
 * Class Deactivator
 */
final class Deactivator {
/**
 * Run on plugin deactivation.
 */
public static function deactivate(): void {
Scheduler::get_instance()->unschedule_events();
do_action( 'wp_betterforms/deactivated' );
}
}
