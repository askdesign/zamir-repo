<?php
//Render the all payment buttons tab
?>

<div class="eMember_grey_box">
    <p>All the membership buttons that you created in the plugin are displayed here.</p>
</div>

<?php
include_once(WP_EMEMBER_PATH . 'includes/admin-side/class.emember-payment-buttons-list-table.php');
//Create an instance of our package class...
$payments_buttons_table = new EmemberPaymentButtonsListTable();

//Fetch, prepare, sort, and filter our data...
$payments_buttons_table->prepare_items();

?>

<form id="emember-payment-buttons-form" method="post" onSubmit="return confirm('Are you sure you want to perform this bulk operation on the selected entries?');">

    <input type="hidden" name="page" value="" />
    <!-- Now we can render the completed list table -->
    <?php $payments_buttons_table->display(); ?>
</form>

<p>
    <a href="admin.php?page=emember_payments&tab=create_new_button" class="button">Create New Button</a>
</p>
