<?php
/**
 * Contains the query functions for WooCommerce which alter the front-end post queries and loops.
 *
 * @class 		EG_Query
 * @version		2.3.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {exit;}

if ( ! class_exists( 'EG_Query' ) ) :

/**
 * EG_Query Class
 */
class EG_Query {

	/** @public array Query vars to add to wp */
	public $query_vars = array();

	/** @public array Query vars to add to wp */
	public $estimates = array();

	/** @public array Unfiltered product ids (before layered nav etc) */
	public $default_e 	= array(
		'todo' 				=> 'a:0:{}',
		'scope_categories' 	=> 'a:0:{}',
		'measurements' 		=> 'a:0:{}',
		'attachments' 		=> 'a:0:{}',
		'email_history'		=> 'a:0:{}',
		'contract_signature'=> 'a:0:{}',
		'stage' 			=> 'draft'
	);

	/** @public array Filtered product ids (after layered nav) */
	public $filtered_product_ids 	= array();

	/** @public array Filtered product ids (after layered nav, per taxonomy) */
	public $filtered_product_ids_for_taxonomy 	= array();

	/** @public array Product IDs that match the layered nav + price filter */
	public $post__in 		= array();

	/** @public array The meta query for the page */
	public $meta_query 		= '';

	/** @public array Post IDs matching layered nav only */
	public $layered_nav_post__in 	= array();

	/** @public array Stores post IDs matching layered nav, so price filter can find max price in view */
	public $layered_nav_product_ids = array();

	/**
	 * Constructor for the query class. Hooks in methods.
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoints' ) );
		add_action( 'init', array( $this, 'layered_nav_init' ) );
		add_action( 'init', array( $this, 'price_filter_init' ) );

		if ( ! is_admin() ) {
			add_action( 'wp_loaded', array( $this, 'get_errors' ), 20 );
			add_filter( 'query_vars', array( $this, 'add_query_vars'), 0 );
			add_action( 'parse_request', array( $this, 'parse_request'), 0 );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			add_filter( 'the_posts', array( $this, 'the_posts' ), 11, 2 );
			add_action( 'wp', array( $this, 'remove_product_query' ) );
			add_action( 'wp', array( $this, 'remove_ordering_args' ) );
		}

		$this->init_query_vars();
	}

	/**
	 * Get any errors from querystring
	 */
	public function get_errors() {
		if ( ! empty( $_GET['wc_error'] ) && ( $error = sanitize_text_field( $_GET['wc_error'] ) ) && ! wc_has_notice( $error, 'error' ) ) {
			wc_add_notice( $error, 'error' );
		}
	}

	/**
	 * Init query vars by loading options.
	 */
	public function init_query_vars() {
		// Query vars to add to WP
		$this->query_vars = array(
			// Checkout actions
			'order-pay'          => get_option( 'estimategadget_checkout_pay_endpoint', 'order-pay' ),
			'order-received'     => get_option( 'estimategadget_checkout_order_received_endpoint', 'order-received' ),

			// My account actions
			'view-order'         => get_option( 'estimategadget_myaccount_view_order_endpoint', 'view-order' ),
			'edit-account'       => get_option( 'estimategadget_myaccount_edit_account_endpoint', 'edit-account' ),
			'edit-address'       => get_option( 'estimategadget_myaccount_edit_address_endpoint', 'edit-address' ),
			'lost-password'      => get_option( 'estimategadget_myaccount_lost_password_endpoint', 'lost-password' ),
			'customer-logout'    => get_option( 'estimategadget_logout_endpoint', 'customer-logout' ),
			'add-payment-method' => get_option( 'estimategadget_myaccount_add_payment_method_endpoint', 'add-payment-method' ),
		);
	}

	/**
	 * Get page title for an endpoint
	 * @param  string
	 * @return string
	 */
	public function get_endpoint_title( $endpoint ) {
		global $wp;

		switch ( $endpoint ) {
			case 'order-pay' :
				$title = __( 'Pay for Order', 'estimategadget' );
			break;
			case 'order-received' :
				$title = __( 'Order Received', 'estimategadget' );
			break;
			case 'view-order' :
				$order = wc_get_order( $wp->query_vars['view-order'] );
				$title = ( $order ) ? sprintf( __( 'Order %s', 'estimategadget' ), _x( '#', 'hash before order number', 'estimategadget' ) . $order->get_order_number() ) : '';
			break;
			case 'edit-account' :
				$title = __( 'Edit Account Details', 'estimategadget' );
			break;
			case 'edit-address' :
				$title = __( 'Edit Address', 'estimategadget' );
			break;
			case 'add-payment-method' :
				$title = __( 'Add Payment Method', 'estimategadget' );
			break;
			case 'lost-password' :
				$title = __( 'Lost Password', 'estimategadget' );
			break;
			default :
				$title = '';
			break;
		}
		return $title;
	}

	/**
	 * Add endpoints for query vars
	 */
	public function add_endpoints() {
		foreach ( $this->query_vars as $key => $var ) {
			add_rewrite_endpoint( $var, EP_ROOT | EP_PAGES );
		}
	}

	/**
	 * add_query_vars function.
	 *
	 * @access public
	 * @param array $vars
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		foreach ( $this->query_vars as $key => $var ) {
			$vars[] = $key;
		}

		return $vars;
	}

	/**
	 * Get query vars
	 *
	 * @return array
	 */
	public function get_query_vars() {
		return 'Query Vars .  ';
		return $this->query_vars;
	}

	/**
	 * Get query current active query var
	 *
	 * @return string
	 */
	public function get_current_endpoint() {
		global $wp;
		foreach ( $this->get_query_vars() as $key => $value ) {
			if ( isset( $wp->query_vars[ $key ] ) ) {
				return $key;
			}
		}
		return '';
	}

	/**
	 * Parse the request and look for query vars - endpoints may not be supported
	 */
	public function parse_request() {
		global $wp;

		// Map query vars to their keys, or get them if endpoints are not supported
		foreach ( $this->query_vars as $key => $var ) {
			if ( isset( $_GET[ $var ] ) ) {
				$wp->query_vars[ $key ] = $_GET[ $var ];
			}

			elseif ( isset( $wp->query_vars[ $var ] ) ) {
				$wp->query_vars[ $key ] = $wp->query_vars[ $var ];
			}
		}
	}

