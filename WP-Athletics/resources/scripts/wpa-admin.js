/*
 * Javascript functions for WPA Admin.
 */


WPA.Admin = {
		
	/**
	 * Saves the admin settings
	 */
	saveSettings: function(callbackFn) {
		
		var data = {
			language: jQuery('#setting-language').val(),
			theme: jQuery('#setting-theme').val(),
			action: 'wpa_admin_save_settings'
		}
		
		jQuery.ajax({
			type: "post",
			url: WPA.Ajax.url,
			data: data,
			success: callbackFn
		});
	}

}