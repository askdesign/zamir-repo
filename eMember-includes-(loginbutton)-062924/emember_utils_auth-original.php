<?php

function wp_emem_logout() {
    $auth = Emember_Auth::getInstance();
    if ($auth->isLoggedIn())
        $auth->logout();
}

function update_account_status($username) {
    global $wpdb;
    if ($username) {
        $member_table = WP_EMEMBER_MEMBERS_TABLE_NAME;
        $ret_member_db = $wpdb->get_row("SELECT * FROM $member_table WHERE user_name='" . esc_sql($username) . "'", OBJECT);
        if ($ret_member_db) {
            $wp_user = get_user_by('login', $username);
            if ($wp_user) {
                $new_capabilities = array();
                $account_states = array('expired', '', 'inactive', 'pending', 'unsubscribed');
                $modified = false;
                if ($wp_user->wp_capabilities) {
                    foreach ($wp_user->wp_capabilities as $role => $state) {
                        if ($role == 'administrator') {
                            $new_capabilities[$role] = $state;
                        }
                        if (in_array($ret_member_db->account_state, $account_states)) {
                            if (is_bool(strpos($role, '#emembermark#'))) {
                                $new_capabilities[$role . '#emembermark#' . $ret_member_db->account_state] = $state;
                                $modified = true;
                            }
                        } else if ($ret_member_db->account_state === 'active') {
                            if (!is_bool(strpos($role, '#emembermark#'))) {
                                $parts = explode('#emembermark#', $role);
                                $new_capabilities[$parts[0]] = $state;
                                $modified = true;
                            }
                        }
                    }
                }
                if ($modified) {
                    update_user_meta($wp_user->ID, 'wp_capabilities', $new_capabilities);
                }
            }
        }
    }
}

function get_renewal_link() {
    $emember_config = Emember_Config::getInstance();
    $account_upgrade_url = $emember_config->getValue('eMember_account_upgrade_url');
    if (empty($account_upgrade_url)) {
        $msg = 'Account renewal page is not defined. Please Contact <a href="mailto:' . $emember_config->getValue('admin_email') . '">Admin</a>.';
        return wp_emember_format_message($msg);
    }

    $msg = EMEMBER_SUBSCRIPTION_EXPIRED_MESSAGE . ' ' . EMEMBER_PLEASE .
            ' <a href=" ' . $account_upgrade_url . '" target=_blank>' . EMEMBER_RENEW_OR_UPGRADE .
            '</a> ' . EMEMBER_YOUR_ACCOUNT;
    return wp_emember_format_message($msg);
}

function get_login_link() {
    $emember_config = Emember_Config::getInstance();
    $login_url = get_permalink();
    $join_url = $emember_config->getValue('eMember_payments_page');
    $eMember_enable_fancy_login = $emember_config->getValue('eMember_enable_fancy_login');
    if (empty($join_url)) {
        $msg = '<b>Membership Payment/Join Page</b> is not defined in the settings page.Please Contact <a href="mailto:' . $emember_config->getValue('admin_email') . '">Admin</a>.';
        return wp_emember_format_message($msg);
    }

    $format_classes = "eMember_protected_message";
    if ($emember_config->getValue('eMember_format_post_page_protected_msg')) {//apply default formatting to the post/page protected message
        $format_classes .= ' eMember_protected_message_default';
    }

    if ($eMember_enable_fancy_login) {
        $url_text = '<div class="' . $format_classes . '">';
        $url_text .= EMEMBER_PLEASE . ' <a id="' . microtime(true) . '" class="emember_fancy_login_link activeLink" href="javascript:void(0);">' .
                EMEMBER_LOGIN . '</a> ' . EMEMBER_TO_VIEW_CONTENT;
        $url_text .= '<span class="eMember_not_a_member_msg">(' . EMEMBER_NON_MEMBER . ' <a href="' . $join_url . '">' . EMEMBER_JOIN . '</a>)</span>';
        $url_text .= '</div>';
        return $url_text;
    }

    $disable_inline_login = $emember_config->getValue('eMember_disable_inline_login');
    if ($disable_inline_login) {
        //Not using inline login. So redirect the standard membership login page.
        $login_url = $emember_config->getValue('login_page_url');
    } else {
        //Inline login is being used. Add query parameters accordingly
        $arr_params = array('event' => 'login');
        $login_url = add_query_arg($arr_params, $login_url);

        //Check if the after login is disabled for inline option
        $eMember_inline_login_no_redirection = $emember_config->getValue('eMember_inline_login_no_redirection');
        if($eMember_inline_login_no_redirection){
            eMember_log_debug("After login redirection is disabled for inline login option.", true);
            $arr_params = array('no-redirect' => '1');
            $login_url = add_query_arg($arr_params, $login_url);
        }
    }
    $login_url = emember_add_no_redirect_param_if_applicable($login_url);

    $no_fancy_login = '';
    $no_fancy_login .= '<div class="' . $format_classes . '">';
    $no_fancy_login .= EMEMBER_PLEASE . ' <a href="' . $login_url . '">' . EMEMBER_LOGIN . '</a> ' . EMEMBER_TO_VIEW_CONTENT;
    $no_fancy_login .= '<span class="eMember_not_a_member_msg">(' . EMEMBER_NON_MEMBER . ' <a href="' . $join_url . '">' . EMEMBER_JOIN . '</a>)</span>';
    $no_fancy_login .= '</div>';

    return $no_fancy_login;
}