	/**
	 * Hook into pre_get_posts to do the main product query
	 *
	 * @access public
	 * @param mixed $q query object
	 * @return void
	 */
	public function pre_get_posts( $q ) {
		// We only want to affect the main query
		
	}

	/**
	 * Get query vars
	 *
	 * @return array
	 */
	public function get_estimates() {
		return $this->estimates;
	}

	/**
	 * Get query vars
	 *
	 * @return array
	 */
	public function get_estimate($_id) {
		global $wpdb;$_ = $array;extract(EG()->tables);
		if ( ! empty($_id) && is_numeric($_id) ) {
			$_= $wpdb->get_row( 
				$wpdb->prepare("SELECT * FROM ".$_e_table." WHERE qe_id = %d", $_id),ARRAY_A 
			);
			$_=self::parse_db_entry($_);
		}
		return $_;
	}

	/**
	 * Add endpoints for query vars
	 */
	public function load_vars() {
		$this->estimates = $this->get_all_estimates();
	}

	/**
	 * Get query current active query var
	 *
	 * @return string
	 */
	public function get_all_estimates() {
		global $wpdb;
		$_ = $array;
		extract(EG()->tables);
		//$_est = $wpdb->get_row('SELECT * FROM `wp_qe_estimates` WHERE `qe_id` = '.$e_id, ARRAY_A);
		//$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}".EG()->tables[estimates]." WHERE qe_id = %d AND order_item_type = %s", $this->id, $type ) 
		$t= $wpdb->get_results( 
				$wpdb->prepare( "SELECT * FROM ".$_e_table,''),ARRAY_A 
			);
		// Map query vars to their keys, or get them if endpoints are not supported
		foreach ((array) $t as $_k => $_e ) {
			$_e=self::parse_db_entry($_e);
			$_[]=$_e;
		}
		return $_;
	}

	public function get_project_vars($e_id) {
		global $wpdb;
		$_ = array();extract(EG()->tables);
		$_est = $wpdb->get_row('SELECT * FROM `'.$_e_table.'` WHERE `qe_id` = '.$e_id, ARRAY_A);
		$_inv = $wpdb->get_row('SELECT * FROM `'.$_in_table.'` WHERE `qe_id` = '.$e_id, ARRAY_A);
		$_work = $this->get_work($e_id);
		$_client = $wpdb->get_row( 'SELECT * FROM `'.$_c_table.'` WHERE `id` = '.$_est[client_id],ARRAY_A);
		$_set=$this->get_settings();
		$_[_project]=$_est;$_[_client]=$_client;$_[_invoice]=$_inv;$_[_work]=$_work;$_[_settings]=$_set;
		//print_r($_);exit;
		$_[_invoice][invoices]=self::decode_db_array($_inv[invoices]);
		$_[_invoice][payments]=self::decode_db_array($_inv[payments]);
		$_[_invoice][add_cost]=self::decode_db_array($_inv[add_cost]);
		$_[_project][client]=self::decode_db_array($_est[client]);
		$_[_project][email_history]=self::decode_db_array($_est[email_history]);
		$_[_project][notes]=self::decode_db_array($_est[notes]);
		$_[_project][measurements]=self::decode_db_array($_est[measurements]);
		$_[_project][attachments]=self::decode_db_array($_est[attachments]);
		$_[_project][scope_categories]=self::decode_db_array($_est[scope_categories]);
		$_[_project][contacts]=self::decode_db_array($_est[contacts]);

		//$_attch_files;$_attch_photos;foreach((array) $_attachments as $k => $v){if( explode("/",$v[type])[0]=='image' ){$_attch_photos[$k]=$v;}else{$_attch_files[$k]=$v;}}
		
		//extract($_)
		//print_r();exit;
		

		return $_;
	}

	/**
	 * Parse the request and look for query vars - endpoints may not be supported
	 */
	public function parse_db_entry($a) {
		global $wp;
		//$_ = $array;
		//print_r($a[client]);exit;
		if ( ! empty($a) && is_array($a) ) {
			$a[client]=self::decode_db_array($a[client]);
			$a[contacts]=self::decode_db_array($a[contacts]);//contacts
			$a[attachments]=self::decode_db_array($a[attachments]);
			$a[measurements]=self::decode_db_array($a[measurements]);
			//print_r($a);exit;
		}
		return $a;
		// Map query vars to their keys, or get them if endpoints are not supported
		foreach ( $this->query_vars as $key => $var ) {
			
		}
	}

	public function encode_db_array($array) {
		global $wpdb;
		$_ = $array;
		$_ = json_encode($_, JSON_HEX_QUOT);
		$_ = base64_encode($_);
		return $_;
	}

	public function decode_db_array($str) {
		global $wpdb;
		$_ = $str;
		@$_ = base64_decode($_);
		//$_ = unserialize($_);
		$_ = stripslashes_deep(json_decode($_,true));
		//json_decode($item[labor],true);
		if($_==$str || empty($_)){return @unserialize($str);}
		return $_;
	}

	/**
	 * Get query current active query var
	 *
	 * @return string
	 */
	public function get_all_clients() {
		global $wpdb;
		$_ = $array;extract(EG()->tables);
		//$_est = $wpdb->get_row('SELECT * FROM `wp_qe_estimates` WHERE `qe_id` = '.$e_id, ARRAY_A);
		//$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}".EG()->tables[estimates]." WHERE qe_id = %d AND order_item_type = %s", $this->id, $type ) 
		$t= $wpdb->get_results( 
				$wpdb->prepare( "SELECT * FROM ".$_c_table,''),ARRAY_A 
			);
		// Map query vars to their keys, or get them if endpoints are not supported
		foreach ((array) $t as $_k => $_e ) {
			$_e=self::parse_db_entry($_e);
			$_[]=$_e;
		}
		return $_;
	}

	public function get_client($id) {
		global $wpdb;
		$_ = $array;extract(EG()->tables);
		$t = $wpdb->get_row('SELECT * FROM `'.$_c_table.'` WHERE `id` = '.$id, ARRAY_A);
		// Map query vars to their keys, or get them if endpoints are not supported
		foreach ((array) $t as $_k => $_e ) {
			$_e=self::parse_db_entry($_e);
			$_[$_k]=$_e;
		}
		return $_;
	}


	public function get_settings() { 
		global $wpdb;extract(EG()->tables);
		$ss = $wpdb->get_results('SELECT * FROM '.$_s_table);
		$temp;
		
		foreach($ss as $s) {
			if (strpos($s->name, 'templates') !== false) {
				$temp[$s->name]=self::decode_db_array($s->value);
			}else{$temp[$s->name]=($s->value);}
			
			//$temp[]=array($s->name => $s->value);
		
		}
		return $temp;
	}


