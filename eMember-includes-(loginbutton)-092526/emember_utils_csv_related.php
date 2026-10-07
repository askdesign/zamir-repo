<?php

function export_members_to_csv() {
    //This function is run at 'admin_init' time using the 'admin_init' hook.
    
    global $wpdb;
    
    //Check and Export members to a CSV file.
    if (isset($_POST['wp_emember_export'])) {
        
        $wpememmeta = new WPEmemberMeta();
        $member_meta_tbl = $wpememmeta->get_table('member_meta');
        $member_table = $wpememmeta->get_table('member');
        $ret_member_db = $wpdb->get_results("SELECT * FROM $member_table ORDER BY member_id DESC", OBJECT);
        
        //Stream the rows to a CSV file.
        stream_given_members_rows_to_csv( $ret_member_db );
    }
    
    //Check and export members of the specified membership level to a CSV file.
    if (isset($_POST['wp_emember_export_from_level'])) {
        //Check that a valid membership level ID value was entered.
        $wp_emember_export_from_level_id = isset($_REQUEST['wp_emember_export_from_level_id']) ? sanitize_text_field($_REQUEST['wp_emember_export_from_level_id']) : "";
        if(!is_numeric($wp_emember_export_from_level_id) || empty($wp_emember_export_from_level_id)){
            wp_die('Error! The option to export data of members form a level requires a numeric membership level ID value. Please enter a level ID and then try again.');
            return;
        }
        
        $wpememmeta = new WPEmemberMeta();
        $member_meta_tbl = $wpememmeta->get_table('member_meta');
        $member_table = $wpememmeta->get_table('member');
        $ret_member_db = $wpdb->get_results("SELECT * FROM $member_table WHERE membership_level = '$wp_emember_export_from_level_id' ORDER BY member_id DESC", OBJECT);         

        //Stream the rows to a CSV file.
        stream_given_members_rows_to_csv( $ret_member_db );
    }

}

