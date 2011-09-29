<?php
print $this->localize('Import') . "\n";
foreach ($Coachings as $n => $Coaching) {
	print $n + 1 . ': ' . $Coaching->getKey() . ' (' . $this->localize('%d ' . (($count = count($Coaching->getObjects())) == 1 ? $this->localize('object') : $this->localize('objects')), $count) . ')' . "\n";
}
exit(0);