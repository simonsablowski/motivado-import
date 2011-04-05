<?php

class ImporterController extends Controller {
	protected $CoachingId = 1;
	protected $Coachings = array();
	protected $Objects = array();
	protected $ObjectTransitions = array();
	protected $xmlStack = array();
	protected $nodePointer;
	
	protected function truncateTables() {
		return Database::query('TRUNCATE `object`') && Database::query('TRUNCATE `objecttransition`');
	}
	
	protected function analyze($string) {
		preg_match('/\$(\w+):(\w+)\((.*)\)(.*)/i', $string, $matches);
		array_shift($matches);
		return $matches;
	}
	
	protected function handleGateway($node) {
		$node;
		return TRUE;
	}
	
	protected function handleQuestion($node) {
		$node;
		return array(
			'type' => 'Options',
			'key' => NULL,
			'properties' => ''
		);
	}
	
	protected function pushOntoXmlStack($element) {
		return $this->xmlStack[] = $element;
	}
	
	protected function popOffXmlStack() {
		return end($this->xmlStack);
	}
	
	protected function getXmlBuffer() {
		return $this->popOffXmlStack();
	}
	
	protected function import($directory) {
		$path = $this->getConfiguration('pathModeling') . $directory;
		$path .= substr($path, -1) == '/' ? '' : '/';
		$this->scanFile($path . 'Start.xpdl');
	}
	
	protected function findNode($pattern) {
		return $this->setNodePointer($this->getXmlBuffer()->xpath($pattern));
	}
	
	protected function findStartNode() {
		return $this->setNodePointer(pos($this->getXmlBuffer()->xpath('WorkflowProcesses/WorkflowProcess/Activities/Activity/Event/StartEvent/parent::*/parent::*')));
	}
	
	protected function findNextNodes() {
		$id = $this->getNodePointer()->attributes()->Id;
		$nodes = array();
		foreach ($this->getXmlBuffer()->xpath(sprintf('WorkflowProcesses/WorkflowProcess/Transitions/Transition[@From="%s"]', $id)) as $transition) {
			$node = pos($this->getXmlBuffer()->xpath(sprintf('WorkflowProcesses/WorkflowProcess/Activities/Activity[@Id="%s"]/Implementation/Task/parent::*/parent::*', $transition->attributes()->To)));
			
			if ($this->registerNode($node)) $nodes[] = $node;
		}
		return $nodes;
	}
	
	protected function registerNode($node) {
		if (isset($this->Objects[$id = (string)$node->attributes()->Id])) {
			return $this->Objects[$id];
		}
		
		/*if (isset($node->Route)) {
			return $this->handleGateway($node);
		}*/
		
		$title = (string)$node->attributes()->Name;
		switch ($node->Implementation) {
			default:
			case 'TaskManual':
				$analysis = $this->analyze((string)$node->Description);
				list($type, $key, $properties, $description) = each($analysis);
				break;
			case 'TaskScript':
				$description = (string)$node->Description;
				if ($title && !$description) {
					$description = $title;
					$title = '';
				}
				$type = 'Text';
				break;
			case 'TaskReference':
				$analysis = $this->handleQuestion($node);
				list($type, $key, $properties) = each($analysis);
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
		$this->Objects[$id] = $Object;
		
		return $Object;
	}
	
	protected function traverseNodes() {
		if (!$this->getNodePointer()) return FALSE;
		$nodes = $this->findNextNodes();
		foreach ($nodes as $node) {
			$this->setNodePointer($node);
			return $this->traverseNodes();
		}
		return $nodes;
	}
	
	protected function scanFile($pathFile) {
		$data = preg_replace('/(xmlns=")(.+)(")/', '$1$3', file_get_contents($pathFile));
		$this->pushOntoXmlStack(new SimpleXMLElement($data));
		$this->findStartNode();
		return $this->traverseNodes();
	}
	
	public function index() {
		$this->truncateTables();
		$this->import('psychotest');
	}
}