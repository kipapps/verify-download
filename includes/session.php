<?php
/**
 * Handle data for the current customers session.
 *
 * @class       VS_Session
 * @version     1.0.0
 * @package     
 * @category    Abstract Class
 * @author      
 */
class VS_Session {

	/** @var array $_data  */
	public $_data = array();

	/** @var bool $_dirty When something changes */
	protected $_dirty = false;

	public function __construct() {
		//$this->define_constants();
		//$this->includes();
		$this->start_session();
		//$this->tables = self::get_tables();
		//do_action( 'estimategadget_loaded' );
		//add_action('init', array( $this, 'start_session' ), 1);
	}

	public function start_session() {
		if(!session_id()) {
			session_start();
		}
	}

	/**
	 * __get function.
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * __set function.
	 *
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}

	 /**
	 * __isset function.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->_data[ sanitize_title( $key ) ] );
	}

	/**
	 * __unset function.
	 *
	 * @param mixed $key
	 * @return void
	 */
	public function __unset( $key ) {
		if ( isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
			$this->_dirty = true;
		}
	}

	/**
	 * Get a session variable
	 *
	 * @param string $key
	 * @param  mixed $default used if the session variable isn't set
	 * @return mixed value of session variable
	 */
	public function get( $key, $default = null ) {
		$key = sanitize_key( $key );
		return isset( $this->_data[ $key ] ) ? maybe_unserialize( $this->_data[ $key ] ) : $default;
	}

	/**
	 * Set a session variable
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {
		if ( $value !== $this->get( $key ) ) {
			$this->_data[ sanitize_key( $key ) ] = maybe_serialize( $value );
			$this->_dirty = true;
		}
	}
	/**
	 * get_customer_id function.
	 *
	 * @access public
	 * @return int
	 */
	public function set_id($id) {
		$this->set( 'E_ID', $id );
		$_SESSION[E_ID] = $id;
		//return $this->_customer_id;
	}

	public function get_id() {
		//return $this->get( 'E_ID');
		return $_SESSION[E_ID];
	}

	public function get_session() {
		//$this->set( 'E_ID', $id );
		//$this->_estimate_id = $id;
		return $_SESSION;
	}
	public function clear_session() {
		session_destroy();
		@ob_start();
		@session_start();
		//return $_SESSION;
	}

}
