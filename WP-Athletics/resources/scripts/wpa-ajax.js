/*
 * Javascript functions to manage WPA CRUD operations.
 */

WPA.Ajax = {
		
	/**
	 * sets up the object with the AJAX url and security nonce, also retrieves language properties
	 */
	setup: function(url, nonce, userId, callbackFn, skipLoadGlobals) {
		// create custom widgets
		initCustom();
		
		this.url = url;
		WPA.userId = userId;
		this.nonce = nonce;
		if(!skipLoadGlobals) {
			this.loadGlobalData(callbackFn);
		}
		else {
			callbackFn();
		}
	},
	
	/**
	 * Gets user profile info
	 */
	getUserProfile: function(userId, callbackFn) {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_get_user_profile',
				user_id: userId
			},
			success: function(result){
				callbackFn(result);
			}
		});
	},
	
	/**
	 * Returns the oldest recorded year for a user result
	 */
	getUserOldestResultYear: function(userId, callbackFn) {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_get_user_oldest_result_year',
				user_id: userId
			},
			success: callbackFn
		});	
	},
	
	/**
	 * stores useful info such as age categories and event types
	 */
	loadGlobalData: function(callbackFn) {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_load_global_data'
			},
			success: function(result) {
				WPA.globals.eventTypes = result.eventTypes;
				WPA.globals.ageCategories = result.ageCategories;
				WPA.globals.eventCategories = result.eventCategories;
				WPA.Props = result.languageProperties;
				WPA.Settings = result.settings;
				if(callbackFn) {
					callbackFn();
				}
			}
		});
	},
	
	/**
	 * Saves profile photo as a meta option
	 */
	saveProfilePhoto: function(url) {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_save_profile_photo',
				url: url,
				security:  WPA.Ajax.nonce
			},
			success: function(result){
				WPA.MyResults.loadProfilePhoto();
			}
		});
	},
	
	/**
	 * Saves profile information to the user meta data table
	 */
	saveProfileData: function(data, element) {
		
		data['action'] = 'wpa_save_profile_data';
		data['security'] = WPA.Ajax.nonce;

		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: data,
			success: function(result){
				if(result.success) {
					element.effect("highlight", {color: '#63ec39'}, 1000);
				}
			}
		});
	},
	
	/**
	 * Performs delete action for a given event result
	 */
	deleteResult: function(id) {

		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_delete_result',
				security: WPA.Ajax.nonce,
				resultId: id
			},
			success: function(result){
				if(result.success) {
					WPA.MyResults.reloadResults();
				}
			}
		});
	},
	
	/**
	 * Retrieves result information for the update result screen
	 */
	loadResultInfo: function(id) {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_get_result_info',
				security: WPA.Ajax.nonce,
				resultId: id
			},
			success: function(result){
				if(result) {
					WPA.MyResults.setResultUpdateInfo(result);
				}
			}
		});
	},
	
	/**
	 * Creates or updates an event result to the database
	 */
	updateResult: function(data, callbackFn) {
		data['action'] = 'wpa_update_result';
		data['security'] = WPA.Ajax.nonce;
		
		jQuery.ajax({
			type: 'post',
			url: WPA.Ajax.url,
			data: data,
			success: callbackFn
		})
	},
	
	/**
	 * Retrieves single event info based on ID
	 */
	getEventInfo: function(id, callbackFn) {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_get_event',
				eventId: id
			},
			success: callbackFn
		});
	},
	
	/**
	 * Retrieves results for a particular event
	 */
	getEventResults: function(id, callbackFn) {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_get_event_results',
				eventId: id
			},
			success: callbackFn
		});
	},
	
	/** 
	 * Retrieves a list of personal bests. If age category or event category ID specified, will be filtered.
	 */
	getPersonalBests: function(callbackFn, params, disableLoading) {
		
		if(!disableLoading) {
			WPA.togglePbLoading(true);
		}

		var data = {action: 'wpa_get_personal_bests'};
		
		if(params) {
			jQuery.extend(data, params);
		}
		
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: data,
			success: function(result) {
				callbackFn(result);
				if(!disableLoading) {
					WPA.togglePbLoading(false);
				}
			}
		});
	}
}