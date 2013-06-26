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

		/**
		 * Constructor for base class, reads the db object and sets as a global
		 **/
		public function __construct($db) {
			wpa_log(get_class($this) . ' class instantiated successfully');
			$this->wpa_db = $db;

			// add actions
			add_action( 'wp_ajax_wpa_get_personal_bests', array ( $this, 'get_personal_bests') );
			add_action( 'wp_ajax_wpa_load_global_data', array ( $this, 'load_global_data') );
			add_action( 'wp_ajax_wpa_get_results', array ( $this, 'get_results') );
			add_action( 'wp_ajax_wpa_get_event_results', array ( $this, 'get_event_results') );
			add_action( 'wp_ajax_wpa_get_user_profile', array ( $this, 'get_user_profile') );
			add_action( 'wp_ajax_wpa_search_autocomplete', array ( $this, 'search_autocomplete') );
			add_action( 'wp_ajax_wpa_get_user_oldest_result_year', array ( $this, 'get_user_oldest_result_year') );
		}

		/**
		 * [AJAX] Retrieves useful info such as event types and age categories
		 */
		public function load_global_data() {
			global $wpa_lang;
			$age_cats = $this->get_age_categories();
			$sub_types = $this->get_event_sub_type();
			$event_cats = $this->wpa_db->get_event_categories();

			wp_send_json(array(
				'ageCategories' => $age_cats,
				'eventTypes' => $sub_types,
				'eventCategories' => $event_cats,
				'languageProperties' => $wpa_lang
			));
			die();
		}

		/**
		 * [AJAX] Retrieves the oldest year for which the user has a recorded result
		 */
		public function get_user_oldest_result_year() {
			if( isset( $_POST['user_id'] ) ) {
				return wp_send_json($this->wpa_db->get_oldest_result_year( (integer)$_POST['user_id'] ) );
			}
			return false;
			die();
		}

		/**
		 * [AJAX] Returns info to display for a user profile
		 */
		public function get_user_profile() {
			$userId = (integer) $_POST['user_id'];
			$result = array(
				'ageCategory' => get_user_meta( $userId, 'wp-athletics_age_category', true ),
				'faveEvent' => get_user_meta( $userId, 'wp-athletics_fave_event_category', true ),
				'name' => $this->wpa_db->get_user_display_name( $userId ),
				'photo' => get_user_meta( $userId, 'wp-athletics_profile_photo', true )
			);
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
		 * [AJAX] Retrieves list of results for a user
		 */
		public function get_results() {
			global $current_user;
			$userId;

			if( isset( $_POST['user_id'] )) {
				$userId = (integer) $_POST['user_id'];
			}
			else {
				$userId = $current_user->ID;
			}

			// perform the query
			$results = $this->wpa_db->get_results( $userId, $_POST );

			// return as json
			wp_send_json($results);
			die();
		}

		/**
		 * [AJAX] Retrieves list of results for a given event
		 */
		public function get_event_results() {
			global $current_user;

			$eventId = (integer) $_POST['eventId'];

			// perform the query
			$results = $this->wpa_db->get_event_results( $eventId );

			// return as json
			wp_send_json($results);
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
		 * gets an array of event sub types from the settings or uses default value
		 */
		public function get_event_sub_type() {
			$event_sub_types_str = get_option( 'wp-athletics_event_sub_types', 'R:Road;XC:XC;T:Track;TR:Trail' );

			$return_value = array();

			$sub_types = explode( ';', $event_sub_types_str );
			if( count( $sub_types ) > 0) {
				foreach ( $sub_types as $sub_type_str ) {
					$sub_type = explode( ':', $sub_type_str );
					if( count($sub_type) == 2 ) {
						array_push( $return_value, array(
								'id' => $sub_type[0],
								'description' => $sub_type[1]
						));
					}
				}
			}
			return $return_value;
		}

		/**
		 * gets an array of age categories from the settings or uses default value
		 */
		public function get_age_categories() {
			$age_cats_str = get_option( 'wp-athletics_age_cats', 'J:Juvenile;M:Senior Male;F:Senior Female;M35:Male over 35;F35:Female over 35' );

			$return_value = array();

			$age_cats = explode( ';', $age_cats_str );
			if( count( $age_cats ) > 0) {
				foreach ( $age_cats as $age_cat_str ) {
					$age_cat = explode( ':', $age_cat_str );
					if( count($age_cat) == 2 ) {
						array_push( $return_value, array(
							'id' => $age_cat[0],
							'description' => $age_cat[1]
						));
					}
				}
			}
			return $return_value;
		}

		/**
		 * enqueues the required JS scripts for front end or admin pages
		 */
		public function enqueue_scripts_and_styles() {
			global $current_user;

			if( !is_admin() ) {
				$theme = strtolower(get_option( 'wp-athletics_theme', 'default') );

				wpa_log('enqueuing standard scripts');

				// register scripts and styles
				wp_register_script( 'datatables', WPA_PLUGIN_URL . '/resources/scripts/jquery.dataTables.min.js' );
				wp_register_script( 'wpa-custom', WPA_PLUGIN_URL . '/resources/scripts/wpa-custom.js' );
				wp_register_script( 'wpa-functions', WPA_PLUGIN_URL . '/resources/scripts/wpa-functions.js' );
				wp_register_script( 'wpa-ajax', WPA_PLUGIN_URL . '/resources/scripts/wpa-ajax.js' );

				wp_register_style( 'datatables', WPA_PLUGIN_URL . '/resources/css/jquery.dataTables.css' );
				wp_register_style( 'wpa_style', WPA_PLUGIN_URL . '/resources/css/wpa-style.css' );
				wp_register_style( 'wpa_theme_jqueryui', WPA_PLUGIN_URL . '/resources/css/themes/' . $theme . '/jquery-ui.css' );

				// enqueue scripts
				wp_enqueue_script( 'datatables' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-tabs' );
				wp_enqueue_script( 'jquery-ui-dialog' );
				wp_enqueue_script( 'jquery-ui-autocomplete' );
				wp_enqueue_script( 'jquery-ui-tooltip' );
				wp_enqueue_script( 'jquery-effects-highlight' );
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( 'wpa-custom' );
				wp_enqueue_script( 'wpa-functions' );
				wp_enqueue_script( 'wpa-ajax' );
				wp_enqueue_media();

				// enqueue styles
				wp_enqueue_style( 'datatables' );
				wp_enqueue_style( 'wpa_theme_jqueryui' );
				wp_enqueue_style( 'wpa_style' );
			}
			else {
				wpa_log('enqueuing admin scripts');
				wp_enqueue_script('jquery');
			}
		}

		/**
		 * Writes HTML to generate a dialogs for user profile and event results
		 */
		public function create_common_dialogs() {
		?>
			<!-- USER PROFILE DIALOG -->
			<div style="display:none" id="user-profile-dialog">
				<div class="wpa">
					<div class="wpa-profile">
						<!-- ATHLETE INFO -->
						<div class="wpa-profile-info">

							<!-- ATHLETE PHOTO -->
							<div class="wpa-profile-photo wpa-profile-photo-default" id="wpaUserProfilePhoto"></div>

							<!-- DISPLAY NAME -->
							<div class="wpa-profile-field">
								<label><?php echo $this->get_property('my_profile_display_name_label'); ?>:</label>
								<span id="wpa-profile-name"></span>
							</div>

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
								<span id="profileFilterEventNameCancel" style="display:none;" title="<?php echo $this->get_property('filter_event_name_cancel_text'); ?>" class="filter-event-name-remove"></span>
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
									<th><?php echo $this->get_property('column_position') ?></th>
									<th><?php echo $this->get_property('column_club_rank') ?><span class="column-help" title="<?php echo $this->get_property('help_column_rank'); ?>"></span></th>
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
				  <div class="wpa-event-info">
					<div class="wpa-event-info-title">
						<span id="eventInfoName"></span>
					</div>
					<div>
						<span id="eventInfoDate"></span>
						<span id="eventInfoDetail"></span>
					</div>
				  </div>

				  <div id="event-results">
					<table cellpadding="0" cellspacing="0" border="0" class="display ui-state-default" style="border-bottom:none" id="event-results-table" width="100%">
						<thead>
							<tr>
								<th></th>
								<th></th>
								<th><?php echo $this->get_property('column_athlete_name') ?></th>
								<th><?php echo $this->get_property('column_time') ?></th>
								<th><?php echo $this->get_property('column_age_category') ?></th>
								<th><?php echo $this->get_property('column_position') ?></th>
								<th></th>
							</tr>
						</thead>
					</table>
				  </div>
			  </div>
			</div>
		<?php
		}
	}
}

?>