/* * * Return a login link HTML code with just the "Login" anchor text based on settings an parameter value ** */

function eMember_get_login_link_only_based_on_settings_condition($direct_login_page_url = '', $redirect_to = '') {
    $emember_config = Emember_Config::getInstance();
    $join_url = $emember_config->getValue('eMember_payments_page');
    $login_link = "";
    /* Start checking parameter and settings values and server the correct HTML code for the login link */
    if (!empty($direct_login_page_url)) {//Just need a direct link to the login page
        $link_url = $emember_config->getValue('login_page_url');
        if (empty($link_url)) {
            return '<div class="emember_error">You did not specify a login page URL in the settings menu. Please specify a value in the login page URL field of eMember settings!</div>';
        }
        $login_link .= '<a href="' . $link_url . '">' . EMEMBER_LOGIN . '</a>';
        return $login_link;
    }
    //Check if fancy login is enabled
    $eMember_enable_fancy_login = $emember_config->getValue('eMember_enable_fancy_login');
    if ($eMember_enable_fancy_login) {//Fancy login enabled... create link suitable for fancy login
        $login_link .= '<a id="' . microtime(true) . '" class="emember_fancy_login_link" href="javascript:void(0);">' . EMEMBER_LOGIN . '</a>';
        if (is_search()) {
            return $login_link;
        }

        ob_start();
        include_once(WP_EMEMBER_PATH.'fancy_login.php');
        $output = ob_get_contents();
        ob_end_clean();
        return $login_link . $output;
    }
    //Not using fancy display.. serve normal login link
    $content_url = get_permalink();
    $seperator = '?';
    if (strpos($content_url, '?')) {
        $seperator = '&';
    }
    $link_url = $content_url . $seperator . "event=login";
    $login_link .= '<a href="' . $link_url . '">' . EMEMBER_LOGIN . '</a>';
    return $login_link;
}

function filter_eMember_login_form($content) {
    $pattern = '#\[wp_eMember_login_form:end]#';
    preg_match_all($pattern, $content, $matches);

    foreach ($matches[0] as $match) {
        $replacement = print_eMember_login_form();
        $content = str_replace($match, $replacement, $content);
    }

    return $content;
}

function print_eMember_login_form() {
    return eMember_login_widget();
}

function emember_after_wp_login_callback($userlogin, $user) {
    $emember_config = Emember_Config::getInstance();
    $sign_in_with_wp = $emember_config->getValue('eMember_signin_emem_user');
    //Automatically login to emember system
    if ($sign_in_with_wp) {
        $auth = Emember_Auth::getInstance();
        if (!$auth->isLoggedIn()){
            $auth->login_through_wp($userlogin, $user);
        }
    }
}

function emember_after_wp_logout_handler() {
    $emember_config = Emember_Config::getInstance();
    $sign_in_with_wp = $emember_config->getValue('eMember_signin_emem_user');
    //automattically log into emember enabled.
    if ($sign_in_with_wp) {
        $auth = Emember_Auth::getInstance();
        if ($auth->isLoggedIn())
            $auth->logout();
    }

    $eMember_auto_affiliate_account_login = $emember_config->getValue('eMember_auto_affiliate_account_login');
    if ($eMember_auto_affiliate_account_login && function_exists('wp_aff_platform_install')) {
        //logout the affiliate account
        unset($_SESSION['user_id']);
        setcookie("user_id", "", time() - 60 * 60 * 24 * 7, "/", COOKIE_DOMAIN);
    }
}

