<?php

/**
 * Class for mananaging a users result history
 */

if(!class_exists('WP_Athletics_My_Results')) {

	class WP_Athletics_My_Results extends WPA_Base {

		/**
		 * default constructor
		 */
		public function __construct( $db ) {
			parent::__construct( $db );
			add_action( 'wp_ajax_wpa_save_profile_photo', array ( $this, 'save_profile_photo') );
			add_action( 'wp_ajax_wpa_add_edit_priv', array ( $this, 'add_edit_post_priv') );
			add_action( 'wp_ajax_wpa_remove_edit_priv', array ( $this, 'remove_edit_post_priv') );
			add_filter( 'posts_where', array( $this, 'custom_query_attachments' ) );
		}

		/**
		 * [AJAX] Saves new user profile photo in user metadata table
		 */
		public function save_profile_photo() {
			if( $this->is_valid_ajax( $this->nonce ) ) {
				global $current_user;

				update_user_meta( $current_user->ID, 'wp-athletics_profile_photo', $_POST['url'] );

				$result = array('success'=>true);

				// return as json
				wp_send_json($result);
			}
			die();
		}

		/**
		 * [AJAX] Allows temporary edit post privileges to allow the user to edit their profile photo (a custom post type)
		 */
		public function add_edit_post_priv() {
			$subscriber = get_role('subscriber');
			$subscriber->add_cap('edit_posts');
		}

		/**
		 * [AJAX] Remove temporary edit post privileges
		 */
		public function remove_edit_post_priv() {
			$subscriber = get_role('subscriber');
			$subscriber->remove_cap('edit_posts');
		}

		/**
		 * A slight hack, intercepts the query for attachments and filters so users can only see their own profile photos when selecting a new one.
		 */
		public function custom_query_attachments($where) {
			global $current_user;
			if(strpos($where, "wp_posts.post_type = 'attachment'")) {
				$where = ' AND wp_posts.post_author = ' . $current_user->ID . $where;
			}
			return $where;
		}

		/**
		 * Returns number of results recorded for current user
		 */
		public function get_my_results_recorded() {
			global $current_user;
			return $this->wpa_db->get_results_recorded( $current_user->ID );
		}

		/**
		 * Enqueues scripts and styles
		 */
		public function enqueue_scripts_and_styles() {
			// common scripts and styles
			$this->enqueue_common_scripts_and_styles();

			wp_enqueue_script( 'wpa-my-results' );
		}

		/**
		 * Creates a "My Results" page
		 */
		public function create_page() {
			$pages_created = get_option( 'wp-athletics_pages_created', array() );

			$the_page_id = $this->generate_page( $this->get_property('my_results_page_title') );

			if($the_page_id) {
				add_option('wp-athletics_my_results_page_id', $the_page_id, '', 'yes');

				array_push( $pages_created, $the_page_id );
				update_option( 'wp-athletics_pages_created', $pages_created);

				wpa_log('My Results page created!');
			}
		}

		/**
		 * For content filtering, ensures the content is only displayed in the WP loop
		 */
		public function my_results_content_filter( $content ) {
			if( !in_the_loop() ) return $content;
			$this->my_results();
		}

		/**
		 * Generates a 'my results' settings page when the shortcode [wpa-my-results] is used
		 */
		public function my_results() {

			if ( is_user_logged_in() ) {

				global $current_user;
				global $wpa_settings;

				$this->enqueue_scripts_and_styles();

				// create user meta if not yet created
				if(!get_user_meta( $current_user->ID, 'wpa_athlete_name', true) ) {
					add_user_meta( $current_user->ID, 'wp-athletics_gender', '', true );
					add_user_meta( $current_user->ID, 'wp-athletics_dob', '', true );
					add_user_meta( $current_user->ID, 'wp-athletics_hide_dob', '', true );
					add_user_meta( $current_user->ID, 'wp-athletics_fave_event_category', '', true );
				}

				// add image upload capabilities for subscriber role (default role for registered members);
				$subscriber = get_role('subscriber');
				$subscriber->add_cap('upload_files');
				//$subscriber->add_cap('edit_posts');

				$nonce = wp_create_nonce( $this->nonce );

				// profile info
				$user_fave_event_cat = get_user_meta( $current_user->ID, 'wp-athletics_fave_event_category', true );
				$user_display_name = $this->wpa_db->get_user_display_name( $current_user->ID );
			?>
				<script type='text/javascript'>
					jQuery(document).ready(function() {

						// set up ajax and retrieve my results
						WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo WPA_PLUGIN_URL; ?>', '<?php echo $current_user->ID; ?>',  function() {

							jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ '<?php echo strtolower(get_option( 'wp-athletics_language', 'en') ); ?>' ] );

							// common setup function
							WPA.setupCommon();

							// create results table
							WPA.MyResults.createMyResultsTables();

							// setup results dialog
							WPA.setupEditResultDialog(WPA.MyResults.reloadResults);

							// setup filters
							WPA.setupFilters(WPA.userID, WPA.MyResults.myResultsTable, WPA.MyResults.getPersonalBests, WPA.MyResults.doEventNameFilter, {
								event: 6,
								type: 5,
								age: 7,
								period: 2
							});

							// set up tabs
							jQuery('#tabs').tabs({
								activate: function( event, ui ) {
									WPA.MyResults.currentTab = ui.newPanel[0].attributes['wpa-tab-type'].value;
									WPA.MyResults.reloadResults();

									var suffix = WPA.userProfileDialog && WPA.userProfileDialog.dialog("isOpen") ? '-dialog' : '';

									if(ui.newPanel[0].attributes['wpa-tab-type'] && ui.newPanel[0].attributes['wpa-tab-type'].value == 'pb') {
										jQuery('.filter-ignore-for-pb' + suffix).hide();
									}
									else {
										jQuery('.filter-ignore-for-pb' + suffix).show();
									}
									console.log('tab selected ' + WPA.MyResults.currentTab);
								},
								create: function( event, ui ) {
									WPA.MyResults.currentTab = 'results';
									WPA.MyResults.reloadResults();
								}
							});

							// bind blur event to display name select field
							// detect enter key press on the display name field
							jQuery("#myProfileDisplayName").blur(function() {
								WPA.Ajax.saveProfileData({
									displayName: jQuery(this).val()
								}, jQuery(this));
							}).keypress(function(e) {
							    if(e.which == 13) {
								    // blur will cause a save event to trigger
							        jQuery(this).blur();
							    }
							});

							// set 'my profile' dob date picker element
							jQuery('#myProfileDOB').datepicker({
						      showOn: "both",
						      buttonImage: "<?php echo WPA_PLUGIN_URL ?>/resources/images/date_picker.png",
						      buttonImageOnly: true,
						      changeMonth: true,
						      changeYear: true,
						      buttonText: "",
						      maxDate: 0,
						      dateFormat: WPA.Settings['display_date_format'],
						      yearRange: 'c-100:c'
						    }).change(function(event) {
							    if(jQuery(this).val() != '') {
							    	WPA.userDOB = jQuery(this).val();
									jQuery('#myProfileAgeClass').val(WPA.calculateCurrentAthleteAgeCategory(WPA.userDOB).name);
							    	WPA.Ajax.saveProfileData({
										dob: WPA.userDOB
									}, jQuery(event.currentTarget));
							    }
						    }).datepicker('setDate', WPA.userDOB);

						    // set default value of 'hide DOB' checkbox
					    	jQuery('#myProfileHideDOB').attr("checked", WPA.userHideDOB).change(function() {
					    		WPA.userHideDOB = jQuery(this).is(':checked');
					    		WPA.Ajax.saveProfileData({
									hideDob: WPA.userHideDOB
								});
					    	});

						    // set age category
						    if(WPA.userDOB != '') {
								jQuery('#myProfileAgeClass').val(WPA.calculateCurrentAthleteAgeCategory(WPA.userDOB).name);
						    }

							// my fave event
							jQuery("#myProfileFaveEvent").combobox({
								select: function(event, ui) {
									WPA.Ajax.saveProfileData({
										faveEvent: jQuery(this).val()
									}, jQuery(event.currentTarget));
								}
							}).combobox('setValue', '<?php echo $user_fave_event_cat ?>');

							// gender
							jQuery("#myProfileGender").combobox({
								select: function(event, ui) {
									WPA.userGender = jQuery(this).val();

									WPA.Ajax.saveProfileData({
										gender: WPA.userGender
									}, jQuery(event.currentTarget));
								}
							}).combobox('setValue', WPA.userGender);

							// set profile photo
							WPA.MyResults.loadProfilePhoto();

							WPA.customUploader = null;

							// upload photo handler, uses native WP media uploader
							jQuery('#wpaProfilePhoto').click(function(e) {
						        e.preventDefault();

						        // allow edit privileges
					    		jQuery.ajax({
					    			type: "post",
					    			url: WPA.Ajax.url,
					    			data: {
						    			action: 'wpa_add_edit_priv'
					    			}
					    		});

						        // if the uploader object has already been created, reopen the dialog
						        if (WPA.customUploader) {
						        	WPA.customUploader.open();
						            return;
						        }
						        else {
							        // extend the wp.media object
							        WPA.customUploader = wp.media.frames.file_frame = wp.media({
							            title: WPA.getProperty('my_profile_select_profile_image_title'),
							            button: {
							                text: WPA.getProperty('my_profile_select_profile_image')
							            },
							            multiple: false
							        });

							        WPA.customUploader.on('close', function() {
							            // remove edit privileges
							    		jQuery.ajax({
							    			type: "post",
							    			url: WPA.Ajax.url,
							    			data: {
								    			action: 'wpa_remove_edit_priv'
							    			}
							    		});
							        });

							        // when a photo is selected, grab the URL and set it as the text field's value, then save to user metadata
							        WPA.customUploader.on('select', function() {
							            attachment = WPA.customUploader.state().get('selection').first().toJSON();
							            jQuery('#user-image').val(attachment.url);
							            WPA.Ajax.saveProfilePhoto(attachment.url);
							            WPA.customUploader.close();
							        });

							        // open the uploader dialog
							        WPA.customUploader.open();
						        }
							});
						});
					});
				</script>

				<div class="wpa">

					<!-- ATHLETE PROFILE -->
					<div class="wpa-my-profile">

						<!-- ATHLETE PHOTO -->
						<input id="user-image" type="hidden" value="<?php echo get_user_meta( $current_user->ID, 'wp-athletics_profile_photo', true );?>" />
						<div class="wpa-profile-photo wpa-profile-photo-default" title="<?php echo $this->get_property('my_profile_image_upload_text'); ?>" id="wpaProfilePhoto"></div>

						<!-- ATHLETE INFO -->
						<div class="wpa-profile-info">

							<div class="wpa-profile-info-fieldset">

								<!-- DISPLAY NAME -->
								<div class="wpa-profile-field">
									<label><?php echo $this->get_property('my_profile_display_name_label'); ?>:</label>
									<input type="text" id="myProfileDisplayName" size="20" maxlength="30" class="ui-widget ui-widget-content ui-state-default ui-corner-all" value="<?php echo $user_display_name; ?>"/>
								</div>

								<!-- FAVOURITE EVENT -->
								<div class="wpa-profile-field">
									<label><?php echo $this->get_property('my_profile_fave_event'); ?>:</label>
									<select id="myProfileFaveEvent">
										<option value=""><?php echo $this->get_property('my_profile_select_fave_event'); ?></option>
									</select>
								</div>

								<!-- GENDER -->
								<div class="wpa-profile-field">
									<label><?php echo $this->get_property('my_profile_gender'); ?>:</label>
									<select id="myProfileGender">
										<option value="M"><?php echo $this->get_property('gender_M'); ?></option>
										<option value="F"><?php echo $this->get_property('gender_F'); ?></option>
									</select>
								</div>
							</div>

							<div class="wpa-profile-info-fieldset">

								<!-- DATE OF BIRTH -->
								<div class="wpa-profile-field" style="padding-bottom:3px">
									<label><?php echo $this->get_property('my_profile_dob'); ?>:</label>
									<input readonly="readonly" class="ui-widget ui-widget-content ui-state-default ui-corner-all" size="30" type="text" id="myProfileDOB"/>
								</div>

								<!-- HIDE DATE OF BIRTH ON PROFILE -->
								<div class="wpa-profile-field">
									<label><?php echo $this->get_property('my_profile_hide_dob'); ?>:</label>
									<input style="position:relative; top:2px;" type="checkbox" id="myProfileHideDOB"/>
								</div>

								<!--  AGE CLASS -->
								<div class="wpa-profile-field">
									<label><?php echo $this->get_property('my_profile_age_class'); ?>:</label>
									<input type="text" disabled="disabled" id="myProfileAgeClass" size="20" class="ui-widget ui-widget-content ui-state-default ui-corner-all wpa-field-noborder"/>
								</div>
							</div>

							<br style="clear:both;"/>

						</div>

						<div id="wpa-profile-right">
							<!-- EVENT / ATHLETE SEARCH -->
							<div class="wpa-profile-search wpa-ac-search">
								<span class="wpa-search-image"></span>
								<input type="text" class="ui-corner-all ui-widget ui-state-default wpa-search wpa-search-disabled" default-text="<?php echo $this->get_property('wpa_search_text'); ?>" value="" id="wpa-search" class="text ui-widget-content ui-corner-all" />
							</div>
						</div>

						<br style="clear:both;" />
					</div>

					<div class="wpa-menu">

						<!-- FILTERS -->
						<div class="wpa-filters ui-corner-all">
							<div class="filter-ignore-for-pb">
								<select id="filterEvent">
									<option value="all" selected="selected"><?php echo $this->get_property('filter_events_option_all'); ?></option>
								</select>
							</div>

							<select id="filterPeriod">
								<option value="all" selected="selected"><?php echo $this->get_property('filter_period_option_all'); ?></option>
								<option value="this_year"><?php echo $this->get_property('filter_period_option_this_year'); ?></option>
							</select>

							<select id="filterType">
								<option value="all" selected="selected"><?php echo $this->get_property('filter_type_option_all'); ?></option>
							</select>

							<select id="filterAge">
								<option value="all" selected="selected"><?php echo $this->get_property('filter_age_option_all'); ?></option>
							</select>

							<div class="filter-ignore-for-pb">
								<input id="filterEventName" highlight-class="filter-highlight" default-text="<?php echo $this->get_property('filter_event_name_input_text'); ?>" class="ui-corner-all ui-widget ui-widget-content ui-state-default wpa-search wpa-search-disabled"></input>
								<span id="filterEventNameCancel" style="display:none;" title="<?php echo $this->get_property('filter_event_name_cancel_text'); ?>" class="filter-name-remove"></span>
							</div>
						</div>

						<!-- ADD RESULT BUTTON -->
						<div id="wpa-profile-add-result">
							<button><?php echo $this->get_property('my_results_add_result_button') ?></button>
						</div>

						<br style="clear:both"/>
					</div>

					<!-- MY RESULTS TABS -->
					<div id="tabs">
					  <ul>
					    <li><a href="#tabs-my-results"><?php echo $this->get_property('my_results_main_tab') ?></a></li>
					    <li><a href="#tabs-my-personal-bests"><?php echo $this->get_property('my_results_personal_bests_tab') ?></a></li>
					  </ul>
					  <div id="tabs-my-results" wpa-tab-type="results">
						<table cellpadding="0" cellspacing="0" border="0" class="display ui-state-default" style="border-bottom:none" id="my-results-table" width="100%">
							<thead>
								<tr>
									<th></th>
									<th></th>
									<th><?php echo $this->get_property('column_event_date') ?></th>
									<th><?php echo $this->get_property('column_event_name') ?></th>
									<th><?php echo $this->get_property('column_event_location') ?></th>
									<th><?php echo $this->get_property('column_event_type') ?></th>
									<th><?php echo $this->get_property('column_category') ?></th>
									<th><?php echo $this->get_property('column_age_category') ?></th>
									<th><?php echo $this->get_property('column_time') ?></th>
									<th><?php echo $this->get_property('column_pace') ?></th>
									<th><?php echo $this->get_property('column_position') ?></th>
									<th></th>
								</tr>
							</thead>
						</table>
					  </div>
					  <div id="tabs-my-personal-bests" wpa-tab-type="pb">
						<table cellpadding="0" cellspacing="0" border="0" class="display ui-state-default" id="my-personal-bests-table" width="100%">
							<thead>
								<tr>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th><?php echo $this->get_property('column_category') ?></th>
									<th><?php echo $this->get_property('column_time') ?></th>
									<th><?php echo $this->get_property('column_pace') ?></th>
									<th><?php echo $this->get_property('column_event_name') ?></th>
									<th><?php echo $this->get_property('column_event_location') ?></th>
									<th><?php echo $this->get_property('column_event_type') ?></th>
									<th><?php echo $this->get_property('column_age_category') ?></th>
									<th><?php echo $this->get_property('column_event_date') ?></th>
									<th><?php echo $this->get_property('column_club_rank') ?><span class="column-help" title="<?php echo $this->get_property('help_column_rank'); ?>"></span></th>
									<th></th>
								</tr>
							</thead>
						</table>
					  </div>
					</div>

					<!-- ADD/EDIT RESULTS DIALOG -->
					<?php $this->create_edit_result_dialog(); ?>

					<!-- COMMON DIALOGS -->
					<?php $this->create_common_dialogs(); ?>
				</div>

			<?php
			} else {
				echo '<div class="error">' . $this->get_property('my_results_not_logged_in') . '</div>';
			}
		}
	}
}
?>