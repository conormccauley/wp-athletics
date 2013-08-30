<?php

/**
 * Class for displaying an event results table using shortcode
 */

if(!class_exists('WP_Athletics_Event_Results')) {

	class WP_Athletics_Event_Results extends WPA_Base {

		/**
		 * default constructor
		 */
		public function __construct( $db ) {
			parent::__construct( $db );
		}

		/**
		 * Enqueues scripts and styles
		 */
		public function enqueue_scripts_and_styles() {
			$this->enqueue_common_scripts_and_styles();
		}

		/**
		 * Generates a table of event results for a supplied event ID
		 */
		public function event_results( $atts = null ) {
			// ensure ID attribute is supplied
			if(isset( $atts ) && isset( $atts['id'] ) ) {
				global $current_user;
				global $wpa_settings;

				$id = $atts['id'];

				$this->enqueue_scripts_and_styles();
				$nonce = wp_create_nonce( $this->nonce );

				?>
				<script type='text/javascript'>
					jQuery(document).ready(function() {

						// set up ajax and retrieve my results
						WPA.Ajax.setup('<?php echo admin_url( 'admin-ajax.php' ); ?>', '<?php echo $nonce; ?>', '<?php echo WPA_PLUGIN_URL; ?>', '<?php echo $current_user->ID; ?>',  function() {

							// create the table
							WPA.createEventResultsDatatables(<?php echo $id?>);

							// common setup function
							WPA.setupCommon();

							// setup results dialog
							WPA.setupEditResultDialog(WPA.reloadEventResults);

							// load the event results
							WPA.loadEmbeddedEventResults(<?php echo $id?>);
						});
					});
				</script>

				<div class="wpa">

					<!-- RESULTS TABLE -->
					<?php echo $this->create_event_results_table('event-results-table-' . $id); ?>
					<div id="wpa-embedded-results-bottom">

						<div id="wpa-event-results-info-<?php echo $id; ?>" class="wpa-event-results-info">
							<span id="eventInfoName<?php echo $id; ?>"></span> -
							<span id="eventInfoDate<?php echo $id; ?>"></span> -
							<span id="eventInfoDetail<?php echo $id; ?>"></span>
						</div>

						<div id="wpa-embedded-results-options">
							<?php
							if(get_option('wp-athletics_records_mode') == 'combined') {
							?>
								<span onclick="window.location='<?php echo get_permalink(get_option('wp-athletics_records_page_id')); ?>'">
									<?php echo $this->get_property('embedded_event_results_club_records_link')?>
								</span>
							<?php
							} else {
							?>
								<span onclick="window.location='<?php echo get_permalink(get_option('wp-athletics_records_male_page_id')); ?>'">
									<?php echo $this->get_property('embedded_event_results_male_records_link')?>
								</span>
								<span onclick="window.location='<?php echo get_permalink(get_option('wp-athletics_records_female_page_id')); ?>'">
									<?php echo $this->get_property('embedded_event_results_female_records_link')?>
								</span>
							<?php
							}
							?>
							<span onclick="WPA.launchAddResultDialog(<?php echo $id ?>, true)"><?php echo $this->get_property('embedded_event_results_add_result_link')?></span>
						</div>
						<br style="clear:both;"/>
					</div>

					<!-- ADD/EDIT RESULTS DIALOG -->
					<?php $this->create_edit_result_dialog(); ?>

					<!-- COMMON DIALOGS -->
					<?php $this->create_common_dialogs(); ?>
				</div>
			<?php
			}
			else {
			?>
				<div>'[Error: ID attribute for the event result table was not supplied]'</div>
			<?php
			}
		}
	}
}
?>