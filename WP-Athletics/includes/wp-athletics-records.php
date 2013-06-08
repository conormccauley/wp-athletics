<?php

/**
 * Class for mananaging the querying and generation of club records
 */

if(!class_exists('WP_Athletics_Records')) {

	class WP_Athletics_Records extends WPA_Base {

		public $nonce = 'wpathleticsrecords';

		/**
		 * default constructor
		 */
		public function __construct($db) {
			parent::__construct($db);
		}

		/**
		 * Generates a 'records' page when the shortcode [wpa-records] is used
		 */
		public function records() {
			$this->enqueue_scripts_and_styles();

			// custom script
			wp_register_script( 'wpa-records', WPA_PLUGIN_URL . '/resources/scripts/wpa-records.js' );
			wp_enqueue_script( 'wpa-records' );

			global $current_user;
			$nonce = wp_create_nonce( $this->nonce );

			// retrieve arrays for select elements
			$age_cats = $this->get_age_categories();
			$sub_types = $this->get_event_sub_type();
			$event_cats = $this->wpa_db->get_event_categories();
			?>
				<script type='text/javascript'>
					jQuery(document).ready(function() {
						// load event types
						WPA.eventTypes = {};
						WPA.ageCats = {};
						<?php
						foreach ( $sub_types as $sub_type ) {
						?>
							WPA.eventTypes['<?php echo $sub_type['id']?>'] = '<?php echo $sub_type['description']?>'
						<?php
						}
						?>

						// set up ajax and retrieve my results
						WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', function() {

							// create tabs for each age category
							<?php
							foreach ( $age_cats as $age_cat ) {
							?>
								WPA.ageCats['<?php echo $age_cat['id']?>'] = '<?php echo $age_cat['description']?>'
								jQuery('#tabs ul').append('<li category="<?php echo $age_cat['id']?>"><a href="#tab-<?php echo $age_cat['id']?>"><?php echo $age_cat['description']?></a></li>');
								jQuery('#tabs').append('<div id="tab-<?php echo $age_cat['id']?>">' + WPA.Records.createTableHTML('<?php echo $age_cat['id']?>') + '</div>');
								WPA.Records.createDataTable('<?php echo $age_cat['id']?>');
							<?php
							}
							?>

							// set up tabs
							jQuery('#tabs').tabs({
								activate: function( event, ui ) {
									var category = ui.newTab.attr('category');
									WPA.Records.getPersonalBests(category);
								},
								create: function( event, ui ) {
									var category = ui.tab.attr('category');
									WPA.Records.getPersonalBests(category);
								}
							});
						});
					});
				</script>

				<!-- MY RESULTS TABS -->
				<div id="tabs">
				  <ul>
				  </ul>
				</div>

			<?php
		}

	}
}
?>