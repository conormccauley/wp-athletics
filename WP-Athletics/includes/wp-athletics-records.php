<?php

/**
 * Class for mananaging the querying and generation of club records
 */

if(!class_exists('WP_Athletics_Records')) {

	class WP_Athletics_Records extends WPA_Base {

		/**
		 * default constructor
		 */
		public function __construct($db) {
			parent::__construct($db);
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

			// custom script
			wp_register_script( 'wpa-records', WPA_PLUGIN_URL . '/resources/scripts/wpa-records.js' );
			wp_enqueue_script( 'wpa-records' );

			global $current_user;
			$nonce = wp_create_nonce( WPA_NONCE );
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