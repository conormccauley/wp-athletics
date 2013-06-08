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
			add_action( 'wp_ajax_wpa_get_language_props', array ( $this, 'get_language_props') );
			add_action( 'wp_ajax_wpa_get_personal_bests', array ( $this, 'get_personal_bests') );
		}

		/**
		 * [AJAX] retrieves a JSON object of properties based on the selected language
		 */
		public function get_language_props() {
			global $wpa_lang;
			wp_send_json($wpa_lang);
			die();
		}

		/**
		 * [AJAX] returns personal bests for current user
		 */
		public function get_personal_bests() {
			global $current_user;

			$userId = null;

			wpa_log('individual: ' . $_POST['individual']);

			if( isset( $_POST['individual'] ) && $_POST['individual'] == 'true') {
				$userId = $current_user->ID;
			}

			// perform the query
			$results = $this->wpa_db->get_personal_bests( $userId, $_POST['ageCategory'], $_POST['eventCategoryId'] );
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
		public function is_valid_ajax($nonce) {
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
			if( !$current_user->ID )
				return;

			if( !is_admin() ) {
				$theme = strtolower(get_option( 'wp-athletics_theme', 'default') );

				wpa_log('enqueuing standard scripts');

				// register scripts and styles
				wp_register_script( 'datatables', WPA_PLUGIN_URL . '/resources/scripts/jquery.dataTables.min.js' );
				wp_register_script( 'wpa-functions', WPA_PLUGIN_URL . '/resources/scripts/wpa-functions.js' );
				wp_register_script( 'wpa-ajax', WPA_PLUGIN_URL . '/resources/scripts/wpa-ajax.js' );

				wp_register_style( 'datatables', WPA_PLUGIN_URL . '/resources/css/jquery.dataTables.css' );
				wp_register_style( 'wpa_style', WPA_PLUGIN_URL . '/resources/css/wpa-style.css' );
				wp_register_style( 'wpa_theme_jqueryui', WPA_PLUGIN_URL . '/resources/css/themes/' . $theme . '/jquery-ui.min.css' );
				wp_register_style( 'wpa_theme', WPA_PLUGIN_URL . '/resources/css/themes/' . $theme . '/wpa-' . $theme . '.css' );

				// enqueue scripts
				wp_enqueue_script( 'datatables' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-tabs' );
				wp_enqueue_script( 'jquery-ui-dialog' );
				wp_enqueue_script( 'jquery-ui-autocomplete' );
				wp_enqueue_script( 'jquery-ui-tooltip' );
				wp_enqueue_script( 'jquery-effects-highlight' );
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( 'wpa-functions' );
				wp_enqueue_script( 'wpa-ajax' );

				// enqueue styles
				wp_enqueue_style( 'datatables' );
				wp_enqueue_style( 'wpa_theme_jqueryui' );
				wp_enqueue_style( 'wpa_theme' );
				wp_enqueue_style( 'wpa_style' );
			}
			else {
				wpa_log('enqueuing admin scripts');
				wp_enqueue_script('jquery');
			}
		}
	}
}

?>