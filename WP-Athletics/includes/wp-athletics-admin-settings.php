<?php

if(!class_exists('WP_Athletics_Admin')) {

	class WP_Athletics_Admin extends WPA_Base {

		public $nonce = 'wpaathleticsadmin';

		/**
		 * default constructor
		 */
		public function __construct($db) {
			parent::__construct($db);
			add_action( 'wp_ajax_wpa_admin_save_settings', array ( $this, 'save_settings') );
		}

		/**
		 * Constructs an admin menu
		 */
		function admin_menu() {
			add_menu_page('WP Athletics Settings', 'WP Athletics', 'manage_options', 'wp-athletics-settings', array( $this, 'wpa_settings' ) );
			add_submenu_page( 'wp-athletics-settings', 'WP Athletics Events Mangager', 'Manage Events', 'manage_options', 'wp-athletics-manage-events', 'my_magic_function');
			add_submenu_page( 'wp-athletics-settings', 'WP Athletics Results Mangager', 'Manage Results', 'manage_options', 'wp-athletics-manage-results', 'my_magic_function');
			add_submenu_page( 'wp-athletics-settings', 'WP Athletics Event Categories', 'Event Categories', 'manage_options', 'wp-athletics-event-categories', 'my_magic_function');
			add_submenu_page( 'wp-athletics-settings', 'WP Athletics Age Categories', 'Age Categories', 'manage_options', 'wp-athletics-age-categories', 'my_magic_function');
		}

		/**
		 * [AJAX] Saves Settings
		 */
		function save_settings() {
			if( isset( $_POST['language'] ) ) {
				update_option('wp-athletics_language', $_POST['language'] );
			}
			if( isset( $_POST['theme'] ) ) {
				update_option('wp-athletics_theme', $_POST['theme'] );
			}

			$result = array('success'=>true);

			// return as json
			wp_send_json($result);
			die();
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
				$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wp-athletics-settings">Settings</a>';
				array_unshift( $links, $settings_link );
			}

			return $links;
		}

		/**
		 * Enqueues scripts and styles
		 */
		public function enqueue_scripts_and_styles() {
			$this->enqueue_common_scripts_and_styles();
		}

		/**
		 * Generates a general admin settings page
		 */
		function wpa_settings() {
			if (!current_user_can('manage_options')) {
				wp_die('You do not have sufficient permissions to access this page.');
			}
			else {
				global $current_user;
				$nonce = wp_create_nonce( $this->nonce );
			?>
				<script type="text/javascript">
					jQuery(document).ready(function() {

						// set up ajax
						WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo $current_user->ID; ?>',  function() {

							// set setting fields
							jQuery('#setting-language').val('<?php echo strtolower(get_option( 'wp-athletics_language', 'en') ); ?>');
							jQuery('#setting-theme').val('<?php echo strtolower(get_option( 'wp-athletics_theme', 'default') ); ?>');


							// save settings button
							jQuery('#wpa-save-settings').button().click(function() {
								jQuery('#wpa-save-settings').button('option', 'label', 'Saving...').button('option', 'disabled', true);
								WPA.Admin.saveSettings(function(result) {
									if(result.success) {
										jQuery('#wpa-save-settings').button('option', 'label', 'Saved!');
										setTimeout("jQuery('#wpa-save-settings').button('option', 'label', 'Save Settings').button('option', 'disabled', false);", 2000);
									}
								});
							});
						}, true);
					});
				</script>
				<div class="wpa-admin"></div>
				<div class="wpa-admin-intro">
					<h2>WP Athletics</h2>
					<p>
					Thanks for downloading WP Athletics, I hope you'll enjoy using this plugin as much as I enjoyed creating it. The purpose of this plugin is to allow your registered club members to <b>log their athletic results</b>,
					analyse their race histroy and <b>track their personal bests</b>. There is also a feature whereby the <b>overall club records</b> (categorised by age) can be viewed. There are powerful <b>filters</b> available throughout the plugin
					allowing users to narrow down the results by period, event category, age class, terrain type and event name. There is also a userful <b>smart search</b> field available allowing users to search for past events
					and other athletes. To ensure your club records are accurate, you can <b>manually add results</b> for unregistered members or the legends of your club who no longer pound the pavements, it wouldn't be fair to exclude them now would it!
					</p>
					<p>
					I wanted to make this using this plugin as simple as possible for you so I've created two shortcodes
					representing the two major features of this plugin. You simply need to create two blank pages and copy the shortcodes below, that's it!
					</p>
					<ul>
					  <li><b>[wpa-my-results]</b> Use this shortcode on the page where logged in users can manage their athletic results and view their personal bests.</li>
					  <li><b>[wpa-records]</b> User this shortcode on the page where overall club personal bests (categorised by age class) can be viewed.</li>
					</ul>

					<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;">
						<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
						<strong>Hint! </strong>Ensure the above pages are set to use a full width page template in order to display the tables correctly</p>
					</div>
					<h3>Event Categories</h3>
					<p>
					By default, there are a number of typical event categories (e.g 100m, 5 mile etc) already set up for you. If you want to add any new categories or
					remove default categories, check out the <a href="<?php echo get_bloginfo('wpurl')?>/wp-admin/admin.php?page=wp-athletics-event-categories">Event Category Settings</a>.
					</p>
					<h3>Age Categories</h3>
					<p>
					As above, there are also a number of typical age class categories (e.g M, F, M35 etc) already set. If you want to add any new age classes or
					remove default age classes, check out the <a href="<?php echo get_bloginfo('wpurl')?>/wp-admin/admin.php?page=wp-athletics-age-categories">Age Category Settings</a>.
					</p>
					<h3>Event/Result Management</h3>
					<p>
					As users add more events and results, you may wish to manage and control this data. For example, two users may enter the same event twice (this should not usually happen) but
					in which case you would need to remove the duplicates by merging the duplicated events. You may also <b>manually add results</b> for unregistered users or historic races of runners
					no longer with the club or no longer run. Manage club results in the <a href="<?php echo get_bloginfo('wpurl')?>/wp-admin/admin.php?page=wp-athletics-manage-results">Result Manager</a>
					and manage events in the <a href="<?php echo get_bloginfo('wpurl')?>/wp-admin/admin.php?page=wp-athletics-manage-events">Event Manager</a>.
					</p>
					<h2>General Settings</h2>
					<div>
						<div class="wpa-admin-setting">
							<label>Language:</label>
							<select id="setting-language">
								<option value="en">English</option>
								<option value="sw">Swedish</option>
								<!--
								<option value="fr">French</option>
								<option value="es">Spanish</option>
								<option value="de">German</option>
								<option value="sw">Swedish</option>
								-->
							</select>
						</div>
						<div class="wpa-admin-setting">
							<label>Theme:</label>
							<select id="setting-theme">
								<option value="default">Gray</option>
								<option value="red">Red</option>
								<option value="blue">Blue</option>
								<option value="yellow">Yellow</option>
							</select>
						</div>
						<div class="wpa-admin-setting">
							<label></label>
							<button id="wpa-save-settings" style="margin-top: 3px; font-size:11px">Save Settings</button>
						</div>
					</div>

				</div>
			<?php
			}
		}
	}
}
?>