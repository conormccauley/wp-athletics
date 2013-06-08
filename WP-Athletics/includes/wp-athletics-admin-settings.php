<?php

if(!class_exists('WP_Athletics_Admin')) {

	class WP_Athletics_Admin extends WPA_Base {

		/**
		 * Constructs an admin menu
		 */
		function admin_menu() {
			$page_title = 'WP Athletics Settings';
			$menu_title = 'WP Athletics';
			$capability = 'manage_options';
			$menu_slug = 'wp-athletics-admin-settings';
			$function = array ( $this, 'admin_settings' );
			add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function );
		}

		/**
		 * Adds a "settings" link to the actions links on the plugin page
		 */
		function action_links($links, $file) {
			if ($file == WPA_PLUGIN_BASENAME) {
				wpa_log('Adding a "settings" link to the plugin actions');
				// The "page" query string value must be equal to the slug
				// of the Settings admin page we defined earlier, which in
				// this case equals "myplugin-settings".
				$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wp-athletics-admin-settings">Settings</a>';
				array_unshift( $links, $settings_link );
			}

			return $links;
		}

		/**
		 * Generates an admin settings page
		 */
		function admin_settings() {
			if (!current_user_can('manage_options')) {
				wp_die('You do not have sufficient permissions to access this page.');
			}
			else {
				$this->enqueue_scripts_and_styles();
			?>
				<div>my admin page</div>
			<?php
			}
		}
	}
}
?>