<?php
/* * ***************************************************************
 * Render the new PayPal Subscription payment button creation interface
 * ************************************************************** */

function emember_render_save_edit_pp_subscription_interface($render_data, $is_edit_mode = false) {

	$currency_arr=array(
		'USD' => 'US Dollars ($)',
		'EUR' => 'Euros (€)',
		'GBP' => 'Pounds Sterling (£)',
		'AUD' => 'Australian Dollars ($)',
		'BRL' => 'Brazilian Real (R$)',
		'CAD' => 'Canadian Dollars ($)',
		'CNY' => 'Chinese Yuan',
		'CZK' => 'Czech Koruna',
		'DKK' => 'Danish Krone',
		'HKD' => 'Hong Kong Dollar ($)',
		'HUF' => 'Hungarian Forint',
		'INR' => 'Indian Rupee',
		'IDR' => 'Indonesia Rupiah',
		'ILS' => 'Israeli Shekel',
		'JPY' => 'Japanese Yen (¥)',
		'MYR' => 'Malaysian Ringgits',
		'MXN' => 'Mexican Peso ($)',
		'NZD' => 'New Zealand Dollar ($)',
		'NOK' => 'Norwegian Krone',
		'PHP' => 'Philippine Pesos',
		'PLN' => 'Polish Zloty',
		'SGD' => 'Singapore Dollar ($)',
		'ZAR' => 'South African Rand (R)',
		'KRW' => 'South Korean Won',
		'SEK' => 'Swedish Krona',
		'CHF' => 'Swiss Franc',
		'TWD' => 'Taiwan New Dollars',
		'THB' => 'Thai Baht',
		'TRY' => 'Turkish Lira',
		'VND' => 'Vietnamese Dong',
		);

        $all_levels = emember_get_all_membership_levels_list();
	$levels_str='';

	foreach ($all_levels as $level) {
		$levels_str.='<option value="'.$level->id.'"'.($is_edit_mode ? ($level->id==$render_data['membership_level_id'] ? ' selected':''):'') .'>'.stripslashes($level->alias).'</option>';
	}

	$currency_str='';
	foreach ($currency_arr as $key=>$value) {
		$currency_str.='<option value="'.$key.'"'.($is_edit_mode ? ($key==$render_data['payment_currency'] ? ' selected':''):'') .'>'.$value.'</option>';
	}
?>

    <div class="eMember_yellow_box">
        <p>View <a href="https://www.tipsandtricks-hq.com/wordpress-membership/creating-paypal-subscription-button-membership-payment-1620" target="_blank">the documentation</a> to learn how to create a PayPal Subscription payment button and use it.</p>
    </div>

	<form id="pp_button_config_form" method="post">

		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php echo 'PayPal Subscription Button Configuration'; ?></label></h3>
			<div class="inside">

				<input type="hidden" name="button_type" value="<?php echo sanitize_text_field($_REQUEST['button_type']); ?>">
				<?php if (!$is_edit_mode) { ?>
				<input type="hidden" name="emember_button_type_selected" value="1">
				<?php } ?>

				<table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">
					<?php if ($is_edit_mode) { ?>
					<tr valign="top">
						<th scope="row"><?php echo 'Button ID'; ?></th>
						<td>
							<input type="text" size="10" name="button_id" value="<?php echo $render_data['button_id']; ?>" readonly required />
							<p class="description">This is the ID of this payment button. It is automatically generated for you and it cannot be changed.</p>
						</td>
					</tr>
					<?php } ?>

					<tr valign="top">
						<th scope="row"><?php echo 'Button Title'; ?></th>
						<td>
							<input type="text" size="50" name="button_name" value="<?php echo ($is_edit_mode ? $render_data['button_name'] : ''); ?>" required />
							<p class="description">Give this membership payment button a name. Example: Gold membership payment</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo 'Membership Level'; ?></th>
						<td>
							<select id="membership_level_id" name="membership_level_id">
								<?php echo $levels_str; ?>
							</select>
							<p class="description">Select the membership level this payment button is for.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo 'Payment Currency'; ?></th>
						<td>
							<select id="payment_currency" name="payment_currency">
								<?php echo $currency_str; ?>
							</select>
							<p class="description">Select the currency for this payment button.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo 'PayPal Email'; ?></th>
						<td>
							<input type="text" size="50" name="paypal_email" value="<?php echo ($is_edit_mode ? $render_data['paypal_email'] : ''); ?>" required />
							<p class="description">Enter your PayPal email address. The payment will go to this PayPal account.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo 'Billing Amount Each Cycle'; ?></th>
						<td>
							<input type="text" size="6" name="billing_amount" value="<?php echo ($is_edit_mode ? $render_data['billing_amount'] : ''); ?>" required />
							<p class="description">Amount to be charged on every billing cycle. If used with a trial period then this amount will be charged after the trial period is over. Example values: 10.00 or 19.50 or 299.95 etc (do not put currency symbol).</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo 'Billing Cycle'; ?></th>
						<td>
							<input type="text" size="4" name="billing_cycle" value="<?php echo ($is_edit_mode ? $render_data['billing_cycle'] : ''); ?>" required />
							<select style="vertical-align: top;" id="billing_cycle_term" name="billing_cycle_term">
								<option value="D"<?php echo ($is_edit_mode ? ($render_data['billing_cycle_term']=="D" ? ' selected':''):''); ?>>Day(s)</option>
								<option value="M"<?php echo ($is_edit_mode ? ($render_data['billing_cycle_term']=="M" ? ' selected':''):''); ?>>Month(s)</option>
								<option value="Y"<?php echo ($is_edit_mode ? ($render_data['billing_cycle_term']=="Y" ? ' selected':''):''); ?>>Year(s)</option>
							</select>
							<p class="description">Set the interval of the recurring payment. Example value: 1 Month (if you want to charge every month)</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo 'Billing Cycle Count'; ?></th>
						<td>
							<input type="text" size="6" name="billing_cycle_count" value="<?php echo ($is_edit_mode ? $render_data['billing_cycle_count'] : ''); ?>" />
							<p class="description">After how many cycles should billing stop. Leave this field empty (or enter 0) if you want the payment to continue until the subscription is canceled.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo 'Re-attempt on Failure'; ?></th>
						<td>
							<input type="checkbox" name="billing_reattempt" value="1" <?php echo ($is_edit_mode ? (!empty($render_data['billing_reattempt']) ? 'checked' : '') : ''); ?>/>
							<p class="description">When checked, the payment will be re-attempted two more times if the payment fails. After the third failure, the subscription will be canceled..</p>
						</td>
					</tr>

				</table>

			</div>
		</div><!-- end of main button configuration box -->

		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php echo 'Trial Billing Details (Leave empty if you are not offering a trial period)'; ?></label></h3>
			<div class="inside">

				<table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">

					<tr valign="top">
						<th scope="row"><?php echo 'Trial Billing Amount'; ?></th>
						<td>
							<input type="text" size="6" name="trial_billing_amount" value="<?php echo ($is_edit_mode ? $render_data['trial_billing_amount'] : ''); ?>" />
							<p class="description">Amount to be charged for the trial period. Enter 0 if you want to offer a free trial period.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo 'Trial Billing Period'; ?></th>
						<td>
							<input type="text" size="4" name="trial_billing_cycle" value="<?php echo ($is_edit_mode ? $render_data['trial_billing_cycle'] : ''); ?>" />
							<select style="vertical-align: top;" id="tiral_billing_cycle_term" name="trial_billing_cycle_term">
								<option value="D"<?php echo ($is_edit_mode ? ($render_data['trial_billing_cycle_term']=="D" ? ' selected':''):''); ?>>Day(s)</option>
								<option value="M"<?php echo ($is_edit_mode ? ($render_data['trial_billing_cycle_term']=="M" ? ' selected':''):''); ?>>Month(s)</option>
								<option value="Y"<?php echo ($is_edit_mode ? ($render_data['trial_billing_cycle_term']=="Y" ? ' selected':''):''); ?>>Year(s)</option>
							</select>
							<p class="description">Length of the trial period</p>
						</td>
					</tr>

				</table>
			</div>
		</div><!-- end of trial billing details box -->

		<div class="postbox">
			<h3 class="hndle"><label for="title"><?php echo 'Optional Details'; ?></label></h3>
			<div class="inside">

				<table class="form-table" width="100%" border="0" cellspacing="0" cellpadding="6">

					<tr valign="top">
						<th scope="row"><?php echo 'Return URL'; ?></th>
						<td>
							<input type="text" size="100" name="return_url" value="<?php echo ($is_edit_mode ? $render_data['return_url'] : ''); ?>" />
							<p class="description">This is the URL the user will be redirected to after a successful payment. Enter the URL of your Thank You page here.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo 'Button Image URL'; ?></th>
						<td>
							<input type="text" size="100" name="button_image_url" value="<?php echo ($is_edit_mode ? $render_data['button_image_url'] : ''); ?>" />
							<p class="description">If you want to customize the look of the button using an image then enter the URL of the image.</p>
						</td>
					</tr>

				</table>
			</div>
		</div><!-- end of optional details box -->

		<p class="submit">
			<input type="submit" name="emember_pp_subscription_<?php echo ($is_edit_mode ? 'edit' : 'save'); ?>_submit" class="button-primary" value="<?php echo 'Save Payment Data'; ?>" >
		</p>

	</form>

<?php
}

