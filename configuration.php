<?php

$configuration = array();

$configuration['pathApplication'] = dirname(__FILE__) . '/';

$configuration['baseUrl'] = 'http://localhost/coaching-import/';
$configuration['cheeseUrl'] = 'http://localhost/cheese/';
$configuration['coachingDatabaseUrl'] = 'http://localhost/coaching-database/';
$configuration['coachingTestUrl'] = 'http://localhost/coaching-test/%s';

$configuration['includeDirectories'] = array(
	$configuration['pathApplication'],
	'D:/Entwicklung/api/',
	'D:/Entwicklung/coaching-database/',
	'D:/Entwicklung/cheese/',
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
	'default' => 'en',
	'de' => array(
		'language' => 'de',
		'locale' => 'de_DE',
		'name' => 'Deutsch'
	),
	'en' => array(
		'language' => 'en',
		'locale' => 'en_GB',
		'name' => 'English'
	)
);

$configuration['Request'] = array(
	'defaultQuery' => 'index',
	'aliasQueries' => array()
);

$configuration['debugMode'] = TRUE;

// $configuration['executionTime'] = 120;

$configuration['pathModeling'] = $configuration['pathApplication'] . 'material/modeling/';
$configuration['approvedPathModeling'] = $configuration['pathModeling'] . 'approved/';
$configuration['sourcePathModeling'] = 'ftp://gast:gast@192.168.3.102/disk1/share/Inhalt/Modellierung/';
$configuration['fileExtensionModeling'] = '.xpdl';
$configuration['ignoreFilesModeling'] = array('.', '..', '.svn', 'attachments', 'bpm');

$configuration['clearTables'] = FALSE;

$configuration['encryptionKey'] = 'hkRTwjHneHf83Gb2wf8z';