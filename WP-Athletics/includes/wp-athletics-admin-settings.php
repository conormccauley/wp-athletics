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
			add_action( 'wp_ajax_wpa_admin_save_event_categories', array ( $this, 'save_event_category') );
			add_action( 'wp_ajax_wpa_admin_delete_event_category', array ( $this, 'delete_event_category') );
			add_action( 'wp_ajax_wpa_admin_delete_age_category', array ( $this, 'delete_age_category') );
			add_action( 'wp_ajax_wpa_admin_save_age_category', array ( $this, 'save_age_category') );
			add_action( 'wp_ajax_wpa_get_all_events', array( $this, 'get_events' ) );
			add_action( 'wp_ajax_wpa_delete_events', array( $this, 'delete_events' ) );
			add_action( 'wp_ajax_wpa_merge_events', array( $this, 'merge_events' ) );
			add_action( 'wp_ajax_wpa_create_user', array( $this, 'create_user' ) );
		}

		/**
		 * Constructs an admin menu
		 */
		function admin_menu() {
			if( $this->has_permission_to_manage( false ) ) {
				add_menu_page('WP Athletics Settings', 'WP Athletics', 'manage_wp_athletics', 'wp-athletics-settings', array( $this, 'wpa_settings' ) );
				add_submenu_page( 'wp-athletics-settings', 'WP Athletics Add Results', 'Add Results', 'manage_wp_athletics', 'wp-athletics-add-results', array( $this, 'wpa_add_results') );
				add_submenu_page( 'wp-athletics-settings', 'WP Athletics Results Mangager', 'Manage Results', 'manage_wp_athletics', 'wp-athletics-manage-results', array( $this, 'wpa_manage_results') );
				add_submenu_page( 'wp-athletics-settings', 'WP Athletics Events Mangager', 'Manage Events', 'manage_wp_athletics', 'wp-athletics-manage-events', array( $this, 'wpa_manage_events') );
				add_submenu_page( 'wp-athletics-settings', 'WP Athletics Event Categories', 'Event Categories', 'manage_wp_athletics', 'wp-athletics-event-categories', array( $this, 'wpa_event_category_settings' ) );
				add_submenu_page( 'wp-athletics-settings', 'WP Athletics Age Categories', 'Age Categories', 'manage_wp_athletics', 'wp-athletics-age-categories', array( $this, 'wpa_age_category_settings') );
				add_submenu_page( 'wp-athletics-settings', 'WP Athletics Print Rankings', 'Print Rankings', 'manage_wp_athletics', 'wp-athletics-print-rankings', array( $this, 'wpa_print_rankings') );
			}
		}

		/**
		 * [AJAX] Performs request for events
		 */
		function get_events() {
			// perform the query
			$result = $this->wpa_db->search_events( $_POST );

			// return as json
			wp_send_json( $result );
			die();
		}

		/**
		 * [AJAX] Create a new athlete user
		 */
		function create_user() {
			// perform the query
			$result = $this->wpa_db->create_user( $_POST );

			// return as json
			wp_send_json( $result );
			die();
		}

		/**
		 * [AJAX] Deletes a list of events and reassigns their results to a specified event ID
		 */
		function delete_events() {
			// perform the query
			$result = $this->wpa_db->delete_events( $_POST );

			// return as json
			wp_send_json( $result );
			die();
		}

		/**
		 * [AJAX] Merges a list of event results to a specific event ID and discards the events
		 */
		function merge_events() {
			// perform the query
			$results = $this->wpa_db->merge_events( $_POST );

			// return as json
			wp_send_json( $results );
			die();
		}

		/**
		 * checks if current admin user has privileges to manage WP athletics
		 */
		function has_permission_to_manage( $die = true ) {
			if ( current_user_can('manage_wp_athletics') ) {
				return true;
			}
			else {
				if( $die ) {
					wp_die('You do not have sufficient permissions to access this page.');
				}
				return false;
			}
		}

		/**
		 * [AJAX] Saves an age cateogry
		 */
		function save_age_category() {
			wp_send_json( array('success'=> $this->wpa_db->update_age_category( $_POST ) ) );
			die();
		}

		/**
		 * [AJAX] Deletes an age category
		 */
		function delete_age_category() {
			wp_send_json( array( 'success'=> $this->wpa_db->delete_age_category( $_POST['id'] ) ) );
			die();
		}

		/**
		 * [AJAX] Saves an event cateogry
		 */
		function save_event_category() {
			wp_send_json( array('success'=> $this->wpa_db->update_event_category( $_POST ) ) );
			die();
		}

		/**
		 * [AJAX] Deletes an event category by ID
		 */
		function delete_event_category() {
			wp_send_json( array( 'success'=> $this->wpa_db->delete_event_category( $_POST['id'] ) ) );
			die();
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
			if( isset( $_POST['recordsMode'] ) ) {
				$wpa_records = new WP_Athletics_Records( $this->wpa_db );
				$wpa_records->recreate_records_pages( $_POST['recordsMode'] );
			}
			if( isset( $_POST['clubName'] ) ) {
				update_option('wp-athletics_club_name', $_POST['clubName'] );
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
		 * Generates a page for the event category settings
		 */
		function wpa_event_category_settings() {
			require 'admin/event-category-settings.php';
		}

		/**
		 * Generates a page for the age category settings
		 */
		function wpa_age_category_settings() {
			require 'admin/age-category-settings.php';
		}

		/**
		 * Generates a page for result management
		 */
		function wpa_manage_results() {
			require 'admin/manage-results.php';
		}

		/**
		 * Generates a page for adding results
		 */
		function wpa_add_results() {
			require 'admin/add-results.php';
		}

		/**
		 * Generates a page for result management
		 */
		function wpa_manage_events() {
			require 'admin/manage-events.php';
		}

		/**
		 * Generates a page for printing rankings
		 */
		function wpa_print_rankings() {
			require 'admin/print-rankings.php';
		}

		/**
		 * Generates a general admin settings page
		 */
		function wpa_settings() {
			require 'admin/general-settings.php';
		}
	}
}
?>