	public function get_work($e_id) {
		global $wpdb;extract(EG()->tables);
		$_ = array();

		$_temp = $wpdb->get_results( 'SELECT w.scope_id as "id", s.name FROM `'.$_w_table.'` AS w INNER JOIN `wp_qe_scope` AS s ON w.scope_id = s.scope_id WHERE w.qe_id = '.$e_id.' group by w.scope_id',ARRAY_A);
		
		foreach((array) $_temp as $scope){
			$_[$scope[id]][name] = $scope[name];
			//print_r($_);exit;	
			$types = $wpdb->get_results( 'SELECT `type_id` as "id",`type_name` as "name" FROM `'.$_w_table.'` WHERE `scope_id` = '.$scope[id].' AND `qe_id` = '.$e_id.' GROUP BY type_id', ARRAY_A);
			foreach((array) $types as $type){
				$_[$scope[id]][types][$type[id]][name] = $type[name];
				$items = $wpdb->get_results( 'SELECT * FROM `'.$_w_table.'` WHERE `scope_id` = '.$scope[id].' AND `qe_id` = '.$e_id.' AND `type_id` = '.$type[id], ARRAY_A);
				$temp=array();
				foreach((array) $items as $item){
					$item[labor]=self::decode_db_array($item[labor]);
					$item[material]=self::decode_db_array($item[material]);
					$item[itemize]=self::decode_db_array($item[itemize]);
					//print_r($item[itemize]);exit;
					//if($item[labor][enabled]&&$item[labor][enabled]!=null)$_cost += $item[units]*$item[labor][cost];
					//if($item[material][enabled]&&$item[material][enabled]!=null)$_cost += $item[units]*$item[material][cost];
					//foreach((array) $item[itemize] as $it){if($it[enabled]&&$it[enabled]!=null)$_cost += $item[units]*$it[cost];}
					$temp[]= $item;
				}
				//print_r($temp);	
				$_[$scope[id]][types][$type[id]][items] = $temp;
				//print_r($_);exit;	
			
			}
			
		}
		
		
		return $_;
	}

	public function add_work($j) {
		global $wpdb;extract(EG()->tables);
		if (empty($e_id)){$e_id=EG()->session->get_id();}
		extract(EG()->query->get_project_vars($e_id));

		$r = $wpdb->get_results('SELECT * FROM `'.$_j_table.'` WHERE `id` = '.$j,ARRAY_A)[0];
		
		if(!empty($r[labor])&&is_numeric($r[labor])){
			$c=$r[labor];$l[name]='Labor';$l[time]=$r[est_time];
			$l[cost]=$c;$l[description]=trim($r[description]);
			$r[labor]=self::encode_db_array($l);
		}
		if(!empty($r[material])&&is_numeric($r[material])){
			$c=$r[material];$m[name]='Material';$m[time]=$r[est_time];
			$m[cost]=$c;$m[description]=trim($r[description]);
			$r[material]=self::encode_db_array($m);
			$r[itemize]=self::encode_db_array(trim($r[itemize]));
		}
		
		$r[description]=trim($r[description]);
		$r[job_id]= $j;unset($r[id]);
		foreach((array)$r as $k=>$v){if(empty($v)){unset($r[$k]);}}
		//print_r($r);exit;
		$wpdb->insert($_w_table,array('qe_id' => $e_id));
		$work_id = $wpdb->insert_id;
		$wpdb->update($_w_table, $r, array( 'work_id' => $work_id ));
		self::process_invoice($e_id);
		return $wpdb->get_row('SELECT * FROM `'.$_w_table.'` WHERE `work_id` = '.$work_id,ARRAY_A);
	}

	public function get_items() {
		global $wpdb;extract(EG()->tables);
		$_ = array();
		
		
		$_temp = $wpdb->get_results( 'SELECT * FROM `wp_qe_scope`',ARRAY_A);
		$_[_scopes]=$_temp;
		$_temp = $wpdb->get_results( 'SELECT `scope_id`,`type_id`,`type_name` FROM `wp_qe_job_list` GROUP BY `type_id`',ARRAY_A);
		$_[_types]=$_temp;
		$_temp = $wpdb->get_results( 'SELECT * FROM `wp_qe_job_list`',ARRAY_A);
		$_[_items]=$_temp;

		
		return $_;
	}

	public function get_items2() {
		global $wpdb;extract(EG()->tables);
		$_ = array();

		$_temp = $wpdb->get_results( 'SELECT `scope_id` as "id",`name` FROM `wp_qe_scope`',ARRAY_A);
		
		foreach((array) $_temp as $scope){
			$_[$scope[id]][name] = $scope[name];
				
			$types = $wpdb->get_results( 'SELECT `type_id` as "id",`type_name` as "name" FROM `'.$_j_table.'` WHERE `scope_id` = '.$scope[id].' GROUP BY type_id', ARRAY_A);
			
			foreach((array) $types as $type){
				
				$_[$scope[id]][types][$type[id]][name] = $type[name];
				$items = $wpdb->get_results( 'SELECT * FROM `'.$_j_table.'` WHERE `scope_id` = '.$scope[id].' AND `type_id` = '.$type[id], ARRAY_A);
				
				$temp=array();
				foreach((array) $items as $item){
					$item[labor]=self::decode_db_array($item[labor]);
					$item[material]=self::decode_db_array($item[material]);
					$item[itemize]=self::decode_db_array($item[itemize]);
					//if($item[labor][enabled]&&$item[labor][enabled]!=null)$_cost += $item[units]*$item[labor][cost];
					//if($item[material][enabled]&&$item[material][enabled]!=null)$_cost += $item[units]*$item[material][cost];
					//foreach((array) $item[itemize] as $it){if($it[enabled]&&$it[enabled]!=null)$_cost += $item[units]*$it[cost];}
					$temp[]= $item;
				}
				//print_r($temp);	
				$_[$scope[id]][types][$type[id]][items] = $temp;
				//print_r($_);exit;	
			
			}
			
		}
		
		
		return $_;
	}

