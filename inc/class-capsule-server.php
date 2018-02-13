<?php
/**
 * Capsule server implementation.
 *
 * @package capsule-server
 */

/**
 * Capsule Server class.
 */
class Capsule_Server {
	/**
	 * API meta key
	 *
	 * @var string $api_meta_key
	 */
	public $api_meta_key;

	/**
	 * API user key.
	 *
	 * @var string $user_api_key
	 */
	public $user_api_key;

	/**
	 * API endpoint
	 *
	 * @var string $api_endpoint
	 */
	protected $api_endpoint;

	/**
	 * Constructor.
	 *
	 * @param integer $user_id User id.
	 */
	public function __construct( $user_id = null ) {
		$this->user_id = ( null === $user_id ) ? get_current_user_id() : $user_id;

		$this->api_endpoint = home_url();

		$this->api_meta_key = capsule_server_api_meta_key();

		$this->user_api_key = get_user_meta( $this->user_id, $this->api_meta_key, true );
	}

	/**
	 * Add action handlers.
	 *
	 * @return void
	 */
	public function add_actions() {
		add_action( 'user_register', array( $this, 'user_register' ) );
		add_action( 'show_user_profile', array( $this, 'user_profile' ) );
		add_action( 'edit_user_profile', array( $this, 'user_profile' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Register user, create API key.
	 *
	 * @param integer $user_id User id.
	 * @return void
	 */
	public function user_register( $user_id ) {
		$cap_server = new Capsule_Server( $user_id );
		// This sets a new api key.
		$cap_server->user_api_key();
	}

	/**
	 * Display Capsule server info in user profile.
	 *
	 * @param object $user_data User data.
	 * @return void
	 */
	public function user_profile( $user_data ) {
		// Add API Key to User's Profile.
		$cap_server = new Capsule_Server( $user_data->ID );
		$api_key    = $cap_server->user_api_key();

		// Just a request handler.
		$api_endpoint = $cap_server->api_endpoint;
		include get_stylesheet_directory() . '/inc/profile.php';
	}

	/**
	 * Enqueue scripts and styles for admin pages.
	 *
	 * @param string $hook Current page hook.
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( 'profile.php' === $hook ) {
			wp_enqueue_script( 'capsule-admin-profile', get_stylesheet_directory_uri() . '/assets/js/admin-profile.js', array( 'jquery' ), '20180213.1245' );
		}
		wp_enqueue_script( 'capsule-admin', get_stylesheet_directory_uri() . '/assets/js/admin.js', array( 'jquery' ), '20180213.1245' );

		wp_enqueue_style( 'capsule-admin', get_stylesheet_directory_uri() . '/assets/css/admin.css', array(), '20180213.1245' );
	}

	/**
	 * Generate API key.
	 *
	 * @return string API key.
	 */
	public function generate_api_key() {
		// Generate unique keys on a per blog basis.
		if ( is_multisite() ) {
			$key = AUTH_KEY . get_current_blog_id();
		} else {
			$key = AUTH_KEY;
		}

		return sha1( $this->user_id . $key . microtime() );
	}

	/**
	 * Set API key.
	 *
	 * @param string $key API key.
	 * @return void
	 */
	public function set_api_key( $key = null ) {
		if ( null === $key ) {
			$key = $this->user_api_key;
		}
		update_user_meta( $this->user_id, $this->api_meta_key, $key );
	}

	/**
	 * Gets an api key for a user, generates a new one and sets it if the user doesn't have a key
	 *
	 * @return string API key.
	 */
	public function user_api_key() {
		if ( empty( $this->user_api_key ) ) {
			$this->user_api_key = $this->generate_api_key();
			$this->set_api_key();
		}

		return $this->user_api_key;
	}
}
