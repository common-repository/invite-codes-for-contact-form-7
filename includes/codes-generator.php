<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function icfcf7_codes_generator( $prefix, $postfix, $code_length, $include_lowercase, $include_uppercase, $include_numbers, $begin_with_letter, $include_symbols, $no_similar, $no_duplicate, $no_sequential, $quantity ) {
    global $wpdb;
    $table_name = esc_sql( $wpdb->prefix . 'icfcf7' );
    $codes = array();
    $characters = '';
    $exclude_chars = array('I', 'l', '1', 'L', 'o', '0', 'O', '5', 'S', '6', 'G', '8', 'B');

    // Build character set
    if ($include_numbers) {
        $characters .= '0123456789';
    }
    if ($include_lowercase) {
        $characters .= 'abcdefghijklmnopqrstuvwxyz';
    }
    if ($include_uppercase) {
        $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    $symbols = $include_symbols ? '!@^-' : '';

    // Exclude similar characters if the option is selected
    if ($no_similar) {
        $characters = str_replace($exclude_chars, '', $characters);
    }

    // If no characters are available, return an empty result
    if (empty($characters)) {
        return array(); // No characters to generate from
    }

    // Convert characters to an array for easier manipulation
    $characters = str_split($characters);
    $symbol_positions = [];

    // Determine symbol positions based on length
    for ($i = 4; $i <= $code_length; $i += 4) {
        $symbol_positions[] = $i - 1; // Store zero-based index (4th position is index 3)
    }

    while (count($codes) < $quantity) {
        $code = '';
        $used_chars = array(); // Track used characters

        for ($i = 0; $i < $code_length; $i++) {
            // Check if the current position is a symbol position
            if ($include_symbols && in_array($i, $symbol_positions)) {
                $char = $symbols[array_rand(str_split($symbols))];
                $code .= $char;
                $used_chars[] = $char;
            } else {
                // Choose a random character from the remaining valid characters
                $valid_chars = $characters;

                // If the no duplicate characters option is selected, filter out used characters
                if ($no_duplicate) {
                    $valid_chars = array_diff($valid_chars, $used_chars);
                }

                // If no valid characters are left, break and regenerate the code
                if (empty($valid_chars)) {
                    break;
                }

                $char = $valid_chars[array_rand($valid_chars)];
                $code .= $char;
                $used_chars[] = $char;
            }
        }

        // Cache lookup to avoid repeated queries
        $cache_key = 'existing_code_' . md5($code);
        $existing_codes = wp_cache_get($cache_key);

        if (false === $existing_codes) {
            // Check for uniqueness in the database
            $existing_codes = $wpdb->get_col($wpdb->prepare("SELECT invite_code FROM {$table_name} WHERE invite_code = %s", $code));
            wp_cache_set($cache_key, $existing_codes, '', HOUR_IN_SECONDS);
        }

        // Ensure the code meets all requirements
        if (!in_array($code, $existing_codes)) {
            if ($begin_with_letter && !ctype_alpha($code[0])) {
                continue; // Code doesn't start with a letter
            }
            if ($no_sequential && icfcf7_has_sequential_chars($code)) {
                continue; // Code contains sequential characters
            }
            // Add prefix and postfix and save the code
            $codes[] = $prefix . $code . $postfix;
        }
    }

    return $codes;
}

function icfcf7_has_sequential_chars($code) {
    // Check for sequential characters (e.g., 'abc', '123')
    for ($i = 0; $i < strlen($code) - 1; $i++) {
        if (ord($code[$i]) + 1 === ord($code[$i + 1])) {
            return true; // Found sequential characters
        }
    }
    return false; // No sequential characters found
}

?>