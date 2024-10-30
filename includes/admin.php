<?php
/**
 * Admin Functions for Invite Codes for Contact Form 7
 */

/**
 * Create submenu under Contact Form 7
 */
function icfcf7_menu() {
    // Ensure only admin users can see the submenu
    if (current_user_can('administrator')) {
        add_submenu_page(
            'wpcf7', // Parent slug
            __( 'Invite Codes', 'invite-codes-for-contact-form-7' ), // Page title
            __( 'Invite Codes', 'invite-codes-for-contact-form-7' ), // Menu title
            'manage_options', // Admin-only capability
            'invite-codes-for-contact-form-7', // Menu slug
            'icfcf7_admin_page' // Callback function
        );
    }

    // Register settings
    add_action('admin_init', 'icfcf7_register_settings');
}
add_action('admin_menu', 'icfcf7_menu');

add_action( 'admin_notices', 'icfcf7_display_admin_notices' );
function icfcf7_display_admin_notices() {
    // Check if the transient is set
    $notice_type = get_transient( 'icfcf7_admin_notice' );

    if ( $notice_type === 'error' ) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Validation message fields cannot be empty.', 'invite-codes-for-contact-form-7' ) . '</p></div>';
    } elseif ( $notice_type === 'success' ) {
        echo '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'invite-codes-for-contact-form-7' ) . '</p></div>';
    }

    // Delete the transient after displaying the message
    delete_transient( 'icfcf7_admin_notice' );
}


/**
 * Display the admin page for Invite Codes.
 */
