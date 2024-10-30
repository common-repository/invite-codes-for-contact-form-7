<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function icfcf7_create_table() {
	global $wpdb;

	$cache_key = 'icfcf7_table_exists';
	$table_exists = wp_cache_get( $cache_key );

	if ( false === $table_exists ) {
		$table_name = esc_sql( $wpdb->prefix . 'icfcf7' );
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id INT NOT NULL AUTO_INCREMENT,
			invite_code VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
			times_used MEDIUMINT UNSIGNED DEFAULT 0,
			max_usage_limit MEDIUMINT UNSIGNED DEFAULT 1,
			expiration_date DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY invite_code (invite_code)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// Set cache for table existence
		wp_cache_set( $cache_key, true, '', 24 * HOUR_IN_SECONDS );
	}
}


function icfcf7_delete_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'icfcf7';
    $sql = "DROP TABLE IF EXISTS `$table_name`;";
    $wpdb->query( $sql );

    // Clear the cache after deletion
    wp_cache_delete( 'icfcf7_table_exists' );
}