add_action('emember_create_new_button_for_pp_subscription', 'emember_create_new_pp_subscription_button');

function emember_create_new_pp_subscription_button() {
	$render_data = array(
		'button_type' => sanitize_text_field($_REQUEST['button_type']),
		);
	emember_render_save_edit_pp_subscription_interface($render_data);
}

/*
 * Process submission and save the new PayPal Subscription payment button data
 */
add_action('emember_create_new_button_process_submission', 'emember_save_edit_pp_subscription_button_data');
add_action('emember_edit_payment_button_process_submission', 'emember_save_edit_pp_subscription_button_data');

function emember_save_edit_pp_subscription_button_data() {

	function emember_pp_subscription_add_update_post_meta($id,$name,$data,$is_save_event) {
		if ($is_save_event) add_post_meta($id,$name,$data);
		else update_post_meta($id,$name,$data);
	}

	$is_save_event=false;

	if (isset($_REQUEST['emember_pp_subscription_save_submit'])) {
		//This is a PayPal subscription button SAVE event. Process the submission.
		$is_save_event=true;
		$button_id = wp_insert_post(
			array(
				'post_title' => sanitize_text_field($_REQUEST['button_name']),
				'post_type' => 'emem_payment_button',
				'post_content' => '',
				'post_status' => 'publish'
				)
			);
		$button_type = sanitize_text_field($_REQUEST['button_type']);
	} else if (isset($_REQUEST['emember_pp_subscription_edit_submit'])) {
		//This is a PayPal subscription button EDIT event. Process the submission.
		$button_id = sanitize_text_field($_REQUEST['button_id']);
		$button_id = absint($button_id);
		$button_type = sanitize_text_field($_REQUEST['button_type']);
		$button_name = sanitize_text_field($_REQUEST['button_name']);

		$button_post = array(
			'ID' => $button_id,
			'post_title' => $button_name,
			'post_type' => 'emem_payment_button',
			);
		wp_update_post($button_post);
	} else return; //no event

	emember_pp_subscription_add_update_post_meta($button_id, 'button_type', $button_type,$is_save_event);
	emember_pp_subscription_add_update_post_meta($button_id, 'membership_level_id', sanitize_text_field($_REQUEST['membership_level_id']),$is_save_event);
	emember_pp_subscription_add_update_post_meta($button_id, 'payment_currency', sanitize_text_field($_REQUEST['payment_currency']),$is_save_event);
	emember_pp_subscription_add_update_post_meta($button_id, 'return_url', trim(sanitize_text_field($_REQUEST['return_url'])),$is_save_event);
	emember_pp_subscription_add_update_post_meta($button_id, 'paypal_email', trim(sanitize_email($_REQUEST['paypal_email'])),$is_save_event);
	emember_pp_subscription_add_update_post_meta($button_id, 'button_image_url', trim(sanitize_text_field($_REQUEST['button_image_url'])),$is_save_event);

		//Subscription billing details
	emember_pp_subscription_add_update_post_meta($button_id, 'billing_amount', sanitize_text_field($_REQUEST['billing_amount']),$is_save_event);
	emember_pp_subscription_add_update_post_meta($button_id, 'billing_cycle', sanitize_text_field($_REQUEST['billing_cycle']),$is_save_event);
	emember_pp_subscription_add_update_post_meta($button_id, 'billing_cycle_term', sanitize_text_field($_REQUEST['billing_cycle_term']),$is_save_event);
	emember_pp_subscription_add_update_post_meta($button_id, 'billing_cycle_count', sanitize_text_field($_REQUEST['billing_cycle_count']),$is_save_event);
	emember_pp_subscription_add_update_post_meta($button_id, 'billing_reattempt', isset($_REQUEST['billing_reattempt']) ? '1' : '',$is_save_event);

		//Trial billing details
	emember_pp_subscription_add_update_post_meta($button_id, 'trial_billing_amount', sanitize_text_field($_REQUEST['trial_billing_amount']),$is_save_event);
	emember_pp_subscription_add_update_post_meta($button_id, 'trial_billing_cycle', sanitize_text_field($_REQUEST['trial_billing_cycle']),$is_save_event);
	emember_pp_subscription_add_update_post_meta($button_id, 'trial_billing_cycle_term', sanitize_text_field($_REQUEST['trial_billing_cycle_term']),$is_save_event);

	if ($is_save_event) {
		//Redirect to the manage payment buttons interface
		$url = admin_url() . 'admin.php?page=emember_payments&tab=payment_buttons';
		if (!headers_sent()) {
			header('Location: ' . $url);
		} else {
			echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
		}
		exit;
	} else {
		echo '<div id="message" class="updated fade"><p>Payment button data successfully updated!</p></div>';
	}


}

