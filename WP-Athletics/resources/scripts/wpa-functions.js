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
		this.closeDialogs();
		
		WPA.currentUserProfileId = userId;

		if(WPA.userProfileDialog) {
			WPA.userProfileDialog.dialog("open");
			WPA.resultsTable.fnClearTable();
			WPA.resultsTable.fnDraw();
		}
		else {
			this.createUserProfileDatatables(userId);
			
			WPA.userProfileDialog = jQuery('#user-profile-dialog').dialog({
				title: this.getProperty('user_profile_dialog_title'),
				autoOpen: true,
				modal: true,
				width: jQuery(document).width()-100,
				height: jQuery(window).height()-100,
			})
		}
		WPA.getPersonalBests(userId);
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
		})
	},
	
	/**
	 * Displays the event results dialog
	 */
	displayEventResultsDialog: function(eventId) {
		this.closeDialogs();

		if(WPA.eventResultsDialog) {
			WPA.eventResultsDialog.dialog("open");
		}
		else {
			this.createEventResultsDatatables();
			
			WPA.eventResultsDialog = jQuery('#event-results-dialog').dialog({
				title: this.getProperty('event_results_dialog_title'),
				autoOpen: true,
				modal: true,
				width: jQuery(document).width()-400,
				height: jQuery(window).height()-100,
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
		});
	},
	
	/**
	 * Loads personal bests
	 */
	getPersonalBests: function(userId) {
		WPA.Ajax.getPersonalBests(function(result) {
			WPA.pbTable.fnClearTable();
			WPA.pbTable.fnAddData(result);
		}, userId);
	},
	
	/**
	 * Creates the event results datatables
	 */
	createEventResultsDatatables: function() {

		WPA.eventResultsTable = jQuery('#event-results-table').dataTable(WPA.createTableConfig({
			//"sDom": 'rt',
			"bPaginate": false,
			"aaSorting": [[ 2, "asc" ]],
			"aoColumns": [{ 
				"mData": "time_format",
				"bVisible": false
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
				"mData": "event_name"
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
	
	renderPositionColumn: function(data, type, full) {
		if(parseInt(data) > 0) {
			return data
		}
		return '-';
	}
};

(function( $ ) {
    $.widget( "custom.combobox", {
      _create: function() {
        this.wrapper = $( "<span>" )
          .addClass( "custom-combobox" )
          .insertAfter( this.element );
 
        this.element.hide();
        this._createAutocomplete();
        this._createShowAllButton();
      },
 
      _createAutocomplete: function() {
        var selected = this.element.children( ":selected" ),
          value = selected.val() ? selected.text() : "";
 
        this.input = $( "<input>" )
          .appendTo( this.wrapper )
          .val( value )
          .attr( "title", "" )
          .addClass( "custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left" )
          .autocomplete({
            delay: 0,
            minLength: 0,
            source: $.proxy( this, "_source" )
          })
          .tooltip({
            tooltipClass: "ui-state-highlight"
          });
 
        this._on( this.input, {
          autocompleteselect: function( event, ui ) {
            ui.item.option.selected = true;
            this._trigger( "select", event, {
              item: ui.item.option
            });
          },
 
          autocompletechange: "_removeIfInvalid"
        });
      },
 
      _createShowAllButton: function() {
        var input = this.input,
          wasOpen = false;
 
        this.button = $( "<a>" )
          .attr( "tabIndex", -1 )
          .attr( "title", "Show All Items" )
          .tooltip()
          .appendTo( this.wrapper )
          .button({
            icons: {
              primary: "ui-icon-triangle-1-s"
            },
            text: false
          })
          .removeClass( "ui-corner-all" )
          .addClass( "custom-combobox-toggle ui-corner-right" )
          .mousedown(function() {
            wasOpen = input.autocomplete( "widget" ).is( ":visible" );
          })
          .click(function() {
            input.focus();
 
            // Close if already visible
            if ( wasOpen ) {
              return;
            }
 
            // Pass empty string as value to search for, displaying all results
            input.autocomplete( "search", "" );
          });
      },
 
      _source: function( request, response ) {
        var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
        response( this.element.children( "option" ).map(function() {
          var text = $( this ).text();
          if ( this.value && ( !request.term || matcher.test(text) ) )
            return {
              label: text,
              value: text,
              option: this
            };
        }) );
      },
 
      _removeIfInvalid: function( event, ui ) {
 
        // Selected an item, nothing to do
        if ( ui.item ) {
          return;
        }
 
        // Search for a match (case-insensitive)
        var value = this.input.val(),
          valueLowerCase = value.toLowerCase(),
          valid = false;
        this.element.children( "option" ).each(function() {
          if ( $( this ).text().toLowerCase() === valueLowerCase ) {
            this.selected = valid = true;
            return false;
          }
        });
 
        // Found a match, nothing to do
        if ( valid ) {
          return;
        }
 
        // Remove invalid value
        this.input
          .val( "" )
          .attr( "title", value + " didn't match any item" )
          .tooltip( "open" );
        this.element.val( "" );
        this._delay(function() {
          this.input.tooltip( "close" ).attr( "title", "" );
        }, 2500 );
        this.input.data( "ui-autocomplete" ).term = "";
      },
      
      addCls: function(cls) {
    	  this.input.addClass(cls);
      },
      
      removeCls: function(cls) {
    	  this.input.removeClass(cls);
      },
      
      disabled: function(enable) {
    	  if(enable) {
    		  this.disable();
    	  }
    	  else {
    		  this.enable();
    	  }
      },
      
      disable: function() {
    	  this.input.prop('disabled', true);
    	  this.button.remove();
      },
      
      enable: function() {
    	  this.input.prop('disabled', false);
    	  this._createShowAllButton(); 
      },
      
      setValue : function(value) {
	    this.element.val(value);
	    this.input.val($("#" + this.bindings[0].id + " option[value='" + value + "']").text());
      },
 
      _destroy: function() {
        this.wrapper.remove();
        this.element.show();
      }
    });
  })( jQuery );