	public function get_time_by_workid($work_id) {
		global $wpdb;
		
		$_;
		$_temp = $wpdb->get_row('SELECT * FROM `wp_qe_work_list` WHERE `work_id` = '.$work_id,ARRAY_A);
		$_temp[labor]=self::decode_db_array($_temp[labor]);
		$_temp[material]=self::decode_db_array($_temp[material]);
		$_temp[itemize]=self::decode_db_array($_temp[itemize]);
		if($_temp[labor][enabled]&&$_temp[labor][enabled]!=null)$_ += $_temp[units]*$_temp[labor][time];
		if($_temp[material][enabled]&&$_temp[material][enabled]!=null)$_ += $_temp[units]*$_temp[material][time];
		foreach((array) $_temp[itemize] as $item){if($item[enabled]&&$item[enabled]!=null)$_ += $_temp[units]*$item[time];}
		//print_r($_cost);exit;
		
		return $_;
	}
	public function get_cost_by_workid($work_id) {
		global $wpdb;
		$_ = array();
		$_t;$_l;$_m;$_i;
		$_temp = $wpdb->get_row('SELECT * FROM `wp_qe_work_list` WHERE `work_id` = '.$work_id,ARRAY_A);
		$_temp[labor]=self::decode_db_array($_temp[labor]);
		$_temp[material]=self::decode_db_array($_temp[material]);
		$_temp[itemize]=self::decode_db_array($_temp[itemize]);
		if($_temp[labor][enabled]&&$_temp[labor][enabled]!=null)$_l += $_temp[units]*$_temp[labor][cost];
		if($_temp[material][enabled]&&$_temp[material][enabled]!=null)$_m += $_temp[units]*$_temp[material][cost];
		foreach((array) $_temp[itemize] as $item){if($item[enabled]&&$item[enabled]!=null)$_i += $_temp[units]*$item[cost];}
		$_[total] = $_l + $_m + $_i;
		$_[labor] = $_l;
		$_[material] = $_m;
		$_[itemize] = $_i;
		
		return $_;
	}

	public function get_project_cost($e_id) {
		global $wpdb;extract(EG()->tables);if(empty($e_id)){return false;}
		extract(EG()->query->get_project_vars($e_id));
		$_ = array();
		$_t;$_l;$_m;$_i;$_o;
		$_temp = $wpdb->get_results( 'SELECT * FROM `'.$_w_table.'` WHERE `qe_id` = '.$e_id, ARRAY_A);
		//print_r($_invoice[add_cost]);exit;
		foreach((array) $_temp as $item){
			//print_r($item);exit;
			$item[labor]=self::decode_db_array($item[labor]);
			$item[material]=self::decode_db_array($item[material]);
			$item[itemize]=self::decode_db_array($item[itemize]);
			if($item[labor][enabled]&&$item[labor][enabled]!=null)$_l  += $item[units]*$item[labor][cost];
			if($item[material][enabled]&&$item[material][enabled]!=null)$_m += $item[units]*$item[material][cost];
			foreach((array) $item[itemize] as $it){if($it[enabled]&&$it[enabled]!=null)$_i += $item[units]*$it[cost];}
		}
		foreach((array)$_invoice[add_cost] as $k => $v) {
			if($v[type]=='percent'){
				$_o+= (($_l + $_m + $_i) * (float)('0.'.$v[value]));
			}else{
				$_o+= ((float)($v[value]));
			}
		}
		$_[total] = ($_l + $_m + $_i + $_o);
		$_[labor] = ($_l);
		$_[material] = ($_m);
		$_[itemize] = ($_i);
		$_[other] = ($_o);
		
		return $_;
	}

	public function get_invoices($e_id) {
		global $wpdb;
		$_ = array();
		$_est = self::get_estimate($e_id);
		$_cost;
		
		
		//print_r($_temp);exit;
		
		return $_cost;
	}

	public function add_payment($a,$e_id='') {
		global $wpdb;extract(EG()->tables);
		if (empty($e_id)){$e_id=EG()->session->get_id();}
		extract(EG()->query->get_project_vars($e_id));
		
		$_inv_id = intval($a[invoice_id]);//unset($a[invoice_id]);
		$_payments = $_invoice[payments];
		$_invoices = $_invoice[invoices];
		if($_inv_id>0){$_invoice = $_invoices[$_inv_id];$_invoice[status]='Paid';$_invoices[$_inv_id]=$_invoice;$new[invoices]=self::encode_db_array($_invoices);}
		//print_r($new);exit;
		@end($_payments);         // move the internal pointer to the end of the array
		@$key = key($_payments);  // fetches the key of the element pointed to by the internal pointer

		$_payments[$a[id]]=$a;
        //print_r($_payments);exit;
		$new[payments]=self::encode_db_array($_payments);

		$wpdb->update($_in_table, $new, array( 'qe_id' => $e_id ));
		
		self::process_invoice($e_id);
		return true;
	}
	public function delete_payment($a,$e_id='') {
		global $wpdb;extract(EG()->tables);
		if (empty($e_id)){$e_id=EG()->session->get_id();}
		extract(EG()->query->get_project_vars($e_id));
		
		$_inv_id = intval($a[invoice_id]);//unset($a[invoice_id]);
		$_payments = $_invoice[payments];
		$_invoices = $_invoice[invoices];
		if($_inv_id>0){$_invoice = $_invoices[$_inv_id];$_invoice[status]='UnPaid';$_invoices[$_inv_id]=$_invoice;$new[invoices]=self::encode_db_array($_invoices);}
		
		unset($_payments[$a[id]]);
        
		$new[payments]=self::encode_db_array($_payments);

		$wpdb->update($_in_table, $new, array( 'qe_id' => $e_id ));
		
		self::process_invoice($e_id);
		return true;
	}

	public function add_invoice($a,$e_id='') {
		global $wpdb;extract(EG()->tables);
		if (empty($e_id)){$e_id=EG()->session->get_id();}
		extract(EG()->query->get_project_vars($e_id));
		
		$_invoices = $_invoice[invoices];
		
		@end($_invoices);         // move the internal pointer to the end of the array
		@$key = key($_invoices);  // fetches the key of the element pointed to by the internal pointer
		//print_r($a);exit;
		$_invoices[$a[id]]=$a;

		$new[invoices]=self::encode_db_array($_invoices);

		$wpdb->update($_in_table, $new, array( 'qe_id' => $e_id ));
		
		self::process_invoice($e_id);
		return true;
	}

