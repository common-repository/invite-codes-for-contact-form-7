<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Export Invite Codes to CSV with a random filename.
 */
function icfcf7_export_invite_codes() {
    // Verify nonce for security
    if ( ! check_admin_referer( 'icfcf7_export_codes_action', 'icfcf7_export_codes_nonce' ) ) {
        wp_die( esc_html__( 'Nonce verification failed.', 'invite-codes-for-contact-form-7' ) );
    }

    global $wpdb;

    // Prepare the query, manually sanitize the table name
    $table_name = esc_sql( $wpdb->prefix . 'icfcf7' ); // Sanitize table name

    // Execute the query without using prepare since there are no dynamic inputs in the query
    $query = "SELECT * FROM {$table_name}";
    $codes = wp_cache_get( 'icfcf7_export' );

    if ( false === $codes ) {
        $codes = $wpdb->get_results( $query, ARRAY_A ); // Direct execution of query

        // Cache the results for an hour
        wp_cache_set( 'icfcf7_export', $codes, '', HOUR_IN_SECONDS );
    }

    if ( $codes ) {
        // Generate random 12-character string
        $random_string = substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' ), 0, 12 );

        // Get WordPress uploads directory
        $upload_dir = wp_upload_dir();

        // Define the subdirectory path for the 'icfcf7' folder inside the uploads directory
        $icfcf7_dir = trailingslashit( $upload_dir['basedir'] ) . 'icfcf7';

        // Create the 'icfcf7' subdirectory if it doesn't exist
        if ( ! file_exists( $icfcf7_dir ) ) {
            wp_mkdir_p( $icfcf7_dir );  // Create the directory if it doesn't exist
        }

        // Set the file path and URL to the icfcf7 directory
        $csv_file_name = 'invite_codes_' . gmdate( 'Y-m-d' ) . '_' . $random_string . '.csv';
        $csv_file_path = trailingslashit( $icfcf7_dir ) . $csv_file_name;
        $csv_file_url = trailingslashit( $upload_dir['baseurl'] ) . 'icfcf7/' . $csv_file_name;

        // Initialize WP_Filesystem for writing the CSV
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        global $wp_filesystem;

        if ( WP_Filesystem() ) {
            // Create CSV content as a string
            $csv_content = '"ID","Invite Code","Times Used","Max Usage","Expiration Date"' . "\n"; // CSV headers

            // Iterate through each code and add CSV rows
            foreach ( $codes as $code ) {
                $csv_content .= '"' . intval( $code['id'] ) . '","' . sanitize_text_field( $code['invite_code'] ) . '","' . intval( $code['times_used'] ) . '","' . intval( $code['max_usage_limit'] ) . '","' . ( $code['expiration_date'] ? date_i18n( 'Y-m-d H:i:s', strtotime( $code['expiration_date'] ) ) : esc_html__( 'No expiration date', 'invite-codes-for-contact-form-7' ) ) . '"' . "\n";
            }

            // Write the CSV content to the file using WP_Filesystem
            $result = $wp_filesystem->put_contents( $csv_file_path, $csv_content, FS_CHMOD_FILE );

            if ( ! $result ) {
                wp_die( esc_html__( 'Failed to create CSV file.', 'invite-codes-for-contact-form-7' ) );
            }

            // Display success message with download link
            echo '<div class="updated"><p>';
            echo esc_html__( 'Invite codes exported successfully.', 'invite-codes-for-contact-form-7' ) . ' ';
            echo '<a href="' . esc_url( $csv_file_url ) . '">' . esc_html__( 'Download CSV file', 'invite-codes-for-contact-form-7' ) . '</a>';
            echo '</p></div>';
        } else {
            wp_die( esc_html__( 'Failed to initialize the file system.', 'invite-codes-for-contact-form-7' ) );
        }
    } else {
        echo '<div class="updated"><p>' . esc_html__( 'No codes found to export.', 'invite-codes-for-contact-form-7' ) . '</p></div>';
    }
}

/**
 * List exported files using scandir instead of glob.
 */
