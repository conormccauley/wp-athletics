<?php

/**
 * Class for mananaging the querying and generation of club records
 */

if(!class_exists('WP_Athletics_Records')) {

	class WP_Athletics_Records extends WPA_Base {

		public $nonce = 'wpathleticsrecords';

		/**
		 * default constructor
		 */
		public function __construct($db) {
			parent::__construct($db);
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
		 * Generates the records for male gender
		 */
		public function records_male() {
			$this -> records_by_gender( 'M' );
		}

		/**
		 * Generates the records for female gender
		 */
		public function records_female() {
			$this -> records_by_gender( 'F' );
		}

		/**
		 * Generates the records for a specific gender
		 */
		protected function records_by_gender( $gender ) {
			$this -> records( array( 'gender' => $gender ) );
		}

		/**
		 * Generates the records pages
		 */
		public function create_pages() {
			$pages_created = get_option( 'wp-athletics_pages_created', array() );

			// generate pages for male and female records
			$female_page_id = $this->generate_page( $this->get_property('records_female_page_title') );
			$male_page_id = $this->generate_page( $this->get_property('records_male_page_title') );

			// by default the records page for both genders is disabled
			$page_id = $this->generate_page( $this->get_property('records_page_title'), 'draft' );

			// store the page ids as options
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

			if( $page_id) {
				add_option('wp-athletics_records_page_id', $page_id, '', 'yes');
				wpa_log('Generic Records page created!');
				array_push( $pages_created, $page_id );
			}

			// set option to determine which mode we are using (separate or single pages for records)
			add_option('wp-athletics_records_mode', 'separate', '', 'yes');

			// update option of which pages we created (so they can be deleted when plugin uninstalled)
			update_option( 'wp-athletics_pages_created', $pages_created );
		}

		/**
		 * Generates a 'records' page when the shortcode [wpa-records] is used
		 */
		public function records( $atts ) {

			$this->enqueue_scripts_and_styles();

			$displayGenderOption = true;
			$gender = 'M';

			// check for a gender attribute
			if(isset( $atts['gender'] ) ) {
				$genderStr = strtoupper( $atts['gender'] );
				if($genderStr == 'M' || $genderStr == 'F') {
					$displayGenderOption = false;
					$gender = $genderStr;
				}
			}

			global $current_user;
			$nonce = wp_create_nonce( $this->nonce );
			?>
				<script type='text/javascript'>
					jQuery(document).ready(function() {

						// set up ajax and retrieve my results
						WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo $current_user->ID; ?>', function() {

							WPA.Records.gender = '<?php echo $gender; ?>';

							// create tabs for each age category
							jQuery.each(WPA.globals.ageCategories, function(cat, item) {
								jQuery('#tabs ul').append('<li category="' + cat + '"><a href="#tab-' + cat + '">' + item.name + '</a></li>');
								jQuery('#tabs').append('<div id="tab-' + cat + '">' + WPA.Records.createTableHTML(cat) + '</div>');
								WPA.Records.createDataTable(cat);
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
								}
							});

							// create top 10 table
							WPA.Records.createTop10DataTable();

							// setup top 10 dialog
							WPA.Records.top10Dialog = jQuery("#top10Dialog").dialog({
								autoOpen: false,
								resizable: false,
								modal: true,
								width: 'auto',
								height: 'auto',
								resizable: false,
								maxHeight: 600
							});

							// setup filters
							WPA.setupFilters(null, null, WPA.Records.getPersonalBests);

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
							if ( $displayGenderOption ) {
							?>
							<select id="filterGender">
								<option value="M"><?php echo $this->get_property('gender_M'); ?></option>
								<option value="F"><?php echo $this->get_property('gender_F'); ?></option>
							</select>
							<?php
							}
							?>

							<select id="filterPeriod">
								<option value="all" selected="selected"><?php echo $this->get_property('filter_period_option_all'); ?></option>
								<option value="this_month"><?php echo $this->get_property('filter_period_option_this_month'); ?></option>
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

					<?php $this->create_common_dialogs(); ?>

				</div>

				<!-- TOP 10 DIALOG -->
				<div style="display:none" id="top10Dialog">
					<table width="100%" class="display ui-state-default" id="table-top-10">
						<thead>
							<tr>
								<th></th>
								<th></th>
								<th>#</th>
								<th><?php echo $this->get_property('column_athlete_name') ?></th>
								<th><?php echo $this->get_property('column_time') ?></th>
								<th><?php echo $this->get_property('column_event_name') ?></th>
								<th><?php echo $this->get_property('column_event_location') ?></th>
								<th><?php echo $this->get_property('column_event_type') ?></th>
								<th><?php echo $this->get_property('column_event_date') ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>

			<?php
		}

	}
}
?>