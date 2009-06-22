<?php
	
	class Extension_CalendarOverlay extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function about() {
			return array(
				'name'			=> 'Calendar Overlay',
				'version'		=> '1.0.3',
				'release-date'	=> '2009-04-02',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://pixelcarnage.com/',
					'email'			=> 'rowan@pixelcarnage.com'
				),
				'description'	=> 'Converts date fields into calendars.'
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'InitaliseAdminPageHead',
					'callback'	=> 'initaliseAdminPageHead'
				)
			);
		}
		
		public function initaliseAdminPageHead($context) {
			$page = $context['parent']->Page;
			
      $page->addScriptToHead(URL . '/extensions/calendaroverlay/assets/jquery.js', 346673);
            $page->addScriptToHead(URL . '/extensions/calendaroverlay/assets/datejs.core.js', 346675);
            $page->addScriptToHead(URL . '/extensions/calendaroverlay/assets/publish.js', 346676);
			$page->addStylesheetToHead(URL . '/extensions/calendaroverlay/assets/publish.css', 'screen', 346677);
		}
	}
		
?>