function eMember_login_widget() {

    if (!defined('DONOTCACHEPAGE')){
        define('DONOTCACHEPAGE', TRUE); //Cache plugin compatibility. Do not cache the login form.
    }

    $emember_config = Emember_Config::getInstance();
    $auth = Emember_Auth::getInstance();
    $username = $auth->getUserInfo('user_name');
    $output = '';
    if ($auth->isLoggedIn()) {
        $expires = $auth->getUserInfo('account_state');
        $subscription_duration = $auth->permitted->subscription_duration;
        if ($subscription_duration['type'] == 'noexpire'){
            $sub_expires = EMEMBER_NEVER;
        }
        else if ($subscription_duration['type'] == 'fixeddate'){
            $expiry_date = $subscription_duration['duration'];//For fixed date expiry, the duration field contains the expiry date.
            $sub_expires = emember_get_formatted_date_according_to_wp_settings($expiry_date);
        }
        else {
            //Create date object with start date
            $start_date = $auth->getUserInfo('subscription_starts');
            $date_obj = new DateTime($start_date);

            //Create the interval object using the subscription duration value.
            $interval_string = 'P' . $subscription_duration['duration'] . 'D';//Example - 30 days will be P30D
            $interval = new DateInterval($interval_string);

            //Add them
            $date_obj->add($interval);

            //Create the formatted date output
            $expiry_date = $date_obj->format('Y-m-d');
            $sub_expires = emember_get_formatted_date_according_to_wp_settings($expiry_date);
        }

        $states = array('active' => EMEMBER_ACTIVE,
            'inactive' => EMEMBER_INACTIVE,
            'expired' => EMEMBER_EXPIRED,
            'pending' => EMEMBER_PENDING,
            'unsubscribed' => EMEMBER_UNSUBSCRIBED);
        $eMember_secure_rss = $emember_config->getValue('eMember_secure_rss');
        $eMember_show_welcome_page_link = $emember_config->getValue('eMember_show_link_to_after_login_page');
        $feed_url = get_bloginfo('rss2_url');

        global $wp_rewrite;
        //$nonce = wp_create_nonce('emember-secure-feed-nonce');
        if ($wp_rewrite->using_permalinks()){
            $feed_url .= '?emember_feed_key=' . md5($auth->getUserInfo('member_id'));
        }
        else{
            $feed_url .= '&emember_feed_key=' . md5($auth->getUserInfo('member_id'));
        }

        $logout = get_logout_url();
        $output .= '<div class="eMember_logged_widget">';
        $output .= '<div class="eMember_logged_user_info_section">';
        $output .= '<div class="eMember_logged_in_as">' . EMEMBER_LOGGED_IN_AS;
        $output .= '<label class="eMember_highlight">' . $username . '</label></div>';
        $output .= '<div class="eMember_logged_in_level">' . EMEMBER_LOGGED_IN_LEVEL;
        $output .= '<label class="eMember_highlight">' . $auth->permitted->primary_level->get('alias') . '</label></div>';
        $output .= '<div class="eMember_logged_in_account_status">' . EMEMBER_ACCOUNT_STATUS . " ";
        $output .= '<label class="eMember_highlight">' . $states[$auth->getUserInfo('account_state')] . '</label></div>';
        $output .= '<div class="eMember_logged_user_expiry">';
        if ($expires != 'expired') {
            $output .= EMEMBER_ACCOUNT_EXPIRES_ON . " ";
            $output .= '<label class="eMember_highlight">' . $sub_expires . '</label>';
        } else {
            $renew_url = $emember_config->getValue('eMember_account_upgrade_url');
            $output .= '<span class="eMember_logged_renewal_link"><a href="' . $renew_url . '">' . EMEMBER_RENEW_OR_UPGRADE . '</a></span>';
        }
        $output .= '</div>'; //End of eMember_logged_user_expiry

        $output .= '</div>'; //End of eMember_logged_user_info_section
        $output .= '<ul class="eMember_logged_member_resources_links">';
        $output .= '<li class="eMember_logged_logout_link"><a href="' . $logout . '">' . EMEMBER_LOGOUT . '</a></li>';
        if ($eMember_secure_rss){
            $output .= '<li class="eMember_logged_rss_feed_link"><a href="' . $feed_url . '">' . EMEMBER_MY_FEED . '</a></li>';
        }
        $edit_profile_page = $emember_config->getValue('eMember_profile_edit_page');
        $support_page = $emember_config->getValue('eMember_support_page');
        if (!empty($edit_profile_page)){
            $output .= '<li class="eMember_logged_edit_profile_link"><a href="' . $edit_profile_page . '">' . EMEMBER_EDIT_PROFILE . '</a></li>';
        }
        if (!empty($support_page)){
            $output .= '<li class="eMember_logged_support_page_link"><a href="' . $support_page . '">' . EMEMBER_SUPPORT_PAGE . '</a></li>';
        }
        if ($eMember_show_welcome_page_link) {
            $welcome_page_url = emember_get_after_login_page_url_of_current_user();
            $output .= '<li class="eMember_logged_welcome_page_link"><a href="' . $welcome_page_url . '">' . EMEMBER_WELCOME_PAGE . '</a></li>';
        }
        $bookmark_feature = $emember_config->getValue('eMember_enable_bookmark');
        if ($bookmark_feature) {
            $bookmark_page_url = $emember_config->getValue('eMember_bookmark_listing_page');
            if (!empty($bookmark_page_url)){
                $output .= '<li class="eMember_logged_bookmark_page_link"><a href="' . $bookmark_page_url . '">' . EMEMBER_BOOKMARK_PAGE . '</a></li>';
            }
        }
        $output .= '</ul>';
        $custom_login_msg = stripslashes($emember_config->getValue('eMember_login_widget_message_for_logged_members'));
        if (!empty($custom_login_msg)) {
            $custom_login_msg = do_shortcode($custom_login_msg);
            $output .= html_entity_decode($custom_login_msg, ENT_COMPAT);
        }
        $output .= '</div>'; //End of eMember_logged_widget
    } else {
        $output = show_login_form();
    }

    return $output;
}

