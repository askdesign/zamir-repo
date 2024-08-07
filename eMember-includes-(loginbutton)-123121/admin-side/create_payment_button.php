<?php
include_once(WP_EMEMBER_PATH . '/includes/admin-side/payment-buttons/admin_paypal_buy_now_button.php');
include_once(WP_EMEMBER_PATH . '/includes/admin-side/payment-buttons/admin_paypal_subscription_button.php');
include_once(WP_EMEMBER_PATH . '/includes/admin-side/payment-buttons/admin_braintree_buy_now_button.php');

do_action('emember_create_new_button_process_submission'); //Addons can use this hook to save the data after the form submit then redirect to the "edit" interface of that newly created button.

if (!isset($_REQUEST['emember_button_type_selected'])) {
    //Button type hasn't been selected. Show the selection option.
    //Let's check if Membership Levels exists first
    $all_levels = dbAccess::findAll(WP_EMEMBER_MEMBERSHIP_LEVEL_TABLE, ' id != 1 ', ' id DESC ');
    if (empty($all_levels)) { //No Membership Levels found yet. Let's display a message.
        echo '<div class="notice notice-error"><p>No Membership Levels found. Before creating payment buttons, you need to add membership levels first.</p>
		<p><a href="admin.php?page=eMember_membership_level_menu">Click here</a> to go to membership levels page to create a new level.</p>
		</div>';
        return;
    } else {
        ?>

        <div class="eMember_grey_box">
            <p>You can create new payment buttons for your memberships using this interface.</p>
        </div>

        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php echo 'Select Payment Button Type'; ?></label></h3>
            <div class="inside">
                <form action="" method="post">
                    <input type="radio" name="button_type" value="pp_buy_now" checked><?php echo 'PayPal Buy Now'; ?>
                    <br />
                    <input type="radio" name="button_type" value="pp_subscription"><?php echo 'PayPal Subscription'; ?>
                    <br />
                    <input type="radio" name="button_type" value="braintree_buy_now"><?php echo 'Braintree Buy Now'; ?>
                    <br />
                    <?php
                    apply_filters('emember_new_button_select_button_type', '');
                    ?>

                    <br />
                    <input type="submit" name="emember_button_type_selected" class="button-primary" value="<?php echo 'Next'; ?>" />
                </form>

            </div>
        </div><!-- end of .postbox -->
        <?php
    }
} else {
    //Button type has been selected. Show the payment button configuration option.
    //Fire the action hook. The addons can render the payment button configuration option as appropriate.
    $button_type = sanitize_text_field($_REQUEST['button_type']);
    do_action('emember_create_new_button_for_' . $button_type);
    //The payment addons will create the button from then redirect to the "edit" interface of that button after save.
}
