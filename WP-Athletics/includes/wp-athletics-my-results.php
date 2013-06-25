<?php

/**
 * Class for mananaging a users result history
 */

if(!class_exists('WP_Athletics_My_Results')) {

	class WP_Athletics_My_Results extends WPA_Base {

		public $nonce = 'wpathleticsmyresults';

		/**
		 * default constructor
		 */
		public function __construct($db) {
			parent::__construct($db);
			add_action( 'wp_ajax_wpa_event_autocomplete', array ( $this, 'event_autocomplete') );
			add_action( 'wp_ajax_wpa_get_event', array ( $this, 'get_event') );
			add_action( 'wp_ajax_wpa_update_result', array ( $this, 'update_result') );
			add_action( 'wp_ajax_wpa_delete_result', array ( $this, 'delete_result') );
			add_action( 'wp_ajax_wpa_get_result_info', array ( $this, 'get_result_info') );
			add_action( 'wp_ajax_wpa_save_profile_data', array ( $this, 'save_profile_data') );
			add_action( 'wp_ajax_wpa_save_profile_photo', array ( $this, 'save_profile_photo') );
			add_filter( 'posts_where', array( $this, 'custom_query_attachments' ) );
		}

		/**
		 * [AJAX] Retrieves list of events for autocomplete search
		 */
		public function event_autocomplete() {
			global $current_user;

			// perform the query
			$results = $this->wpa_db->get_events( strtolower($_GET['term']) );

			// return as json
			wp_send_json($results);
			die();
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
		 * [AJAX] Saves or updates an event result
		 */
		public function update_result() {
			if( $this->is_valid_ajax( $this->nonce ) ) {
				global $current_user;

				// perform the insert or update
				$result = $this->wpa_db->update_event(
					array(
						'userId' => $current_user->ID,
						'resultId' => $_POST['resultId'],
						'time' => $_POST['time'],
						'eventId' => $_POST['eventId'],
						'eventDate' => $_POST['eventDate'],
						'eventName' => $_POST['eventName'],
						'eventCategory' => $_POST['eventCategory'],
						'position' => $_POST['position'],
						'garminId' => $_POST['garminId'],
						'ageCategory' => $_POST['ageCategory'],
						'eventSubType' => $_POST['eventSubType'],
						'eventLocation' => $_POST['eventLocation']
					)
				);

				// return as json
				wp_send_json($result);
			}
			die();
		}

		/**
		 * [AJAX] Deletes an event result
		 */
		public function delete_result() {
			if( $this->is_valid_ajax( $this->nonce ) ) {
				global $current_user;

				// perform the delete
				$result = $this->wpa_db->delete_event( $_POST['resultId'] );

				// return as json
				wp_send_json($result);
			}
			die();
		}

		/**
		 * [AJAX] Retrieves event info
		 */
		public function get_result_info() {
			if( $this->is_valid_ajax( $this->nonce ) ) {
				global $current_user;

				// perform the delete
				$result = $this->wpa_db->get_result_info( $_POST['resultId'] );

				// return as json
				wp_send_json($result);
			}
			die();
		}

		/**
		 * [AJAX] Retrieves single event based on ID
		 */
		public function get_event() {
			global $current_user;

			// perform the query
			$result = $this->wpa_db->get_event( intval( $_POST['eventId'] ) );

			// return as json
			wp_send_json($result);
			die();
		}

		/**
		 * [AJAX] Saves provided profile data to the user meta data table
		 */
		public function save_profile_data() {
			if( $this->is_valid_ajax( $this->nonce ) ) {
				global $current_user;

				$age_cat = $_POST['ageCategory'];
				$fave_event = $_POST['faveEvent'];
				$display_name = $_POST['displayName'];

				if(isset( $age_cat ) ) {
					wpa_log('updating age category');
					update_user_meta( $current_user->ID, 'wp-athletics_age_category', $age_cat );
				}
				if(isset( $fave_event ) ) {
					wpa_log('updating fave event');
					update_user_meta( $current_user->ID, 'wp-athletics_fave_event_category', $fave_event );
				}
				if(isset( $display_name ) ) {
					wpa_log('updating display name');
					$this->wpa_db->update_user_display_name( $current_user->ID, $display_name );
				}

				$result = array('success'=>true);

				// return as json
				wp_send_json($result);
			}
			die();
		}

		public function custom_query_attachments($where) {
			global $current_user;
			if(strpos($where, "wp_posts.post_type = 'attachment'")) {
				$where = ' AND wp_posts.post_author = ' . $current_user->ID . $where;
			}
			return $where;
		}

		/**
		 * [AJAX] returns number of results recorded for current user
		 */
		public function get_my_results_recorded() {
			global $current_user;
			return $this->wpa_db->get_results_recorded( $current_user->ID );
		}

		/**
		 * Generates a 'my results' settings page when the shortcode [wpa-my-results] is used
		 */
		public function my_results() {
			if ( is_user_logged_in() ) {
				$this->enqueue_scripts_and_styles();

				// add image upload capabilities for subscriber role (default role for registered members);
				$subscriber = get_role('subscriber');
				$subscriber->add_cap('upload_files');
				$subscriber->add_cap('edit_posts');

				// custom script
				wp_register_script( 'wpa-my-results', WPA_PLUGIN_URL . '/resources/scripts/wpa-my-results.js' );
				wp_enqueue_script( 'wpa-my-results' );

				global $current_user;
				$nonce = wp_create_nonce( $this->nonce );

				// profile info
				$user_age_cat = get_user_meta( $current_user->ID, 'wp-athletics_age_category', true );
				$user_fave_event_cat = get_user_meta( $current_user->ID, 'wp-athletics_fave_event_category', true );
				$user_display_name = $this->wpa_db->get_user_display_name( $current_user->ID );
				$user_oldest_result_year = $this->wpa_db->get_oldest_result_year( $current_user->ID );

				// retrieve arrays for select elements
				$age_cats = $this->get_age_categories();
				$sub_types = $this->get_event_sub_type();
				$event_cats = $this->wpa_db->get_event_categories();
			?>
				<script type='text/javascript'>
					jQuery(document).ready(function() {

						// set up ajax and retrieve my results
						WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo $current_user->ID; ?>',  function() {

							WPA.MyResults.createMyResultsTables();

							// common setup function
							WPA.setupCommon();

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

							// change event for time fields to validate real time
							var timeFields = jQuery('input[time-format]');
							jQuery.each(timeFields, function() {
								jQuery(this).keyup(function() {
									var value = jQuery(this).val();
									if(value != '' && !WPA.isValidTime(jQuery(this).attr('time-format'), value)) {
										jQuery(this).val('');
									}
									else {
										jQuery(this).removeClass('ui-state-error');
									}
								}).focus(function() {
									jQuery(this).select();
								});
							});

							// change event for position
							jQuery('#addResultPosition').keyup(function() {
								var value = jQuery('#addResultPosition').val();
								if(value != '' && !jQuery.isNumeric(value)) {
									jQuery('#addResultPosition').val('');
								}
							});

							// create dialog for adding result
							jQuery("#addResultDialog").dialog({
								autoOpen: false,
								height: 480,
								width: 460,
								modal: true,
								buttons: [{
									text: "Submit",
							      	click: function() {
							      		WPA.MyResults.submitResult();
							      	}
							    },{
								    text: 'Cancel',
								    click: function() {
								    	jQuery(this).dialog("close");
								    }
							    }
							  ]
							});

							// set date picker element
							jQuery('#addResultDate').datepicker({
						      showOn: "both",
						      buttonImage: "<?php echo WPA_PLUGIN_URL ?>/resources/images/date_picker.png",
						      buttonImageOnly: true,
						      dateFormat: 'dd M yy',
						      altFormat: 'yy-mm-dd',
						      altField: '#addResultEventDate'
						    }).change(function() {
							    if(jQuery(this).val() != '') {
							    	jQuery(this).removeClass('ui-state-error');
							    }
						    });

							jQuery('#addResultEventLocation').change(function() {
							    if(jQuery(this).val() != '') {
							    	jQuery(this).removeClass('ui-state-error');
							    }
						    });

						   	// autocomplete on the event name for adding results
						   	jQuery("#addResultEventName").autocomplete({
								source: WPA.Ajax.url + '?action=wpa_event_autocomplete',
								minLength: 2,
								select: function( event, ui ) {
									WPA.Ajax.getEventInfo(ui.item.value, WPA.MyResults.loadEventInfoCallback);
								}
						    }).focus(function(){
						        this.select();
						    }).keyup(function() {
							    if(jQuery(this).val() != '') {
							    	jQuery(this).removeClass('ui-state-error');
							    }
						    });

						   	// cancel selected event
						    jQuery('.add-result-cancel-event').click(function() {
						    	WPA.MyResults.toggleAddResultEvent(true);
						    });

							// add result button
							jQuery('#wpa-profile-add-result button').button({
								icons: {
					              primary: 'ui-icon-circle-plus'
					            }
							}).click(function() {
								jQuery("#addResultId").val('');
								jQuery("#addResultDialog").dialog("option", "title", WPA.getProperty('add_result_title'));
								jQuery("#addResultDialog").dialog("open");
								WPA.MyResults.resetAddResultForm();
							});

							// load PB tab
							WPA.MyResults.getPersonalBests();

							// my fave event
							jQuery("#myProfileFaveEvent").combobox({
								select: function(event, ui) {
									WPA.Ajax.saveProfileData({
										faveEvent: jQuery(this).val()
									}, jQuery(event.currentTarget));
								}
							}).combobox('setValue', '<?php echo $user_fave_event_cat ?>');

							// age class
							WPA.currentAgeCategory = '<?php echo $user_age_cat ?>';
							jQuery("#myProfileAgeClass").combobox({
								select: function(event, ui) {
									var value = jQuery(this).val();

									jQuery("#addResultAgeCategory").val(value).combobox('setValue', value);

									WPA.currentAgeCategory = value;

									WPA.Ajax.saveProfileData({
										ageCategory: value
									}, jQuery(event.currentTarget));
								}
							}).combobox('setValue', WPA.currentAgeCategory);

							// add result age cat combo
							jQuery("#addResultAgeCategory").combobox({
								select: function(event, ui) {
									jQuery(this).combobox('removeCls', 'ui-state-error');
								}
							});

							// add result event combo
							jQuery("#addResultEventCategory").combobox({
								select: function(event, ui) {
									jQuery(this).combobox('removeCls', 'ui-state-error');
									WPA.MyResults.triggerAddEventCategoryChange();
								}
							});

							// add result sub type combo
							jQuery("#addResultEventSubType").combobox({
								select: function(event, ui) {
									jQuery(this).combobox('removeCls', 'ui-state-error');
								}
							});

							// filter event combo
							jQuery("#filterEvent").combobox({
								select: function(event, ui) {
									WPA.filterEvent = ui.item.value;
									WPA.MyResults.myResultsTable.fnFilter( ui.item.value, 6 );
								},
								selectClass: 'filter-highlight'
							});

							// filter period combo
							jQuery("#filterPeriod").combobox({
								select: function(event, ui) {
									WPA.filterPeriod = ui.item.value;
									WPA.MyResults.myResultsTable.fnFilter( ui.item.value, 2 );
									WPA.MyResults.getPersonalBests();
								},
								selectClass: 'filter-highlight'
							});

							// filter type combo
							jQuery("#filterType").combobox({
								select: function(event, ui) {
									WPA.filterType = ui.item.value;
									WPA.MyResults.myResultsTable.fnFilter( ui.item.value, 5 );
									WPA.MyResults.getPersonalBests();
								},
								selectClass: 'filter-highlight'
							});

							// filter age combo
							jQuery("#filterAge").combobox({
								select: function(event, ui) {
									WPA.filterAge = ui.item.value;
									WPA.MyResults.myResultsTable.fnFilter( ui.item.value, 7 );
									WPA.MyResults.getPersonalBests();
								},
								selectClass: 'filter-highlight'
							});

							// filter event name
							WPA.setupInputFilter('filterEventName', 'filterEventNameCancel', WPA.MyResults.doEventNameFilter);

							// set profile photo
							WPA.MyResults.loadProfilePhoto();

							WPA.customUploader = null;

							// upload photo handler, uses native WP media uploader
							jQuery('#wpaProfilePhoto').click(function(e) {
						        e.preventDefault();

						        // if the uploader object has already been created, reopen the dialog
						        if (WPA.customUploader) {
						        	WPA.customUploader.open();
						            return;
						        }

						        // extend the wp.media object
						        WPA.customUploader = wp.media.frames.file_frame = wp.media({
						            title: WPA.getProperty('my_profile_select_profile_image_title'),
						            button: {
						                text: WPA.getProperty('my_profile_select_profile_image')
						            },
						            multiple: false
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

							<!-- DISPLAY NAME -->
							<div class="wpa-profile-field">
								<label><?php echo $this->get_property('my_profile_display_name_label'); ?>:</label>
								<input type="text" id="myProfileDisplayName" size="20" maxlength="30" class="ui-widget ui-widget-content ui-state-default ui-corner-all" value="<?php echo $user_display_name; ?>"/>
							</div>

							<!-- AGE CLASS -->
							<div class="wpa-profile-field">
								<label><?php echo $this->get_property('my_profile_age_class'); ?>:</label>
								<select id="myProfileAgeClass">
									<option value=""><?php echo $this->get_property('my_profile_select_age_class'); ?></option>
									<?php
									foreach ( $age_cats as $age_cat ) {
									?>
										<option value="<?php echo $age_cat['id']; ?>"><?php echo $age_cat['description']; ?></option>
									<?php
									}
									?>
								</select>
							</div>

							<!-- FAVOURITE EVENT -->
							<div class="wpa-profile-field">
								<label><?php echo $this->get_property('my_profile_fave_event'); ?>:</label>
								<select id="myProfileFaveEvent">
									<option value=""><?php echo $this->get_property('my_profile_select_fave_event'); ?></option>
									<?php
									foreach ( $event_cats as $event_cat ) {
									?>
										<option value="<?php echo $event_cat['id']; ?>"><?php echo $event_cat['name']; ?></option>
									<?php
									}
									?>
								</select>
							</div>

							<!-- RESULTS RECORDED -->
							<div class="wpa-profile-field">
								<label><?php echo $this->get_property('my_profile_results_recorded'); ?>:</label>
								<span><?php echo $this->get_my_results_recorded(); ?></span>
							</div>
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
									<?php
									foreach ( $event_cats as $event_cat ) {
									?>
										<option value="<?php echo $event_cat['id']; ?>"><?php echo $event_cat['name']; ?></option>
									<?php
									}
									?>
								</select>
							</div>

							<select id="filterPeriod">
								<option value="all" selected="selected"><?php echo $this->get_property('filter_period_option_all'); ?></option>
								<option value="this_month"><?php echo $this->get_property('filter_period_option_this_month'); ?></option>
								<option value="this_year"><?php echo $this->get_property('filter_period_option_this_year'); ?></option>
								<?php
								if($user_oldest_result_year) {
									for($year = date('Y')-1; $year >= $user_oldest_result_year; $year--) {
								?>
										<option value="year:<?php echo $year;?>"><?php echo $year;?></option>
								<?php
									}
								}
								?>
							</select>

							<select id="filterType">
								<option value="all" selected="selected"><?php echo $this->get_property('filter_type_option_all'); ?></option>
								<?php
								foreach ( $sub_types as $sub_type ) {
								?>
									<option value="<?php echo $sub_type['id']; ?>"><?php echo $sub_type['description']; ?></option>
								<?php
								}
								?>
							</select>

							<select id="filterAge">
								<option value="all" selected="selected"><?php echo $this->get_property('filter_age_option_all'); ?></option>
								<?php
								foreach ( $age_cats as $age_cat ) {
								?>
									<option value="<?php echo $age_cat['id']; ?>"><?php echo $age_cat['description']; ?></option>
								<?php
								}
								?>
							</select>

							<div class="filter-ignore-for-pb">
								<input id="filterEventName" highlight-class="filter-highlight" default-text="<?php echo $this->get_property('filter_event_name_input_text'); ?>" class="ui-corner-all ui-widget ui-widget-content ui-state-default wpa-search wpa-search-disabled"></input>
								<span id="filterEventNameCancel" style="display:none;" title="<?php echo $this->get_property('filter_event_name_cancel_text'); ?>" class="filter-event-name-remove"></span>
							</div>
						</div>

						<!-- ADD RESULT BUTTON -->
						<div id="wpa-profile-add-result">
							<button><?php echo $this->get_property('my_results_add_result_button') ?></button>
						</div>

						<br style="clear:both"/>
					</div>

					<!-- MY RESULTS TABS -->
					<div class="wpa-tabs wpa-results-tabs" id="tabs">
					  <ul>
					    <li><a href="#tabs-my-results"><?php echo $this->get_property('my_results_main_tab') ?></a></li>
					    <li><a href="#tabs-my-personal-bests"><?php echo $this->get_property('my_results_personal_bests_tab') ?></a></li>
					  </ul>
					  <div id="tabs-my-results">
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
									<th><?php echo $this->get_property('column_position') ?></th>
									<th><?php echo $this->get_property('column_club_rank') ?><span class="column-help" title="<?php echo $this->get_property('help_column_rank'); ?>"></span></th>
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

					<!-- ADD RESULTS DIALOG -->
					<div style="display:none" id="addResultDialog">
						<form>
							<input type="hidden" id="addResultId" value=""/>
							<input type="hidden" id="addResultEventId" value=""/>
							<input type="hidden" id="addResultEventDate" value=""/>
							<div class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_event_name'); ?>:</label>
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="35" maxlength=100 type="text" id="addResultEventName" />
								<span class="add-result-help" title="<?php echo $this->get_property('help_add_result_event_name'); ?>"></span>
								<span style="display:none;" title="<?php echo $this->get_property('help_add_result_cancel_event'); ?>" class="add-result-cancel-event"></span>
							</div>
							<div class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_event_category'); ?>:</label>
								<select class="add-result-required" id="addResultEventCategory">
									<option value="" selected="selected"><?php echo $this->get_property('add_result_select_event'); ?></option>
									<?php
									foreach ( $event_cats as $event_cat ) {
									?>
										<option time-format="<?php echo $event_cat['time_format']; ?>" value="<?php echo $event_cat['id']; ?>"><?php echo $event_cat['name']; ?></option>
									<?php
									}
									?>
								</select>
							</div>
							<div class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_location'); ?>:</label>
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="25" maxlength=100 type="text" id="addResultEventLocation" />
							</div>
							<div class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_event_sub_type'); ?>:</label>
								<select class="add-result-required" id="addResultEventSubType">
									<option value="" selected="selected"><?php echo $this->get_property('my_profile_select_sub_type'); ?></option>
									<?php
									foreach ( $sub_types as $sub_type ) {
									?>
										<option value="<?php echo $sub_type['id']; ?>"><?php echo $sub_type['description']; ?></option>
									<?php
									}
									?>
								</select>
							</div>
							<div class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_event_date'); ?>:</label>
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="30" type="text" id="addResultDate"/>
							</div>
							<div class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_age_class'); ?>:</label>
								<select class="add-result-required" id="addResultAgeCategory">
									<option value="" <?php echo ('' == $user_age_cat ? 'selected="selected"' : ''); ?>><?php echo $this->get_property('my_profile_select_age_class'); ?></option>
									<?php
									foreach ( $age_cats as $age_cat ) {
									?>
										<option value="<?php echo $age_cat['id']; ?>" <?php echo ($age_cat['id'] == $user_age_cat ? 'selected="selected"' : ''); ?>><?php echo $age_cat['description']; ?></option>
									<?php
									}
									?>
								</select>
							</div>
							<div style="display:none;" time-format="h" class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_event_time_hours'); ?>:</label>
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="h" maxlength="2" size="3" type="text" id="addResultTimeHours" value="0">
							</div>
							<div style="display:none;" time-format="m" class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_event_time_minutes'); ?>:</label>
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="m" maxlength="2" size="3" type="text" id="addResultTimeMinutes" value="0">
							</div>
							<div style="display:none;" time-format="s" class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_event_time_seconds'); ?>:</label>
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="s" maxlength="2" size="3" type="text" id="addResultTimeSeconds" value="0">
							</div>
							<div style="display:none;" time-format="ms" class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_event_time_milliseconds'); ?>:</label>
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="ms" maxlength="3" size="3" type="text" id="addResultTimeMilliSeconds" value="0">
							</div>
							<div class="wpa-add-result-field">
								<label><?php echo $this->get_property('add_result_event_position'); ?>:</label>
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" size="5" type="text" id="addResultPosition" value="">
							</div>
							<div class="wpa-add-result-field">
								<label><?php echo $this->get_property('add_result_garmin_link'); ?>:</label>
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" size="30" type="text" id="addResultGarminId" value="">
								<span class="add-result-help" title="<?php echo $this->get_property('help_add_result_garmin_id'); ?>"></span>
							</div>
						</form>
					</div>

					<div style="display:none" id="result-delete-confirm" title="<?php echo $this->get_property('confirm_result_delete_title'); ?>">
	  					<p class="wpa-alert">
	  						<span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span>
	  						<?php echo $this->get_property('confirm_result_delete'); ?>
	  					</p>
					</div>

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