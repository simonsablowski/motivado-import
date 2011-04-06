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
	
	public function run($Coachings) {
		foreach ($Coachings as $key) {
			try {
				$Coaching = Coaching::findByKey($key);
			} catch (Error $Error) {
				$Coaching = new Coaching(array(
					'key' => $key
				));
				$Coaching->create();
			}
			$this->setCurrentCoaching($Coaching);
			
			$this->cleanTables();
			$this->import($this->getCurrentCoaching()->getKey());
		}
	}
	
	//TODO: delete doesn't work
	protected function cleanTables() {
		return Database::query('TRUNCATE `object`') && Database::query('TRUNCATE `objecttransition`');
		$condition = array(
			'CoachingId' => $this->getCurrentCoaching()->getId()
		);
		$result = FALSE;
		foreach (Object::findAll($condition) as $Object) {
			$result = $result && $Object->delete();
		}
		foreach (ObjectTransition::findAll($condition) as $ObjectTransition) {
			$result = $result && $ObjectTransition->delete();
		}
		return $result;
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
	
	protected function setCurrentCoaching(Coaching $Coaching) {
		return $this->Coachings[] = $Coaching;
	}
	
	protected function getCurrentCoaching() {
		return end($this->Coachings);
	}
	
	protected function import($directory) {
		$path = $this->getConfiguration('pathModeling') . $directory;
		$path .= substr($path, -1) == '/' ? '' : '/';
		$this->scanFile($path . $this->getConfiguration('startFileNameModeling'));
	}
	
	protected function findNode($pattern) {
		return $this->setNodePointer($this->getXmlBuffer()->xpath($pattern));
	}
	
	protected function findTargetNode($pattern) {
		if ($array = $this->getXmlBuffer()->xpath($pattern)) {
			return pos($array);
		}
		return NULL;
	}
	
	protected function findNodesTransitions($node = NULL) {
		if (is_null($node)) {
			$node = $this->getNodePointer();
		}
		$id = $node->attributes()->Id;
		return $this->getXmlBuffer()->xpath(sprintf('//Transition[@From="%s"]', $id));
	}
	
	protected function findStartNode($pattern = NULL) {
		if (is_null($pattern)) {
			$pattern = '//Activity/Event/StartEvent/parent::*/parent::*';
		}
		return $this->setNodePointer(pos($this->getXmlBuffer()->xpath($pattern)));
	}
	
	protected function findNextNodes($pattern = NULL) {
		if (is_null($pattern)) {
			$pattern = '//Activity[@Id="%1$s"]/Implementation/Task/*/parent::*/parent::*/parent::*';
			$pattern .= '|//Activity[@Id="%1$s"]/Route/parent::*';
		}
		$nodes = array();
		foreach ($this->findNodesTransitions() as $transition) {
			if ($node = $this->findTargetNode(sprintf($pattern, $transition->attributes()->To))) {
				if ($this->registerNode($node)) {
					$this->registerTransition($transition);
					$nodes[] = $node;
				}
			}
		}
		return $nodes;
	}
	
	protected function analyze($string) {
		preg_match('/\$(\w+):(\w+)\((.*)\)(.*)/is', $string, $matches);
		return array_map('trim', array_slice($matches, 1));
	}
	
	//TODO: generate conditions
	protected function handleOptions($node) {
		$pattern = '//Activity[@Id="%s"]/Implementation/Task/parent::*/parent::*';
		$nodePointer = $this->getNodePointer();
		$this->setNodePointer($node);
		$options = array();
		foreach ($this->findNodesTransitions() as $transition) {
			if ($option = $this->findTargetNode(sprintf($pattern, $transition->attributes()->To))) {
				$options[(string)$transition->attributes()->Name] = (string)$option->attributes()->Name;
			}
		}
		$this->setNodePointer($nodePointer);
		
		$properties = "options:[";
		$i = 1;
		foreach ($options as $key => $value) {
			$comma = $i < count($options) ? ',' : '';
			$properties .= sprintf("{key:'%s',value:'%s'}%s", $key, $value, $comma);
			$i++;
		}
		$properties .= "]";
		
		return array(
			'Options',
			NULL,
			$properties
		);
	}
	
	//TODO
	protected function handleGateway($node) {
		return TRUE;
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
			list($type, $key, $properties) = $this->handleOptions($node);
		} else if (isset($node->Route)) {
			return $this->handleGateway($node);
		} else {
			throw new Error('Unknown object type', array_map(function($node) {
				foreach ($node as $key => $value) {
					$node[$key] = $value != ($v = substr($value, 0, 200)) ? $v . '...' : $value;
				}
				return $node;
			}, array_slice((array)$node, 0, 3)));
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
	
	protected function registerTransition($transition) {
		if (isset($this->ObjectTransitions[$id = (string)$transition->attributes()->Id])) {
			return $this->ObjectTransitions[$id];
		}
		
		if (isset($this->Objects[$from = (string)$transition->attributes()->From])) {
			$LeftId = $this->Objects[$from]->getId();
		} else {
			$LeftId = 0;
		}
		if (isset($this->Objects[$to = (string)$transition->attributes()->To])) {
			$RightId = $this->Objects[$to]->getId();
		} else {
			$RightId = 0;
		}
		$condition = (string)$transition->attributes()->Name;
		
		$ObjectTransition = new ObjectTransition(array(
			'CoachingId' => $this->getCurrentCoaching()->getId(),
			'LeftId' => $LeftId,
			'RightId' => $RightId,
			'condition' => $condition
		));
		$ObjectTransition->create();
		$this->ObjectTransition[$id] = $ObjectTransition;
		
		return $ObjectTransition;
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