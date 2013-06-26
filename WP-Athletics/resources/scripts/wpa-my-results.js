/*
 * Javascript functions for WPA my results page
 */

WPA.MyResults = {

	/**
	 * Enables or disables the pre selected event when adding a result
	 */
	toggleAddResultEvent: function(enable) {
		if(enable) {
			// reset the event category id
			jQuery('#addResultEventId').val('');
			jQuery('#addResultEventName').val('').focus();
			jQuery('#addResultEventCategory').combobox('setValue', '');
			jQuery('#addResultEventSubType').combobox('setValue', '');
			jQuery('#addResultDate').val('');
			jQuery('#addResultEventLocation').val('');
		}
		jQuery('.ui-datepicker-trigger').toggle(enable);
		jQuery('.add-result-cancel-event').toggle(!enable);
		jQuery('#addResultEventName').prop('disabled', !enable);
		jQuery('#addResultDate').prop('disabled', !enable);
		jQuery('#addResultEventLocation').prop('disabled', !enable);
		
		// selects
		jQuery('#addResultEventSubType').combobox('disabled', !enable);
		jQuery('#addResultEventCategory').combobox('disabled', !enable);
	},
		
	/**
	 * Validates the add result form and adds error class if required
	 */
	validateAddResultForm: function() {
		var valid = true;
		
		var requiredFields = jQuery('.add-result-required');
		jQuery.each(requiredFields, function() {
			var el = jQuery(this);
			if(el.val() == '') {
				if(el.is("select")) {
					el.combobox('addCls', 'ui-state-error');
				}
				else {
					el.addClass('ui-state-error');
				}
				valid = false;
			}
		});
		
		return valid;
	},
	
	/**
	 * Opens confirm dialog to deletes an event result
	 */
	deleteResult: function(id) {
		jQuery("#result-delete-confirm").dialog({
	      resizable: false,
	      height:140,
	      modal: true,
	      buttons: {
	        "Delete": function() {
	          jQuery( this ).dialog("close");
	          WPA.Ajax.deleteResult(id);
	        },
	        Cancel: function() {
	          jQuery( this ).dialog( "close" );
	        }
	      }
	    });
	},
	
	/**
	 * Opens the dialog to edit an event result
	 */
	editResult: function(id) {
		jQuery("#addResultDialog").dialog("option", "title", WPA.getProperty('edit_result_title'));
		jQuery("#addResultDialog").dialog("open");
		WPA.Ajax.loadResultInfo(id);
	},
	
	/**
	 * callback for when event info has been requested on the add/update result screen
	 */
	loadEventInfoCallback: function(result) {
		// inputs
		jQuery('#addResultDate').removeClass('ui-state-error').datepicker('setDate', result.date);
		jQuery('#addResultEventName').removeClass('ui-state-error').val(result.name);
		jQuery('#addResultEventId').val(result.id);
		jQuery('#addResultEventLocation').removeClass('ui-state-error').val(result.location).change();
		
		// selects
		jQuery('#addResultEventCategory').combobox('setValue', result.event_cat_id).combobox('removeCls', 'ui-state-error');
		jQuery('#addResultEventSubType').combobox('setValue', result.sub_type_id).combobox('removeCls', 'ui-state-error');
		
		WPA.MyResults.triggerAddEventCategoryChange();
		WPA.MyResults.toggleAddResultEvent(false);
	},
	
	/**
	 * loads the result information onto the update fields
	 */
	setResultUpdateInfo: function(result) {
		// load the event info
		WPA.Ajax.getEventInfo(result.event_id, function(_result) {
			WPA.MyResults.loadEventInfoCallback(_result);
			var time = WPA.millisecondsToTime(result.time);
			jQuery("#addResultId").val(result.id);
			jQuery('#addResultAgeCategory').combobox('setValue', result.age_category).combobox('removeCls', 'ui-state-error');
			jQuery('#addResultPosition').val(result.position);
			jQuery('#addResultGarminId').val(result.garmin_id);
			jQuery('#addResultTimeHours').val(time.hours);
			jQuery('#addResultTimeMinutes').val(time.minutes);
			jQuery('#addResultTimeSeconds').val(time.seconds);
			jQuery('#addResultTimeMilliSeconds').val(time.milliseconds);
		});
	},
	
	/**
	 * reads the profile photo url from the hidden input and populates as background image of the profile photo div
	 */
	loadProfilePhoto: function() {
		var url =  jQuery('#user-image').val();
		if(url) {
			jQuery('#wpaProfilePhoto').removeClass('wpa-profile-photo-default').css('background-image', 'url(' + url + ')');
		}
	},
	
	/**
	 * set visiblity of time fields when event category changes on add result screen
	 */
	triggerAddEventCategoryChange: function() {
		// set visibility of time fields
		var fields = jQuery('div[time-format]');
		jQuery.each(fields, function() {
			jQuery(this).find('input').val('').removeClass('add-result-required');
			jQuery(this).hide();
		});
		
		var selectEl = jQuery("#addResultEventCategory");
	
		var timeFormatStr = jQuery('option:selected', selectEl).attr('time-format');
		if(timeFormatStr) {
			var timeFormats = timeFormatStr.split(':');
			jQuery.each(timeFormats, function(i, format) {
				jQuery('div[time-format="' + format + '"]').show().find('input').addClass('add-result-required');
			});
		}
	},
	
	/**
	 * reloads all results
	 */
	reloadResults: function() {
		// redraw the table
		WPA.MyResults.myResultsTable.fnDraw();
		// load personal bests
		WPA.MyResults.getPersonalBests();
	},
	
	/**
	 * Loads personal bests
	 */
	getPersonalBests: function() {
		WPA.Ajax.getPersonalBests(function(result) {
			WPA.MyResults.pbTable.fnClearTable();
			WPA.MyResults.pbTable.fnAddData(result);
		}, {
			userId: WPA.userId,
			ageCategory: WPA.filterAge,
			eventSubTypeId: WPA.filterType,
			eventDate: WPA.filterPeriod
		});
	},

	/** 
	 * validates and submits the add result form
	 */
	submitResult: function() {
		if(WPA.MyResults.validateAddResultForm()) {
			WPA.Ajax.updateResult({
				resultId: jQuery('#addResultId').val(),
				time: WPA.timeToMilliseconds(
					jQuery('#addResultTimeHours').val(),
					jQuery('#addResultTimeMinutes').val(),
					jQuery('#addResultTimeSeconds').val(),
					jQuery('#addResultTimeMilliSeconds').val()
				),
				eventId: jQuery('#addResultEventId').val(),
				eventDate: jQuery('#addResultEventDate').val(),
				eventName: jQuery('#addResultEventName').val(),
				eventCategory: jQuery('#addResultEventCategory').val(),
				eventSubType: jQuery('#addResultEventSubType').val(),
				position: jQuery('#addResultPosition').val(),
				garminId: jQuery('#addResultGarminId').val(),
				ageCategory: jQuery("#addResultAgeCategory").val(),
				eventLocation: jQuery('#addResultEventLocation').val()
			}, function() {
				// success function - load the results and close dialog
				WPA.MyResults.resetAddResultForm();
				
				jQuery("#addResultDialog").dialog("close");
				
				// reload the results
				WPA.MyResults.reloadResults();
			});
		}
	},
	
	/**
	 * resets the fields in the add result form
	 */
	resetAddResultForm: function() {
		WPA.MyResults.toggleAddResultEvent(true);
		jQuery('#addResultDialog form input,select').each(function() {
			jQuery(this).val('');
		});
		
		// set age category
		jQuery("#addResultAgeCategory").val(WPA.currentAgeCategory).combobox('setValue', WPA.currentAgeCategory);
	},
	
	/** 
	 * Creates my results tables
	 */
	createMyResultsTables: function() {

		// My Results table
		WPA.MyResults.myResultsTable = jQuery('#my-results-table').dataTable(WPA.createTableConfig({
			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": WPA.Ajax.url,
			"sServerMethod": "POST",
			"fnServerParams": function ( aoData ) {
			    aoData.push( 
			    	{name : 'action', value : 'wpa_get_results' },
			    	{name : 'security', value : WPA.Ajax.nonce }
			    );
			},
			"aaSorting": [[ 2, "desc" ]],
			"aoColumns": [{
				"mData": "time_format",
				"bVisible": false
			},{
				"mData": "id",
				"sWidth": "60px",
				"mRender": WPA.renderDeleteEditResultColumn,
				"bSortable": false
			},{
				"mData": "event_date"
			},{
				"mData": "event_name",
				"mRender" : WPA.renderEventLinkColumn
			},{
				"mData": "event_location"
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
				"mData": "position",
				"sWidth": "20px",
				"mRender": WPA.renderPositionColumn,
				"sClass": "datatable-center"
			},{
				"mData": "club_rank",
				"sWidth": "20px",
				"bSortable": false,
				"sClass": "datatable-center"
			},{
				"mData": "garmin_id",
				"sWidth": "16px",
				"mRender": WPA.renderGarminColumn,
				"bSortable": false
			}]
		}));
		
		// Create the personal bests table
		WPA.MyResults.pbTable = jQuery('#my-personal-bests-table').dataTable(WPA.createTableConfig({
			"sDom": 'rt',
			"bPaginate": false,
			"aaSorting": [[ 2, "asc" ]],
			"aoColumns": [{ 
				"mData": "time_format",
				"bVisible": false
			},{ 
				"mData": "user_id",
				"bVisible": false
			},{
				"mData": "event_cat_id",
				"bVisible": false
			},{
				"mData": "id",
				"sWidth": "60px",
				"mRender": WPA.renderDeleteEditResultColumn,
				"bSortable": false
			},{ 
				"mData": "category",
				"sClass": "datatable-bold-right-gray"
			},{ 
				"mData": "time",
				"sClass": "datatable-bold",
				"mRender": WPA.renderTimeColumn
			},{ 
				"mData": "event_name",
				"mRender" : WPA.renderEventLinkColumn
			},{
				"mData": "event_location"
			},{
				"mData": "event_sub_type_id",
				"mRender" : WPA.renderEventTypeColumn
			},{
				"mData": "age_category",
				"mRender" : WPA.renderAgeCategoryColumn
			},{ 
				"mData": "event_date"
			},{
				"mData": "club_rank",
				"sWidth": "20px",
				"bSortable": false,
				"sClass": "datatable-center"
			},{
				"mData": "garmin_id",
				"sWidth": "16px",
				"mRender": WPA.renderGarminColumn,
				"bSortable": false
			}]
		}));

	},
	
	/**
	 * performs filter search on the event name
	 */
	doEventNameFilter: function() {
		var defaultText = jQuery('#filterEventName').attr('default-text');
		var val = jQuery('#filterEventName').val();
		if(val != '' && defaultText != val) {
			WPA.filterEventName = val;
			WPA.MyResults.myResultsTable.fnFilter( val, 3 );
		}
		else {
			WPA.filterEventName = null;
			WPA.MyResults.myResultsTable.fnFilter( '', 3 );
		}
	}
};
