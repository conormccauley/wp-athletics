<?php

/**
 * Class for mananaging the querying and generation of club records
 */

if(!class_exists('WP_Athletics_Records')) {

	class WP_Athletics_Records extends WPA_Base {

		/**
		 * default constructor
		 */
		public function __construct( $db ) {
			parent::__construct( $db );
		}

		/**
		 * Enqueues scripts and styles
		 */
		public function enqueue_scripts_and_styles() {
			// common scripts and styles
			$this->enqueue_common_scripts_and_styles();
			wp_enqueue_script( 'wpa-records' );
		}

		/**
		 * Called when the records mode has been changed to 'separate' or 'combined'
		 */
		public function recreate_records_pages( $new_mode ) {
			$current_mode = get_option( 'wp-athletics_records_mode' );

			// only recreate the pages if the mode has been changed (obviously ;)
			if( $current_mode && $current_mode != $new_mode ) {
				$this->reset_created_pages( $current_mode );
				$this->create_pages( $new_mode );
			}
		}

		/**
		 * Removes any created records pages
		 */
		public function reset_created_pages( $mode = 'separate' ) {
			$pages_created = get_option( 'wp-athletics_pages_created', array() );

			wpa_log('pages created is ' . $pages_created);

			$remove_pages;

			if( $mode == 'separate' ) {
				$female_id = get_option('wp-athletics_records_female_page_id');
				$male_id = get_option('wp-athletics_records_male_page_id');
				if( $male_id && $female_id ) {
					wp_delete_post( $female_id, true );
					wp_delete_post( $male_id, true );
					delete_option('wp-athletics_records_female_page_id');
					delete_option('wp-athletics_records_male_page_id');
					$remove_pages = array( $female_id, $male_id );
				}
			}
			else if( $mode == 'combined' ) {
				$page_id = get_option('wp-athletics_records_page_id');
				if( $page_id ) {
					wp_delete_post( $page_id, true );
					delete_option('wp-athletics_records_page_id');
					$remove_pages = array( $page_id );
				}
			}

			// update the created pages option
			if( $remove_pages ) {
				$new_pages_created = array_diff( $pages_created, $remove_pages );
				wpa_log('pages created is now ' . $new_pages_created);
				update_option( 'wp-athletics_pages_created', $new_pages_created );
			}
		}

		/**
		 * Generates the records pages (defaults to 'separate' mode, i.e male and female pages)
		 */
		public function create_pages( $mode = 'separate' ) {
			$pages_created = get_option( 'wp-athletics_pages_created', array() );

			// generate pages for male and female records
			if( $mode == 'separate' ) {
				$female_page_id = $this->generate_page( $this->get_property('records_female_page_title') );
				$male_page_id = $this->generate_page( $this->get_property('records_male_page_title') );

				if( $female_page_id ) {
					add_option('wp-athletics_records_female_page_id', $female_page_id, '', 'yes');
					wpa_log('Female Records page created!');
					array_push( $pages_created, $female_page_id );
				}

				if( $male_page_id) {
					add_option('wp-athletics_records_male_page_id', $male_page_id, '', 'yes');
					wpa_log('Male Records page created!');
					array_push( $pages_created, $male_page_id );
				}
			}
			else if( $mode == 'combined' ) {
				// by default the records page for both genders is disabled
				$page_id = $this->generate_page( $this->get_property('records_page_title') );

				if( $page_id) {
					add_option('wp-athletics_records_page_id', $page_id, '', 'yes');
					wpa_log('Generic Records page created!');
					array_push( $pages_created, $page_id );
				}
			}

			// set option to determine which mode we are using (separate or combined pages for records)
			update_option('wp-athletics_records_mode', $mode );

			// update option of which pages we created (so they can be deleted when plugin uninstalled)
			update_option( 'wp-athletics_pages_created', $pages_created );
		}

		/**
		 * For content filtering, ensures the content is only displayed in the WP loop
		 */
		public function records_content_filter( $content ) {
			if( !in_the_loop() ) return $content;
			$this->records();
		}

		/**
		 * Generates a 'records' page when the shortcode [wpa-records] is used
		 */
		public function records( $atts = null ) {
			global $records_gender;

			$this->enqueue_scripts_and_styles();

			// display the gender combo box? this will remain false unless a gender has been specified either via global or an attribute
			$display_gender_option = false;

			// check for a gender attribute
			if(isset( $atts ) && isset( $atts['gender'] ) ) {
				$genderStr = strtoupper( $atts['gender'] );
				if( $genderStr == 'M' || $genderStr == 'F') {
					$records_gender = $genderStr;
				}
			}
			// check is the records_gender global set
			else if( false == isset( $records_gender ) ) {
				// it's not set, display the gender combo and default to M
				$records_gender = 'B';
				$display_gender_option = true;
			}

			global $current_user;
			$nonce = wp_create_nonce( $this->nonce );
			?>
				<script type='text/javascript'>
					jQuery(document).ready(function() {

						// set up ajax and retrieve my results
						WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo WPA_PLUGIN_URL; ?>', '<?php echo $current_user ? $current_user->ID : -1 ?>', function() {

							WPA.Records.gender = '<?php echo $records_gender; ?>';

							// create tab for all age categories
							jQuery('#tabs ul').append('<li category="all"><a title="' + WPA.getProperty('all_age_classes') + '" href="#tab-all">' + WPA.getProperty('all_age_classes_label') + '</a></li>');
							jQuery('#tabs').append('<div id="tab-all">' + WPA.Records.createTableHTML('all') + '</div>');
							WPA.Records.createDataTable('all', true);

							// create tabs for each age category
							jQuery.each(WPA.globals.ageCategories, function(cat, item) {
								jQuery('#tabs ul').append('<li category="' + cat + '"><a title="' + item.from + ' - ' + item.to + '" href="#tab-' + cat + '">' + item.name + '</a></li>');
								jQuery('#tabs').append('<div id="tab-' + cat + '">' + WPA.Records.createTableHTML(cat) + '</div>');
								WPA.Records.createDataTable(cat, false);
							});

							// set up tabs
							jQuery('#tabs').tabs({
								activate: function( event, ui ) {
									WPA.Records.currentCategory = ui.newTab.attr('category');
									WPA.Records.getPersonalBests();
								},
								create: function( event, ui ) {
									WPA.Records.currentCategory = ui.tab.attr('category');
									WPA.Records.getPersonalBests();
								}
							});

							// filter gender
							jQuery("#filterGender").combobox({
								select: function(event, ui) {

									WPA.Records.gender = ui.item.value;
									WPA.Records.getPersonalBests();
								},
								selectClass: 'filter-highlight',
								defaultValue: 'B'
							});

							// setup filters
							WPA.setupFilters(null, null, WPA.Records.getPersonalBests);

							// setup results dialog
							WPA.setupEditResultDialog(WPA.loadEventResults);

							// common setup function
						    WPA.setupCommon();
						});
					});
				</script>

				<div class="wpa">

					<div class="wpa-menu">

						<!-- FILTERS -->
						<div class="wpa-filters wpa-filter-records ui-corner-all" style="width:600px">

							<?php
							if ( $display_gender_option ) {
							?>
							<select id="filterGender">
								<option value="B"><?php echo $this->get_property('gender_B'); ?></option>
								<option value="M"><?php echo $this->get_property('gender_M'); ?></option>
								<option value="F"><?php echo $this->get_property('gender_F'); ?></option>
							</select>
							<?php
							}
							?>

							<select id="filterPeriod">
								<option value="all" selected="selected"><?php echo $this->get_property('filter_period_option_all'); ?></option>
								<!--
								<option value="this_month"><?php echo $this->get_property('filter_period_option_this_month'); ?></option>
								-->
								<option value="this_year"><?php echo $this->get_property('filter_period_option_this_year'); ?></option>
							</select>

							<select id="filterType">
								<option value="all" selected="selected"><?php echo $this->get_property('filter_type_option_all'); ?></option>
							</select>
						</div>

						<!-- EVENT / ATHLETE SEARCH -->
						<div class="wpa-ac-search wpa-records-search">
							<span class="wpa-search-image"></span>
							<input type="text" class="ui-corner-all ui-widget ui-state-default wpa-search wpa-search-disabled" default-text="<?php echo $this->get_property('wpa_search_text'); ?>" value="" id="wpa-search" class="text ui-widget-content ui-corner-all" />
						</div>

						<br style="clear:both"/>
					</div>

					<!-- MY RESULTS TABS -->
					<div class="wpa-tabs" id="tabs">
					  <ul>
					  </ul>
					</div>

					<?php $this->create_edit_result_dialog(); ?>

					<?php $this->create_common_dialogs(); ?>

				</div>



			<?php
		}

	}
}
?>