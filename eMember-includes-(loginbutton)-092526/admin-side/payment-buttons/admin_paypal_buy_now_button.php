<?php

function emember_render_save_edit_pp_buy_now_interface($render_data, $is_edit_mode = false) {

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

	//$all_levels = dbAccess::findAll(WP_EMEMBER_MEMBERSHIP_LEVEL_TABLE, ' id != 1 ', ' id DESC ');
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
        <p>View <a href="https://www.tipsandtricks-hq.com/wordpress-membership/creating-paypal-buy-now-button-membership-payment-1612" target="_blank">the documentation</a> to learn how to create a PayPal Buy Now payment button and use it.</p>
    </div>

	<div class="postbox">
		<h3 class="hndle"><label for="title"><?php echo 'PayPal Buy Now Button Configuration'; ?></label></h3>
		<div class="inside">

			<form id="pp_button_config_form" method="post">
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
						<th scope="row"><?php echo 'Payment Amount'; ?></th>
						<td>
							<input type="text" size="6" name="payment_amount" value="<?php echo ($is_edit_mode ? $render_data['payment_amount'] : ''); ?>" required />
							<p class="description">Enter payment amount. Example values: 10.00 or 19.50 or 299.95 etc (do not put currency symbol).</p>
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
						<th scope="row"><?php echo 'Return URL'; ?></th>
						<td>
							<input type="text" size="100" name="return_url" value="<?php echo ($is_edit_mode ? $render_data['return_url'] : ''); ?>" />
							<p class="description">This is the URL the user will be redirected to after a successful payment. Enter the URL of your Thank You page here.</p>
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
						<th scope="row"><?php echo 'Button Image URL'; ?></th>
						<td>
							<input type="text" size="100" name="button_image_url" value="<?php echo ($is_edit_mode ? $render_data['button_image_url'] : ''); ?>" />
							<p class="description">If you want to customize the look of the button using an image then enter the URL of the image.</p>
						</td>
					</tr>

				</table>

				<p class="submit">
					<input type="submit" name="emember_pp_buy_now_<?php echo ($is_edit_mode ? 'edit' : 'save'); ?>_submit" class="button-primary" value="<?php echo 'Save Payment Data'; ?>" >
				</p>

			</form>
		</div>
	</div>
	<?php
}

add_action('emember_create_new_button_for_pp_buy_now', 'emember_create_new_pp_buy_now_button');

function emember_create_new_pp_buy_now_button() {

	$render_data = array(
		'button_type' => sanitize_text_field($_REQUEST['button_type']),
		);

	emember_render_save_edit_pp_buy_now_interface($render_data);
}

add_action('ememember_edit_payment_button_for_pp_buy_now', 'emember_edit_pp_buy_now_button');

function emember_edit_pp_buy_now_button() {

	//Retrieve the payment button data and present it for editing.

	$button_id = sanitize_text_field($_REQUEST['button_id']);
	$button_id = absint($button_id);

	$button = get_post($button_id); //Retrieve the CPT for this button

	$render_data = array(
		'button_id' => $button_id,
		'button_type' => sanitize_text_field($_REQUEST['button_type']),
		'button_name' => $button->post_title,
		'membership_level_id' => get_post_meta($button_id, 'membership_level_id', true),
		'payment_amount' => get_post_meta($button_id, 'payment_amount', true),
		'payment_currency' => get_post_meta($button_id, 'payment_currency', true),
		'return_url' => get_post_meta($button_id, 'return_url', true),
		'paypal_email' => get_post_meta($button_id, 'paypal_email', true),
		'button_image_url' => get_post_meta($button_id, 'button_image_url', true),
		);

	emember_render_save_edit_pp_buy_now_interface($render_data,true);
}

add_action('emember_create_new_button_process_submission', 'emember_save_edit_pp_buy_now_button_data');
add_action('emember_edit_payment_button_process_submission', 'emember_save_edit_pp_buy_now_button_data');

function emember_save_edit_pp_buy_now_button_data() {

	function emember_pp_buy_now_add_update_post_meta($id,$name,$data,$is_save_event) {
		if ($is_save_event) add_post_meta($id,$name,$data);
		else update_post_meta($id,$name,$data);
	}

	$is_save_event=false;

	if (isset($_REQUEST['emember_pp_buy_now_save_submit'])) {
		//This is a Paypal buy now button SAVE event.

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
	}

	else if (isset($_REQUEST['emember_pp_buy_now_edit_submit'])) {
		//This is a Paypal buy now button EDIT event.

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
	}
	else return; //no event

	emember_pp_buy_now_add_update_post_meta($button_id, 'button_type', $button_type,$is_save_event);
	emember_pp_buy_now_add_update_post_meta($button_id, 'membership_level_id', sanitize_text_field($_REQUEST['membership_level_id']),$is_save_event);
	emember_pp_buy_now_add_update_post_meta($button_id, 'payment_amount', trim(sanitize_text_field($_REQUEST['payment_amount'])),$is_save_event);
	emember_pp_buy_now_add_update_post_meta($button_id, 'payment_currency', sanitize_text_field($_REQUEST['payment_currency']),$is_save_event);
	emember_pp_buy_now_add_update_post_meta($button_id, 'return_url', trim(sanitize_text_field($_REQUEST['return_url'])),$is_save_event);
	emember_pp_buy_now_add_update_post_meta($button_id, 'paypal_email', trim(sanitize_email($_REQUEST['paypal_email'])),$is_save_event);
	emember_pp_buy_now_add_update_post_meta($button_id, 'button_image_url', trim(sanitize_text_field($_REQUEST['button_image_url'])),$is_save_event);

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
