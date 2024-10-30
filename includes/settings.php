<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Register plugin settings.
 */
function icfcf7_register_settings() {
    // Register settings for validation messages with a sanitize callback
    register_setting(
        'icfcf7_settings_group',
        'icfcf7_messages',
        'icfcf7_sanitize_messages'
    );

    // Register setting for case sensitivity with an integer sanitize callback
    register_setting(
        'icfcf7_settings_group',
        'icfcf7_case_sensitive',
        'intval'
    );

    // Register settings section
    add_settings_section(
        'icfcf7_settings_section',
        __( 'Invite Code Validation Messages', 'invite-codes-for-contact-form-7' ),
        'icfcf7_settings_section_cb',
        'icfcf7_settings'
    );

    // Register settings fields for validation messages
    add_settings_field(
        'icfcf7_invalid_code_message',
        __( 'Invalid Invite Code Message', 'invite-codes-for-contact-form-7' ),
        'icfcf7_invalid_code_message_cb',
        'icfcf7_settings',
        'icfcf7_settings_section'
    );

    add_settings_field(
        'icfcf7_used_code_message',
        __( 'Used Invite Code Message', 'invite-codes-for-contact-form-7' ),
        'icfcf7_used_code_message_cb',
        'icfcf7_settings',
        'icfcf7_settings_section'
    );

    add_settings_field(
        'icfcf7_expired_code_message',
        __( 'Expired Invite Code Message', 'invite-codes-for-contact-form-7' ),
        'icfcf7_expired_code_message_cb',
        'icfcf7_settings',
        'icfcf7_settings_section'
    );

    // Register settings field for case sensitivity
    add_settings_field(
        'icfcf7_case_sensitive',
        __( 'Case Sensitive Invite Codes', 'invite-codes-for-contact-form-7' ),
        'icfcf7_case_sensitive_cb',
        'icfcf7_settings',
        'icfcf7_settings_section'
    );
}
add_action( 'admin_init', 'icfcf7_register_settings' );

/**
 * Settings section callback.
 */
function icfcf7_settings_section_cb() {
    echo '<p>' . esc_html__( 'Customize the validation messages displayed on the front-end of your forms.', 'invite-codes-for-contact-form-7' ) . '</p>';
}

/**
 * Callback for Invalid Invite Code Message field.
 */
function icfcf7_invalid_code_message_cb() {
    $messages = get_option( 'icfcf7_messages', array() );
    $message  = isset( $messages['invalid_code'] ) ? $messages['invalid_code'] : __( 'The invite code you entered is invalid.', 'invite-codes-for-contact-form-7' );
    ?>
    <input type="text" name="icfcf7_messages[invalid_code]" value="<?php echo esc_attr( $message ); ?>" class="regular-text" />
    <p class="description"><?php esc_html_e( 'Message displayed when an invalid invite code is entered.', 'invite-codes-for-contact-form-7' ); ?></p>
    <?php
}

/**
 * Callback for Used Invite Code Message field.
 */
function icfcf7_used_code_message_cb() {
    $messages = get_option( 'icfcf7_messages', array() );
    $message  = isset( $messages['used_code'] ) ? $messages['used_code'] : __( 'This invite code has already been used.', 'invite-codes-for-contact-form-7' );
    ?>
    <input type="text" name="icfcf7_messages[used_code]" value="<?php echo esc_attr( $message ); ?>" class="regular-text" />
    <p class="description"><?php esc_html_e( 'Message displayed when an invite code has already been used.', 'invite-codes-for-contact-form-7' ); ?></p>
    <?php
}

/**
 * Callback for Expired Invite Code Message field.
 */
function icfcf7_expired_code_message_cb() {
    $messages = get_option( 'icfcf7_messages', array() );
    $message  = isset( $messages['expired_code'] ) ? $messages['expired_code'] : __( 'This invite code has expired.', 'invite-codes-for-contact-form-7' );
    ?>
    <input type="text" name="icfcf7_messages[expired_code]" value="<?php echo esc_attr( $message ); ?>" class="regular-text" />
    <p class="description"><?php esc_html_e( 'Message displayed when an invite code has expired.', 'invite-codes-for-contact-form-7' ); ?></p>
    <?php
}

/**
 * Callback function for Case Sensitive Invite Codes field.
 */
function icfcf7_case_sensitive_cb() {
    // Retrieve the saved value from the database (0 if not set)
    $case_sensitive = get_option( 'icfcf7_case_sensitive', 0 );

    $is_checked = ( $case_sensitive == 1 ) ? true : false;

    ?>
    <input type="checkbox" name="icfcf7_case_sensitive" id="icfcf7_case_sensitive" value="1" <?php checked( true, $is_checked, true ); ?> />
    <label for="icfcf7_case_sensitive"><?php esc_html_e( 'Tick this box if you want the invite code entered on the form to be case sensitive.', 'invite-codes-for-contact-form-7' ); ?></label>
    <?php
}

/**
 * Sanitize and validate validation messages.
 *
 * @param array $input The input array from the settings form.
 * @return array The sanitized input array.
 */
function icfcf7_sanitize_messages( $input ) {
    $sanitized_input = array();

    // Retrieve existing messages to retain non-updated fields
    $existing_messages = get_option( 'icfcf7_messages', array() );

    // List of required message fields
    $required_fields = array( 'invalid_code', 'used_code', 'expired_code' );

    // Initialize a flag to check if any field is left empty
    $empty_field_found = false;

    foreach ( $required_fields as $field ) {
        if ( isset( $input[ $field ] ) ) {
            // Use sanitize_text_field for single-line inputs
            $sanitized_value = sanitize_text_field( $input[ $field ] );

            if ( empty( $sanitized_value ) ) {
                $empty_field_found = true;

                // Retain the existing value if available
                if ( isset( $existing_messages[ $field ] ) && ! empty( $existing_messages[ $field ] ) ) {
                    $sanitized_input[ $field ] = $existing_messages[ $field ];
                } else {
                    // Set default messages if values are empty
                    switch ( $field ) {
                        case 'invalid_code':
                            $sanitized_input[ $field ] = __( 'Invalid invite code.', 'invite-codes-for-contact-form-7' );
                            break;
                        case 'used_code':
                            $sanitized_input[ $field ] = __( 'This invite code has already been used.', 'invite-codes-for-contact-form-7' );
                            break;
                        case 'expired_code':
                            $sanitized_input[ $field ] = __( 'This invite code has expired.', 'invite-codes-for-contact-form-7' );
                            break;
                        default:
                            $sanitized_input[ $field ] = '';
                    }
                }
            } else {
                // Valid input; save the sanitized value
                $sanitized_input[ $field ] = $sanitized_value;
            }
        }
    }

    // Handle case sensitivity checkbox
    $sanitized_input['icfcf7_case_sensitive'] = isset( $input['icfcf7_case_sensitive'] ) ? 1 : 0;

    // Set a transient to show error or success messages
    if ( $empty_field_found ) {
        set_transient( 'icfcf7_admin_notice', 'error', 10 );  // Set error notice
    } else {
        set_transient( 'icfcf7_admin_notice', 'success', 10 );  // Set success notice
    }

    return $sanitized_input;
}

?>
