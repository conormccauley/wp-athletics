/*
 * Javascript functions for WPA records page
 */

WPA.Records = {
		
	tables: [],
		
	/**
	 * Generates HTML for a PB record table based on age class
	 */
	createTableHTML: function(id) {
		return '<table width="100%" class="display ui-state-default" id="table-' + id + '">' +
			'<thead>' + 
				'<tr>' +
					'<th></th>' +
					'<th></th>' +
					'<th></th>' +
					'<th>' + WPA.getProperty('column_category') + '</th>' +
					'<th>' + WPA.getProperty('column_time') + '</th>' +
					'<th>' + WPA.getProperty('column_athlete_name') + '</th>' +
					'<th>' + WPA.getProperty('column_event_name') + '</th>' +
					'<th>' + WPA.getProperty('column_event_location') + '</th>' +
					'<th>' + WPA.getProperty('column_event_type') + '</th>' +
					'<th>' + WPA.getProperty('column_event_date') + '</th>' +
					'<th>' + WPA.getProperty('column_age_category') + '</th>' +
					'<th></th>' +
				'</tr>' +
			'</thead>' + 
			'<tbody></tbody>' +
		'</table>';
	},
	
	/**
	 * Loads personal bests
	 */
	getPersonalBests: function() {
		WPA.Ajax.getPersonalBests(function(result) {
			WPA.Records.tables[WPA.Records.currentCategory].fnClearTable();
			WPA.Records.tables[WPA.Records.currentCategory].fnAddData(result);
		}, {
			ageCategory: WPA.Records.currentCategory,
			eventSubTypeId: WPA.filterType,
			eventDate: WPA.filterPeriod,
			gender: WPA.Records.gender
		});
	},
	
	/**
	 * Creates a datatable for a given age category ID
	 */
	createDataTable: function(id, showAgeColumn) {
		this.tables[id] = jQuery('#table-' + id).dataTable(WPA.createTableConfig({
			"sDom": 'rt',
			"bPaginate": false,
			"sScrollX": "100%",
			"bScrollCollapse": true,
			"aaSorting": [[ 1, "asc" ]],
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
				"mData": "time",
				"bVisible": false
			},{
				"mData": "event_cat_id",
				"sWidth": "20px",
				"mRender": WPA.renderRankingsLinkColumn,
				"bSortable": false,
				"sClass" : "datatable-center"
			},{
				"mData": "category",
				"sClass": "datatable-bold-right-gray"
			},{
				"mData": "time",
				"mRender": WPA.renderTimeColumn,
				"sClass": "datatable-bold"
			},{
				"mData": "athlete_name",
				"mRender" : WPA.renderProfileLinkColumn
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
				"mData": "event_date"
			},{
				"mData": "age_category",
				"bVisible": showAgeColumn,
				"mRender" : WPA.renderAgeCategoryColumn
			},{
				"mData": "garmin_id",
				"sWidth": "16px",
				"mRender": WPA.renderGarminColumn,
				"bSortable": false
			}]
		}));
	}
};
