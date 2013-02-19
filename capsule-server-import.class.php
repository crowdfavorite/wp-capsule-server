<?php 
class Capsule_Server_Import_Post {

	// $data includes three keys:
	function __construct($api_key, $post, $tax_data) {
		$this->cap_server = new Capsule_Server();

		$this->api_key = $api_key;
		$this->post = $post;
		$this->tax_data = $tax_data;
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
		// All the taxonomies sent from the client
		$taxonomies = $this->tax_data['taxonomies'];
		// The term mappings for each taxonomy
		$tax_input = $this->tax_data['tax_input'];
		// The taxonomies which had a local mapping
		$mapped_taxonomies = $this->tax_data['mapped_taxonomies'];

		if (!empty($taxonomies)) {
			foreach ($taxonomies as $taxonomy) {
				error_log($taxonomy);
				if (isset($tax_input[$taxonomy])) {
					$terms = (array) $tax_input[$taxonomy];

					// wp_set_post_terms will use the integers as names if not the correct type
					if (in_array($taxonomy, $mapped_taxonomies)) {
						// This handles forcing hierarchial taxonomies to be have terms as integers
						
						wp_set_post_terms($this->local_post_id, $terms, $taxonomy);
					}
					else {
						// Check if hierarchical, need to pass IDs
						if(is_taxonomy_hierarchical($taxonomy)) {
							$terms_as_id = array();
							
							foreach ($terms as $term) {
								// Get term, create if not exists
								$term_id = $this->cap_server_create_term($term, $taxonomy);
								
								if ($term_id) {
									$terms_as_id[] = $term_id;
								}
							}
							
							wp_set_post_terms($this->local_post_id, $terms_as_id, $taxonomy);
						}
						else {
							wp_set_post_terms($this->local_post_id, $terms, $taxonomy);
						}
					}
				}
				else {
					error_log($taxonomy);
					// There were no terms passed, so unset the current terms
					wp_set_post_terms($this->local_post_id, array(), $taxonomy);
				}
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

	// Similar functionality of wp_create_term but wp_create_term is in wp-admin includes which are not loaded
	// 
	function cap_server_create_term($tag_name, $taxonomy) {
		if ($term_info = term_exists($tag_name, $taxonomy)) {
			if (is_array($term_info)) {
				return $term_info['term_id'];
			}

			return false;
		}

		$term_info = wp_insert_term($tag_name, $taxonomy);
		if (is_array($term_info)) {
			return $term_info['term_id'];
		}
		return false;
	}
}

class Capsule_Server_Export_Terms {
	// $data includes three keys:
	function __construct($api_key = null, $taxonomies = array()) {
		//$this->cap_server = new Capsule_Server();
		//$this->api_key = $api_key;
		//$this->set_user();
		$this->taxonomies = $taxonomies;
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
		//@TODO validate user
		$taxonomy_array = array();

		$terms = get_terms($this->taxonomies, array('hide_empty' => false));
		if (is_array($terms)) {
			foreach ($terms as $term) {
				$taxonomy_array[$term->taxonomy][$term->slug] = array(
					'id' => $term->term_id, 
					'name' => $term->name,
					'description' => $term->description,
					'taxonomy' => $term->taxonomy,
				);
			}
		}
		return $taxonomy_array;
	}
}


function capsule_server_request_handler() {
	switch ($_POST['capsule_server_action']) {
		case 'insert_post':
			// Cannot use nonce here as they're salted with unique keys, going to have to rely on api key.
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
			$term_exporter = new Capsule_Server_Export_Terms($_POST['capsule_client_post_data']['api_key'], $_POST['capsule_client_post_data']['taxonomies']);
			//@TODO Validate user here, not in function
			echo serialize($term_exporter->get_terms());
			die();
			break;		
		default:
			break;
	}
	
}
add_action('wp_loaded', 'capsule_server_request_handler');

