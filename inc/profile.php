<div class="capsule-profile">
	<h3><?php esc_html_e('Capsule', 'capsule-server'); ?></h3>
	<table class="form-table">
		<tr>
			<th></th>
			<td><span class="description">
				<?php
				esc_html_e(
					'To publish to this Capsule server,
					add the following information as a Server in your Capsule client.',
					'capsule-server'
				);
				?>
			</td>
		</tr>
		<tr id="capsule-endpoint">
			<th><label for="cap-endpoint"><?php esc_html_e('Capsule API Endpoint', 'capsule-server'); ?></label></th>
			<td><span id="cap-endpoint"><?php echo esc_html($api_endpoint); ?><span/></td>
		</tr>
		<tr id="capsule-api-key">
			<th><label for="cap-api-key"><?php esc_html_e('Capsule API Key', 'capsule-server'); ?></label></th>
			<td><span id="cap-api-key"><?php echo esc_html($api_key); ?></span></td>
		</tr>
		<tr>
			<th></th>
			<td>
				<a
					href="<?php echo esc_url_raw(wp_nonce_url(admin_url('admin-ajax.php'), 'cap-regenerate-key')); ?>"
					id="cap-regenerate-key"
					class="button"
					data-user-id="<?php echo esc_attr($user_data->ID); ?>"
				>
					<?php esc_html_e('Change Capsule API Key', 'capsule-server'); ?>
				</a>
			</td>
		</tr>
	</table>
</div>
