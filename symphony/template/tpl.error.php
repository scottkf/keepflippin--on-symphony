<?php
	
	include_once(TOOLKIT . '/class.htmlpage.php');
	
	$Page = new HTMLPage();
	
	$Page->Html->setElementStyle('html');
	
	$Page->Html->setDTD('<!DOCTYPE html>');
	$Page->Html->setAttribute('xml:lang', 'en');
	$Page->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
	$Page->addStylesheetToHead(URL . '/symphony/assets/error.css', 'screen', 30);

	$Page->addHeaderToPage('HTTP/1.0 500 Server Error');
	$Page->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
	$Page->addHeaderToPage('Symphony-Error-Type', 'generic');	
	if(isset($additional['header'])) $Page->addHeaderToPage($additional['header']);

	$Page->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), $heading)));
	
	$div = new XMLElement('div', NULL, array('id' => 'description'));
	$div->appendChild(new XMLElement('h1', $heading));
	$div->appendChild((is_object($errstr) ? $errstr : new XMLElement('p', trim($errstr))));
	$Page->Body->appendChild($div);

	print $Page->generate();

	exit();
	
