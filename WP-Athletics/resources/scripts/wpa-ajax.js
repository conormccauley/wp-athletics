/*
 * Javascript functions to manage WPA CRUD operations.
 */

WPA.Ajax = {
		
	/**
	 * sets up the object with the AJAX url and security nonce, also retrieves language properties
	 */
	setup: function(url, nonce, userId, callbackFn) {
		this.url = url;
		WPA.userId = userId;
		this.nonce = nonce;
		this.getLanguageProperties(callbackFn);
		this.loadAthleteData();
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
	 * stores useful info such as age categories and event types
	 */
	loadAthleteData: function() {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_load_athlete_data'
			},
			success: function(result){
				WPA.globals.eventTypes = result.eventTypes;
				WPA.globals.ageCategories = result.ageCategories;
				WPA.globals.eventCategories = result.eventCategories;
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
	 * If individual is true, will return for logged in user, otherwise, all club records
	 */
	getPersonalBests: function(callbackFn, userId, ageCategory, eventCategoryId) {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_get_personal_bests',
				userId: userId,
				ageCategory: ageCategory,
				eventCategoryId: eventCategoryId
			},
			success: callbackFn
		});
	},
	
	/**
	 * Launches the dialog to upload media and also sets a filter beforehand ensuring users will only see their own files
	 */
	launchMediaUploader: function() {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_add_media_query_filter',
			},
			success: function() {
				WPA.customUploader.open();
			}
		});
	},
	
	/**
	 * Launches the dialog to upload media and also sets a filter beforehand ensuring users will only see their own files
	 */
	closeMediaUploader: function() {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_remove_media_query_filter',
			},
			success: function() {
				
			}
		});
	}
		
}