function eMember_compact_login_widget($show_profile_link = '') {
    $emember_config = Emember_Config::getInstance();
    $join_url = $emember_config->getValue('eMember_payments_page');
    $auth = Emember_Auth::getInstance();
    $output = "";
    $output .= "<div class='eMember_compact_login'>";
    if ($auth->isLoggedIn()) {
        $output .= EMEMBER_HELLO;
        $name = $auth->getUserInfo('first_name') . " " . $auth->getUserInfo('last_name');
        ;
        $output .= $name;

        if (!empty($show_profile_link)) {
            $output .= ' | ';
            $edit_profile_page = $emember_config->getValue('eMember_profile_edit_page');
            $output .= '<a href="' . $edit_profile_page . '">' . EMEMBER_EDIT_PROFILE . '</a>';
        }

        $logout = get_logout_url();
        $output .= ' | ';
        $output .= '<a href="' . $logout . '">' . EMEMBER_LOGOUT . '</a>';
    } else {
        if (is_search())
            return get_login_link();
        $output .= EMEMBER_HELLO;
        $eMember_enable_fancy_login = $emember_config->getValue('eMember_enable_fancy_login');
        if ($eMember_enable_fancy_login) {
            $output .= '<a id="' . microtime(true) . '" class="emember_fancy_login_link" href="javascript:void(0);">' . EMEMBER_LOGIN . '</a>';
            ob_start();
            include_once(WP_EMEMBER_PATH.'fancy_login.php');
            $output_fancy_jquery = ob_get_contents();
            ob_end_clean();
            $output .= $output_fancy_jquery;
        } else {
            $login_url = $emember_config->getValue('login_page_url');
            $output .= '<a href="' . $login_url . '">' . EMEMBER_LOGIN . '</a>';
        }
        $output .= EMEMBER_NOT_A_MEMBER_TEXT;
        $join_url = $emember_config->getValue('eMember_payments_page');
        $output .= '<a href="' . $join_url . '">' . EMEMBER_JOIN . '</a>';
    }
    $output .= "</div>";
    return $output;
}

function eMember_compact_login_widget_custom() {
    $emember_config = Emember_Config::getInstance();
    $auth = Emember_Auth::getInstance();
    $output = "";
    $output .= "<div class='eMember_compact_login_custom'>";
    if ($auth->isLoggedIn()) {//User is logged in (show the details for logged in user)
        //Show the member's name (remove the following 3 lines if you don't want to show the name)
        $output .= $auth->getUserInfo('first_name') . " " . $auth->getUserInfo('last_name');
        ;

        //Show a link to the profile edit page (remove the following 3 lines if you don't want to show a edit profile link)
        $output .= ' | ';
        $edit_profile_page = $emember_config->getValue('eMember_profile_edit_page');
        $output .= '<a href="' . $edit_profile_page . '">' . EMEMBER_EDIT_PROFILE . '</a>';

        //Show a logout link (remove the following 3 lines if you don't want to show a logout link)
        $logout = get_logout_url();
        $output .= ' | ';
        $output .= '<a href="' . $logout . '">' . EMEMBER_LOGOUT . '</a>';
    } else {//User is not logged in (show the login prompt)
        //Show a login link
        $login_url = $emember_config->getValue('login_page_url');
        $output .= '<a href="' . $login_url . '">' . EMEMBER_LOGIN . '</a>';

        //Show link to the Join us page
        $output .= EMEMBER_NOT_A_MEMBER_TEXT;
        $join_url = $emember_config->getValue('eMember_payments_page');
        $output .= '<a href="' . $join_url . '">' . EMEMBER_JOIN . '</a>';
    }
    $output .= "</div>"; //End of "eMember_compact_login_custom" div
    return $output;
}

function get_logout_url() {
    $emember_config = Emember_Config::getInstance();
    $url = $emember_config->getValue('login_page_url');
    if (empty($url)) {
        $url = get_bloginfo('url');
    }
    if (strpos($url, '?')) {
        $logout = $url . "&emember_logout=true";
    } else {
        $logout = trailingslashit($url) . "?emember_logout=true";
    }
    return $logout;
}

function wp_emember_is_member_logged_in($level_id = '') {
    /* returns true if the member is logged in. if a level_id is specified then return true only if a member from that level is logged in */
    $emember_auth = Emember_Auth::getInstance();
    if ($emember_auth->isLoggedIn()) {
        if (empty($level_id)) {
            return true; //member is logged in
        }
        $membership_level = $emember_auth->getUserInfo('membership_level');
        if ($level_id == $membership_level) {
            return true;
        }
        if (emember_is_logged_into_secondary_level($level_id)) {
            return true;
        }
    }
    return false; //member is not logged in
}

function emember_is_logged_into_secondary_level($level_id) {
    $emember_auth = Emember_Auth::getInstance();
    if (!($emember_auth->isLoggedIn())) {
        return false;
    }
    $secondary_levels = $emember_auth->permitted->secondary_levels;
    if (isset($secondary_levels)) {
        foreach ($secondary_levels as $level) {
            if ($level->get('id') == $level_id) {
                return true;
            }
        }
    }
    return false;
}

function wp_emember_is_member_logged_in_and_active($level_id = '') {
    if (!wp_emember_is_member_logged_in($level_id)) {
        return false;
    }
    $emember_auth = Emember_Auth::getInstance();
    $account_status = $emember_auth->getUserInfo('account_state');
    if ($account_status == 'active') {
        return true;
    }
    return false;
}