function icfcf7_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        echo '<div class="error"><p>' . esc_html__('Only users with Administrator rights can access this page.', 'invite-codes-for-contact-form-7') . '</p></div>';
        return;
    }

    global $wpdb;
    $table_name = esc_sql( $wpdb->prefix . 'icfcf7' );

    // Processing form submissions for importing codes
    if ( isset( $_POST['icfcf7_import_codes'] ) && check_admin_referer( 'icfcf7_import_codes_action', 'icfcf7_import_codes_nonce' ) ) {
        $codes = isset($_POST['invite_codes']) ? explode( "\n", sanitize_textarea_field( wp_unslash( $_POST['invite_codes'] ) ) ) : [];
        $codes = array_map( 'trim', $codes );
        $codes = array_filter( $codes ); // Remove empty lines

        $max_usage_limit = isset($_POST['max_usage_limit']) ? sanitize_text_field( wp_unslash( $_POST['max_usage_limit'] ) ) : '';
        $expiry_date = isset($_POST['expiry_date']) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';

        // Prepare placeholders for the existing codes
        $placeholders = implode(',', array_fill(0, count($codes), '%s'));
        $cache_key = 'existing_codes_' . md5(implode('_', $codes));
        $existing_codes = wp_cache_get( $cache_key );

        if ( false === $existing_codes ) {
            $query = $wpdb->prepare("SELECT invite_code FROM {$table_name} WHERE invite_code IN ($placeholders)", ...$codes);
            $existing_codes = $wpdb->get_col($query);
            wp_cache_set( $cache_key, $existing_codes, '', HOUR_IN_SECONDS );
        }

        if ( empty( $codes ) ) {
            echo '<div class="error"><p>' . esc_html__('Invisible codes are difficult to import. Enter at least on invite code.', 'invite-codes-for-contact-form-7') . '</p></div>';
        } elseif ( ! empty( $existing_codes ) ) {
            echo '<div class="error"><p>' . esc_html__('One or more invite codes you want to import already exist.', 'invite-codes-for-contact-form-7') . '</p></div>';
        } else {
            foreach ( $codes as $code ) {
                $wpdb->insert(
                    $table_name,
                    [
                        'invite_code'     => $code,
                        'times_used'      => 0,
                        'max_usage_limit' => $max_usage_limit,
                        'expiration_date' => $expiry_date ? gmdate( 'Y-m-d H:i:s', strtotime( $expiry_date ) ) : null
                    ]
                );
            }
            echo '<div class="updated"><p>' . esc_html__( 'Invite codes imported successfully.', 'invite-codes-for-contact-form-7' ) . '</p></div>';
        }
    }

    // Deleting invite codes
    if ( isset( $_POST['icfcf7_delete_code'] ) && check_admin_referer( 'icfcf7_delete_code_action', 'icfcf7_delete_code_nonce' ) ) {
        $code_id = isset($_POST['code_id']) ? intval( wp_unslash( $_POST['code_id'] ) ) : 0;
        if ($code_id) {
            $wpdb->delete( $table_name, [ 'id' => $code_id ] );
            echo '<div class="updated"><p>' . esc_html__( 'Invite code deleted successfully.', 'invite-codes-for-contact-form-7' ) . '</p></div>';
        }
    }

	// Update Expiry Date code
    if ( isset( $_POST['icfcf7_update_expiry'] ) && check_admin_referer( 'icfcf7_update_expiry_action', 'icfcf7_update_expiry_nonce' ) ) {
        $code_id = isset($_POST['code_id']) ? intval( wp_unslash( $_POST['code_id'] ) ) : 0;
		$new_expiry_date  = isset($_POST['new_expiry_date']) ? sanitize_text_field( wp_unslash( $_POST['new_expiry_date'] ) ) : '';
        $wpdb->update( $table_name, [ 'expiration_date' => $new_expiry_date ? gmdate( 'Y-m-d H:i:s', strtotime( $new_expiry_date ) ) : null ], [ 'id' => $code_id ] );
        echo '<div class="updated"><p>' . esc_html__( 'Expiry date updated successfully.', 'invite-codes-for-contact-form-7' ) . '</p></div>';
    }

    // Deleting all invite codes
    if ( isset( $_POST['icfcf7_delete_all_codes'] ) && check_admin_referer( 'icfcf7_delete_all_codes_action', 'icfcf7_delete_all_codes_nonce' ) ) {
        $wpdb->query("TRUNCATE TABLE {$table_name}");
        echo '<div class="updated"><p>' . esc_html__( 'All invite codes successfully deleted.', 'invite-codes-for-contact-form-7' ) . '</p></div>';
    }

    // Pagination and fetching codes with caching
    $order_by = isset($_GET['order_by']) ? sanitize_sql_orderby(wp_unslash($_GET['order_by'])) : 'id';
	$order = ( isset($_GET['order']) && strtolower(sanitize_text_field(wp_unslash($_GET['order']))) === 'asc' ) ? 'desc' : 'asc';
    $per_page = isset($_GET['per_page']) ? intval(wp_unslash($_GET['per_page'])) : 25;
    $page = isset($_GET['paged']) ? intval(wp_unslash($_GET['paged'])) : 1;
    $offset = ( $page - 1 ) * $per_page;

    // Fetch codes from cache if available
    $cache_key = 'icfcf7_' . md5($order_by . $order . $per_page . $page);
    $codes = wp_cache_get( $cache_key );

    if ( false === $codes ) {
        // Fetch total codes
        $total_codes = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

        // Prepare SQL query with pagination
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY {$order_by} {$order} LIMIT %d, %d", $offset, $per_page);
        $codes = $wpdb->get_results($query);
        
        // Cache the results
        wp_cache_set( $cache_key, $codes, '', HOUR_IN_SECONDS );
    }

    // Display the admin page
    ?>
    <div class="wrap" style="width: 90%">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Invite Codes', 'invite-codes-for-contact-form-7' ); ?></h1>
        <nav class="nav-tab-wrapper">
            <a href="#codes" class="nav-tab nav-tab-active" id="tab-codes"><?php esc_html_e( 'Codes', 'invite-codes-for-contact-form-7' ); ?></a>
            <a href="#import" class="nav-tab" id="tab-import"><?php esc_html_e( 'Import', 'invite-codes-for-contact-form-7' ); ?></a>
            <a href="#export" class="nav-tab" id="tab-export"><?php esc_html_e( 'Export', 'invite-codes-for-contact-form-7' ); ?></a>
            <a href="#generator" class="nav-tab" id="tab-generator"><?php esc_html_e( 'Codes Generator', 'invite-codes-for-contact-form-7' ); ?></a>
            <a href="#settings" class="nav-tab" id="tab-settings"><?php esc_html_e( 'Settings', 'invite-codes-for-contact-form-7' ); ?></a>
            <a href="#instructions" class="nav-tab" id="tab-instructions"><?php esc_html_e( 'Instructions', 'invite-codes-for-contact-form-7' ); ?></a>
        </nav>
        <div id="codes" class="tab-content">
            <h2><?php esc_html_e( 'Invite Codes', 'invite-codes-for-contact-form-7' ); ?></h2>
            <table class="wp-list-table widefat auto striped table-view-list">
                <thead>
                    <tr>
                        <th><a href="?page=icfcf7&order_by=id&order=<?php echo esc_attr( $order ); ?>"><?php esc_html_e( 'ID', 'invite-codes-for-contact-form-7' ); ?></a></th>
						<th><a href="?page=icfcf7&order_by=invite_code&order=<?php echo esc_attr( $order ); ?>"><?php esc_html_e( 'Invite Code', 'invite-codes-for-contact-form-7' ); ?></a></th>
						<th><a href="?page=icfcf7&order_by=times_used&order=<?php echo esc_attr( $order ); ?>"><?php esc_html_e( 'Times Used', 'invite-codes-for-contact-form-7' ); ?></a></th>
						<th><a href="?page=icfcf7&order_by=max_usage_limit&order=<?php echo esc_attr( $order ); ?>"><?php esc_html_e( 'Max Usage', 'invite-codes-for-contact-form-7' ); ?></a></th>
						<th><a href="?page=icfcf7&order_by=expiration_date&order=<?php echo esc_attr( $order ); ?>"><?php esc_html_e( 'Expiration Date', 'invite-codes-for-contact-form-7' ); ?></a></th>
						<th><?php esc_html_e( 'Actions', 'invite-codes-for-contact-form-7' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $codes ) ) : ?>
                        <?php foreach ( $codes as $code ) : ?>
                            <tr>
                                <td><?php echo esc_html( $code->id ); ?></td>
                                <td><?php echo esc_html( $code->invite_code ); ?></td>
                                <td><?php echo esc_html( $code->times_used ); ?></td>
                                <td><?php echo esc_html( $code->max_usage_limit ); ?></td>
                                <td><?php echo $code->expiration_date ? esc_html( gmdate( 'd-m-Y', strtotime( $code->expiration_date ) ) ) : '-'; ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field( 'icfcf7_delete_code_action', 'icfcf7_delete_code_nonce' ); ?>
                                        <input type="hidden" name="code_id" value="<?php echo esc_attr( $code->id ); ?>">
                                        <input type="submit" name="icfcf7_delete_code" value="<?php esc_attr_e( 'Delete', 'invite-codes-for-contact-form-7' ); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this invite code?', 'invite-codes-for-contact-form-7' ); ?>');">
                                    </form>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field( 'icfcf7_update_expiry_action', 'icfcf7_update_expiry_nonce' ); ?>
                                        <input type="hidden" name="code_id" value="<?php echo esc_attr( $code->id ); ?>">
                                        <input type="date" name="new_expiry_date">
                                        <input type="submit" name="icfcf7_update_expiry" value="<?php esc_attr_e( 'Update Expiry Date', 'invite-codes-for-contact-form-7' ); ?>" class="button button-secondary">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'No invite codes found.', 'invite-codes-for-contact-form-7' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="tablenav" style="display: flex; justify-content: space-between; align-items: center;">
                <form method="get">
                    <input type="hidden" name="page" value="icfcf7">
                    <label for="per_page"><?php esc_html_e( 'Entries per page:', 'invite-codes-for-contact-form-7' ); ?></label>
                    <select name="per_page" id="per_page" onchange="this.form.submit()">
                        <option value="25" <?php selected( $per_page, 25 ); ?>>25</option>
                        <option value="50" <?php selected( $per_page, 50 ); ?>>50</option>
                        <option value="100" <?php selected( $per_page, 100 ); ?>>100</option>
                        <option value="250" <?php selected( $per_page, 250 ); ?>>250</option>
                        <option value="500" <?php selected( $per_page, 500 ); ?>>500</option>
                    </select>
                </form>
				<?php $total_pages = ceil( $total_codes / $per_page ); ?>
				<div class="tablenav-pages">
					<?php
					echo wp_kses_post( paginate_links( array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => __( '&laquo;', 'invite-codes-for-contact-form-7' ),
						'next_text' => __( '&raquo;', 'invite-codes-for-contact-form-7' ),
						'total'     => $total_pages,
						'current'   => $page,
					) ) );
					?>
				</div>
            </div>
            <br>
            <form method="post">
                <?php wp_nonce_field( 'icfcf7_delete_all_codes_action', 'icfcf7_delete_all_codes_nonce' ); ?>
                <input type="submit" name="icfcf7_delete_all_codes" value="<?php esc_attr_e( 'Delete All Codes', 'invite-codes-for-contact-form-7' ); ?>" class="button button-primary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete all invite codes?', 'invite-codes-for-contact-form-7' ); ?>');">
            </form>
        </div>

        <div id="import" class="tab-content" style="display:none;">
            <h2><?php esc_html_e( 'Import Invite Codes', 'invite-codes-for-contact-form-7' ); ?></h2>
            <form method="post">
                <?php wp_nonce_field( 'icfcf7_import_codes_action', 'icfcf7_import_codes_nonce' ); ?>
                <table class="form-table-import">
                    <tr>
                        <th class="row"><label for="invite_codes"><?php esc_html_e('Invite codes', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <th class="desc"><label for="description"><?php esc_html_e('Description', 'invite-codes-for-contact-form-7'); ?></label></th>
                    </tr>
                    <tr>
                        <td class="input"><textarea name="invite_codes" rows="10" cols="25" placeholder="<?php esc_attr_e( 'Enter one invite code per line', 'invite-codes-for-contact-form-7' ); ?>"></textarea></td>
                        <td class="desc"><?php 
                            // translators: placeholder description
                            printf( esc_html__('Paste all the codes you want to use in the form here. %s Enter one invite code per line.', 'invite-codes-for-contact-form-7'), '<br>'); 
                        ?></td>
                    </tr>
                    <tr>
                        <th class="row"><label for="max_usage_limit"><?php esc_html_e('Maximum usage', 'invite-codes-for-contact-form-7'); ?></label></th>
                    </tr>
                    <tr>
                        <td class="input"><input type="number" name="max_usage_limit" id="max_usage_limit" min="0" max="999" value="1"></td>
                        <td class="desc"><?php esc_html_e('Number of times the code can be used. Use 0 for unlimited usage.', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th class="row"><label for="expiry_date"><?php esc_html_e( 'Expiration Date (optional):', 'invite-codes-for-contact-form-7' ); ?></label></th>
                    </tr>
                    <tr>
                        <td class="input"><input type="date" name="expiry_date" id="expiry_date"><br><br></td>
                        <td class="desc"><?php esc_html_e('Expiry date of the code. Leave empty if no expiry date.', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                </table>
                <input type="submit" name="icfcf7_import_codes" value="<?php esc_attr_e( 'Import Invite Codes', 'invite-codes-for-contact-form-7' ); ?>" class="button button-primary">
            </form>
        </div>
		<?php
		if ( isset( $_POST['icfcf7_export_codes'] ) ) {
			icfcf7_export_invite_codes(); // Call the export function when the form is submitted
		}
		?>

		<div id="export" class="tab-content" style="display:none;">
			<h2><?php esc_html_e( 'Export Invite Codes', 'invite-codes-for-contact-form-7' ); ?></h2>
			<p><?php esc_html_e( 'Click the button below to export the codes to a CSV file.', 'invite-codes-for-contact-form-7' ); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'icfcf7_export_codes_action', 'icfcf7_export_codes_nonce' ); ?>
				<input type="submit" name="icfcf7_export_codes" value="<?php esc_attr_e( 'Export Invite Codes', 'invite-codes-for-contact-form-7' ); ?>" class="button button-primary">
			</form>

			<h3><?php esc_html_e( 'Exported Files', 'invite-codes-for-contact-form-7' ); ?></h3>
			<?php icfcf7_list_exported_files(); ?>
		</div>


        <div id="generator" class="tab-content" style="display:none;">
            <h2><?php esc_html_e('Codes Generator', 'invite-codes-for-contact-form-7'); ?></h2>
            <p>
                1. <?php esc_html_e('Select your options below', 'invite-codes-for-contact-form-7'); ?><br>
                2. <?php esc_html_e('Generate the codes', 'invite-codes-for-contact-form-7'); ?><br>
                3. <?php esc_html_e('Copy codes to clipboard and import them via Import tab', 'invite-codes-for-contact-form-7'); ?><br>
            </p>
            <form method="post" action="" id="codes-generator-form">
                <?php wp_nonce_field('icfcf7_codes_generator', 'icfcf7_codes_generator_nonce'); ?>
                <input type="hidden" name="active_tab" id="active_tab" value="">
                
                <table class="form-table-cg">
                    <tr>
                        <th scope="row"><label for="prefix"><?php esc_html_e('Prefix', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input"><input type="text" name="prefix" id="prefix" style="width: 120px" maxlength="10"></td>
                        <td class="desc"><?php esc_html_e('Starting set of characters for every code. e.g.: DONUT- will generate DONUT-A32D, DONUT-D5NZ', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="postfix"><?php esc_html_e('Postfix', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input"><input type="text" name="postfix" id="postfix" style="width: 120px" maxlength="10"></td>
                        <td class="desc"><?php esc_html_e('Ending set of characters for every code. e.g.: -DONUT will generate A32D-DONUT, D5NZ-DONUT', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="code_length"><?php esc_html_e('Unique Code Length', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input">
                            <select name="code_length" id="code_length" style="width: 120px">
                                <?php for ( $i = 4; $i <= 20; $i++ ) : ?>
                                    <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, 6 ); ?>><?php echo esc_html( $i ); ?></option>
                                <?php endfor; ?>
                            </select>
                        </td>
                        <td class="desc"><?php esc_html_e('Length of every unique code (excl. prefix/postfix)', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="include_lowercase"><?php esc_html_e('Include Lowercase Characters', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input"><input type="checkbox" name="include_lowercase" id="include_lowercase" value="1"></td>
                        <td class="desc"><?php esc_html_e('e.g. abcdefgh', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="include_uppercase"><?php esc_html_e('Include Uppercase Characters', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input"><input type="checkbox" name="include_uppercase" id="include_uppercase" value="1" checked></td>
                        <td class="desc"><?php esc_html_e('e.g. ABCDEFGH', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="include_numbers"><?php esc_html_e('Include Numbers', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input"><input type="checkbox" name="include_numbers" id="include_numbers" value="1" checked></td>
                        <td class="desc"><?php esc_html_e('e.g. 123456', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="begin_with_letter"><?php esc_html_e('Begin With A Letter', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input"><input type="checkbox" name="begin_with_letter" id="begin_with_letter" value="1" checked></td>
                        <td class="desc"><?php esc_html_e('Don\'t begin with a number or symbol', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="include_symbols"><?php esc_html_e('Include Symbols', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input"><input type="checkbox" name="include_symbols" id="include_symbols" value="1"></td>
                        <td class="desc"><?php esc_html_e('Include symbols: !@^', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="no_similar"><?php esc_html_e('No Similar Characters', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input"><input type="checkbox" name="no_similar" id="no_similar" value="1" checked></td>
                        <td class="desc"><?php esc_html_e('Don\'t use characters like i, l, 1, L, o, 0, O, etc.', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="no_duplicate"><?php esc_html_e('No Duplicate Characters', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input"><input type="checkbox" name="no_duplicate" id="no_duplicate" value="1" checked></td>
                        <td class="desc"><?php esc_html_e('Don\'t use the same character more than once', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="no_sequential"><?php esc_html_e('No Sequential Characters', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input"><input type="checkbox" name="no_sequential" id="no_sequential" value="1" checked></td>
                        <td class="desc"><?php esc_html_e('Don\'t use sequential characters, e.g. abc, 789', 'invite-codes-for-contact-form-7'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quantity"><?php esc_html_e('Quantity', 'invite-codes-for-contact-form-7'); ?></label></th>
                        <td class="input">
                            <select name="quantity" id="quantity" style="width: 120px">
                                <?php
                                $quantities = array( 1, 2, 3, 4, 5, 10, 20, 30, 40, 50, 100, 200, 300, 500, 1000 );
                                foreach ( $quantities as $quantity ) : ?>
                                    <option value="<?php echo esc_attr( $quantity ); ?>" <?php selected( $quantity, 10 ); ?>><?php echo esc_html( $quantity ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="desc"></td>
                    </tr>
                </table>
                <input type="submit" name="generate_codes" value="<?php esc_attr_e('Generate Codes', 'invite-codes-for-contact-form-7'); ?>" class="button button-primary" style="width:200px">
            </form>

            <?php
            // Check if the form has been submitted to generate codes
            if ( isset( $_POST['icfcf7_codes_generator_nonce'] ) ) {
                // Unsplash the nonce and sanitize it immediately
                $nonce = sanitize_text_field(wp_unslash($_POST['icfcf7_codes_generator_nonce'])); // Unsplash and sanitize

                // Verify the nonce
                if ( wp_verify_nonce( $nonce, 'icfcf7_codes_generator' ) ) {
                    // Use updated options to generate codes
                    $prefix = isset($_POST['prefix']) ? sanitize_text_field( wp_unslash( $_POST['prefix'] ) ) : '';
                    $postfix = isset($_POST['postfix']) ? sanitize_text_field( wp_unslash( $_POST['postfix'] ) ) : '';
                    $code_length = isset($_POST['code_length']) ? intval( wp_unslash( $_POST['code_length'] ) ) : 6;
                    $include_lowercase = isset($_POST['include_lowercase']);
                    $include_uppercase = isset($_POST['include_uppercase']);
                    $include_numbers = isset($_POST['include_numbers']);
                    $begin_with_letter = isset($_POST['begin_with_letter']);
                    $include_symbols = isset($_POST['include_symbols']);
                    $no_similar = isset($_POST['no_similar']);
                    $no_duplicate = isset($_POST['no_duplicate']);
                    $no_sequential = isset($_POST['no_sequential']);
                    $quantity = isset($_POST['quantity']) ? intval( wp_unslash( $_POST['quantity'] ) ) : 10;

                    $codes = icfcf7_codes_generator($prefix, $postfix, $code_length, $include_lowercase, $include_uppercase, $include_numbers, $begin_with_letter, $include_symbols, $no_similar, $no_duplicate, $no_sequential, $quantity);
                
                    // Display the generated codes
                    if (!empty($codes)) {
                        echo '<hr style="width:30%;text-align:left;margin-left:0">';
                        echo '<h2>' . esc_html__('Generated Codes', 'invite-codes-for-contact-form-7') . '</h2>';
                        echo '<textarea rows="10" cols="25" id="GeneratedCodes" readonly>' . esc_textarea(implode("\n", $codes)) . '</textarea>';
                        ?>
                        <br><br>
                        <button onclick="copyGeneratedCodes()" class="button button-primary" style="width:200px"><?php esc_html_e('Copy Codes to Clipboard', 'invite-codes-for-contact-form-7'); ?></button>
                        <?php
                    } else {
                        echo '<p>' . esc_html__('No codes generated. Please check your settings.', 'invite-codes-for-contact-form-7') . '</p>';
                    }
                } else {
                    // Optional: Handle nonce verification failure
                    echo '<div class="error"><p>' . esc_html__('Nonce verification failed. Please try again.', 'invite-codes-for-contact-form-7') . '</p></div>';
                }
            }
            ?>

        </div>
        
        <div id="settings" class="tab-content" style="display:none;">
            <h2><?php esc_html_e( 'Settings', 'invite-codes-for-contact-form-7' ); ?></h2>
            <!--suppress HtmlUnknownTarget -->
            <form method="post" action="options.php">
                <?php
                settings_fields( 'icfcf7_settings_group' );
                do_settings_sections( 'icfcf7_settings' );
                submit_button( __( 'Save Settings', 'invite-codes-for-contact-form-7' ) );
                ?>
            </form>
        </div>
        <div id="instructions" class="tab-content" style="display:none;">
            <h2><?php esc_html_e( 'Instructions', 'invite-codes-for-contact-form-7' ); ?></h2>
            <h1><strong><?php esc_html_e( 'Guide for Using the "Invite Codes for Contact Form 7" Plugin', 'invite-codes-for-contact-form-7' ); ?></strong></h1>
            <p><?php esc_html_e( 'The "Invite Codes for Contact Form 7" plugin adds invite code functionality to Contact Form 7. This guide will walk you through using the plugin, both on the backend (admin) and frontend (user interface).', 'invite-codes-for-contact-form-7' ); ?></p>
            
            <h2><strong><?php esc_html_e( 'Backend Usage (Admin Interface)', 'invite-codes-for-contact-form-7' ); ?></strong></h2>
            
            <h3><strong><?php esc_html_e( 'Codes', 'invite-codes-for-contact-form-7' ); ?></strong></h3>
            <p><?php esc_html_e( 'The Codes tab displays a list of all created codes. Here you can see the status of each code and the expiration date. In this screen, you can delete individual codes one by one and update their expiration dates.', 'invite-codes-for-contact-form-7' ); ?></p>
            
            <h3><strong><?php esc_html_e( 'Import', 'invite-codes-for-contact-form-7' ); ?></strong></h3>
            <p><?php esc_html_e( 'The Import tab allows you to import codes. Enter one code per line, choose an expiration date (optional), and click Import Invite Codes. The import module is tested up to 1000 codes at a time.', 'invite-codes-for-contact-form-7' ); ?></p>
            
            <h3><strong><?php esc_html_e( 'Export', 'invite-codes-for-contact-form-7' ); ?></strong></h3>
            <p><?php esc_html_e( 'Use this function to export imported codes to a CSV file. The export also includes the status and expiration date for each code.', 'invite-codes-for-contact-form-7' ); ?></p>
            
            <h3><strong><?php esc_html_e( 'Codes Generator', 'invite-codes-for-contact-form-7' ); ?></strong></h3>
            <p>
                <?php esc_html_e( 'With the Codes Generator, you can generate unique codes in one go based on selected specifications.', 'invite-codes-for-contact-form-7' ); ?>
            </p>
            
            <h3><strong><?php esc_html_e( 'Settings', 'invite-codes-for-contact-form-7' ); ?></strong></h3>
            <p><?php esc_html_e( 'Here you can customize notifications displayed on the form and set whether the form should treat entered codes as case-sensitive.', 'invite-codes-for-contact-form-7' ); ?></p>
            
            <h2><strong><?php esc_html_e( 'Frontend Usage (Contact Form 7)', 'invite-codes-for-contact-form-7' ); ?></strong></h2>
            
            <h3><strong><?php esc_html_e( 'Using Invite Codes in Forms', 'invite-codes-for-contact-form-7' ); ?></strong></h3>
            <p>
                <?php 
                // translators: placeholder shortcode
                printf( esc_html__('Add an invite code field to your Contact Form 7 form by using the shortcode %s.', 'invite-codes-for-contact-form-7'), '<code>[invite_code]</code>' ); 
                ?>
                <?php esc_html_e( 'The field will automatically be validated upon form submission.', 'invite-codes-for-contact-form-7' ); ?>
                <?php esc_html_e( 'Ensure that the entered code matches an existing code in the database to successfully submit the form.', 'invite-codes-for-contact-form-7' ); ?>
            </p>
            
            <h3><strong><?php esc_html_e( 'Example Shortcodes', 'invite-codes-for-contact-form-7' ); ?></strong></h3>
            <code>[invite_code]</code>
            <p><?php esc_html_e( 'Use without a label and required input, and default placeholder "Enter your invite code".', 'invite-codes-for-contact-form-7' ); ?><br></p>
            
            <code>Unique code[text* invite_code placeholder "Enter your unique code"]</code>
            <p><?php esc_html_e( 'Use with a label, required input, and custom placeholder.', 'invite-codes-for-contact-form-7' ); ?></p>
        </div>
    </div>
    <?php
}
?>
