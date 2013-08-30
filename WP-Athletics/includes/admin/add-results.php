<?php
if ( $this->has_permission_to_manage() ) {
	global $current_user;
	$nonce = wp_create_nonce( $this->nonce );

?>
	<script type="text/javascript">

	jQuery(document).ready(function() {
		WPA.isAdminScreen = true;

		WPA.Admin.eventSelected = function() {
			jQuery('#eventName').attr('readonly', 'readonly');
			jQuery('#add-event-cancel').show();
			jQuery('#create-new-event-container').hide();
			jQuery('#add-results-step').fadeIn(500);

			// if athlete already selected, refresh the fields
			if(WPA.Admin.selectedAthleteId) {
				WPA.Admin.showResultFields();
			}
		}

		WPA.Admin.requestAthleteGender = function(callbackFn) {
			// reset value to male
			WPA.Admin.athleteGender = 'M';
			jQuery('#setGender').val(WPA.Admin.athleteGender);

			jQuery('#add-result-gender-dialog').dialog({
				title: WPA.getProperty('add_result_gender_dialog_title'),
				autoOpen: true,
				resizable: false,
				modal: true,
				height: 'auto',
				width: 300,
				buttons: [{
			    	text: WPA.getProperty('submit'),
			    	click: function() {
			    		WPA.Admin.saveAthleteGender();
				    }
			    }]
			});
		}

		WPA.Admin.saveAthleteGender = function() {
			WPA.toggleLoading(true);
			WPA.Ajax.saveProfileData({
				gender: WPA.Admin.athleteGender,
				userId: WPA.Admin.selectedAthleteId
			}, null, function() {
				WPA.toggleLoading(false);
				jQuery('#add-result-gender-dialog').dialog('close');
				WPA.Admin.validateDobAndGender();
			});
		}

		WPA.Admin.setAthleteDob = function(callbackFn) {
			jQuery('#add-result-dob-dialog').dialog({
				title: WPA.getProperty('add_result_dob_dialog_title'),
				autoOpen: true,
				resizable: false,
				modal: true,
				height: 'auto',
				width: 300,
				buttons: [{
			    	text: WPA.getProperty('cancel'),
			    	click: function() {
			    		jQuery('#add-result-dob-dialog').dialog('close');
				    }
				},{
			    	text: WPA.getProperty('submit'),
			    	click: function() {
				    	if(WPA.Admin.athleteDob != '') {
			    			WPA.Admin.saveAthleteDob();
				    	}
				    	else {
					    	WPA.alertError(WPA.getProperty('add_result_set_dob_error'));
				    	}
				    }
			    }]
			});
		}

		WPA.Admin.saveAthleteDob = function() {
			WPA.toggleLoading(true);
			WPA.Ajax.saveProfileData({
				dob: WPA.Admin.athleteDob,
				userId: WPA.Admin.selectedAthleteId
			}, null, function() {
				WPA.toggleLoading(false);
				jQuery('#add-result-dob-dialog').dialog('close');
				WPA.Admin.toggleAgeClassSelect(false);
				WPA.Admin.setAgeCat();
			});
		}

		WPA.Admin.getAthleteInfo = function() {
			WPA.Ajax.getUserProfile(WPA.Admin.selectedAthleteId, function(result) {

				WPA.Admin.athleteDob = result.dob;
				WPA.Admin.athleteGender = result.gender;

				WPA.Admin.validateDobAndGender();
			});
		}

		WPA.Admin.validateDobAndGender = function() {
			// if no gender specified for athlete, request it
			if(WPA.Admin.athleteGender == '') {
				WPA.Admin.requestAthleteGender(function(result) {
					WPA.Admin.athleteGender = result;
				});
			}
			else {
				WPA.Admin.athleteSelected();
			}

			// if no DOB speficied for athlete, allow user manually set the age class, otherwise calculate the age cat based on race date and DOB
			if(WPA.Admin.athleteDob == '') {
				WPA.Admin.toggleAgeClassSelect(true);
			}
			else {
				WPA.Admin.setAgeCat();
			}
		}

		WPA.Admin.setAgeCat = function() {
			var ageCat = WPA.calculateAthleteAgeCategory(WPA.Admin.eventDate, WPA.Admin.athleteDob, true);
			WPA.Admin.athleteAgeCat = ageCat.id;
		}

		WPA.Admin.toggleAgeClassSelect = function(show) {
			if(show) {
				jQuery('#addResultAgeClass').show();
				WPA.Admin.athleteAgeCat = jQuery('#addResultAgeClass select').val();
			}
			else {
				jQuery('#addResultAgeClass').hide();
			}
		}

		WPA.Admin.athleteSelected = function() {
			jQuery('#athleteName').attr('readonly', 'readonly');
			jQuery('#add-athlete-cancel').show();
			jQuery('#create-new-athlete-container').hide();
			WPA.Admin.toggleAgeClassSelect(false);
			WPA.Admin.showResultFields();
		}

		WPA.Admin.hideResultFields = function() {
			jQuery('#add-result-form').hide();
		}

		WPA.Admin.submitResult = function() {
			var timeMillis = WPA.timeToMilliseconds(
				jQuery('#addResultTimeHours').val(),
				jQuery('#addResultTimeMinutes').val(),
				jQuery('#addResultTimeSeconds').val(),
				jQuery('#addResultTimeMilliSeconds').val()
			);

			var paces = WPA.getResultPaces(timeMillis, WPA.Admin.eventDistanceMeters);

			WPA.toggleLoading(true);
			WPA.Ajax.updateResult({
				userId: WPA.Admin.selectedAthleteId,
				eventId: WPA.Admin.selectedEventId,
				ageCategory: WPA.Admin.athleteAgeCat,
				gender: WPA.Admin.athleteGender,
				paceKm: paces.km,
				paceMiles: paces.miles,
				position: jQuery('#addResultPosition').val(),
				time: timeMillis
			}, function() {
				WPA.toggleLoading(false);
				WPA.Admin.addAthleteCancel();
				WPA.Admin.showResultSuccess();
			});
		}

		WPA.Admin.addAthleteCancel = function() {
			jQuery('#add-athlete-cancel').hide();
			jQuery('#athleteName').removeAttr('readonly').val('').focus();
			jQuery('#create-new-athlete-container').show();
			WPA.Admin.selectedAthleteId = undefined;
			WPA.Admin.hideResultFields();
		}

		WPA.Admin.showResultSuccess = function() {
			jQuery('#show-result-success').show().fadeOut(5000);
		}

		WPA.Admin.showResultFields = function() {
			jQuery('#add-result-form').show();

			// set visibility of time fields
			var fields = jQuery('div[time-format]');
			jQuery.each(fields, function() {
				jQuery(this).find('input').val('0');
				jQuery(this).hide();
			});

			// reset position
			jQuery('#addResultPosition').val('');

			var firstField;

			if(WPA.Admin.eventTimeFormat) {
				var timeFormats = WPA.Admin.eventTimeFormat.split(':');
				jQuery.each(timeFormats, function(i, format) {
					if(i == 0) {
						firstField = jQuery('div[time-format="' + format + '"] input')
					}
					jQuery('div[time-format="' + format + '"]').show();
				});
			}

			// focus on the first time field
			jQuery(firstField).focus();
		}

		WPA.Admin.viewCurrentEventResults = function() {
			if(WPA.Admin.selectedEventId) {
				WPA.displayEventResultsDialog(WPA.Admin.selectedEventId);
			}
		}

		WPA.Admin.selectEvent = function() {
			WPA.Ajax.getEventInfo(WPA.Admin.selectedEventId, function(result) {
				// find the time format
				WPA.Admin.eventTimeFormat = WPA.getEventTimeFormat(result.event_cat_id);
				WPA.Admin.eventDate = result.date;
				WPA.Admin.eventDistanceMeters = result.distance_meters;
				WPA.Admin.eventSelected();

				// show shortcode
				jQuery('#add-results-embed span').html(WPA.Admin.selectedEventId);
				jQuery('#add-results-embed').show();
			});
		}

		WPA.Admin.showCreateUserDialog = function() {
			// reset fields
			jQuery('#createUserName,#createUserDob').val();

			jQuery('#create-user-dialog').dialog({
				title: WPA.getProperty('add_result_create_user_dialog_title'),
				autoOpen: true,
				resizable: false,
				modal: true,
				height: 'auto',
				width: 'auto',
				buttons: [{
			    	text: WPA.getProperty('cancel'),
			    	click: function() {
			    		jQuery('#create-user-dialog').dialog('close');
				    }
				},{
			    	text: WPA.getProperty('submit'),
			    	click: function() {
			    		WPA.Ajax.createUser();
				    }
			    }]
			});
		}

		WPA.Ajax.createUser = function() {
			var name = jQuery('#createUserName').val();
			if(name != '') {
				WPA.toggleLoading(true);
				jQuery.ajax({
					type: "post",
					url: WPA.Ajax.url,
					data: {
						action: 'wpa_create_user',
						name: name,
						gender: jQuery('#createUserGender').val(),
						dob: jQuery('#createUserDob').val()
					},
					success: function(result){
						WPA.toggleLoading(false);
						if(parseInt(result.id) > 0) {
							jQuery('#create-user-dialog').dialog('close');

							// set athlete info
							jQuery('#athleteName').val(jQuery('#createUserName').val()).removeClass('wpa-search-disabled');
							WPA.Admin.selectedAthleteId = result.id;
							WPA.Admin.getAthleteInfo();

							// show dialog with athlete info
							jQuery('#create-user-success-dialog span').html(result.username + ' / ' + result.username);
							jQuery('#create-user-success-dialog').dialog({
								title: WPA.getProperty('add_result_create_user_success_dialog_title'),
								autoOpen: true,
								resizable: false,
								modal: true,
								height: 'auto',
								width: 300,
								buttons: [{
							    	text: WPA.getProperty('ok'),
							    	click: function() {
							    		jQuery('#create-user-success-dialog').dialog('close');
								    }
							    }]
							});

						}
					}
				});
			}
			else {
				jQuery('#createUserName').addClass('ui-state-error');
			}
		}

		// set up ajax
		WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo WPA_PLUGIN_URL; ?>', '<?php echo $current_user->ID; ?>',  function() {

			// common setup function
			WPA.setupCommon();

			// setup the edit event screen
			WPA.setupEditEventDialog(function(createId) {
				WPA.Admin.selectedEventId = createId;
				jQuery('#eventName').val(jQuery('#editEventName').val()).removeClass('wpa-search-disabled');
				WPA.Admin.selectEvent();
			});

			jQuery('#create-event-button button').button({
				icons: {
		        	primary: 'ui-icon-circle-plus'
		        }
			}).click(function(e) {
				e.preventDefault();
				WPA.showCreateEventDialog();
			});

			jQuery('#add-result-submit button').button({
				icons: {
		        	primary: 'ui-icon-circle-plus'
		        }
			}).click(function(e) {
				e.preventDefault();
				WPA.Admin.submitResult();
			})

			jQuery('#create-athlete-button button').button({
				icons: {
		        	primary: 'ui-icon-circle-plus'
		        }
			}).click(function(e) {
				e.preventDefault();
				WPA.Admin.showCreateUserDialog();
			});

			jQuery('#add-event-cancel').click(function() {
				jQuery(this).hide();
				WPA.Admin.selectedEventId = undefined;
				jQuery('#eventName').removeAttr('readonly').val('').focus();
				jQuery('#create-new-event-container').show();
				jQuery('#add-results-step').hide();
				jQuery('#add-results-embed').hide();
			});

			jQuery('#add-athlete-cancel').click(function() {
				WPA.Admin.addAthleteCancel();
			});

			// autocomplete for choosing event
			jQuery("#eventName").autocomplete({
				source: WPA.Ajax.url + '?action=wpa_event_autocomplete',
				minLength: 2,
				select: function( event, ui ) {
					WPA.globals.temp = ui.item.label;
					setTimeout("jQuery('#eventName').val(WPA.globals.temp)", 50);
					WPA.Admin.selectedEventId = ui.item.value;
					WPA.Admin.selectEvent();
				}
		    }).focus(function(){
		        this.select();
		    })

			// autocomplete for choosing athlete
			jQuery("#athleteName").autocomplete({
				source: WPA.Ajax.url + '?action=wpa_user_autocomplete',
				minLength: 2,
				select: function( event, ui ) {

					// validate that the user is not already registered in this event
					WPA.Ajax.validateEventEntry(WPA.Admin.selectedEventId, function() {
						// success
						jQuery('#athleteName').val(ui.item.label);
						WPA.Admin.selectedAthleteId = ui.item.value;
						WPA.Admin.getAthleteInfo();
					}, function() {
						// fail
						jQuery('#athleteName').val('');
					}, ui.item.value)

				}
		    }).focus(function(){
		        this.select();
		    })

			// change event for time fields to validate real time
			var timeFields = jQuery('input[time-format]');
			jQuery.each(timeFields, function() {
				jQuery(this).keyup(function() {
					var value = jQuery(this).val();
					if(value != '' && !WPA.isValidTime(jQuery(this).attr('time-format'), value)) {
						jQuery(this).val('0');
					}
					else {
						jQuery(this).removeClass('ui-state-error');
					}
				}).focus(function() {
					jQuery(this).select();
				}).blur(function() {
					if(jQuery(this).val() == '') {
						jQuery(this).val('0');
					}
				});
			});

			// keyup on create user name field
			jQuery('#createUserName').keyup(function() {
				jQuery(this).removeClass('ui-state-error');
			});

			// gender select
			jQuery("#setGender").change(function() {
				WPA.Admin.athleteGender = jQuery(this).val();
			});

			// age category select
			jQuery.each(WPA.globals.ageCategories, function(cat, item) {
				jQuery("#addResultAgeClass select").append('<option value="' + cat + '">' + item.name + '</option>');
			});

			jQuery('#addResultAgeClass select').change(function() {
				WPA.Admin.athleteAgeCat = jQuery(this).val();
			});

			// date picker
			jQuery('#setDob,#createUserDob').datepicker({
		      showOn: "both",
		      buttonImage: "<?php echo WPA_PLUGIN_URL ?>/resources/images/date_picker.png",
		      buttonImageOnly: true,
		      changeMonth: true,
		      changeYear: true,
		      maxDate: 0,
		      dateFormat: WPA.Settings['display_date_format'],
		      yearRange: 'c-100:c'
		    }).change(function(event) {
			    if(jQuery(this).val() != '') {
			    	WPA.Admin.athleteDob = jQuery(this).val();
			    }
		    })
		});
	});

	</script>

	<div>
		<div class="wpa-admin-title">
			<h2><?php echo $this->get_property('admin_add_results_title'); ?></h2>
		</div>
		<br style="clear:both;"/>
	</div>

	<div class="wpa">

		<div class="add-results-left add-results-step">
			<h1>1. <?php echo $this->get_property('add_results_choose_event_title'); ?></h1>
			<p><?php echo $this->get_property('add_results_choose_event_text'); ?></p>
			<div>
				<input style="background:#fff" size="50" id="eventName" default-text="<?php echo $this->get_property('add_results_event_input_text'); ?>" class="ui-corner-all ui-widget ui-widget-content ui-state-default wpa-search wpa-search-disabled"></input>
				<span style="display:none;" id="add-event-cancel" class="input-cancel"></span>
			</div>
			<div id="create-new-event-container">
				<div id="add-result-or">OR</div>
				<div>
					<div id="create-event-button">
						<button><?php echo $this->get_property('events_create_button'); ?></button>
					</div>
				</div>
			</div>
			<br style="clear:both;"/>
		</div>

		<div class="add-results-step" id="add-results-step" style="display:none">
			<h1>2. <?php echo $this->get_property('add_results_title'); ?></h1>
			<p>
				<?php echo $this->get_property('add_results_text'); ?>
				<span class="wpa-admin-link" onclick="WPA.Admin.viewCurrentEventResults()"><?php echo $this->get_property('add_results_view_current_event_results'); ?></span>
			</p>
			<p id="add-results-embed" style="display:none">
				<i><?php echo $this->get_property('add_result_embed_text'); ?></i>
				[wpa-event id=<span></span>]
			</p>
			<div class="add-results-left">
				<div>
					<input style="background:#fff" size="30" id="athleteName" default-text="<?php echo $this->get_property('add_results_athlete_input_text'); ?>" class="ui-corner-all ui-widget ui-widget-content ui-state-default wpa-search wpa-search-disabled"></input>
					<span style="display:none;" id="add-athlete-cancel" class="input-cancel"></span>
				</div>
				<div id="create-new-athlete-container">
					<div id="add-result-or">OR</div>
					<div>
						<div id="create-athlete-button">
							<button><?php echo $this->get_property('add_result_create_athlete_button'); ?></button>
						</div>
					</div>
				</div>

				<br style="clear:both;"/>
			</div>

			<div class="success-message" id="show-result-success" style="display:none">
				<?php echo $this->get_property('add_result_success_message'); ?>
			</div>

			<div id="add-result-form" style="display:none">

				<!-- time fields -->
				<div id="add-result-time">
					<div time-format="h" class="wpa-add-result-field add-result-no-bg">
						<label><?php echo $this->get_property('add_result_event_time_hours'); ?>:</label>
						<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="h" maxlength="2" size="3" type="text" id="addResultTimeHours" value="0">
					</div>
					<div time-format="m" class="wpa-add-result-field add-result-no-bg">
						<label><?php echo $this->get_property('add_result_event_time_minutes'); ?>:</label>
						<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="m" maxlength="2" size="3" type="text" id="addResultTimeMinutes" value="0">
					</div>
					<div time-format="s" class="wpa-add-result-field add-result-no-bg">
						<label><?php echo $this->get_property('add_result_event_time_seconds'); ?>:</label>
						<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="s" maxlength="2" size="3" type="text" id="addResultTimeSeconds" value="0">
					</div>
					<div time-format="ms" class="wpa-add-result-field add-result-no-bg">
						<label><?php echo $this->get_property('add_result_event_time_milliseconds'); ?>:</label>
						<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" time-format="ms" maxlength="2" size="3" type="text" id="addResultTimeMilliSeconds" value="0">
					</div>
				</div>

				<!-- position -->
				<div class="wpa-add-result-field add-result-no-bg">
					<label><?php echo $this->get_property('add_result_event_position'); ?>:</label>
					<input class="ui-widget ui-widget-content ui-state-default ui-corner-all" size="3" type="text" id="addResultPosition" value="">
				</div>

				<!-- add result age class -->
				<div id="addResultAgeClass" class="wpa-add-result-field add-result-no-bg">
				<label><?php echo $this->get_property('add_result_age_class'); ?>:</label>
					<select></select>
					<span class="wpa-help" title="<?php echo $this->get_property('add_result_age_class_help'); ?>"></span>
					<span onclick="WPA.Admin.setAthleteDob()" class="wpa-admin-link-small"><?php echo $this->get_property('add_result_set_dob_text'); ?></span>
				</div>

				<!-- add results button -->
				<div id="add-result-submit">
					<button><?php echo $this->get_property('add_result_button_text'); ?></button>
				</div>

			</div>

		</div>

		<!-- CREATE USER DIALOG -->
		<div id="create-user-dialog" style="display:none">
			<div class="wpa-add-result-field add-result-no-bg">
				<label class="required"><?php echo $this->get_property('add_result_name'); ?>:</label>
				<input class="ui-widget ui-widget-content ui-state-default ui-corner-all add-result-required" size="30" maxlength=100 type="text" id="createUserName" />
			</div>
			<div class="wpa-add-result-field add-result-no-bg">
				<label class="required"><?php echo $this->get_property('my_profile_gender'); ?>:</label>
				<select id="createUserGender">
					<option value="M"><?php echo $this->get_property('gender_M'); ?></option>
					<option value="F"><?php echo $this->get_property('gender_F'); ?></option>
				</select>
			</div>
			<div class="wpa-add-result-field add-result-no-bg">
				<label><?php echo $this->get_property('my_profile_dob'); ?>:</label>
				<input readonly="readonly" class="ui-widget ui-widget-content ui-state-default ui-corner-all" size="20" type="text" id="createUserDob"/>
				<span class="wpa-help" title="<?php echo $this->get_property('add_result_create_user_dob_help'); ?>"></span>
			</div>
		</div>

		<!-- CREATE USER SUCCESS DIALOG -->
		<div id="create-user-success-dialog" style="display:none">
			<p><?php echo $this->get_property('add_result_create_user_success_text'); ?></p>
			<div>
				<b><span></span></b>
			</div>
		</div>

		<!-- GENDER DIALOG -->
		<div id="add-result-gender-dialog" style="display:none">
			<p><?php echo $this->get_property('add_result_gender_text'); ?></p>
			<select id="setGender">
				<option value="M"><?php echo $this->get_property('gender_M'); ?></option>
				<option value="F"><?php echo $this->get_property('gender_F'); ?></option>
			</select>
		</div>

		<!-- DOB DIALOG -->
		<div id="add-result-dob-dialog" style="display:none">
			<p><?php echo $this->get_property('add_result_dob_text'); ?></p>
			<div>
				<input readonly="readonly" class="ui-widget ui-widget-content ui-state-default ui-corner-all" size="20" type="text" id="setDob"/>
			</div>
		</div>

		<!-- ADD/EDIT EVENT DIALOG -->
		<?php $this->create_edit_event_dialog(); ?>

		<!-- COMMON DIALOGS -->
		<?php $this->create_common_dialogs(); ?>

	</div>

<?php
}
?>