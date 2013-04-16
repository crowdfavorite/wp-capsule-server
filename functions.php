<?php 

include_once('ui/functions.php');
include_once('capsule-server-import-export.php');

class Capsule_Server {
	
	public $api_meta_key;
	public $user_api_key;

	protected $api_endpoint;

	function __construct($user_id = null) {
		
		$this->user_id = $user_id === null ? get_current_user_id() : $user_id;
		
		$this->api_endpoint = home_url();

		$this->api_meta_key = capsule_server_api_meta_key();

		$this->user_api_key = get_user_meta($this->user_id, $this->api_meta_key, true);
	}

	public function add_actions() {
		add_action('user_register', array(&$this, 'user_register'));
		add_action('show_user_profile', array(&$this, 'user_profile'));
		add_action('edit_user_profile', array(&$this, 'user_profile'));
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
<div class="capsule-profile">
<h3><?php _e('Capsule', 'capsule-server'); ?></h3>
<table class="form-table">
	<tr>
		<th></th>
		<td><span class="description"><?php _e('To publish to this Capsule server, add the following information as a Server in your Capsule client.', 'capsule-server'); ?></td>
	</tr>
	<tr id="capsule-endpoint">
		<th><label for="cap-endpoint"><?php _e('Capsule API Endpoint', 'capsule-server'); ?></label></th>
		<td><span id="cap-endpoint"><?php echo $api_endpoint; ?><span/></td>
	</tr>
	<tr id="capsule-api-key">
		<th><label for="cap-api-key"><?php _e('Capsule API Key', 'capsule-server'); ?></label></th>
		<td><span id="cap-api-key"><?php echo esc_html($api_key); ?></span></td>
	</tr>
	<tr>
		<th></th>
		<td><a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php'), 'cap-regenerate-key'); ?>" id="cap-regenerate-key" class="button" data-user-id="<?php echo esc_attr($user_data->ID); ?>"><?php _e('Change Capsule API Key', 'capsule-server'); ?></a></td>
	</tr>
</table>
</div>
<script type="text/javascript">
(function($) {
	// move into place
	$profile = $('.capsule-profile');
	$profile.prependTo($profile.closest('form'));
	// reset API key
	$('#cap-regenerate-key').on('click', function(e) {
		var id = $(this).data('user-id');
		var url = $(this).attr('href');
		e.preventDefault();
		$.post(
			url, { 
				action: 'cap_new_api_key',
				user_id: id 
			},
			function(data) {
				if (data) {
					$('#cap-api-key').html(data);
				}
			});
	});
})(jQuery);
</script>
<?php 
	}

	 function generate_api_key() {
		// Generate unique keys on a per blog basis
		if (is_multisite()) {
			global $blog_id;
			$key = AUTH_KEY.$blog_id;
		}
		else {
			$key = AUTH_KEY;
		}

		return sha1($this->user_id.$key.microtime());
	}


	 function set_api_key($key = null) {
		if ($key == null) {
			$key = $this->user_api_key;
		}
		update_user_meta($this->user_id, $this->api_meta_key, $key);
	}

	// Gets an api key for a user, generates a new one and sets it if the user doesn't have a key
	 function user_api_key() {
		if (empty($this->user_api_key)) {
			$this->user_api_key = $this->generate_api_key();
			$this->set_api_key();
		}

		return $this->user_api_key;
	}
}
$cap_server = new Capsule_Server();
$cap_server->add_actions();

