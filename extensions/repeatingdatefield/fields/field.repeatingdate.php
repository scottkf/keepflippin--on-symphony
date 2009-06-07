<?php
	
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	class FieldRepeatingDate extends Field {
		protected $_driver = null;
		protected $filter = array(0,0);
		
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function __construct(&$parent) {
			parent::__construct($parent);

			$this->_name = 'Repeating Date';
			$this->_driver = $this->_engine->ExtensionManager->create('repeatingdatefield');
		}
		
		public function createTable() {
			$field_id = $this->get('id');
			
			$this->_engine->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_{$field_id}` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`entry_id` int(11) unsigned NOT NULL,
					`link_id` bigint(20) unsigned NOT NULL,
					`start` int(11) default NULL,
					`end` int(11) default NULL,
					`units` int(11) unsigned NOT NULL default 1,
					`mode` enum(
						'days',
						'weeks',
						'months-by-date',
						'months-by-weekday',
						'years-by-date',
						'years-by-weekday'
					) NOT NULL default 'days',
					PRIMARY KEY (`id`),
					KEY `entry_id` (`entry_id`),
					KEY `link_id` (`link_id`),
					KEY `start` (`start`),
					KEY `end` (`end`)
				)
			");
			
			$this->_engine->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_{$field_id}_dates` (
					`id` int(11) NOT NULL auto_increment,
					`link_id` bigint(20) default NULL,
					`value` int(11) NOT NULL default '0',
					PRIMARY KEY  USING BTREE (`id`),
					KEY `link_id` (`link_id`),
					KEY `value` (`value`)
				)
			");
		}
		public function allowDatasourceOutputGrouping(){
			return true;
		}
		
		public function isSortable() {
			return true;
		}
		
		public function canPrePopulate() {
			return true;
		}
		
		public function canFilter() {
			return true;
		}
		
	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/
		
		public function findDefaults(&$fields){	
			if (!isset($fields['pre_populate'])) $fields['pre_populate'] = 'yes';
		}
		
		public function displaySettingsPanel(&$wrapper) {
			parent::displaySettingsPanel($wrapper);
			
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][pre_populate]', 'yes', 'checkbox');
			
			if ($this->get('pre_populate') == 'yes') {
				$input->setAttribute('checked', 'checked');
			}
			
			$label->setValue($input->generate() . ' Pre-populate this field with today\'s date');
			$wrapper->appendChild($label);		
			
			$this->appendShowColumnCheckbox($wrapper);
		}
		
		public function commit() {
			if (!parent::commit()) return false;
			
			$id = $this->get('id');
			$handle = $this->handle();
			
			if ($id === false) return false;	
			
			$fields = array();
			
			$fields['field_id'] = $id;
			$fields['pre_populate'] = ($this->get('pre_populate') ? $this->get('pre_populate') : 'no');
			
			$this->_engine->Database->query("DELETE FROM `tbl_fields_{$handle}` WHERE `field_id` = '{$id}' LIMIT 1");			
			$this->_engine->Database->insert($fields, "tbl_fields_{$handle}");
		}
		
	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/
		
		public function displayPublishPanel(&$wrapper, $data = null, $error = null, $prefix = null, $suffix = null) {
			// $this->_engine->Page->addStylesheetToHead(URL . '/extensions/repeatingdatefield/assets/publish.css', 'screen', 9172332);
			
			$label = new XMLElement('div', $this->get('label'));
			$label->setAttribute('class', 'repeatingdate');
			$div = new XMLElement('div');
			
			$this->displayDate('Start', $div, $data, $prefix, $suffix);
			$this->displayDate('End', $div, $data, $prefix, $suffix);
			$this->displayMode($div, $data, $prefix, $suffix);
			
			$label->appendChild($div);
			
			if ($error) {
				$label = Widget::wrapFormElementWithError($label, $error);
			}
			
			$wrapper->appendChild($label);
		}
		
		protected function displayDate($type, $wrapper, $data, $prefix = null, $suffix = null) {
			$timestamp = null;
			$name = $this->get('element_name');
			$subname = strtolower($type);
			
			if ($data) {
				$value = $data[$subname];
				$timestamp = (!is_numeric($value) ? strtotime($value) : $value);
			}
			
			$label = Widget::Label("{$type} date");
			$label->appendChild(Widget::Input(
				"fields{$prefix}[{$name}][{$subname}]{$suffix}", (
					$data || $this->get('pre_populate') == 'yes'
					? DateTimeObj::get(__SYM_DATETIME_FORMAT__, $timestamp) : null
				)
			));
			
			$label->setAttribute('class', 'date');
			$wrapper->appendChild($label);
		}
		
		protected function displayMode($wrapper, $data, $prefix = null, $suffix = null) {
			$name = $this->get('element_name');
			$units = @((integer)$data['units'] > 0 ? (integer)$data['units'] : 1);
			
			$input = Widget::Input(
				"fields{$prefix}[{$name}][units]{$suffix}", $units
			);
			$input->setAttribute('size', '2');
			
			$modes = array(
				array(
					'days', false, 'Days'
				),
				array(
					'weeks', false, 'Weeks'
				),
				array(
					'months-by-date', false, 'Months (by Date)'
				),
				array(
					'months-by-weekday', false, 'Months (by Weekday)'
				),
				array(
					'years-by-date', false, 'Years (by Date)'
				),
				array(
					'years-by-weekday', false, 'Years (by Weekday)'
				)
			);
			
			foreach ($modes as $index => $mode) {
				if ($mode[0] == @$data['mode']) {
					$modes[$index][1] = true;
				}
			}
			
			$select = Widget::Select(
				"fields{$prefix}[{$name}][mode]{$postfix}", $modes
			);
			
			$label = new XMLElement('p');
			$label->setValue('<label>Repeat every ' . $input->generate() . '</label>' . $select->generate());
			
			$wrapper->appendChild($label);
		}
		
	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/
		
		protected function validateDate($date) {
			$string = trim((string)$date);
			
			if (empty($string)) return false;
			
			if (strtotime($string) === false) return false;
			
			return true;	
		}
		
		public function checkPostFieldData($data, &$message, $entry_id = null) {
			$status = self::__OK__;
			$data = array_merge(array(
				'start'	=> null,
				'end'	=> null,
				'units'	=> null,
				'mode'	=> null
			), $data);
			$message = null;
			
			if (!$this->validateDate($data['start'])) {
				$message = "The start date specified in '". $this->get('label')."' is invalid.";
				$status = self::__INVALID_FIELDS__;
				
			} else if (!$this->validateDate($data['end'])) {
				$message = "The end date specified in '". $this->get('label')."' is invalid.";
				$status = self::__INVALID_FIELDS__;
				
			} else if ((integer)$data['units'] < 0) {
				$message = "The number of repeats specified in '". $this->get('label')."' must be greater or equal to 1.";
				$status = self::__INVALID_FIELDS__;
			}
			
			return $status;		
		}
		
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
			$status = self::__OK__;
			$data = array_merge(array(
				'start'    => null,
				'end'    => null,
				'units'    => 1,
				'mode'    => 'weeks'
				), $data);

			$data['start'] = strtotime(DateTimeObj::get('c', strtotime($data['start'])));
			$data['end'] = strtotime(DateTimeObj::get('c', strtotime($data['end'])));
			$data['units'] = (integer)$data['units'];

			// Build data:
			if (!$simulate) {
				$field_id = $this->get('id');
				$link_id = $this->_driver->getEntryLinkId($field_id, $entry_id);
				$data['link_id'] = $link_id;

				$dates = $this->_driver->getDates($data);

				// Remove old dates:
				$this->_engine->Database->query("
					DELETE QUICK FROM
					`tbl_entries_data_{$field_id}_dates`
					WHERE
					`link_id` = {$link_id}
				");

				// Insert new dates:
				foreach ($dates as $date) {
					$this->_engine->Database->query("
						INSERT INTO
						`tbl_entries_data_{$field_id}_dates`
						SET
						`link_id` = {$link_id},
						`value` = {$date}
					");
				}

				// Clean up indexes:
				$this->_engine->Database->query("
					OPTIMIZE TABLE
					`tbl_entries_data_{$field_id}_dates`
					");
			}

			return $data;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/
		
		public function appendFormattedElement(&$wrapper, $data, $encode = false) {
			$dates = $this->_driver->getEntryDates($data, $this->get('id'), $this->_Parent->filter);
			$element = new XMLElement($this->get('element_name'));

			$element->appendChild(General::createXMLDateObject($data['start'], 'start'));
			
			// make sure not to print all the dates without a filter otherwise it pollutes the xml
			if ($this->_Parent->filter)
				foreach ($dates[0] as $index => $date) {
					$element->appendChild(General::createXMLDateObject($date['value'], 'current'));
				}
			
			// foreach ($dates[1] as $index => $date) {
			// 	$element->appendChild(General::createXMLDateObject($date['value'], ($index == 0 ? 'current' : 'after')));
			// }
			
			$element->appendChild(General::createXMLDateObject($data['end'], 'end'));
			
			$element->setAttribute('mode', @$data['mode']);
			$element->setAttribute('units', @$data['units']);
			
			$wrapper->appendChild($element);
		}
		
		public function prepareTableValue($data, XMLElement $link = null) {
			$date = $this->_driver->getEntryDate($data, $this->get('id'));
			$date = DateTimeObj::get('D h:i a Y', $date); //  a, j M Y
			return parent::prepareTableValue(
				array(
					'value' => "{$date}"
				), $link
			);
		}
		
		
		/*-------------------------------------------------------------------------
			Grouping:
		-------------------------------------------------------------------------*/

		public function groupRecords($records){
			if(!is_array($records) || empty($records)) return;

			$groups = array('year' => array());


			// cache all the days ahead of time, otherwise it results in a ton of queries
			foreach($records as $r){
				$d = $r->getData($this->get('id'));
				$cache[$r->_fields['id']] = $d['link_id'];
			}
			$dates = $this->_driver->getEntryDates($cache,$this->get('id'),$this->_Parent->filter, 99); // this will appear at most 31 times in one month, hack
			foreach ($dates[0] as $v) {
				$days[$v['link_id']][] = $v['value'];
			}
			foreach($records as $r){

				foreach($days[$cache[$r->_fields['id']]] as $d) {
					$info = getdate($d);
					$year = $info['year'];
					$month = ($info['mon'] < 10 ? '0' . $info['mon'] : $info['mon']);
					$day = ($info['mday'] < 10 ? '0' . $info['mday'] : $info['mday']);

					if(!isset($groups['year'][$year])) $groups['year'][$year] = array('attr' => array('value' => $year),
																					  'records' => array(), 
																					  'groups' => array());

					if(!isset($groups['year'][$year]['groups']['month'])) $groups['year'][$year]['groups']['month'] = array();

					if(!isset($groups['year'][$year]['groups']['month'][$month])) $groups['year'][$year]['groups']['month'][$month] = array('attr' => array('value' => $month),
																					  					  'records' => array(), 
																					  					  'groups' => array());		

					if(!isset($groups['year'][$year]['groups']['month'][$month]['groups']['day'])) $groups['year'][$year]['groups']['month'][$month]['groups']['day'] = array();

					if(!isset($groups['year'][$year]['groups']['month'][$month]['groups']['day'][$day])) $groups['year'][$year]['groups']['month'][$month]['groups']['day'][$day] = array('attr' => array('value' => $day),
																					  					  'records' => array(), 
																					  					  'groups' => array());		
					$groups['year'][$year]['groups']['month'][$month]['groups']['day'][$day]['records'][] = $r;
				}

			}

			return $groups;

		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/
		
		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			$filter = preg_split('/(.*) to (.*)/', $data[0], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			if (count($filter) == 2) {
				$this->_Parent->filter = array(strtotime($filter[0]),strtotime($filter[1]));
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}
						ON (e.id = t{$field_id}.entry_id)
				";
				$where .= "
					AND ((t{$field_id}.start <= {$this->_Parent->filter[0]} AND t{$field_id}.end >= {$this->_Parent->filter[1]})
						OR t{$field_id}.start >= {$this->_Parent->filter[0]} OR t{$field_id}.end <= {$this->_Parent->filter[1]})
					";
					echo $joins." $where\n";
			} else {
				$this->_Parent->filter[0] = array(strtotime(@$data[0]));

	      $joins .= "
	        LEFT JOIN
	          `tbl_entries_data_{$field_id}` AS t{$field_id}
	          ON (e.id = t{$field_id}.entry_id)
	      ";
	      $where .= "
	        AND t{$field_id}.end > {$this->filter}
	      ";
			}
			return true;
		}
		
	/*-------------------------------------------------------------------------
		Sorting:
	-------------------------------------------------------------------------*/
		
		public function buildSortingSQL(&$joins, &$where, &$sort, $order = 'ASC') {
			$field_id = $this->get('id');
			$joins .= "
				INNER JOIN
					`tbl_entries_data_{$field_id}` AS f
					ON (e.id = f.entry_id)
			";
			
			if (strtolower($order) == 'random') {
				$sort = 'ORDER BY RAND()';
				
			} else {
				$today = $this->_driver->getToday();
				$sort = "
					ORDER BY
						(
							SELECT
								d.value
							FROM
								`tbl_entries_data_{$field_id}_dates` d
							WHERE
								f.link_id = d.link_id
								AND d.value >= {$today}
							ORDER BY
								d.value ASC
							LIMIT 1
						) {$order}
				";
			}
		}
	}
	
?>