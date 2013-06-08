/*
 * Javascript util functions for WPA.
 */

var WPA = {
		
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