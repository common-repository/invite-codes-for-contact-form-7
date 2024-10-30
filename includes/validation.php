<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Add custom validation for the 'invite_code' form tag in Contact Form 7.
 */
function icfcf7_add_validation_filter() {
    add_filter( 'wpcf7_validate_text*', 'icfcf7_validate', 20, 2 );
}
add_action( 'wpcf7_init', 'icfcf7_add_validation_filter' );

/**
 * Validate the 'invite_code' form tag.
 */
function icfcf7_validate( $result, $tag ) {
    $tag = new WPCF7_FormTag( $tag );

    // Only validate the 'invite_code' field
    if ( 'invite_code' !== $tag->name ) {
        return $result;
    }

    // Retrieve validation messages from settings
    $messages = get_option( 'icfcf7_messages', array(
        'empty_field'  => __( 'Invite code is required.', 'invite-codes-for-contact-form-7' ),
        'invalid_code' => __( 'Invalid invite code.', 'invite-codes-for-contact-form-7' ),
        'used_code'    => __( 'This invite code has already been used.', 'invite-codes-for-contact-form-7' ),
        'expired_code' => __( 'This invite code has expired.', 'invite-codes-for-contact-form-7' ),
    ) );

    // Get the submitted invite code and use wp_unslash() before sanitization
    $invite_code = isset( $_POST['invite_code'] ) ? sanitize_text_field( wp_unslash( $_POST['invite_code'] ) ) : '';

    if ( empty( $invite_code ) ) {
        $result->invalidate( $tag, $messages['empty_field'] );
        return $result;
    }

    // Cache lookup
    $cache_key = 'icfcf7_invite_code_' . md5( $invite_code );
    $code_data = wp_cache_get( $cache_key );

    if ( false === $code_data ) {
        // Retrieve the code from the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'icfcf7';
        $case_sensitive = get_option( 'icfcf7_case_sensitive', 0 );

        // Adjust query based on case sensitivity setting
        if ( $case_sensitive ) {
            $code_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE invite_code = %s", $invite_code ) );
        } else {
            $code_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE LOWER(invite_code) = LOWER(%s)", $invite_code ) );
        }

        // Cache the result
        wp_cache_set( $cache_key, $code_data, '', HOUR_IN_SECONDS );
    }

    // Validate if the code exists
    if ( ! $code_data ) {
        $result->invalidate( $tag, $messages['invalid_code'] );
        return $result;
    }

    // Validate expiration date
    if ( $code_data->expiration_date && strtotime( $code_data->expiration_date ) < current_time( 'timestamp' ) ) {
        $result->invalidate( $tag, $messages['expired_code'] );
        return $result;
    }

    // Validate usage limit
    if ( $code_data->max_usage_limit != 0 && $code_data->times_used >= $code_data->max_usage_limit ) {
        $result->invalidate( $tag, $messages['used_code'] );
        return $result;
    }

    return $result;
}

/**
 * Mark the invite code as used after successful form submission.
 */
function icfcf7_mark_invite_code_as_used( $contact_form ) {
    $submission = WPCF7_Submission::get_instance();

    if ( $submission ) {
        $data = $submission->get_posted_data();

        if ( isset( $data['invite_code'] ) ) {
            $invite_code = sanitize_text_field( wp_unslash( $data['invite_code'] ) );

            // Cache lookup
            $cache_key = 'icfcf7_invite_code_' . md5( $invite_code );
            $code_data = wp_cache_get( $cache_key );

            // Define table name here for better scope
            global $wpdb;
            $table_name = $wpdb->prefix . 'icfcf7';

            if ( false === $code_data ) {
                $case_sensitive = get_option( 'icfcf7_case_sensitive', 0 );

                // Retrieve the code from the database
                if ( $case_sensitive ) {
                    $code_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE invite_code = %s", $invite_code ) );
                } else {
                    $code_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE LOWER(invite_code) = LOWER(%s)", $invite_code ) );
                }

                // Cache the result
                wp_cache_set( $cache_key, $code_data, '', HOUR_IN_SECONDS );
            }

            // Increment the times_used if validation is passed
            if ( $code_data ) {
                // Check if times_used is a valid integer
                $new_times_used = (int) $code_data->times_used + 1;

                // Update the database
                $wpdb->update(
                    $table_name,
                    array( 'times_used' => $new_times_used ),
                    array( 'id' => $code_data->id ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
        }
    }
}
add_action( 'wpcf7_mail_sent', 'icfcf7_mark_invite_code_as_used' );

