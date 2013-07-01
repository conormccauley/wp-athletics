<?php

/**
 * Class for mananaging all database operations
 */

if(!class_exists('WP_Athletics_DB')) {

	class WP_Athletics_DB {

		public $EVENT_TABLE;
		public $EVENT_CAT_TABLE;
		public $RESULT_TABLE;

		/**
		 * Construct the 'my results' object
		 **/
		public function __construct() {
			global $wpdb;
			$this->RESULT_TABLE = $wpdb->prefix . "wpa_result";
			$this->EVENT_TABLE = $wpdb->prefix . "wpa_event";
			$this->EVENT_CAT_TABLE = $wpdb->prefix . "wpa_event_cat";
		}

		/**
		 * creates/updates the database tables
		 */
		public function create_db() {
			global $wpa_settings;
			$installed_ver = get_option( 'wp-athletics_db_version', 'not_installed');

			wpa_log('Installed DB version is ' . $installed_ver);
			wpa_log('Current DB version is ' . WPA_DB_VERSION);

			if(WP_DEBUG || $installed_ver != WPA_DB_VERSION ) {

				wpa_log('Creating ' . WPA_DB_VERSION . ' database tables');

				$sql = "CREATE TABLE $this->EVENT_TABLE (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				start_time varchar(10),
				name varchar(100) NOT NULL,
				event_cat_id mediumint(9) NOT NULL,
				sub_type_id varchar(2) NOT NULL,
				lat varchar(50),
				lng varchar(50),
				cost varchar(10),
				location varchar(100),
				address varchar(255),
				contact_name varchar(100),
				contact_email varchar(100),
				url varchar(55),
				UNIQUE KEY id (id)
				);

				CREATE TABLE $this->EVENT_CAT_TABLE (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				name tinytext NOT NULL,
				distance float(10) NOT NULL,
				unit varchar(10) NOT NULL,
				default_data boolean DEFAULT false,
				time_format varchar(6),
				UNIQUE KEY id (id)
				);

				CREATE TABLE $this->RESULT_TABLE (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				date_created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				user_id bigint(20) NOT NULL,
				event_id mediumint(9) NOT NULL,
				time bigint(10) NOT NULL,
				garmin_id varchar(100),
				position integer(4),
				age_category varchar(4),
				gender varchar(1),
				UNIQUE KEY id (id)
				);";

				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );

				update_option( "wp-athletics_db_version", WPA_DB_VERSION );

				// is this a first time install? if so, create event categories and user meta data
				if($installed_ver == 'not_installed') {
					$this->create_default_event_cats();

					// create sample data
					if( (bool)$wpa_settings['create_demo_data_on_activate'] ) {
						require_once 'wp-athletics-demo-data.php';
						new WP_Athletics_Demo( $this );
					}
				}
			}
		}

		/**
		 * Installs default categories into the event category table
		 */
		public function create_default_event_cats() {
			global $wpdb;
			$wpdb->query('delete from ' . $this->EVENT_CAT_TABLE . ' where default_data = true');
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '100m', 'distance' => 100, 'unit' => 'm', 'default_data' => true, 'time_format' => 's:ms' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '200m', 'distance' => 200, 'unit' => 'm', 'default_data' => true, 'time_format' => 's:ms' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '400m', 'distance' => 400, 'unit' => 'm', 'default_data' => true, 'time_format' => 's:ms' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '800m', 'distance' => 800, 'unit' => 'm', 'default_data' => true, 'time_format' => 'm:s:ms' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '1000m', 'distance' => 1000, 'unit' => 'm', 'default_data' => true, 'time_format' => 'm:s:ms' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '1500m', 'distance' => 1500, 'unit' => 'm', 'default_data' => true, 'time_format' => 'm:s:ms' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '1 mile', 'distance' => 1, 'unit' => 'mile', 'default_data' => true, 'time_format' => 'm:s:ms' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '3000m', 'distance' => 3000, 'unit' => 'm', 'default_data' => true, 'time_format' => 'm:s:ms' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '5k', 'distance' => 5, 'unit' => 'km', 'default_data' => true, 'time_format' => 'm:s' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '4 miles', 'distance' => 4, 'unit' => 'mile', 'default_data' => true, 'time_format' => 'h:m:s' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '5 miles', 'distance' => 5, 'unit' => 'mile', 'default_data' => true, 'time_format' => 'h:m:s' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '10k', 'distance' => 10, 'unit' => 'km', 'default_data' => true, 'time_format' => 'h:m:s' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '10 miles', 'distance' => 10, 'unit' => 'mile', 'default_data' => true, 'time_format' => 'h:m:s' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '1/2 Marathon', 'distance' => 21.0975, 'unit' => 'km', 'default_data' => true, 'time_format' => 'h:m:s' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => 'Marathon', 'distance' => 42.195, 'unit' => 'km', 'default_data' => true, 'time_format' => 'h:m:s' ) );
			$wpdb->insert( $this->EVENT_CAT_TABLE, array( 'name' => '50k', 'distance' => 50, 'unit' => 'km', 'default_data' => true, 'time_format' => 'h:m:s' ) );
		}

		/**
		 * Returns a list of results for a given event ID
		 * @param $event_id the event ID
		 */
		public function get_event_results($event_id) {
			global $wpdb;

			$wpdb->query('SET @rank=0;');

			$results = $wpdb->get_results(
					"
					SELECT @rank:=@rank+1 AS rank, r.id, r.user_id, r.time, r.age_category, r.garmin_id, r.position, ec.time_format,
					(SELECT display_name FROM wp_users WHERE id = r.user_id) as athlete_name
					FROM $this->RESULT_TABLE r
					LEFT JOIN $this->EVENT_TABLE e ON r.event_id = e.id
					LEFT JOIN $this->EVENT_CAT_TABLE ec ON e.event_cat_id = ec.id
					WHERE r.event_id = $event_id ORDER BY time asc
					"
			);
			return $results;
		}

		/**
		 * Gets the year of the oldest known result for a user. If no user ID is specified, will return oldest recorded record
		 */
		public function get_oldest_result_year( $user_id ) {
			global $wpdb;

			$where = $user_id ? 'WHERE r.user_id = ' . $user_id : '';

			$year = $wpdb->get_var(
				"SELECT date_format(e.date,'%Y') FROM $this->RESULT_TABLE r
				LEFT JOIN $this->EVENT_TABLE e ON r.event_id = e.id
				$where ORDER BY e.date ASC LIMIT 1"
			);

			return $year;
		}

		/**
		 * Returns a list of results for the provided criteria
		 * @param $user_id the user ID
		 */
		public function get_results($user_id, $request) {
			global $wpdb;

			$extraWhere = '';
			$sEcho =  (int) $request['sEcho'];
			$limit =  (int) $request['iDisplayLength'];
			$offset = (int) $request['iDisplayStart'];
			$numColumns = (int) $request['iColumns'];

			$sortCol = $this->convertResultColumn( $request['mDataProp_' . $request['iSortCol_0']] );
			$sortDir = $request['sSortDir_0'];

			$searchCategory;
			$searchSubType;
			$searchAgeCat;
			$searchPeriod;
			$searchEvent;

			// loops columns and set and process any filters that have been set
			for($i = 0; $i < $numColumns; $i++) {
				$param = $request['sSearch_' . $i];
				wpa_log('sSearch_' . $i . ' is ' . $param);
				if($param && $param != '') {
					$dataProp = $request['mDataProp_' . $i];
					wpa_log('mDataProp_' . $i . ' is ' . $dataProp);
					if($dataProp == 'category') {
						$searchCategory = $param;
					}
					else if($dataProp == 'event_sub_type_id') {
						$searchSubType = $param;
					}
					else if($dataProp == 'age_category') {
						$searchAgeCat = $param;
					}
					else if($dataProp == 'event_date') {
						$searchPeriod = $param;
					}
					else if($dataProp == 'event_name') {
						$searchEvent = $param;
					}
				}
			}

			// filters
			if(isset($searchCategory) && $searchCategory != 'all') {
				$extraWhere .= ' AND e.event_cat_id = ' . $searchCategory;
			}

			if(isset($searchSubType) && $searchSubType != 'all') {
				$extraWhere .= " AND e.sub_type_id = '" . $searchSubType . "'";
			}

			if(isset($searchAgeCat) && $searchAgeCat != 'all') {
				$extraWhere .= " AND r.age_category = '" . $searchAgeCat . "'";
			}

			if(isset($searchPeriod) && $searchPeriod != 'all') {
				$dateWhere = $this->convert_date( $searchPeriod, 'e.date' );
				if( $dateWhere != '' ) {
					$extraWhere .= ' AND ' . $dateWhere;
				}
			}

			if(isset($searchEvent) && $searchEvent != '') {
				$extraWhere .= " AND lower(e.name) like '%" . strtolower($searchEvent) . "%'";
			}

			$result_display_count = $wpdb->get_var(					"
				SELECT count(r.id) FROM $this->RESULT_TABLE r
				LEFT JOIN $this->EVENT_TABLE e ON r.event_id = e.id
				LEFT JOIN $this->EVENT_CAT_TABLE ec ON e.event_cat_id = ec.id
				WHERE r.user_id = $user_id $extraWhere
			");

			$results = $wpdb->get_results(
				"
				SELECT r.id, r.time, r.age_category, r.gender, r.garmin_id, r.position, e.id as event_id, e.name as event_name, e.location as event_location, e.sub_type_id AS event_sub_type_id,
				date_format(e.date,'" . WPA_DATE_FORMAT . "') as event_date, ec.name as category, ec.time_format, e.event_cat_id as event_cat
				FROM $this->RESULT_TABLE r
				LEFT JOIN $this->EVENT_TABLE e ON r.event_id = e.id
				LEFT JOIN $this->EVENT_CAT_TABLE ec ON e.event_cat_id = ec.id
				WHERE r.user_id = $user_id $extraWhere ORDER BY $sortCol $sortDir LIMIT $offset,$limit
				"
			);

			// loop each result and find the overall club rank for the age cat and event
			foreach ( $results as $result ) {
				$result->club_rank = $this->get_result_club_rank( $result->id, $result->age_category, $result->gender, $result->event_cat);
			}

			return array(
				'sEcho' => $sEcho,
				'iTotalRecords' => $this->get_result_count( $user_id, $request ),
				'iTotalDisplayRecords' => $result_display_count,
				'aaData' => $results
			);
		}

		/**
		 * Returns the overall club rank for a specified result, filtered by event category (e.g 1500m) and age category (e.g M)
		 */
		public function get_result_club_rank( $result_id, $age_category, $gender, $event_cat_id ) {
			global $wpdb;

			return $wpdb->get_var( "
				SELECT rank_table.rank FROM
					(SELECT r1.id as id, @curRow := @curRow+1 as rank FROM wp_wpa_result r1
					LEFT JOIN wp_wpa_event e1 ON r1.event_id = e1.id
					LEFT JOIN wp_wpa_event_cat ec1 ON e1.event_cat_id = ec1.id
					JOIN (SELECT @curRow := 0) crow
					WHERE r1.age_category = '$age_category' AND r1.gender = '$gender' AND e1.event_cat_id = $event_cat_id
					ORDER BY r1.time) rank_table
				WHERE rank_table.id = $result_id;
			" );
		}

		/**
		 * Returns a list of personal bests for each existing event category. If user is specified, will return results
		 * for that user, otherwise will return bests for the all athletes.
		 */
		public function get_personal_bests( $request ) {
			global $wpdb;

			wpa_log('getting event personal bests');

			$user_id = $_POST['userId'];
			$age_category = $_POST['ageCategory'];
			$gender = $_POST['gender'];
			$event_cat_id = $_POST['eventCategoryId'];
			$event_sub_type_id = $_POST['eventSubTypeId'];
			$date = $_POST['eventDate'];
			$where = '';
			$rank = '';
			$orderBy = 'event_cat_id,time';
			$groupByAndLimit = 'GROUP BY event_cat_id';
			$selectDisplayName = "(SELECT display_name FROM wp_users WHERE id = r.user_id) as athlete_name";

			if(isset( $user_id ) && $user_id != '' ) {
				wpa_log('user id is ' . $user_id);
				$where = $where . 'r.user_id = ' . $user_id;
				$selectDisplayName = "'' as athlete_name";
			}

			if(isset( $date ) && $date != 'all' && $date != '' ) {
				wpa_log('date is ' . $date);
				$dateWhere = $this->convert_date( $date );
				if( $dateWhere != '' ) {
					$where = $where . ($where == '' ? '' : ' AND ');
					$where = $where . $dateWhere;
				}
			}

			if(isset( $age_category ) && $age_category != '' && $age_category != 'all' ) {
				wpa_log('age cat is ' . $age_category);
				$where = $where . ($where == '' ? '' : ' AND ');
				$where = $where . "age_category = '" . $age_category . "'";
			}

			if(isset( $gender ) && $gender != '' ) {
				wpa_log('gender is ' . $gender);
				$where = $where . ($where == '' ? '' : ' AND ');
				$where = $where . "gender = '" . $gender . "'";
			}

			if(isset( $event_sub_type_id ) && $event_sub_type_id != 'all' && $event_sub_type_id != '' ) {
				wpa_log('sub type id is ' . $event_sub_type_id);
				$where = $where . ($where == '' ? '' : ' AND ');
				$where = $where . "sub_type_id = '" . $event_sub_type_id . "'";
			}

			if(isset( $event_cat_id ) && $event_cat_id != 'all' && $event_cat_id != '' ) {
				wpa_log('event cat is ' . $event_cat_id);
				$where = $where . ($where == '' ? '' : ' AND ');
				$where = $where . 'event_cat_id = ' . $event_cat_id;
				$groupByAndLimit = 'LIMIT 10';
				$orderBy = 'time';
				$wpdb->query('SET @rank=0;');
				$rank = '@rank:=@rank+1 AS rank,';
			}

			if( $where != '' ) {
				$where = 'WHERE ' . $where;
			}

			$results = $wpdb->get_results(
				"SELECT $rank d.id, d.athlete_name, d.gender, d.age_category, d.time, d.user_id, date_format(d.event_date,'" . WPA_DATE_FORMAT . "') as event_date, d.event_cat_id, d.event_name,
				d.event_location, d.event_sub_type_id, d.event_id, ec.name as category, ec.time_format, d.garmin_id from $this->EVENT_CAT_TABLE ec
				JOIN (
				SELECT r.id, r.gender, r.age_category, r.time AS time, r.garmin_id, r.user_id, ec1.id AS event_cat_id, e.name AS event_name, e.location as event_location,
				e.sub_type_id AS event_sub_type_id, e.id as event_id, e.date AS event_date," . $selectDisplayName . " from $this->RESULT_TABLE r
				LEFT JOIN $this->EVENT_TABLE e ON r.event_id = e.id
				LEFT JOIN $this->EVENT_CAT_TABLE ec1 ON e.event_cat_id = ec1.id " . $where . " ORDER BY $orderBy)
				d ON d.event_cat_id = ec.id " . $groupByAndLimit
			);

			// loop each result and find the overall club rank for the age cat and event
			foreach ( $results as $result ) {
				$result->club_rank = $this->get_result_club_rank( $result->id, $result->age_category, $result->gender, $result->event_cat_id );
			}

			return $results;
		}

		/**
		 * Converts a date paramters into a where clause
		 */
		public function convert_date( $date, $date_field = 'date' ) {
			$returnVal = '';
			if( $date != '' ) {
				if( $date == 'this_month' ) {
					$returnVal = ' YEAR(' . $date_field . ') = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) ';
				}
				else if( $date == 'this_year' ) {
					$returnVal = ' YEAR(' . $date_field . ') = YEAR(CURDATE()) ';
				}
				else if( false !== strpos( ' ' . $date, 'year:' ) ) {
					$yearArr = explode( ":", $date );
					if(count($yearArr) == 2 ) {
						$returnVal = ' YEAR(' . $date_field . ') = ' . $yearArr[1] . ' ';
					}
				}
			}
			return $returnVal;
		}

		/**
		 * Returns a list of event categories
		 */
		public function get_event_categories() {
			global $wpdb;

			wpa_log('getting event categories');

			$results = $wpdb->get_results(
					"
					SELECT id, name, time_format
					FROM $this->EVENT_CAT_TABLE
					"
			,ARRAY_A);
			return $results;
		}

		/**
		 * Returns list of results recorded for a user
		 */
		public function get_results_recorded( $user_id ) {
			global $wpdb;

			wpa_log('getting results recorded for ' . $user_id);

			return $wpdb->get_var( "SELECT COUNT(id) FROM $this->RESULT_TABLE WHERE user_id = $user_id" );
		}

		/**
		 * Returns list of events based on a search term
		 */
		public function get_events( $term ) {
			global $wpdb;

			wpa_log('getting events for search term "' . $term . '"');

			return $wpdb->get_results( "SELECT e.id AS value, CONCAT(e.name, ' (', ec.name, ', ', date_format(e.date,'%Y'), ')') AS label
					FROM $this->EVENT_TABLE e
					LEFT JOIN $this->EVENT_CAT_TABLE ec ON e.event_cat_id = ec.id
					WHERE LOWER(e.name) LIKE '%$term%' ORDER BY e.date DESC LIMIT 10" );
		}

		/**
		 * Returns a list of athletes based on a search term
		 */
		public function get_athletes( $term ) {
			global $wpdb;
			wpa_log('getting athletes for search term "' . $term . '"');
			return $wpdb->get_results( "SELECT id AS value, display_name AS label FROM wp_users WHERE LOWER(display_name) LIKE '%$term%' ORDER BY display_name ASC LIMIT 10" );
		}

		/**
		 * Returns information on a single event based on supplied ID
		 */
		public function get_event( $id ) {
			global $wpdb;

			wpa_log('getting single event by ID "' . $id . '"');

			return $wpdb->get_row( "SELECT id, name, event_cat_id, sub_type_id, location, date_format(date,'" . WPA_DATE_FORMAT . "') as date FROM $this->EVENT_TABLE WHERE id = $id"  );
		}

		/**
		 * Returns display name for a given user ID
		 */
		public function get_user_display_name( $user_id ) {
			global $wpdb;
			return $wpdb->get_var( "SELECT display_name FROM wp_users WHERE id = $user_id" );
		}

		/**
		 * updates or creates an event result
		 */
		function update_event( $data ) {
			global $wpdb;

			$isUpdate = isset($data['resultId']) && $data['resultId'] != '';
			$createEvent = $data['eventId'] == null || $data['eventId'] == '';

			// event does not exist, we'll create a new one
			if($createEvent) {
				$id = $this->create_event( $data );
				if( $id ) {
					wpa_log('created event with ID ' . $id);
					$data['eventId'] = $id;
				}
				else {
					die( $this->get_property( 'error_problem_creating_event') );
				}
			}

			$success = false;

			// create the event result
			if( !$isUpdate ) {
				$success = $wpdb->query( $wpdb->prepare(
					"
					INSERT INTO $this->RESULT_TABLE
					( user_id, event_id, time, garmin_id, position, age_category, gender )
					VALUES ( %d, %d, %d, %s, %d, %s, %s )
					",
					$data['userId'],
					$data['eventId'],
					$data['time'],
					$data['garminId'],
					$data['position'],
					$data['ageCategory'],
					$data['gender']
				) );
			}
			// update the event result
			else {
				$success = $wpdb->update(
						$this->RESULT_TABLE,
						array(
							'event_id' => $data['eventId'],
							'time' => $data['time'],
							'garmin_id' => $data['garminId'],
							'position' => $data['position'],
							'age_category' => $data['ageCategory']
						),
						array( 'id' => $data['resultId'] ),
						array(
							'%d',
							'%d',
							'%s',
							'%d',
							'%s'
						),
						array( '%d' )
				);
			}

			return $success;
		}

		/**
		 * deletes an event from the database based on ID
		 */
		function delete_event( $id ) {
			global $wpdb;
			$success = $wpdb->query(
				$wpdb->prepare(
					"
					DELETE FROM $this->RESULT_TABLE
					WHERE id = %d
					",
					$id
				)
			);

			if($success) {
				return array('success' => true);
			}
		}

		/**
		 * Retrieves single result info based on ID
		 */
		function get_result_info( $id ) {
			global $wpdb;
			$result = $wpdb->get_row(
				"
				SELECT r.id, r.time, r.garmin_id, r.position, r.event_id, r.age_category
				FROM $this->RESULT_TABLE r WHERE r.id = $id
				"
			);

			return $result;
		}

		/**
		 * Updates a user display name
		 */
		function update_user_display_name( $user_id, $display_name ) {
			global $wpdb;
			$success = $wpdb->update(
				'wp_users',
				array(
					'display_name' => $display_name
				),
				array( 'ID' => $user_id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		/**
		 * inserts a new event into the database
		 */
		function create_event( $data ) {
			global $wpdb;
			$success = $wpdb->query( $wpdb->prepare(
				"
				INSERT INTO $this->EVENT_TABLE
				( date, sub_type_id, name, location, event_cat_id )
				VALUES ( %s, %s, %s, %s, %d )
				",
				$data['eventDate'],
				$data['eventSubType'],
				$data['eventName'],
				$data['eventLocation'],
				$data['eventCategory']
			) );

			if($success) {
				return $wpdb->get_var( "SELECT LAST_INSERT_ID()" );
			}

			return false;
		}

		/**
		 * Checks if an alternative sort column should be used
		 */
		public function convertResultColumn( $column)  {
			if( $column == 'event_date' ) {
				return 'e.date';
			}
			return $column;
		}

		/**
		 * counts number of results available for a given user
		 */
		public function get_result_count($user_id, $request) {
			global $wpdb;
			return $wpdb->get_var( "SELECT COUNT(id) FROM $this->RESULT_TABLE WHERE user_id = $user_id" );
		}

		/**
		 * clear sample data before loading new data
		 */
		public function clear_sample_data() {
			global $wpdb;
			$wpdb->query("delete from wp_usermeta where user_id in (select id from wp_users where user_login like 'sample_user_%' )");
			$wpdb->query("delete from wp_users where user_login like 'sample_user_%'");
			$wpdb->query("delete from $this->EVENT_TABLE");
			$wpdb->query("delete from $this->RESULT_TABLE");
		}

		/**
		 * Removes all WPA tables and data
		 */
		public function uninstall_wpa() {
			$this->clear_sample_data();
			$this->remove_wpa_data();
			$this->uninstall_tables();
		}

		/**
		 * removes all the WP Athletics tables
		 */
		function uninstall_tables() {
			global $wpdb;
			$wpdb->query("drop table $this->EVENT_TABLE");
			$wpdb->query("drop table $this->RESULT_TABLE");
			$wpdb->query("drop table $this->EVENT_CAT_TABLE");
		}

		/**
		 * removes any WP Athletics fields from the usermeta and options table
		 */
		function remove_wpa_data() {
			global $wpdb;
			$wpdb->query("delete from wp_usermeta where meta_key like '%wp-athletics%'");
			$wpdb->query("delete from wp_options where option_name like '%wp-athletics%'");
		}
	}
}
?>