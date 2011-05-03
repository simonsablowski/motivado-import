<?php

$configuration = array();

$configuration['pathApplication'] = dirname(__FILE__) . '/';

$configuration['baseUrl'] = 'http://localhost/importer/';
$configuration['cheeseUrl'] = 'http://localhost/cheese/';

$configuration['includeDirectories'] = array(
	$configuration['pathApplication'],
	'D:/Entwicklung/api/',
	'D:/Entwicklung/nacho/',
	'D:/Entwicklung/cheese/'
);

$configuration['Database'] = array(
	'type' => 'MySql',
	'host' => 'localhost',
	'name' => 'motivado_importer',
	'user' => 'root',
	'password' => ''
);

$configuration['Localization'] = array(
	'default' => 'de_DE',
	'de_DE' => array(
		'language' => 'de_DE',
		'locale' => 'de_DE',
		'name' => 'Deutsch'
	)
);

$configuration['Request'] = array(
	'defaultQuery' => 'Importer/index'
);

$configuration['debugMode'] = TRUE;

// $configuration['executionTime'] = 120;

$configuration['pathModeling'] = $configuration['pathApplication'] . 'material/modeling/';
// $configuration['pathModeling'] = 'ftp://gast:gast@192.168.3.102/disk1/share/Inhalt/Modellierung_Kopie/';
$configuration['fileExtensionModeling'] = '.xpdl';
$configuration['ignoreFilesModeling'] = array('.', '..', '.svn', 'attachments', 'bpm');

$configuration['clearTables'] = TRUE;