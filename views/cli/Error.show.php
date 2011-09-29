<?php
print $this->localize('Unfortunately,') . ' ' . $this->localize('we encountered an error:') . "\n";
$fields = array('Type', 'Message');
if ($this->getApplication()->getConfiguration('debugMode')) {
	$fields = array_merge($fields, array('Details'));
}
foreach ($fields as $n => $field) {
	print $this->localize($field) . ': ';
	$getter = 'get' . $field;
	if ($field != 'Details') {
		print $this->localize($Error->$getter());
	} else {
		var_dump($Error->$getter());
	}
	print "\n";
}
exit(1);