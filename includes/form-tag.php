<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Register the 'invite_code' form tag with Contact Form 7.
 */
function icfcf7_add_form_tag_invite_codes() {
    wpcf7_add_form_tag(
        array('invite_code', 'invite-code'), // The form tag(s)
        'icfcf7_form_tag_handler', // The handler function
        array( 'name-attr' => true )         // Options
    );
}
add_action( 'wpcf7_init', 'icfcf7_add_form_tag_invite_codes' );

/**
 * Handle the 'invite_code' form tag.
 *
 * @param WPCF7_FormTag $tag The form tag object.
 *
 * @return string The HTML output for the form tag.
 */
function icfcf7_form_tag_handler( $tag ) {
    // Parse the form tag
    $tag = new WPCF7_FormTag( $tag );

    if ( empty( $tag->name ) ) {
        return '';
    }

    // Define the CSS classes for the input
    $class = wpcf7_form_controls_class( 'text' );

    // Set default placeholder
    $default_placeholder = __( 'Enter your invite code', 'invite-codes-for-contact-form-7' );

    // Check if a placeholder is provided in the form tag
    $placeholder = $default_placeholder;
    if ( $tag->has_option( 'placeholder' ) ) {
        $placeholder = $tag->get_option( 'placeholder', 'display', 'text', true );
    }

    // Determine if the field is required
    $required = $tag->is_required() ? ' required' : '';

    // Build the input element
    $html = sprintf(
        '<input type="text" name="%1$s" class="%2$s" placeholder="%3$s"%4$s aria-label="%3$s" />',
        esc_attr( $tag->name ),
        esc_attr( $class ),
        esc_attr( $placeholder ),
        $required
    );

    return $html;
}
?>