	public function delete_invoice($a,$e_id='') {
		global $wpdb;extract(EG()->tables);
		if (empty($e_id)){$e_id=EG()->session->get_id();}
		extract(EG()->query->get_project_vars($e_id));
		
		$_invoices = $_invoice[invoices];
		
		unset($_invoices[$a[id]]);

		$new[invoices]=self::encode_db_array($_invoices);

		$wpdb->update($_in_table, $new, array( 'qe_id' => $e_id ));
		
		self::process_invoice($e_id);
		return true;
	}

	public function process_invoice($e_id) {
		global $wpdb,$user_login,$ao;extract(EG()->tables);
		if (empty($e_id)){return false;}
		extract(EG()->query->get_project_vars($e_id));
		$_total = self::get_project_cost($e_id)[total];
		$_labor = self::get_project_cost($e_id)[labor];
		$_material = self::get_project_cost($e_id)[material];
		$_itemize = self::get_project_cost($e_id)[itemize];
		$_other = self::get_project_cost($e_id)[other];
		
		$other_cost = $_other;
		$markup = str_pad($_project[markup],2,"0",STR_PAD_LEFT);
		$tax = str_pad($_project[tax],2,"0",STR_PAD_LEFT);
		
		$material_cost = $_material+($_material*floatval('0.'.$tax));
		$labor_cost = $_labor+($_labor*floatval('0.'.$markup));
		$sub_total=$_labor+$_material+$_itemize;
		$total_cost = bcadd($sub_total,$other_cost,2);
		
		self::check_invoice($e_id);
		$_invoice[last_accessed]=date("Y-m-d H:i:s");
		$_invoice[total_cost]=$total_cost;
		$_invoice[paid] = 0;
		foreach((array)$_invoice[payments] as $k => $v) {($_invoice[paid]+=$v[amount]);}
		
		$_invoice[balance] = (count($_invoice[payments])==0) ? $_total : bcsub($_total, $_invoice[paid], 2); 
		$_invoice[due]=0;
		foreach((array)$_invoice[invoices] as $k => $v) {if($v[status]!='Paid'){($_invoice[due]+=$v[amount]);}}

		$_invoice[paid] = bcsub($_invoice[paid], 0, 2);
		$_invoice[due] = bcsub($_invoice[due], 0, 2);
		//print_r($_invoice[due]);exit;
		unset($_invoice[payments]);unset($_invoice[invoices]);unset($_invoice[add_cost]);unset($_invoice[created]);
		$id = $_invoice[id];unset($_invoice[id]);
		//print_r($_invoice);exit;
		return $wpdb->update($_in_table, $_invoice, array( 'id' => $id ));
	}

	public function check_invoice($e_id) {
		global $wpdb;extract(EG()->tables);if(empty($e_id)){return false;}
		extract(EG()->query->get_project_vars($e_id));
		if(empty($_invoice)){
			$wpdb->insert($_in_table,array('qe_id' => $e_id));
			$wpdb->update($_in_table, array('terms' => 'Payable upon receipt.', 'invoices' => 'a:0:{}', 'payments' => 'a:0:{}'), array( 'qe_id' => $e_id ));
			return false;
		}
		return true;
	}

	public function update_email_template($a) {
		global $wpdb;extract(EG()->tables);
		if (empty($a)){return false;}
		
		$_set=EG()->query->get_settings();
		
		$_templates=$_set[email_templates];
		//print_r($_templates);exit;
		@end($_templates);         // move the internal pointer to the end of the array
		@$key = key($_templates);  // fetches the key of the element pointed to by the internal pointer
		//print_r($a);exit;
		$_templates[$a[id]]=$a;
		//print_r($_templates);exit;
		$new=self::encode_db_array($_templates);
		//print_r($new);exit;
		$wpdb->query("UPDATE `".$_s_table."` SET `value` = '".$new."' WHERE `name` = 'email_templates'");
		//$wpdb->update($_s_table, $new, array( 'qe_id' => $e_id ));
		
		//self::process_invoice($e_id);
		return true;
	}

	public function new_estimate($c) {
		global $wpdb;extract(EG()->tables);
		if (empty($c)){return false;}
		$_ = $this->default_e;
		$_c = self::get_client($c);unset($_c[created]);
		foreach((array)$_c as $k=>$v){if(!empty($v)){$_[client][$k]=$v;}}
		$_[client][id]=$_c[id];$_[client][name]=trim($_c[first_name].' '.$_c[last_name]);
		$_[client][number]=$_c[number];
		$_[client][site][address1]=$_c[address1];$_[client][site][city]=$_c[city];
		$_[client][site][state]=$_c[state];$_[client][site][zip]=$_c[zip];
		$_[client]=self::encode_db_array($_[client]);
		//print_r($c);exit;
		$wpdb->insert($_e_table,array('client_id' => $_POST[client_id] ));
		$e_id=$wpdb->insert_id;
		$wpdb->update($_e_table, $_, array( 'qe_id' => $e_id ));
		return $wpdb->get_row('SELECT * FROM `'.$_e_table.'` WHERE `qe_id` = '.$e_id, ARRAY_A);
	}

	public function update_email_history($e,$e_id='') {
		global $wpdb;extract(EG()->tables);
		if (empty($e_id)){$e_id=EG()->session->get_id();}
		extract(EG()->query->get_project_vars($e_id));
		$_ = array();
		$_[date]=date("F d, Y");$_[time]=date("h:iA");
		$_[from]=$e[from];$_[to]=$e[to];
		$_[subject]=$e[subject];$_[body]=$e[body];
		$_[status]=$e[status];
		$_emails=$_project[email_history];
		$_emails[]=$_;
		$new[email_history]=self::encode_db_array($_emails);
		//print_r($new);exit;
		$wpdb->update($_e_table, $new, array( 'qe_id' => $e_id ));
		return true;
	}

	public function update_estimate($entry,$e_id='') {
		global $wpdb;extract(EG()->tables);
		if(empty($e_id)){$e_id=EG()->session->get_id();}
		extract(EG()->query->get_project_vars($e_id));
		$wpdb->update($_e_table, $entry, array( 'qe_id' => $e_id ));
		$wpdb->update($_e_table, array('last_accessed'=> date("Y-m-d H:i:s")), array( 'qe_id' => $e_id ));
		return true;
	}


	
	

