<?php

add_shortcode('emember_payment_button', 'emember_payment_button_handler');

function emember_payment_button_handler($args) {
    extract(shortcode_atts(array(
        'id' => '',
        'button_text' => '',
        'new_window' => '',
                    ), $args));

    if (empty($id)) {
        return '<p class="emember-red-box">Error! You must specify a button ID with this shortcode. Check the usage documentation.</p>';
    }

    $button_id = $id;
    //$button = get_post($button_id); //Retrieve the CPT for this button
    $button_type = get_post_meta($button_id, 'button_type', true);
    if (empty($button_type)) {
        $error_msg = '<p class="emember-red-box">';
        $error_msg .= 'Error! The button ID (' . $button_id . ') you specified in the shortcode does not exist. You may have deleted this payment button. ';
        $error_msg .= 'Go to the Manage Payment Buttons interface then copy and paste the correct button ID in the shortcode.';
        $error_msg .= '</p>';
        return $error_msg;
    }

    $button_code = '';
    $button_code = apply_filters('emember_payment_button_shortcode_for_' . $button_type, $button_code, $args);

    $output = '';
    $output .= '<div class="emember-payment-button">' . $button_code . '</div>';

    return $output;
}

/* * ************************************************
 * PayPal Buy Now button shortcode handler
 * *********************************************** */
add_filter('emember_payment_button_shortcode_for_pp_buy_now', 'emember_render_pp_buy_now_button_sc_output', 10, 2);

function emember_render_pp_buy_now_button_sc_output($button_code, $args) {

    $button_id = isset($args['id']) ? $args['id'] : '';
    if (empty($button_id)) {
        return '<p class="emember-red-box">Error! emember_render_pp_buy_now_button_sc_output() function requires the button ID value to be passed to it.</p>';
    }

    //Check new_window parameter
    $window_target = isset($args['new_window']) ? 'target="_blank"' : '';

    $settings = Emember_Config::getInstance();
    $button_cpt = get_post($button_id); //Retrieve the CPT for this button

    $membership_level_id = get_post_meta($button_id, 'membership_level_id', true);
    //Verify that this membership level exists (to prevent user paying for a level that has been deleted)
    if (!emember_membership_level_id_exists($membership_level_id)) {
        return '<p class="emember-red-box">Error! The membership level specified in this button does not exist. You may have deleted this membership level. Edit the button and use the correct membership level.</p>';
    }

    $paypal_email = get_post_meta($button_id, 'paypal_email', true);
    $payment_amount = get_post_meta($button_id, 'payment_amount', true);
    if (!is_numeric($payment_amount)) {
        return '<p class="emember-red-box">Error! The payment amount value of the button must be a numeric number. Example: 49.50 </p>';
    }
    $payment_amount = round($payment_amount, 2); //round the amount to 2 decimal place.   
    $payment_currency = get_post_meta($button_id, 'payment_currency', true);

    $sandbox_enabled = $settings->GetValue('eMember_enable_sandbox');
    $notify_url = WP_EMEMBER_SITE_HOME_URL . '/?emember_process_paypal_ipn=1';
    $return_url = get_post_meta($button_id, 'return_url', true);
    if (empty($return_url)) {
        $return_url = home_url();
    }
    $cancel_url = home_url();

    $user_ip = get_real_ip_addr();
    $_SESSION['emember_payment_button_interaction'] = $user_ip;

    //Custom field data
    $custom_args = array('level_id' => $membership_level_id);
    $custom_field_value = get_wp_emember_custom_field_val($custom_args);
    $custom_field_value = urlencode($custom_field_value); //URL encode the custom fields.

    /* === PayPal Buy Now Button Form === */
    $output = '';
    $output .= '<div class="emember-button-wrapper emember-pp-buy-now-wrapper">';

    if ($sandbox_enabled) {
        $output .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" ' . $window_target . '>';
    } else {
        $output .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" ' . $window_target . '>';
    }

    $output .= '<input type="hidden" name="cmd" value="_xclick" />';
    $output .= '<input type="hidden" name="charset" value="utf-8" />';
    $output .= '<input type="hidden" name="bn" value="TipsandTricks_SP" />';
    $output .= '<input type="hidden" name="business" value="' . $paypal_email . '" />';
    $output .= '<input type="hidden" name="amount" value="' . $payment_amount . '" />';
    $output .= '<input type="hidden" name="currency_code" value="' . $payment_currency . '" />';
    $output .= '<input type="hidden" name="item_number" value="' . $button_id . '" />';
    $output .= '<input type="hidden" name="item_name" value="' . htmlspecialchars($button_cpt->post_title) . '" />';

    $output .= '<input type="hidden" name="no_shipping" value="1" />'; //Do not prompt for an address

    $output .= '<input type="hidden" name="notify_url" value="' . $notify_url . '" />';
    $output .= '<input type="hidden" name="return" value="' . $return_url . '" />';
    $output .= '<input type="hidden" name="cancel_return" value="' . $cancel_url . '" />';

    $output .= '<input type="hidden" name="custom" value="' . $custom_field_value . '" />';

    //Filter to add additional payment input fields to the form (example: langauge code or country code etc).
    $output .= apply_filters('emember_pp_payment_form_additional_fields', '');

    $button_image_url = get_post_meta($button_id, 'button_image_url', true);
    if (!empty($button_image_url)) {
        $output .= '<input type="image" src="' . $button_image_url . '" class="emember-buy-now-button-submit" alt="' . 'Buy Now' . '"/>';
    } else {
        $button_text = (isset($args['button_text'])) ? $args['button_text'] : 'Buy Now';
        $output .= '<input type="submit" class="emember-buy-now-button-submit" value="' . $button_text . '" />';
    }

    $output .= '</form>'; //End .form
    $output .= '</div>'; //End .emember_button_wrapper

    return $output;
}

