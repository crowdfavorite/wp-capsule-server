<?php 
class Capsule_Server_Import_Post {

	// $data includes three keys:
	function __construct($api_key, $post, $taxonomies) {
		$this->cap_server = new Capsule_Server();

		$this->api_key = $api_key;
		$this->post = $post;
		$this->taxonomies = $taxonomies;
		$this->local_post_id = 0;

		$this->set_user();
	}

	//@TODO This should be somewhere else so capsule-server can use it
	function set_user() {
		global $wpdb;
		$sql = $wpdb->prepare("
			SELECT `user_id`
			FROM $wpdb->usermeta
			WHERE `meta_key` = %s
			AND `meta_value` = %s", 
			$this->cap_server->api_meta_key,
			$this->api_key
		);

		//@TODO throw error if none found
		$this->user_id = $wpdb->get_var($sql);
		$this->post['post_author'] = $this->user_id;
	}

	function import() {
		$this->import_post();
		$this->import_terms();
	}

	function import_post() {
		$this->post['guid'] = $this->post['guid'].'_user_'.$this->post['post_author'];

		// Update post if it already exists on the server
		$local_id = self::get_post_id_by_guid($this->post['guid']);
		if (!$local_id) {
			unset($this->post['ID']);
		}
		else {
			$this->post['ID'] = $local_id;
		}

		$this->local_post_id = wp_insert_post($this->post);
		//@TODO throw error?
	}

	function import_terms() {
		$tax_input = empty($this->post['tax_input']) ? array() : $this->post['tax_input'];

		foreach ($this->taxonomies as $taxonomy) {
			if (isset($tax_input[$taxonomy])) {
				foreach ($tax_input as $terms) {
					// wp_set_post_terms will use the integers as names if not the correct type
					$terms = (array) $terms;
					$terms = array_map('intval', $terms);
					wp_set_post_terms($this->local_post_id, $terms, $taxonomy);
				}
			}
			else {
				// There were no terms passed, so unset the current terms
				wp_set_post_terms($this->local_post_id, array(), $taxonomy);
			}
		}
	}

	public static function get_post_id_by_guid($guid) {
		global $wpdb;
		$sql = $wpdb->prepare("
			SELECT ID
			FROM $wpdb->posts
			WHERE guid = %s
		", $guid);

		$post_id = $wpdb->get_var($sql);

		if (!empty($post_id)) {
			return $post_id;
		}
		else {
			return false;
		}
	}

	//@TODO remove user from blog, delete their API key
	function cap_server_remove_user() {}
}

class Capsule_Server_Export_Terms {
	// $data includes three keys:
	function __construct($api_key = null) {
		//$this->cap_server = new Capsule_Server();
		//$this->api_key = $api_key;
		//$this->set_user();
	}

	//@TODO This should be somewhere else so capsule-server can use it
	function set_user() {
		global $wpdb;
		$sql = $wpdb->prepare("
			SELECT `user_id`
			FROM $wpdb->usermeta
			WHERE `meta_key` = %s
			AND `meta_value` = %s", 
			$this->cap_server->api_meta_key,
			$this->api_key
		);

		//@TODO throw error if none found
		$this->user_id = $wpdb->get_var($sql);
	}

	/*
	 *'taxonomy_1' => array(
	 *		'term-slug' => array(
	 *			'id' => 1,
	 *			'name' => 'Amazing Term',
	 *			'description' => 'This term is amazing AND useful',
	 *		),
	 *		'term-slug-2' ...
	 * 	),
	 * 	'taxonomy_2' ....
	 */ 
	function get_terms() {
		$args = array(
			'object_type' => array('post'),
		);
		$taxonomies = get_taxonomies($args);
		// No Need to map post formats
		unset($taxonomies['post_format']);
		//error_log(print_r($taxonomies,1));
		$taxonomy_array = array();
		$terms = get_terms($taxonomies, array('hide_empty' => false));

		foreach ($terms as $term) {
			$taxonomy_array[$term->taxonomy][$term->slug] = array(
				'id' => $term->term_id, 
				'name' => $term->name,
				'description' => $term->description,
				'taxonomy' => $term->taxonomy,
			);
		}
		return $taxonomy_array;
	}
}



function capsule_server_request_handler() {
	switch ($_POST['capsule_server_action']) {
		case 'insert_post':
			if (isset($_POST['capsule_client_post_data'])) {
				$data = $_POST['capsule_client_post_data'];
				if (isset($data['post']) && isset($data['tax']) && isset($data['api_key'])) {
					//@TODO validate here?
					$capsule_import = new Capsule_Server_Import_Post($data['api_key'], $data['post'], $data['tax']);
					$post_id = $capsule_import->import();
					//@TODO catch error?
				}
				else {
					//@TODO error
				}
			}
			break;
		case 'get_terms':
			$cap_server = new Capsule_Server_Export_Terms();
			//@TODO Validate here, not in function
			echo serialize($cap_server->get_terms($_POST['capsule_client_post_data']['api_key']));
			die();
			break;		
		default:
			break;
	}
	
}
add_action('wp_loaded', 'capsule_server_request_handler');