	/**
	 * search_post_excerpt function.
	 *
	 * @access public
	 * @param string $where (default: '')
	 * @return string (modified where clause)
	 */
	public function search_post_excerpt( $where = '' ) {
		global $wp_the_query;

		// If this is not a WC Query, do not modify the query
		if ( empty( $wp_the_query->query_vars['wc_query'] ) || empty( $wp_the_query->query_vars['s'] ) )
		    return $where;

		$where = preg_replace(
		    "/post_title\s+LIKE\s*(\'\%[^\%]+\%\')/",
		    "post_title LIKE $1) OR (post_excerpt LIKE $1", $where );

		return $where;
	}

	/**
	 * wpseo_metadesc function.
	 * Hooked into wpseo_ hook already, so no need for function_exist
	 *
	 * @access public
	 * @return string
	 */
	public function wpseo_metadesc() {
		return WPSEO_Meta::get_value( 'metadesc', wc_get_page_id('shop') );

	}


	/**
	 * wpseo_metakey function.
	 * Hooked into wpseo_ hook already, so no need for function_exist
	 *
	 * @access public
	 * @return string
	 */
	public function wpseo_metakey() {
		return WPSEO_Meta::get_value( 'metakey', wc_get_page_id('shop') );
	}


	/**
	 * Hook into the_posts to do the main product query if needed - relevanssi compatibility
	 *
	 * @access public
	 * @param array $posts
	 * @param WP_Query|bool $query (default: false)
	 * @return array
	 */
	public function the_posts( $posts, $query = false ) {
		// Abort if there's no query
		if ( ! $query )
			return $posts;

		// Abort if we're not filtering posts
		if ( empty( $this->post__in ) )
			return $posts;

		// Abort if this query has already been done
		if ( ! empty( $query->wc_query ) )
			return $posts;

		// Abort if this isn't a search query
		if ( empty( $query->query_vars["s"] ) )
			return $posts;

		// Abort if we're not on a post type archive/product taxonomy
		if 	( ! $query->is_post_type_archive( 'product' ) && ! $query->is_tax( get_object_taxonomies( 'product' ) ) )
	   		return $posts;

		$filtered_posts   = array();
		$queried_post_ids = array();

		foreach ( $posts as $post ) {
		    if ( in_array( $post->ID, $this->post__in ) ) {
			    $filtered_posts[] = $post;
			    $queried_post_ids[] = $post->ID;
		    }
		}

		$query->posts = $filtered_posts;
		$query->post_count = count( $filtered_posts );

		// Ensure filters are set
		$this->unfiltered_product_ids = $queried_post_ids;
		$this->filtered_product_ids   = $queried_post_ids;

		if ( sizeof( $this->layered_nav_post__in ) > 0 ) {
		    $this->layered_nav_product_ids = array_intersect( $this->unfiltered_product_ids, $this->layered_nav_post__in );
		} else {
		    $this->layered_nav_product_ids = $this->unfiltered_product_ids;
		}

		return $filtered_posts;
	}


	/**
	 * Query the products, applying sorting/ordering etc. This applies to the main wordpress loop
	 *
	 * @access public
	 * @param mixed $q
	 * @return void
	 */
	public function product_query( $q ) {

		// Meta query
		$meta_query = $this->get_meta_query( $q->get( 'meta_query' ) );

		// Ordering
		$ordering   = $this->get_catalog_ordering_args();

		// Get a list of post id's which match the current filters set (in the layered nav and price filter)
		$post__in   = array_unique( apply_filters( 'loop_shop_post_in', array() ) );

		// Ordering query vars
		$q->set( 'orderby', $ordering['orderby'] );
		$q->set( 'order', $ordering['order'] );
		if ( isset( $ordering['meta_key'] ) ) {
			$q->set( 'meta_key', $ordering['meta_key'] );
		}

		// Query vars that affect posts shown
		$q->set( 'meta_query', $meta_query );
		$q->set( 'post__in', $post__in );
		$q->set( 'posts_per_page', $q->get( 'posts_per_page' ) ? $q->get( 'posts_per_page' ) : apply_filters( 'loop_shop_per_page', get_option( 'posts_per_page' ) ) );

		// Set a special variable
		$q->set( 'wc_query', 'product_query' );

		// Store variables
		$this->post__in   = $post__in;
		$this->meta_query = $meta_query;

		do_action( 'estimategadget_product_query', $q, $this );
	}


	/**
	 * Remove the query
	 *
	 * @access public
	 * @return void
	 */
	public function remove_product_query() {
		remove_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
	}