function show_login_form() {
    $emember_auth = Emember_Auth::getInstance();
    $emember_config = Emember_Config::getInstance();

    $msg = $emember_auth->getSavedMessage('eMember_login_status_msg');
    $state_code = $emember_auth->getSavedMessage('eMember_login_status_code');
    $join_url = $emember_config->getValue('eMember_payments_page');
    $eMember_multiple_logins = $emember_config->getValue('eMember_multiple_logins');
    $eMember_pw_visibility_for_login_form = $emember_config->getValue('eMember_pw_visibility_for_login_form');

    wp_enqueue_style('dashicons');
    ob_start();
    ?>
    <form action="" method="post" class="loginForm wp_emember_loginForm" name="wp_emember_loginForm" id="wp_emember_loginForm">
        <?php wp_nonce_field('emember-login-nonce'); ?>
        <table width="95%" border="0" cellpadding="3" cellspacing="5" class="forms">
            <tr>
                <td colspan="2"><label for="login_user_name" class="eMember_label"><?php echo esc_html(EMEMBER_USER_NAME); ?></label></td>
            </tr>
            <tr>
                <td colspan="2"><input class="eMember_text_input" type="text" id="login_user_name" name="login_user_name" size="15" value="<?php echo isset($_POST['login_user_name']) ? esc_attr(stripslashes($_POST['login_user_name'])) : ""; ?>" /></td>
            </tr>
            <tr>
                <td colspan="2"><label for="login_pwd" class="eMember_label"><?php echo esc_html(EMEMBER_PASSWORD); ?></label></td>
            </tr>
            <tr>
                <td style="position:relative;" colspan="2">
                    <input class="eMember_text_input" type="password" id="login_pwd" name="login_pwd" size="15" value="<?php echo isset($_POST['login_pwd']) ? esc_attr(strip_tags($_POST['login_pwd'])) : ""; ?>" />
                    <?php
                    if (isset($eMember_pw_visibility_for_login_form) && !empty($eMember_pw_visibility_for_login_form)) {
                        echo '<span class="eMember_show_pw_btn"><i class="dashicons dashicons-visibility" aria-hidden="true"></i></span>';
                    }
                    ?>
                </td>
            </tr>
            <?php if (empty($eMember_multiple_logins)): ?>
                <tr>
                    <td colspan="2">
                        <div class="eMember_remember_me"><label><input type="checkbox" tabindex="90" value="forever" id="rememberme" name="rememberme" /><span class="eMember_remember_me_label"> <?php echo esc_html(EMEMBER_REMEMBER_ME); ?></span></label></div>
                    </td>
                </tr>
            <?php endif; ?>
            <tr class="emember-login-captcha-tr">
                <td colspan="2" class="emember-login-captcha-td">
                    <div class="emember-login-captcha-section">
                    <?php
                        $login_captcha_output = '';
                        $enable_captcha_login_form = $emember_config->getValue('emember_enable_recaptcha_login_form');
                        if ($enable_captcha_login_form) {
                            $login_captcha_output = emember_recaptcha_html();
                        }
                        echo apply_filters('emember_captcha_login', $login_captcha_output);
                    ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <input type="hidden" value="1" name="testcookie" />
                    <input name="doLogin" type="submit" id="doLogin" class="eMember_button emember_login_submit" value="<?php echo esc_attr(EMEMBER_LOGIN); ?>" />
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <?php
                    $password_reset_url = $emember_config->getValue('eMember_password_reset_page');
                    if ($password_reset_url):
                        ?>
                        <a id="forgot_pass" href="<?php echo esc_url($password_reset_url); ?>"><?php echo esc_html(EMEMBER_FORGOT_PASS); ?></a>
                    <?php else : ?>
                        <a id="forgot_pass" rel="#emember_forgot_pass_prompt" class="forgot_pass_link" href="javascript:void(0);"><?php echo esc_html(EMEMBER_FORGOT_PASS); ?></a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td colspan="2"><a id="register" class="register_link" href="<?php echo esc_url($join_url); ?>"><?php echo esc_html(EMEMBER_JOIN_US); ?></a></td>
            </tr>
            <tr>
                <td colspan="2"><span class="<?php echo esc_attr($state_code == 6 ? 'emember_ok' : 'emember_error'); ?>"> <?php echo esc_html($msg); ?> </span></td>
            </tr>
        </table>
    </form>
    <?php
    if (isset($eMember_pw_visibility_for_login_form) && !empty($eMember_pw_visibility_for_login_form)) {
        require_once WP_EMEMBER_PATH . 'js/emember_show_hide_pw.php';
    }
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
}

function wp_emember_num_days_since_expired($subcript_period, $subscript_unit, $start_date) {
    $expires = emember_calculate_expiry_date($subcript_period, $subscript_unit, $start_date);
    if ($expires == 'noexpire'){
        return 10000000;
    }
    $now = time();
    $expires = strtotime($expires);
    if ($now > $expires){
        return floor(($now - $expires) / (60 * 60 * 24));
    }
}

function wp_emember_num_days_before_expires($subcript_period, $subscript_unit, $start_date) {
    $expires = emember_calculate_expiry_date($subcript_period, $subscript_unit, $start_date);
    if ($expires == 'noexpire'){
        return 10000000;
    }
    $now = time();
    $expires = strtotime($expires);
    if ($now < $expires){
        return floor(($expires - $now) / (60 * 60 * 24));
    } else {
        return -1;//Indicates it has expired already.
    }
}

