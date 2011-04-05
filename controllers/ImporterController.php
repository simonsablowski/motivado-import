<?php

class ImporterController extends Controller {
	protected $CoachingId = 1;
	protected $Coachings;
	protected $Objects;
	protected $ObjectTransitions;
	protected $xmlBuffer;
	
	protected function truncateTables() {
		return Database::query('TRUNCATE `object`') && Database::query('TRUNCATE `objecttransition`');
	}
	
	protected function readFiles() {
		$dir = dir($this->getConfiguration('pathModeling'));
		while (($file = $dir->read()) !== FALSE) {
			$pathFile = $this->getConfiguration('pathModeling') . $file;
			if (!is_file($pathFile)) continue;
			
			$this->scanFile($pathFile);
		}
		$dir->close();
	}
	
	protected function getActivities() {
		return $this->getXmlBuffer()->xpath('WorkflowProcesses/WorkflowProcess/Activities/Activity');
	}
	
	protected function getTransitions() {
		return $this->getXmlBuffer()->xpath('WorkflowProcesses/WorkflowProcess/Transitions/Transition');
	}
	
	protected function analyze($string) {
		preg_match('/\$(\w+):(\w+)\((.*)\)(.*)/i', $string, $matches);
		return $matches;
	}
	
	protected function scanFile($pathFile) {
		$data = preg_replace('/(xmlns=")(.+)(")/', '$1$3', file_get_contents($pathFile));
		$this->setXmlBuffer(new SimpleXMLElement($data));
		
		foreach ($this->getActivities() as $Activity) {
			if (isset($Activity->Route)) {
				//TODO: handle gateways
			}
			
			if (!isset($Activity->Implementation)) continue;
			
			$title = (string)$Activity->attributes()->Name;
			switch ($Activity->Implementation) {
				default:
				case 'TaskManual':
					$analysis = $this->analyze((string)$Activity->Description);
					list(, $type, $key, $properties, $description) = each($analysis);
					break;
				case 'TaskScript':
					$description = (string)$Activity->Description;
					if ($title && !$description) {
						$description = $text;
						$text = '';
					}
					$type = 'Text';
					break;
				case 'TaskReference':
					$type = 'Options';
					$properties = '';
					break;
				case 'Task':
					//TODO: handle anwering options
					break;
			}
			
			$Object = new Object(array(
				'CoachingId' => $this->getCoachingId(),
				'title' => $title,
				'description' => isset($description) ? $description : NULL,
				'type' => $type,
				'key' => isset($key) ? $key : NULL,
				'properties' => isset($properties) ? $properties : NULL
			));
			$Object->create();
			
			$this->Objects[(string)$Activity->attributes()->Id] = $Object;
		}
		
		foreach ($this->getTransitions() as $Transition) {
			if (isset($this->Objects[(string)$Transition->attributes()->From])) {
				$LeftId = $this->Objects[(string)$Transition->attributes()->From]->getId();
			} else {
				$LeftId = 0;
			}
			
			if (isset($this->Objects[(string)$Transition->attributes()->To])) {
				$RightId = $this->Objects[(string)$Transition->attributes()->To]->getId();
			} else {
				$RightId = 0;
			}
			
			$ObjectTransition = new ObjectTransition(array(
				'CoachingId' => $this->getCoachingId(),
				'LeftId' => $LeftId,
				'RightId' => $RightId,
				'condition' => (string)$Transition->attributes()->Name
			));
			$ObjectTransition->create();
			
			$this->ObjectTransitions[] = $ObjectTransition;
		}
	}
	
	protected function bla() {
		printf("<ul>\n");
		foreach ($this->getObjects() as $Object) {
			printf("<li>%s</li>\n", $Object->getTitle());
		}
		printf("<ul>\n");
	}
	
	public function index() {
		$this->truncateTables();
		$this->readFiles();
		$this->bla();
	}
}