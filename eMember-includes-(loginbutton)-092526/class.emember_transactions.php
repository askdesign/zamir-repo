<?php

/*
 * Provides helpful functions to deal with the payment transactions
 */

class eMember_Transactions {

    static function save_txn_record($ipn_data, $items = array()) {
        
        $current_date = date("Y-m-d");
        
        //Get the custom var array from the string
        //$custom_var_array = eMember_Transactions::parse_custom_var($ipn_data['custom']);
        parse_str($ipn_data['custom'], $custom_var_array);//Use the new technique to get the array from the key value string.
        eMember_log_debug('Custom field value array:', true);
        eMember_log_debug_array($custom_var_array, true);
                
        $txn_data = array();
        $txn_data['email'] = $ipn_data['payer_email'];
        $txn_data['first_name'] = $ipn_data['first_name'];
        $txn_data['last_name'] = $ipn_data['last_name'];
        $txn_data['member_id'] = isset($custom_var_array['eMember_id']) ? $custom_var_array['eMember_id'] : '';
        $txn_data['membership_level'] = isset($custom_var_array['subsc_ref']) ? $custom_var_array['subsc_ref'] : '';

        $txn_data['txn_date'] = $current_date;
        $txn_data['txn_id'] = $ipn_data['txn_id'];
        $txn_data['subscr_id'] = isset($ipn_data['subscr_id']) ? $ipn_data['subscr_id'] : '';
        $txn_data['reference'] = isset($custom_var_array['reference'])? $custom_var_array['reference'] : '';
        $txn_data['payment_amount'] = isset($ipn_data['mc_gross']) ? $ipn_data['mc_gross'] : 0;
        $txn_data['gateway'] = isset($ipn_data['gateway']) ? $ipn_data['gateway'] : '';
        $txn_data['status'] = isset($ipn_data['status']) ? $ipn_data['status'] : '';
        
        $txn_data = array_filter($txn_data);//Remove any null values.
        
        //Save the transaction data to CPT
	$txn_post_id = wp_insert_post(
            array(
            'post_title' => sanitize_text_field($txn_data['txn_id']),
            'post_type' => 'emem_transactions',
            'post_content' => '',
            'post_status' => 'publish'
            )
        );
        
        add_post_meta($txn_post_id,'email',$txn_data['email']);
        add_post_meta($txn_post_id,'first_name',$txn_data['first_name']);
        add_post_meta($txn_post_id,'last_name',$txn_data['last_name']);
        add_post_meta($txn_post_id,'member_id',isset($txn_data['member_id']) ? $txn_data['member_id'] : '');
        add_post_meta($txn_post_id,'membership_level',$txn_data['membership_level']);
        add_post_meta($txn_post_id,'txn_date',$txn_data['txn_date']);
        add_post_meta($txn_post_id,'txn_id',$txn_data['txn_id']);
        add_post_meta($txn_post_id,'subscr_id',isset($txn_data['subscr_id']) ? $txn_data['subscr_id'] : '');
        add_post_meta($txn_post_id,'reference',isset($txn_data['reference']) ? $txn_data['reference'] : '');
        add_post_meta($txn_post_id,'payment_amount',$txn_data['payment_amount']);
        add_post_meta($txn_post_id,'gateway',$txn_data['gateway']);
        add_post_meta($txn_post_id,'status',$txn_data['status']);
        
    }

    static function parse_custom_var($custom) {
        $delimiter = "&";
        $customvariables = array();

        $namevaluecombos = explode($delimiter, $custom);
        foreach ($namevaluecombos as $keyval_unparsed) {
            $equalsignposition = strpos($keyval_unparsed, '=');
            if ($equalsignposition === false) {
                $customvariables[$keyval_unparsed] = '';
                continue;
            }
            $key = substr($keyval_unparsed, 0, $equalsignposition);
            $value = substr($keyval_unparsed, $equalsignposition + 1);
            $customvariables[$key] = $value;
        }
        
        return $customvariables;
    }

}