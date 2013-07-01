<?php

// WP Athletics Settings
return array(
	'create_demo_data_on_activate' => true, // must also be in WP_DEBUG mode for this to activate
	'display_date_format' => 'dd M yy',

	'default_age_categories' => array(
		'J' => array('name' => 'Junior', 'from' => 0, 'to' => 20),
		'S' => array('name' => 'Senior', 'from' => 20, 'to' => 35),
		'M35' => array('name' => '35-40', 'from' => 35, 'to' => 40),
		'M40' => array('name' => '40-45', 'from' => 40, 'to' => 45),
		'M45' => array('name' => '45-50', 'from' => 45, 'to' => 50),
		'M50' => array('name' => '50-55', 'from' => 50, 'to' => 55),
		'M55' => array('name' => '55-60', 'from' => 55, 'to' => 60),
		'M60' => array('name' => '60-65', 'from' => 60, 'to' => 65),
		'M65' => array('name' => '65-70', 'from' => 65, 'to' => 70),
		'M70' => array('name' => '70-75', 'from' => 70, 'to' => 75),
		'M75' => array('name' => '75-80', 'from' => 75, 'to' => 80),
		'M80' => array('name' => '80-85', 'from' => 80, 'to' => 85),
		'M85' => array('name' => '85-90', 'from' => 85, 'to' => 90),
		//'M90' => array('name' => '90-95', 'from' => 90, 'to' => 95),
		//'M95' => array('name' => '95-100', 'from' => 95, 'to' => 100)
	),

	'default_terrain_categories' => array(
		'R' => 'Road',
		'T' => 'Track',
		'I' => 'Indoor',
		'XC' => 'XC',
		'TR' => 'Trail'
	)
);

?>