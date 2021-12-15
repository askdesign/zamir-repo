<?php
//Render the transactions menu page
?>

<div class="eMember_grey_box">
    <p>The payments/transactions from your members are recorded here.</p>
</div>

<div class="postbox">
    <h3 class="hndle"><label for="title">Search Transaction</label></h3>
    <div class="inside">
        Search for a transaction by using email, name or transaction ID
        <br /><br />
        <form method="post" action="">
            <input name="emember_txn_search" type="text" size="40" value="<?php echo isset($_POST['emember_txn_search']) ? esc_attr($_POST['emember_txn_search']) : ''; ?>"/>
            <input type="submit" name="emember_txn_search_btn" class="button" value="Search" />
        </form>
    </div>
</div>

<?php
include_once(WP_EMEMBER_PATH . '/includes/admin-side/class.emember-transactions-list-table.php');
//Create an instance of our package class...
$transactions_list_table = new eMemberTransactionsListTable();

//Check if an action was performed
if (isset($_REQUEST['action'])) { //Do list table form row action tasks
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete_txn') { //Delete link was clicked for a row in list table
        $record_id = sanitize_text_field($_REQUEST['id']);
        $record_id = absint($record_id);
        $transactions_list_table->delete_record($record_id);
        $success_msg = '<div id="message" class="updated"><p><strong>';
        $success_msg .= 'The selected entry was deleted!';
        $success_msg .= '</strong></p></div>';
        echo $success_msg;
    }
}

//Fetch, prepare, sort, and filter our data...
$transactions_list_table->prepare_items();
?>
<form id="tables-filter" method="get" onSubmit="return confirm('Are you sure you want to perform this bulk operation on the selected entries?');">
    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
    <!-- Now we can render the completed list table -->
    <?php $transactions_list_table->display(); ?>
</form>
