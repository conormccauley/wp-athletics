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
		 * Construct the plugin object
		 **/
		public function __construct() {
			$this->setup();

			// admin
			if( is_admin() ) {
				add_action( 'admin_menu', array( $this, 'do_admin' ) );
			}
			else {
				add_action('wp', array( $this, 'is_wpa_page' ) );
			}

			global $wpa_lang;
			global $wpa_settings;

			// retrieve language properties
			$lang = strtolower(get_option( 'wp-athletics_language', 'en') );
			$wpa_lang = require 'includes/lang/wp-athletics-' . $lang . '.php';

			// load settings file
			$wpa_settings = require 'includes/wp-athletics-settings.php';

			// create objects
			$this->wpa_db = new WP_Athletics_DB();
			$this->wpa_records = new WP_Athletics_Records( $this->wpa_db );
			$this->wpa_my_results = new WP_Athletics_My_Results( $this->wpa_db );

			// installation and uninstallation hooks
			register_activation_hook( __FILE__, array ( $this, 'activate') );
			register_deactivation_hook( __FILE__, array ( $this, 'deactivate') );
			register_uninstall_hook( __FILE__, array( 'WP_Athletics', 'uninstall' ) );

			// short codes
			add_shortcode( 'wpa-records', array( $this, 'do_records' ) );
			add_shortcode( 'wpa-my-results', array( $this, 'do_my_results' ) );

			add_action('init', array( $this , 'register_assets') );
		}

		/**
		 * Checks if the current page is a WPA page and filters the content using the relevant shortcode function call
		 */
		function is_wpa_page() {
			global $post;

			// my results
			if( $post->ID == get_option('wp-athletics_my_results_page_id') ) {
				$filter = array( $this->wpa_my_results, 'my_results_content_filter' );
			}

			// records (both)
			if( $post->ID == get_option('wp-athletics_records_page_id') ) {
				$filter = array( $this->wpa_records, 'records' );
			}

			// records (male)
			if( $post->ID == get_option('wp-athletics_records_male_page_id') ) {
				$filter = array( $this->wpa_records, 'records_male' );
			}

			// records (female)
			if( $post->ID == get_option('wp-athletics_records_female_page_id') ) {
				$filter = array( $this->wpa_records, 'records_female' );
			}

			if( isset ( $filter ) ) {
				$this->enqueue_scripts();
				add_filter('the_content', $filter, 1);
			}
 		}

		/**
		 * Ensure the correct (bundled) jquery build is included to avoid conflicts
		 */
		public function enqueue_scripts() {
			wpa_log('************** WRITING WPA SCRIPTS');

			// ensure the bundled wordpress jQuery/UI is always used
			wp_deregister_script('jquery');
			wp_deregister_script('jquery-ui');

			wp_register_script('jquery', '/wp-includes/js/jquery/jquery.js');
			wp_enqueue_script( 'jquery' );
		}

		/**
		 * registers scripts and stylesheets
		 */
		public function register_assets() {
			$theme = strtolower(get_option( 'wp-athletics_theme', 'default') );

			// scripts
			wp_register_script( 'wpa-functions', WPA_PLUGIN_URL . '/resources/scripts/wpa-functions.js' );
			wp_register_script( 'wpa-custom', WPA_PLUGIN_URL . '/resources/scripts/wpa-custom.js' );
			wp_register_script( 'wpa-ajax', WPA_PLUGIN_URL . '/resources/scripts/wpa-ajax.js' );
			wp_register_script( 'wpa-my-results', WPA_PLUGIN_URL . '/resources/scripts/wpa-my-results.js' );
			wp_register_script( 'wpa-records', WPA_PLUGIN_URL . '/resources/scripts/wpa-records.js' );
			wp_register_script( 'datatables', WPA_PLUGIN_URL . '/resources/scripts/jquery.dataTables.min.js', array('jquery'), '1.0', true );

			// styles
			wp_register_style( 'datatables', WPA_PLUGIN_URL . '/resources/css/jquery.dataTables.css' );
			wp_register_style( 'wpa_style', WPA_PLUGIN_URL . '/resources/css/wpa-style.css' );
			wp_register_style( 'wpa_theme_jqueryui', WPA_PLUGIN_URL . '/resources/css/themes/' . $theme . '/jquery-ui.css' );
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

			// store plugin version number
			add_option('wp-athletics_version', WPA_VERSION_NUM );
		}

		/**
		 * Generates admin menu options
		 */
		public function do_admin() {
			$this->wpa_admin = new WP_Athletics_Admin( $this->wpa_db );
			add_filter('plugin_action_links', array( $this->wpa_admin, 'action_links' ), 10, 2 );
			$this->wpa_admin->admin_menu();
		}

		/**
		 * Shortcode action for the records page
		 */
		public function do_records( $atts ) {
			$this->wpa_records->records( $atts );
		}

		/**
		 * Shortcode action for the my results page
		 */
		public function do_my_results( $atts) {
			$this->wpa_my_results->my_results( $atts );
		}

		/**
		 * Activate the plugin
		 **/
		public function activate() {
			global $wpa_settings;

			if(!$this-> is_installed() ) {

				// load settings file
				$wpa_settings = require 'includes/wp-athletics-settings.php';

				$this->wpa_db = new WP_Athletics_DB();

				// install database and create/update tables
				$this->wpa_db->create_db();

				// create a "my results" page
				$this->wpa_my_results->create_page();

				// create a records pages
				$this->wpa_records->create_pages();
			}
		}

		/**
		 * Determines if the plugin is installed
		 */
		public function is_installed() {
			$installed_ver = get_option( 'wp-athletics_db_version', 'not_installed');
			return $installed_ver == WPA_DB_VERSION;
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

			// remove pages we created
			$pages_created = get_option( 'wp-athletics_pages_created' );
			foreach( $pages_created as $page_id ) {
				wpa_log('deleting page ' . $page_id);
				wp_delete_post( $page_id, true );
			}

			// remove tables and meta data
			$wpa_db = new WP_Athletics_DB();
			$wpa_db->uninstall_wpa();

		}
	}

	// WP Athletics begin here!
	if(class_exists('WP_Athletics')) {
		// instantiate the plugin class & call constructor
		$wp_athletics = new WP_Athletics();
	}
}
?>