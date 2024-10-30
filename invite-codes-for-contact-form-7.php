<?php
/*
Plugin Name: Invite Codes for Contact Form 7
Requires Plugins: contact-form-7
Description: Adds invite codes functionality to Contact Form 7.
Version: 1.2.6
Author: Haste
License: GPLv2 or later
Text Domain: invite-codes-for-contact-form-7
Domain Path: /languages
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if Contact Form 7 is active before plugin activation.
 */
function icfcf7_activation_check() {
    if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
        // Deactivate this plugin
        deactivate_plugins( plugin_basename( __FILE__ ) );

        // Display an error message to the user
        wp_die(
            __( 'The "Invite Codes for Contact Form 7" plugin requires the "Contact Form 7" plugin to be installed and activated.', 'invite-codes-for-contact-form-7' ),
            __( 'Plugin Activation Error', 'invite-codes-for-contact-form-7' ),
            array( 'back_link' => true )
        );
    }
}
register_activation_hook( __FILE__, 'icfcf7_activation_check' );

/**
 * Admin notice if Contact Form 7 is not active.
 */
function icfcf7_admin_notice() {
    if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
        echo '<div class="error"><p>';
        _e( 'The "Invite Codes for Contact Form 7" plugin requires the "Contact Form 7" plugin to be installed and activated. Please install and activate it first.', 'invite-codes-for-contact-form-7' );
        echo '</p></div>';
    }
}
add_action( 'admin_notices', 'icfcf7_admin_notice' );

/**
 * Enqueue the CSS and JS file for the plugin.
 */
function icfcf7_enqueue_styles() {
    wp_enqueue_style( 'icfcf7-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/icfcf7-admin-style.css', array(), '1.2.4' );
}
add_action( 'admin_enqueue_scripts', 'icfcf7_enqueue_styles' );

function icfcf7_admin_scripts() {
    wp_enqueue_script( 'icfcf7-admin-scripts', plugin_dir_url( __FILE__ ) . 'assets/js/icfcf7-admin.js', array(), '1.2.4', array( 'in_footer' => 'true' ) );
}
add_action( 'admin_enqueue_scripts', 'icfcf7_admin_scripts' );

/**
 * Load plugin text domain for translations.
 */
function icfcf7_load_textdomain() {
    // Get the current locale
    $locale = apply_filters( 'plugin_locale', get_locale(), 'invite-codes-for-contact-form-7' );

    // Skip loading .mo file if the locale is en_US (default English locale)
    if ( 'en_US' === $locale ) {
        return;
    }

    // Load translations from the system languages folder
    $mofile = WP_LANG_DIR . '/plugins/invite-codes-for-contact-form-7-' . $locale . '.mo';

    if ( ! load_textdomain( 'invite-codes-for-contact-form-7', $mofile ) ) {
        // If system directory fails, load from the plugin's /languages/ folder
        load_plugin_textdomain( 'invite-codes-for-contact-form-7', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
}
add_action( 'plugins_loaded', 'icfcf7_load_textdomain', 5 );

/**
 * Include necessary plugin files.
 */
function icfcf7_include_files() {
    // Check if Contact Form 7 is active before including files
    if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
        return;
    }

    include_once plugin_dir_path( __FILE__ ) . 'includes/database.php';
    include_once plugin_dir_path( __FILE__ ) . 'includes/admin.php';
    include_once plugin_dir_path( __FILE__ ) . 'includes/codes-generator.php';
    include_once plugin_dir_path( __FILE__ ) . 'includes/export.php';
    include_once plugin_dir_path( __FILE__ ) . 'includes/form-tag.php';
    include_once plugin_dir_path( __FILE__ ) . 'includes/settings.php';
    include_once plugin_dir_path( __FILE__ ) . 'includes/validation.php';
}
add_action( 'plugins_loaded', 'icfcf7_include_files', 20 );

/**
 * Activation hook - creates necessary database tables.
 */
function icfcf7_activate() {
    // Check if Contact Form 7 is active
    if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
        return;
    }

    // Ensure that the database functions are loaded
    if ( ! function_exists( 'icfcf7_create_table' ) ) {
        include_once plugin_dir_path( __FILE__ ) . 'includes/database.php';
    }

    // Call the function to create the table
    icfcf7_create_table();

    // Ensure the option is created with default value of 0 if it doesn't exist
    add_option( 'icfcf7_case_sensitive', 0 );
}
register_activation_hook( __FILE__, 'icfcf7_activate' );

/**
 * Uninstallation hook - deletes database tables.
 */
function icfcf7_uninstall() {
    global $wpdb;

    // Delete the invite codes table
    $table_name = $wpdb->prefix . 'icfcf7';
    $wpdb->query( "DROP TABLE IF EXISTS `$table_name`;" );

    // Clear the cache after deletion
    wp_cache_delete( 'icfcf7_table_exists' );

    // Delete all exported CSV files from the upload directory
    $upload_dir = wp_upload_dir();
    $csv_files = glob( trailingslashit( $upload_dir['basedir'] ) . 'icfcf7/' . 'invite_codes_*.csv' );

    if ( ! empty( $csv_files ) ) {
        foreach ( $csv_files as $csv_file ) {
            wp_delete_file( $csv_file );
        }
    }
}
register_uninstall_hook( __FILE__, 'icfcf7_uninstall' );

?>
