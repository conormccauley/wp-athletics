<?php

/**
 * Logs a message to the output file when debug mode is enabled
 * @param $message
 */
function wpa_log($message) {
	if (WP_DEBUG === true) {
		if (is_array($message) || is_object($message)) {
			error_log('[WPA]:' . print_r($message, true));
		} else {
			error_log('[WPA]:' . $message);
		}
	}
}

/**
 * Base class for classes requiring database access and utility functions
 */
if(!class_exists('WPA_Base')) {

	class WPA_Base {

		public $wpa_db;
		public $nonce = 'wpathletics3947372';

		/**
		 * Constructor for base class, reads the db object and sets as a global
		 **/
		public function __construct( $db ) {

			$this->wpa_db = $db;

			// add actions
			add_action( 'wp_ajax_wpa_get_personal_bests', array ( $this, 'get_personal_bests') );
			add_action( 'wp_ajax_wpa_load_global_data', array ( $this, 'load_global_data') );
			add_action( 'wp_ajax_wpa_get_event_categories', array ( $this, 'get_event_categories') );
			add_action( 'wp_ajax_wpa_get_age_categories', array ( $this, 'get_age_categories') );
			add_action( 'wp_ajax_wpa_get_results', array ( $this, 'get_results') );
			add_action( 'wp_ajax_wpa_get_all_results', array ( $this, 'get_all_results') );
			add_action( 'wp_ajax_wpa_get_event_results', array ( $this, 'get_event_results') );
			add_action( 'wp_ajax_wpa_get_user_profile', array ( $this, 'get_user_profile') );
			add_action( 'wp_ajax_wpa_get_user_dob', array ( $this, 'get_user_dob') );
			add_action( 'wp_ajax_wpa_search_autocomplete', array ( $this, 'search_autocomplete') );
			add_action( 'wp_ajax_wpa_get_user_oldest_result_year', array ( $this, 'get_user_oldest_result_year') );
			add_action( 'wp_ajax_wpa_save_profile_data', array ( $this, 'save_profile_data') );

			add_action( 'wp_ajax_wpa_event_autocomplete', array ( $this, 'event_autocomplete') );
			add_action( 'wp_ajax_wpa_location_autocomplete', array ( $this, 'location_autocomplete') );
			add_action( 'wp_ajax_wpa_user_autocomplete', array ( $this, 'user_autocomplete') );

			add_action( 'wp_ajax_wpa_get_event', array ( $this, 'get_event') );
			add_action( 'wp_ajax_wpa_update_result', array ( $this, 'update_result') );
			add_action( 'wp_ajax_wpa_validate_event_entry', array ( $this, 'validate_event_entry') );
			add_action( 'wp_ajax_wpa_delete_result', array ( $this, 'delete_result') );
			add_action( 'wp_ajax_wpa_delete_results', array ( $this, 'delete_results') );
			add_action( 'wp_ajax_wpa_reassign_results', array ( $this, 'reassign_results') );
			add_action( 'wp_ajax_wpa_get_result_info', array ( $this, 'get_result_info') );
			add_action( 'wp_ajax_wpa_update_event', array( $this, 'update_event' ) );
			add_action( 'wp_ajax_wpa_create_event', array( $this, 'create_event' ) );

			// no priv actions (for users not logged in)
			add_action( 'wp_ajax_nopriv_wpa_get_personal_bests', array ( $this, 'get_personal_bests') );
			add_action( 'wp_ajax_nopriv_wpa_load_global_data', array ( $this, 'load_global_data') );
			add_action( 'wp_ajax_nopriv_wpa_get_results', array ( $this, 'get_results') );
			add_action( 'wp_ajax_nopriv_wpa_get_event', array ( $this, 'get_event') );
			add_action( 'wp_ajax_nopriv_wpa_get_event_results', array ( $this, 'get_event_results') );
			add_action( 'wp_ajax_nopriv_wpa_get_user_profile', array ( $this, 'get_user_profile') );
			add_action( 'wp_ajax_nopriv_wpa_search_autocomplete', array ( $this, 'search_autocomplete') );
			add_action( 'wp_ajax_nopriv_wpa_get_user_oldest_result_year', array ( $this, 'get_user_oldest_result_year') );

			if( is_admin() ) {
				add_action( 'admin_enqueue_scripts', array ($this, 'enqueue_common_scripts_and_styles' ) );
			}
		}

		/**
		 * [AJAX] Retrieves useful info such as event types and age categories
		 */
		public function load_global_data() {
			global $wpa_lang;
			global $wpa_settings;
			global $current_user;

			wpa_log('loading global data');

			$age_cats = $this->wpa_db->get_age_categories();
			$sub_types = $this->wpa_db->get_event_sub_types();
			$event_cats = $this->wpa_db->get_event_categories();

			wp_send_json(array(
				'ageCategories' => $age_cats,
				'eventTypes' => $sub_types,
				'eventCategories' => $event_cats,
				'languageProperties' => $wpa_lang,
				'settings' => $wpa_settings,
				'userDOB' => get_user_meta( $current_user->ID, 'wp-athletics_dob', true ),
				'userHideDOB' => (get_user_meta( $current_user->ID, 'wp-athletics_hide_dob', true ) == 'yes'),
				'userGender' => get_user_meta( $current_user->ID, 'wp-athletics_gender', true ),
				'isLoggedIn' => is_user_logged_in(),
				'isAdmin' => is_admin()
			));
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
		 * [AJAX] Updates a single event details
		 */
		function update_event() {
			// perform the query
			$result = $this->wpa_db->update_event( $_POST );

			// return as json
			wp_send_json( $result );
			die();
		}

		/**
		 * [AJAX] Creates a single event
		 */
		function create_event() {
			// perform the query
			$result = $this->wpa_db->create_event( $_POST );

			// return as json
			wp_send_json( $result );
			die();
		}

		/**
		 * [AJAX] Validates an event entry
		 */
		function validate_event_entry() {
			// perform the query
			$result = array(
				'valid' => $this->wpa_db->validate_event_entry( $_POST )
			);

			// return as json
			wp_send_json( $result );
			die();
		}

		/**
		 * [AJAX] Saves or updates an event result
		 */
		public function update_result() {
			//if( $this->is_valid_ajax( $this->nonce ) ) {
				global $current_user;

				if( !isset($_POST['userId']) ) {
					$_POST['userId'] = $current_user->ID;
				}
				// perform the insert or update
				$result = $this->wpa_db->update_result( $_POST );

				// return as json
				wp_send_json($result);
			//}
			die();
		}

		/**
		 * [AJAX] Saves provided profile data to the user meta data table
		 */
		public function save_profile_data() {
			//if( $this->is_valid_ajax( $this->nonce ) ) {
				global $current_user;

				$gender = $_POST['gender'];
				$dob = $_POST['dob'];
				$hide_dob = $_POST['hideDob'];
				$fave_event = $_POST['faveEvent'];
				$display_name = $_POST['displayName'];

				$user_id = $current_user->ID;

				if( isset( $_POST['userId'] ) && $_POST['userId'] != '' ) {
					$user_id = $_POST['userId'];
				}

				if(isset( $gender ) ) {
					wpa_log('updating gender');
					update_user_meta( $user_id, 'wp-athletics_gender', $gender );
				}
				if(isset( $dob ) ) {
					wpa_log('updating dob');
					update_user_meta( $user_id, 'wp-athletics_dob', $dob );
				}
				if(isset( $hide_dob ) ) {
					wpa_log('updating hide dob value');
					update_user_meta( $user_id, 'wp-athletics_hide_dob', ( $hide_dob == 'true' ? 'yes' : 'no' ) );
				}
				if(isset( $fave_event ) ) {
					wpa_log('updating fave event');
					update_user_meta( $user_id, 'wp-athletics_fave_event_category', $fave_event );
				}
				if(isset( $display_name ) ) {
					wpa_log('updating display name');
					$this->wpa_db->update_user_display_name( $user_id, $display_name );
				}

				$result = array('success'=>true);

				// return as json
				wp_send_json($result);
			//}
			die();
		}

		/**
		 * [AJAX] Deletes an event result
		 */
		public function delete_result() {
			//if( $this->is_valid_ajax( $this->nonce ) ) {
				global $current_user;

				// perform the delete
				$result = $this->wpa_db->delete_result( $_POST['resultId'] );

				// return as json
				wp_send_json( $result );
			//}
			die();
		}

		/**
		 * [AJAX] Deletes a set of event results
		 */
		public function delete_results() {
			global $current_user;

			// perform the delete
			$result = $this->wpa_db->delete_results( $_POST['ids'] );

			// return as json
			wp_send_json( $result );
			die();
		}

		/**
		 * [AJAX] Reassigns a set of event results to another user ID
		 */
		public function reassign_results() {
			global $current_user;

			// perform the delete
			$result = $this->wpa_db->reassign_results( $_POST['ids'],  $_POST['reassignId'] );

			// return as json
			wp_send_json( $result );
			die();
		}

		/**
		 * [AJAX] Retrieves event info
		 */
		public function get_result_info() {
			//if( $this->is_valid_ajax( $this->nonce ) ) {
				global $current_user;

				// perform the delete
				$result = $this->wpa_db->get_result_info( $_POST['resultId'] );

				// return as json
				wp_send_json( $result );
			//}
			die();
		}

		/**
		 * [AJAX] Gets a list of event categories
		 */
		public function get_event_categories() {
			wp_send_json( $this->wpa_db->get_event_categories() );
			die();
		}

		/**
		 * [AJAX] Gets a list of age categories
		 */
		public function get_age_categories() {
			wp_send_json( $this->wpa_db->get_age_categories() );
			die();
		}

		/**
		 * [AJAX] Gets a list of sub type categories
		 */
		public function get_sub_type_categories() {
			wp_send_json( $this->wpa_db->get_event_sub_types() );
			die();
		}

		/**
		 * [AJAX] Retrieves the oldest year for which the user has a recorded result
		 */
		public function get_user_oldest_result_year() {
			if( isset( $_POST['user_id'] ) ) {
				return wp_send_json( $this->wpa_db->get_oldest_result_year( (integer)$_POST['user_id'] ) );
			}
			return false;
			die();
		}

		/**
		 * [AJAX] Retrieves list of events for autocomplete search
		 */
		public function event_autocomplete() {
			// perform the query
			$results = $this->wpa_db->get_events( strtolower($_GET['term']) );

			// return as json
			wp_send_json( $results );
			die();
		}

		/**
		 * [AJAX] Retrieves list of locations for autocomplete search
		 */
		public function location_autocomplete() {
			// perform the query
			$results = $this->wpa_db->get_locations( strtolower($_GET['term']) );

			// return as json
			wp_send_json( $results );
			die();
		}

		/**
		 * [AJAX] Retrieves list of users for autocomplete search
		 */
		public function user_autocomplete() {
			// perform the query
			$results = $this->wpa_db->get_athletes( strtolower($_GET['term']) );

			// return as json
			wp_send_json( $results );
			die();
		}

		/**
		 * [AJAX] Returns info to display for a user profile
		 */
		public function get_user_profile() {
			$user_id = (integer) $_POST['user_id'];
			$result = array(
				'gender' => get_user_meta( $user_id, 'wp-athletics_gender', true ),
				'dob' => get_user_meta( $user_id, 'wp-athletics_dob', true ),
				'faveEvent' => get_user_meta( $user_id, 'wp-athletics_fave_event_category', true ),
				'name' => $this->wpa_db->get_user_display_name( $user_id ),
				'photo' => get_user_meta( $user_id, 'wp-athletics_profile_photo', true )
			);
			wp_send_json($result);
			die();
		}

		/**
		 * [AJAX] Returns a user DOB value
		 */
		public function get_user_dob() {
			$user_id = (integer) $_POST['user_id'];
			$result = get_user_meta( $user_id, 'wp-athletics_dob', true );
			wp_send_json($result);
			die();
		}

		/**
		 * [AJAX] Performs ajax request for autocomple search on events and users
		 */
		public function search_autocomplete() {
			$term = strtolower($_GET['term']);

			// perform the event query
			$events = $this->wpa_db->get_events( $term );
			foreach ( $events as $event ) {
				$event->category = 'event';
			}

			// perform athlete query
			$athletes = $this->wpa_db->get_athletes( $term );
			foreach ( $athletes as $athlete ) {
				$athlete->category = 'athlete';
			}

			// merge both results
			$results = array_merge($events, $athletes);

			// return as json
			wp_send_json($results);
			die();
		}

		/**
		 * [AJAX] Retrieves list of results for all users
		 */
		public function get_all_results() {

			// perform the query
			$results = $this->wpa_db->get_results( -1, $_POST, true );

			// return as json
			wp_send_json($results);
			die();
		}

		/**
		 * [AJAX] Retrieves list of results for a user
		 */
		public function get_results() {
			global $current_user;
			$user_id;

			if( isset( $_POST['user_id'] )) {
				$user_id = (integer) $_POST['user_id'];
			}
			else {
				$user_id = $current_user->ID;
			}

			// perform the query
			$results = $this->wpa_db->get_results( $user_id, $_POST );

			// return as json
			wp_send_json( $results );
			die();
		}

		/**
		 * [AJAX] Retrieves list of results for a given event
		 */
		public function get_event_results() {
			global $current_user;

			$event_id = (integer) $_POST['eventId'];

			// perform the query
			$results = $this->wpa_db->get_event_results( $event_id );

			$data = array(
				'id' => $event_id,
				'data' => $results
			);

			// return as json
			wp_send_json( $data );
			die();
		}

		/**
		 * [AJAX] returns personal bests for current user
		 */
		public function get_personal_bests() {

			// perform the query
			$results = $this->wpa_db->get_personal_bests( $_POST );
			wpa_log('retrieved ' . count($results) . ' personal best result');

			// return as json
			wp_send_json($results);
			die();
		}

		/**
		 * retrives a language property based on a supplied key, if it does not exist, returns the $default value or the key
		 */
		public function get_property($key, $default = null) {
			global $wpa_lang;

			if( array_key_exists( $key, $wpa_lang ) ) {
				return $wpa_lang[$key];
			}

			return $default ? $default : $key;
		}

		/**
		 * checks if the nonce value is valid for a wordpress AJAX request
		 */
		public function is_valid_ajax( $nonce ) {

			global $wpa_lang;
			if(check_ajax_referer( $nonce, 'security', false ) ) {
				return true;
			}
			else {
				die( $this->get_property( 'ajax_no_permission') );
			}
		}

		/**
		 * Creates a display page
		 */
		public function generate_page( $title, $status = 'publish') {
			$page = array(
					'post_title' => $title,
					'post_content' => '',
					'post_status' => $status,
					'post_type' => 'page',
					'comment_status' => 'closed',
					'ping_status' => 'closed',
					'post_category' => array(1)
			);
			return wp_insert_post( $page );
		}

		/**
		 * enqueues the common required JS scripts for front end or admin pages
		 */
		public function enqueue_common_scripts_and_styles() {
			$theme = strtolower(get_option( 'wp-athletics_theme', 'default') );

			// common scripts
			wp_enqueue_script( 'wpa-custom' );
			wp_enqueue_script( 'wpa-functions' );
			wp_enqueue_script( 'wpa-ajax' );
			wp_enqueue_script( 'datatables' );

			// common styles
			wp_enqueue_style( 'datatables' );

			if( !is_admin() ) {

				wp_register_style( 'wpa_theme_jqueryui', WPA_PLUGIN_URL . '/resources/css/themes/' . $theme . '/jquery-ui.css' );

				// enqueue scripts

				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-tabs' );
				wp_enqueue_script( 'jquery-ui-dialog' );
				wp_enqueue_script( 'jquery-ui-autocomplete' );
				wp_enqueue_script( 'jquery-ui-tooltip' );
				wp_enqueue_script( 'jquery-effects-highlight' );
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_media();

				// enqueue styles
				wp_enqueue_style( 'wpa_theme_jqueryui' );
				wp_enqueue_style( 'wpa_style' );
			}
			else {

				// register scripts and styles
				wp_register_style( 'wpa_admin_style', WPA_PLUGIN_URL . '/resources/css/wpa-admin-style.css' );
				wp_register_style( 'wpa_theme_jqueryui', WPA_PLUGIN_URL . '/resources/css/themes/default/jquery-ui.css' );
				wp_register_script( 'wpa-admin', WPA_PLUGIN_URL . '/resources/scripts/wpa-admin.js' );

				// enqueue scripts
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-tabs' );
				wp_enqueue_script( 'jquery-ui-dialog' );
				wp_enqueue_script( 'jquery-ui-autocomplete' );
				wp_enqueue_script( 'jquery-ui-tooltip' );
				wp_enqueue_script( 'jquery-effects-highlight' );
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( 'wpa-admin' );

				// enqueue styles
				wp_enqueue_style( 'wpa_style' );
				wp_enqueue_style( 'wpa_admin_style' );
				wp_enqueue_style( 'wpa_theme_jqueryui' );
			}
		}

		/**
		 * Creates a dialog to edit/add events
		 */
		public function create_edit_event_dialog() {
		?>
			<div id="edit-event-dialog" style="display:none">
			<input type="hidden" id="editEventDate" value=""/>
				<div class="wpa-add-result-field">
					<label class="required"><?php echo $this->get_property('add_result_name'); ?>:</label>
					<input style="background: #fff" class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="40" maxlength=100 type="text" id="editEventName" />
				</div>
				<div class="wpa-add-result-field add-result-no-bg">
					<label class="required"><?php echo $this->get_property('add_result_event_category'); ?>:</label>
					<select class="add-result-required" id="editEventCategory">
						<option value="" selected="selected"></option>
					</select>
				</div>
				<div class="wpa-add-result-field add-result-no-bg">
					<label class="required"><?php echo $this->get_property('add_result_location'); ?>:</label>
					<input class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="25" maxlength=100 type="text" id="editEventLocation" />
				</div>
				<div class="wpa-add-result-field add-result-no-bg">
					<label class="required"><?php echo $this->get_property('add_result_event_sub_type'); ?>:</label>
					<select class="add-result-required" id="editEventSubType">
						<option value="" selected="selected"></option>
					</select>
				</div>
				<div class="wpa-add-result-field add-result-no-bg">
					<label class="required"><?php echo $this->get_property('add_result_event_date'); ?>:</label>
					<input readonly="readonly" style="position:relative; top:-2px" class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="30" type="text" id="editResultDate"/>
				</div>
			</div>
		<?php
		}

		/**
		 * Creates a dialog to edit/add event results
		 */
		public function create_edit_result_dialog() {
			global $edit_result_dialog_created;

			if( !$edit_result_dialog_created ) {
		?>
			<div style="display:none" id="add-result-dialog">
				<form>
					<input type="hidden" id="addResultId" value=""/>
					<input type="hidden" id="addResultEventId" value=""/>
					<input type="hidden" id="addResultEventDate" value=""/>

					<div class="wpa-add-result-field">
						<label class="required"><?php echo $this->get_property('add_result_event_name'); ?>:</label>
						<input style="background: #fff" class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="40" maxlength=100 type="text" id="addResultEventName" />
						<span class="wpa-help" title="<?php echo $this->get_property('help_add_result_event_name'); ?>"></span>
						<span style="display:none;" title="<?php echo $this->get_property('help_add_result_cancel_event'); ?>" class="add-result-cancel-event"></span>
					</div>
					<div class="wpa-add-result-field add-result-no-bg">
						<label class="required"><?php echo $this->get_property('add_result_event_category'); ?>:</label>
						<select class="add-result-required" id="addResultEventCategory">
							<option value="" selected="selected"></option>
						</select>
					</div>
					<div class="wpa-add-result-field add-result-no-bg">
						<label class="required"><?php echo $this->get_property('add_result_location'); ?>:</label>
						<input class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="25" maxlength=100 type="text" id="addResultEventLocation" />
					</div>
					<div class="wpa-add-result-field add-result-no-bg">
						<label class="required"><?php echo $this->get_property('add_result_event_sub_type'); ?>:</label>
						<select class="add-result-required" id="addResultEventSubType">
							<option value="" selected="selected"></option>
						</select>
					</div>
					<div class="wpa-add-result-field add-result-no-bg">
						<label class="required"><?php echo $this->get_property('add_result_event_date'); ?>:</label>
						<input readonly="readonly" style="position:relative; top:-2px" class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="20" type="text" id="addResultDate"/>
					</div>
					<div class="wpa-add-result-field add-result-no-bg">
						<label><?php echo $this->get_property('add_result_age_class'); ?>:</label>
						<input type="hidden" id="addResultAgeCategory" value=""/>
						<input type="text" class="ui-widget ui-widget-content ui-state-default ui-corner-all" disabled="disabled" readonly="readonly" size="20" id="addResultAgeCategoryDisplay"/>
					</div>
					<div style="display:none;" time-format="h" class="wpa-add-result-field add-result-no-bg">
						<label class="required"><?php echo $this->get_property('add_result_event_time_hours'); ?>:</label>
						<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="h" maxlength="2" size="3" type="text" id="addResultTimeHours" value="0">
					</div>
					<div style="display:none;" time-format="m" class="wpa-add-result-field add-result-no-bg">
						<label class="required"><?php echo $this->get_property('add_result_event_time_minutes'); ?>:</label>
						<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="m" maxlength="2" size="3" type="text" id="addResultTimeMinutes" value="0">
					</div>
					<div style="display:none;" time-format="s" class="wpa-add-result-field add-result-no-bg">
						<label class="required"><?php echo $this->get_property('add_result_event_time_seconds'); ?>:</label>
						<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="s" maxlength="2" size="3" type="text" id="addResultTimeSeconds" value="0">
					</div>
					<div style="display:none;" time-format="ms" class="wpa-add-result-field add-result-no-bg">
						<label class="required"><?php echo $this->get_property('add_result_event_time_milliseconds'); ?>:</label>
						<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="ms" maxlength="2" size="3" type="text" id="addResultTimeMilliSeconds" value="0">
					</div>
					<div class="wpa-add-result-field add-result-no-bg">
						<label><?php echo $this->get_property('add_result_event_position'); ?>:</label>
						<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" size="5" type="text" id="addResultPosition" value="">
					</div>
					<div class="wpa-add-result-field add-result-no-bg">
						<label><?php echo $this->get_property('add_result_garmin_link'); ?>:</label>
						<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" size="30" type="text" id="addResultGarminId" value="">
						<span class="wpa-help" title="<?php echo $this->get_property('help_add_result_garmin_id'); ?>"></span>
					</div>
				</form>
			</div>
			<?php
				$edit_result_dialog_created = true;
			}
		}

		/**
		 * Writes the HTML to generate the event results table
		 */
		public function create_event_results_table( $tableId = 'event-results-table' ) {
		?>
			<div id="event-results">
				<table cellpadding="0" cellspacing="0" border="0" class="display ui-state-default" style="border-bottom:none" id="<?php echo $tableId; ?>" width="100%">
					<thead>
						<tr>
						<th></th>
						<th></th>
						<th></th>
						<th><?php echo $this->get_property('column_athlete_name') ?></th>
						<th><?php echo $this->get_property('column_time') ?></th>
						<th><?php echo $this->get_property('column_pace') ?></th>
						<th><?php echo $this->get_property('column_age_category') ?></th>
						<th><?php echo $this->get_property('column_position') ?></th>
						<th></th>
						</tr>
					</thead>
				</table>
			  </div>
		<?php
		}

		/**
		 * Writes HTML to generate a dialogs for user profile and event results
		 */
		public function create_common_dialogs() {
			global $common_dialogs_created;
			global $current_user;

			if( !$common_dialogs_created ) {
		?>
				<!-- USER PROFILE DIALOG -->
				<div style="display:none" id="user-profile-dialog">
					<div class="wpa">
						<div class="wpa-profile">
							<!-- ATHLETE INFO -->
							<div class="wpa-profile-info">

								<!-- ATHLETE PHOTO -->
								<div class="wpa-profile-photo wpa-profile-photo-default" id="wpaUserProfilePhoto"></div>

								<div class="wpa-profile-info-fieldset">
									<!-- DISPLAY NAME -->
									<div class="wpa-profile-field">
										<label><?php echo $this->get_property('my_profile_display_name_label'); ?>:</label>
										<span id="wpa-profile-name"></span>
									</div>

									<!-- DOB -->
									<?php
									if( get_user_meta( $current_user->ID, 'wp-athletics_hide_dob', true ) != 'yes' ) {
									?>
									<div class="wpa-profile-field">
										<label><?php echo $this->get_property('my_profile_dob'); ?>:</label>
										<span id="wpa-profile-dob"></span>
									</div>
									<?php
									}
									?>

									<!-- AGE CLASS -->
									<div class="wpa-profile-field">
										<label><?php echo $this->get_property('my_profile_age_class'); ?>:</label>
										<span id="wpa-profile-age-class"></span>
									</div>

									<!-- FAVOURITE EVENT -->
									<div class="wpa-profile-field">
										<label><?php echo $this->get_property('my_profile_fave_event'); ?>:</label>
										<span id="wpa-profile-fave-event"></span>
									</div>
								</div>
								<br style="clear:both"/>
							</div>
						</div>

						<div class="wpa-menu">

							<!-- FILTERS -->
							<div class="wpa-filters ui-corner-all" style="width:100%">
								<div class="filter-ignore-for-pb-dialog">
									<select id="profileFilterEvent">
										<option value="all" selected="selected"><?php echo $this->get_property('filter_events_option_all'); ?></option>
									</select>
								</div>

								<select id="profileFilterPeriod">
									<option value="all" selected="selected"><?php echo $this->get_property('filter_period_option_all'); ?></option>
									<option value="this_month"><?php echo $this->get_property('filter_period_option_this_month'); ?></option>
									<option value="this_year"><?php echo $this->get_property('filter_period_option_this_year'); ?></option>
								</select>

								<select id="profileFilterType">
									<option value="all" selected="selected"><?php echo $this->get_property('filter_type_option_all'); ?></option>
								</select>

								<select id="profileFilterAge">
									<option value="all" selected="selected"><?php echo $this->get_property('filter_age_option_all'); ?></option>
								</select>

								<div class="filter-ignore-for-pb-dialog">
									<input id="profileFilterEventName" highlight-class="filter-highlight" default-text="<?php echo $this->get_property('filter_event_name_input_text'); ?>" class="ui-corner-all ui-widget ui-widget-content ui-state-default wpa-search wpa-search-disabled"></input>
									<span id="profileFilterEventNameCancel" style="display:none;" title="<?php echo $this->get_property('filter_event_name_cancel_text'); ?>" class="filter-name-remove"></span>
								</div>
							</div>

							<br style="clear:both"/>
						</div>

						<!-- RESULTS TABS -->
						<div class="wpa-tabs wpa-results-tabs" id="results-tabs">
						  <ul>
						    <li><a href="#tabs-results"><?php echo $this->get_property('results_main_tab') ?></a></li>
						    <li><a href="#tabs-personal-bests"><?php echo $this->get_property('results_personal_bests_tab') ?></a></li>
						  </ul>
						  <div id="tabs-results">
							<table cellpadding="0" cellspacing="0" border="0" class="display ui-state-default" style="border-bottom:none" id="results-table" width="100%">
								<thead>
									<tr>
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
						  <div id="tabs-personal-bests" wpa-tab-type="pb">
							<table cellpadding="0" cellspacing="0" border="0" class="display ui-state-default" id="personal-bests-table" width="100%">
								<thead>
									<tr>
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
					</div>
				</div>

				<!-- EVENT RESULTS DIALOG -->
				<div style="display:none" id="event-results-dialog">
				  <div class="wpa">
				  	  <div>
					  	  <!-- event info -->
						  <div class="wpa-event-info">
							<div class="wpa-event-info-title">
								<span id="eventInfoName"></span>
							</div>
							<div>
								<span id="eventInfoDate"></span>
								<span id="eventInfoDetail"></span>
							</div>
						  </div>
						  <!-- add result button -->
						 <div class="wpa-event-info-actions">
						 	<button id="wpa-event-info-add-result"><?php echo $this->get_property('add_result_title_event_dialog') ?></button>
						 </div>
						 <br style="clear:both;"/>
					 </div>

					 <?php $this->create_event_results_table(); ?>
				  </div>
				</div>

				<!-- RANKINGS DIALOG -->
				<div style="display:none" id="rankingsDialog">
					<div id="rankingsDisplayOptions">
						<form id="rankings-display-form">
							<input type="radio" id="best-athlete-result-radio" checked="checked" name="rankings-display-mode" value="best-athlete-result">
								<label for="best-athlete-result-radio"><?php echo $this->get_property('rankings_display_best_athlete_result') ?></label>
							</input>
							<input type="radio" id="all-athlete-results-radio" name="rankings-display-mode" value="all-athlete-results">
								<label for="all-athlete-results-radio"><?php echo $this->get_property('rankings_display_all_athlete_results') ?></label>
							</input>
						</form>
					</div>
					<table width="100%" class="display ui-state-default" id="table-rankings">
						<thead>
							<tr>
								<th></th>
								<th></th>
								<th>#</th>
								<th><?php echo $this->get_property('column_time') ?></th>
								<th><?php echo $this->get_property('column_pace') ?></th>
								<th><?php echo $this->get_property('column_athlete_name') ?></th>
								<th><?php echo $this->get_property('column_event_name') ?></th>
								<th><?php echo $this->get_property('column_event_location') ?></th>
								<th><?php echo $this->get_property('column_event_type') ?></th>
								<th><?php echo $this->get_property('column_event_date') ?></th>
								<th><?php echo $this->get_property('column_age_category') ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>

				<!-- CONFIRM DELETE RESULT DIALOG -->
				<div style="display:none" id="result-delete-confirm" title="<?php echo $this->get_property('confirm_result_delete_title'); ?>">
	  				<p class="wpa-alert">
	  					<span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span>
	  					<?php echo $this->get_property('confirm_result_delete'); ?>
	  				</p>
				</div>

				<!--  ERROR DIALOG -->
				<div style="display:none" id="wpa-error-dialog" title="<?php echo $this->get_property('error_dialog_title'); ?>">
	  				<div id="wpa-error-dialog-text"></div>
				</div>

				<!-- LOADING DIALOG -->
				<div style="display:none" id="wpa-loading-dialog">
					<div class="wpa-loading">
						<div id="wpa-loading-animation"></div>
						<div id="wpa-loading-text"><?php echo $this->get_property('loading_dialog_text'); ?></div>
						<br style="clear:both;"/>
					</div>
				</div>

				<!-- PERSONAL BESTS TABLE LOADING -->
				<div id="wpa-pb-table-processing" class="dataTables_pb_processing" style="display:none;"><?php echo $this->get_property('table_loading_records_message'); ?>.</div>

			<?php
				$common_dialogs_created = true;
			}
		}

	}
}

?>