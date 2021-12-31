<?php

/*
 * Retrieves all the membership level IDs of this site in an array.
 */
function emember_get_all_membership_level_ids() {
    global $wpdb;
    $table = WP_EMEMBER_MEMBERSHIP_LEVEL_TABLE;
    $query = "SELECT id FROM " . $table . " WHERE id != 1";
    return $wpdb->get_col($query);
}

/*
 * Retrieves all the membership level Names and IDs of this site in an object.
 * This function can be used to replace the dbAccess::findAll() function calls to retrive a levels list.
 */
function emember_get_all_membership_levels_list(){
    global $wpdb;
    $table = WP_EMEMBER_MEMBERSHIP_LEVEL_TABLE;
    $levels_array = array();

    $query = "SELECT alias, id FROM " . $table . " WHERE id != 1 ORDER BY id DESC";
    $levels = $wpdb->get_results($query);
    return $levels;
}

/*
 * Retrieves all the membership level Names and IDs of this site in an array.
 * Creates an array like the following with all the available levels.
 * Array ( [2] => Free Level, [3] => Silver Level, [4] => Gold Level )
 */
function emember_get_all_membership_levels_list_in_array(){
    global $wpdb;
    $table = WP_EMEMBER_MEMBERSHIP_LEVEL_TABLE;
    $levels_array = array();

    $query = "SELECT alias, id FROM " . $table . " WHERE id != 1";
    $levels = $wpdb->get_results($query);
    foreach ($levels as $level) {
        if(isset($level->id)){
            $levels_array[$level->id] = $level->alias;
        }
    }
    return $levels_array;
}

/*
 * Get membership level details for the given level ID.
 */
function emember_get_membership_level_row_by_id($level_id) {
    //Retrieves the membership level row for the given level ID.
    global $wpdb;
    $table = WP_EMEMBER_MEMBERSHIP_LEVEL_TABLE;
    $query = $wpdb->prepare("SELECT * FROM " . $table . " WHERE id =%d", $level_id);
    $level_row = $wpdb->get_row($query);
    return $level_row;
}

function emember_membership_level_id_exists($level_id) {
    //Returns true if the specified membership level exists in the system. Returns false if the level has been deleted (or doesn't exist).
    $all_level_ids = emember_get_all_membership_level_ids();
    if (in_array($level_id, $all_level_ids)) {
        //Valid level ID
        return true;
    } else {
        return false;
    }
}

function emember_get_membership_level_name_by_id($level_id) {
    //Retrieves the membership level name for the given level ID
    global $wpdb;
    $table = WP_EMEMBER_MEMBERSHIP_LEVEL_TABLE;
    $query = "SELECT alias FROM " . $table . " WHERE id= %s";
    $level_name = $wpdb->get_var($wpdb->prepare($query, $level_id));
    return $level_name; //Returns level name
}

/*
 * Retrieves all the membership level IDs of the logged-in member (primary and secondary) and returns an array with all hte IDs.
 * The code using this function should do an "is_array" check of the return values before using it.
 */
function emember_get_all_level_ids_of_logged_in_member() {
    $emember_auth = Emember_Auth::getInstance();
    if (!$emember_auth->isLoggedIn()) {
        //User is not logged in.
        return 'User is not logged in.';
    }

    $member_id = $emember_auth->getUserInfo('member_id');
    $all_levels = emember_get_all_level_ids_of_a_member($member_id);
    return $all_levels;
}

/*
 * Retrieves all the membership level IDs of a given member ID (primary and secondary) and returns an array with all hte IDs.
 * The code using this function should do an "is_array" check of the return values before using it.
 */
function emember_get_all_level_ids_of_a_member($member_id) {
    global $wpdb;
    $query = $wpdb->prepare('SELECT * FROM ' . WP_EMEMBER_MEMBERS_TABLE_NAME . ' WHERE member_id=%s', $member_id);
    $resultset = $wpdb->get_row($query);
    if (empty($resultset)) {
        return 'Member profile not found for the given member id.';
    }

    //Get the primary levels
    $primary_level = $resultset->membership_level;
    $pri_level_array = array($primary_level);

    $emember_config = Emember_Config::getInstance();
    if (!$emember_config->getValue('eMember_enable_secondary_membership')) {
        //Secondary levels feature is NOT enabled.
        return $pri_level_array;
    }

    //Get the secondary levels
    $secondary_levels = $resultset->more_membership_levels;
    $sec_levels_array = array();
    if (is_string($secondary_levels)) {
        $sec_levels_array = explode(',', $secondary_levels);
    }

    $all_levels_array = array_merge($pri_level_array, $sec_levels_array);
    $all_levels_array = array_map('trim', $all_levels_array); //Trime all the values
    $all_levels_array = array_filter($all_levels_array); //Remove any empty values
    return $all_levels_array;
}

