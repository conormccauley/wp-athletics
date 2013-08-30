<?php
if ( $this->has_permission_to_manage() ) {
	global $current_user;
	$nonce = wp_create_nonce( $this->nonce );

?>
	<script type="text/javascript">

		WPA.Admin.loadAgeCategories = function(data) {

			WPA.Admin.ageCategories = data;

			var dataArr = [];

			// add a blank row for adding a new category
			dataArr.push({
				id: '0',
				name: '',
				from: '0',
				to: '0'
			});

			// convert object to array
			jQuery.each(data, function(id, obj) {
				obj.id = id;
				dataArr.push(obj)
			});

			WPA.Admin.ageCatTable.fnClearTable();
			WPA.Admin.ageCatTable.fnAddData(dataArr);

			// ensure the add new row is always editable
			WPA.Admin.editAgeCategory('0');
		}

		WPA.Admin.toggleAddAgeCategory = function(show) {
			if(show) {
				jQuery('.wpa-admin-add-new-row').show();
				jQuery('#wpa_age_cat_name_0').focus();
			}
			else {
				jQuery('.wpa-admin-add-new-row').hide();
			}
		}

		WPA.Admin.deleteAgeCategory = function(id) {
			jQuery.ajax({
				type: "post",
				url: WPA.Ajax.url,
				data: {
					action: 'wpa_admin_delete_age_category',
					id: id
				},
				success: function(result){
					if(result.success) {
						WPA.Admin.refreshAgeCategories();
					}
				}
			});
		}

		WPA.Admin.editAgeCategory = function(id) {
			jQuery('input[cat_id="' + id + '"]').removeClass('wpa-admin-editable-disabled').removeAttr('disabled');
			jQuery('span[cat_id="' + id + '"]').show();
			jQuery('#wpa_age_cat_name_' + id).focus();
		}

		WPA.Admin.cancelEditAgeCategory = function(id) {
			jQuery('input[cat_id="' + id + '"]').addClass('wpa-admin-editable-disabled').attr('disabled', 'disabled');
			jQuery('span[cat_id="' + id + '"]').hide();
		}

		WPA.Admin.refreshAgeCategories = function() {
			jQuery.ajax({
				type: "post",
				url: WPA.Ajax.url,
				data: {
					action: 'wpa_get_age_categories'
				},
				success: function(result){
					if(result) {
						WPA.Admin.loadAgeCategories(result);
					}
				}
			});
		}

		WPA.Admin.saveAgeCategory = function(id) {
			var data = WPA.Admin.validateAgeCategory(id);

			if(data) {
				WPA.toggleLoading(true);
				data['action'] = 'wpa_admin_save_age_category';
				jQuery.ajax({
					type: "post",
					url: WPA.Ajax.url,
					data: data,
					success: function(result){
						WPA.toggleLoading(false);
						if(id == '0') {
							WPA.Admin.toggleAddAgeCategory(false)
						}
						else {
							WPA.Admin.cancelEditAgeCategory(id);
						}

						if(result.success) {
							WPA.Admin.refreshAgeCategories();
						}
					}
				});
			}
		}

		/**
		* Validates an event category. Returns false if the row is valid, otherwise returns array of error messages.
		*/
		WPA.Admin.validateAgeCategory = function(id) {
			var errors = [];

			// collect the data
			var data = {
				name: jQuery('#wpa_age_cat_name_' + id).val(),
				from: jQuery('#wpa_age_cat_from_' + id).val(),
				to: jQuery('#wpa_age_cat_to_' + id).val(),
				id: id
			}

			// validate name
			if(data.name == '') {
				errors.push(WPA.getProperty('admin_edit_age_cat_invalid_name'));
			}

			var validToFrom = true;

			// validate year from
			if(data.from == '' || WPA.isPositiveNumber(data.from, true) == false) {
				errors.push(WPA.getProperty('admin_edit_age_cat_invalid_from_year'));
				validToFrom = false;
			}

			// validate year to
			if(data.to == '' || WPA.isPositiveNumber(data.to) == false) {
				errors.push(WPA.getProperty('admin_edit_age_cat_invalid_to_year'));
				validToFrom = false;
			}

			// validate the age range does not clash with another age range
			if(validToFrom) {
				var from = parseInt(data.from);
				var to = parseInt(data.to);
				var ages = [];
				var age;

				// we now have an array of the ages we wish to add
				for(age = from; age < to; age++) {
					ages.push(age);
				}

				// check if any of the other ranges contain these ages, if so it's invalid
				jQuery.each(WPA.Admin.ageCategories, function(key, cat) {
					if(cat.id != data.id) {
						var catFrom = parseInt(cat.from);
						var catTo = parseInt(cat.to);
						for(var i = catFrom; i < catTo; i++) {
							if(jQuery.inArray(i, ages) > -1) {
								errors.push(WPA.getProperty('admin_edit_age_cat_invalid_range') + cat.name);
								validToFrom = false;
								return false;
							}
						}
					}
				});

				// check the from value is greater than the to value
				if(from >= to) {
					errors.push(WPA.getProperty('admin_edit_age_cat_from_greater_than_to'));
				}
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
				WPA.Admin.ageCatTable = jQuery('#age-cat-table').dataTable(WPA.createTableConfig({
					"bProcessing": true,
					"aaSorting": [[ 0, "asc" ]],
					"sDom": 'rt',
					"bPaginate": false,
					"fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
						if(aData['id'] == '0') {
							jQuery(nRow).addClass('wpa-admin-add-new-row').hide();
						}
					},
					"aoColumns": [{
						"mData": "from",
						"bSortable": true,
						"bVisible": false
					},{
						"mData": "id",
						"sWidth": "60px",
						"mRender": function(data, type, full) {
							if(data != '0') {
								return '<div class="datatable-icon delete" onclick="WPA.Admin.deleteAgeCategory(\'' + data + '\')" title="' + WPA.getProperty('delete') + '"></div>' +
								'&nbsp;<div id="event_edit_button_' + data + '" class="datatable-icon edit" onclick="WPA.Admin.editAgeCategory(\'' + data + '\')" title="' + WPA.getProperty('edit') + '"></div>';
							}
							return '';
						},
						"bSortable": false
					},{
						"mData": "name",
						"bSortable": false,
						"mRender": function(data, type, full) {
							return '<input size="15" disabled="disabled" class="wpa-admin-editable-disabled" value="' + data + '" cat_id="' + full['id'] + '" type="text" id="wpa_age_cat_name_' + full['id'] + '"/>';
						}
					},{
						"mData": "from",
						"bSortable": false,
						"mRender": function(data, type, full) {
							return '<input size="2" disabled="disabled" class="wpa-admin-editable-disabled" value="' + data + '" cat_id="' + full['id'] + '" type="text" id="wpa_age_cat_from_' + full['id'] + '"/>';
						}
					},{
						"mData": "to",
						"bSortable": false,
						"mRender": function(data, type, full) {

							var cancelFn = 'WPA.Admin.cancelEditAgeCategory(\'' + full['id'] + '\')';
							if(full['id'] == '0') {
								// if we are adding a new age cat, the cancel function is different
								cancelFn = 'WPA.Admin.toggleAddAgeCategory(false)';
							}

							return '<input size="2" disabled="disabled" class="wpa-admin-editable-disabled" value="' + data + '" cat_id="' + full['id'] + '" type="text" id="wpa_age_cat_to_' + full['id'] + '"/>' +
							'<span cat_id="' + full['id'] + '" class="wpa-admin-editable-save-cancel">' +
								'<span onclick="WPA.Admin.saveAgeCategory(\'' + full['id'] + '\')">' + WPA.getProperty('save') + '</span>&nbsp;' +
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
					WPA.Admin.toggleAddAgeCategory(true);
				});

				// load the data
				WPA.Admin.loadAgeCategories(WPA.globals.ageCategories);

				// tooltips
				jQuery(document).tooltip({
					track: true
				});

			});
		});
	</script>

	<div id="age-cat-table-container">

		<div>
			<div class="wpa-admin-title">
				<h2><?php echo $this->get_property('admin_edit_age_cat_title'); ?></h2>
			</div>
			<div class="wpa-admin-create-button">
				<button><?php echo $this->get_property('admin_edit_age_cat_create_button'); ?></button>
			</div>
			<br style="clear:both;"/>
		</div>

		<div>
			<table width="100%" class="display ui-state-default" id="age-cat-table">
				<thead>
					<tr>
						<th></th>
						<th></th>
						<th><?php echo $this->get_property('admin_column_age_cat_name') ?></th>
						<th><?php echo $this->get_property('admin_column_age_cat_from') ?></th>
						<th><?php echo $this->get_property('admin_column_age_cat_to') ?></th>
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