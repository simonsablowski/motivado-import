<?php

class Importer extends Application {
	protected $Coachings = array();
	protected $Objects = array();
	protected $ObjectTransitions = array();
	protected $xmlStack = array();
	protected $nodePointer;
	
	public function __construct($configuration) {
		$this->setConfiguration($configuration);
	}
	
	public function run($void = NULL) {
		$this->truncateTables();
		$this->import($this->getCurrentCoaching()->getKey());
	}
	
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
	
	protected function getCurrentCoaching() {
		return end($this->Coachings);
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
		$pattern = '//Activity/Event/StartEvent/parent::*/parent::*';
		return $this->setNodePointer(pos($this->getXmlBuffer()->xpath($pattern)));
	}
	
	protected function findNextNodes() {
		$id = $this->getNodePointer()->attributes()->Id;
		$nodes = array();
		$pattern = '//Transition[@From="%s"]';
		foreach ($this->getXmlBuffer()->xpath(sprintf($pattern, $id)) as $transition) {
			$pattern = '//Activity[@Id="%s"]/Implementation/Task/*/parent::*/parent::*/parent::*';
			if ($array = $this->getXmlBuffer()->xpath(sprintf($pattern, $transition->attributes()->To))) {
				if ($this->registerNode($node = pos($array))) $nodes[] = $node;
			}
		}
		return $nodes;
	}
	
	protected function registerNode($node) {
		if (isset($this->Objects[$id = (string)$node->attributes()->Id])) {
			return $this->Objects[$id];
		}
		
		$title = (string)$node->attributes()->Name;
		$description = (string)$node->Description;
		if (isset($node->Implementation->Task->TaskManual)) {
			list($type, $key, $properties, $description) = $this->analyze($description);
		} else if (isset($node->Implementation->Task->TaskScript)) {
			if ($title && !$description) {
				$description = $title;
				$title = '';
			}
			$type = 'Text';
		} else if (isset($node->Implementation->Task->TaskReference)) {
			list($type, $key, $properties) = $this->handleQuestion($node);
		} else {
			throw new Error('Unknown object type', $node);
		}
		
		$Object = new Object(array(
			'CoachingId' => $this->getCurrentCoaching()->getId(),
			'title' => $title,
			'description' => isset($description) ? $description : NULL,
			'type' => $type,
			'key' => isset($key) ? $key : NULL,
			'properties' => isset($properties) ? sprintf('{%s}', $properties) : NULL
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
}