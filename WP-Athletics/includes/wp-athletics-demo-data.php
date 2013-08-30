<?php

/**
 * Class for generating random sample data for debugging and testing
 */

if(!class_exists('WP_Athletics_Demo')) {

	class WP_Athletics_Demo extends WPA_Base {

		public $nonce = 'wpathleticsdemo';

		public $NUMBER_USERS = 500;
		public $NUMBER_RESULTS = 20000;
		public $NUMBER_EVENTS = 5000;

		public $users = array();
		public $events = array();

		/**
		 * default constructor
		 */
		public function __construct($db) {
			parent::__construct($db);
			$this->create_sample_data();
		}

		/**
		 * generates the random data
		 */
		public function create_sample_data() {
			wpa_log('Generating sample data');

			wpa_log('Clearing old sample data');
			$this-> wpa_db -> clear_sample_data();

			wpa_log('Generating sample users');
			$this -> generate_sample_users();

			wpa_log('Generating random events');
			$this -> generate_random_events();

			wpa_log('Generating random results');
			$this -> generate_random_results();
		}

		/**
		 * generates a random person
		 */
		public function generate_random_user() {

			$female_names = array('Sassy', 'Dee', 'Jess', 'Mary', 'Joan', 'Tarja', 'Nelly', 'Kathleen', 'Saoirse', 'Johanna', 'Melanie', 'Jill', 'Fidelma', 'Roya', 'Stella', 'Pippa', 'Rhona', 'Gillian', 'Jenny', 'Melissa', 'Jordan', 'Ann', 'Belinda', 'Mary', 'Sinead', 'Eileen', 'Rosy', 'Jackie');
			$male_names = array('Cormac', 'Stephen', 'Jack', 'Baz', 'Larry', 'Vernon', 'Steffan', 'Zach', 'Pete', 'Harry', 'Jack', 'John', 'Garry', 'Jason', 'Jonathan', 'Chris', 'Steve', 'Tom', 'James', 'Jerry', 'Rob', 'Conor', 'Barry', 'Evan', 'Ronan', 'Larry', 'Rory', 'Bryan', 'Colin', 'Percy', 'Winston');
			$surnames = array('Wilson', 'Gregson', 'Mahony', 'Moorhouse', 'Smith', 'Lyster', 'Sliney', 'Jonas', 'Fredrikson', 'Stenson', 'Ailsbury', 'Black', 'White', 'Green', 'Shaw', 'Kennedy', 'Scott', 'McDonald', 'Price', 'Cooper', 'McCauley', 'Lennon', 'Lauraeus', 'Kelly', 'Murphy', 'Perry', 'Jackson', 'McIlroy', 'Connors', 'Heuston', 'Simpson', 'Fielding');

			$gender = $this -> trueOrFalse() ? 'M' : 'F';

			$age_cats = array_keys($this -> wpa_db -> get_age_categories());
			$age_cat_idx = array_rand( $age_cats );
			$age_cat = $age_cats[$age_cat_idx];

			$first_name;

			if($gender ==  'M' ) {
				$first_name = $male_names[array_rand( $male_names )];
			}
			else {
				$first_name = $female_names[array_rand( $female_names )];
			}

			$name = $first_name . ' ' . $surnames[array_rand( $surnames )];

			return array( 'name' => $name, 'age_category' => $age_cat, 'gender' => $gender );
		}

		/**
		 * Generates a given number of random results
		 */
		public function generate_random_results() {

			for ( $i = 1; $i <= $this -> NUMBER_RESULTS; $i++) {
				$user = $this -> users[ array_rand( $this -> users ) ];
				$event = $this -> events[ array_rand( $this -> events ) ];
				$position = $this -> trueOrFalse() ? ( rand( 1,2000 ) ) : '-';
				$time = $this -> generate_random_time( $event['eventCategoryName'] );

				$data = array(
					'eventId' => $event['id'],
					'userId' => $user['user_id'],
					'ageCategory' => $user['age_cat'],
					'position' => $position,
					'garminId' => '',
					'gender' => $user['gender'],
					'time' => $time
				);
				$this -> wpa_db -> update_result( $data );
			}
		}

		/**
		 * Generates a given number of random events
		 */
		public function generate_random_events() {
			$event_locations = array('Dublin', 'Waterford', 'San Francisco', 'London', 'Paris', 'Wexford', 'Enniscorthy', 'Can Tho', 'Bermuda', 'Caribbean', 'Edinburgh', 'Chicago', 'Japan', 'China', 'Louth', 'Stockholm', 'Malmo', 'Venice', 'Vientiane', 'Kilkenny');
			$event_sub_names = array('Docklands', 'Warriers', 'Annual', 'Rock n Roll', 'Strawberry', 'Summer', 'Winter', 'Spring', 'Waterside', 'Coastal', 'Hillside', 'Mountain');
			$event_sub_types = $this -> wpa_db -> get_event_sub_types();
			$event_cats = $this -> wpa_db -> get_event_categories();

			for ( $i = 1; $i <= $this -> NUMBER_EVENTS; $i++) {
				$location = $event_locations[ array_rand( $event_locations ) ];
				$cat = $event_cats[ array_rand( $event_cats ) ];
				$sub_type = $this -> generate_random_sub_type( $cat['name'] );
				$name = $location . ' ' . $event_sub_names[ array_rand( $event_sub_names ) ] . ' ' . $cat['name'];
				$date = $this -> generate_random_date();

				$data = array(
					'eventLocation' => $location,
					'eventCategory' => $cat['id'],
					'eventCategoryName' => $cat['name'],
					'eventName' => $name,
					'eventDate' => $date,
					'eventSubType' => $sub_type
				);

				$data['id'] =  $this -> wpa_db -> create_event( $data );

				array_push( $this -> events, $data );
			}
		}

		/**
		 * Generates a random date
		 */
		public function generate_random_date() {
			$day = rand(1,28);
			$month = rand(1,12);
			$year = rand(1995,2013);
			return $year . '-' . ($month < 10 ? '0' : '') . $month . '-' . ($day < 10 ? '0' : '') . $day . ' 00:00:00';
		}

		/**
		 * Generates a random time relevant to the event category
		 */
		public function generate_random_sub_type( $event ) {

			$roadIndoor = array('T','I');
			$roadTrack = array('R','T', 'I');
			$roadTrailXC = array('R','TR','XC');

			if( $event == '100m') {
				return $roadIndoor[array_rand( $roadIndoor )];
			}
			else if( $event == '200m') {
				return $roadIndoor[array_rand( $roadIndoor )];
			}
			else if( $event == '400m') {
				return $roadIndoor[array_rand( $roadIndoor )];
			}
			else if( $event == '800m') {
				return $roadIndoor[array_rand( $roadIndoor )];
			}
			else if( $event == '1000m') {
				return $roadIndoor[array_rand( $roadIndoor )];
			}
			else if( $event == '1500m') {
				return $roadIndoor[array_rand( $roadIndoor )];
			}
			else if( $event == '3000m') {
				return $roadTrack[array_rand( $roadTrack )];
			}
			else if( $event == '1 mile') {
				return $roadTrack[array_rand( $roadTrack )];
			}
			else if( $event == '5k') {
				return $roadTrack[array_rand( $roadTrack )];
			}
			else if( $event == '4 miles') {
				return $roadTrailXC[array_rand( $roadTrailXC )];
			}
			else if( $event == '5 miles') {
				return $roadTrailXC[array_rand( $roadTrailXC )];
			}
			else if( $event == '10k') {
				return $roadTrailXC[array_rand( $roadTrailXC )];
			}
			else if( $event == '10 miles') {
				return $roadTrailXC[array_rand( $roadTrailXC )];
			}
			else return 'R';
		}

		/**
		 * Generates a random time relevant to the event category
		 */
		public function generate_random_time( $event ) {
			if( $event == '100m') {
				return rand( 10000,15000 );
			}
			else if( $event == '200m') {
				return rand( 20000,40000 );
			}
			else if( $event == '400m') {
				return rand( 20000,40000 );
			}
			else if( $event == '800m') {
				return rand( 40000,70000 );
			}
			else if( $event == '1000m') {
				return rand( 100000,150000 );
			}
			else if( $event == '1500m') {
				return rand( 150000,200000 );
			}
			else if( $event == '3000m') {
				return rand( 300000,400000 );
			}
			else if( $event == '1 mile') {
				return rand( 300000,420000 );
			}
			else if( $event == '5k') {
				return rand( 900000,1680000 );
			}
			else if( $event == '5 miles' || $event == '4 miles') {
				return rand( 1560000,2400000 );
			}
			else if( $event == '10k') {
				return rand( 2100000,2880000);
			}
			else if( $event == '10 miles') {
				return rand( 3300000,5100000 );
			}
			else if( $event == '1/2 Marathon') {
				return rand( 3600000,6000000 );
			}
			else if( $event == 'Marathon') {
				return rand( 8400000,18000000);
			}
			else if( $event == '50k') {
				return rand( 18000000,22000000 );
			}
			else return rand( 900000,2680000 );

		}

		/**
		 * Returns a 50/50 chance boolean
		 */
		public function trueOrFalse($chance = 50) {
			return (rand(1,100) <= $chance);
		}

		/**
		 * Generate users sample data
		 */
		public function generate_sample_users() {
			for ( $i = 1; $i <= $this -> NUMBER_USERS; $i++) {
				$user_email = 'sample_user_' . $i . '@polarworks.net';
				$user_name = 'sample_user_' . $i;

				$user_id = username_exists( $user_name );

				if ( !$user_id and email_exists( $user_email ) == false ) {
					$random_user = $this -> generate_random_user();
					$user_id = wp_create_user( $user_name, $user_name, $user_email );
					$this->wpa_db->update_user_display_name( $user_id, $random_user['name'] );

					// add to meta
					update_user_meta( $user_id, 'wp-athletics_dob', '04 Apr 1983' );
					update_user_meta( $user_id, 'wp-athletics_gender', $random_user['gender'] );

					array_push( $this->users, array(
							'user_id' => $user_id,
							'age_cat' => $random_user['age_category'],
							'gender' => $random_user['gender']
						)
					);
				} else {
					$random_password = __('User already exists.  Password inherited.');
				}
			}

			// add data for admin user (me!)
			array_push( $this->users, array(
				'user_id' => 0,
				'age_cat' => 'S',
				'gender' => 'M'
			));
		}
	}
}

?>