<?php 

class Capsule_Server {
	
	public $api_meta_key;
	public $user_api_key;

	protected $api_endpoint;

	function __construct($user_id = null) {
		$this->user_id = $user_id === null ? get_current_user_id() : $user_id;
		
		$this->api_endpoint = home_url();

		if (is_multisite()) {
			global $blog_id;
			$this->api_meta_key = $blog_id == 1 ? '_capsule_api_key' : '_capsule_api_key_'.$blog_id;
		}
		else {
			$this->api_meta_key = '_capsule_api_key';
		}

		$this->user_api_key = get_user_meta($this->user_id, $this->api_meta_key, true);
	}

	public function add_actions() {
		add_action('user_register', array(&$this, 'user_register'));
		add_action('show_user_profile', array(&$this, 'user_profile'));
	}

	public function user_register($user_id) {
		$cap_server = new Capsule_Server($user_id);
		// This sets a new api key and returns it
		$cap_server->user_api_key();
	}

	public function user_profile($user_data) {
		// Add API Key to User's Profile
		$cap_server = new Capsule_Server($user_data->ID);		
		$api_key = $cap_server->user_api_key();

		// Just a request handler
		$api_endpoint = $cap_server->api_endpoint;
?>
<h3><?php _e('Capsule Credentials', 'capsule-server'); ?></h3>
<table class="form-table">
	<tr id="capsule-api-key">
		<th><label for="cap-api-key"><?php _e('Capsule API Key', 'capsule-server'); ?></label></th>
		<td><input type="text" name="cap-api-key" id="cap-api-key" size="35" value="<?php echo esc_attr($api_key); ?>" readonly="readonly" />
		</td>
	</tr>
	<tr id="capsule-endpoint">
		<th><label for="cap-endpoint"><?php _e('Capsule API Endpoint', 'capsule-server'); ?></label></th>
		<td><input type="text" name="cap-endpoint" id="cap-endpoint" size="35" value="<?php echo $api_endpoint; ?>" readonly="readonly" />
		</td>
	</tr>
</table>
<?php 
	}

	protected function get_api_key() {
		// Generate unique keys on a per blog basis
		if (is_multisite()) {
			global $blog_id;
			$key = AUTH_KEY.$blog_id;
		}
		else {
			$key = AUTH_KEY;
		}

		return sha1($this->user_id.$key);
	}

	protected function set_api_key() {
		update_user_meta($this->user_id, $this->api_meta_key, $this->user_api_key);
	}

	// Gets an api key for a user, generates a new one and sets it if the user doesn't have a key
	protected function user_api_key() {
		if (empty($this->user_api_key)) {
			$this->user_api_key = $this->get_api_key();
			$this->set_api_key();
		}

		return $this->user_api_key;
	}
}

$cap_server = new Capsule_Server();
$cap_server->add_actions();

