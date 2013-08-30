<?php
if ( $this->has_permission_to_manage() ) {
	global $current_user;
	$nonce = wp_create_nonce( $this->nonce );

?>
	<script type="text/javascript">

	WPA.Admin.doEventNameFilter = function() {
		var defaultText = jQuery('#filterEventName').attr('default-text');
		var val = jQuery('#filterEventName').val();
		if(val != '' && defaultText != val) {
			WPA.filterEventName = val;
			WPA.Admin.resultsTable.fnFilter( val, 5 );
		}
		else {
			WPA.filterEventName = null;
			WPA.Admin.resultsTable.fnFilter( '', 5 );
		}
	}

	WPA.Admin.resetSelectValuesAndReload = function() {
		WPA.Admin.resetSelectValues();
		WPA.Admin.reloadResults();
	}

	WPA.Admin.doAthleteNameFilter = function() {
		var defaultText = jQuery('#filterAthleteName').attr('default-text');
		var val = jQuery('#filterAthleteName').val();
		if(val != '' && defaultText != val) {
			WPA.filterEventName = val;
			WPA.Admin.resultsTable.fnFilter( val, 2 );
		}
		else {
			WPA.filterEventName = null;
			WPA.Admin.resultsTable.fnFilter( '', 2 );
		}
	}

	WPA.Admin.reloadResults = function() {
		WPA.Admin.resultsTable.fnDraw();
	}

	WPA.Admin.deleteResult = function(id) {
		WPA.Admin.deleteResults([id]);
	}

	WPA.Admin.reassignResults = function() {
		WPA.Admin.resultReassignId = '';
		jQuery('.input-cancel').hide();
		jQuery('#reassignToUser').removeAttr('readonly').val('').blur();

		WPA.Admin.reassignResultsDialog = jQuery('#reassign-results-dialog').dialog({
			title: WPA.getProperty('reassign_results_title'),
			autoOpen: true,
			resizable: false,
			modal: true,
			height: 'auto',
			width: 400,
			buttons: [{
		    	text: WPA.getProperty('submit'),
		    	click: function() {
			    	if(WPA.Admin.resultReassignId != '') {
						WPA.Ajax.reassignResults(WPA.Admin.selectedRecords, WPA.Admin.resultReassignId, function(result) {
							if(result && result.success) {
								jQuery('#reassign-results-dialog').dialog("close");
								WPA.Admin.resetSelectValuesAndReload();
							}
							else {
								WPA.alertError(WPA.getProperty('reassign_results_error'));
							}
						});
			    	}
			    	else {
				    	WPA.alertError(WPA.getProperty('reassign_results_no_user_selected'));
			    	}
			    }
		    },{
				text: WPA.getProperty('cancel'),
		    	click: function() {
		          jQuery( this ).dialog("close");
		    	}
			}]
		});
	}

	WPA.Admin.deleteResults = function(ids) {
		WPA.Admin.deleteIds = ids;

		jQuery('#result-count').html(ids.length);

		WPA.Admin.deleteResultsDialog = jQuery('#delete-results-confirm-dialog').dialog({
			title: WPA.getProperty('delete_results_confirm_title'),
			autoOpen: true,
			resizable: false,
			modal: true,
			height: 'auto',
			width: 'auto',
			buttons: [{
		    	text: WPA.getProperty('delete'),
		    	click: function() {
					WPA.Ajax.deleteResults(WPA.Admin.deleteIds, function(result) {
						if(result.success) {
							jQuery('#delete-results-confirm-dialog').dialog("close");
							WPA.Admin.resetSelectValuesAndReload();
						}
					});
			    }
		    },{
				text: WPA.getProperty('cancel'),
		    	click: function() {
		          jQuery( this ).dialog("close");
		    	}
			}]
		});
	}

	WPA.Admin.createResultsTable = function() {
		WPA.Admin.resultsTable = jQuery('#all-results-table').dataTable(WPA.createTableConfig({
			"bServerSide": true,
			"sScrollX": "100%",
			"bScrollCollapse": true,
			"sDom": 'rt<"bottom fg-toolbar ui-toolbar ui-widget-header ui-corner-bl ui-corner-br ui-helper-clearfix"lip>',
			"sAjaxSource": WPA.Ajax.url,
			"iDisplayLength": 25,
			"sServerMethod": "POST",
			"fnDrawCallback": function() {
				jQuery('.datatable-checkbox input').change(WPA.Admin.selectRecordToggleWrapper);
			},
			"fnServerParams": function ( aoData ) {
			    aoData.push(
			    	{name : 'action', value : 'wpa_get_all_results' },
			    	{name : 'security', value : WPA.Ajax.nonce }
			    );
			},
			"aaSorting": [[ 3, "desc" ]],
			"aoColumns": [{
				"mData": "time_format",
				"bVisible": false
			},{
				"mData": "id",
				"sWidth": "60px",
				"sTitle": "<input title='" + WPA.getProperty('select_unselect_all_tooltip') + "' id='datatable-select-all' type='checkbox'/>",
				"mRender": WPA.renderAdminDeleteEditResultColumn,
				"bSortable": false
			},{
				"mData": "athlete_name",
				"mRender" : WPA.renderProfileLinkColumn
			},{
				"mData": "result_date"
			},{
				"mData": "event_date"
			},{
				"mData": "event_name",
				"mRender" : WPA.renderEditEventLinkColumn
			},{
				"mData": "event_location",
				"bVisible": false,
				"mRender": WPA.renderEventLocationColumn
			},{
				"mData": "event_sub_type_id",
				"mRender" : WPA.renderEventTypeColumn
			},{
				"mData": "category"
			},{
				"mData": "age_category",
				"mRender" : WPA.renderAgeCategoryColumn
			},{
				"mData": "time",
				"mRender": WPA.renderTimeColumn
			},{
				"mData": "garmin_id",
				"sWidth": "16px",
				"mRender": WPA.renderGarminColumn,
				"bSortable": false
			}]
		}));
	}

	jQuery(document).ready(function() {
		WPA.isAdminScreen = true;

		// set up ajax
		WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo WPA_PLUGIN_URL; ?>', '<?php echo $current_user->ID; ?>',  function() {

			// create the table
			WPA.Admin.createResultsTable();

			// common setup function
			WPA.setupCommon();

			// setup results dialog
			WPA.setupEditResultDialog(WPA.Admin.reloadResults);

			// setup the edit event screen
			WPA.setupEditEventDialog(WPA.Admin.reloadResults);

			// setup listener for select/deselct all checkboxes
			WPA.Admin.configureSelectAllCheckboxes();

			// setup filters
			WPA.setupFilters(null, WPA.Admin.resultsTable, null, WPA.Admin.doEventNameFilter, {
				event: 8,
				type: 7,
				age: 9,
				period: 4
			}, WPA.Admin.doAthleteNameFilter);

			jQuery('#delete-results').click(function() {
				WPA.Admin.deleteResults(WPA.Admin.selectedRecords);
			});

			jQuery('#reassign-results').click(function() {
				WPA.Admin.reassignResults();
			});

			jQuery('.input-cancel').click(function() {
				jQuery(this).hide();
				WPA.Admin.resultReassignId = '';
				jQuery('#reassignToUser').removeAttr('readonly').val('').focus();
			});

			// autocomplete for reassigning results to another user
			jQuery("#reassignToUser").autocomplete({
				source: WPA.Ajax.url + '?action=wpa_user_autocomplete',
				minLength: 2,
				select: function( event, ui ) {
					WPA.globals.temp = ui.item.label;
					setTimeout("jQuery('#reassignToUser').val(WPA.globals.temp)", 50);
					WPA.Admin.resultReassignId = ui.item.value;

					jQuery('#reassignToUser').attr('readonly', 'readonly');
					jQuery('.input-cancel').show();
				}
		    }).focus(function(){
		        this.select();
		    })
		});
	});

	</script>

	<div>
		<div class="wpa-admin-title">
			<h2><?php echo $this->get_property('admin_manage_results_title'); ?></h2>
		</div>
		<br style="clear:both;"/>
	</div>

	<div class="wpa">

		<div class="wpa-menu">

			<!-- FILTERS -->
			<div class="wpa-filters ui-corner-all">
				<div class="filter-ignore-for-pb">
					<select id="filterEvent">
						<option value="all" selected="selected"><?php echo $this->get_property('filter_events_option_all'); ?></option>
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

				<select id="filterAge">
					<option value="all" selected="selected"><?php echo $this->get_property('filter_age_option_all'); ?></option>
				</select>

				<div class="filter-ignore-for-pb">
					<input id="filterEventName" highlight-class="filter-highlight" default-text="<?php echo $this->get_property('filter_event_name_input_text'); ?>" class="ui-corner-all ui-widget ui-widget-content ui-state-default wpa-search wpa-search-disabled"></input>
					<span id="filterEventNameCancel" style="display:none;" title="<?php echo $this->get_property('filter_event_name_cancel_text'); ?>" class="filter-name-remove"></span>
				</div>

				<div class="filter-ignore-for-pb">
					<input id="filterAthleteName" highlight-class="filter-highlight" default-text="<?php echo $this->get_property('filter_athlete_name_input_text'); ?>" class="ui-corner-all ui-widget ui-widget-content ui-state-default wpa-search wpa-search-disabled"></input>
					<span id="filterAthleteNameCancel" style="display:none;" title="<?php echo $this->get_property('filter_athlete_name_cancel_text'); ?>" class="filter-name-remove"></span>
				</div>
			</div>

			<br style="clear:both"/>
		</div>

		<!-- SELECTED EVENT OPTIONS -->
		<div class="wpa-select-options">
			<span id="delete-results"><?php echo $this->get_property('delete_selected_results_text') ?></span>
			<span id="reassign-results"><?php echo $this->get_property('reassign_selected_results_text') ?></span>
		</div>

		<!-- DATA TABLE -->
		<div id="wpa-admin-manage-results">
			<table cellpadding="0" cellspacing="0" border="0" class="display ui-state-default" style="border-bottom:none" id="all-results-table" width="100%">
			  <thead>
				<tr>
					<th></th>
					<th></th>
					<th><?php echo $this->get_property('column_athlete_name') ?></th>
					<th><?php echo $this->get_property('column_result_date') ?></th>
					<th><?php echo $this->get_property('column_event_date') ?></th>
					<th><?php echo $this->get_property('column_event_name') ?></th>
					<th><?php echo $this->get_property('column_event_location') ?></th>
					<th><?php echo $this->get_property('column_event_type') ?></th>
					<th><?php echo $this->get_property('column_category') ?></th>
					<th><?php echo $this->get_property('column_age_category') ?></th>
					<th><?php echo $this->get_property('column_time') ?></th>
					<th></th>
					</tr>
				</thead>
			</table>
		</div>

		<!-- DELETE RESULTS CONFIRM DIALOG -->
		<div id="delete-results-confirm-dialog" style="display:none">
			<p>
				<?php echo $this->get_property('delete_results_confirm_text') ?>
			</p>
		</div>

		<!-- REASSIGN RESULTS DIALOG -->
		<div id="reassign-results-dialog" style="display:none">
			<p>
				<?php echo $this->get_property('reassign_results_text') ?>
			</p>
			<p>
				<input style="background:#fff" size="50" id="reassignToUser" default-text="<?php echo $this->get_property('reassign_results_input_text'); ?>" class="ui-corner-all ui-widget ui-widget-content ui-state-default wpa-search wpa-search-disabled"></input>
				<span style="display:none;" class="input-cancel"></span>
			</p>
		</div>

		<!-- ADD/EDIT RESULTS DIALOG -->
		<?php $this->create_edit_result_dialog(); ?>

		<!-- EDIT EVENT DIALOG -->
		<?php $this->create_edit_event_dialog(); ?>

		<!-- COMMON DIALOGS -->
		<?php $this->create_common_dialogs(); ?>

	</div>

<?php
}
?>