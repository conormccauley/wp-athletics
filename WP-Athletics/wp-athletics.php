<?php
/*
Plugin Name: WP Athletics
Plugin URI: http://www.conormccauley.me/wp-athletics
Description: Track individual athletic results and view records and stats for the entire club
Author: Conor McCauley
Version: 1.0.0
Author URI: http://www.conormccauley.me
*/

include_once 'includes/wp-athletics-functions.php';
include_once 'includes/wp-athletics-db.php';
include_once 'includes/wp-athletics-my-results.php';
include_once 'includes/wp-athletics-records.php';
include_once 'includes/wp-athletics-admin-settings.php';

global $wpa_lang;
global $wpa_settings;

// define a plugin class
if(!class_exists('WP_Athletics')) {

	class WP_Athletics {

		public $wpa_admin;
		public $wpa_records;
		public $wpa_my_results;
		public $wpa_db;

		protected static $instance;

		public static function init() {
			is_null( self::$instance ) AND self::$instance = new self;
			return self::$instance;
		}

		/**
		 * Creates plugin globals and manages version number
		 */
		public function setup() {
			// define global variables
			if (!defined('WPA_THEME_DIR') )
				define('WPA_THEME_DIR', ABSPATH . 'wp-content/themes/' . get_template() );

			if (!defined('WPA_PLUGIN_NAME') )
				define('WPA_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__) ), '/') );

			if (!defined('WPA_PLUGIN_DIR') )
				define('WPA_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . WPA_PLUGIN_NAME);

			if (!defined('WPA_PLUGIN_URL') )
				define('WPA_PLUGIN_URL', WP_PLUGIN_URL . '/' . WPA_PLUGIN_NAME);

			if (!defined('WPA_PLUGIN_BASENAME') )
				define('WPA_PLUGIN_BASENAME', plugin_basename(__FILE__) );

			if(!defined('WPA_DATE_FORMAT') )
				define('WPA_DATE_FORMAT', '%d %b %Y');

			if (!defined('WPA_VERSION_NUM') )
				define('WPA_VERSION_NUM', '1.0.0');

			if (!defined('WPA_DB_VERSION') )
				define('WPA_DB_VERSION', '1.0.0');

			if (!defined('WPA_NONCE') )
				define('WPA_NONCE', 'wpaathletics2013');

			// store plugin version number
			add_option('wp-athletics_version', WPA_VERSION_NUM );

			wpa_log('Theme Directory: ' . WPA_THEME_DIR );
			wpa_log('Plugin Name: ' . WPA_PLUGIN_NAME );
			wpa_log('Plugin Directory directory: ' . WPA_PLUGIN_DIR );
			wpa_log('Plugin URL: ' . WPA_PLUGIN_URL );
			wpa_log('****************');
		}

		/**
		 * Construct the plugin object
		 **/
		public function __construct() {
			global $wpa_lang;
			global $wpa_settings;

			$this->setup();

			wp_enqueue_script( 'jquery' );

			// retrieve language properties
			$lang = strtolower(get_option( 'wp-athletics_language', 'en') );
			$wpa_lang = require 'includes/lang/wp-athletics-' . $lang . '.php';

			// load settings file
			$wpa_settings = require 'includes/wp-athletics-settings.php';

			// create objects
			$this->wpa_db = new WP_Athletics_DB();
			$this->wpa_admin = new WP_Athletics_Admin( $this->wpa_db );
			$this->wpa_records = new WP_Athletics_Records( $this->wpa_db );
			$this->wpa_my_results = new WP_Athletics_My_Results( $this->wpa_db );

			// installation and uninstallation hooks
			register_activation_hook( __FILE__, array ( $this, 'activate') );
			register_deactivation_hook( __FILE__, array ( $this, 'deactivate') );
			register_uninstall_hook( __FILE__, array( 'WP_Athletics', 'uninstall' ) );

			// short codes
			add_shortcode( 'wpa-records', array( $this->wpa_records, 'records' ) );
			add_shortcode( 'wpa-my-results', array( $this->wpa_my_results, 'my_results' ) );

			// actions
			add_action( 'admin_menu', array( $this->wpa_admin, 'admin_menu' ) );
			add_action( 'init', array( $this, 'create_user_meta_data' ) );

			// filters
			add_filter('plugin_action_links', array( $this->wpa_admin, 'action_links' ), 10, 2 );
		}

		/**
		 * Activate the plugin
		 **/
		public function activate() {
			global $wpa_settings;

			// load settings file
			$wpa_settings = require 'includes/wp-athletics-settings.php';

			// install database and create/update tables
			$this->wpa_db->create_db();
		}

		/**
		 * Deactivate the plugin
		 **/
		public function deactivate() {
			// Do nothing
		}

		/**
		 * Uninstalls the plugin
		 */
		public static function uninstall() {
			if ( ! current_user_can( 'activate_plugins' ) )
				return;

			wpa_log('Uninstalling WPA Athletics...');
			$wpa_db = new WP_Athletics_DB();
			$wpa_db->uninstall_wpa();
		}

		public function register_scripts() {
			// register scripts and styles
			if( !is_admin() ) {
				wp_register_script( 'datatables', WPA_PLUGIN_DIR . '/resources/scripts/jquery.dataTables.min.js', array('jquery') );
			}
		}

		/**
		 * Creates additional user meta data required for the plugin
		 */
		public function create_user_meta_data() {
			global $current_user;

			if(!get_user_meta( $current_user->ID, 'wpa_athlete_name', true) ) {
				add_user_meta( $current_user->ID, 'wp-athletics_age_category', '', true );
				add_user_meta( $current_user->ID, 'wp-athletics_fave_event_category', '', true );
			}
		}
	}

	if(class_exists('WP_Athletics')) {
		wpa_log('*** Initializing WP-Athletics Plugin ***');

		// instantiate the plugin class & call constructor
		$wp_athletics = new WP_Athletics();
	}
}
?>