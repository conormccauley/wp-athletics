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
					'<th>' + WPA.getProperty('column_category') + '</th>' +
					'<th>' + WPA.getProperty('column_athlete_name') + '</th>' +
					'<th>' + WPA.getProperty('column_time') + '</th>' +
					'<th>' + WPA.getProperty('column_event_name') + '</th>' +
					'<th>' + WPA.getProperty('column_event_location') + '</th>' +
					'<th>' + WPA.getProperty('column_event_type') + '</th>' +
					'<th>' + WPA.getProperty('column_event_date') + '</th>' +
					'<th></th>' +
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
	 * Loads top 10 personal bests
	 */
	getTop10PersonalBests: function(eventCatId) {
		WPA.Ajax.getPersonalBests(function(result) {
			WPA.Records.tables['top10'].fnClearTable();
			WPA.Records.tables['top10'].fnAddData(result);
			WPA.Records.top10Dialog.dialog('open');
		}, {
			ageCategory: WPA.Records.currentCategory,
			gender: WPA.Records.gender,
			eventSubTypeId: WPA.filterType,
			eventDate: WPA.filterPeriod,
			eventCategoryId: eventCatId
		});
	},
	
	/**
	 * Displays the top 10 dialog for a given age category
	 */
	displayEventTop10Dialog: function(eventCatId, category) {
		WPA.Records.top10Dialog.dialog('option', 'title', WPA.Records.generateTop10DialogTitle(category));		
		WPA.Records.getTop10PersonalBests(eventCatId);
	},
	
	/**
	 * Generates the title of the top 10 dialog by replacing tokens in the string literal
	 */
	generateTop10DialogTitle: function(category) {
		var title =  WPA.getProperty('top_10_dialog_title');
		
		// category
		title = title.replace('[category]', category);
		
		// type
		title = title.replace('[type]', jQuery('#filterType').combobox('getLabel'));
		
		// age 
		title = title.replace('[age]', WPA.getAgeCategoryDescription(WPA.Records.currentCategory));
		
		// period
		title = title.replace('[period]', jQuery('#filterPeriod').combobox('getLabel'));
		
		return title;
	},
	
	/**
	 * Creates a datatable for a given age category ID
	 */
	createDataTable: function(id) {
		this.tables[id] = jQuery('#table-' + id).dataTable(WPA.createTableConfig({
			"sDom": 'rt',
			"bPaginate": false,
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
			},{
				"mData": "event_cat_id",
				"sWidth": "20px",
				"mRender": WPA.renderTop10LinkColumn,
				"bSortable": false,
				"sClass" : "datatable-center"
			}]
		}));
	},
	
	/**
	 * Creates the top 10 datatable
	 */
	createTop10DataTable: function() {
		this.tables['top10'] = jQuery('#table-top-10').dataTable(WPA.createTableConfig({
			"sDom": 'rt',
			"bPaginate": false,
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
				"mData": "rank",
				"sClass": "datatable-bold",
				"bSortable": false
			},{
				"mData": "athlete_name",
				"mRender" : WPA.renderProfileLinkColumn,
				"bSortable": false
			},{
				"mData": "time",
				"mRender": WPA.renderTimeColumn,
				"sClass": "datatable-bold",
				"bSortable": false
			},{ 
				"mData": "event_name",
				"mRender" : WPA.renderEventLinkColumn,
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
				"mData": "garmin_id",
				"sWidth": "16px",
				"mRender": WPA.renderGarminColumn,
				"bSortable": false
			}]
		}));
	}
};