	/**
	 * Remove ordering queries
	 */
	public function remove_ordering_args() {
		remove_filter( 'posts_clauses', array( $this, 'order_by_popularity_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'order_by_rating_post_clauses' ) );
	}

	/**
	 * Remove the posts_where filter
	 *
	 * @access public
	 * @return void
	 */
	public function remove_posts_where() {
		remove_filter( 'posts_where', array( $this, 'search_post_excerpt' ) );
	}


	/**
	 * Get an unpaginated list all product ID's (both filtered and unfiltered). Makes use of transients.
	 *
	 * @access public
	 * @return void
	 */
	public function get_products_in_view() {
		global $wp_the_query;

		// Get main query
		$current_wp_query = $wp_the_query->query;

		// Get WP Query for current page (without 'paged')
		unset( $current_wp_query['paged'] );

		// Generate a transient name based on current query
		$transient_name = 'wc_uf_pid_' . md5( http_build_query( $current_wp_query ) . EG_Cache_Helper::get_transient_version( 'product_query' ) );
		$transient_name = ( is_search() ) ? $transient_name . '_s' : $transient_name;

		if ( false === ( $unfiltered_product_ids = get_transient( $transient_name ) ) ) {

		    // Get all visible posts, regardless of filters
		    $unfiltered_product_ids = get_posts(
				array_merge(
					$current_wp_query,
					array(
						'post_type'              => 'product',
						'numberposts'            => -1,
						'post_status'            => 'publish',
						'meta_query'             => $this->meta_query,
						'fields'                 => 'ids',
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'pagename'               => '',
						'wc_query'               => 'get_products_in_view'
					)
				)
			);

			set_transient( $transient_name, $unfiltered_product_ids, DAY_IN_SECONDS * 30 );
		}

		// Store the variable
		$this->unfiltered_product_ids = $unfiltered_product_ids;

		// Also store filtered posts ids...
		if ( sizeof( $this->post__in ) > 0 ) {
			$this->filtered_product_ids = array_intersect( $this->unfiltered_product_ids, $this->post__in );
		} else {
			$this->filtered_product_ids = $this->unfiltered_product_ids;
		}

		// And filtered post ids which just take layered nav into consideration (to find max price in the price widget)
		if ( sizeof( $this->layered_nav_post__in ) > 0 ) {
			$this->layered_nav_product_ids = array_intersect( $this->unfiltered_product_ids, $this->layered_nav_post__in );
		} else {
			$this->layered_nav_product_ids = $this->unfiltered_product_ids;
		}
	}


	/**
	 * Returns an array of arguments for ordering products based on the selected values
	 *
	 * @access public
	 * @return array
	 */
	public function get_catalog_ordering_args( $orderby = '', $order = '' ) {
		global $wpdb;

		// Get ordering from query string unless defined
		if ( ! $orderby ) {
			$orderby_value = isset( $_GET['orderby'] ) ? wc_clean( $_GET['orderby'] ) : apply_filters( 'estimategadget_default_catalog_orderby', get_option( 'estimategadget_default_catalog_orderby' ) );

			// Get order + orderby args from string
			$orderby_value = explode( '-', $orderby_value );
			$orderby       = esc_attr( $orderby_value[0] );
			$order         = ! empty( $orderby_value[1] ) ? $orderby_value[1] : $order;
		}

		$orderby = strtolower( $orderby );
		$order   = strtoupper( $order );
		$args    = array();

		// default - menu_order
		$args['orderby']  = 'menu_order title';
		$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
		$args['meta_key'] = '';

		switch ( $orderby ) {
			case 'rand' :
				$args['orderby']  = 'rand';
			break;
			case 'date' :
				$args['orderby']  = 'date';
				$args['order']    = $order == 'ASC' ? 'ASC' : 'DESC';
			break;
			case 'price' :
				$args['orderby']  = "meta_value_num {$wpdb->posts}.ID";
				$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
				$args['meta_key'] = '_price';
			break;
			case 'popularity' :
				$args['meta_key'] = 'total_sales';

				// Sorting handled later though a hook
				add_filter( 'posts_clauses', array( $this, 'order_by_popularity_post_clauses' ) );
			break;
			case 'rating' :
				// Sorting handled later though a hook
				add_filter( 'posts_clauses', array( $this, 'order_by_rating_post_clauses' ) );
			break;
			case 'title' :
				$args['orderby']  = 'title';
				$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
			break;
		}

		return apply_filters( 'estimategadget_get_catalog_ordering_args', $args );
	}

	/**
	 * WP Core doens't let us change the sort direction for invidual orderby params - http://core.trac.wordpress.org/ticket/17065
	 *
	 * This lets us sort by meta value desc, and have a second orderby param.
	 *
	 * @access public
	 * @param array $args
	 * @return array
	 */
	public function order_by_popularity_post_clauses( $args ) {
		global $wpdb;

		$args['orderby'] = "$wpdb->postmeta.meta_value+0 DESC, $wpdb->posts.post_date DESC";

		return $args;
	}

	/**
	 * order_by_rating_post_clauses function.
	 *
	 * @access public
	 * @param array $args
	 * @return array
	 */
	public function order_by_rating_post_clauses( $args ) {
		global $wpdb;

		$args['fields'] .= ", AVG( $wpdb->commentmeta.meta_value ) as average_rating ";

		$args['where'] .= " AND ( $wpdb->commentmeta.meta_key = 'rating' OR $wpdb->commentmeta.meta_key IS null ) ";

		$args['join'] .= "
			LEFT OUTER JOIN $wpdb->comments ON($wpdb->posts.ID = $wpdb->comments.comment_post_ID)
			LEFT JOIN $wpdb->commentmeta ON($wpdb->comments.comment_ID = $wpdb->commentmeta.comment_id)
		";

		$args['orderby'] = "average_rating DESC, $wpdb->posts.post_date DESC";

		$args['groupby'] = "$wpdb->posts.ID";

		return $args;
	}

	/**
	 * Appends meta queries to an array.
	 * @access public
	 * @param array $meta_query
	 * @return array
	 */
	public function get_meta_query( $meta_query = array() ) {
		if ( ! is_array( $meta_query ) )
			$meta_query = array();

		$meta_query[] = $this->visibility_meta_query();
		$meta_query[] = $this->stock_status_meta_query();

		return array_filter( $meta_query );
	}

	/**
	 * Returns a meta query to handle product visibility
	 *
	 * @access public
	 * @param string $compare (default: 'IN')
	 * @return array
	 */
	public function visibility_meta_query( $compare = 'IN' ) {
		if ( is_search() )
			$in = array( 'visible', 'search' );
		else
			$in = array( 'visible', 'catalog' );

		$meta_query = array(
		    'key'     => '_visibility',
		    'value'   => $in,
		    'compare' => $compare
		);

		return $meta_query;
	}

	/**
	 * Returns a meta query to handle product stock status
	 *
	 * @access public
	 * @param string $status (default: 'instock')
	 * @return array
	 */
	public function stock_status_meta_query( $status = 'instock' ) {
		$meta_query = array();
		if ( get_option( 'estimategadget_hide_out_of_stock_items' ) == 'yes' ) {
			$meta_query = array(
		        'key' 		=> '_stock_status',
				'value' 	=> $status,
				'compare' 	=> '='
		    );
		}
		return $meta_query;
	}

	/**
	 * Layered Nav Init
	 */
	public function layered_nav_init( ) {

		if ( is_active_widget( false, false, 'estimategadget_layered_nav', true ) && ! is_admin() ) {

			global $_chosen_attributes;

			$_chosen_attributes = array();

			$attribute_taxonomies = wc_get_attribute_taxonomies();
			if ( $attribute_taxonomies ) {
				foreach ( $attribute_taxonomies as $tax ) {

					$attribute       = wc_sanitize_taxonomy_name( $tax->attribute_name );
					$taxonomy        = wc_attribute_taxonomy_name( $attribute );
					$name            = 'filter_' . $attribute;
					$query_type_name = 'query_type_' . $attribute;

			    	if ( ! empty( $_GET[ $name ] ) && taxonomy_exists( $taxonomy ) ) {

			    		$_chosen_attributes[ $taxonomy ]['terms'] = explode( ',', $_GET[ $name ] );

			    		if ( empty( $_GET[ $query_type_name ] ) || ! in_array( strtolower( $_GET[ $query_type_name ] ), array( 'and', 'or' ) ) )
			    			$_chosen_attributes[ $taxonomy ]['query_type'] = apply_filters( 'estimategadget_layered_nav_default_query_type', 'and' );
			    		else
			    			$_chosen_attributes[ $taxonomy ]['query_type'] = strtolower( $_GET[ $query_type_name ] );

					}
				}
		    }

		    add_filter('loop_shop_post_in', array( $this, 'layered_nav_query' ) );
	    }
	}

	/**
	 * Layered Nav post filter
	 *
	 * @param array $filtered_posts
	 * @return array
	 */
	public function layered_nav_query( $filtered_posts ) {
		global $_chosen_attributes;

		if ( sizeof( $_chosen_attributes ) > 0 ) {

			$matched_products   = array(
				'and' => array(),
				'or'  => array()
			);
			$filtered_attribute = array(
				'and' => false,
				'or'  => false
			);

			foreach ( $_chosen_attributes as $attribute => $data ) {
				$matched_products_from_attribute = array();
				$filtered = false;

				if ( sizeof( $data['terms'] ) > 0 ) {
					foreach ( $data['terms'] as $value ) {

						$posts = get_posts(
							array(
								'post_type' 	=> 'product',
								'numberposts' 	=> -1,
								'post_status' 	=> 'publish',
								'fields' 		=> 'ids',
								'no_found_rows' => true,
								'tax_query' => array(
									array(
										'taxonomy' 	=> $attribute,
										'terms' 	=> $value,
										'field' 	=> 'term_id'
									)
								)
							)
						);

						if ( ! is_wp_error( $posts ) ) {

							if ( sizeof( $matched_products_from_attribute ) > 0 || $filtered ) {
								$matched_products_from_attribute = $data['query_type'] == 'or' ? array_merge( $posts, $matched_products_from_attribute ) : array_intersect( $posts, $matched_products_from_attribute );
							} else {
								$matched_products_from_attribute = $posts;
							}

							$filtered = true;
						}
					}
				}

				if ( sizeof( $matched_products[ $data['query_type'] ] ) > 0 || $filtered_attribute[ $data['query_type'] ] === true ) {
					$matched_products[ $data['query_type'] ] = ( $data['query_type'] == 'or' ) ? array_merge( $matched_products_from_attribute, $matched_products[ $data['query_type'] ] ) : array_intersect( $matched_products_from_attribute, $matched_products[ $data['query_type'] ] );
				} else {
					$matched_products[ $data['query_type'] ] = $matched_products_from_attribute;
				}

				$filtered_attribute[ $data['query_type'] ] = true;

				$this->filtered_product_ids_for_taxonomy[ $attribute ] = $matched_products_from_attribute;
			}

			// Combine our AND and OR result sets
			if ( $filtered_attribute['and'] && $filtered_attribute['or'] )
				$results = array_intersect( $matched_products[ 'and' ], $matched_products[ 'or' ] );
			else
				$results = array_merge( $matched_products[ 'and' ], $matched_products[ 'or' ] );

			if ( $filtered ) {

				WC()->query->layered_nav_post__in   = $results;
				WC()->query->layered_nav_post__in[] = 0;

				if ( sizeof( $filtered_posts ) == 0 ) {
					$filtered_posts   = $results;
					$filtered_posts[] = 0;
				} else {
					$filtered_posts   = array_intersect( $filtered_posts, $results );
					$filtered_posts[] = 0;
				}

			}
		}
		return (array) $filtered_posts;
	}

	/**
	 * Price filter Init
	 */
	public function price_filter_init() {
		if ( is_active_widget( false, false, 'estimategadget_price_filter', true ) && ! is_admin() ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'wc-jquery-ui-touchpunch', WC()->plugin_url() . '/assets/js/frontend/jquery-ui-touch-punch' . $suffix . '.js', array( 'jquery-ui-slider' ), EG_VERSION, true );
			wp_register_script( 'wc-price-slider', WC()->plugin_url() . '/assets/js/frontend/price-slider' . $suffix . '.js', array( 'jquery-ui-slider', 'wc-jquery-ui-touchpunch' ), EG_VERSION, true );

			wp_localize_script( 'wc-price-slider', 'estimategadget_price_slider_params', array(
				'currency_symbol' 	=> get_estimategadget_currency_symbol(),
				'currency_pos'      => get_option( 'estimategadget_currency_pos' ),
				'min_price'			=> isset( $_GET['min_price'] ) ? esc_attr( $_GET['min_price'] ) : '',
				'max_price'			=> isset( $_GET['max_price'] ) ? esc_attr( $_GET['max_price'] ) : ''
			) );

			add_filter( 'loop_shop_post_in', array( $this, 'price_filter' ) );
		}
	}

	/**
	 * Price Filter post filter
	 *
	 * @param array $filtered_posts
	 * @return array
	 */
	public function price_filter( $filtered_posts = array() ) {
	    global $wpdb;

	    if ( isset( $_GET['max_price'] ) || isset( $_GET['min_price'] ) ) {

			$matched_products = array();
			$min              = isset( $_GET['min_price'] ) ? floatval( $_GET['min_price'] ) : 0;
			$max              = isset( $_GET['max_price'] ) ? floatval( $_GET['max_price'] ) : 9999999999;

	        $matched_products_query = apply_filters( 'estimategadget_price_filter_results', $wpdb->get_results( $wpdb->prepare( '
	        	SELECT DISTINCT ID, post_parent, post_type FROM %1$s
				INNER JOIN %2$s ON ID = post_id
				WHERE post_type IN ( "product", "product_variation" )
				AND post_status = "publish"
				AND meta_key IN ("' . implode( '","', apply_filters( 'estimategadget_price_filter_meta_keys', array( '_price' ) ) ) . '")
				AND meta_value BETWEEN %3$d AND %4$d
			', $wpdb->posts, $wpdb->postmeta, $min, $max ), OBJECT_K ), $min, $max );

	        if ( $matched_products_query ) {
	            foreach ( $matched_products_query as $product ) {
	                if ( $product->post_type == 'product' ) {
	                    $matched_products[] = $product->ID;
	                }
	                if ( $product->post_parent > 0 && ! in_array( $product->post_parent, $matched_products ) ) {
	                    $matched_products[] = $product->post_parent;
	                }
	            }
	        }

	        // Filter the id's
	        if ( 0 === sizeof( $filtered_posts ) ) {
				$filtered_posts = $matched_products;
	        } else {
				$filtered_posts = array_intersect( $filtered_posts, $matched_products );

	        }
	        $filtered_posts[] = 0;
	    }

	    return (array) $filtered_posts;
	}

}

endif;

return new EG_Query();
