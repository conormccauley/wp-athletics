/*
 * Javascript functions to manage WPA CRUD operations.
 */

WPA.Ajax = {
		
	/**
	 * sets up the object with the AJAX url and security nonce, also retrieves language properties
	 */
	setup: function(url, nonce, pluginUrl, userId, callbackFn, skipLoadGlobals) {
		if(!WPA.ajaxSetup) {
			// create custom widgets
			initCustom();
			
			WPA.globals.pluginUrl = pluginUrl;
			WPA.userId = userId;
			this.url = url;
			this.nonce = nonce;
			if(!skipLoadGlobals) {
				this.loadGlobalData(callbackFn);
			}
			else {
				callbackFn();
			}
			//WPA.ajaxSetup = true;
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
	 * Gets a user DOB
	 */
	getUserDOB: function(userId, callbackFn) {
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_get_user_dob',
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

				WPA.userDOB = result.userDOB;
				WPA.userHideDOB = result.userHideDOB;
				WPA.userGender = result.userGender;
				WPA.isLoggedIn = result.isLoggedIn;
				WPA.isAdmin = result.isAdmin;
				
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
	 * Validates that the user hasn't already entered a given event. If userId is not specified, checks for the current
	 * logged in user.
	 */
	validateEventEntry: function(eventId, trueFn, falseFn, userId) {
		WPA.toggleLoading(true);
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_validate_event_entry',
				eventId: eventId,
				userId: userId
			},
			success: function(result){
				WPA.toggleLoading(false);
				if(result.valid) {
					trueFn();
				}
				else {
					WPA.alertError(WPA.getProperty('error_event_already_entered'));
					if(falseFn) {
						falseFn();
					}
				}
			}
		});
	},
	
	/**
	 * Saves profile information to the user meta data table
	 */
	saveProfileData: function(data, element, callbackFn) {
		
		data['action'] = 'wpa_save_profile_data';
		data['security'] = WPA.Ajax.nonce;

		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: data,
			success: function(result){
				if(result.success) {
					if(element) {
						element.effect("highlight", {color: '#63ec39'}, 1000);
					}
					if(callbackFn) {
						callbackFn();
					}
				}
			}
		});
	},
	
	/**
	 * Performs delete action for a given event result
	 */
	deleteResult: function(id, callbackFn) {
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
					if(callbackFn) {
						callbackFn();
					}
				}
			}
		});
	},
	
	/**
	 * Deletes a given set of results IDs
	 */
	deleteResults: function(ids, callbackFn) {
		WPA.toggleLoading(true);
		ids = WPA.Ajax.toIdString(ids);
		
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_delete_results',
				security: WPA.Ajax.nonce,
				ids: ids
			},
			success: function(result) {
				WPA.toggleLoading(false);
				if(callbackFn) {
					callbackFn(result);
				}
			}
		});
	},
	
	/**
	 * Reassigns a set of results to another user ID
	 */
	reassignResults: function(ids, reassignId, callbackFn) {
		WPA.toggleLoading(true);
		ids = WPA.Ajax.toIdString(ids);
		
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_reassign_results',
				security: WPA.Ajax.nonce,
				reassignId: reassignId,
				ids: ids
			},
			success: function(result) {
				WPA.toggleLoading(false);
				if(callbackFn) {
					callbackFn(result);
				}
			}
		});
	},
	
	/**
	 * Retrieves result information for the update result screen
	 */
	loadResultInfo: function(id) {
		WPA.toggleLoading(true);
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_get_result_info',
				security: WPA.Ajax.nonce,
				resultId: id
			},
			success: function(result){
				WPA.toggleLoading(false);
				if(result) {
					WPA.setResultUpdateInfo(result);
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
	 * Edits or creates a single event details
	 */
	updateEvent: function(data, callbackFn, isCreate) {
		WPA.toggleLoading(true);
		
		data.action = isCreate ? 'wpa_create_event' : 'wpa_update_event';
		
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: data,
			success: function(result) {
				WPA.toggleLoading(false);
				callbackFn(result);
			}
		});
	},
	
	
	/**
	 * Retrieves single event info based on ID
	 */
	getEventInfo: function(id, callbackFn) {
		WPA.toggleLoading(true);
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_get_event',
				eventId: id
			},
			success: function(result) {
				WPA.toggleLoading(false);
				callbackFn(result);
			}
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
			success: function(result) {
				if(result.id == id) {
					callbackFn(result.data, result.id)
				}
			}
		});
	},
	
	/**
	 * Merges 2 or more events. Reads in an array of events and a primary event ID for which to merge all the other results to.
	 */
	mergeEvents: function(ids, primaryId, callbackFn) {
		// convert array to a string for transmitting via ajax
		if(ids.length > 0) {
			var idStr = '';
			jQuery.each(ids, function(index, id) {
				// do not include the primary Id for deletion
				if(id != primaryId) {
					idStr += id + ',';
				}
			})
		
			// remove trailing comma
			idStr = idStr.substring(0, idStr.length-1);

			// the ajax call!
			jQuery.ajax({
				type: "post",
				url: WPA.Ajax.url,
				data: {
					action: 'wpa_merge_events',
					ids: idStr,
					reassignId: primaryId
				},
				success: callbackFn
			});
		}
	},
	
	/**
	 * converts an array of ids to a string for transmitting via ajax
	 */
	toIdString: function(ids) {
		idStr = '';
		
		if(ids.length > 0) {
			jQuery.each(ids, function(index, id) {
				idStr += id + ',';
			})
		
			// remove trailing comma
			idStr = idStr.substring(0, idStr.length-1);
		}
		
		return idStr;
	},
	
	/**
	 * Deletes event(s). Reads in an array of ids and an event id of which to reassign the results for the deleted events. 
	 */
	deleteEvents: function(ids, reassignId, callbackFn) {
		
		ids = WPA.Ajax.toIdString(ids);

		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: {
				action: 'wpa_delete_events',
				ids: idStr,
				reassignId: reassignId
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