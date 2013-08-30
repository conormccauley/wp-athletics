/*
 * Custom jQuery Widgets
 */

function initCustom() {
	
	// Custom Autocomplete
	jQuery.widget( "custom.catcomplete", jQuery.ui.autocomplete, {
	  _renderMenu: function( ul, items ) {
	  var that = this,
	  currentCategory = "";
	  jQuery.each( items, function( index, item ) {
	    if ( item.category != currentCategory ) {
	      ul.append( "<li class='ui-autocomplete-category'>" + WPA.getProperty('wpa_search_category_' + item.category) + "</li>" );
	          currentCategory = item.category;
	        }
	        that._renderItemData( ul, item );
	      });
	    }
	});
	
	jQuery.widget("ui.tooltip", jQuery.ui.tooltip, {
	    options: {
	        content: function () {
	            return jQuery(this).prop('title');
	        }
	    }
	});

	// Custom combo box
    jQuery.widget( "custom.combobox", {
      _create: function() {
        this.wrapper = jQuery( "<span>" )
          .addClass( "custom-combobox" )
          .insertAfter( this.element );
 
        this.selectClass = this.options.selectClass;
        this.defaultValue = this.options.defaultValue;
        this.element.hide();
        this._createAutocomplete();
        this._createShowAllButton();
      },
      
      _clicked: function() {
    	  WPA.globals.supressComboClickEvents = true;
    	  if(this.input.autocomplete( "widget" ).is( ":visible" )) {
    		  this.input.autocomplete( "close" );
    	  }
    	  else {
    		  this.input.blur();
    		  this.input.autocomplete( "search", "" );
    	  }
    	  WPA.globals.supressComboClickEvents = false;
      },
      
      _highlight: function() {
    	  jQuery(this.input).removeClass('ui-state-default').addClass(this.selectClass);
      },
      
      _unhighlight: function() {
    	  jQuery(this.input).addClass('ui-state-default').removeClass(this.selectClass);
      },
 
      _createAutocomplete: function() {
    	var me = this;
        var selected = this.element.children( ":selected" ),
          value = selected.val() ? selected.text() : "";
 
        this.input = jQuery( "<input>" )
          .appendTo( this.wrapper )
          .val( value )
          .attr( "title", "" )
          .attr( "readonly", "readonly" )
          .addClass( "custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left" )
          .autocomplete({
            delay: 0,
            minLength: 0,
            source: jQuery.proxy( this, "_source" )
          })
          .tooltip({
            tooltipClass: "ui-state-highlight"
          }).click(function() {
        	  me._clicked();
          });
 
        this._on( this.input, {
          autocompleteselect: function( event, ui ) {
            ui.item.option.selected = true;
            this._trigger( "select", event, {
              item: ui.item.option
            });
            
            if(this.selectClass) {
	            if(ui.item.option.value == '' || ui.item.option.value == 'all' || ui.item.option.value == this.defaultValue) {
	            	this._unhighlight();
	            }
	            else {
	            	this._highlight();
	            }
            }
          },
 
          autocompletechange: "_removeIfInvalid"
        });
      },
 
      _createShowAllButton: function() {
        var input = this.input;
        var me = this;
 
        this.button = jQuery( "<span>" )
          .attr( "tabIndex", -1 )
          .appendTo( this.wrapper )
          .button({
            icons: {
              primary: "ui-icon-triangle-1-s"
            },
            text: false
          })
          .removeClass( "ui-corner-all" )
          .addClass( "custom-combobox-toggle ui-corner-right" )
          .click(function() {
            me._clicked();
          });
      },
 
      _source: function( request, response ) {
        var matcher = new RegExp( jQuery.ui.autocomplete.escapeRegex(request.term), "i" );
        response( this.element.children( "option" ).map(function() {
          var text = jQuery( this ).text();
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
          if ( jQuery( this ).text().toLowerCase() === valueLowerCase ) {
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
    	  this.input.prop('disabled', true).addClass( "ui-corner-all" );
    	  this.button.hide();
      },
      
      enable: function() {
    	  this.input.prop('disabled', false).removeClass( "ui-corner-all" );;
    	  this.button.remove();
    	  this._createShowAllButton();
      },
      
      setValue : function(value) {
	    this.element.val(value);
	    this.input.val(jQuery("#" + this.bindings[0].id + " option[value='" + value + "']").text());
      },
      
      getLabel: function() {
    	  return this.input.val();
      },
 
      _destroy: function() {
        this.wrapper.remove();
        this.element.show();
      }
    });
}

jQuery.fn.center = function () {
    this.css("position","absolute");
    this.css("top", Math.max(0, ((jQuery(window).height() - jQuery(this).outerHeight()) / 2) + 
                                                jQuery(window).scrollTop()) + "px");
    this.css("left", Math.max(0, ((jQuery(window).width() - jQuery(this).outerWidth()) / 2) + 
                                                jQuery(window).scrollLeft()) + "px");
    return this;
}

Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};