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
				"sEmptyTable": WPA.getProperty('table_no_results'),
				"sProcessing": WPA.getProperty('table_loading_message')
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
			milliseconds: Math.round(((milli % 1000) / 10)),
			seconds: Math.floor((milli / 1000) % 60),
			minutes: Math.floor((milli / (60 * 1000)) % 60),
			hours: Math.floor((milli / ( 1000 * 60 * 60)) % 24)
		}
	},
	
	/**
	 * Converts supplied hours,minute,second and millisecond values into a total milliseconds result
	 */
	timeToMilliseconds: function(hours, minutes, seconds, milliseconds) {
		
		hours = parseInt(hours);
		minutes = parseInt(minutes);
		seconds = parseInt(seconds);
		milliseconds = Math.round(parseInt(milliseconds) * 10);
		
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
	 * Converts a time for a given distance to a pace in miles/km.
	 */
	timeToPace: function(milliseconds, meters, format, includeFormat) {
        // 1. convert millis to minutes
	    var mins = ((milliseconds / (60 * 1000)));

        // 2. convert distance to miles/km
        var distance = meters / 1000;
        if(format && format == 'm') {
            distance = distance * 0.621371;
        }

        // 3. calculate minute/second pace
        var pace = mins / distance;
        
        var paceMins = Math.floor(pace);
        var paceSeconds = paceMins > 0 ? (Math.floor((pace % paceMins) * 60)) : (Math.floor(pace * 60));
        
        if(paceSeconds < 10) {
        	paceSeconds = '0' + paceSeconds;
        }

        return paceMins + ':' + paceSeconds  + (includeFormat ? (' ' + WPA.getProperty('pace_minute') + '/' + WPA.getProperty('pace_' + format)) : '');
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
		
		if(parseInt(value) > 0) {
			if(format && format != '') {
				
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
					else if(f == 'ms') {
	
						if(time.milliseconds < 10) {
							returnValue += '0' + time.milliseconds + ':'
						}
						else {
							returnValue += time.milliseconds + ':'
						}
					}
				})
				return returnValue != '' ? returnValue.substring(0, returnValue.length -1) : WPA.getProperty('time_invalid_text');
			}
		}
		else {
			return WPA.getProperty('time_no_value_text'); 
		}
	},
	
	/**
	 * Returns age category description object based on ID
	 */
	getAgeCategoryDescription: function(id) {		
		var result = '';
		if(WPA.globals.ageCategories) {
			jQuery.each(WPA.globals.ageCategories, function(cat,detail) {
				if(cat == id) {
					result = detail.name;
					return false;
				}
			});
		}
		return result;
	},
	
	/**
	 * Converts a period paramters into a friendly description
	 */
	getPeriodDescription: function(period) {
		if(period == 'all') {
			return WPA.getProperty('filter_period_option_all');
		}
		else if(period == 'this_year') {
			var date = new Date();
			return date.getFullYear();
		}
		else if(period == 'this_month') {
			var date = new Date();
			return jQuery.datepicker.formatDate('MM yy', new Date());
		}
		else if(period.indexOf('year:') > -1) {
			return period.substring(period.indexOf('year:')+5, period.length);
		}
	},
	
	/**
	 * Returns event sub type description based on ID
	 */
	getEventSubTypeDescription: function(id) {
		
		if(id == 'all') {
			return WPA.getProperty('filter_type_option_all');
		}
		
		var result = '';
		if(WPA.globals.eventTypes) {
			jQuery.each(WPA.globals.eventTypes, function(type,name) {
				if(type == id) {
					result = name;
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
		
		// is the profile for this user already open?
		if(WPA.userProfileDialog && jQuery("#user-profile-dialog").dialog("isOpen") && WPA.currentUserProfileId == WPA.dialogProfileId) {
			WPA.userProfileDialog.dialog("close");
			WPA.userProfileDialog.dialog("open");
		}
		else {
			WPA.toggleLoading(true);
			var firstTimeLoad = !WPA.userProfileDialog;
			if(firstTimeLoad) {
				this.createUserProfileDatatables(userId);
				
				WPA.userProfileDialog = jQuery('#user-profile-dialog').dialog({
					title: this.getProperty('user_profile_dialog_title'),
					autoOpen: false,
					resizable: false,
					modal: true,
					maxWidth: jQuery(document).width()-100,
					height: 'auto',
					width: 'auto',
					maxHeight: jQuery(window).height()-100
				})
			}
			// get personal bests
			WPA.getPersonalBests(true);
			
			// get user profile info
			WPA.Ajax.getUserProfile(userId, function(result) {
				// reset fields
				jQuery('#wpa-profile-age-class').html('');
				jQuery('#wpa-profile-dob').html('');
				
				jQuery('#wpa-profile-name').html(result.name);
				jQuery('#wpa-profile-fave-event').html(WPA.getEventCategoryDescription(result.faveEvent));
				
				if(result.dob != '' && result.gender != '') {
					jQuery('#wpa-profile-dob').html(result.dob);
					//jQuery('#wpa-profile-dob').attr('title', result.dob);
					var ageCat = WPA.calculateCurrentAthleteAgeCategory(result.dob);
					if(ageCat) {
						jQuery('#wpa-profile-age-class').html(WPA.getProperty('gender_' + result.gender) + ' ' + ageCat.name);
					}
				}
				
				if(result.photo) {
					jQuery('#wpaUserProfilePhoto').removeClass('wpa-profile-photo-default').css('background-image', 'url(' + result.photo + ')');
				}
				else {
					jQuery('#wpaUserProfilePhoto').addClass('wpa-profile-photo-default')
				}
				if(!firstTimeLoad) {
					WPA.resultsTable.fnDraw(false);
				}
			})
			
			// set up the period filter
			WPA.Ajax.getUserOldestResultYear(userId, function(result) {
				
				// remove old values
				jQuery('#profileFilterPeriod option[year="y"]').remove();
	
				if(result) {
					var userYear = parseInt(result);
					var currentYear = new Date().getFullYear()-1;
					if(currentYear >= userYear) {
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
		}
	},
	
	/**Äÿ
	 * Opens the add result dialog
	 */
	openAddResultDialog: function(id) {
		jQuery("#addResultId").val('');
		jQuery("#add-result-dialog").dialog("option", "title", WPA.getProperty('add_result_title'));
		jQuery("#add-result-dialog").dialog("open");
		WPA.resetAddResultForm();
	},
	
	/**
	 * Validates and launches the add result dialog. If eventId is supplied, the event details will be populated
	 */
	launchAddResultDialog: function(eventId, isEmbedded) {
		if(WPA.isLoggedIn) {
			if(WPA.userGender != '' && WPA.userDOB != '') {
				if(eventId) {
					WPA.Ajax.validateEventEntry(eventId, function() {
						WPA.globals.embeddedResultTableId = eventId;
						WPA.openAddResultDialog();
						if(eventId) {
							WPA.Ajax.getEventInfo(eventId, WPA.loadEventInfoCallback);
						}
					});
				}
				else {
					WPA.openAddResultDialog();
				}
			}
			else {
				WPA.alertError(WPA.getProperty('error_add_result_no_gender_dob'));
			}
		}
		else {
			WPA.alertError(WPA.getProperty('error_not_logged_in'));
		}
		return false;
	},
	
	/**
	 * Displays the event results dialog
	 */
	displayEventResultsDialog: function(eventId) {
		
		WPA.globals.currentEventResultsId = eventId;
		
		if(!WPA.eventResultsDialog) {
			this.createEventResultsDatatables();
			
			WPA.eventResultsDialog = jQuery('#event-results-dialog').dialog({
				title: this.getProperty('event_results_dialog_title'),
				autoOpen: false,
				resizable: false,
				modal: true,
				width: 'auto',
				height: 'auto',
				resizable: false,
				maxHeight: 600
			})
			
			// add new result button
			if(WPA.isLoggedIn && !WPA.isAdminScreen) {
				jQuery('#wpa-event-info-add-result').button({
					icons: {
		              primary: 'ui-icon-circle-plus'
		            }
				}).click(function() {
					WPA.launchAddResultDialog(WPA.globals.currentEventResultsId);
				});
			}
			else {
				jQuery('#wpa-event-info-add-result').hide();
			}
		}
		
		WPA.Ajax.getEventInfo(eventId, function(result) {
			jQuery('#eventInfoName').html(result.name + ', ' + result.location);
			jQuery('#eventInfoDate').html(result.date);
			jQuery('#eventInfoDetail').html(WPA.getEventSubTypeDescription(result.sub_type_id) + ' ' + WPA.getEventCategoryDescription(result.event_cat_id));
		})
		
		// load the events
		WPA.loadEventResults();
	},
	
	/**
	 * Reload either the embedded or dialog results
	 */
	reloadEventResults: function() {
		if(WPA.eventResultsDialog && WPA.eventResultsDialog.dialog("isOpen")) {
			WPA.loadEventResults();
		}
		else if(WPA.globals.embeddedResultTableId) {
			WPA.loadEmbeddedEventResults(WPA.globals.embeddedResultTableId);
			WPA.globals.embeddedResultTableId = undefined;
		}
	},
	
	/**
	 * Loads the results into an embedded results table using a shortcode
	 */
	loadEmbeddedEventResults: function(id) {
		
		// load event details
		WPA.Ajax.getEventInfo(id, function(result) {
			jQuery('#eventInfoName' + id).html(result.name + ', ' + result.location);
			jQuery('#eventInfoDate' + id).html(result.date);
			jQuery('#eventInfoDetail' + id).html(WPA.getEventCategoryDescription(result.event_cat_id) + ' - ' + WPA.getEventSubTypeDescription(result.sub_type_id));
			jQuery('#wpa-event-results-info-' + id).show();
		})
		
		// load event results
		WPA.Ajax.getEventResults(id, function(result, returnId) {
			WPA.embeddedEventTables[returnId].fnClearTable();
			WPA.embeddedEventTables[returnId].fnAddData(result);
		});
	},
	
	/**
	 * Loads the results into the event results dialog
	 */
	loadEventResults: function() {
		WPA.toggleLoading(true);
		WPA.Ajax.getEventResults(WPA.globals.currentEventResultsId, function(result) {
			WPA.eventResultsTable.fnClearTable();
			WPA.eventResultsTable.fnAddData(result);
			WPA.eventResultsDialog.dialog("close");
			WPA.eventResultsDialog.dialog("open");
			WPA.toggleLoading(false);
		});
	},
	
	/**
	 * Loads personal bests
	 */
	getPersonalBests: function(disableLoading) {
		WPA.Ajax.getPersonalBests(function(result) {
			WPA.pbTable.fnClearTable();
			WPA.pbTable.fnAddData(result);
		}, {
			userId: WPA.currentUserProfileId,
			ageCategory: WPA.profileFilterAge,
			eventSubTypeId: WPA.profileFilterType,
			eventDate: WPA.profileFilterPeriod,
			showAllCats: true
		}, disableLoading);
	},
	
	embeddedEventTables: [],
	
	/**
	 * Creates the event results datatables
	 */
	createEventResultsDatatables: function(tableId) {
		
		var elId = tableId ? 'event-results-table-' + tableId : 'event-results-table';

		var table = jQuery('#' + elId).dataTable(WPA.createTableConfig({
			//"sDom": 'rt',
			"bPaginate": false,
			"aaSorting": [[ 1, "asc" ]],
			"sDom": 'rt<"wpa-table-bottom">',
			"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
				// highlight the row if it is one of my results
				if(aData['user_id'] == WPA.userId) {
					jQuery(nRow).addClass('records-highlight-my-result');
				}
			},
			"aoColumns": [{ 
				"mData": "time_format",
				"bVisible": false
			},{ 
				"mData": "rank",
				"bVisible": false
			},{ 
				"mData": "rank",
				"sClass": "datatable-center",
				"bSortable": false
			},{ 
				"mData": "athlete_name",
				"mRender" : WPA.renderProfileLinkColumn,
				"sClass": "datatable-right",
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
				"mData": "age_category",
				"mRender" : WPA.renderAgeCategoryColumn,
				"bSortable": false
			},{ 
				"mData": "position",
				"mRender": WPA.renderPositionColumn,
				"bSortable": false
			},{
				"mData": "garmin_id",
				"sWidth": "16px",
				"mRender": WPA.renderGarminColumn,
				"bSortable": false
			}]
		}));
		
		// store in array
		if(tableId) {
			WPA.embeddedEventTables[tableId] = table;
		}
		else {
			WPA.eventResultsTable = table
		}
	},
	
	/**
	 * Creates the user profile datatables
	 */
	createUserProfileDatatables: function(userId) {

		// Results table
		WPA.resultsTable = jQuery('#results-table').dataTable(WPA.createTableConfig({
			"bProcessing": true,
			"bServerSide": true,
			"fnDrawCallback": function() {
				WPA.toggleLoading(false);
				if(!WPA.userProfileDialog.dialog("isOpen") || WPA.currentUserProfileId != WPA.dialogProfileId) {
					console.log('current ID: ' + WPA.currentUserProfileId + ' Display ID: ' + WPA.dialogProfileId);
					WPA.userProfileDialog.dialog("close");
					WPA.userProfileDialog.dialog("open");
					WPA.dialogProfileId = WPA.currentUserProfileId;
				}
			},
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
				"mData": "event_location",
				"mRender" : WPA.renderEventLocationColumn
			},{
				"mData": "event_sub_type_id",
				"mRender" : WPA.renderEventTypeColumn,
				"bVisible": false
			},{
				"mData": "category",
				"mRender": WPA.renderCategoryAndTerrainColumn
			},{
				"mData": "age_category",
				"mRender" : WPA.renderAgeCategoryColumn
			},{
				"mData": "time",
				"mRender": WPA.renderTimeColumn
			},{
				"mData": "time",
				"mRender": WPA.renderPaceMilesColumn,
				"bSortable": false
			},{
				"mData": "position",
				"sWidth": "20px",
				"sClass": "datatable-center",
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
				"mData": "time",
				"mRender": WPA.renderPaceMilesColumn,
				"bSortable": false
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
				"mRender": WPA.renderClubRankColumn,
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
	setupFilters: function(userId, table, personalBestsCallFn, eventNameFilterFn, columnIndexes, athleteNameFilterFn) {
		// add items to combos
		jQuery.each(WPA.globals.eventCategories, function(index, item) {
			jQuery("#filterEvent").append('<option value="' + item.id + '">' + item.name + '</option>');
		});
		
		jQuery.each(WPA.globals.eventTypes, function(type, name) {
			jQuery("#filterType").append('<option value="' + type + '">' + name + '</option>');
		});
		
		jQuery.each(WPA.globals.ageCategories, function(cat, item) {
			jQuery("#filterAge").append('<option value="' + cat + '">' + item.name + '</option>');
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
		var period = columnIndexes ? columnIndexes.period : null;
		if(userId) {
			WPA.Ajax.getUserOldestResultYear(userId, function(result) {
				WPA.setupPeriodFilter('filterPeriod', result, table, columnIndexes, personalBestsCallFn);
			});
		}
		else {
			WPA.setupPeriodFilter('filterPeriod', 1900, table, columnIndexes, personalBestsCallFn);
		}
		
		// filter event name
		if(eventNameFilterFn) {
			WPA.setupInputFilter('filterEventName', 'filterEventNameCancel', eventNameFilterFn);
		}
		
		// filter athlete name
		if(athleteNameFilterFn) {
			WPA.setupInputFilter('filterAthleteName', 'filterAthleteNameCancel', athleteNameFilterFn);
		}
	},
	
	/**
	 * Sets up a filter combobox and adds options based on the max year supplied
	 */
	setupPeriodFilter: function(elId, maxYear, table, index, personalBestsCallFn) {
		if(maxYear) {
			var userYear = parseInt(maxYear);
			var currentYear = new Date().getFullYear()-1;
			if(currentYear >= userYear) {
				for(var year = currentYear; year >= userYear; year--) {
					jQuery("#" + elId).append('<option year="y" value="year:' + year + '">' + year + '</option>');
				}
			}
		}
		
		// filter period combo
		jQuery("#" + elId).combobox({
			select: function(event, ui) {
				WPA.filterPeriod = ui.item.value;
				if(table) table.fnFilter( ui.item.value, index.period );
				if(personalBestsCallFn) personalBestsCallFn();
			},
			selectClass: 'filter-highlight'
		});
	},
	
	/**
	 * configures the dialogs for user profile and event results
	 */
	setupDialogs: function() {
		// add items to combos
		if(WPA.globals.eventCategories) {
			jQuery.each(WPA.globals.eventCategories, function(index, item) {
				jQuery("#profileFilterEvent").append('<option value="' + item.id + '">' + item.name + '</option>');
			});
		}
		
		if(WPA.globals.eventTypes) {
			jQuery.each(WPA.globals.eventTypes, function(type, name) {
				jQuery("#profileFilterType").append('<option value="' + type + '">' + name + '</option>');
			});
		}
		
		if(WPA.globals.ageCategories) {
			jQuery.each(WPA.globals.ageCategories, function(cat, item) {
				jQuery("#profileFilterAge").append('<option value="' + cat + '">' + item.name + '</option>');
			});
		}
		
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

		// create rankings table
		WPA.createRankingsDataTable();
	},
	
	/**
	 * Loads rankings for a given event
	 */
	getRankingsPersonalBests: function(params, hideRankOptions, callbackFn) {
		if(hideRankOptions) {
			jQuery('#rankingsDisplayOptions').hide();
		}
		else {
			jQuery('#rankingsDisplayOptions').show();
		}
		WPA.globals.rankingParams = params;
		
		WPA.Ajax.getPersonalBests(function(result) {
			WPA.rankingsTable.fnClearTable(false);
			WPA.rankingsTable.fnAddData(result);
			
			if(WPA.rankingsDialog && !WPA.rankingsDialog.dialog('isOpen')) {
				WPA.rankingsDialog.dialog('open');
			}
			
			if(callbackFn) {
				callbackFn();
			}
			
		}, params);
	},
	
	/**
	 * Creates the rankings datatable
	 */
	createRankingsDataTable: function() {
		WPA.rankingsTable = jQuery('#table-rankings').dataTable(WPA.createTableConfig({
			"sDom": 'rt',
			"bPaginate": false,
			"aaSorting": [[ 1, "asc" ]],
			"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
				// highlight the row if it is one of my results
				if(aData['user_id'] == WPA.userId) {
					jQuery(nRow).addClass('records-highlight-my-result');
				}
				// separate top 10 rankings using strong line
				if(parseInt(aData['rank']) == 10) {
					jQuery(nRow).addClass('records-top-10-seperator');
				}
			},
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
				"mRender": WPA.renderClubRankColumnNoLink,
				"sClass": "datatable-center"
			},{
				"mData": "time",
				"mRender": WPA.renderTimeColumn,
				"sClass": "datatable-bold",
				"bSortable": false
			},{
				"mData": "time",
				"mRender": WPA.renderPaceMilesColumn,
				"bSortable": false
			},{
				"mData": "athlete_name",
				"mRender" : WPA.renderProfileLinkColumn,
				"bSortable": false
			},{ 
				"mData": "event_name",
				"mRender" : WPA.renderEventLinkColumnNoStrip,
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
			},{
				"mData": "age_category",
				"mRender" : WPA.renderAgeCategoryColumn,
				"bSortable": false
			},{
				"mData": "garmin_id",
				"sWidth": "16px",
				"mRender": WPA.renderGarminColumn,
				"bSortable": false
			}]
		}));
		
		// setup rankings dialog
		WPA.rankingsDialog = jQuery("#rankingsDialog").dialog({
			autoOpen: false,
			resizable: false,
			modal: true,
			width: 'auto',
			height: 500
		});
		
		// set up refresh action on display radio options
		jQuery('input[type="radio"]', '#rankings-display-form').change(function() {
			WPA.reloadEventRankings();
		})
	},
	
	/**
	 * Refreshes the top rankings when the display type is changed
	 */
	reloadEventRankings: function() {
		WPA.globals.rankingParams.rankingDisplay = jQuery('input[name=rankings-display-mode]:checked', '#rankings-display-form').val();
		WPA.getRankingsPersonalBests(WPA.globals.rankingParams);
	},
	
	/**
	 * Displays the rankings table for a given age category
	 */
	displayRecordsEventRankingsDialog: function(eventCatId) {
		WPA.globals.rank = 0;
		WPA.globals.currentRankingsCatId = eventCatId;
		
		// set title of dialog
		if(eventCatId) {
			WPA.rankingsDialog.dialog('option', 'title', WPA.generateRankingsDialogTitle(eventCatId, WPA.Records.gender, WPA.Records.currentCategory));	
		}
		
		// perform ajax call to get rankings
		WPA.getRankingsPersonalBests({
			ageCategory: WPA.Records.currentCategory,
			gender: WPA.Records.gender,
			eventSubTypeId: WPA.filterType,
			eventDate: WPA.filterPeriod,
			eventCategoryId: eventCatId,
			rankingDisplay: jQuery('input[name=rankings-display-mode]:checked', '#rankings-display-form').val()
		});
	},
	
	/**
	 * Generates the title of the top 10 dialog by replacing tokens in the string literal
	 */
	generateRankingsDialogTitle: function(category, gender, ageCategory, allTimeAllTerrain) {
		var title =  WPA.getProperty('rankings_dialog_title');
		
		// category
		title = title.replace('[category]', WPA.getEventName(category));
		
		// gender
		title = title.replace('[gender]', WPA.getGenderDescription(gender));
		
		// type
		title = title.replace('[type]', allTimeAllTerrain ? WPA.getProperty('filter_type_option_all') : jQuery('#filterType').combobox('getLabel'));
		
		// age 
		title = title.replace('[age]', WPA.getAgeCategoryDescription(ageCategory));
		
		// period
		title = title.replace('[period]', allTimeAllTerrain ? WPA.getProperty('filter_period_option_all') : jQuery('#filterPeriod').combobox('getLabel'));
		
		return title;
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
		}).blur(function() {
			actionFn();
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
		
		if(!WPA.commonSetup) {

			// document tooltips
			jQuery(document).tooltip({
				//track: true
			});
	
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
			
			// remove any open combo box menus when clicked away from it
			jQuery(document).click(function(e) {
				if(e.srcElement && e.srcElement.className && e.srcElement.className.indexOf('ui-button') == -1 && e.srcElement.className.indexOf('custom-combobox-input') == -1) {
					jQuery('.ui-autocomplete').each(function() {
						if(jQuery(this).is(':visible')) {
							jQuery(this).hide();
						}
					});
				}
			});
			
			// create loading dialog
			WPA.createLoadingDialog();
			
			// apply focus/blur functions to any search fields
		    WPA.setupSearchFields();
	
			// setup search
			WPA.setupAutocompleteSearch();
	
			// setup dialogs
			WPA.setupDialogs();
			
			WPA.commonSetup = true;
		}
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
	
	/**
	 * Retrieves a WPA setting and uses a default value if not found
	 */
	getSetting: function(key, defaultValue) {
		if(WPA.Settings[key]) {
			return WPA.Settings[key];
		}
		return defaultValue ? defaultValue : false;
	},
	
	/**
	 * Determines what category an athlete runs in based on a a date and their date of birth
	 */
	calculateAthleteAgeCategory: function(date, dob, doParse) {	
		if(doParse) {
			dob = jQuery.datepicker.parseDate( WPA.getSetting('display_date_format'),  dob );
			date = jQuery.datepicker.parseDate( WPA.getSetting('display_date_format'),  date );
		}
		
		var age = this.howOld(date, dob);
		var ageCat = { name: '' };
		
		// loops age classes and determine which category applies
		jQuery.each(WPA.globals.ageCategories, function(cat, details) {
			if(age >= parseInt(details.from) && age < parseInt(details.to)) {
				details.id = cat;
				ageCat = details;
				return false;
			}
		});
		
		return ageCat;
	},
	
	/**
	 * Determines what category an athlete runs in based on their date of birth
	 */
	calculateCurrentAthleteAgeCategory: function(dob) {
		if(dob != '') {
			dob = jQuery.datepicker.parseDate( WPA.getSetting('display_date_format'),  dob );
			return this.calculateAthleteAgeCategory(new Date(), dob);
		}
		return null;
	},
	
	/**
	 * Checks if a given input is a positive number
	 */
	isPositiveNumber: function (num, allowZero) {
		return jQuery.isNumeric(num) && (allowZero ? (parseInt(num) >= 0) : (parseInt(num) > 0) );
	},

	/**
	 * Calculates athlete age in years at a given date
	 */
	howOld: function(varAsOfDate, varBirthDate) {
	   var dtAsOfDate;
	   var dtBirth;
	   var dtAnniversary;
	   var intYears;
	   var intMonths;

	   // get born date
	   dtBirth = new Date(varBirthDate);
	   
	   // get as of date
	   dtAsOfDate = new Date(varAsOfDate);

	   // if as of date is on or after born date
	   if ( dtAsOfDate >= dtBirth )
	      {

	      // get time span between as of time and birth time
	      intSpan = ( dtAsOfDate.getUTCHours() * 3600000 +
	                  dtAsOfDate.getUTCMinutes() * 60000 +
	                  dtAsOfDate.getUTCSeconds() * 1000    ) -
	                ( dtBirth.getUTCHours() * 3600000 +
	                  dtBirth.getUTCMinutes() * 60000 +
	                  dtBirth.getUTCSeconds() * 1000       )

	      // start at as of date and look backwards for anniversary 

	      // if as of day (date) is after birth day (date) or
	      //    as of day (date) is birth day (date) and
	      //    as of time is on or after birth time
	      if ( dtAsOfDate.getUTCDate() > dtBirth.getUTCDate() ||
	           ( dtAsOfDate.getUTCDate() == dtBirth.getUTCDate() && intSpan >= 0 ) )
	         {

	         // most recent day (date) anniversary is in as of month
	         dtAnniversary = 
	            new Date( Date.UTC( dtAsOfDate.getUTCFullYear(),
	                                dtAsOfDate.getUTCMonth(),
	                                dtBirth.getUTCDate(),
	                                dtBirth.getUTCHours(),
	                                dtBirth.getUTCMinutes(),
	                                dtBirth.getUTCSeconds() ) );

	         }

	      // if as of day (date) is before birth day (date) or
	      //    as of day (date) is birth day (date) and
	      //    as of time is before birth time
	      else {

	         // most recent day (date) anniversary is in month before as of month
	         dtAnniversary = 
	            new Date( Date.UTC( dtAsOfDate.getUTCFullYear(),
	                                dtAsOfDate.getUTCMonth() - 1,
	                                dtBirth.getUTCDate(),
	                                dtBirth.getUTCHours(),
	                                dtBirth.getUTCMinutes(),
	                                dtBirth.getUTCSeconds() ) );

	         // get previous month
	         intMonths = dtAsOfDate.getUTCMonth() - 1;
	         if ( intMonths == -1 )
	            intMonths = 11;

	         // while month is not what it is supposed to be (it will be higher)
	         while ( dtAnniversary.getUTCMonth() != intMonths )
	            // move back one day
	            dtAnniversary.setUTCDate( dtAnniversary.getUTCDate() - 1 );
	      }

	      // if anniversary month is on or after birth month
	      if ( dtAnniversary.getUTCMonth() >= dtBirth.getUTCMonth() ) {
	         // years elapsed is anniversary year - birth year
	         intYears = dtAnniversary.getUTCFullYear() - dtBirth.getUTCFullYear();
	      }

	      // if birth month is after anniversary month
	      else {
	         // years elapsed is year before anniversary year - birth year
	         intYears = (dtAnniversary.getUTCFullYear() - 1) - dtBirth.getUTCFullYear();
	      }
	   }
	   return intYears;
	},
	
	/**
	 * Opens error dialog with custom text
	 */
	alertError: function(text) {
		jQuery("#wpa-error-dialog-text").html(text);
		jQuery("#wpa-error-dialog").dialog({
	      resizable: false,
	      height:'auto',
	      width: 450,
	      modal: true,
	      buttons: {
	        "Ok": function() {
	          jQuery( this ).dialog("close");
	        }
	      }
	    });
	},
	
	/**
	 * Reads an array of error messages and displays in list format
	 */
	alertErrors: function(errors) {
		var html = '<ul>';
		jQuery.each(errors, function(index, error) {
			html+= '<li>' + error + '</li>';
		});
		html+= '</ul>';
		WPA.alertError(html);
	},
	
	/**
	 * Creates the loading dialog
	 */
	createLoadingDialog: function() {
		WPA.loadingDialog = jQuery("#wpa-loading-dialog").dialog({
	      resizable: false,	
	      dialogClass: 'wpa-dialog-no-title',
	      draggable: false,
	      autoOpen: false,
	      width: 'auto',
	      height: 80,
	      //hide: 1000,
	      modal: true,
	      open: function (event, ui) {
	        jQuery("#wpa-loading-dialog").css('overflow', 'hidden');
	      }
	    });
	},
	
	/**
	 * Shows or hides the loading dialog
	 */
	toggleLoading: function(show) {
		if(show) {
			if(WPA.loadingDialog && !jQuery(WPA.loadingDialog).dialog("isOpen")) {
				jQuery(WPA.loadingDialog).dialog("open");
			}
		}
		else {
			jQuery(WPA.loadingDialog).dialog("close");
		}
	},
	
	/**
	 * Shows or hides a processing message for loading pbs, since this is manually loaded
	 */
	togglePbLoading: function(show) {
		if(show) {
			jQuery('#wpa-pb-table-processing').show().center();
		}
		else {
			jQuery('#wpa-pb-table-processing').hide();
		}
	},
	
	/**
	 * Read in a distance and a unit and returns the distance in meters
	 */
	calculateDistanceInMeters: function(distance, unit) {
		if(unit == 'mile') {
			return parseFloat(distance) * 1609.34;
		}
		else if(unit == 'km') {
			return parseFloat(distance) * 1000;
		}
		return distance;
	},
	
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
			jQuery('#addResultAgeCategoryDisplay, #addResultAgeCategory').val('');
			jQuery('#addResultEventLocation').val('');
		}
		jQuery('#add-result-dialog .ui-datepicker-trigger').toggle(enable);
		jQuery('.add-result-cancel-event').toggle(!enable);
		jQuery('#addResultEventName').prop('disabled', !enable);
		jQuery('#addResultDate').prop('disabled', !enable);
		jQuery('#addResultEventLocation').prop('disabled', !enable);
		
		// selects
		jQuery('#addResultEventSubType').combobox('disabled', !enable);
		jQuery('#addResultEventCategory').combobox('disabled', !enable);
	},
	
	/**
	 * Validates the add/edit result form and adds error class if required
	 */
	validateAddEditForm: function(parentDivId) {
		var valid = true;
		
		var requiredFields = jQuery('#' + parentDivId + ' .add-result-required');
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
	 * Opens the dialog to edit an event result
	 */
	editResult: function(id, userId) {
		WPA.globals.currentEditUserId = userId;
		WPA.toggleLoading(true);
		jQuery("#add-result-dialog").dialog("option", "title", WPA.getProperty('edit_result_title'));
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
		
		if(WPA.lastUsedEventCat != result.event_cat_id) {
			WPA.triggerAddEventCategoryChange();
		}
		WPA.toggleAddResultEvent(false);
		WPA.setAddResultAgeCategory(function() {
			WPA.toggleAddResultEvent(true);
		});
		
		WPA.lastUsedEventCat = result.event_cat_id;
	},

	/**
	 * loads the result information onto the update fields
	 */
	setResultUpdateInfo: function(result) {
		// load the event info
		WPA.Ajax.getEventInfo(result.event_id, function(_result) {
			WPA.loadEventInfoCallback(_result);
			var time = WPA.millisecondsToTime(result.time);
			jQuery("#addResultId").val(result.id);
			jQuery('#addResultAgeCategoryDisplay').val(WPA.getAgeCategoryDescription(result.age_category));
			jQuery('#addResultAgeCategory').val(result.age_category);
			jQuery('#addResultPosition').val(result.position);
			jQuery('#addResultGarminId').val(result.garmin_id);
			jQuery('#addResultTimeHours').val(time.hours);
			jQuery('#addResultTimeMinutes').val(time.minutes);
			jQuery('#addResultTimeSeconds').val(time.seconds);
			jQuery('#addResultTimeMilliSeconds').val(time.milliseconds);
			
			WPA.toggleLoading(false);
			jQuery("#add-result-dialog").dialog("open");
		});
	},
	
	/**
	 * Triggers when the date field has changed on the "add result" screen, determines which age category the result falls into
	 */
	setAddResultAgeCategory: function(failCallbackFn) {
		if(WPA.userDOB) {
			WPA.processResultAgeCategory(failCallbackFn, WPA.userDOB);
		}
		else if(WPA.globals.currentEditUserId) {
			// get the user DOB
			WPA.Ajax.getUserDOB(WPA.globals.currentEditUserId, function(result) {
				WPA.processResultAgeCategory(failCallbackFn, result);
			});
		}
	},
	
	/**
	 * Performs the calculations to determine which age catgory the result fits into
	 */
	processResultAgeCategory: function(failCallbackFn, userDOB) {
	    var ageCat = WPA.calculateAthleteAgeCategory(jQuery('#addResultDate').val(), userDOB, true);
	    if(ageCat && ageCat.id) {
    		jQuery('#addResultAgeCategoryDisplay').val(ageCat.name);
    		jQuery('#addResultAgeCategory').val(ageCat.id);
	    }
	    else {
	    	WPA.alertError(WPA.getProperty('error_no_age_category'));
	    	jQuery('#addResultDate').val('');
	    	if(failCallbackFn) {
	    		failCallbackFn();
	    	}
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
	 * Returns an event time format based on the event category ID
	 */
	getEventTimeFormat: function(eventCatId) {
		var returnVal;
		jQuery(WPA.globals.eventCategories).each(function(index, item) {
			if(item.id == eventCatId) {
				returnVal = item.time_format;
				return false
			}
		});
		return returnVal;
	},
	
	/**
	 * Gets the distance in meters for a given event category ID
	 */
	getEventDistanceMeters: function(eventCatId) {
		var returnVal;
		jQuery(WPA.globals.eventCategories).each(function(index, item) {
			if(item.id == eventCatId) {
				returnVal = item.distance_meters;
				return false
			}
		});
		return returnVal;
	},
	
	/**
	 * Gets the name for a given event category ID
	 */
	getEventName: function(eventCatId) {
		var returnVal;
		jQuery(WPA.globals.eventCategories).each(function(index, item) {
			if(item.id == eventCatId) {
				returnVal = item.name;
				return false
			}
		});
		return returnVal;
	},
	
	/** 
	 * validates and submits the add result form
	 */
	submitResult: function(reloadFn) {

		if(WPA.validateAddEditForm('add-result-dialog')) {
			
			// incase anyone ignore the instructions ;)
			var garminLink = jQuery('#addResultGarminId').val();
			garminLink = garminLink.replace('http://connect.garmin.com/activity/', '');
			
			var timeMillis = WPA.timeToMilliseconds(
				jQuery('#addResultTimeHours').val(),
				jQuery('#addResultTimeMinutes').val(),
				jQuery('#addResultTimeSeconds').val(),
				jQuery('#addResultTimeMilliSeconds').val()
			);
			var distanceMeters = WPA.getEventDistanceMeters(jQuery('#addResultEventCategory').val());
			var paces = WPA.getResultPaces(timeMillis, distanceMeters);
			
			WPA.toggleLoading(true);
			WPA.Ajax.updateResult({
				resultId: jQuery('#addResultId').val(),
				time: timeMillis,
				eventId: jQuery('#addResultEventId').val(),
				eventDate: jQuery('#addResultEventDate').val(),
				eventName: jQuery('#addResultEventName').val(),
				eventCategory: jQuery('#addResultEventCategory').val(),
				eventSubType: jQuery('#addResultEventSubType').val(),
				position: jQuery('#addResultPosition').val(),
				garminId: garminLink,
				ageCategory: jQuery("#addResultAgeCategory").val(),
				eventLocation: jQuery('#addResultEventLocation').val(),
				gender: WPA.userGender,
				paceKm: paces.km,
				paceMiles: paces.miles
			}, function() {
				WPA.toggleLoading(false);
				
				// success function - load the results and close dialog
				WPA.resetAddResultForm();
				
				jQuery("#add-result-dialog").dialog("close");
				
				// reload the results
				reloadFn();
			});
		}
	},
	
	/**
	 * resets the fields in the add result form
	 */
	resetAddResultForm: function() {
		WPA.toggleAddResultEvent(true);
		jQuery('#add-result-dialog form input,select').each(function() {
			jQuery(this).val('').removeClass('ui-state-error');
		});
	},
	
	/**
	 * Sets the values of the hidden pace fields when the time has been changed
	 */
	getResultPaces: function(time, format) {
		return {
			km: WPA.timeToPace(time, format, 'km'),
			miles: WPA.timeToPace(time, format, 'm')
		}
	},
	
	/**
	 * Opens confirm dialog to deletes an event result
	 */
	deleteResult: function(id, deleteCallbackFn) {
		jQuery("#result-delete-confirm").dialog({
	      resizable: false,
	      height:160,
	      modal: true,
	      buttons: {
	        "Delete": function() {
	          jQuery( this ).dialog("close");
	          WPA.Ajax.deleteResult(id, deleteCallbackFn);
	        },
	        Cancel: function() {
	          jQuery( this ).dialog( "close" );
	        }
	      }
	    });
	},
	
	/**
	 * Configure the edit/add event dialog
	 */
	setupEditEventDialog: function(reloadFn) {

		// create event category selection lists
		jQuery(WPA.globals.eventCategories).each(function(index, item) {
			jQuery("#editEventCategory").append('<option time-format="' + item.time_format + '" value="' + item.id + '">' + item.name + '</option>');
		});

		// create event sub type selection list
		jQuery.each(WPA.globals.eventTypes, function(id, name) {
			jQuery("#editEventSubType").append('<option value="' + id + '">' + name + '</option>');
		});

		// add result event combo
		jQuery("#editEventCategory").combobox({
			select: function(event, ui) {
				jQuery(this).combobox('removeCls', 'ui-state-error');
			}
		});

		// add result sub type combo
		jQuery("#editEventSubType").combobox({
			select: function(event, ui) {
				jQuery(this).combobox('removeCls', 'ui-state-error');
			}
		});

		jQuery('#editEventLocation,#editEventName').change(function() {
		    if(jQuery(this).val() != '') {
		    	jQuery(this).removeClass('ui-state-error');
		    }
	    });

		// set 'add result' date picker element
		jQuery('#editResultDate').datepicker({
	      showOn: "both",
	      buttonImage: WPA.globals.pluginUrl + '/resources/images/date_picker.png',
	      buttonImageOnly: true,
	      changeMonth: true,
	      changeYear: true,
	      //maxDate: 0,
	      dateFormat: WPA.getSetting('display_date_format'),
	      yearRange: 'c-100:c+1',
	      altFormat: 'yy-mm-dd',
	      altField: '#editEventDate'
	    }).change(function() {
		    if(jQuery(this).val()) {
		    	jQuery(this).removeClass('ui-state-error');
		    }
	    });

		// the edit dialog
		WPA.editEventDialog = jQuery('#edit-event-dialog').dialog({
			title: WPA.getProperty('edit_event_dialog_title'),
			autoOpen: false,
			resizable: false,
			modal: true,
			height: 'auto',
			width: 'auto',
			buttons: [{
				text: WPA.getProperty('cancel'),
		    	click: function() {
		          jQuery( this ).dialog("close");
		    	}
			},{
		    	text: WPA.getProperty('submit'),
		    	click: function() {
		    		WPA.submitEditEvent(reloadFn);
			    }
		    }]
		});
	},
	
	/**
	 * Validates the edit/add event data and submits ajax request if valid.
	 */
	submitEditEvent: function(reloadFn) {
		if(WPA.validateAddEditForm('edit-event-dialog')) {

			var detail = WPA.gatherEventDetail();
			detail.id = WPA.editId;

			WPA.toggleLoading(true);
			WPA.Ajax.updateEvent(detail, function(createId) {
				WPA.toggleLoading(false);

				jQuery(WPA.editEventDialog).dialog("close");

				// reload
				reloadFn(createId);
			}, WPA.eventCreateMode);
		}
	},
	
	/**
	 * Launches the edit event dialog and loads the event data
	 */
	editEvent: function(id) {
		WPA.editId = id;
		WPA.eventCreateMode = false;

		// populate the event info and open dialog
		WPA.Ajax.getEventInfo(id, function(result) {
			// inputs
			jQuery('#editResultDate').removeClass('ui-state-error').datepicker('setDate', result.date);
			jQuery('#editEventName').removeClass('ui-state-error').val(result.name);
			jQuery('#editEventId').val(result.id);
			jQuery('#editEventLocation').removeClass('ui-state-error').val(result.location).change();

			// selects
			jQuery('#editEventCategory').combobox('setValue', result.event_cat_id).combobox('removeCls', 'ui-state-error');
			jQuery('#editEventSubType').combobox('setValue', result.sub_type_id).combobox('removeCls', 'ui-state-error');

			// open the dialog now
			jQuery(WPA.editEventDialog).dialog('option', 'title', WPA.getProperty('edit_event_dialog_title'));
			jQuery(WPA.editEventDialog).dialog('open');
		});
	},

	/**
	 * Returns an object with the add/edit event form data
	 */
	gatherEventDetail: function() {
		return {
			eventDate: jQuery('#editEventDate').val(),
			eventName: jQuery('#editEventName').val(),
			eventCategory: jQuery('#editEventCategory').val(),
			eventSubType: jQuery('#editEventSubType').val(),
			eventLocation: jQuery('#editEventLocation').val()
		}
	},

	/**
	 * Shows the create event dialog
	 */
	showCreateEventDialog: function() {
		WPA.editId = '';
		WPA.resetEditDialogFields();
		WPA.eventCreateMode = true;
		jQuery(WPA.editEventDialog).dialog('option', 'title', WPA.getProperty('create_event_dialog_title'));
		jQuery(WPA.editEventDialog).dialog('open');
	},

	/**
	 * Resets all the input fields on the edit/add event dialog
	 */
	resetEditDialogFields: function() {
		jQuery('#edit-event-dialog input').val('');
		jQuery('#edit-event-dialog select').val('');
	},
	
	/**
	 * Configure the edit/add result dialog
	 */
	setupEditResultDialog: function(reloadFn) {
		
		if(!WPA.edtiResultDialogSetup) {
			// create event category selection lists
			jQuery(WPA.globals.eventCategories).each(function(index, item) {
				jQuery("#addResultEventCategory, #myProfileFaveEvent").append('<option time-format="' + item.time_format + '" value="' + item.id + '">' + item.name + '</option>');
			});
	
			// create event sub type selection list
			jQuery.each(WPA.globals.eventTypes, function(id, name) {
				jQuery("#addResultEventSubType").append('<option value="' + id + '">' + name + '</option>');
			});
			
			// change event for time fields to validate real time
			var timeFields = jQuery('input[time-format]');
			jQuery.each(timeFields, function() {
				jQuery(this).keyup(function() {
					var value = jQuery(this).val();
					if(value != '' && !WPA.isValidTime(jQuery(this).attr('time-format'), value)) {
						jQuery(this).val('');
					}
					else {
						jQuery(this).removeClass('ui-state-error');
					}
				}).focus(function() {
					jQuery(this).select();
				}).blur(function() {
					if(jQuery(this).val() == '') {
						jQuery(this).val('0');
					}
				});
			});
	
			// change event for position
			jQuery('#addResultPosition').keyup(function() {
				var value = jQuery('#addResultPosition').val();
				if(value != '' && !jQuery.isNumeric(value)) {
					jQuery('#addResultPosition').val('');
				}
			});
	
			// create dialog for adding result
			jQuery("#add-result-dialog").dialog({
				autoOpen: false,
				height: 'auto',
				width: 500,
				modal: true,
				buttons: [{
					text: WPA.getProperty('submit'),
			      	click: function() {
			      		WPA.submitResult(reloadFn);
			      	}
			    },{
				    text: WPA.getProperty('cancel'),
				    click: function() {
				    	jQuery(this).dialog("close");
				    }
			    }
			  ]
			});
	
			// set 'add result' date picker element
			jQuery('#addResultDate').datepicker({
		      showOn: "both",
		      buttonImage: WPA.globals.pluginUrl + '/resources/images/date_picker.png',
		      buttonImageOnly: true,
		      changeMonth: true,
		      changeYear: true,
		      maxDate: 0,
		      dateFormat: WPA.getSetting('display_date_format'),
		      yearRange: 'c-100:c',
		      altFormat: 'yy-mm-dd',
		      altField: '#addResultEventDate'
		    }).change(function() {
			    if(jQuery(this).val() != '' && WPA.userDOB != '') {
			    	jQuery(this).removeClass('ui-state-error');
					WPA.setAddResultAgeCategory();
			    }
		    });
	
		   	// autocomplete on the event name for adding results
		   	jQuery("#addResultEventName").autocomplete({
				source: WPA.Ajax.url + '?action=wpa_event_autocomplete',
				minLength: 2,
				select: function( event, ui ) {
					WPA.Ajax.validateEventEntry(ui.item.value, function() {
						WPA.Ajax.getEventInfo(ui.item.value, WPA.loadEventInfoCallback);
					}, function() {
						jQuery("#addResultEventName").val('');
					})
				}
		    }).focus(function(){
		        this.select();
		    }).keyup(function() {
			    if(jQuery(this).val() != '') {
			    	jQuery(this).removeClass('ui-state-error');
			    }
		    });
		   	
		   	// autocomplete on the event name for adding results
		   	jQuery("#addResultEventLocation").autocomplete({
				source: WPA.Ajax.url + '?action=wpa_location_autocomplete',
				minLength: 2,
				select: function( event, ui ) {
					console.log(ui.item);
				}
		    }).focus(function(){
		        this.select();
		    }).keyup(function() {
			    if(jQuery(this).val() != '') {
			    	jQuery(this).removeClass('ui-state-error');
			    }
		    });
	
		   	// cancel selected event
		    jQuery('.add-result-cancel-event').click(function() {
		    	WPA.toggleAddResultEvent(true);
		    });
	
			// add result button
			jQuery('#wpa-profile-add-result button').button({
				icons: {
	              primary: 'ui-icon-circle-plus'
	            }
			}).click(function() {
				WPA.launchAddResultDialog();
			});
			
			// add result event combo
			jQuery("#addResultEventCategory").combobox({
				select: function(event, ui) {
					jQuery(this).combobox('removeCls', 'ui-state-error');
					WPA.triggerAddEventCategoryChange();
				}
			});
	
			// add result sub type combo
			jQuery("#addResultEventSubType").combobox({
				select: function(event, ui) {
					jQuery(this).combobox('removeCls', 'ui-state-error');
				}
			});
			
			jQuery('#addResultTimeHours,#addResultTimeMinutes,#addResultTimeSeconds,#addResultTimeMilliSeconds').blur(function() {
				if(jQuery(this).val() == '') {
					jQuery(this).val('0');
				}
			});
			
			// add result millis blur listener
			jQuery('#addResultTimeMilliSeconds').blur(function() {
				var value = jQuery(this).val();
				if(value.length == 1 && value != '0') {
					//jQuery(this).val(value + '0');
				}
			});
			WPA.edtiResultDialogSetup = true;
		}
	},
	
	/**
	 * Gets gender description based on an ID
	 */
	getGenderDescription: function(gender) {
		return WPA.getProperty('gender_' + gender);
	},
	
	/** DATATABLE COLUMN RENDERERS **/
	renderTimeColumn: function(data, type, full) {
		var pace = '<div>';
		pace+= '<p>' + WPA.timeToPace(data, full['distance_meters'], 'm', true) + '</p>';
		pace+= '<p>' + WPA.timeToPace(data, full['distance_meters'], 'km', true) + '</p>';
		pace+= '</div>';
		return '<div title="' + pace + '">' + WPA.displayEventTime(data, full['time_format']) + '</div>';
	},
	
	renderPaceMilesColumn: function(data, type, full) {
		if(parseInt(data) > 0) {
			return '<div title="' + WPA.timeToPace(data, full['distance_meters'], 'km', true) + '">' + WPA.timeToPace(data, full['distance_meters'], 'm', true) + '</div>';
		}
		else {
			return WPA.getProperty('time_no_value_text');
		}
	},
	
	renderGarminColumn: function (data, type, full) {
		return data ? '<a target="new" href="http://connect.garmin.com/activity/' + data + '" class="datatable-icon garmin" title="' + WPA.getProperty('garmin_link_text') + '">&nbsp;</a>' : '';
	},
	
	renderCategoryAndTerrainColumn: function(data, type, full) {
		return data + ' ' + WPA.getEventSubTypeDescription(full['event_sub_type_id']);
	},
	
	renderDeleteEditResultColumn: function (data, type, full) {
		return WPA.renderDeleteEditColumn(data, full['user_id'], WPA.getProperty('edit_result_text'), WPA.getProperty('delete_result_text'), 'WPA.editResult', 'WPA.MyResults.deleteResult');
	},
	
	renderAdminDeleteEditResultColumn: function (data, type, full) {
		return '<div class="datatable-checkbox"><input record-id="' + data + '" type="checkbox"/></div>' +
		'<div class="datatable-icon delete" onclick="WPA.Admin.deleteResult(' + data + ',' + full['result_count'] + ')" title="' + WPA.getProperty('delete_result_text') + '"></div>' +
		'&nbsp;<div class="datatable-icon edit" onclick="WPA.editResult(' + data + ',' + full['user_id'] + ')" title="' + WPA.getProperty('edit_result_text') + '"></div>';
	},
	
	renderAdminDeleteEditEventColumn: function(data, type, full) {
		return '<div class="datatable-checkbox"><input result-count="' + full['result_count'] + '" record-id="' + data + '" type="checkbox"/></div>' +
		'<div class="datatable-icon delete" onclick="WPA.Admin.deleteEvent(' + data + ',' + full['result_count'] + ')" title="' + WPA.getProperty('delete_event_text') + '"></div>' +
		'&nbsp;<div class="datatable-icon edit" onclick="WPA.editEvent(' + data + ',' + full['user_id'] + ')" title="' + WPA.getProperty('edit_event_text') + '"></div>';
	},
	
	renderDeleteEditColumn: function(id, userId, editText, deleteText, editFunction, deleteFunction) {
		return '<div class="datatable-icon delete" onclick="' + deleteFunction + '(' + id + ')" title="' + deleteText + '"></div>' +
		'&nbsp;<div class="datatable-icon edit" onclick="' + editFunction + '(' + id + ',' + userId + ')" title="' + editText + '"></div>';
	},
	
	renderAgeCategoryColumn: function(data, type, full) {
		return '<div class="datatable-center">' + WPA.getAgeCategoryDescription(data) + '</div>';
	},
	
	renderEventTypeColumn: function(data, type, full) {
		return WPA.getEventSubTypeDescription(data);
	},
	
	renderProfileLinkColumn: function(data, type, full) {
		return '<div class="wpa-link" onclick="WPA.displayUserProfileDialog(' + full['user_id'] + ')">' + data + '</div>';
	},
	
	renderEventLocationColumn: function(data, type, full) {
		var max = WPA.getSetting('table_max_location_length');
		if(data.length > max) {
			data = data.substring(0,max) + '...';
		}
		return '<div title="' + full['event_location'] + '">' + data + '</div>';
	},
	
	renderEventLinkColumn: function(data, type, full) {
		var max = WPA.getSetting('table_max_event_name_length');
		if(data.length > max) {
			data = data.substring(0,max) + '...';
		}
		return '<div class="wpa-link" title="' + full['event_name'] + '" onclick="WPA.displayEventResultsDialog(' + full['event_id'] + ')">' + data + '</div>';
	},
	
	renderEventLinkColumnNoStrip: function(data, type, full) {
		return '<div class="wpa-link" onclick="WPA.displayEventResultsDialog(' + full['event_id'] + ')">' + data + '</div>';
	},
	
	renderEditEventLinkColumn: function(data, type, full) {
		var max = WPA.getSetting('table_max_event_name_length');
		if(data.length > max) {
			data = data.substring(0,max) + '...';
		}
		return '<div class="wpa-link">' + 
		'<div class="datatable-icon edit" onclick="WPA.editEvent(' + full['event_id'] + ')" title="' + WPA.getProperty('edit_event_text') + '"></div>' + 
		'<span title="' + full['event_name'] + ', ' + full['event_location'] + '" onclick="WPA.displayEventResultsDialog(' + full['event_id'] + ')">' + data + '</span></div>';
	},
	
	renderRankingsLinkColumn: function(data, type, full) {
		return '<a class="datatable-icon rankings" title="' + WPA.getProperty('rankings_link_text') + '" href="javascript:WPA.displayRecordsEventRankingsDialog(' + data + ')"></a>';
	},
	
	renderEventShorcode: function(data) {
		return '[wpa-event id=' + data + ']';
	},
	
	displayTableColumnClubRankings: function(ageCat, eventCat, gender) {

		WPA.rankingsDialog.dialog('option', 'title', WPA.generateRankingsDialogTitle(eventCat, gender, ageCat, true));
		
		jQuery('#best-athlete-result-radio').attr('checked', 'checked');
		var params = {
			ageCategory: ageCat,
			gender: gender,
			eventSubTypeId: 'all',
			eventDate: 'all',
			eventCategoryId: eventCat,
			rankingDisplay: 'best-athlete-result'
		}
		WPA.getRankingsPersonalBests(params, true);
	},
	
	renderClubRankColumnNoLink: function(data, type, full) {
		var rank = parseInt(data);
		var divClass = 'wpa-rank';

		if(rank == 1) {
			divClass += ' rank-first';
		}
		else if(rank <= 10) {
			divClass += ' rank-top-10';
		}
		return '<div class="' + divClass + '">' + data + '</div>';
	},
	
	renderClubRankColumn: function(data, type, full) {
		var rank = parseInt(data);
		var divAction = 'WPA.displayTableColumnClubRankings(\'' + full['age_category'] + '\',\'' + full['event_cat_id'] + '\',\'' + full['gender'] + '\')';
		var divClass = 'wpa-rank';

		if(rank == 1) {
			divClass += ' rank-first';
		}
		else if(rank <= 10) {
			divClass += ' rank-top-10';
		}
		return '<div title="' + WPA.getProperty('rankings_column_hover_text') + '" class="' + divClass + '" onclick="' + divAction + '">' + data + '</div>';
	},
	
	renderPositionColumn: function(data, type, full) {
		if(parseInt(data) > 0) {
			return data
		}
		return '-';
	}
};