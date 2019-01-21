<?php
/**
 * Create Mailpoet_Bounce_Log class that will extend the WP_List_Table
 */
class Mailpoet_Bounce_Log extends WP_List_Table
{
	public function __construct(){
		parent::__construct();
		add_filter('views_mailpoet_page_mailpoet_bounce_handling', array($this, 'button_before_table'));
	}
	public function button_before_table($views){
		$views['show'] = sprintf('<button id="delete-all-log" type="button"  title="%s" class="button button-link-delete">%s</button>', __('Delete All', 'bounce-handler-mailpoet'), __('Delete All', 'bounce-handler-mailpoet'));
		$views['search'] = sprintf('<input type="search" id="log-search-input" name="s" value=""><button id="search-submit" class="button">%s</button>', __('Search', 'bounce-handler-mailpoet'));
    	return $views;
	}

	public function extra_tablenav($which) {
		if($which=='top') {
			$html = sprintf('<div class="custom-show">%1$s 
			<select id="log-show-per-page-select">
  				<option value="10">10</option>
  				<option value="25">25</option>
				<option value="50">50</option>
				<option value="100">100</option>
			</select>
			 <button id="log-show-per-page" type="button"  title="%2$s" class="button">%3$s</button></div>', 
			 __('Logs per page', 'bounce-handler-mailpoet'), 
			 __('Show', 'bounce-handler-mailpoet'),
			 __('Show', 'bounce-handler-mailpoet')
			);
			$html .= sprintf('<div class="filter"><select id="log-filter-select">
		  				<option value="all">%1$s</option>
		  				<option value="mailbox_not_available">%2$s</option>
						<option value="mailbox_full">%3$s</option>
						<option value="message_delayed">%4$s</option>
						<option value="weird_forward">%5$s</option>
					</select>
					<button id="log-filter" type="button"  title="%6$s" class="button">%7$s</button></div>', 
					__('All', 'bounce-handler-mailpoet'),
					__('Mailbox Not Available', 'bounce-handler-mailpoet'),
					__('Mailbox Full', 'bounce-handler-mailpoet'),
					__('Message Delayed', 'bounce-handler-mailpoet'),
					__('Weird Forward', 'bounce-handler-mailpoet'),
					__('Filter', 'bounce-handler-mailpoet'),
					__('Filter', 'bounce-handler-mailpoet')

				);
			echo $html;
		}
	}
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $data = $this->table_data();

        usort( $data, array( &$this, 'usort_reorder' ) );
        if( isset($_GET['log_per_page']) ){
        	$perPage = $_GET['log_per_page'];
        }else{
        	$perPage = 10;
        }
        
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );
        $paged_data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $paged_data;
    }

    function usort_reorder( $a, $b ) {
      // If no sort, default to title
      $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'id';
      // If no order, default to asc
      $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
      // Determine sort order
      if($orderby == 'id') {
      	$result = $a[$orderby] - $b[$orderby];
      } else {
      	$result = strcmp( $a[$orderby], $b[$orderby] );
      }
      // Send final sort direction to usort
      return ( $order === 'asc' ) ? $result : -$result;
    }
    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
        	'cb'        => '<input type="checkbox" />',
            'id'        => __('ID','bounce-handler-mailpoet'),
            'email'     => __('Email', 'bounce-handler-mailpoet'),
            'reason'	=> __('Reason', 'bounce-handler-mailpoet'),
            'last_checked' => __('Last Checked', 'bounce-handler-mailpoet')
        );
        return $columns;
    }
    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }
    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array(
        	'id' => array('id', false),
        	'email'=> array('email', false),
        	'reason'=> array('reason', false),
        	'last_checked'=> array('last_checked', false),
        );
    }
    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
    	global $wpdb;
    	$bounceTable = $wpdb->prefix . 'bounced_email_logs';

    	$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    	if( $filter == 'all' ) {
    		$logs = $wpdb->get_results("SELECT * FROM {$bounceTable}", ARRAY_A);
    	} else {
    		$logs = $wpdb->get_results("SELECT * FROM {$bounceTable} WHERE reason='{$filter}'", ARRAY_A);
    	}

    	if( isset($_GET['search'])  ){
    		$search = $_GET['search'];
    		$logs = $wpdb->get_results("SELECT * FROM {$bounceTable} WHERE (id LIKE '%{$search}%' OR email LIKE '%{$search}%' OR reason LIKE '%{$search}%' OR last_checked LIKE '%{$search}%' )", ARRAY_A);
    	} 

    	if( !isset($_GET['search']) && !isset($_GET['filter']) ) {
    		$logs = $wpdb->get_results("SELECT * FROM {$bounceTable}", ARRAY_A);
    	}
        $data = array();
        foreach($logs as $log) {
	    	$data[] = array( "id" => (int)$log['id'], "email"=>$log['email'], "reason"=>$log['reason'], "last_checked"=>$log['last_checked']);	    	
        }
        return $data;
    }
    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
            case 'email':
            case 'reason':
            case 'last_checked':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
        }
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="log[]" value="%s" />', $item['id']
        );    
    }
    public function column_id($item) {
      	$actions = array(
            'delete'    => sprintf('<a href="?page=%1$s&id=%2$s#log-table" class="bounce-delete">%3$s</a>',$_REQUEST['page'], $item['id'], __('Delete', 'bounce-handler-mailpoet')),
        );

      	return sprintf('%1$s %2$s', $item['id'], $this->row_actions($actions) );
    }
    public function get_bulk_actions() {
      	$actions = array(
        	'delete'    => __('Delete', 'bounce-handler-mailpoet')
      	);
      	return $actions;
    }
}