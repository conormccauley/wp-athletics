/*
 * Javascript util/common functions for WPA.
 */

var WPA = {
		
	globals: {},
		
	/**
	 * creates a datatables config, merges provided config with default config
	 */
	createTableConfig: function(config) {
		var defaultConfig = {
			"bProcessing": true,
			"sDom": 'rt<"bottom fg-toolbar ui-toolbar ui-widget-header ui-corner-bl ui-corner-br ui-helper-clearfix"ip>',
			"bJQueryUI": true,
			"oLanguage": {
				"sEmptyTable": this.getProperty('table_no_results')
			}
		}
		jQuery.extend(defaultConfig, config);
		return defaultConfig;
	},
		
	/**
	 * Returns a language property based on a supplied key. Returns the default value if specified or otherwise the original key
	 */
	getProperty: function(key, _default) {
		if(WPA.Props && WPA.Props[key]) {
			return WPA.Props[key];
		}
		return _default ? _default : key;
	},

	/**
	 * Converts a supplied value in milliseconds and returns an object representing milliseconds, seconds, minutes and hours
	 */
	millisecondsToTime: function(value) {
		var milli = parseInt(value);
		return {
			milliseconds: milli % 1000,
			seconds: Math.floor((milli / 1000) % 60),
			minutes: Math.floor((milli / (60 * 1000)) % 60),
			hours: Math.floor((milli / ( 1000 * 60 * 60)) % 24)
		}
	},
	
	/**
	 * Converts supplies hours,minute,second and millisecond values into a total milliseconds result
	 */
	timeToMilliseconds: function(hours, minutes, seconds, milliseconds) {
		
		hours = parseInt(hours);
		minutes = parseInt(minutes);
		seconds = parseInt(seconds);
		milliseconds = parseInt(milliseconds);
		
		var result = milliseconds ? milliseconds : 0;
		
		if(hours) {
			result+= Math.floor(hours * 3600000);
		}
		if(minutes) {
			result+= Math.floor(minutes * 60000);
		}
		if(seconds) {
			result+= Math.floor(seconds * 1000);
		}
		return result;
	},
	
	/**
	 * Validates a time to ensure it is in the correct format
	 */
	isValidTime: function(format, value) {
		if(jQuery.isNumeric(value)) {
			
			value = parseInt(value);
			
			if(format == 'm' || format == 's') {
				return value >= 0 && value <= 60;
			}
			if(format == 'h' || format == 'ms') {
				return value >= 0;
			}
		}
		return false;
	},
	
	/**
	 * converts a time in milliseconds to a custom displayed format where:
	 * 
	 * h = hours
	 * m = minutes
	 * s = seconds
	 * ms = milliseconds
	 * 
	 */
	displayEventTime: function(value, format) {
		var time = this.millisecondsToTime(value);
		var returnValue = '';
		var formatArray = format.split(':');
		
		jQuery.each(formatArray, function(i, f) {
			if(f == 'h') {
				returnValue += time.hours + ':';
			}
			else if(f == 'm') {
				returnValue += (time.minutes < 10 ? '0' : '') + time.minutes + ':';
			}
			else if(f == 's') {
				returnValue += (time.seconds < 10 ? '0' : '') + time.seconds + '.';
			}
			else if(f == 'ms' && time.milliseconds > 0) {
				returnValue += Math.round(time.milliseconds * 100) / 100 + ':';
			}
		})
		return returnValue != '' ? returnValue.substring(0, returnValue.length -1) : 'Invalid Time';
	},
	
	/**
	 * Returns age category description object based on ID
	 */
	getAgeCategoryDescription: function(id) {
		var result = '';
		if(WPA.globals.ageCategories) {
			jQuery.each(WPA.globals.ageCategories, function(index,obj) {
				if(obj.id == id) {
					result = obj.description;
					return false;
				}
			});
		}
		return result;
	},
	
	/**
	 * Returns event sub type description based on ID
	 */
	getEventSubTypeDescription: function(id) {
		var result = '';
		if(WPA.globals.eventTypes) {
			jQuery.each(WPA.globals.eventTypes, function(index,obj) {
				if(obj.id == id) {
					result = obj.description;
					return false;
				}
			});
		}
		return result;
	},
	
	/**
	 * Returns event category description based on ID
	 */
	getEventCategoryDescription: function(id) {
		var result = '';
		if(WPA.globals.eventCategories) {
			jQuery.each(WPA.globals.eventCategories, function(index,obj) {
				if(obj.id == id) {
					result = obj.name;
					return false;
				}
			});
		}
		return result;
	},
	
	/**
	 * Displays the user profile dialog
	 */
	displayUserProfileDialog: function(userId) {
		WPA.currentUserProfileId = userId;
		if(!WPA.userProfileDialog) {
			this.createUserProfileDatatables(userId);
			
			WPA.userProfileDialog = jQuery('#user-profile-dialog').dialog({
				title: this.getProperty('user_profile_dialog_title'),
				autoOpen: true,
				resizable: false,
				modal: true,
				width: jQuery(document).width()-100,
				height: 'auto',
				maxHeight: jQuery(window).height()-100,
			})
		}
		// get personal bests
		WPA.getPersonalBests();
		
		// get user profile info
		WPA.Ajax.getUserProfile(userId, function(result) {
			jQuery('#wpa-profile-name').html(result.name);
			jQuery('#wpa-profile-fave-event').html(WPA.getEventCategoryDescription(result.faveEvent));
			jQuery('#wpa-profile-age-class').html(WPA.getAgeCategoryDescription(result.ageCategory));
			if(result.photo) {
				jQuery('#wpaUserProfilePhoto').removeClass('wpa-profile-photo-default').css('background-image', 'url(' + result.photo + ')');
			}
			else {
				jQuery('#wpaUserProfilePhoto').addClass('wpa-profile-photo-default')
			}
			WPA.userProfileDialog.dialog("close");
			WPA.userProfileDialog.dialog("open");
			WPA.resultsTable.fnDraw(false);
		})
		
		// set up the period filter
		WPA.Ajax.getUserOldestResultYear(userId, function(result) {
			
			// remove old values
			jQuery('#profileFilterPeriod option[year="y"]').remove();

			if(result) {
				var userYear = parseInt(result);
				var currentYear = new Date().getFullYear()-1;
				if(currentYear > userYear) {
					for(var year = currentYear; year >= userYear; year--) {
						jQuery("#profileFilterPeriod").append('<option year="y" value="year:' + year + '">' + year + '</option>');
					}
				}
			}
			
			// filter period combo
			jQuery("#profileFilterPeriod").combobox({
				select: function(event, ui) {
					WPA.profileFilterPeriod = ui.item.value;
					WPA.resultsTable.fnFilter( WPA.profileFilterPeriod, 1 );
					WPA.getPersonalBests();
				},
				selectClass: 'filter-highlight'
			});
		});
	},
	
	/**
	 * Displays the event results dialog
	 */
	displayEventResultsDialog: function(eventId) {
		if(!WPA.eventResultsDialog) {
			this.createEventResultsDatatables();
			
			WPA.eventResultsDialog = jQuery('#event-results-dialog').dialog({
				title: this.getProperty('event_results_dialog_title'),
				autoOpen: true,
				resizable: false,
				modal: true,
				width: 600,
				height: 'auto',
				resizable: false,
				maxHeight: 600
			})
		}
		
		WPA.Ajax.getEventInfo(eventId, function(result) {
			jQuery('#eventInfoName').html(result.name + ', ' + result.location);
			jQuery('#eventInfoDate').html(result.date);
			jQuery('#eventInfoDetail').html(WPA.getEventSubTypeDescription(result.sub_type_id) + ' ' + WPA.getEventCategoryDescription(result.event_cat_id));
		})
		
		// load the events
		WPA.Ajax.getEventResults(eventId, function(result) {
			WPA.eventResultsTable.fnClearTable();
			WPA.eventResultsTable.fnAddData(result);
			WPA.eventResultsDialog.dialog("close");
			WPA.eventResultsDialog.dialog("open");
		});
	},
	
	/**
	 * Loads personal bests
	 */
	getPersonalBests: function(userId) {
		WPA.Ajax.getPersonalBests(function(result) {
			WPA.pbTable.fnClearTable();
			WPA.pbTable.fnAddData(result);
		}, {
			userId: WPA.currentUserProfileId,
			ageCategory: WPA.profileFilterAge,
			eventSubTypeId: WPA.profileFilterType,
			eventDate: WPA.profileFilterPeriod
		});
	},
	
	/**
	 * Creates the event results datatables
	 */
	createEventResultsDatatables: function() {

		WPA.eventResultsTable = jQuery('#event-results-table').dataTable(WPA.createTableConfig({
			//"sDom": 'rt',
			"bPaginate": false,
			"aaSorting": [[ 1, "asc" ]],
			"aoColumns": [{ 
				"mData": "time_format",
				"bVisible": false
			},{ 
				"mData": "rank",
				"sClass": "datatable-center"
			},{ 
				"mData": "athlete_name",
				"mRender" : WPA.renderProfileLinkColumn,
				"sClass": "datatable-right"
			},{ 
				"mData": "time",
				"sClass": "datatable-bold",
				"mRender": WPA.renderTimeColumn
			},{
				"mData": "age_category",
				"mRender" : WPA.renderAgeCategoryColumn
			},{ 
				"mData": "position",
				"mRender": WPA.renderPositionColumn
			},{
				"mData": "garmin_id",
				"sWidth": "16px",
				"mRender": WPA.renderGarminColumn,
				"bSortable": false
			}]
		}));
	},
	
	/**
	 * Creates the user profile datatables
	 */
	createUserProfileDatatables: function(userId) {
		
		// destroy current table if it exists
		if(WPA.resultsTable) {
			//WPA.resultsTable.fnDestroy();
		}

		// Results table
		WPA.resultsTable = jQuery('#results-table').dataTable(WPA.createTableConfig({
			"bProcessing": true,
			"bServerSide": true,
			"sAjaxSource": WPA.Ajax.url,
			"sServerMethod": "POST",
			"fnServerParams": function ( aoData ) {
			    aoData.push( 
			    	{name : 'action', value : 'wpa_get_results' },
			    	{name : 'security', value : WPA.Ajax.nonce },
			    	{name: 'user_id', value: WPA.currentUserProfileId }
			    );
			},
			"aaSorting": [[ 1, "desc" ]],
			"aoColumns": [{
				"mData": "time_format",
				"bVisible": false
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
				"mRender": WPA.renderPositionColumn
			},{
				"mData": "garmin_id",
				"sWidth": "16px",
				"mRender": WPA.renderGarminColumn,
				"bSortable": false
			}]
		}));
		
		// Personal bests table
		WPA.pbTable = jQuery('#personal-bests-table').dataTable(WPA.createTableConfig({
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
				"mData": "garmin_id",
				"sWidth": "16px",
				"mRender": WPA.renderGarminColumn,
				"bSortable": false
			}]
		}));
	},
	
	/**
	 * closes any open dialogs
	 */
	closeDialogs: function() {
		if(WPA.eventResultsDialog) {
			WPA.eventResultsDialog.dialog("close");
		}
		if(WPA.userProfileDialog) {
			WPA.userProfileDialog.dialog("close");
		}
	},
	
	/**
	 * configures blur/focus functions of any fields with the 'wpa-search' class
	 */
	setupSearchFields: function() {
		jQuery('.wpa-search').each(function() {
			var defaultText = jQuery(this).attr('default-text');
			jQuery(this).focus(function() {
				var text = jQuery(this).attr('default-text');
		    	if(jQuery(this).val() == text) {
		    		jQuery(this).val('').removeClass('wpa-search-disabled');
		    	}
		    	else {
		    		jQuery(this).select();
		    	}
		    }).blur(function() {
		    	var text = jQuery(this).attr('default-text');
		    	var value = jQuery(this).val();
		    	if(value == '') {
		    		jQuery(this).val(text).addClass('wpa-search-disabled');
		    	}
		    }).val(defaultText);
		});	
	},
	
	/**
	 * configures the wpa autcomplete search field
	 */
	setupAutocompleteSearch: function() {
		jQuery('#wpa-search').catcomplete({
			source: WPA.Ajax.url + '?action=wpa_search_autocomplete',
			minLength: 2,
			select: function( event, ui ) {
				if(ui.item.category == 'event') {
					WPA.displayEventResultsDialog(ui.item.value);
				}
				else if(ui.item.category == 'athlete') {
					WPA.displayUserProfileDialog(ui.item.value);
				}
				setTimeout('jQuery("#wpa-search").val("").blur();', 1000);
			}
	    })
	},
	
	/**
	 * configures the filters for 'records' or 'my results' page
	 */
	setupFilters: function(userId, table, personalBestsCallFn, eventNameFilter, columnIndexes) {
		// add items to combos
		jQuery(WPA.globals.eventCategories).each(function(index, item) {
			jQuery("#filterEvent").append('<option value="' + item.id + '">' + item.name + '</option>');
		});
		
		jQuery(WPA.globals.eventTypes).each(function(index, item) {
			jQuery("#filterType").append('<option value="' + item.id + '">' + item.description + '</option>');
		});
		
		jQuery(WPA.globals.ageCategories).each(function(index, item) {
			jQuery("#filterAge").append('<option value="' + item.id + '">' + item.description + '</option>');
		});
		
		WPA.filterPeriod = undefined;
		WPA.filterEvent = undefined;
		WPA.filterType = undefined;
		WPA.filterAge = undefined;
		
		// filter event combo
		jQuery("#filterEvent").combobox({
			select: function(event, ui) {
				WPA.filterEvent = ui.item.value;
				if(table) table.fnFilter( ui.item.value, columnIndexes.event );
			},
			selectClass: 'filter-highlight'
		});

		// filter type combo
		jQuery("#filterType").combobox({
			select: function(event, ui) {
				WPA.filterType = ui.item.value;
				if(table) table.fnFilter( ui.item.value, columnIndexes.type );
				if(personalBestsCallFn) personalBestsCallFn();
			},
			selectClass: 'filter-highlight'
		});

		// filter age combo
		jQuery("#filterAge").combobox({
			select: function(event, ui) {
				WPA.filterAge = ui.item.value;
				if(table) table.fnFilter( ui.item.value, columnIndexes.age );
				if(personalBestsCallFn) personalBestsCallFn();
			},
			selectClass: 'filter-highlight'
		});
		
		// set up the period filter
		WPA.Ajax.getUserOldestResultYear(userId, function(result) {
			
			if(result) {
				var userYear = parseInt(result);
				var currentYear = new Date().getFullYear()-1;
				if(currentYear > userYear) {
					for(var year = currentYear; year >= userYear; year--) {
						jQuery("#filterPeriod").append('<option year="y" value="year:' + year + '">' + year + '</option>');
					}
				}
			}
			
			// filter period combo
			jQuery("#filterPeriod").combobox({
				select: function(event, ui) {
					WPA.filterPeriod = ui.item.value;
					if(table) table.fnFilter( ui.item.value, columnIndexes.period );
					if(personalBestsCallFn) personalBestsCallFn();
				},
				selectClass: 'filter-highlight'
			});
		});
		
		// filter event name
		if(eventNameFilter) {
			WPA.setupInputFilter('filterEventName', 'filterEventNameCancel', eventNameFilter);
		}
	},
	
	/**
	 * configures the dialogs for user profile and event results
	 */
	setupDialogs: function() {
		// add items to combos
		jQuery(WPA.globals.eventCategories).each(function(index, item) {
			jQuery("#profileFilterEvent").append('<option value="' + item.id + '">' + item.name + '</option>');
		});
		
		jQuery(WPA.globals.eventTypes).each(function(index, item) {
			jQuery("#profileFilterType").append('<option value="' + item.id + '">' + item.description + '</option>');
		});
		
		jQuery(WPA.globals.ageCategories).each(function(index, item) {
			jQuery("#profileFilterAge").append('<option value="' + item.id + '">' + item.description + '</option>');
		});
		
		// filter event combo
		jQuery("#profileFilterEvent").combobox({
			select: function(event, ui) {
				WPA.profileFilterEvent = ui.item.value;
				WPA.resultsTable.fnFilter( ui.item.value, 5 );
			},
			selectClass: 'filter-highlight'
		});

		// filter type combo
		jQuery("#profileFilterType").combobox({
			select: function(event, ui) {
				WPA.profileFilterType = ui.item.value;
				WPA.resultsTable.fnFilter( ui.item.value, 4 );
				WPA.getPersonalBests();
			},
			selectClass: 'filter-highlight'
		});

		// filter age combo
		jQuery("#profileFilterAge").combobox({
			select: function(event, ui) {
				WPA.profileFilterAge = ui.item.value;
				WPA.resultsTable.fnFilter( ui.item.value, 6 );
				WPA.getPersonalBests();
			},
			selectClass: 'filter-highlight'
		});
		
		// filter event name
		WPA.setupInputFilter('profileFilterEventName', 'profileFilterEventNameCancel', WPA.doUserProfileEventNameFilter);
	},
	
	/**
	 * configures an input filter element by providing an element ID and action function
	 */
	setupInputFilter: function(elId, cancelBtnId, actionFn) {
		// filter event name
		jQuery('#' + elId).keyup(function(e) {
		    if(e.which == 13) {
		    	actionFn();
		    }
		    
		    var highlightClass = jQuery(this).attr('highlight-class');

		    if(jQuery(this).val() != '') {
		    	if(highlightClass) {
		    		jQuery(this).addClass(highlightClass).removeClass('ui-state-default');
		    	}
				jQuery('#' + cancelBtnId).show();
		    }
		    else {
		    	if(highlightClass) {
		    		jQuery(this).removeClass(highlightClass).addClass('ui-state-default');
		    	}
		    	jQuery('#' + cancelBtnId).hide();
		    }
		});

		jQuery('#' + cancelBtnId).click(function() {
			jQuery(this).hide();
			
			var highlightClass = jQuery('#' + elId).attr('highlight-class');
			if(highlightClass) {
				jQuery('#' + elId).removeClass(highlightClass).addClass('ui-state-default');
			}
			
			jQuery('#' + elId).val('').blur();
			actionFn();
		});
	},
	
	/**
	 * sets up common javascript listeners for both 'records' and 'my results' features
	 */
	setupCommon: function() {
		
		// set up tabs
		jQuery('.wpa-results-tabs').tabs({
			activate: function( event, ui ) {
				var suffix = WPA.userProfileDialog && WPA.userProfileDialog.dialog("isOpen") ? '-dialog' : '';
				
				if(ui.newPanel[0].attributes['wpa-tab-type'] && ui.newPanel[0].attributes['wpa-tab-type'].value == 'pb') {
					jQuery('.filter-ignore-for-pb' + suffix).hide();
				}
				else {
					jQuery('.filter-ignore-for-pb' + suffix).show();
				}
			}
		});
		
		// apply focus/blur functions to any search fields
	    WPA.setupSearchFields();

		// setup search
		WPA.setupAutocompleteSearch();

		// setup dialogs
		WPA.setupDialogs();
		
		// tooltips on add results dialog
		jQuery(document).tooltip({
			track: true
		});
	},
	
	/**
	 * performs filtering of event name on the user profile dialog
	 */
	doUserProfileEventNameFilter: function() {
		var defaultText = jQuery('#profileFilterEventName').attr('default-text');
		var val = jQuery('#profileFilterEventName').val();
		if(val != '' && defaultText != val) {
			WPA.profileFilterEventName = val;
			WPA.resultsTable.fnFilter( val, 2 );
		}
		else {
			WPA.profileFilterEventName = null;
			WPA.resultsTable.fnFilter( '', 2 );
		}
	},
	
	/** DATATABLE COLUMN RENDERERS **/
	renderTimeColumn: function(data, type, full) {
		return WPA.displayEventTime(data, full['time_format']);
	},
	
	renderGarminColumn: function (data, type, full) {
		return data ? '<a target="new" href="http://connect.garmin.com/activity/' + data + '" class="datatable-icon garmin" title="' + WPA.getProperty('garmin_link_text') + '">&nbsp;</a>' : '';
	},
	
	renderDeleteEditResultColumn: function (data, type, full) {
		return '<div class="datatable-icon delete" onclick="WPA.MyResults.deleteResult(' + data + ')" title="' + WPA.getProperty('delete_result_text') + '"></div>' +
		'&nbsp;<div class="datatable-icon edit" onclick="WPA.MyResults.editResult(' + data + ')" title="' + WPA.getProperty('edit_result_text') + '"></div>';
	},
	
	renderAgeCategoryColumn: function(data, type, full) {
		return '<div class="datatable-center" title="' + WPA.getAgeCategoryDescription(data) + '">' + data + '</div>';
	},
	
	renderEventTypeColumn: function(data, type, full) {
		return WPA.getEventSubTypeDescription(data);
	},
	
	renderProfileLinkColumn: function(data, type, full) {
		return '<div class="wpa-link" onclick="WPA.displayUserProfileDialog(' + full['user_id'] + ')">' + data + '</div>';
	},
	
	renderEventLinkColumn: function(data, type, full) {
		return '<div class="wpa-link" onclick="WPA.displayEventResultsDialog(' + full['event_id'] + ')">' + data + '</div>';
	},
	
	renderTop10LinkColumn: function(data, type, full) {
		return '<div class="wpa-link" onclick="WPA.Records.displayEventTop10Dialog(' + data + ', \'' + full['category'] + '\')">' + WPA.getProperty('column_top_10') + '</div>';
	},
	
	renderPositionColumn: function(data, type, full) {
		if(parseInt(data) > 0) {
			return data
		}
		return '-';
	}
};