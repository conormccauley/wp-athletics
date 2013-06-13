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
						'<th></th>' +
						'<th></th>' +
						'<th>' + WPA.getProperty('column_category') + '</th>' +
						'<th>' + WPA.getProperty('column_athlete_name') + '</th>' +
						'<th>' + WPA.getProperty('column_time') + '</th>' +
						'<th>' + WPA.getProperty('column_event_name') + '</th>' +
						'<th>' + WPA.getProperty('column_event_location') + '</th>' +
						'<th>' + WPA.getProperty('column_event_type') + '</th>' +
						'<th>' + WPA.getProperty('column_event_date') + '</th>' +
						'<th></th>' +
					'</thead>' + 
					'<tbody></tbody>' +
				'</table>';
	},
	
	/**
	 * Loads personal bests
	 */
	getPersonalBests: function(ageCategory) {
		WPA.Ajax.getPersonalBests(function(result) {
			WPA.Records.tables[ageCategory].fnClearTable();
			WPA.Records.tables[ageCategory].fnAddData(result);
		}, -1, ageCategory );
	},
	
	/**
	 * Creates a datatable for a given age category ID
	 */
	createDataTable: function(id) {
		this.tables[id] = jQuery('#table-' + id).dataTable(WPA.createTableConfig({
			"sDom": 'rt',
			"bPaginate": false,
			"aaSorting": [[ 1, "asc" ]],
			"aoColumns": [{ 
				"mData": "time_format",
				"bVisible": false
			},{ 
				"mData": "time",
				"bVisible": false
			},{
				"mData": "category",
				"sClass": "datatable-bold-right-gray"
			},{
				"mData": "athlete_name",
				"mRender" : WPA.renderProfileLinkColumn
			},{
				"mData": "time",
				"mRender": WPA.renderTimeColumn,
				"sClass": "datatable-bold"
			},{ 
				"mData": "event_name",
				"mRender" : WPA.renderEventLinkColumn
			},{
				"mData": "event_location"
			},{
				"mData": "event_sub_type_id",
				"mRender" : WPA.renderEventTypeColumn
			},{ 
				"mData": "event_date"
			},{
				"mData": "garmin_id",
				"sWidth": "16px",
				"mRender": WPA.renderGarminColumn,
				"bSortable": false
			}]
		}));
	}
};