function stream_given_members_rows_to_csv( $ret_member_db ){
    global $wpdb;

    $wpememmeta = new WPEmemberMeta();
    $member_meta_tbl = $wpememmeta->get_table('member_meta');
    //$member_table = $wpememmeta->get_table('member');

    //Discard any stray output (e.g. notices/whitespace from other plugins) so it can't block the headers below.
    if (ob_get_length()) {
        ob_end_clean();
    }
    if (headers_sent($sent_file, $sent_line)) {
        error_log("WP-eMember CSV export aborted: headers already sent by $sent_file on line $sent_line");
        wp_die('Error! Could not start the file download because the server already sent output to the browser (' . esc_html($sent_file) . ' line ' . esc_html($sent_line) . '). Please check your PHP error log and disable/deactivate other plugins that may be printing output early, then try again.');
    }

    //Large member lists can take a while to generate/transfer. Remove the default execution time limit and
    //raise the memory limit so the export isn't killed by PHP mid-stream - that's what causes the browser to
    //report a failed/interrupted download instead of receiving a complete (or clearly failed) file.
    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }
    if (function_exists('wp_raise_memory_limit')) {
        wp_raise_memory_limit('admin');
    }
    //Avoid PHP/webserver level buffering so rows reach the browser as they're written instead of all at once.
    @ini_set('zlib.output_compression', 'Off');

    $filename = "member_list_" . date("Y-m-d_H-i", time());
    header('Content-Encoding: UTF-8');
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Description: File Transfer");
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-disposition: attachment; filename=" . $filename . ".csv");
    //Tell reverse proxies (e.g. Nginx) not to buffer the response so streaming actually reaches the browser early.
    header("X-Accel-Buffering: no");

    //Filter that can be used to override the member CSV export completely from an addon
    $output = apply_filters('emember_export_csv', '');
    if (!empty($output)){
        header("Content-Length: " . strlen($output));
        echo "\xEF\xBB\xBF";
        echo $output;
        exit;
    }

    $emember_config = Emember_Config::getInstance();
    $wp_user_integration_enabled = $emember_config->getValue('eMember_create_wp_user');

    //Write rows straight to the response stream instead of buffering the whole file in memory (ob_start()),
    //so large member lists can't exhaust memory_limit and the browser starts receiving data immediately.
    $output_buffer = fopen("php://output", 'w');
    fwrite($output_buffer, "\xEF\xBB\xBF");

    $customer_field_indices = array();

    $header = array("Member ID", "Username", "First Name", "Last Name",
        "Street", "City", "State", "ZIP Code", "Country",
        "Email Address", "Phone Number", "Membership Level", "Account State", "Membership Start", "Membership Expiry",
        "Member Since", "Last Accessed", "Last Accessed From IP", "Additional Membership Levels",
        "Gender", "Referrer", "Reg Code", "Txn ID", "Subscr ID", "Company", "Notes");

    if ($wp_user_integration_enabled){
        //WP user integration enabled. Output the WP User ID also
        array_push($header, 'WP User ID');
    }

    $custom_fields_enabled = $emember_config->getValue('eMember_custom_field');
    if ($custom_fields_enabled) {
        $custom_fields = get_option('emember_custom_field_type');
        if (is_array($custom_fields)) {
            $custom_names = isset($custom_fields['emember_field_name']) && is_array($custom_fields['emember_field_name']) ? $custom_fields['emember_field_name'] : array();
            $custom_types = isset($custom_fields['emember_field_type']) && is_array($custom_fields['emember_field_type']) ? $custom_fields['emember_field_type'] : array();
            $custom_extras = isset($custom_fields['emember_field_extra']) && is_array($custom_fields['emember_field_extra']) ? $custom_fields['emember_field_extra'] : array();
            if (count($custom_names) > 0) {
                foreach ($custom_names as $i => $name) {
                    $name = stripslashes($name);
                    $customer_field_indices[$i] = emember_escape_custom_field($name);
                    array_push($header, $name);
                }
            }
        }
    }
    fputcsv($output_buffer, $header);

    //Fetch every member's custom field data in a single query instead of one query per member (N+1). On sites
    //with large member lists this N+1 pattern was a major contributor to the export timing out.
    $custom_field_map = array();
    if ($custom_fields_enabled && count($customer_field_indices) > 0) {
        $custom_rows = $wpdb->get_results("SELECT user_id, meta_value FROM $member_meta_tbl WHERE meta_key = 'custom_field'", OBJECT);
        foreach ($custom_rows as $custom_row) {
            $custom_field_map[$custom_row->user_id] = $custom_row->meta_value;
        }
    }

    $membership_levels = Emember_Level_Collection::get_instance();
    $order = array('member_id', 'user_name', 'first_name', 'last_name', 'address_street',
        'address_city', 'address_state', 'address_zipcode',
        'country', 'email', 'phone', 'alias', 'account_state', 'subscription_starts',
        'expiry_date', 'member_since', 'last_accessed', 'last_accessed_from_ip', 'more_membership_levels',
        'gender', 'referrer', 'reg_code', 'txn_id', 'subscr_id', 'company_name', 'notes',
    );
    if ($wp_user_integration_enabled){
        //WP user integration enabled. Handle outputting of the WP User ID value.
        array_push($order, 'wp_user_id');
    }

    $row_count = 0;
    foreach ($ret_member_db as $result) {
        $level = $membership_levels->get_levels($result->membership_level);
        $data = array();
        foreach ($order as $key) {
            $value = '';
            switch ($key) {
                case 'alias':
                    //Primary level
                    $value = (empty($level) || is_array($level))? '' : escape_csv_value(stripslashes($level->get('alias')));
                    break;
                case 'more_membership_levels':
                    //Additional levels
                    if (!$emember_config->getValue('eMember_enable_secondary_membership')) {
                        //Secondary levels feature is disabled.
                        $value = '';
                    } else {
                        $member_id = $result->member_id;
                        $names = emember_get_more_membership_level_names_of_a_member($member_id);
                        $sec_level_names_string = implode(", ", $names);
                        $value = $sec_level_names_string;
                    }
                    break;
                case 'expiry_date':
                    $value = emember_get_expiry_by_member_id($result->member_id);
                    $value = escape_csv_value(stripslashes($value));
                    break;
                case 'wp_user_id':
                    $wp_user_id = username_exists($result->user_name);
                    if($wp_user_id){
                        $value = $wp_user_id;
                    }
                    break;
                default:
                    $value = escape_csv_value(stripslashes($result->$key));
                    break;
            }
            array_push($data, $value);
        }
        if ($custom_fields_enabled) {
            $custom_values = unserialize(isset($custom_field_map[$result->member_id]) ? $custom_field_map[$result->member_id] : "");
            foreach ($customer_field_indices as $i => $n) {
                $v = isset($custom_values[$n]) ? $custom_values[$n] : "";
                if ($custom_types[$i] == 'dropdown') {
                    $m = explode(",", stripslashes($custom_extras[$i]));
                    $e = array();
                    foreach ($m as $k) {
                        $k = explode("=>", $k);
                        $e[$k[0]] = $k[1];
                    }

                    $v = isset($e[$v]) ? $e[$v] : "";
                }
                $value = escape_csv_value(stripslashes($v));
                array_push($data, $value);
            }
        }
        fputcsv($output_buffer, $data);

        //Periodically flush so rows reach the browser as they're generated instead of only at the very end.
        $row_count++;
        if ($row_count % 500 === 0) {
            if (ob_get_length()) {
                ob_flush();
            }
            flush();
        }
    }
    fclose($output_buffer);
    exit;
}