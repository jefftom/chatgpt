<?php
/**
 * Plugin Name: WP Better Forms
 * Plugin URI: https://example.com/wp-betterforms
 * Description: Blazing-fast WordPress form builder with visual styler and AJAX submissions.
 * Version: 1.0.0
 * Author: WP Better Forms
 * Author URI: https://example.com
 * Text Domain: wp-betterforms
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.0
 */

defined( 'ABSPATH' ) || exit;

$includes = [
'includes/class-activator.php',
'includes/class-deactivator.php',
'includes/class-form-repository.php',
'includes/class-rest.php',
'includes/class-render.php',
'includes/class-validator.php',
'includes/class-mailer.php',
'includes/class-logger.php',
'includes/class-admin-notices.php',
'includes/class-scheduler.php',
'includes/db/schema.php',
'includes/db/migrations.php',
'includes/class-plugin.php',
];

foreach ( $includes as $include ) {
require_once __DIR__ . '/' . $include;
}

\WP_BetterForms\Plugin::get_instance()->boot();
