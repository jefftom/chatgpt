<?php
/**
 * Main plugin bootstrap.
 *
 * @package WP_BetterForms
 */

namespace WP_BetterForms;

use WP_BetterForms\Admin\Admin_Dashboard;
use WP_BetterForms\Admin\Admin_Notices;
use WP_BetterForms\Rest\Rest_Controller;
use WP_BetterForms\Rendering\Renderer;
use WP_BetterForms\Scheduler\Scheduler;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
final class Plugin {
/**
 * Plugin version.
 */
public const VERSION = '1.0.0';

/**
 * Singleton instance.
 */
private static ?Plugin $instance = null;

/**
 * Plugin basename.
 */
private string $basename;

/**
 * Constructor.
 */
private function __construct() {
$this->basename = plugin_basename( dirname( __DIR__ ) . '/wp-betterforms.php' );
}

/**
 * Get singleton instance.
 */
public static function get_instance(): Plugin {
if ( ! self::$instance ) {
self::$instance = new self();
}

return self::$instance;
}

/**
 * Boot plugin hooks.
 */
public function boot(): void {
register_activation_hook( dirname( __DIR__ ) . '/wp-betterforms.php', [ Activator::class, 'activate' ] );
register_deactivation_hook( dirname( __DIR__ ) . '/wp-betterforms.php', [ Deactivator::class, 'deactivate' ] );

add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
add_action( 'init', [ $this, 'register_assets' ] );
add_action( 'init', [ $this, 'register_shortcodes' ] );
add_action( 'init', [ $this, 'register_blocks' ] );
add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

Admin_Notices::get_instance();
Admin_Dashboard::get_instance();
Scheduler::get_instance();
}

/**
 * Load translations.
 */
public function load_textdomain(): void {
load_plugin_textdomain( 'wp-betterforms', false, dirname( $this->basename ) . '/languages' );
}

/**
 * Register scripts and styles.
 */
public function register_assets(): void {
$asset_version = self::VERSION;

wp_register_script(
'wp-betterforms-admin',
plugins_url( 'public/js/admin.js', dirname( __DIR__ ) . '/wp-betterforms.php' ),
[ 'wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch' ],
$asset_version,
true
);

wp_localize_script(
'wp-betterforms-admin',
'wpBetterFormsConfig',
[
'nonce'    => wp_create_nonce( 'wp_rest' ),
'restBase' => esc_url_raw( rest_url( Rest_Controller::NAMESPACE . '/forms' ) ),
]
);

wp_register_style(
'wp-betterforms-admin',
plugins_url( 'public/css/admin.css', dirname( __DIR__ ) . '/wp-betterforms.php' ),
[],
$asset_version
);

wp_register_script(
'wp-betterforms-runtime',
plugins_url( 'public/js/runtime.js', dirname( __DIR__ ) . '/wp-betterforms.php' ),
[],
$asset_version,
true
);

wp_register_style(
'wp-betterforms-runtime',
plugins_url( 'public/css/runtime.css', dirname( __DIR__ ) . '/wp-betterforms.php' ),
[],
$asset_version
);
}

/**
 * Register shortcodes.
 */
public function register_shortcodes(): void {
add_shortcode( 'betterform', [ Renderer::class, 'render_shortcode' ] );
}

/**
 * Register blocks if Gutenberg exists.
 */
public function register_blocks(): void {
if ( ! function_exists( 'register_block_type' ) ) {
return;
}

register_block_type( 'wp-betterforms/form', [
'render_callback' => [ Renderer::class, 'render_block' ],
'editor_script'   => 'wp-betterforms-admin',
] );
}

/**
 * Register REST routes.
 */
public function register_rest_routes(): void {
Rest_Controller::get_instance()->register_routes();
}

/**
 * Enqueue admin assets.
 */
public function register_admin_menu(): void {
add_menu_page(
__( 'Better Forms', 'wp-betterforms' ),
__( 'Better Forms', 'wp-betterforms' ),
'manage_options',
'wp-betterforms',
[ $this, 'render_admin_page' ],
'dashicons-feedback',
58
);
}

/**
 * Render admin app container.
 */
public function render_admin_page(): void {
wp_enqueue_script( 'wp-betterforms-admin' );
wp_enqueue_style( 'wp-betterforms-admin' );
include dirname( __DIR__ ) . '/admin/index.php';
}

/**
 * Enqueue block editor assets if block used.
 */
public function enqueue_block_editor_assets(): void {
if ( function_exists( 'register_block_type' ) ) {
wp_enqueue_script( 'wp-betterforms-admin' );
wp_enqueue_style( 'wp-betterforms-admin' );
}
}

/**
 * Enqueue frontend assets when shortcode present.
 */
public function enqueue_frontend_assets(): void {
if ( ! Renderer::should_enqueue_frontend_assets() ) {
return;
}

wp_enqueue_script( 'wp-betterforms-runtime' );
wp_enqueue_style( 'wp-betterforms-runtime' );

wp_localize_script(
'wp-betterforms-runtime',
'wpBetterFormsRuntime',
[
'nonce' => wp_create_nonce( 'wp_rest' ),
'root'  => esc_url_raw( rest_url( Rest_Controller::NAMESPACE . '/submit' ) ),
]
);
}

/**
 * Get plugin basename.
 */
public function get_basename(): string {
return $this->basename;
}
}
