<?php
echo $this->localize('Unfortunately,') . ' ' . $this->localize('we encountered an error:') . "\n";
$fields = array('Type', 'Message');
if ($this->getApplication()->getConfiguration('debugMode')) {
	$fields = array_merge($fields, array('Details'));
}
foreach ($fields as $n => $field) {
	echo $this->localize($field) . ': ';
	$getter = 'get' . $field;
	if ($field != 'Details') {
		echo $this->localize($Error->$getter());
	} else {
		var_dump($Error->$getter());
	}
	echo "\n";
}