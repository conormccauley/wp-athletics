<?php
if ( $this->has_permission_to_manage() ) {
	global $current_user;
	$nonce = wp_create_nonce( $this->nonce );

	if( isset( $_GET['print'] ) ) {
?>
		<div id="wpa-print-rankings">

			<style>
				table.dataTable tr.odd { background-color: white; }
				table.dataTable tr.even { background-color: white; }

				table.dataTable tr { border-bottom: 1px solid gray; }
				table.dataTable td { border-right: 1px solid gray; }
				table.dataTable { border: 1px solid gray; }
				table.display th { text-align: left; }
			</style>

			<script type="text/javascript">
			jQuery(document).ready(function() {
				WPA.isAdminScreen = true;

				jQuery('#adminmenuwrap,#adminmenuback,#wpfooter,#wpadminbar').hide();
				jQuery('#wpcontent').css('marginLeft', '0px');
				jQuery('.wp-toolbar').css('paddingTop', '0px');

				var gender = '<?php echo $_GET['gender']; ?>';
				var event = '<?php echo $_GET['event']; ?>';
				var type = '<?php echo $_GET['type']; ?>';
				var age = '<?php echo $_GET['age']; ?>';
				var period = '<?php echo $_GET['period']; ?>';

				var today = jQuery.datepicker.formatDate('dd M yy', new Date())

				jQuery('#wpaPrintHeaderRight div').html(today);

				// set up ajax
				WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo WPA_PLUGIN_URL; ?>', '<?php echo $current_user->ID; ?>',  function() {
					jQuery('#wpaPrintHeaderMain').html(((WPA.getEventName(event) + ' ' + WPA.getAgeCategoryDescription(age)) + ' ' + WPA.getProperty('print_rankings_text').toUpperCase() + ' - ' + WPA.getPeriodDescription(period)).toUpperCase());
					jQuery('#wpaPrintHeaderSub').html('(' + WPA.getGenderDescription(gender) + ' - ' + WPA.getEventSubTypeDescription(type) + ')');

					WPA.rankingsTable = jQuery('#table-rankings').dataTable({
						"sDom": 'rt',
						"bProcessing": true,
						"oLanguage": {
							"sEmptyTable": WPA.getProperty('table_no_results'),
							"sProcessing": WPA.getProperty('table_loading_message')
						},
						"bPaginate": false,
						"aaSorting": [[ 1, "asc" ]],
						"aoColumns": [{
							"mData": "time_format",
							"bVisible": false
						},{
							"mData": "time",
							"bVisible": false
						},{
							"mData": "rank",
							"sWidth": "20px",
							"bSortable": false,
							"sClass": "datatable-center"
						},{
							"mData": "athlete_name",
							"sClass": "datatable-bold",
							"bSortable": false
						},{
							"mData": "time",
							"sClass": "datatable-bold",
							"mRender": WPA.renderTimeColumn,
							"bSortable": false
						},{
							"mData": "time",
							"mRender": WPA.renderPaceMilesColumn,
							"bSortable": false
						},{
							"mData": "event_name",
							"bSortable": false
						},{
							"mData": "event_location",
							"bSortable": false
						},{
							"mData": "event_sub_type_id",
							"mRender" : WPA.renderEventTypeColumn,
							"bSortable": false
						},{
							"mData": "event_date",
							"bSortable": false
						}]
					});

					// perform ajax call to get rankings
					WPA.getRankingsPersonalBests({
						ageCategory: age,
						gender: gender,
						eventSubTypeId: type,
						eventDate: period,
						eventCategoryId: event,
						rankingDisplay: 'best-athlete-result'
					}, false, function() {
						window.print();
					});
				});
			});
			</script>

			<div id="wpaPrintHeader">
				<div id="wpaPrintHeaderLeft">
					<p><?php echo $this->get_property('print_rankings_enter_results_text'); ?></p>
					<p id="wpaPrintHeaderUrl"><?php echo get_bloginfo('wpurl') ?></p>
				</div>
				<div id="wpaPrintHeaderCenter">
					<div id="wpaPrintHeaderClubName"><?php echo get_option( 'wp-athletics_club_name', 'Your club name' ) ?></div>
					<div id="wpaPrintHeaderMain"></div>
					<div id="wpaPrintHeaderSub"></div>
				</div>
				<div id="wpaPrintHeaderRight">
					<div></div>
				</div>
				<br style="clear:both;"/>
			</div>

			<div>
				<table width="100%" class="display ui-state-default" id="table-rankings">
					<thead>
						<tr>
							<th></th>
							<th></th>
							<th>#</th>
							<th><?php echo $this->get_property('column_athlete_name') ?></th>
							<th><?php echo $this->get_property('column_time') ?></th>
							<th><?php echo $this->get_property('column_pace') ?></th>
							<th><?php echo $this->get_property('column_event_name') ?></th>
							<th><?php echo $this->get_property('column_event_location') ?></th>
							<th><?php echo $this->get_property('column_event_type') ?></th>
							<th><?php echo $this->get_property('column_event_date') ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>

			<div id="wpaPrintFooter">
				<div><?php echo $this->get_property('print_rankings_powered_by'); ?></div>
			</div>

		</div>
<?php
	}
	else {
?>
	<script type="text/javascript">

	jQuery(document).ready(function() {
		WPA.isAdminScreen = true;

		// set up ajax
		WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo WPA_PLUGIN_URL; ?>', '<?php echo $current_user->ID; ?>',  function() {

			WPA.Admin.printUrl = '<?php echo get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wp-athletics-print-rankings' ?>';

			// common setup function
			WPA.setupCommon();

			// setup common filters
			WPA.setupFilters();

			// setup gender filter
			jQuery("#filterGender").combobox({
				selectClass: 'filter-highlight',
				defaultValue: 'B'
			});

			jQuery('#generateRankingsButton button').button({
				icons: {
	              primary: 'ui-icon-print'
	            }
			}).click(function() {

				var period = jQuery('#filterPeriod').val();
				var type = jQuery('#filterType').val();
				var age = jQuery('#filterAge').val();
				var event = jQuery('#filterEvent').val();
				var gender = jQuery('#filterGender').val();

				window.open(WPA.Admin.printUrl + '&print=true&period=' + period + '&type=' + type + '&age=' + age + '&event=' + event + '&gender=' + gender,'_blank')
			});

		});
	});

	</script>

	<div>
		<div class="wpa-admin-title">
			<h2><?php echo $this->get_property('admin_print_rankings_title'); ?></h2>
		</div>
		<br style="clear:both"/>
		<p><?php echo $this->get_property('admin_print_rankings_description'); ?></p>
	</div>

	<div class="wpa">

		<div class="wpa-menu">

			<!-- FILTERS -->
			<div class="wpa-filters ui-corner-all">
				<div class="filter-ignore-for-pb">
					<select id="filterEvent">
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

				<select id="filterGender">
					<option value="B"><?php echo $this->get_property('gender_B'); ?></option>
					<option value="M"><?php echo $this->get_property('gender_M'); ?></option>
					<option value="F"><?php echo $this->get_property('gender_F'); ?></option>
				</select>

				<select id="filterAge">
					<option value="all" selected="selected"><?php echo $this->get_property('filter_age_option_all'); ?></option>
				</select>

				<div id="generateRankingsButton">
					<a href="<?php get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wp-athletics-settings' ?>" target="new" style="display:none;"></a>
					<button>Print</button>
				</div>

			</div>

			<br style="clear:both"/>
		</div>

	</div>

<?php
	}
}
?>