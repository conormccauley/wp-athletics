/*
 * Javascript functions for WPA my results page
 */

WPA.MyResults = {
	
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
	 * reloads all results
	 */
	reloadResults: function() {
		console.log('reloading results function');
		// redraw the table
		if(WPA.MyResults.currentTab == 'results') {
			WPA.MyResults.myResultsTable.fnDraw();
		}
		else if(WPA.MyResults.currentTab == 'pb') {
			// load personal bests
			WPA.MyResults.getPersonalBests();
		}
		
		// reload an event dialog if it is open
		WPA.reloadEventResults();
	},
	
	/**
	 * Loads personal bests
	 */
	getPersonalBests: function(disableLoading) {
		WPA.Ajax.getPersonalBests(function(result) {
			WPA.MyResults.pbTable.fnClearTable();
			WPA.MyResults.pbTable.fnAddData(result);
		}, {
			userId: WPA.userId,
			ageCategory: WPA.filterAge,
			eventSubTypeId: WPA.filterType,
			eventDate: WPA.filterPeriod,
			showAllCats: true
		}, disableLoading);
	},
	
	/** 
	 * Creates my results tables
	 */
	createMyResultsTables: function() {

		// My Results table
		WPA.MyResults.myResultsTable = jQuery('#my-results-table').dataTable(WPA.createTableConfig({
			"bServerSide": true,
			"sAjaxSource": WPA.Ajax.url,
			"sServerMethod": "POST",
			"sScrollX": "100%",
			"bScrollCollapse": true,
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
				"mData": "event_location",
				"mRender": WPA.renderEventLocationColumn
			},{
				"mData": "event_sub_type_id",
				"mRender" : WPA.renderEventTypeColumn,
				"bVisible": false
			},{
				"mData": "category",
				"mRender" : WPA.renderCategoryAndTerrainColumn
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
				"mRender": WPA.renderPositionColumn,
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
			"sScrollX": "100%",
			"bScrollCollapse": true,
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
				"mData": "time",
				"mRender": WPA.renderPaceMilesColumn,
				"bSortable": false
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
	 * Deletes a result
	 */
	deleteResult: function(id) {
		WPA.deleteResult(id, function() {
			WPA.MyResults.reloadResults();
		});
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
