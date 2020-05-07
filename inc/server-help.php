<div class="wrap capsule-admin">
	<div class="capsule-welcome">
		<h1><?php esc_html_e( 'Capsule Server', 'capsule-server' ); ?></h1>
		<p><?php esc_html_e( 'Turning the developer\'s code journal into a collaboration hub', 'capsule-server' ); ?></p>
	</div>
	<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/hero.jpg" style="width: 100%;" alt="" />

	<div class="capsule-doc-col-left">
		<h3><?php esc_html_e( 'Overview', 'capsule-server' ); ?></h3>
		<p>
			<?php
				printf(
					// Translators: %s is the Capsule url.
					esc_html__( 'Capsule Server is a collaboration hub. Developers do their independent journaling in their %s installs, then can choose to selectively send that content to the Capsule Server. Content is not created on the Capsule Server, it is replicated from developer\'s Capsule installations.', 'capsule-server' ),
					'<a href="https://crowdfavorite.com/capsule/">' . esc_html__( 'Capsule', 'capsule-server' ) . '</a>'
				);
			?>
		</p>
		<p><?php esc_html_e( 'Capsule Server can serve as a shared memory for a developement team - a home for decisions, failed approaches, ideas and notes that might not make it into code comments or other documentation.', 'capsule-server' ); ?></p>

		<h3><?php esc_html_e( 'Developers, Get Your API Key', 'capsule-server' ); ?></h3>
		<p>
			<?php
				printf(
					esc_html( 'Contributors to this Capsule Server can get their API key on their %s.', 'capsule-server' ),
					'<a href="profile.php">' . esc_html__( 'Profile page', 'capsule-server' ) . '</a>'
				);
			?>
		</p>

		<h3><?php esc_html_e( 'Manage Projects', 'capsule-server' ); ?></h3>
		<p>
			<a href="edit-tags.php?taxonomy=projects"><?php esc_html_e( 'Create projects', 'capsule-server' ); ?></a> <?php esc_html_e( 'to determine what content the Capsule Server will accept. Each developer can then opt-in to the projects they like, and any posts in their Capsule for that project will be replicated to the Capsule Server.', 'capsule-server' ); ?>
		</p>

		<h3><?php esc_html_e( 'Manage Users', 'capsule-server' ); ?></h3>
		<p><a href="users.php"><?php esc_html_e( 'Add user accounts', 'capsule-server' ); ?></a> <?php esc_html_e( 'for developers who you want to contribute and/or to have access to contributed content. These developers are given the Capsule Server API URL and an API key on their Profile page. They each enter this information into their Capsule install which allows them to contribute content back to the Capsule Server.', 'capsule-server' ); ?></p>
		<p><?php echo wp_kses_post( __( 'The recommended role for Capsule API users is <code>subscriber</code>. Only give accounts to trusted users (see Security below).', 'capsule-server' ) ); ?></p>

		<h3><?php esc_html_e( 'Security', 'capsule-server' ); ?></h3>
		<p><i><?php esc_html_e( 'Capsule Server is designed to be used with trusted users.', 'capsule-server' ); ?></i></p>
		<p><?php esc_html_e( 'For this reason, and to retain fidelity of post content, posts from Capsule instances are replicated verbatim on the Capsule Server. No KSES filtering or content sanitization is performed (regardless of user role).', 'capsule-server' ); ?></p>
		<p><?php esc_html_e( 'To revoke a developer\'s access, you can delete the user account. If you prefer to disable their account rather than deleting it, you can change their API key, password and email address.', 'capsule-server' ); ?></p>
		<p><?php esc_html_e( 'As Capsule Server is expected to be used with technical teams that may use self-signed SSL certificates, Capsule is configured to automatically accept self-signed certificates when talking to a Capsule Server.', 'capsule-server' ); ?></p>

	</div>
	<div class="capsule-doc-col-right">
		<h3><?php esc_html_e( 'Browse by Projects &amp; Tags', 'capsule-server' ); ?></h3>
		<p><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/projects.jpg" alt="<?php esc_html_e( 'Project List', 'capsule-server' ); ?>" class="capsule-screenshot" /></p>
		<p><?php echo wp_kses_post( __( 'You can quickly access Capsule Server content by project or tag using the <b>@</b> and <b>#</b> menu items', 'capsule-server' ) ); ?></p>

		<h3><?php esc_html_e( 'Search', 'capsule-server' ); ?></h3>
		<p><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/search.jpg" alt="<?php esc_html_e( 'Search', 'capsule-server' ); ?>" class="capsule-screenshot" /></p>
		<p><?php esc_html_e( 'Capsule supports both keyword search and filtering by projects, tags, code languages, developer and date range, whew! When using keyword search you can auto-complete projects, tags, and code languages by using their syntax prefix.', 'capsule-server' ); ?></p>
		<p><?php esc_html_e( 'When filtering, multiple projects/tags/developers/etc. can be selected and are all populated with auto-complete.', 'capsule-server' ); ?></p>
		<h3><?php esc_html_e( 'Using dnsmasq', 'capsule-server' ); ?></h3>
		<p><?php esc_html_e( 'Many local development environments take advantage of dnsmasq to have pretty links for their local projects. However, please be aware that there is a common issue affecting cURL usage on environments with dnsmasq running as a service.', 'capsule-server' ); ?></p>
		<p><?php esc_html_e( 'As WP Capsule uses cURL to sync capsules, you might find that your local instance is not able to properly send information over to your defined WP Capsule Server.', 'capsule-server' ); ?></p>
		<p><?php esc_html_e( 'To check if your local domain properly resolves, use the terminal command dig, followed by your local URL (eg: dig mywebsite.localhost). In the response section of the output you should see an A record pointing to 127.0.0.1.', 'capsule-server' ); ?></p>
	</div>
	<br style="clear: both;">
	<hr>
	<div class="capsule-doc-col-left">
		<h3><?php esc_html_e( 'Credits', 'capsule-server' ); ?></h3>
		<p>
			<?php
				printf(
					// Translators: the %s is the link to Crowd Favorite site.
					esc_html__( 'Capsule was conceived and executed by the brilliant and devastatingly good-looking men and women at %s.', 'capsule-server' ),
					'<a href="http://crowdfavorite.com">' . esc_html__( 'Crowd Favorite', 'capsule-server' ) . '</a>'
				);
			?>
		</p>
		<p><?php esc_html_e( 'Capsule and Capsule Server are released under the GPL v2 license.', 'capsule-server' ); ?></p>
	</div>
	<div class="capsule-doc-col-right">
		<h3>&nbsp;</h3>
		<p><?php esc_html_e( 'In the finest tradition of Open Source, Capsule was built on the shoulders of the following giants:', 'capsule-server' ); ?></p>
		<?php capsule_credits(); ?>
	</div>
</div>
