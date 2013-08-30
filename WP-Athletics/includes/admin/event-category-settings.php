<?php
if ( $this->has_permission_to_manage() ) {
	global $current_user;
	$nonce = wp_create_nonce( $this->nonce );

?>
	<script type="text/javascript">

		WPA.Admin.loadEventCategories = function(data) {

			// add a blank row for adding a new category
			data.push({
				id: '0',
				name: '',
				unit: 'm',
				time_format: 'h:m:s',
				show_records: '0',
				distance: '',
				distance_meters: '0'
			})

			WPA.Admin.eventCatTable.fnClearTable();
			WPA.Admin.eventCatTable.fnAddData(data);

			// ensure the add new row is always editable
			WPA.Admin.editEventCategory(0);
		}

		WPA.Admin.toggleAddEventCategory = function(show) {
			if(show) {
				jQuery('.wpa-admin-add-new-row').show();
				jQuery('#wpa_event_name_0').focus();
			}
			else {
				jQuery('.wpa-admin-add-new-row').hide();
			}
		}

		WPA.Admin.deleteEventCategory = function(id) {
			jQuery.ajax({
				type: "post",
				url: WPA.Ajax.url,
				data: {
					action: 'wpa_admin_delete_event_category',
					id: id
				},
				success: function(result){
					if(result.success) {
						WPA.Admin.refreshEventCategories();
					}
				}
			});
		}

		WPA.Admin.editEventCategory = function(id) {
			jQuery('input[event_id="' + id + '"]').removeClass('wpa-admin-editable-disabled').removeAttr('disabled');
			jQuery('span[event_id="' + id + '"]').show();
			jQuery('#wpa_event_name_' + id).focus();
		}

		WPA.Admin.cancelEditEventCategory = function(id) {
			jQuery('input[event_id="' + id + '"]').addClass('wpa-admin-editable-disabled').attr('disabled', 'disabled');
			jQuery('span[event_id="' + id + '"]').hide();
		}

		WPA.Admin.refreshEventCategories = function() {
			jQuery.ajax({
				type: "post",
				url: WPA.Ajax.url,
				data: {
					action: 'wpa_get_event_categories'
				},
				success: function(result){
					if(result) {
						WPA.Admin.loadEventCategories(result);
					}
				}
			});
		}

		WPA.Admin.saveEventCategory = function(id) {
			var data = WPA.Admin.validateEventCategory(id);

			if(data) {

				data['distanceMeters'] = WPA.calculateDistanceInMeters(data.distance, data.unit);

				data['action'] = 'wpa_admin_save_event_categories';
				jQuery.ajax({
					type: "post",
					url: WPA.Ajax.url,
					data: data,
					success: function(result){
						if(id == 0) {
							WPA.Admin.toggleAddEventCategory(false);
						}
						else {
							WPA.Admin.cancelEditEventCategory(id);
						}

						if(result.success) {
							WPA.Admin.refreshEventCategories();
						}
					}
				});
			}
		}

		/**
		* Validates an event category. Returns false if the row is valid, otherwise returns array of error messages.
		*/
		WPA.Admin.validateEventCategory = function(id) {
			var errors = [];

			// collect the data
			var data = {
				name: jQuery('#wpa_event_name_' + id).val(),
				distance: jQuery('#wpa_event_distance_' + id).val(),
				unit: jQuery('#wpa_event_unit_' + id).val(),
				timeFormat: jQuery('#wpa_event_time_format_' + id).val(),
				showRecords: jQuery('#wpa_event_show_records_' + id).is(':checked') ? 1 : 0,
				type: 'running' // TODO add more support for other event types in future (e.g javelin)
			}

			// add id to data if it is set
			if(id) {
				data['id'] = id;
			}

			// validate name
			if(data.name == '') {
				errors.push(WPA.getProperty('admin_edit_event_cat_invalid_name'));
			}

			// validate distance
			if(data.distance == '' || WPA.isPositiveNumber(data.distance) == false) {
				errors.push(WPA.getProperty('admin_edit_event_cat_invalid_distance'));
			}

			// validate unit
			var unitReg = /\b(mile|m|km)\b/;
			if(data.unit == '' || unitReg.test(data.unit) == false) {
				errors.push(WPA.getProperty('admin_edit_event_cat_invalid_unit'));
			}

			// validate time format
			var validTimeFormat = true;
			var timeFormatReg = /\b(h|m|s|ms)\b/;
			var timeFormatArr = data.timeFormat.split(':');
			jQuery.each(timeFormatArr, function(index, format) {
				if(timeFormatReg.test(format) == false) {
					validTimeFormat = false;
					return;
				}
			});

			if(!validTimeFormat) {
				errors.push(WPA.getProperty('admin_edit_event_cat_invalid_time_format'));
			}

			// alert any errors
			if(errors.length) {
				WPA.alertErrors(errors);
			}

			return errors.length == 0 ? data : false;
		}

		jQuery(document).ready(function() {

			// set up ajax
			WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo WPA_PLUGIN_URL; ?>', '<?php echo $current_user->ID; ?>',  function() {

				// My Results table
				WPA.Admin.eventCatTable = jQuery('#event-cat-table').dataTable(WPA.createTableConfig({
					"bProcessing": true,
					"aaSorting": [[ 0, "asc" ]],
					"sDom": 'rt',
					"bPaginate": false,
					"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
						if(parseInt(aData['id']) == 0) {
							jQuery(nRow).addClass('wpa-admin-add-new-row').hide();
						}
					},
					"aoColumns": [{
						"mData": "distance_meters",
						"bVisible": false
					},{
						"mData": "id",
						"sWidth": "60px",
						"mRender": function(data, type, full) {
							if(parseInt(data) > 0) {
								return '<div class="datatable-icon delete" onclick="WPA.Admin.deleteEventCategory(' + data + ')" title="' + WPA.getProperty('delete') + '"></div>' +
								'&nbsp;<div id="event_edit_button_' + data + '" class="datatable-icon edit" onclick="WPA.Admin.editEventCategory(' + data + ')" title="' + WPA.getProperty('edit') + '"></div>';
							}
							return '';
						},
						"bSortable": false
					},{
						"mData": "name",
						"mRender": function(data, type, full) {
							return '<input size="15" disabled="disabled" class="wpa-admin-editable-disabled" value="' + data + '" event_id="' + full['id'] + '" type="text" id="wpa_event_name_' + full['id'] + '"/>';
						},
						"bSortable": false
					},{
						"mData": "distance",
						"sWidth": "70px",
						"bSortable": false,
						"mRender": function(data, type, full) {
							return '<input size="7" maxlength="10" disabled="disabled" class="wpa-admin-editable-disabled" value="' + data + '" event_id="' + full['id'] + '" type="text" id="wpa_event_distance_' + full['id'] + '"/>';
						},
					},{
						"mData": "unit",
						"sWidth": "70px",
						"bSortable": false,
						"sClass": "datatable-center",
						"mRender": function(data, type, full) {
							return '<input size="4" maxlength="6" disabled="disabled" class="wpa-admin-editable-disabled" value="' + data + '" event_id="' + full['id'] + '" type="text" id="wpa_event_unit_' + full['id'] + '"/>';
						},
					},{
						"mData": "time_format",
						"sWidth": "70px",
						"bSortable": false,
						"mRender": function(data, type, full) {
							return '<input size="6" maxlength="6" disabled="disabled" class="wpa-admin-editable-disabled" value="' + data + '" event_id="' + full['id'] + '" type="text" id="wpa_event_time_format_' + full['id'] + '"/>';
						},
					},{
						"mData": "show_records",
						"sWidth": "60px",
						"sClass": "datatable-center",
						"bSortable": false,
						"mRender": function(data, type, full) {
							var cancelFn = 'WPA.Admin.cancelEditEventCategory(' + full['id'] + ')';
							if(parseInt(full['id']) == 0) {
								// if we are adding a new event, the cancel function is different
								cancelFn = 'WPA.Admin.toggleAddEventCategory(false)';
							}

							return '<input type="checkbox" ' + (parseInt(data) == 1 ? 'checked="checked"' : '') + '" disabled="disabled" class="wpa-admin-editable-disabled" value="' + data + '" event_id="' + full['id'] + '" id="wpa_event_show_records_' + full['id'] + '"/>' +
							'<span event_id="' + full['id'] + '" class="wpa-admin-editable-save-cancel">' +
								'<span onclick="WPA.Admin.saveEventCategory(' + full['id'] + ')">' + WPA.getProperty('save') + '</span>&nbsp;' +
								'<span onclick="' + cancelFn + '">' + WPA.getProperty('cancel') + '</span>' +
							'</span>';
						}
					}]
				}));

				// create loading dialog
				WPA.createLoadingDialog();

				// create button
				jQuery('.wpa-admin-create-button button').button({
					icons: {
			        	primary: 'ui-icon-circle-plus'
			        }
				}).click(function(e) {
					e.preventDefault();
					WPA.Admin.toggleAddEventCategory(true);
				});

				// load the data
				WPA.Admin.loadEventCategories(WPA.globals.eventCategories);

				// tooltips
				jQuery(document).tooltip({
					track: true
				});

				// setup dialogs
				WPA.setupDialogs();
			});
		});
	</script>

	<div id="event-cat-table-container">

		<div>
			<div class="wpa-admin-title">
				<h2><?php echo $this->get_property('admin_edit_event_cat_title'); ?></h2>
			</div>
			<div class="wpa-admin-create-button">
				<button><?php echo $this->get_property('admin_edit_event_cat_create_button'); ?></button>
			</div>
			<br style="clear:both;"/>
		</div>

		<div>
			<table width="100%" class="display ui-state-default" id="event-cat-table">
				<thead>
					<tr>
						<th></th>
						<th></th>
						<th><?php echo $this->get_property('admin_column_event_cat_name') ?></th>
						<th><?php echo $this->get_property('admin_column_event_cat_distance') ?></th>
						<th><?php echo $this->get_property('admin_column_event_cat_unit') ?><span class="column-help" title="<?php echo $this->get_property('admin_edit_event_cat_column_unit'); ?>"></span></th>
						<th><?php echo $this->get_property('admin_column_event_cat_time_format') ?><span class="column-help" title="<?php echo $this->get_property('admin_edit_event_cat_column_time_format'); ?>"></span></th>
						<th><?php echo $this->get_property('admin_column_event_cat_show_records') ?><span class="column-help" title="<?php echo $this->get_property('admin_edit_event_cat_column_show_records'); ?>"></span></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>

	<?php $this->create_common_dialogs(); ?>

<?php
	}
?>