/* * **********************************************************************
 * End of new PayPal subscription payment button stuff
 * ********************************************************************** */


/* * ***************************************************************
 * Render edit PayPal Subscription payment button interface
 * ************************************************************** */

add_action('ememember_edit_payment_button_for_pp_subscription', 'emember_edit_pp_subscription_button');

function emember_edit_pp_subscription_button() {

	//Retrieve the payment button data and present it for editing.

	$button_id = sanitize_text_field($_REQUEST['button_id']);
	$button_id = absint($button_id);

	$button = get_post($button_id); //Retrieve the CPT for this button

	$render_data = array(
		'button_id' => $button_id,
		'button_type' => sanitize_text_field($_REQUEST['button_type']),
		'button_name' => $button->post_title,
		'membership_level_id' => get_post_meta($button_id, 'membership_level_id', true),
		'payment_currency' => get_post_meta($button_id, 'payment_currency', true),
		'return_url' => get_post_meta($button_id, 'return_url', true),
		'paypal_email' => get_post_meta($button_id, 'paypal_email', true),
		'button_image_url' => get_post_meta($button_id, 'button_image_url', true),

		'billing_amount' => get_post_meta($button_id, 'billing_amount', true),
		'billing_cycle' => get_post_meta($button_id, 'billing_cycle', true),
		'billing_cycle_term' => get_post_meta($button_id, 'billing_cycle_term', true),
		'billing_cycle_count' => get_post_meta($button_id, 'billing_cycle_count', true),
		'billing_reattempt' => get_post_meta($button_id, 'billing_reattempt', true),

		'trial_billing_amount' => get_post_meta($button_id, 'trial_billing_amount', true),
		'trial_billing_cycle' => get_post_meta($button_id, 'trial_billing_cycle', true),
		'trial_billing_cycle_term' => get_post_meta($button_id, 'trial_billing_cycle_term', true),
		);

	emember_render_save_edit_pp_subscription_interface($render_data,true);
}

/************************************************************************
 * End of edit PayPal Subscription payment button stuff
 ************************************************************************/