function capsule_server_ajax_new_api() {
	$nonce = $_GET['_wpnonce'];
	$user_id = $_POST['user_id'];
	if ($user_id && wp_verify_nonce($nonce, 'cap-regenerate-key') && (current_user_can('edit_users') || $user_id == get_current_user_id())) {
		$cap = new Capsule_Server($user_id);
		$key = $cap->generate_api_key();
		$cap->set_api_key($key);
		echo $key;
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
function capsule_server_api_meta_key() {
	if (is_multisite()) {
		global $blog_id;
		$api_meta_key = ($blog_id == 1) ? '_capsule_api_key' : '_capsule_api_key_'.$blog_id;
	}
	else {
		$api_meta_key = '_capsule_api_key';
	}
	
	return $api_meta_key;
}

/**
 * Validates a user's existance in the db against an api key.
 * 
 * @param string $api_key The api key to use for the validation
 * @return int|null user ID or null if none can be found. 
 */
function capsule_server_validate_user($api_key) {
	global $wpdb;

	$meta_key = capsule_server_api_meta_key();
	$sql = $wpdb->prepare("
		SELECT `user_id`
		FROM $wpdb->usermeta
		WHERE `meta_key` = %s
		AND `meta_value` = %s", 
		$meta_key,
		$api_key
	);

	return $wpdb->get_var($sql);
}

function capsule_server_admin_notice(){
	if (strpos($_GET['page'], 'capsule') !== false) {
		return;
	}
?>
<style type="text/css">
.capsule-welcome {
	background: #222;
	color: #fff;
	margin: 30px 10px 10px 0;
	padding: 15px;
}
.capsule-welcome h1 {
	font-weight: normal;
	line-height: 100%;
	margin: 0 0 10px 0;
}
.capsule-welcome p {
	font-weight: normal;
	line-height: 100%;
	margin: 0;
}
.capsule-welcome a,
.capsule-welcome a:visited {
	color: #f8f8f8;
}
</style>
<section class="capsule-welcome">
	<h1><?php _e('Welcome to Capsule Server', 'capsule-server'); ?></h1>
	<p><?php printf(__('Please read the overview, FAQs and more about <a href="%s">how Capsule Server works</a>.', 'capsule-server'), esc_url(admin_url('admin.php?page=capsule'))); ?></p>
</section>
<?php
}
add_action('admin_notices', 'capsule_server_admin_notice');

// Add menu pages
function capsule_server_menu() {
	global $menu;
	$menu['3'] = array( '', 'read', 'separator-capsule', '', 'wp-menu-separator' );
	add_menu_page(__('Capsule', 'capsule-server'), __('Capsule', 'capsule-server'), 'manage_options', 'capsule', 'capsule_server_help', '', '3.1' );
	// needed to make separator show up
	ksort($menu);
 	add_submenu_page('capsule', __('Projects', 'capsule-server'), __('Projects', 'capsule-server'), 'manage_options', 'capsule-projects', 'capsule_server_admin_page_projects');
 	add_submenu_page('capsule', __('Users', 'capsule-server'), __('Users', 'capsule-server'), 'manage_options', 'capsule-users', 'capsule_server_admin_page_users');
}
add_action('admin_menu', 'capsule_server_menu');

function capsule_server_menu_js() {
?>
<script type="text/javascript">
// TODO
jQuery(function($) {
	$('#adminmenu').find('a[href*="admin.php?page=capsule-projects"]')
		.attr('href', 'edit-tags.php?taxonomy=projects')
		.end()
		.find('a[href*="admin.php?page=capsule-users"]')
		.attr('href', 'users.php');
});
</script>
<?php
}
add_action('admin_head', 'capsule_server_menu_js');

function capsule_server_help() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
?>
<style type="text/css">
.capsule-welcome {
	background: #222;
	color: #fff;
	margin-top: 20px;
	width: 100%;
}
.capsule-welcome h1 {
	font-weight: normal;
	line-height: 100%;
	margin: 0;
	padding: 15px 0 10px 15px;
}
.capsule-welcome p {
	font-weight: normal;
	line-height: 100%;
	margin: 0;
	padding: 0 0 15px 15px;
}
.capsule-admin h3 {
	margin-top: 25px;
}
.capsule-admin hr {
	border: 0;
	border-top: 1px solid #999;
	margin: 0 100px 10px;
}
.capsule-screenshot {
	border: 1px solid #ccc;
	padding: 2px;
	width: 90%;
}
.capsule-doc-col-left {
	float: left;
	margin-right: 30px;
	margin-bottom: 30px;
	max-width: 500px;
	width: 45%;
}
.capsule-doc-col-right {
	clear: right;
	float: left;
	margin-bottom: 30px;
	max-width: 500px;
	width: 45%;
}
</style>
<div class="wrap capsule-admin">
	<div class="capsule-welcome">
		<h1><?php _e('Capsule Server', 'capsule-server'); ?></h1>
		<p><?php _e('Turning the developer\'s code journal into a collaboration hub', 'capsule-server'); ?></p>
	</div>

	<div class="capsule-doc-col-left">
		<h3><?php _e('Overview', 'capsule-server'); ?></h3>
		<p><?php _e('Capsule Server is a collaboration hub. Developers do their independent journaling in their <a href="http://crowdfavorite.com/capsule/">Capsule</a> installs, then can choose to selectively send that content to the Capsule Server. Content is not created on the Capsule Server, it is replicated from developer\'s Capsule installations.', 'capsule-server'); ?></p>
		<p><?php _e('Capsule Server can serve as a shared memory for a developement team - a home for decisions, failed approaches, ideas and notes that might not make it into code comments or other documentation.', 'capsule-server'); ?></p>

		<h3><?php _e('Developers, Get Your API Key', 'capsule-server'); ?></h3>
		<p><?php _e('Contributors to this Capsule Server can get their API key on their <a href="profile.php">Profile page</a>.', 'capsule-server'); ?></p>


		<h3><?php _e('Manage Projects', 'capsule-server'); ?></h3>
		<p><?php _e('<a href="edit-tags.php?taxonomy=projects">Create projects</a> to determine what content the Capsule Server will accept. Each developer can then opt-in to the projects they like, and any posts in their Capsule for that project will be replicated to the Capsule Server.', 'capsule-server'); ?></p>

		<h3><?php _e('Manage Users', 'capsule-server'); ?></h3>
		<p><?php _e('<a href="users.php">Add user accounts</a> for developers who you want to contribute and/or to have access to contributed content. These developers are given the Capsule Server API URL and an API key on their Profile page. They each enter this information into their Capsule install which allows them to contribute content back to the Capsule Server.', 'capsule-server'); ?></p>
		<p><?php _e('The recommended role for Capsule API users is <code>subscriber</code>. Only give accounts to trusted users (see Security below).', 'capsule-server'); ?></p>

		<h3><?php _e('Security', 'capsule-server'); ?></h3>
		<p><i><?php _e('Capsule Server is designed to be used with trusted users.', 'capsule-server'); ?></i></p>
		<p><?php _e('For this reason, and to retain fidelity of post content, posts from Capsule instances are replicated verbatim on the Capsule Server. No KSES filtering or content sanitization is performed (regardless of user role).', 'capsule-server'); ?></p>
		<p><?php _e('To revoke a developer\'s access, you can delete the user account. If you prefer to disable their account rather than deleting it, you can change their API key, password and email address.', 'capsule-server'); ?></p>

	</div>
	<div class="capsule-doc-col-right">
		<h3><?php _e('Browse by Projects &amp; Tags', 'capsule-server'); ?></h3>
		<p><img src="<?php echo get_template_directory_uri(); ?>/docs/projects.jpg" alt="<?php _e('Project List', 'capsule'); ?>" class="capsule-screenshot" /></p>
		<p><?php _e('You can quickly access Capsule Server content by project or tag using the <b>@</b> and <b>#</b> menu items', 'capsule-server'); ?></p>

		<h3><?php _e('Search', 'capsule-server'); ?></h3>
		<p><img src="<?php echo get_template_directory_uri(); ?>/docs/search.jpg" alt="<?php _e('Search', 'capsule-server'); ?>" class="capsule-screenshot" /></p>
		<p><?php _e('Capsule supports both keyword search and filtering by projects, tags, code languages, developer and date range, whew! When using keyword search you can auto-complete projects, tags, and code languages by using their syntax prefix.', 'capsule-server'); ?></p>
		<p><?php _e('When filtering, multiple projects/tags/developers/etc. can be selected and are all populated with auto-complete.', 'capsule-server'); ?></p>

	</div>
	<br style="clear: both;">
	<hr>
	<div class="capsule-doc-col-left">
		<h3><?php _e('Credits', 'capsule'); ?></h3>
		<p><?php _e('Capsule was conceived and executed by the brilliant and devastatingly good-looking men and women at <a href="http://crowdfavorite.com">Crowd Favorite</a>.', 'capsule'); ?></p>
		<p><?php _e('Capsule and Capsule Server are released under the GPL v2 license.', 'capsule'); ?></p>
	</div>
	<div class="capsule-doc-col-right">
		<h3>&nbsp;</h3>
		<p><?php _e('In the finest tradition of Open Source, Capsule was built on the shoulders of the following giants:', 'capsule'); ?></p>
		<?php capsule_credits(); ?>
	</div>
</div>
<?php
}
function capsule_server_admin_page_projects() {}
function capsule_server_admin_page_users() {}