/* * ************************************************
 * PayPal subscription button shortcode handler 
 * *********************************************** */
add_filter('emember_payment_button_shortcode_for_pp_subscription', 'emember_render_pp_subscription_button_sc_output', 10, 2);

function emember_render_pp_subscription_button_sc_output($button_code, $args) {

    $button_id = isset($args['id']) ? $args['id'] : '';
    if (empty($button_id)) {
        return '<p style="color: red;">Error! emember_render_pp_subscription_button_sc_output() function requires the button ID value to be passed to it.</p>';
    }

    //Check new_window parameter
    $window_target = isset($args['new_window']) ? 'target="_blank"' : '';

    $settings = Emember_Config::getInstance();
    $button_cpt = get_post($button_id); //Retrieve the CPT for this button

    $membership_level_id = get_post_meta($button_id, 'membership_level_id', true);
    //Verify that this membership level exists (to prevent user paying for a level that has been deleted)
    if (!emember_membership_level_id_exists($membership_level_id)) {
        return '<p class="emember-red-box">Error! The membership level specified in this button does not exist. You may have deleted this membership level. Edit the button and use the correct membership level.</p>';
    }

    $paypal_email = get_post_meta($button_id, 'paypal_email', true);
    $payment_currency = get_post_meta($button_id, 'payment_currency', true);

    //Subscription payment details
    $billing_amount = get_post_meta($button_id, 'billing_amount', true);
    if (!is_numeric($billing_amount)) {
        return '<p style="color: red;">Error! The billing amount value of the button must be a numeric number. Example: 49.50 </p>';
    }
    $billing_amount = round($billing_amount, 2); //round the amount to 2 decimal place.  
    $billing_cycle = get_post_meta($button_id, 'billing_cycle', true);
    $billing_cycle_term = get_post_meta($button_id, 'billing_cycle_term', true);
    $billing_cycle_count = get_post_meta($button_id, 'billing_cycle_count', true);
    $billing_reattempt = get_post_meta($button_id, 'billing_reattempt', true);

    //Trial billing details
    $trial_billing_amount = get_post_meta($button_id, 'trial_billing_amount', true);
    if (!empty($trial_billing_amount)) {
        if (!is_numeric($trial_billing_amount)) {
            return '<p style="color: red;">Error! The trial billing amount value of the button must be a numeric number. Example: 19.50 </p>';
        }
    }
    $trial_billing_cycle = get_post_meta($button_id, 'trial_billing_cycle', true);
    $trial_billing_cycle_term = get_post_meta($button_id, 'trial_billing_cycle_term', true);

    $sandbox_enabled = $settings->GetValue('eMember_enable_sandbox');
    $notify_url = WP_EMEMBER_SITE_HOME_URL . '/?emember_process_paypal_ipn=1';
    $return_url = get_post_meta($button_id, 'return_url', true);
    if (empty($return_url)) {
        $return_url = home_url();
    }
    $cancel_url = home_url();

    $user_ip = get_real_ip_addr();
    $_SESSION['emember_payment_button_interaction'] = $user_ip;

    //Custom field data
    $custom_args = array('level_id' => $membership_level_id);
    $custom_field_value = get_wp_emember_custom_field_val($custom_args);
    $custom_field_value = urlencode($custom_field_value); //URL encode the custom fields.

    /* === PayPal Subscription Button Form === */
    $output = '';
    $output .= '<div class="emember-button-wrapper emember-pp-subscription-wrapper">';
    if ($sandbox_enabled) {
        $output .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" ' . $window_target . '>';
    } else {
        $output .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" ' . $window_target . '>';
    }

    $output .= '<input type="hidden" name="cmd" value="_xclick-subscriptions" />';
    $output .= '<input type="hidden" name="charset" value="utf-8" />';
    $output .= '<input type="hidden" name="bn" value="TipsandTricks_SP" />';
    $output .= '<input type="hidden" name="business" value="' . $paypal_email . '" />';
    $output .= '<input type="hidden" name="currency_code" value="' . $payment_currency . '" />';
    $output .= '<input type="hidden" name="item_number" value="' . $button_id . '" />';
    $output .= '<input type="hidden" name="item_name" value="' . htmlspecialchars($button_cpt->post_title) . '" />';

    //Check trial billing
    if (!empty($trial_billing_cycle)) {
        $output .= '<input type="hidden" name="a1" value="' . $trial_billing_amount . '" /><input type="hidden" name="p1" value="' . $trial_billing_cycle . '" /><input type="hidden" name="t1" value="' . $trial_billing_cycle_term . '" />';
    }
    //Main subscription billing
    if (!empty($billing_cycle)) {
        $output .= '<input type="hidden" name="a3" value="' . $billing_amount . '" /><input type="hidden" name="p3" value="' . $billing_cycle . '" /><input type="hidden" name="t3" value="' . $billing_cycle_term . '" />';
    }
    //Re-attempt on failure
    if ($billing_reattempt != '') {
        $output .= '<input type="hidden" name="sra" value="1" />';
    }
    //Reccurring times
    if ($billing_cycle_count > 1) { //do not include srt value if billing cycle count set to 1 or a negetive number.
        $output .= '<input type="hidden" name="src" value="1" /><input type="hidden" name="srt" value="' . $billing_cycle_count . '" />';
    } else if (empty($billing_cycle_count)) {
        $output .= '<input type="hidden" name="src" value="1" />';
    }

    //Other required data
    $output .= '<input type="hidden" name="no_shipping" value="1" />'; //Do not prompt for an address    
    $output .= '<input type="hidden" name="notify_url" value="' . $notify_url . '" />';
    $output .= '<input type="hidden" name="return" value="' . $return_url . '" />';
    $output .= '<input type="hidden" name="cancel_return" value="' . $cancel_url . '" />';
    $output .= '<input type="hidden" name="custom" value="' . $custom_field_value . '" />';

    //Filter to add additional payment input fields to the form (example: langauge code or country code etc).
    $output .= apply_filters('emember_pp_payment_form_additional_fields', '');

    //Submit button
    $button_image_url = get_post_meta($button_id, 'button_image_url', true);
    if (!empty($button_image_url)) {
        $output .= '<input type="image" src="' . $button_image_url . '" class="emember-subscription-button-submit" alt="' . 'Subscribe Now' . '"/>';
    } else {
        $button_text = (isset($args['button_text'])) ? $args['button_text'] : 'Subscribe Now';
        $output .= '<input type="submit" class="emember-subscription-button-submit" value="' . $button_text . '" />';
    }

    $output .= '</form>'; //End .form
    $output .= '</div>'; //End .emember_button_wrapper

    return $output;
}

