<?php

$configuration = array();

$configuration['pathApplication'] = dirname(__FILE__) . '/';

$configuration['pathApi'] = 'D:/Entwicklung/api/';

$configuration['basePath'] = 'http://localhost/importer/';

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

$configuration['Request'] = array(
	'defaultQuery' => 'Importer/index'
);

$configuration['debugMode'] = TRUE;

$configuration['pathModeling'] = $configuration['pathApplication'] . 'material/modeling/';
$configuration['startFileNameModeling'] = 'Start.xpdl';
$configuration['ignoreDirectoriesModeling'] = array('.', '..', '.svn', 'attachments');