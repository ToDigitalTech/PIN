<?php
/**
 * Plugin Name: PIN Platform - Direct Salary Distribution
 * Plugin URI:  https://pinplatform.org
 * Description: Direct salary distribution system that routes payroll taxes to registered officers, bypassing corrupt middlemen.
 * Version:     1.0.0
 * Author:      PIN Platform Team
 * Author URI:  https://pinplatform.org
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pin-platform
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'PIN_VERSION', '1.0.0' );
define( 'PIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'PIN_TAX_RATE', 0.25 );

/**
 * Check if WooCommerce is active before initializing.
 */
function pin_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'pin_woocommerce_missing_notice' );
        return false;
    }
    return true;
}

/**
 * Admin notice when WooCommerce is not active.
 */
function pin_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>PIN Platform</strong> requires WooCommerce to be installed and active. Please install and activate WooCommerce.</p>
    </div>
    <?php
}

/**
 * Plugin activation.
 */
function pin_activate() {
    require_once PIN_PLUGIN_DIR . 'includes/class-pin-activator.php';
    PIN_Activator::activate();
}
register_activation_hook( __FILE__, 'pin_activate' );

/**
 * Plugin deactivation.
 */
function pin_deactivate() {
    // Flush rewrite rules on deactivation.
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pin_deactivate' );

/**
 * Load all plugin classes and initialize.
 */
function pin_init() {
    if ( ! pin_check_woocommerce() ) {
        return;
    }

    // Load includes.
    require_once PIN_PLUGIN_DIR . 'includes/class-pin-roles.php';
    require_once PIN_PLUGIN_DIR . 'includes/class-pin-employer.php';
    require_once PIN_PLUGIN_DIR . 'includes/class-pin-worker.php';
    require_once PIN_PLUGIN_DIR . 'includes/class-pin-officer.php';
    require_once PIN_PLUGIN_DIR . 'includes/class-pin-payroll.php';
    require_once PIN_PLUGIN_DIR . 'includes/class-pin-distribution.php';
    require_once PIN_PLUGIN_DIR . 'includes/class-pin-woocommerce.php';

    // Initialize classes.
    PIN_Roles::init();
    PIN_Employer::init();
    PIN_Worker::init();
    PIN_Officer::init();
    PIN_Payroll::init();
    PIN_Distribution::init();
    PIN_WooCommerce::init();

    // Load public-facing functionality.
    require_once PIN_PLUGIN_DIR . 'public/class-pin-public.php';
    PIN_Public::init();

    // Load admin functionality.
    if ( is_admin() ) {
        require_once PIN_PLUGIN_DIR . 'admin/class-pin-admin.php';
        PIN_Admin::init();
    }
}
add_action( 'plugins_loaded', 'pin_init' );

/**
 * Enqueue public styles and scripts.
 */
function pin_enqueue_public_assets() {
    wp_enqueue_style(
        'pin-public',
        PIN_PLUGIN_URL . 'public/css/pin-public.css',
        array(),
        PIN_VERSION
    );
    wp_enqueue_script(
        'pin-public',
        PIN_PLUGIN_URL . 'public/js/pin-public.js',
        array( 'jquery' ),
        PIN_VERSION,
        true
    );
    wp_localize_script( 'pin-public', 'pinAjax', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'pin_ajax_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'pin_enqueue_public_assets' );

/**
 * Enqueue admin styles and scripts.
 */
function pin_enqueue_admin_assets( $hook ) {
    if ( strpos( $hook, 'pin-platform' ) === false ) {
        return;
    }
    wp_enqueue_style(
        'pin-admin',
        PIN_PLUGIN_URL . 'admin/css/pin-admin.css',
        array(),
        PIN_VERSION
    );
    wp_enqueue_script(
        'pin-admin',
        PIN_PLUGIN_URL . 'admin/js/pin-admin.js',
        array( 'jquery' ),
        PIN_VERSION,
        true
    );
    wp_localize_script( 'pin-admin', 'pinAdmin', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'pin_admin_nonce' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'pin_enqueue_admin_assets' );