/* * ************************************************
 * Braintree Buy Now button shortcode handler
 * *********************************************** */
add_filter('emember_payment_button_shortcode_for_braintree_buy_now', 'emember_render_braintree_buy_now_button_sc_output', 10, 2);

function emember_render_braintree_buy_now_button_sc_output($button_code, $args) {

    $button_id = isset($args['id']) ? $args['id'] : '';
    if (empty($button_id)) {
        return '<p class="emember-red-box">Error! emember_render_braintree_buy_now_button_sc_output() function requires the button ID value to be passed to it.</p>';
    }

    //Get class option for button styling, set Stripe's default if none specified
    $class = isset($args['class']) ? $args['class'] : '';

    $button_text = (isset($args['button_text'])) ? $args['button_text'] : 'Buy Now';

    //Check new_window parameter
    $window_target = isset($args['new_window']) ? 'target="_blank"' : '';

    $settings = Emember_Config::getInstance();
    $button_cpt = get_post($button_id); //Retrieve the CPT for this button

    $item_name = htmlspecialchars($button_cpt->post_title);

    $membership_level_id = get_post_meta($button_id, 'membership_level_id', true);
    //Verify that this membership level exists (to prevent user paying for a level that has been deleted)
    if (!emember_membership_level_id_exists($membership_level_id)) {
        return '<p class="emember-red-box">Error! The membership level specified in this button does not exist. You may have deleted this membership level. Edit the button and use the correct membership level.</p>';
    }

    //Payment amount and currency
    $payment_amount = get_post_meta($button_id, 'payment_amount', true);
    if (!is_numeric($payment_amount)) {
        return '<p class="emember-red-box">Error! The payment amount value of the button must be a numeric number. Example: 49.50 </p>';
    }
    $payment_amount = round($payment_amount, 2); //round the amount to 2 decimal place.
    $payment_amount_formatted = number_format($payment_amount, 2, '.', '');
    $payment_currency = get_post_meta($button_id, 'currency_code', true);

    //Return, cancel, notifiy URLs
    $return_url = get_post_meta($button_id, 'return_url', true);
    if (empty($return_url)) {
        $return_url = home_url();
    }

    $return_url = urlencode($return_url);

    $notify_url = WP_EMEMBER_SITE_HOME_URL . '/?emember_process_braintree_buy_now=1'; //We are going to use it to do post payment processing.

    $user_ip = get_real_ip_addr();
    $_SESSION['emember_payment_button_interaction'] = $user_ip;

    //Custom field data
    $custom_args = array('level_id' => $membership_level_id);
    $custom_field_value = get_wp_emember_custom_field_val($custom_args);
    $custom_field_value = urlencode($custom_field_value); //URL encode the custom fields.

    $sandbox_enabled = $settings->GetValue('eMember_enable_sandbox');

    if ($sandbox_enabled) {
        $braintree_env = "sandbox";
    } else {
        $braintree_env = "production";
    }

    require_once WP_EMEMBER_PATH . 'lib/braintree/lib/Braintree.php';

    try {
        Braintree_Configuration::environment($braintree_env);
        Braintree_Configuration::merchantId(get_post_meta($button_id, 'braintree_merchant_acc_id', true));
        Braintree_Configuration::publicKey(get_post_meta($button_id, 'braintree_public_key', true));
        Braintree_Configuration::privateKey(get_post_meta($button_id, 'braintree_private_key', true));
        $clientToken = Braintree_ClientToken::generate();
    } catch (Exception $e) {
        $e_class = get_class($e);
        $ret = 'Braintree Pay Now button error: ' . $e_class;
        if ($e_class == "Braintree\Exception\Authentication")
            $ret .= "<br />API keys are incorrect. Double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.";
        return $ret;
    }

    if (wp_emember_is_member_logged_in()) {
        $emember_auth = Emember_Auth::getInstance();
        $member_id = $emember_auth->getUserInfo('member_id');
        $member_first_name = $emember_auth->getUserInfo('first_name');
        $member_last_name = $emember_auth->getUserInfo('last_name');
        $member_email = $emember_auth->getUserInfo('email');
    }

    $uniqid = uniqid(); // Get unique ID to ensure several buttons can be added to one page without conflicts

    /* === Braintree Buy Now Button Form === */
    $output = '';
    $output .= '<div class="emem-button-wrapper emem-braintree-buy-now-wrapper">';
    $output .= '<form action="' . $notify_url . '" method="POST">';
    $output .= '<div id="emem-form-cont-' . $uniqid . '" class="emem-braintree-form-container emem-form-container-' . $button_id . '" style="display:none;"></div>';
    $output .= '<div id="emem-braintree-additional-fields-container-' . $uniqid . '" class="emem-braintree-additional-fields-container emem-braintree-additional-fields-container-' . $button_id . '" style="display:none;">';
    $output .= '<p><input type="text" name="first_name" placeholder="First Name" value="' . (isset($member_first_name) ? $member_first_name : '') . '" required></p>';
    $output .= '<p><input type="text" name="last_name" placeholder="Last Name" value="' . (isset($member_last_name) ? $member_last_name : '') . '" required></p>';
    $output .= '<p><input type="text" name="member_email" placeholder="Email" value="' . (isset($member_email) ? $member_email : '') . '" required></p>';
    $output .= '<div id="emem-braintree-amount-container-' . $uniqid . '" class="emem-braintree-amount-container"><p>' . $payment_amount_formatted . ' ' . $payment_currency . '</p></div>';
    $output .= '</div>';

    $output .= wp_nonce_field('stripe_payments', '_wpnonce', true, false);
    $output .= '<input type="hidden" name="item_number" value="' . $button_id . '" />';
    $output .= "<input type='hidden' value='{$item_name}' name='item_name' />";
    $output .= "<input type='hidden' value='{$payment_amount}' name='item_price' />";
    $output .= "<input type='hidden' value='{$payment_currency}' name='currency_code' />";
    $output .= "<input type='hidden' value='{$return_url}' name='return_url' />";
    $output .= '<input type="hidden" name="custom" value="' . $custom_field_value . '" />';

    $output .= '<input type="submit" id="emem-show-form-btn-' . $uniqid . '" class="emem-braintree-pay-now-button emem-braintree-show-form-button-' . $button_id . ' ' . $class . '" value="' . $button_text . '">';
    $output .= '<script src="https://js.braintreegateway.com/js/braintree-2.32.1.min.js"></script>';
    $output .= '<script>';
    $output .= 'jQuery("#emem-show-form-btn-' . $uniqid . '").click(function (e) {';
    $output .= 'e.preventDefault();';
    $output .= 'jQuery(this).unbind("click");';
    $output .= 'jQuery(this).attr("id","emem-submit-form-btn-' . $uniqid . '");';
    $output .= 'document.getElementById(\'emem-form-cont-' . $uniqid . '\').style.display = "block";';
    $output .= "braintree.setup('" . $clientToken . "', 'dropin', {container: 'emem-form-cont-" . $uniqid . "', ";
    $output .= "onReady: function(obj){document.getElementById('emem-braintree-additional-fields-container-" . $uniqid . "').style.display = \"block\";}});";
    $output .= 'return false;';
    $output .= '});';
    $output .= '</script>';
    
    //Filter to add additional payment input fields to the form.
    $output .= apply_filters('emember_braintree_payment_form_additional_fields', '');

    $output .= "</form>";
    $output .= '</div>'; //End .emem_button_wrapper

    return $output;
}