function emember_get_more_membership_level_ids_of_a_member($member_id){
    //Retrieves all the additional membership level IDs for the given member_id. Returns an array containg the level IDs.
    global $wpdb;
    $query = $wpdb->prepare('SELECT * FROM ' . WP_EMEMBER_MEMBERS_TABLE_NAME . ' WHERE member_id=%s', $member_id);
    $resultset = $wpdb->get_row($query);
    if (empty($resultset)) {
        return 'Member profile not found for the given member id.';
    }

    //Get the secondary level IDs
    $secondary_levels = $resultset->more_membership_levels;
    $sec_levels_array = array();
    if (is_string($secondary_levels)) {
        $sec_levels_array = explode(',', $secondary_levels);
    }

    $more_levels_array = array_map('trim', $sec_levels_array); //Trime all the values
    $more_levels_array = array_filter($more_levels_array); //Remove any empty values

    return $more_levels_array;
}


function emember_get_more_membership_level_names_of_a_member($member_id){
    //Retrieves all the additional membership level names for the given member_id. Returns an array containg the level names.
    $more_level_names_arr = array();
    $more_level_ids_arr = emember_get_more_membership_level_ids_of_a_member($member_id);
    foreach($more_level_ids_arr as $sec_level_id){
	$more_level_names_arr[] = emember_get_membership_level_name_by_id($sec_level_id);
    }

    $more_level_names_arr = array_filter($more_level_names_arr); //Remove any empty values
    return $more_level_names_arr;
}

function emember_registered_email_exists($email) {
    global $wpdb;
    $query = $wpdb->prepare('SELECT * FROM ' . WP_EMEMBER_MEMBERS_TABLE_NAME . ' WHERE email=%s AND user_name != ""', $email);
    $resultset = $wpdb->get_row($query);
    if (empty($resultset)) {
        return false;
    }
    return $resultset->member_id;
}

function emember_email_exists_in_other_profile($email, $member_id) {
    //This function checks if the email address is used by any other profile on this site except the one specified via the $member_id parameter.
    global $wpdb;
    $query = $wpdb->prepare('SELECT * FROM ' . WP_EMEMBER_MEMBERS_TABLE_NAME . ' WHERE email=%s AND member_id != %d', $email, $member_id);
    $resultset = $wpdb->get_row($query);
    if (empty($resultset)) {
        return false;
    }
    return $resultset->member_id;
}

function emember_email_exists($email) {
    global $wpdb;
    $query = $wpdb->prepare('SELECT * FROM ' . WP_EMEMBER_MEMBERS_TABLE_NAME . ' WHERE email=%s', $email);
    $resultset = $wpdb->get_row($query);
    if (empty($resultset)) {
        return false;
    }
    return $resultset->member_id;
}

function emember_username_exists($user_name) {
    global $wpdb;
    $query = $wpdb->prepare('SELECT * FROM ' . WP_EMEMBER_MEMBERS_TABLE_NAME . ' WHERE user_name=%s', $user_name);
    $resultset = $wpdb->get_row($query);
    if (empty($resultset)) {
        return false;
    }
    return $resultset->member_id;
}

function emember_get_member_record_by_member_id($member_id) {
    global $wpdb;
    $query = $wpdb->prepare('SELECT * FROM ' . WP_EMEMBER_MEMBERS_TABLE_NAME . ' WHERE member_id=%d', $member_id);
    $resultset = $wpdb->get_row($query);
    if (empty($resultset)) {
        return false;
    }
    return $resultset;
}

function emember_get_member_by_username($user_name) {
    global $wpdb;
    $query = $wpdb->prepare('SELECT * FROM ' . WP_EMEMBER_MEMBERS_TABLE_NAME . ' WHERE user_name=%s', $user_name);
    $resultset = $wpdb->get_row($query);
    if (empty($resultset)) {
        return false;
    }
    return $resultset;
}

function emember_get_member_by_email($email) {
    global $wpdb;
    $query = $wpdb->prepare('SELECT * FROM ' . WP_EMEMBER_MEMBERS_TABLE_NAME . ' WHERE email=%s', $email);
    $resultset = $wpdb->get_row($query);
    if (empty($resultset)) {
        return false;
    }
    return $resultset;
}

function emember_generate_and_send_rego_completion_url($eMember_member_id){

    $member_record = dbAccess::find(WP_EMEMBER_MEMBERS_TABLE_NAME, ' member_id=\'' . $eMember_member_id . '\'');
    if ($member_record) {
        $md5_code = md5($member_record->reg_code);
        $separator = '?';
        $url = get_option('eMember_registration_page');
        if (empty($url)) {
            $errorMsg .= "<br />You need to specify the registration URL in the pages settings menu of this plugin.";
        } else {
            if (strpos($url, '?') !== false) {
                $separator = '&';
            }
            $reg_url = $url . $separator . 'member_id=' . $eMember_member_id . '&code=' . $md5_code;
        }

        $email = $member_record->email;
        $subject = get_option('eMember_email_subject');
        $body = get_option('eMember_email_body');
        $from_address = get_option('senders_email_address');
        $tags = array("{first_name}", "{last_name}", "{reg_link}");
        $vals = array($member_record->first_name, $member_record->last_name, $reg_url);
        $email_body = str_replace($tags, $vals, $body);
        $headers = 'From: ' . $from_address . "\r\n";
        wp_mail($email, $subject, $email_body, $headers);
        eMember_log_debug('Generate and send prompt to complete registration email. Sending email to: ' . $email, true);
    } else {
        eMember_log_debug('emember_generate_and_send_rego_completion_url(). No member profile found for ID: ' . $eMember_member_id, false);
    }
}