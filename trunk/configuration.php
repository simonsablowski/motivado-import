<?php

$configuration = array();

$configuration['pathApplication'] = dirname(__FILE__) . '/';

$configuration['pathApi'] = 'D:/Entwicklung/api/';

$configuration['baseUrl'] = 'http://localhost/importer/';

$configuration['cheeseUrl'] = 'http://localhost/cheese/';

$configuration['includeDirectories'] = array(
	$configuration['pathApplication'],
	$configuration['pathApi'],
	'D:/Entwicklung/nacho/'
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

$configuration['pathModeling'] = $configuration['pathApplication'] . 'material/modeling/';
$configuration['fileExtensionModeling'] = '.xpdl';
$configuration['ignoreFilesModeling'] = array('.', '..', '.svn', 'attachments');

$configuration['clearTables'] = TRUE;