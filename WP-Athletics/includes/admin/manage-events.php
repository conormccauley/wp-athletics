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
			WPA.Admin.eventsTable.fnFilter( val, 3 );
		}
		else {
			WPA.filterEventName = null;
			WPA.Admin.eventsTable.fnFilter( '', 3 );
		}
	}

	WPA.Admin.resetSelectValuesAndReload = function() {
		WPA.Admin.resetSelectValues();
		WPA.Admin.reloadEvents();
	}

	WPA.Admin.getEventDescription = function(id) {
		var data = WPA.Admin.eventsTable.fnGetData();
		var result = id;

		jQuery.each(data, function(index, row) {
			if(row['event_id'] == id) {
				result = row['event_name'];
				return false;
			}
		});

		return result;
	}

	WPA.Admin.mergeEvents = function() {
		if(WPA.Admin.selectedRecords.length > 1) {

			// set the select options
			jQuery('#merge-events-primary-event').find('option').remove();

			// append new options
			jQuery.each(WPA.Admin.selectedRecords, function(index, id) {
				jQuery('#merge-events-primary-event').append('<option value="' + id + '">' + WPA.Admin.getEventDescription(id) + '</option>');
			});

			// set initial value
			WPA.Admin.eventReassignId = WPA.Admin.selectedRecords[0];

			jQuery('#merge-events-dialog').dialog({
				title: WPA.getProperty('merge_events_title'),
				autoOpen: true,
				resizable: false,
				modal: true,
				height: 'auto',
				width: 400,
				buttons: [{
			    	text: WPA.getProperty('merge'),
			    	click: function() {
						WPA.Ajax.mergeEvents(WPA.Admin.selectedRecords, WPA.Admin.eventReassignId, function(result) {
							jQuery('#merge-events-dialog').dialog("close");
							if(parseInt(result) > 0) {
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
		else {
			WPA.alertError(WPA.getProperty('merge_events_invalid_selection'));
		}
	}

	WPA.Admin.reloadEvents = function() {
		WPA.Admin.eventsTable.fnDraw();
	}

	WPA.Admin.deleteEvents = function(ids, resultCount) {
		WPA.Admin.eventReassignId = '';
		WPA.Admin.deleteIds = ids;
		jQuery('#delete-events-reassign-results').show();

		var count = resultCount ? resultCount : WPA.Admin.selectedResultCount;
		console.log('deleting ' + ids + ' total results is ' + count);

		jQuery('#result-count').html(count);
		jQuery('#event-count').html(ids.length);

		if(count == 0) {
			jQuery('#delete-events-reassign-results').hide();
		}

		WPA.Admin.deleteEventsDialog = jQuery('#delete-events-confirm-dialog').dialog({
			title: WPA.getProperty('delete_events_confirm_title'),
			autoOpen: true,
			resizable: false,
			modal: true,
			height: 'auto',
			width: 'auto',
			buttons: [{
		    	text: WPA.getProperty('delete'),
		    	click: function() {
					WPA.Ajax.deleteEvents(WPA.Admin.deleteIds, WPA.Admin.eventReassignId, function(result) {
						jQuery('#delete-events-confirm-dialog').dialog("close");
						if(parseInt(result) > 0) {
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

		jQuery("#reassignToEvent").val('').blur();

	}

	WPA.Admin.deleteEvent = function(id, resultCount) {
		WPA.Admin.deleteEvents([id.toString()], resultCount);
	}

	WPA.Admin.createEventsTable = function() {
		WPA.Admin.eventsTable = jQuery('#all-events-table').dataTable(WPA.createTableConfig({
			"bServerSide": true,
			"sScrollX": "100%",
			"bScrollCollapse": true,
			"sDom": 'rt<"bottom fg-toolbar ui-toolbar ui-widget-header ui-corner-bl ui-corner-br ui-helper-clearfix"lip>',
			"sAjaxSource": WPA.Ajax.url,
			"fnDrawCallback": function() {
				jQuery('.datatable-checkbox input').change(WPA.Admin.selectRecordToggleWrapper);
			},
			"iDisplayLength": 25,
			"sServerMethod": "POST",
			"fnServerParams": function ( aoData ) {
			    aoData.push(
			    	{name : 'action', value : 'wpa_get_all_events' },
			    	{name : 'security', value : WPA.Ajax.nonce }
			    );
			},
			"aaSorting": [[ 1, "desc" ]],
			"aoColumns": [{
				"mData": "event_id",
				"sTitle": "<input title='" + WPA.getProperty('select_unselect_all_tooltip') + "' id='datatable-select-all' type='checkbox'/>",
				"sWidth": "60px",
				"mRender": WPA.renderAdminDeleteEditEventColumn,
				"bSortable": false
			},{
				"mData": "event_id",
				"mRender" : WPA.renderEventShorcode,
				"bSortable": false
			},{
				"mData": "event_date",
				"sWidth": "100px"
			},{
				"mData": "event_name",
				"mRender" : WPA.renderEventLinkColumn
			},{
				"mData": "event_location",
				"mRender": WPA.renderEventLocationColumn
			},{
				"mData": "event_sub_type_id",
				"mRender" : WPA.renderEventTypeColumn
			},{
				"mData": "category"
			},{
				"mData": "result_count",
				"sWidth": "20px",
				"sClass": "datatable-center"
			}]
		}));
	}

	jQuery(document).ready(function() {
		WPA.isAdminScreen = true;

		// set up ajax
		WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo WPA_PLUGIN_URL; ?>', '<?php echo $current_user->ID; ?>',  function() {

			// create the table
			WPA.Admin.createEventsTable();

			// setup the edit event screen
			WPA.setupEditEventDialog(WPA.Admin.reloadEvents);

			// common setup function
			WPA.setupCommon();

			// setup filters
			WPA.setupFilters(null, WPA.Admin.eventsTable, null, WPA.Admin.doEventNameFilter, {
				event: 6,
				type: 5,
				period: 2
			});

			// autocomplete for reassigning results when deleting multiple events
			jQuery("#reassignToEvent").autocomplete({
				source: WPA.Ajax.url + '?action=wpa_event_autocomplete',
				minLength: 2,
				select: function( event, ui ) {
					// make sure it's not an event being delete
					if(jQuery.inArray(ui.item.value, WPA.Admin.deleteIds) == -1) {
						WPA.globals.temp = ui.item.label;
						setTimeout("jQuery('#reassignToEvent').val(WPA.globals.temp)", 200);
						WPA.Admin.eventReassignId = ui.item.value;
					}
					else {
						WPA.alertError(WPA.getProperty('delete_events_invalid_reassign_event'));
						setTimeout("jQuery('#reassignToEvent').val('')", 200);
					}
				}
		    }).focus(function(){
		        this.select();
		    })

		    // change event for the merge dropdown
		    jQuery('#merge-events-primary-event').change(function() {
		    	WPA.Admin.eventReassignId = jQuery(this).val();
		    });

			jQuery('#delete-events').click(function() {
				WPA.Admin.deleteEvents(WPA.Admin.selectedRecords);
			});

			jQuery('#merge-events').click(function() {
				WPA.Admin.mergeEvents();
			});

			jQuery('#create-event-button button').button({
				icons: {
		        	primary: 'ui-icon-circle-plus'
		        }
			}).click(function(e) {
				e.preventDefault();
				WPA.showCreateEventDialog();
			});

			// setup listener for select/deselct all checkboxes
			WPA.Admin.configureSelectAllCheckboxes();
		});
	});

	</script>

	<div>
		<div class="wpa-admin-title">
			<h2><?php echo $this->get_property('admin_manage_events_title'); ?></h2>
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

				<div class="filter-ignore-for-pb">
					<input id="filterEventName" highlight-class="filter-highlight" default-text="<?php echo $this->get_property('filter_event_name_input_text'); ?>" class="ui-corner-all ui-widget ui-widget-content ui-state-default wpa-search wpa-search-disabled"></input>
					<span id="filterEventNameCancel" style="display:none;" title="<?php echo $this->get_property('filter_event_name_cancel_text'); ?>" class="filter-name-remove"></span>
				</div>
			</div>

			<div id="create-event-button">
				<button><?php echo $this->get_property('events_create_button'); ?></button>
			</div>

			<br style="clear:both"/>
		</div>


		<!-- SELECTED EVENT OPTIONS -->
		<div class="wpa-select-options">
			<span id="delete-events"><?php echo $this->get_property('delete_selected_events_text') ?></span>
			<span id="merge-events"><?php echo $this->get_property('merge_selected_events_text') ?></span>
		</div>

		<!-- DATA TABLE -->
		<div id="wpa-admin-manage-events">
			<table cellpadding="0" cellspacing="0" border="0" class="display ui-state-default" style="border-bottom:none" id="all-events-table" width="100%">
			  <thead>
				<tr>
					<th></th>
					<th><?php echo $this->get_property('column_event_shortcode') ?><span class="column-help" title="<?php echo $this->get_property('embed_event_results_column_help'); ?>"></span></th>
					<th><?php echo $this->get_property('column_event_date') ?></th>
					<th><?php echo $this->get_property('column_event_name') ?></th>
					<th><?php echo $this->get_property('column_event_location') ?></th>
					<th><?php echo $this->get_property('column_event_type') ?></th>
					<th><?php echo $this->get_property('column_category') ?></th>
					<th><?php echo $this->get_property('column_result_count') ?></th>
					</tr>
				</thead>
			</table>
		</div>

		<!-- DELETE EVENTS CONFIRM DIALOG -->
		<div id="delete-events-confirm-dialog" style="display:none">
			<p>
				<?php echo $this->get_property('delete_events_text') ?>
			</p>

			<div id="delete-events-reassign-results">
				<p>
					<div id="delete-events-warning">
						*** <?php echo $this->get_property('delete_events_warning_title') ?> ***
					</div>
					<?php echo $this->get_property('delete_events_reassign_text') ?>
				</p>
				<div>
					<input style="background:#fff" size="50" id="reassignToEvent" default-text="<?php echo $this->get_property('delete_events_reassign_results_text'); ?>" class="ui-corner-all ui-widget ui-widget-content ui-state-default wpa-search wpa-search-disabled"></input>
				</div>
			</div>
		</div>

		<!-- MERGE EVENTS DIALOG -->
		<div id="merge-events-dialog" style="display:none">
			<p>
				<?php echo $this->get_property('merge_events_text') ?>
			</p>
			<p>
				<select id="merge-events-primary-event">
				</select>
			</p>
		</div>

		<!-- ADD/EDIT EVENT DIALOG -->
		<?php $this->create_edit_event_dialog(); ?>

		<!-- COMMON DIALOGS -->
		<?php $this->create_common_dialogs(); ?>

	</div>

<?php
}
?>