function wp_emember_is_expired($level, $subscription_starts) {
    /*
     * Below are some example values for the different types of membership levels
     * Duration Expiry = subscription_period: 30, subscription_unit: Days
     * No Expiry = subscription_period: 0, subscription_unit: ''
     * Fixed Date Expiry = subscription_period: 0, subscription_unit: 2018-01-31
     */

    $subcript_period = (int) $level->get('subscription_period');
    $subscript_unit = $level->get('subscription_unit');

    if (($subcript_period == 0) && !empty($subscript_unit)) {
        //Fixed date expiry
        $date_expiry = new DateTime($subscript_unit);
        $date_now = new DateTime();
        if($date_now > $date_expiry){//Account is expired
            return true;
        }else{//Account is NOT expired
            return false;
        }
    }

    switch ($subscript_unit) {
        case 'Days':
            break;
        case 'Weeks':
            $subcript_period = $subcript_period * 7;
            break;
        case 'Months':
            $subcript_period = $subcript_period * 30;
            break;
        case 'Years':
            $subcript_period = $subcript_period * 365;
            break;
    }

    if ($subcript_period === 0){//No expiry
        return false;
    }
    /* alternative */
    $d = ($subcript_period == 1) ? ' day' : ' days';
    $sDate = date('Y-m-d', strtotime(" - " . abs($subcript_period) . $d));
    if ((strtotime($sDate) - strtotime($subscription_starts)) >= 0) {
        return true;
    }
    return false;
}

function emember_is_expired_by_subsc_data($subcript_period, $subscript_unit, $subscription_starts) {
    /*
     * Below are some example values for the different types of membership levels
     * Duration Expiry = subscription_period: 30, subscription_unit: Days
     * No Expiry = subscription_period: 0, subscription_unit: ''
     * Fixed Date Expiry = subscription_period: 0, subscription_unit: 2018-01-31
     */

    if (($subcript_period == 0) && !empty($subscript_unit)) {
        //Fixed date expiry
        $date_expiry = new DateTime($subscript_unit);
        $date_now = new DateTime();
        if($date_now > $date_expiry){//Account is expired
            return true;
        }else{//Account is NOT expired
            return false;
        }
    }

    switch ($subscript_unit) {
        case 'Days':
            break;
        case 'Weeks':
            $subcript_period = $subcript_period * 7;
            break;
        case 'Months':
            $subcript_period = $subcript_period * 30;
            break;
        case 'Years':
            $subcript_period = $subcript_period * 365;
            break;
    }

    if ($subcript_period == 0){//No expiry
        return false;
    }
    /* alternative */
    $d = ($subcript_period == 1) ? ' day' : ' days';
    $sDate = date('Y-m-d', strtotime(" - " . abs($subcript_period) . $d));
    if ((strtotime($sDate) - strtotime($subscription_starts)) >= 0) {
        return true;
    }
    return false;
}

function wp_emember_is_subscription_expired($member, $level) {
    $expiry_1st = strtotime($member->expiry_1st);
    $expiry_2nd = strtotime($member->expiry_2nd);

    if ($expiry_1st && ($expiry_1st > 0)) {
        $is_expired = time() > $expiry_1st;
        return $is_expired;
    }

    if ($expiry_2nd && ($expiry_2nd > 0)) {
        $is_expired = time() > $expiry_2nd;
        return $is_expired;
    }

    return wp_emember_is_expired($level, $member->subscription_starts);
}

function emember_is_secondary_level_expired($member_id, $level_id){
    $member = emember_get_member_record_by_member_id($member_id);
    $level_row = emember_get_membership_level_row_by_id($level_id);

    $more_start_date = (array) json_decode($member->more_membership_levels_start_date, true);

    if (!isset($more_start_date[$level_id])){
        //Start date for this secondary level is not set. Return false to be safe.
        return false;
    }

    if(emember_is_expired_by_subsc_data($level_row->subscription_period, $level_row->subscription_unit, $more_start_date[$level_id])){
        //The given secondary level is expired.
        return true;
    }

    //The given secondary level is NOT expired.
    return false;
}

function emember_update_htpasswd($user, $pass) {
    $upload_dir = wp_upload_dir();
    $dir = $upload_dir['basedir'] . '/emember/downloads/';
    $htpasswd = file_get_contents($dir . '.htpasswd');
    $newhtpasswd = array();
    foreach (explode("\n", trim($htpasswd)) as $i) {
        if (empty($i))
            continue;
        $t = explode(':', $i);
        if ($t[0] == $user)
            continue;
        $newhtpasswd[] = $i;
    }
    $newhtpasswd[] = $user . ':' . crypt($pass, base64_encode($pass));
    $newhtpasswd = implode("\n", $newhtpasswd);
    $newhtpasswd = ltrim($newhtpasswd);
    file_put_contents($dir . '.htpasswd', $newhtpasswd);
}

function emember_get_exipiry_date() {
    $auth = Emember_Auth::getInstance();
    if (!$auth->isLoggedIn())
        return "User is not logged in!";
    $expires = $auth->getUserInfo('account_state');
    $subscription_duration = $auth->permitted->subscription_duration;
    if ($subscription_duration['type'] == 'noexpire')
        $sub_expires = EMEMBER_NEVER;
    else if ($subscription_duration['type'] == 'fixeddate')
        $sub_expires = emember_date_locale(strtotime($subscription_duration['duration']));
    else {
        $sub_start = strtotime($auth->getUserInfo('subscription_starts'));
        $sub_expires = emember_date_locale(strtotime("+" . $subscription_duration['duration'] . " days ", $sub_start));
    }
    return $sub_expires;
}

