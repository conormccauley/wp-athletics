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

				$_POST['userId'] = $current_user->ID;

				// perform the insert or update
				$result = $this->wpa_db->update_event($_POST);

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

				$gender = $_POST['gender'];
				$dob = $_POST['dob'];
				$fave_event = $_POST['faveEvent'];
				$display_name = $_POST['displayName'];

				if(isset( $gender ) ) {
					wpa_log('updating gender');
					update_user_meta( $current_user->ID, 'wp-athletics_gender', $gender );
				}
				if(isset( $dob ) ) {
					wpa_log('updating dob');
					update_user_meta( $current_user->ID, 'wp-athletics_dob', $dob );
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
		 * Called when the my results page is requested
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
					add_user_meta( $current_user->ID, 'wp-athletics_fave_event_category', '', true );
				}

				// add image upload capabilities for subscriber role (default role for registered members);
				$subscriber = get_role('subscriber');
				$subscriber->add_cap('upload_files');
				$subscriber->add_cap('edit_posts');

				$nonce = wp_create_nonce( $this->nonce );

				// profile info
				$user_gender = get_user_meta( $current_user->ID, 'wp-athletics_gender', true );
				$user_dob = get_user_meta( $current_user->ID, 'wp-athletics_dob', true );
				$user_fave_event_cat = get_user_meta( $current_user->ID, 'wp-athletics_fave_event_category', true );
				$user_display_name = $this->wpa_db->get_user_display_name( $current_user->ID );
			?>
				<script type='text/javascript'>
					jQuery(document).ready(function() {

						// set up ajax and retrieve my results
						WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo $current_user->ID; ?>',  function() {

							WPA.MyResults.createMyResultsTables();

							WPA.userDOB = '<?php echo $user_dob; ?>';
							WPA.userGender = '<?php echo $user_gender; ?>';
							WPA.userID = '<?php echo $current_user->ID; ?>';

							jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ '<?php echo strtolower(get_option( 'wp-athletics_language', 'en') ); ?>' ] );

							// common setup function
							WPA.setupCommon();

							// setup filters
							WPA.setupFilters(WPA.userID, WPA.MyResults.myResultsTable, WPA.MyResults.getPersonalBests, WPA.MyResults.doEventNameFilter, {
								event: 6,
								type: 5,
								age: 7,
								period: 2
							});

							// create event category selection lists
							jQuery(WPA.globals.eventCategories).each(function(index, item) {
								jQuery("#addResultEventCategory, #myProfileFaveEvent").append('<option time-format="' + item.time_format + '" value="' + item.id + '">' + item.name + '</option>');
							});

							// create event sub type selection list
							jQuery.each(WPA.globals.eventTypes, function(id, name) {
								jQuery("#addResultEventSubType").append('<option value="' + id + '">' + name + '</option>');
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
									text: WPA.getProperty('submit'),
							      	click: function() {
							      		WPA.MyResults.submitResult();
							      	}
							    },{
								    text: WPA.getProperty('cancel'),
								    click: function() {
								    	jQuery(this).dialog("close");
								    }
							    }
							  ]
							});

							// set 'add result' date picker element
							jQuery('#addResultDate').datepicker({
						      showOn: "both",
						      buttonImage: "<?php echo WPA_PLUGIN_URL ?>/resources/images/date_picker.png",
						      buttonImageOnly: true,
						      changeMonth: true,
						      changeYear: true,
						      maxDate: 0,
						      dateFormat: WPA.Settings['display_date_format'],
						      yearRange: 'c-100:c',
						      altFormat: 'yy-mm-dd',
						      altField: '#addResultEventDate'
						    }).change(function() {
							    if(jQuery(this).val() != '' && WPA.userDOB != '') {
							    	jQuery(this).removeClass('ui-state-error');
									WPA.MyResults.setAddResultAgeCategory();
							    }
						    });

							// set 'my profile' dob date picker element
							jQuery('#myProfileDOB').datepicker({
						      showOn: "both",
						      buttonImage: "<?php echo WPA_PLUGIN_URL ?>/resources/images/date_picker.png",
						      buttonImageOnly: true,
						      changeMonth: true,
						      changeYear: true,
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

						    // set age category
						    if(WPA.userDOB != '') {
								jQuery('#myProfileAgeClass').val(WPA.calculateCurrentAthleteAgeCategory(WPA.userDOB).name);
						    }

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
								if(WPA.userGender != '' && WPA.userDOB != '') {
									jQuery("#addResultId").val('');
									jQuery("#addResultDialog").dialog("option", "title", WPA.getProperty('add_result_title'));
									jQuery("#addResultDialog").dialog("open");
									WPA.MyResults.resetAddResultForm();
								}
								else {
									WPA.alertError('<?php echo $this->get_property('error_add_result_no_gender_dob'); ?>');
								}
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

							// gender
							jQuery("#myProfileGender").combobox({
								select: function(event, ui) {
									WPA.userGender = jQuery(this).val();

									WPA.Ajax.saveProfileData({
										gender: WPA.userGender
									}, jQuery(event.currentTarget));
								}
							}).combobox('setValue', WPA.userGender);

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
							</div>

							<div class="wpa-profile-info-fieldset">

								<!-- DATE OF BIRTH -->
								<div class="wpa-profile-field">
									<label><?php echo $this->get_property('my_profile_dob'); ?>:</label>
									<span style="position: relative; top:-2px"><input class="ui-widget ui-widget-content ui-state-default ui-corner-all" size="30" type="text" id="myProfileDOB"/></span>
								</div>

								<!-- GENDER -->
								<div class="wpa-profile-field">
									<label><?php echo $this->get_property('my_profile_gender'); ?>:</label>
									<select id="myProfileGender">
										<option value="M"><?php echo $this->get_property('gender_M'); ?></option>
										<option value="F"><?php echo $this->get_property('gender_F'); ?></option>
									</select>
								</div>

								<!--  AGE CLASS -->
								<div class="wpa-profile-field">
									<label><?php echo $this->get_property('my_profile_age_class'); ?>:</label>
									<input type="text" disabled="disabled" id="myProfileAgeClass" size="20" class="ui-widget ui-widget-content ui-state-default ui-corner-all"/>
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
								<option value="this_month"><?php echo $this->get_property('filter_period_option_this_month'); ?></option>
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
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="30" maxlength=100 type="text" id="addResultEventName" />
								<span class="add-result-help" title="<?php echo $this->get_property('help_add_result_event_name'); ?>"></span>
								<span style="display:none;" title="<?php echo $this->get_property('help_add_result_cancel_event'); ?>" class="add-result-cancel-event"></span>
							</div>
							<div class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_event_category'); ?>:</label>
								<select class="add-result-required" id="addResultEventCategory">
									<option value="" selected="selected"></option>
								</select>
							</div>
							<div class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_location'); ?>:</label>
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="25" maxlength=100 type="text" id="addResultEventLocation" />
							</div>
							<div class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_event_sub_type'); ?>:</label>
								<select class="add-result-required" id="addResultEventSubType">
									<option value="" selected="selected"></option>
								</select>
							</div>
							<div class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_event_date'); ?>:</label>
								<input class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="30" type="text" id="addResultDate"/>
							</div>
							<div class="wpa-add-result-field">
								<label class="required"><?php echo $this->get_property('add_result_age_class'); ?>:</label>
								<input type="hidden" id="addResultAgeCategory" value=""/>
								<input type="text" class="ui-widget ui-widget-content ui-state-default ui-corner-all" disabled="disabled" readonly="readonly" size="30" id="addResultAgeCategoryDisplay"/>
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