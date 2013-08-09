<?php 
class Capsule_Server_Import_Post {

	// $data includes three keys:
	function __construct($user_id, $post, $tax_data) {
		$this->api_key = $api_key;
		$this->post = $post;
		$this->tax_data = $tax_data;
		$this->local_post_id = 0;

		$this->user_id = $user_id;
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

		remove_filter('content_save_pre', 'wp_filter_post_kses');
		remove_filter('excerpt_save_pre', 'wp_filter_post_kses');
		remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

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
								$term_id = capsule_create_term($term, $taxonomy);
								
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
}

class Capsule_Server_Export_Terms {
	
	function __construct($taxonomies = array()) {
		$this->taxonomies = $taxonomies;
	}

	/**
	 * @return array of formatted taxonomies:
	 *  'taxonomy_1' => array(
	 *		'term-slug' => array(
	 *			'id' => 1,
	 *			'name' => 'Amazing Term',
	 *			'description' => 'This term is amazing AND useful',
	 *		),
	 *		'term-slug-2' ...
	 * 	),
	 * 	'taxonomy_2' ...
	 */ 
	function get_terms() {
		$taxonomy_array = array();

		$terms = get_terms($this->taxonomies, array(
			'hide_empty' => false,
			'orderby' => 'slug',
			'order' => 'ASC',
		));
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

function capsule_server_controller() {
	switch ($_POST['capsule_server_action']) {
		case 'insert_post':
			$response = array(
				'result' => 'error',
			);
			// Cannot use nonce here as they're salted with unique keys, going to have to rely on api key.
			if (isset($_POST['capsule_client_post_data'])) {
				$data = $_POST['capsule_client_post_data'];
				if (isset($data['post']) && isset($data['tax']) && isset($data['api_key'])) {
					if ($user_id = capsule_server_validate_user($data['api_key'])) {
						$capsule_import = new Capsule_Server_Import_Post($user_id, $data['post'], $data['tax']);
						$post_id = $capsule_import->import();
						
						if ($capsule_import->local_post_id != 0) {
							$response = array(
								'result' => 'success',
								'data' => array(
									'permalink' => get_permalink($capsule_import->local_post_id),
								),
							);
						}
					}
					else {
						header('HTTP/1.1 401 Unauthorized');
						die();
					}
				}
			}
			header('Content-type: application/json');
			echo json_encode($response);
			die();
			break;
		case 'get_terms':
			if (isset($_POST['capsule_client_post_data']['api_key']) && capsule_server_validate_user($_POST['capsule_client_post_data']['api_key'])) {
				$taxonomies = isset($_POST['capsule_client_post_data']['taxonomies']) ? $_POST['capsule_client_post_data']['taxonomies'] : array();
				$term_exporter = new Capsule_Server_Export_Terms($taxonomies);
				echo serialize($term_exporter->get_terms());
			}
			else {
				header('HTTP/1.1 401 Unauthorized');
			}
			die();
			break;
		case 'test_credentials':
			if (isset($_POST['capsule_client_post_data']['api_key']) && capsule_server_validate_user($_POST['capsule_client_post_data']['api_key'])) {
				echo 'authorized';
			}
			else {
				header('HTTP/1.1 401 Unauthorized');
			}
			die();
		default:
			break;
	}
}
add_action( 'init', 'capsule_server_controller', 9998 );

