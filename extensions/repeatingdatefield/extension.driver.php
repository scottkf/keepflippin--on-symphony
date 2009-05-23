<?php
	
	class Extension_RepeatingDateField extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public static $params = null;
		
		public function about() {
			return array(
				'name'			=> 'Field: Repeating Date',
				'version'		=> '1.0.6',
				'release-date'	=> '2009-05-15',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://pixelcarnage.com/',
					'email'			=> 'rowan@pixelcarnage.com'
				),
				'description'	=> 'A field that generates, stores and filters repeating dates.'
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'ManipulatePageParameters',
					'callback'	=> 'buildParams'
				)
			);
		}
		
		public function uninstall() {
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_repeatingdate`");
		}
		
		public function install() {
			return $this->_Parent->Database->query("
				CREATE TABLE  `tbl_fields_repeatingdate` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`pre_populate` enum('yes','no') NOT NULL default 'no',
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
		}
		
		public function buildParams($context) {
			self::$params = $context['params'];
		}
		
	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/
		
		/**
		* Get the value of $today, or a fallback.
		* 
		* @return integer
		*/
		public function getToday() {
			$today = @strtotime(self::$params['today']);
			
			if (!$today) $today = time();
			
			return $today;
		}
		
		/**
		* Get the current or new link id for an entry.
		* 
		* @param integer $field_id The field
		* @param integer $entry_id The entry
		* @return string
		*/
		public function getEntryLinkId($field_id, $entry_id) {
			if ($entry_id) $link_id = $this->_Parent->Database->fetchVar('link_id', 0, "
				SELECT
					e.*
				FROM
					`tbl_entries_data_{$field_id}` as e
				WHERE
					e.entry_id = {$entry_id}
				LIMIT 1
			");
			
			if (!isset($link_id) or empty($link_id)) {
				$link_id = strtr((string)microtime(true), '.', '0');
			}
			
			return $link_id;
		}
		
		/**
		* Get the stored date for an entry.
		* 
		* @param integer $data Entry data
		* @return array
		*/
		public function getEntryDate($data, $field_id, $value = null) {
			$link_id = $data['link_id'];
			
			if ($value == null) {
				$value = $this->getToday();
			}
			
			$return = $this->_Parent->Database->fetchVar('value', 0, "
				SELECT
					d.value
				FROM
					`tbl_entries_data_{$field_id}_dates` as d
				WHERE
					d.link_id = {$link_id}
					AND d.value >= {$value}
				ORDER BY
					d.value ASC
				LIMIT
					1
			");
			
			if ($return == null) return $data['end'];
			
			return $return;
		}
		
		/**
		* Get the stored dates for an entry.
		* 
		* @param integer $data Entry data
		* @return array
		*/
		public function getEntryDates($data, $field_id, $limit = 10) {
			$limit = ((integer)$limit < 2 ? 2 : (integer)$limit);
			$link_id = $data['link_id'];
			$today = $this->getToday();
			
			$limit /= 2;
			$before = $this->_Parent->Database->fetch("
				SELECT
					d.value
				FROM
					`tbl_entries_data_{$field_id}_dates` as d
				WHERE
					d.link_id = {$link_id}
					AND d.value < {$today}
				ORDER BY
					d.value DESC
				LIMIT
					{$limit}
			");
			
			$limit++;
			$after = $this->_Parent->Database->fetch("
				SELECT
					d.value
				FROM
					`tbl_entries_data_{$field_id}_dates` as d
				WHERE
					d.link_id = {$link_id}
					AND d.value >= {$today}
				ORDER BY
					d.value ASC
				LIMIT
					{$limit}
			");
			
			if (empty($before)) $before = array();
			if (empty($after)) $after = array();
			
			return array($before, $after);
		}
		
		/**
		* Get the dates to store for an entry.
		* 
		* @param integer $data Entry data
		* @return array
		*/
		public function getDates($data) {
			$mode = strtolower($data['mode']);
			$start = $data['start'];
			$end = $data['end'];
			$units = $data['units'];
			
			switch ($mode) {
				case 'years-by-date':
					return $this->getDatesYearlyByDate($start, $end, $units);
					break;
				case 'years-by-weekday':
					return $this->getDatesYearlyByWeekday($start, $end, $units);
					break;
				case 'months-by-date':
					return $this->getDatesMonthlyByDate($start, $end, $units);
					break;
				case 'months-by-weekday':
					return $this->getDatesMonthlyByWeekday($start, $end, $units);
					break;
				case 'days':
					return $this->getDatesDailyByDay($start, $end, $units);
					break;
				default:
					return $this->getDatesWeeklyByWeekday($start, $end, $units);
					break;
			}
		}
		
	/*-------------------------------------------------------------------------
		Date calculation functions:
	-------------------------------------------------------------------------*/
		
		/**
		* Get an array of dates on a daily basis.
		* 
		* @param integer $start Date to start at
		* @param integer $finish Date to finish at
		* @param integer $skip Number of days to skip
		* @return array
		*/
		public function getDatesDailyByDay($start, $finish, $skip = 1) {
			$skip = ((integer)$skip > 0 ? (integer)$skip : 1);
			$current = $start; $dates = array();
			$results = 0;
		
			while ($current <= $finish and $results < 9999) {
				array_push($dates,$current);
			
				$current = strtotime("+{$skip} day", $current);
			}
		
			return $dates;
		}
	
		/**
		* Get an array of dates on a per week basis.
		* 
		* @param integer $start Date to start at
		* @param integer $finish Date to finish at
		* @param integer $skip Number of weeks to skip
		* @return array
		*/
		public function getDatesWeeklyByWeekday($start, $finish, $skip = 1) {
			$skip = ((integer)$skip > 0 ? (integer)$skip : 1);
			$current = $start; $dates = array();
			$results = 0;
			
			while ($current <= $finish and $results < 9999) {
				array_push($dates, $current);
				
				$current = strtotime("+{$skip} week", $current);
			}
			
			return $dates;
		}
	
		/**
		* Get an array of dates on a per month basis.
		* 
		* @param integer $start Date to start at
		* @param integer $finish Date to finish at
		* @param integer $skip Number of months to skip
		* @return array
		*/
		public function getDatesMonthlyByDate($start, $finish, $skip = 1) {
			$skip = ((integer)$skip > 0 ? (integer)$skip : 1);
			$current = $start; $dates = array();
			$day = date('j', $start);
			$results = 0;
		
			while ($current <= $finish and $results < 9999) {
				array_push($dates,$current);
			
				// Skip to the next month:
				$current = strtotime(date("Y-m-{$day} H:i:s", strtotime("+{$skip} month", $current)));
			}
		
			return $dates;
		}
	
		/**
		* Get an array of dates on a per month basis.
		* 
		* @param integer $start Date to start at
		* @param integer $finish Date to finish at
		* @param integer $skip Number of months to skip
		* @return array
		*/
		public function getDatesMonthlyByWeekday($start, $finish, $skip = 1) {
			$skip = ((integer)$skip > 0 ? (integer)$skip : 1);
			$current = $start; $dates = array();
			$weekday = date('D', $start);
			$week = $this->getWeekOfMonth($start);
			$results = 0;
		
			while ($current <= $finish and $results < 9999) {
				if (date('D', $current) == $weekday and $this->getWeekOfMonth($current) == $week) {
					array_push($dates,$current);
				
					// Skip to the next month:
					$current = strtotime(date('Y-m-01 H:i:s', strtotime("+{$skip} month", $current)));
				}
			
				$current = strtotime("+1 day", $current);
			}
		
			return $dates;
		}
	
		/**
		* Get an array of dates on a per year basis.
		* 
		* @param integer $start Date to start at
		* @param integer $finish Date to finish at
		* @param integer $skip Number of years to skip
		* @return array
		*/
		public function getDatesYearlyByDate($start, $finish, $skip = 1) {
			$skip = ((integer)$skip > 0 ? (integer)$skip : 1);
			$current = $start; $dates = array();
			$month = date('m-d', $start);
			$results = 0;
		
			while ($current <= $finish and $results < 9999) {
				array_push($dates,$current);
			
				// Skip to the next year:
				$current = strtotime(date("Y-{$month} H:i:s", strtotime("+{$skip} year", $current)));
			}
		
			return $dates;
		}
	
		/**
		* Get an array of dates on a per year basis.
		* 
		* @param integer $start Date to start at
		* @param integer $finish Date to finish at
		* @param integer $skip Number of years to skip
		* @return array
		*/
		public function getDatesYearlyByWeekday($start, $finish, $skip = 1) {
			$skip = ((integer)$skip > 0 ? (integer)$skip : 1);
			$current = $start; $dates = array();
			$month = date('m', $start);
			$weekday = date('D', $start);
			$week = $this->getWeekOfYear($start);
			$results = 0;
		
			while ($current <= $finish and $results < 9999) {
				if (date('D', $current) == $weekday and $this->getWeekOfYear($current) == $week) {
					array_push($dates,$current);
				
					// Skip to the next month:
					$current = strtotime(date("Y-{$month}-01 H:i:s", strtotime("+{$skip} year", $current)));
				}
			
				$current = strtotime("+1 day", $current);
			}
		
			return $dates;
		}
	
		/**
		* Get the week of the month by date
		* 
		* @param integer $date Date find the week of
		* @return integer
		*/
		public function getWeekOfMonth($date) {
			$start = strtotime(gmdate('Y-m-01', $date)) / (86400 * 7);
			$current = $date / (86400 * 7);
		
			return floor($current - $start) + 1;
		}
	
		/**
		* Get the week of the year by date
		* 
		* @param integer $date Date find the week of
		* @return integer
		*/
		public function getWeekOfYear($date) {
			$start = strtotime(gmdate('Y-01-01', $date)) / (86400 * 7);
			$current = $date / (86400 * 7);
		
			return floor($current - $start) + 1;
		}
	}
	
?>