function emember_get_exipiry_date_additional_levels() {
    global $wpdb;
    $auth = Emember_Auth::getInstance();
    if (!$auth->isLoggedIn()){
        return "User is not logged in!";
    }
    foreach ($auth->permitted->secondary_levels as $level) {
        $my_subcript_period = $level->get('subscription_period');
        $my_subscript_unit = $level->get('subscription_unit');
        if (($my_subcript_period == 0) && empty($my_subscript_unit))
            $type = 'noexpire';
        else if (($my_subcript_period == 0) && !empty($my_subscript_unit)) {
            $type = 'fixeddate';
            $my_subcript_period = $my_subscript_unit;
        } else {
            $type = 'interval';
            switch ($my_subscript_unit) {
                case 'Days':
                    break;
                case 'Weeks':
                    $my_subcript_period = $my_subcript_period * 7;
                    break;
                case 'Months':
                    $my_subcript_period = $my_subcript_period * 30;
                    break;
                case 'Years':
                    $my_subcript_period = $my_subcript_period * 365;
                    break;
            }
        }
        if ($type == 'noexpire')
            $sub_expires = EMEMBER_NEVER;
        else if ($type == 'fixeddate')
            $sub_expires = emember_date_locale(strtotime($my_subcript_period));
        else {
            $sub_start = strtotime($auth->getUserInfo('subscription_starts'));
            $sub_expires = emember_date_locale(strtotime("+" . $my_subcript_period . " days ", $sub_start));
        }
        $additionals[$level->get('alias')] = $sub_expires;
    }
    return $additionals;
}

function emember_calculate_expiry_date($subcript_period, $subscript_unit, $start_date) {
    if (($subcript_period == 0) && !empty($subscript_unit)) { //will expire after a fixed date.
        return $subscript_unit;
    }
    switch ($subscript_unit) {
        case 'Days':
            break;
        case 'Weeks':
            $subcript_period = $subcript_period * 7;
            break;
        case 'Months':
            $subcript_period = $subcript_period * 30;
            break;
        case 'Years':
            $subcript_period = $subcript_period * 365;
            break;
    }
    if ($subcript_period == 0) {// its set to no expiry until cancelled
        return 'noexpire';
    }
    //Using duration value - lets calculate the expiry
    $d = ($subcript_period == 1) ? ' day' : ' days';
    return date('Y-m-d', strtotime(" + " . abs($subcript_period) . $d, strtotime($start_date)));
}

function emember_get_subscription_start_date_by_level($level, $current_startdate) {
    global $wpdb;
    $today = date('Y-m-d');
    $query = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "wp_eMember_membership_tbl WHERE id =%d", $level);
    $level_info = $wpdb->get_row($query);
    // new start date should be after the current subscription is epxired.
    $subscription_starts = emember_calculate_expiry_date($level_info->subscription_period, $level_info->subscription_unit, $current_startdate);
    // but if account is already expired. then start date should be today's date.
    if (($subscription_starts != "noexpire") && strtotime($subscription_starts) < time()) {
        $subscription_starts = $today;
    }
    return $subscription_starts;
}

function emember_get_expiry_by_member_id($member_id) {
    global $wpdb;
    $query = "SELECT subscription_starts, membership_level, subscription_period, subscription_unit FROM " .
            WP_EMEMBER_MEMBERS_TABLE_NAME . " LEFT JOIN " . WP_EMEMBER_MEMBERSHIP_LEVEL_TABLE .
            " ON membership_level = id WHERE member_id = " . esc_sql($member_id);
    $result = $wpdb->get_row($query);
    if (empty($result)){
        return '1969-01-01';
    }
    $period = $result->subscription_period;
    $unit = $result->subscription_unit;
    $start = $result->subscription_starts;
    return emember_calculate_expiry_date($period, $unit, $start);
}

function emember_my_membership_levels($args) {
    extract(shortcode_atts(array(
        'show_welcome_page' => '',
        'level_name_label' => 'Level Name',
        'level_type_label' => 'Level Type',
        'primary_label' => 'Primary',
        'secondary_label' => 'Secondary',
        'welcome_page_anchor' => 'Welcome Page',
                    ), $args));

    global $wpdb;
    $auth = Emember_Auth::getInstance();
    if (!$auth->isLoggedIn()) {
        return get_login_link();
    }
    $show_welcome_page = false;
    if (isset($args['show_welcome_page'])) {
        $show_welcome_page = true;
    }

    $output = '<div class="emember_my_membership_levels">';
    $output .= '<table>';
    $output .= '<tr>';
    $output .= '<th class="emml_level_name_col">' . $level_name_label . '</th>';
    $output .= '<th class="emml_level_type_col">' . $level_type_label . '</th>';
    $output .= '</tr>';

    $output .= '<tr>';
    $output .= '<td class="emml_level_name_col">';
    $output .= '<span class="emember_mml_primary_level_name">' . $auth->permitted->primary_level->get('alias') . '</span>'; //Name
    if ($show_welcome_page) {
        $redirect_page = $auth->permitted->primary_level->get('loginredirect_page');
        if (!empty($redirect_page)) {
            $output .= '<span class="emember_mml_primary_level_page"> (<a href="' . $redirect_page . '" target="_blank">' . $welcome_page_anchor . '</a>) </span>'; //welcome page
        }
    }
    $output .= '</td>';

    $output .= '<td class="emml_level_type_col">' . $primary_label . '</td>';
    $output .= '</tr>';
    foreach ($auth->permitted->secondary_levels as $level) {
        $output .= '<tr>';
        $output .= '<td class="emml_level_name_col">';
        $output .= '<span class="emember_mml_secondary_level_name">' . $level->get('alias') . '</span>'; //Name
        if ($show_welcome_page) {
            $redirect_page = $level->get('loginredirect_page');
            if (!empty($redirect_page)) {
                $output .= '<span class="emember_mml_secondary_level_page"> (<a href="' . $redirect_page . '" target="_blank">' . $welcome_page_anchor . '</a>) </span>'; //welcome page
            }
        }
        $output .= '</td>';

        $output .= '<td class="emml_level_type_col">' . $secondary_label . '</td>';
        $output .= '</tr>';
    }
    $output .= '</table>';
    $output .= '</div>';
    return $output;
}

