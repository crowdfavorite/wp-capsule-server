<?php
/**
 * Import post class implementation.
 *
 * @package capsule-server
 */

/**
 * Import a WP Post.
 */
class Capsule_Server_Import_Post {

	/**
	 * Constructor.
	 *
	 * @param integer $user_id  User id.
	 * @param array   $post     Post data.
	 * @param array   $tax_data Taxonomy information (taxonomies, tax_input, mapped_taxonomies).
	 */
	public function __construct( $user_id, $post, $tax_data ) {
		$this->post          = $post;
		$this->tax_data      = $tax_data;
		$this->local_post_id = 0;

		$this->user_id             = $user_id;
		$this->post['post_author'] = $this->user_id;
	}

	/**
	 * Import posts and terms.
	 *
	 * @return void
	 */
	public function import() {
		$this->import_post();
		$this->import_terms();
	}

	/**
	 * Import a WP post.
	 *
	 * @throws Exception If cannot insert post.
	 * @return void
	 */
	private function import_post() {
		$this->post['guid'] = $this->post['guid'] . '_user_' . $this->post['post_author'];

		// Update post if it already exists on the server.
		$local_id = self::get_post_id_by_guid( $this->post['guid'] );
		if ( $local_id > 0 ) {
			$this->post['ID'] = $local_id;
		} else {
			unset( $this->post['ID'] );
		}

		remove_filter( 'content_save_pre', 'wp_filter_post_kses' );
		remove_filter( 'excerpt_save_pre', 'wp_filter_post_kses' );
		remove_filter( 'content_filtered_save_pre', 'wp_filter_post_kses' );

		$this->local_post_id = wp_insert_post( $this->post );
		if ( is_wp_error( $this->local_post_id ) ) {
			$this->local_post_id = 0;
			// Translators: %s is the post guid.
			throw new Exception( sprintf( __( 'Unable to import post with guid: %s', 'capsule-server' ), $this->post['guid'] ) );
		}
	}

	/**
	 * Import taxonomy terms.
	 *
	 * @return void
	 */
	private function import_terms() {
		// All the taxonomies sent from the client.
		$taxonomies = $this->tax_data['taxonomies'];
		// The term mappings for each taxonomy.
		$tax_input = $this->tax_data['tax_input'];
		// The taxonomies which had a local mapping.
		$mapped_taxonomies = $this->tax_data['mapped_taxonomies'];

		if ( empty( $taxonomies ) ) {
			return;
		}
		foreach ( $taxonomies as $taxonomy ) {
			if ( isset( $tax_input[ $taxonomy ] ) ) {
				$terms = (array) $tax_input[ $taxonomy ];

				// wp_set_post_terms will use the integers as names if not the correct type.
				if ( in_array( $taxonomy, $mapped_taxonomies, true ) ) {
					// This handles forcing hierarchial taxonomies to be have terms as integers.
					wp_set_post_terms( $this->local_post_id, $terms, $taxonomy );
				} else {
					// Check if hierarchical, need to pass IDs.
					if ( is_taxonomy_hierarchical( $taxonomy ) ) {
						$terms_as_id = array();

						foreach ( $terms as $term ) {
							// Get term, create if not exists.
							$term_id = capsule_create_term( $term, $taxonomy );

							if ( $term_id ) {
								$terms_as_id[] = $term_id;
							}
						}

						wp_set_post_terms( $this->local_post_id, $terms_as_id, $taxonomy );
					} else {
						wp_set_post_terms( $this->local_post_id, $terms, $taxonomy );
					}
				}
			} else {
				// There were no terms passed, so unset the current terms.
				wp_set_post_terms( $this->local_post_id, array(), $taxonomy );
			}
		}
	}

	/**
	 * Get a post id by guid.
	 *
	 * @param string $guid Post guid.
	 * @return integer     Post id, 0 if no post was found.
	 */
	public static function get_post_id_by_guid( $guid ) {
		global $wpdb;

		$post_id = (int) $wpdb->get_var(
			$wpdb->prepare( '
				SELECT ID
				FROM ' . $wpdb->posts . '
				WHERE guid = %s',
				$guid
			)
		);

		return $post_id;
	}

	/**
	 * @TODO remove user from blog, delete their API key.
	 *
	 * @return void
	 */
	function cap_server_remove_user() {

	}
}
