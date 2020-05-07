<?php //phpcs:disable Files.SideEffects.FoundWithSymbols

/**
 * Theme functions file.
 *
 * @package capsule-server
 */

require_once 'ui/functions.php';
require_once 'capsule-server-import-export.php';
require_once 'inc/class-capsule-server.php';

$cap_server = new Capsule_Server();
$cap_server->add_actions();

/**
 * Generate new API key. AJAX handler.
 *
 * @return void
 */
function capsule_server_ajax_new_api()
{
	$nonce   = $_GET['_wpnonce']; //phpcs:ignore
	$user_id = (int) $_POST['user_id'];
	if (
		$user_id && wp_verify_nonce($nonce, 'cap-regenerate-key') &&
		( current_user_can('edit_users') || get_current_user_id() === $user_id )
	) {
		$cap = new Capsule_Server($user_id);
		$key = $cap->generate_api_key();
		$cap->set_api_key($key);
		echo esc_html($key);
	}
	die();
}
add_action('wp_ajax_cap_new_api_key', 'capsule_server_ajax_new_api');

/**
 * Generate the user meta key for the api key value.
 * This generates a different key for each blog if it is a multisite install
 *
 * @return string meta key
 **/
function capsule_server_api_meta_key()
{
	if (is_multisite()) {
		$blog_id      = get_current_blog_id();
		$api_meta_key = ( 1 === $blog_id ) ? '_capsule_api_key' : '_capsule_api_key_' . $blog_id;
	} else {
		$api_meta_key = '_capsule_api_key';
	}

	return $api_meta_key;
}

/**
 * Validates a user's existance in the db against an api key.
 *
 * @param string $api_key The api key to use for the validation.
 * @return integer User ID or 0 if none can be found.
 */
function capsule_server_validate_user($api_key)
{
	global $wpdb;

	$meta_key = capsule_server_api_meta_key();

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			'
			SELECT `user_id`
			FROM ' . $wpdb->usermeta . '
			WHERE `meta_key` = %s
			AND `meta_value` = %s',
			$meta_key,
			$api_key
		)
	);
}

/**
 * Display admin notices.
 *
 * @return void
 */
function capsule_server_admin_notice()
{
	if (empty($_GET['page']) || false !== strpos($_GET['page'], 'capsule')) { //phpcs:ignore
		return;
	}
	?>
<section class="capsule-welcome">
	<h1><?php esc_html_e('Welcome to Capsule Server', 'capsule-server'); ?></h1>
	<p>
		<?php
			// Translators: %s is the admin Capsule page url.
			printf(
				esc_html__(
					'Please read the overview, FAQs and more about <a href="%s">how Capsule Server works</a>.',
					'capsule-server'
				),
				esc_url(admin_url('admin.php?page=capsule'))
			);
		?>
	</p>
</section>
	<?php
}
add_action('admin_notices', 'capsule_server_admin_notice');

/**
 * Add menu entries.
 *
 * @return void
 */
function capsule_server_menu()
{
	global $menu;
	$menu['3'] = array( '', 'read', 'separator-capsule', '', 'wp-menu-separator' );
	add_menu_page(
		__('Capsule Server', 'capsule-server'),
		__('Capsule Server', 'capsule-server'),
		'read',
		'capsule',
		'capsule_server_help',
		'',
		'3.1'
	);
	// Needed to make separator show up.
	ksort($menu);
	add_submenu_page(
		'capsule',
		__('Projects', 'capsule-server'),
		__('Projects', 'capsule-server'),
		'manage_categories',
		'capsule-projects',
		'capsule_server_admin_page_projects'
	);

	add_submenu_page(
		'capsule',
		__('Users', 'capsule-server'),
		__('Users', 'capsule-server'),
		'create_users',
		'capsule-users',
		'capsule_server_admin_page_users'
	);
}
add_action('admin_menu', 'capsule_server_menu');

/**
 * Help page.
 *
 * @return void
 */
function capsule_server_help()
{
	include 'inc/server-help.php';
}

/**
 * Empty placeholder.
 *
 * @return void
 */
function capsule_server_admin_page_projects()
{
}

/**
 * Empty placeholder.
 *
 * @return void
 */
function capsule_server_admin_page_users()
{
}