function emember_after_login($user, $pass, $rememberme) {
    $emember_auth = Emember_Auth::getInstance();
    $emember_config = Emember_Config::getInstance();
    if (!is_user_logged_in()) {
        $sign_in_wp = $emember_config->getValue('eMember_signin_wp_user');
        $user_id = username_exists($user);
        if ($sign_in_wp) {
            eMember_log_debug("Logging into WordPress account. User ID: " . $user_id, true);
            if ($user_id) {
                $preserve_role = $emember_auth->getUserInfo('flags');
                if ($preserve_role != 1) {//User specific preserve role not applicable. The global preserve role settings will get checked inside update_wp_user_Role().
                    $user_info = get_userdata($user_id);
                    $user_cap = is_array($user_info->wp_capabilities) ? array_keys($user_info->wp_capabilities) : array();
                    $account_stat = $emember_auth->getUserInfo('account_state');
                    if (($account_stat === 'active') && !in_array('administrator', $user_cap)){
                        update_wp_user_Role($user_id, $emember_auth->permitted->primary_level->get('role'));
                    }
                }
                update_account_status($user);
                $usr = wp_signon(array('user_login' => $user, 'user_password' => $pass, 'remember' => $rememberme), is_ssl() ? true : false);
                if (is_wp_error($usr)) {//There was an error with wp_signon. Show the error.
                    eMember_log_debug("Auto login to WP User system failed.", false);
                    echo $usr->get_error_message();
                }else {
                    //User was logged-into wp correctly. Set the user so get_currentuserinfo() function in other plugins will work on this same page load.
                    wp_set_current_user($usr->ID);
                }

            }
        }
    }
    $folder_protection = $emember_config->getValue('emember_download_folder_protection');
    if ($folder_protection){
        emember_update_htpasswd($user, $pass);
    }
    do_action('eMember_user_logged_in', $user);
    //Log into the affiliate account if the option is set
    $eMember_auto_affiliate_account_login = $emember_config->getValue('eMember_auto_affiliate_account_login');
    if ($eMember_auto_affiliate_account_login && function_exists('wp_aff_platform_install')) {
        eMember_log_debug("Logging into Affiliate Platform account", true);
        $_SESSION['user_id'] = $user;
        if (isset($_POST['rememberme']))
            setcookie("user_id", $user, time() + 60 * 60 * 24 * 7, "/", COOKIE_DOMAIN);
        else
            setcookie("user_id", $user, time() + 60 * 60 * 6, "/", COOKIE_DOMAIN);
    }
}

function emember_after_logout() {
    $emember_config = Emember_Config::getInstance();
    $sign_in_wp = $emember_config->getValue('eMember_signin_wp_user');

    if ($sign_in_wp && is_user_logged_in()) {
        wp_clear_auth_cookie();
    }
    $eMember_auto_affiliate_account_login = $emember_config->getValue('eMember_auto_affiliate_account_login');
    if ($eMember_auto_affiliate_account_login && function_exists('wp_aff_platform_install')) {
        //logout the affiliate account
        unset($_SESSION['user_id']);
        setcookie("user_id", "", time() - 60 * 60 * 24 * 7, "/", COOKIE_DOMAIN);
    }

    $logout_page = $emember_config->getValue('after_logout_page');
    if ($logout_page) {
        //Redirect to the after logout page (specified in the settings).
        wp_emember_redirect_to_url($logout_page);
    } else {
        //Do redirection to the same page if member_logout query parameter is set to '1'.
        //example.com/some-page/?member_logout=1
        $logout_alt = filter_input(INPUT_GET, 'member_logout');
        if ($logout_alt == 1) {
            wp_emember_redirect_to_non_logout_url();
        }

        //Otherwise do redirection to the homepage
        $wpurl = get_bloginfo("wpurl");
        wp_emember_redirect_to_url($wpurl);
    }
}

function emember_expired_user_has_access_to_this_page(){
    $emember_auth = Emember_Auth::getInstance();

    //Check if the user is logged-into the site.
    if(!$emember_auth->isLoggedIn()){
        //Anonymous user. No access. No need to check anything else.
        return false;
    }

    //Check if account is expired.
    if (!emember_check_all_subscriptions_expired()) {
        //This user's account is not expired. No need to check anything else.
        return false;
    }

    /*** We have an expired member. Lets check if the user is viewing a page that is a core system used URL. ***/
    if (emember_is_current_url_a_system_page()){
        //Allow this expired user to view this post/page content since this is a core system page.
        return true;
    }

    //Not a system used page. So the expired user has no access to this page.
    return false;

}