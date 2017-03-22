<?php

if ( ! class_exists( 'VerifySocial' ) ) :

/**
 * Main VerifySocialClass
 *
 * @class VerifySocial
 * @version	1.0.0
 */
final class VerifySocial {
	
	/**
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * @var string
	 */
	public $app_nicename = 'verifysocial';

	/**
	 * @var string
	 */
	public $app_name = 'VerifySocial';

	/**
	 * @var VerifySocial The single instance of the class
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * @var VS_Session session
	 */
	public $session = null;

	/**
	 * @var VS_Query $query
	 */
	public $query = null;

	public $facebook = null;
	public $twitter = null;
	public $youtube = null;


	/**
	 * Main VerifySocial Instance
	 *
	 * Ensures only one instance of VerifySocial is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
	 * @see VS()
	 * @return VerifySocial - Main instance
	 */
	public static function instance() {
		
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 * @since 2.1
	 */
	public function __clone() {
		
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 * @since 2.1
	 */
	public function __wakeup() {
		
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'payment_gateways', 'shipping', 'mailer', 'checkout' ) ) ) {
			return $this->$key();
		}
	}

	/**
	 * VerifySocial Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
		//echo 'this is crazy';
		//print_r(getenv('FB_APPID'));
		//do_action( 'verifysocial_loaded' );
		// See if there is a user from a cookie
		$user = $this->facebook->getUser();
		// See if there is a user from a cookie
		

		$user_profile;
		$user_likes = false;
		if ($user) {
			try {
				// Proceed knowing you have a logged in user who's authenticated.
				$user_profile = $this->facebook->api('/me');
				//;exit;
				if ($_SESSION[fblike] == 'true') {
					$user_likes = true;
				} else {
					$user_likes = false;
				}
				// print_r($_SESSION[fblike]);
				 print_r($user_likes);exit;
			}
			catch (FacebookApiException $e) {
				// echo '<pre>'.htmlspecialchars(print_r($e, true)).'</pre>';
				$user = null;
			}
			$likes;
			try {
				// @$likes = $facebook->api("/me/likes/181398148572649");
				//$likes = $facebook->api('/me/likes/181398148572649');  
				//print_r($likes);exit;
			}
			catch (FacebookApiException $e) {
				//echo '<pre>'.htmlspecialchars(print_r($e, true)).'</pre>';
			}
			//print_r($likes);
			try {
				//$likes = $facebook->api("/me/likes/181398148572649");
				//print_r($user_profile);
				//if(!empty($user_profile['id'])){
				//    $user_likes=true;
				//setcookie( 'FaceBookAuth', 'Successful', time() + 86400, "/wp/" );
				//
				//        $u = $user_profile;
				
				//print_r($u);exit;
				
				//        verify_wpdb_facebook($u);
				
				
				//}//set_cookie("FaceBookAuth","Successful",15,"/","/");
				
			}
			catch (FacebookApiException $e) {
				$user = null;
				//$_SESSION[dbidwp]=0;
				//print_r($e);
			}
			//print_r($user_likes);exit;
		}
		//version: 'v2.0' // use version 2.2
		print_r($user);
		test_page();
	}
	
	/**
	 * Auto-load in-accessible properties on demand.
	 * @param mixed $key
	 * @return mixed
	 */
	private function get_tables() {
		
		return array(
		'_e_table' => $wpdb->prefix.'qe_estimates',
		'_c_table' => $wpdb->prefix.'qe_clients',
		'_in_table' => $wpdb->prefix.'qe_invoice',
		'_it_table' => $wpdb->prefix.'qe_items',
		'_j_table' => $wpdb->prefix.'qe_job_list',
		'_s_table' => $wpdb->prefix.'qe_settings',
		'_w_table' => $wpdb->prefix.'qe_work_list'

	);
	}

	/**
	 * Hook into actions and filters
	 * @since  2.3
	 */
	private function init_hooks() {
		
		//register_activation_hook( __FILE__, array( 'VS_Install', 'install' ) );
		//add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		//add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		//add_action( 'init', array( $this, 'init' ), 0 );
		$this->init();
		//add_action( 'init', array( 'VS_Shortcodes', 'init' ) );
		//add_action( 'init', array( 'VS_Emails', 'init_transactional_emails' ) );
	}

	/**
	 * Define VS Constants
	 */
	private function define_constants() {
		//$upload_dir;// = wp_upload_dir();

		$this->define( 'VS_PLUGIN_FILE', __DIR__ );
		//$this->define( 'VS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		//$this->define( 'VS_VERSION', $this->version );
		
		//$this->define( 'VS_LOG_DIR', $upload_dir['basedir'] . '/eg-logs/' );
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		//include_once( 'includes/class-autoloader.php' );
		include_once( 'includes/core-functions.php' );
		//include_once( 'includes/eg-widget-functions.php' );
		//include_once( 'includes/eg-webhook-functions.php' );
		//include_once( 'includes/class-install.php' );
		
		include_once( 'includes/session.php' );

		include_once('sdk/facebook/facebook.php');

		//$this->facebook = include('includes/class-query.php' );
		//$this->assets = include( 'assets/class-assets.php' );                  	// The main query class
		//$this->mail = include( 'includes/class-mail.php' );  
		
	}

	
	public function get_pdf_tpl() {
		 return $this->plugin_url().'includes/admin/settings/document_templates/tpl.php';
	}

	/**
	 * Include required ajax files.
	 */
	public function ajax_includes() {
		include_once( 'includes/class-ajax.php' );                           // Ajax functions for admin and the front-end
	}
	/**
	 * Include required ajax files.
	 */
	public function assets_includes() {
		include_once( 'assets/class-assets.php' );                          // Ajax functions for admin and the front-end
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		include_once( 'includes/class-frontend-scripts.php' );               // Frontend Scripts
		//include_once( 'includes/class-eg-form-handler.php' );                   // Form Handlers
		                         // The main cart class
		//include_once( 'includes/class-eg-tax.php' );                            // Tax class
		//include_once( 'includes/class-eg-customer.php' );                       // Customer class
		//include_once( 'includes/class-eg-shortcodes.php' );                     // Shortcodes class
		//include_once( 'includes/class-eg-https.php' );                          // https Helper
	}

	/**
	 * Function used to Init VerifySocial Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		include_once( 'includes/template-functions.php' );
	}

	/**
	 * Init VerifySocial when WordPress Initialises.
	 */
	public function init() {

		$this->before_init();
		// Set up localisation
		//$this->load_plugin_textdomain();

		
		// Load class instances
		//$this->product_factory = new VS_Product_Factory();                      // Product Factory to create new product instances
		//$this->order_factory   = new VS_Order_Factory();                        // Order Factory to create new order instances
		//$this->countries       = new VS_Countries();                            // Countries class
		//$this->integrations    = new VS_Integrations();                         // Integrations class

		
		$this->session 	= new VS_Session();
		$this->facebook = new Facebook(array(
								'appId'  => getenv('FB_APPID'),
								'secret' => getenv('FB_SECRET'),
								));
		//$this->global   = new VS_Global();
		//$this->query  = new VS_Query();
		
		// Init action
		//do_action( 'verifysocial_init' );
	}

  /**
	 * Prevent caching on dynamic pages.
	 *
	 * @access public
	 * @return void
	 */
	public static function before_init() {
		global $wp;
		//if dependent plugin is not active
		if (!class_exists( 'ManagementGadget' ) ) {
			//$wp->deactivate_plugins(plugin_basename(__FILE__));die('ManagementGadget Must Be Instlled and Activated');
		}
		
	}
	
	
	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function icon_url() {
		return plugins_url( '/', __FILE__ ).'assets/images/icon.png';
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return plugins_url( '/', __FILE__ );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) ).'/';
	}

	/**
	 * Get Ajax URL.
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	

	
}

endif;

/**
 * Returns the main instance of VS to prevent the need to use globals.
 *
 * @since  2.1
 * @return VerifySocial
 */
function VS() {
	return VerifySocial::instance();
}

// Global for backwards compatibility.
$GLOBALS['verifysocial'] = VS();