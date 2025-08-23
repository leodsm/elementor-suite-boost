<?php
/**
 * Plugin Name: CM Suite for Elementor
 * Plugin URI: https://example.com/cm-suite-elementor
 * Description: Complete suite of advanced widgets and tools for Elementor with Stories Player, Post Cards, Theme Kit and more.
 * Version: 1.1.0
 * Author: CM Suite Team
 * Text Domain: cm-suite-elementor
 * Domain Path: /languages
 * Requires at least: 6.8
 * Tested up to: 6.8
 * Requires PHP: 8.1
 * Elementor tested up to: 3.30
 * License: GPL v2 or later
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CM_SUITE_VERSION', '1.1.0');
define('CM_SUITE_FILE', __FILE__);
define('CM_SUITE_PATH', plugin_dir_path(__FILE__));
define('CM_SUITE_URL', plugin_dir_url(__FILE__));
define('CM_SUITE_BASENAME', plugin_basename(__FILE__));

/**
 * PSR-4 Autoloader for CM\Suite namespace
 */
spl_autoload_register(function ($class) {
    $prefix = 'CM\\Suite\\';
    $base_dir = CM_SUITE_PATH . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Initialize the plugin
 */
function cm_suite_init() {
    // Check if Elementor is active
    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', 'cm_suite_elementor_missing_notice');
        return;
    }
    
    // Check Elementor version
    if (!version_compare(ELEMENTOR_VERSION, '3.30.0', '>=')) {
        add_action('admin_notices', 'cm_suite_elementor_version_notice');
        return;
    }
    
    // Initialize after Elementor is ready
    add_action('elementor/init', function() {
        new CM\Suite\Suite();
    });
}
add_action('plugins_loaded', 'cm_suite_init');

/**
 * Admin notice when Elementor is missing
 */
function cm_suite_elementor_missing_notice() {
    if (isset($_GET['activate'])) {
        unset($_GET['activate']);
    }
    
    $message = sprintf(
        esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'cm-suite-elementor'),
        '<strong>' . esc_html__('CM Suite for Elementor', 'cm-suite-elementor') . '</strong>',
        '<strong>' . esc_html__('Elementor', 'cm-suite-elementor') . '</strong>'
    );
    
    printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
}

/**
 * Admin notice when Elementor version is too old
 */
function cm_suite_elementor_version_notice() {
    $message = sprintf(
        esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'cm-suite-elementor'),
        '<strong>' . esc_html__('CM Suite for Elementor', 'cm-suite-elementor') . '</strong>',
        '<strong>' . esc_html__('Elementor', 'cm-suite-elementor') . '</strong>',
        '3.30.0'
    );
    
    printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
}

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, function() {
    // Set default options
    if (!get_option('cm_suite_modules')) {
        update_option('cm_suite_modules', [
            'ThemeKit' => true,
            'PostCards' => true,
            'StoriesPlayer' => true,
            'PlayerLauncher' => true,
            'Editor' => true
        ]);
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});