function icfcf7_list_exported_files() {
    // Get WordPress uploads directory and the 'icfcf7' subdirectory
    $upload_dir = wp_upload_dir();
    $icfcf7_dir = trailingslashit( $upload_dir['basedir'] ) . 'icfcf7';
    $icfcf7_url = trailingslashit( $upload_dir['baseurl'] ) . 'icfcf7';

    // Ensure the 'icfcf7' subdirectory exists
    if ( ! file_exists( $icfcf7_dir ) ) {
        echo '<p>' . esc_html__( 'No export files found.', 'invite-codes-for-contact-form-7' ) . '</p>';
        return;
    }

    // Use scandir to list all files in the 'icfcf7' subdirectory
    $csv_files = scandir( $icfcf7_dir );

    // Filter only the files that match the 'invite_codes_*.csv' pattern
    $csv_files = array_filter( $csv_files, function( $file ) {
        return preg_match( '/invite_codes_.*\.csv$/', $file );
    });

    if ( ! empty( $csv_files ) ) {
        // Sort files by creation date (descending)
        usort( $csv_files, function( $a, $b ) use ( $icfcf7_dir ) {
            return filemtime( $icfcf7_dir . '/' . $b ) - filemtime( $icfcf7_dir . '/' . $a );
        });

        echo '<table class="export-table striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Filename', 'invite-codes-for-contact-form-7' ) . '</th>';
        echo '<th>' . esc_html__( 'Creation Date', 'invite-codes-for-contact-form-7' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'invite-codes-for-contact-form-7' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ( $csv_files as $csv_file ) {
            $file_name = basename( $csv_file );
            $file_url = esc_url( add_query_arg( array( 'download_file' => urlencode( $file_name ) ), admin_url() ) );
            $file_date = gmdate( 'Y-m-d H:i:s', filemtime( $icfcf7_dir . '/' . $csv_file ) );

            echo '<tr>';
            echo '<td>' . esc_html( $file_name ) . '</td>';
            echo '<td>' . esc_html( $file_date ) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url( $file_url ) . '" class="button button-secondary">' . esc_html__( 'Download', 'invite-codes-for-contact-form-7' ) . '</a> ';
            echo '<form method="post" style="display:inline;">';
            wp_nonce_field( 'icfcf7_delete_file_action', 'icfcf7_delete_file_nonce' );
            echo '<input type="hidden" name="file_to_delete" value="' . esc_attr( $file_name ) . '">';
            echo '<input type="submit" name="icfcf7_delete_file" value="' . esc_attr__( 'Delete', 'invite-codes-for-contact-form-7' ) . '" class="button button-secondary" onclick="return confirm(\'' . esc_attr__( 'Are you sure you want to delete this file?', 'invite-codes-for-contact-form-7' ) . '\');">';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Add "Delete All Files" button
        echo '<form method="post">';
        wp_nonce_field( 'icfcf7_delete_all_files_action', 'icfcf7_delete_all_files_nonce' );
        echo '<input type="submit" name="icfcf7_delete_all_files" value="' . esc_attr__( 'Delete All Files', 'invite-codes-for-contact-form-7' ) . '" class="button button-primary" onclick="return confirm(\'' . esc_attr__( 'Are you sure you want to delete all export files?', 'invite-codes-for-contact-form-7' ) . '\');">';
        echo '</form>';
    } else {
        echo '<p>' . esc_html__( 'No export files found.', 'invite-codes-for-contact-form-7' ) . '</p>';
    }
}

// Handle file download and deletion
function icfcf7_handle_file_actions() {
    // Get the upload directory and ensure the 'icfcf7' subdirectory exists
    $upload_dir = wp_upload_dir();
    $icfcf7_dir = trailingslashit( $upload_dir['basedir'] ) . 'icfcf7';

    // Check for file download request
    if ( isset( $_GET['download_file'] ) ) {
        $file_name = sanitize_file_name( wp_unslash( $_GET['download_file'] ) );
        $file_path = trailingslashit( $icfcf7_dir ) . $file_name;

        // **Security Enhancement**: Ensure that the file path is within the icfcf7 subdirectory
        if ( strpos( realpath( $file_path ), realpath( $icfcf7_dir ) ) !== 0 ) {
            wp_die( esc_html__( 'Invalid file path.', 'invite-codes-for-contact-form-7' ) );
        }

        // Initialize WP_Filesystem
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        global $wp_filesystem;
        WP_Filesystem();

        if ( $wp_filesystem->exists( $file_path ) ) {
            $file_content = $wp_filesystem->get_contents( $file_path );
            if ( $file_content !== false ) {
                header( 'Content-Description: File Transfer' );
                header( 'Content-Type: application/octet-stream' );
                header( 'Content-Disposition: attachment; filename=' . basename( $file_name ) );
                header( 'Expires: 0' );
                header( 'Cache-Control: must-revalidate' );
                header( 'Pragma: public' );
                header( 'Content-Length: ' . strlen( $file_content ) );
                echo $file_content;
                exit;
            }
        }
    }

    // Handle single file deletion
    if ( isset( $_POST['icfcf7_delete_file'] ) && check_admin_referer( 'icfcf7_delete_file_action', 'icfcf7_delete_file_nonce' ) ) {
        if ( isset( $_POST['file_to_delete'] ) ) {
            $file_to_delete = sanitize_text_field( wp_unslash( $_POST['file_to_delete'] ) );
        } else {
            wp_die( esc_html__( 'No file selected for deletion.', 'invite-codes-for-contact-form-7' ) );
        }

        $file_path = trailingslashit( $icfcf7_dir ) . basename( $file_to_delete );

        // **Security Enhancement**: Ensure the file to delete is within the icfcf7 directory
        if ( strpos( realpath( $file_path ), realpath( $icfcf7_dir ) ) !== 0 ) {
            wp_die( esc_html__( 'Invalid file path for deletion.', 'invite-codes-for-contact-form-7' ) );
        }

        if ( file_exists( $file_path ) ) {
            wp_delete_file( $file_path );
            echo '<div class="updated"><p>' . esc_html__( 'File deleted successfully.', 'invite-codes-for-contact-form-7' ) . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__( 'Failed to delete the file.', 'invite-codes-for-contact-form-7' ) . '</p></div>';
        }
    }

    // Handle delete all files action
    if ( isset( $_POST['icfcf7_delete_all_files'] ) && check_admin_referer( 'icfcf7_delete_all_files_action', 'icfcf7_delete_all_files_nonce' ) ) {
        $csv_files = glob( trailingslashit( $icfcf7_dir ) . 'invite_codes_*.csv' );

        if ( ! empty( $csv_files ) ) {
            foreach ( $csv_files as $csv_file ) {
                // **Security Enhancement**: Ensure the file is within the icfcf7 subdirectory
                if ( strpos( realpath( $csv_file ), realpath( $icfcf7_dir ) ) === 0 ) {
                    wp_delete_file( $csv_file );
                }
            }
            echo '<div class="updated"><p>' . esc_html__( 'All export files deleted successfully.', 'invite-codes-for-contact-form-7' ) . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__( 'No files to delete.', 'invite-codes-for-contact-form-7' ) . '</p></div>';
        }
    }
}
add_action( 'admin_init', 'icfcf7_handle_file_actions' );
