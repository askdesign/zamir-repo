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
    
    $filename = "member_list_" . date("Y-m-d_H-i", time());
    header('Content-Encoding: UTF-8');
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Description: File Transfer");
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-disposition: attachment; filename=" . $filename . ".csv");

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

    ob_start();
    $output_buffer = fopen("php://output", 'w');

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

    if ($emember_config->getValue('eMember_custom_field')) {
        $custom_fields = get_option('emember_custom_field_type');
        $custom_names = $custom_fields['emember_field_name'];
        $custom_types = $custom_fields['emember_field_type'];
        $custom_extras = $custom_fields['emember_field_extra'];
        if (count($custom_names) > 0)
            foreach ($custom_names as $i => $name) {
                $name = stripslashes($name);
                $customer_field_indices[$i] = emember_escape_custom_field($name);
                array_push($header, $name);
            }
    }
    fputcsv($output_buffer, $header);

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
        if ($emember_config->getValue('eMember_custom_field')) {
            $custom_values = $wpdb->get_col("select meta_value from " . $member_meta_tbl
                    . ' WHERE  user_id=' . $result->member_id . ' AND meta_key="custom_field"');
            $custom_values = unserialize(isset($custom_values[0]) ? $custom_values[0] : "");
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
    }
    fclose($output_buffer);
    $output = ob_get_clean();
    header("Content-Length: " . strlen($output));
    echo "\xEF\xBB\xBF";
    echo $output;
    exit;    
}