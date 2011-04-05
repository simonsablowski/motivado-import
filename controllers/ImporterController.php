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
		preg_match('/\$(\w+):(\w+)\((.*)\)(.*)/is', $string, $matches);
		return array_map('trim', array_slice($matches, 1));
	}
	
	//TODO
	protected function handleGateway($node) {
		return TRUE;
	}
	
	//TODO
	protected function handleQuestion($node) {
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
		$pattern = 'WorkflowProcesses/WorkflowProcess/Activities/Activity';
		$pattern .= '/Event/StartEvent/parent::*/parent::*';
		return $this->setNodePointer(pos($this->getXmlBuffer()->xpath($pattern)));
	}
	
	protected function findNextNodes() {
		$id = $this->getNodePointer()->attributes()->Id;
		$nodes = array();
		$pattern = sprintf('WorkflowProcesses/WorkflowProcess/Transitions/Transition[@From="%s"]', $id);
		foreach ($this->getXmlBuffer()->xpath($pattern) as $transition) {
			$pattern = sprintf('WorkflowProcesses/WorkflowProcess/Activities/Activity[@Id="%s"]', $transition->attributes()->To);
			$pattern .= '/Implementation/Task/parent::*/parent::*';
			if ($array = $this->getXmlBuffer()->xpath($pattern)) {
				$node = pos($array);
				if ($this->registerNode($node)) $nodes[] = $node;
			}
		}
		return $nodes;
	}
	
	protected function registerNode($node) {
		if (isset($this->Objects[$id = (string)$node->attributes()->Id])) {
			return $this->Objects[$id];
		}
		
		$title = (string)$node->attributes()->Name;
		
		if (isset($node->Implementation->Task->TaskManual)) {
			list($type, $key, $properties, $description) = $this->analyze((string)$node->Description);
		} else if (isset($node->Implementation->Task->TaskScript)) {
			$description = (string)$node->Description;
			if ($title && !$description) {
				$description = $title;
				$title = '';
			}
			$type = 'Text';
		} else if (isset($node->Implementation->Task->TaskReference)) {
			list($type, $key, $properties) = $this->handleQuestion($node);
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
		var_dump(count($this->getObjects()));
	}
}