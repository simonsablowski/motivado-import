<?php

$configuration = array();

$configuration['pathApplication'] = dirname(__FILE__) . '/';

$configuration['pathApi'] = 'D:/Webprojekte/motivado/api/';

$configuration['basePath'] = 'http://localhost/motivado/importer/';

$configuration['includeDirectories'] = array(
	$configuration['pathApplication'],
	$configuration['pathApi'],
	'D:/Webprojekte/nacho/'
);

$configuration['Database'] = array(
	'type' => 'MySql',
	'host' => 'localhost',
	'name' => 'motivado_importer',
	'user' => 'root',
	'password' => ''
);

$configuration['debugMode'] = TRUE;

$configuration['pathModeling'] = $configuration['pathApplication'] . 'material/modeling/';
$configuration['startFileNameModeling'] = 'Start.xpdl';
$configuration['ignoreDirectoriesModeling'] = array('.', '..', '.svn', 'attachments');