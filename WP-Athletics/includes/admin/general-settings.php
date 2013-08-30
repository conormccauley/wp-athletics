<?php
if ( $this->has_permission_to_manage() ) {
	global $current_user;
	$nonce = wp_create_nonce( $this->nonce );
?>
	<script type="text/javascript">
		jQuery(document).ready(function() {

			// set up ajax
			WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo WPA_PLUGIN_URL; ?>', '<?php echo $current_user->ID; ?>',  function() {

				// set setting fields
				jQuery('#setting-language').val('<?php echo strtolower(get_option( 'wp-athletics_language', 'en') ); ?>');
				jQuery('#setting-theme').val('<?php echo strtolower(get_option( 'wp-athletics_theme', 'default') ); ?>');
				jQuery('#setting-records-mode').val('<?php echo strtolower(get_option( 'wp-athletics_records_mode', 'separate') ); ?>');

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

				// tooltips
				jQuery(document).tooltip({
					track: true
				});

			}, true);


		});
	</script>
	<div class="wpa-admin"></div>
	<div class="wpa-admin-intro">
		<h2>WP Athletics Settings</h2>
		<div id="wpa-admin-settings-container">
			<div class="wpa-admin-setting">
				<label>Club Name:</label>
				<input type="text" size="15" id="club-name" value="<?php echo get_option( 'wp-athletics_club_name', 'Your club name' ); ?>">
			</div>
			<div class="wpa-admin-setting">
				<label>Records Mode:</label>
				<select id="setting-records-mode">
					<option value="separate">Separate</option>
					<option value="combined">Combined</option>
				</select>
				<span class="wpa-help" title="Choose 'Separate' if you wish to use two separate pages for club records (male and female) or choose 'Combined' if you would rather a single page with a dropdown menu allowing users to filter by gender"></span>
			</div>
			<div class="wpa-admin-setting">
				<label>Language:</label>
				<select id="setting-language">
					<option value="en">English</option>
					<!--
					<option value="sw">Swedish</option>
					<option value="fr">French</option>
					<option value="es">Spanish</option>
					<option value="de">German</option>
					<option value="sw">Swedish</option>
					-->
				</select>
			</div>
			<div style="float:left;" class="wpa-admin-setting">
				<label>Theme:</label>
				<select id="setting-theme">
					<option value="default">Gray</option>
					<option value="red">Red</option>
					<option value="blue">Blue</option>
					<option value="yellow">Yellow</option>
				</select>
			</div>
			<div style="float:right;text-align:right" class="wpa-admin-setting">
				<label></label>
				<button id="wpa-save-settings" style="margin-top: 3px; font-size:11px">Save Settings</button>
			</div>
			<br style="clear:both"/>
		</div>
		<h2>About</h2>
		<p>
		Thanks for downloading WP Athletics, a complete solution for tracking results, personal records and generating rankings for your athletic club.
		Please rate if you find this plugin useful and visit <a href="http://www.conormccauley.me/wp-athletics">http://www.conormccauley.me/wp-athletics</a>
		for more information and FAQ.
		</p>
		<div>
			<b>User features:</b>
			<ul>
				<li>Log and manage their athletic results using a friendly and intuitive interface</li>
				<li>Analyse their race history and track their personal bests using powerful filters</li>
				<li>View past event results and other athlete profiles</li>
				<li>See how they rank against fellow members in the club rankings</li>
				<li>View the club records categorised by age category and filter using a variety of parameters</li>
				<li>Use the smart search tool to find other athletes and past events
			</ul>
			<b>Administrator features:</b>
			<ul>
				<li>Easily embed an interactive table of results for an event into a new post</li>
				<li>Manually add results for unregistered members or past/deceased athletes</li>
				<li>Manage the event categories, age categories, events and results</li>
				<li>Print a custom rankings list for pinning up in your locker rooms (e.g 5k male rankings for 2013)</li>
			</ul>
		</div>
		<p>
		I wanted to make this using this plugin as simple as possible for you, that's why the two feature pages have already been created, the "Manage Results" and "Club Records" pages, meaning the plugin is ready to use straight away
		 (Note: you should modify the template for these pages to use a full-width page template). The club records by default are split into two pages, one representing the male and the other representing the female records. You may modify the setting below so both genders are displayed in one page with a gender filter.
		</p>
		<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;">
			<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
			<strong>Important! </strong>Please ensure that all of the WP Athletics pages are modified to use a full width page template in order to display the data correctly</p>
		</div>
		<h3>Embedding results in a post</h3>
		<p>
		There is a useful shortcode available in the plugin allowing you to embed an interactive table of results into a a news post, this shortcode is used as follows:
		</p>
		<b>[wpa-event id=xxx]</b> Where "xxx" is the event ID. See the <a href="<?php echo get_bloginfo('wpurl')?>/wp-admin/admin.php?page=wp-athletics-manage-events">event manager</a> to retrieve the ID for a specific event.
		<h3>Event Categories</h3>
		<p>
		There are a number of typical athletic event categories (e.g 100m, 5 mile etc) already set up for you. If you wish to add any new event categories or
		remove default categories, check out the <a href="<?php echo get_bloginfo('wpurl')?>/wp-admin/admin.php?page=wp-athletics-event-categories">event category settings</a>.
		</p>
		<h3>Age Categories</h3>
		<p>
		As with the event categories, there are also a number of typical age class categories already set up (e.g Junior, Senior, 35-39, etc). If you wish to add any new age classes or
		remove default age classes, check out the <a href="<?php echo get_bloginfo('wpurl')?>/wp-admin/admin.php?page=wp-athletics-age-categories">age category settings</a>.
		</p>
		<h3>Event/Result Management</h3>
		<p>
		As users add more and more events and results, you may wish to manage and control this data. For example, two users may enter the same event twice (this should not usually happen) but
		in which case you would need to remove the duplicates by merging the duplicated events. You may also <a href="<?php echo get_bloginfo('wpurl')?>/wp-admin/admin.php?page=wp-athletics-add-results">manually add results</a> for unregistered users or historic races of runners
		no longer with the club or no longer run. Manage club results using the <a href="<?php echo get_bloginfo('wpurl')?>/wp-admin/admin.php?page=wp-athletics-manage-results">result manager</a>
		and manage events using the <a href="<?php echo get_bloginfo('wpurl')?>/wp-admin/admin.php?page=wp-athletics-manage-events">event manager</a>.
		</p>
	</div>
<?php
}
?>