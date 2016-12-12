<?php

$configuration = array();

$configuration['pathApplication'] = dirname(__FILE__) . '/';

$configuration['includeDirectories'] = array(
	$configuration['pathApplication'],
	$configuration['pathApplication'] . '../motivado-api/',
	$configuration['pathApplication'] . '../motivado-database/',
	$configuration['pathApplication'] . '../cheese/',
	$configuration['pathApplication'] . '../nacho/'
);

$configuration['Database'] = array(
	'type' => 'MySql',
	'host' => 'localhost',
	'name' => 'motivado',
	'user' => 'motivado',
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

$configuration['host'] = 'http://localhost';
$configuration['baseUrl'] = $configuration['host'] . '/motivado-import/';
$configuration['cheeseUrl'] = $configuration['host'] . '/cheese/';
$configuration['coachingDatabaseUrl'] = $configuration['host'] . '/motivado-database/';
$configuration['coachingTestUrl'] = $configuration['host'] . '/motivado-test/%s';

$configuration['pathModeling'] = $configuration['pathApplication'] . 'material/modeling/unapproved/';
$configuration['approvedPathModeling'] = $configuration['pathApplication'] . 'material/modeling/approved/';
$configuration['sourcePathModeling'] = $configuration['pathApplication'] . 'material/modeling/';
$configuration['fileExtensionModeling'] = '.xpdl';
$configuration['ignoreFilesModeling'] = array('.', '..', '.svn', 'attachments', 'bpm');

$configuration['clearTables'] = FALSE;

$configuration['encryptionKey'] = '***';
