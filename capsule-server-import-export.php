<?php
/**
 * Implement post import and taxonomy terms export.
 */

require_once 'inc/class-capsule-server-import-post.php';
require_once 'inc/class-capsule-server-export-terms.php';

/**
 * Import export controller function.
 *
 * @return void
 */
function capsule_server_controller() {
	if ( empty( $_POST['capsule_server_action'] ) ) {
		return;
	}

	switch ( $_POST['capsule_server_action'] ) {
		case 'insert_post':
			$response = array(
				'result' => 'error',
			);
			// Cannot use nonce here as they're salted with unique keys, going to have to rely on api key.
			if ( isset( $_POST['capsule_client_post_data'] ) ) {
				$data = $_POST['capsule_client_post_data'];
				if ( isset( $data['post'], $data['tax'], $data['api_key'] ) ) {
					$user_id = capsule_server_validate_user( $data['api_key'] );
					if ( $user_id > 0 ) {
						$capsule_import = new Capsule_Server_Import_Post( $user_id, $data['post'], $data['tax'] );
						$post_id        = $capsule_import->import();

						if ( $capsule_import->local_post_id > 0 ) {
							$response = array(
								'result' => 'success',
								'data'   => array(
									'permalink' => get_permalink( $capsule_import->local_post_id ),
								),
							);
						}
					} else {
						header( 'HTTP/1.1 401 Unauthorized' );
						die();
					}
				}
			}
			wp_json_send( $response );
			break;

		case 'get_terms':
			if ( isset( $_POST['capsule_client_post_data']['api_key'] ) && capsule_server_validate_user( $_POST['capsule_client_post_data']['api_key'] ) ) {
				$taxonomies    = isset( $_POST['capsule_client_post_data']['taxonomies'] ) ? $_POST['capsule_client_post_data']['taxonomies'] : array();
				$term_exporter = new Capsule_Server_Export_Terms( $taxonomies );
				echo wp_json_encode( $term_exporter->get_terms() );
			} else {
				header( 'HTTP/1.1 401 Unauthorized' );
			}
			die();
			break;

		case 'test_credentials':
			if ( isset( $_POST['capsule_client_post_data']['api_key'] ) && capsule_server_validate_user( $_POST['capsule_client_post_data']['api_key'] ) ) {
				echo 'authorized';
			} else {
				header( 'HTTP/1.1 401 Unauthorized' );
			}
			die();
			break;

		default:
			break;
	}
}
// Come in after taxonomies are typically registered but before the wp-gatekeeper plugin.
add_action( 'init', 'capsule_server_controller', 11 );

