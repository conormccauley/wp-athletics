/*
 * Javascript functions to manage WPA CRUD operations.
 */

WPA.Ajax = {
		
	/**
	 * sets up the object with the AJAX url and security nonce, also retrieves language properties
	 */
	setup: function(url, nonce, callbackFn) {
		this.url = url;
		this.nonce = nonce;
		this.getLanguageProperties(callbackFn);
	},
	
	/**
	 * performs AJAX call to retrieve array of translated literal properties
	 */
	getLanguageProperties: function(callbackFn) {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_get_language_props'
			},
			success: function(result){
				console.log('Language properties:' + result);
				WPA.Props = result;
				callbackFn();
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
			success: function(result) {
				WPA.MyResults.loadEventInfoCallback(result);
				if(callbackFn) {
					callbackFn();
				}
			}
		});
	},
	
	/** 
	 * Retrieves a list of personal bests. If age category or event category ID specified, will be filtered.
	 * If individual is true, will return for logged in user, otherwise, all club records
	 */
	getPersonalBests: function(callbackFn, individual, ageCategory, eventCategoryId) {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_get_personal_bests',
				individual: individual,
				ageCategory: ageCategory,
				eventCategoryId: eventCategoryId
			},
			success: callbackFn
		});
	}
		
}