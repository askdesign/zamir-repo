<?php

if (!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class eMemberTransactionsListTable extends WP_List_Table {

	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct(array(
			'singular' => 'transaction', //singular name of the listed records
			'plural' => 'transactions', //plural name of the listed records
			'ajax' => false //does this table support ajax?
			));
	}

	function column_default($item, $column_name) {
		//Just print the data for that column
		return $item[$column_name];
	}

	function column_id($item) {

		//Build row actions
		$actions = array(
			/* 'edit' => sprintf('<a href="admin.php?page=emember_payments&edit_txn=%s">Edit</a>', $item['id']),//TODO - Will be implemented in a future date */
			'delete' => sprintf('<a href="admin.php?page=emember_payments&action=delete_txn&id=%s" onclick="return confirm(\'Are you sure you want to delete this record?\')">Delete</a>', $item['id']),
			);

		//Return the refid column contents
		return $item['id'] . $this->row_actions($actions);
	}

	function column_member_profile($item)
	{
		global $wpdb;
		$members_table_name = $wpdb->prefix . "wp_eMember_members_tbl";
		$member_id = $item['member_id'];
		$subscr_id = $item['subscr_id'];
		$column_value = '';

		if(empty($member_id)){//Lets try to get the member id using unique reference
			if(!empty($subscr_id)){
				$resultset = $wpdb->get_row($wpdb->prepare("SELECT * FROM $members_table_name where subscr_id=%s", $subscr_id), OBJECT);
				if ($resultset) {
					//Found a record
					$member_id = $resultset->member_id;
				}
			}
		}
		
		if(!empty($member_id)){
			$profile_page = 'admin.php?page=wp_eMember_manage&members_action=add_edit&editrecord='.$member_id;
			$column_value = '<a href="'.$profile_page.'">View Profile</a>';
		}
		else{
			$column_value = '';
		}
		return $column_value;
	}
	
	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				/* $1%s */ $this->_args['singular'], //Let's reuse singular label (affiliate)
				/* $2%s */ $item['id'] //The value of the checkbox should be the record's key/id
				);
	}

	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
			'id' => 'Row ID',
			'email' => 'Email Address',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'txn_date' => 'Date',
			'txn_id' => 'Transaction ID',
			'subscr_id' => 'Subscriber ID',
			'payment_amount' => 'Amount',
			'membership_level' => 'Membership Level',
			'member_profile' => 'Member Profile',                
			);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'id' => array('id', false), //true means its already sorted
			'membership_level' => array('membership_level', false),
			'last_name' => array('last_name', false),
			'txn_date' => array('txn_date', false),
			);
		return $sortable_columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete'
			);
		return $actions;
	}

	function process_bulk_action() {
		//Detect when a bulk action is being triggered... 
		if ('delete' === $this->current_action()) {
			if (empty($_GET['transaction'])) {
				echo '<div id="message" class="notice notice-warning fade"><p>Please select items to perform a bulk action!</p></div>';
				return;
			}
			$records_to_delete = array_map( 'sanitize_text_field', $_GET['transaction'] );
			if (empty($records_to_delete)) {
				echo '<div id="message" class="notice notice-error fade"><p>Error! You need to select multiple records to perform a bulk action!</p></div>';
				return;
			}
			foreach ($records_to_delete as $record_id) {
				if( !is_numeric( $record_id )){
					wp_die('Error! ID must be numeric.');
				}                
				$this->delete_record($record_id);
			}
			echo '<div id="message" class="updated fade"><p>Selected records deleted successfully!</p></div>';
		}
	}

	function delete_record($record_id) {
		wp_delete_post($record_id,true);
	}

	function prepare_items() {
		global $wpdb;
		
		// Lets decide how many records per page to show
		$per_page = apply_filters('emember_transactions_menu_items_per_page', 50);

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->process_bulk_action();

		//This checks for sorting input. Read and sanitize the inputs
		$orderby_column = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : '';
		$sort_order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : '';
		if (empty($orderby_column)) {
			$orderby_column = "id";
			$sort_order = "DESC";
		}
		$orderby_column = emember_sanitize_value_by_array($orderby_column, $sortable);
		$sort_order = emember_sanitize_value_by_array($sort_order, array('DESC' => '1', 'ASC' => '1'));   

		//pagination requirement
		$current_page = (isset($_GET['paged']) ? sanitize_text_field($_GET['paged']) : 1);

		$data = array();

		if (isset($_POST['emember_txn_search_btn'])) {//Only load the searched records
			$search_term = trim(sanitize_text_field($_POST['emember_txn_search']));

			$cpt_args = array(
				'order' => $sort_order,
				'orderby' => $orderby_column,
				'post_type' => 'emem_transactions',
				'post_status' => 'publish',
				'posts_per_page' => $per_page,
				'paged' => $current_page,
				'meta_query' => array (
					'relation' => 'OR',
					array(
						'key' => 'email',
						'value' => $search_term,
						'compare' => 'LIKE'
						),
					array(
						'key' => 'txn_id',
						'value' => $search_term,
						'compare' => 'LIKE'
						),					
					array(
						'key' => 'first_name',
						'value' => $search_term,
						'compare' => 'LIKE'
						),	
					array(
						'key' => 'last_name',
						'value' => $search_term,
						'compare' => 'LIKE'
						),	
					)
				); 
		} else {//Load all data in an optimized way (so it is only loading data for the current page)
			$cpt_args = array(
				'order' => $sort_order,
				'orderby' => $orderby_column,
				'post_type' => 'emem_transactions',
				'post_status' => 'publish',
				'posts_per_page' => $per_page,
				'paged' => $current_page
				);           
		}
		$items = new WP_Query($cpt_args);

		foreach ($items->posts as $item) {
			$data[]=array(
				'id' => $item->ID,
				'email' => $item->email,
				'first_name' => $item->first_name,
				'last_name' => $item->last_name,
				'txn_date' => $item->txn_date,
				'txn_id' => $item->txn_id,
				'subscr_id' => $item->subscr_id,
				'payment_amount' => $item->payment_amount,
				'membership_level' => $item->membership_level,
				'member_profile' => 'Member Profile',
				'member_id' => $item->member_id,
                );
		}

		$count_args=$cpt_args;

		unset($count_args['posts_per_page']);
		unset($count_args['paged']);

		$counts = new WP_Query($count_args);
		$total_items = $counts->post_count;
		// Now we add our *sorted* data to the items property, where it can be used by the rest of the class.
		$this->items = $data;

		//pagination requirement
		$this->set_pagination_args(array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page' => $per_page, //WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
			));
	}

}
