<?php

class Importer extends Application {
	protected $Coachings = array();
	protected $Objects = array();
	protected $ObjectTransitions = array();
	protected $xmlStack = array();
	protected $nodePointer;
	protected $clearTables;
	
	public function __construct($configuration) {
		$this->setConfiguration($configuration);
		$this->setClearTables((bool)$this->getConfiguration('clearTables'));
	}
	
	protected function setCurrentCoaching(Coaching $Coaching) {
		return $this->Coachings[] = $Coaching;
	}
	
	protected function getCurrentCoaching() {
		return end($this->Coachings);
	}
	
	protected function clearTables() {
		if (Database::query('TRUNCATE `coaching`') &&
			Database::query('TRUNCATE `object`')) {
			return Database::query('TRUNCATE `objecttransition`');
		}
	}
	
	protected function cleanTables() {
		$condition = array(
			'CoachingId' => $this->getCurrentCoaching()->getId()
		);
		$result = TRUE;
		foreach (Object::findAll($condition) as $Object) {
			$result = $result && $Object->delete();
		}
		foreach (ObjectTransition::findAll($condition) as $ObjectTransition) {
			$result = $result && $ObjectTransition->delete();
		}
		return $result;
	}
	
	protected function traverseNodes($node = NULL) {
		if (is_null($node)) {
			$node = $this->getNodePointer();
		}
		if (!$node) return FALSE;
		$nodes = $this->findNextNodes(NULL, $node);
		foreach ($nodes as $node) {
			$this->setNodePointer($node);
			$nodes = array_merge($nodes, $this->traverseNodes());
		}
		return $nodes;
	}
	
	protected function scanFile($pathFile) {
		if (!mb_detect_encoding($contents = file_get_contents($pathFile), 'UTF-8', TRUE)) {
			throw new FatalError('Wrong character encoding', $pathFile);
		}
		$data = preg_replace('/(xmlns=")(.+)(")/', '$1$3', $contents);
		$this->pushOntoXmlStack(new SimpleXMLElement($data));
		if (!$this->findStartNode()) {
			throw new FatalError('No start node defined', $pathFile);
		}
		return $this->traverseNodes();
	}
	
	protected function import($directory) {
		$path = $this->getConfiguration('pathModeling') . $directory;
		$path .= substr($path, -1) == '/' ? '' : '/';
		return $this->scanFile($path . $this->getConfiguration('startFileNameModeling'));
	}
	
	protected function validate($type, $value) {
		switch ($type) {
			case 'CoachingKey':
				if (preg_match('/[^a-z0-9-]/', $value)) {
					throw new FatalError('Invalid coaching key', $value);
				}
				return TRUE;
		}
	}
	
	public function run($Coachings) {
		if ($this->isClearTables()) {
			$this->clearTables();
		}
		
		foreach ($Coachings as $key) {
			$this->validate('CoachingKey', $key);
			
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
	
	protected function pushOntoXmlStack($element) {
		return $this->xmlStack[] = $element;
	}
	
	protected function popOffXmlStack() {
		return end($this->xmlStack);
	}
	
	protected function getXmlBuffer() {
		return $this->popOffXmlStack();
	}
	
	protected function getPattern($type) {
		switch ($type) {
			case 'NodeById':
				return '//Activity[@Id="%1$s"]';
			case 'TransitionFrom':
				return '//Transition[@From="%1$s"]';
			case 'TransitionTo':
				return '//Transition[@To="%1$s"]';
			case 'SetById':
				return '//ActivitySet[@Id="%1$s"]';
			case 'SetByIdStart':
				return sprintf('//%s/Activities/Activity/Event/StartEvent/parent::*/parent::*', $this->getPattern('SetById'));
			case 'SplitterById':
				return '//Activity[@Id="%1$s"]/Route/parent::*';
			case 'OptionById':
				return '//Activity[@Id="%1$s"]/Implementation/Task/parent::*/parent::*';
			case 'Start':
				return '//WorkflowProcess/Activities/Activity/Event/StartEvent/parent::*/parent::*';
			case 'EndById':
				return '//Activity[@Id="%1$s"]/Event/EndEvent/parent::*/parent::*';
		}
	}
	
	protected function findTargetNode($pattern) {
		if ($array = $this->getXmlBuffer()->xpath($pattern)) return pos($array);
		return NULL;
	}
	
	protected function findNode($pattern) {
		return $this->setNodePointer($this->findTargetNode($pattern));
	}
	
	protected function findNodeById($id) {
		return $this->findNode(sprintf($this->getPattern('NodeById'), $id));
	}
	
	protected function findNodesTransitions($node = NULL, $pattern = NULL) {
		if (is_null($node)) {
			$node = $this->getNodePointer();
		}
		if (is_null($pattern)) {
			$pattern = $this->getPattern('TransitionFrom');
		}
		return $this->getXmlBuffer()->xpath(sprintf($pattern, $this->getNodeProperty('id', $node)));
	}
	
	protected function findStartNode($pattern = NULL) {
		if (is_null($pattern)) {
			$pattern = $this->getPattern('Start');
		}
		return $this->findNode($pattern);
	}
	
	protected function findNextNodes($pattern = NULL, $node = NULL) {
		if (is_null($pattern)) {
			$pattern = $this->getPattern('NodeById');
			$pattern .= '|' . $this->getPattern('SplitterById');
			$pattern .= '|' . $this->getPattern('EndById');
		}
		if (is_null($node)) {
			$node = $this->getNodePointer();
		}
		$nodes = array();
		foreach ($this->findNodesTransitions($node) as $transition) {
			if ($descendant = $this->findTargetNode(sprintf($pattern, $this->getNodeProperty('to', $transition)))) {
				if ($this->registerNode($descendant)) {
					$this->registerTransition($transition);
					$nodes[] = $descendant;
				}
			}
		}
		return $nodes;
	}
	
	protected function getNodeProperty($property, $node = NULL) {
		if (is_null($node)) {
			$node = $this->getNodePointer();
		}
		switch ($property) {
			case 'id':
				return (string)$node->attributes()->Id;
			case 'title':
			case 'condition':
				return (string)$node->attributes()->Name;
			case 'description':
				return (string)$node->Description;
			case 'setId':
				return (string)$node->BlockActivity->attributes()->ActivitySetId;
			case 'to':
				return (string)$node->attributes()->To;
			case 'from':
				return (string)$node->attributes()->From;
		}
	}
	
	protected function setNodeProperty($property, $value, &$node = NULL) {
		if (is_null($node)) {
			$node = &$this->getNodePointer();
		}
		switch ($property) {
			case 'to':
				return $node->attributes()->To = $value;
			case 'from':
				return $node->attributes()->From = $value;
			case 'condition':
				return $node->attributes()->Name = $value;
		}
	}
	
	protected function isNodeType($type = NULL, $node = NULL) {
		if (is_null($node)) {
			$node = $this->getNodePointer();
		}
		switch ($type) {
			default:
				return isset($node->Implementation->Task->TaskManual);
			case 'Options':
				return isset($node->Implementation->Task->TaskReference);
			case 'Text':
				return isset($node->Implementation->Task->TaskScript);
			case 'Set':
				return isset($node->BlockActivity) &&
					($setId = $node->BlockActivity->attributes()->ActivitySetId) &&
					$this->findTargetNode(sprintf($this->getPattern('SetById'), $setId));
			case 'Splitter':
				return isset($node->Route);
			case 'Option':
				return isset($node->Implementation->Task) &&
					!$this->isNodeType(NULL, $node) &&
					!$this->isNodeType('Options', $node) &&
					!$this->isNodeType('Text', $node) &&
					!$this->isNodeType('Set', $node) &&
					!$this->isNodeType('Splitter', $node);
			case 'End':
				return isset($node->Event->EndEvent);
		}
	}
	
	protected function handleObject($node) {
		preg_match('/\$(\w+)(:(\w+))?\((.*)\)(.*)/is', $this->getNodeProperty('description', $node), $matches);
		if (!$matches) {
			throw new FatalError('Object type undefined', $this->abstractNode($node));
		}
		
		return array_map('trim', array_values(array(
			'type' => $matches[1],
			'key' => $matches[3],
			'properties' => $matches[4],
			'description' => $matches[5]
		)));
	}
	
	protected function handleOptions($node) {
		$pattern = $this->getPattern('OptionById');
		$options = array();
		foreach ($this->findNodesTransitions($node) as $transition) {
			if ($option = $this->findTargetNode(sprintf($pattern, $this->getNodeProperty('to', $transition)))) {
				$options[] = array(
					'key' => $this->getNodeProperty('condition', $transition),
					'value' => $this->getNodeProperty('title', $option)
				);
			}
		}
		
		$properties = array('options' => array());
		$o = 1;
		foreach ($options as $option) {
			$properties['options'][] = array(
				'key' => $option['key'] ? $option['key'] : $o,
				'value' => $option['value']
			);
			$o++;
		}
		
		if (!$key = $this->getNodeProperty('description', $node)) {
			$key = preg_replace('/[^a-z0-9]/i', '', $this->getNodeProperty('id', $node));
		}
		
		return array_values(array(
			'key' => $key,
			'properties' => $properties
		));
	}
	
	protected function handleText($node) {
		$title = $this->getNodeProperty('title', $node);
		$description = $this->getNodeProperty('description', $node);
		
		if ($title && !$description) {
			$description = $title;
			$title = '';
		}
		
		return array_values(array(
			'title' => $title,
			'description' => $description
		));
	}
	
	protected function abstractNode($node) {
		return array_slice((array)$node, 0, 3);
	}
	
	protected function registerNode($node) {
		if (isset($this->Objects[$id = $this->getNodeProperty('id', $node)])) {
			return $this->Objects[$id];
		}
		
		$title = $this->getNodeProperty('title', $node);
		$description = $this->getNodeProperty('description', $node);
		
		if ($this->isNodeType(NULL, $node)) {
			list($type, $key, $properties, $description) = $this->handleObject($node);
			if ($properties) {
				if (!$properties = Json::decode(sprintf('{%s}', $properties))) {
					throw new FatalError('Invalid JSON code', $this->abstractNode($node));
				}
			}
		} else if ($this->isNodeType('Options', $node)) {
			$type = 'Options';
			list($key, $properties) = $this->handleOptions($node);
			$description = '';
		} else if ($this->isNodeType('Text', $node)) {
			$type = 'Text';
			list($title, $description) = $this->handleText($node);
		} else if ($this->isNodeType('Set', $node) ||
					$this->isNodeType('Splitter', $node) ||
					$this->isNodeType('Option', $node) ||
					$this->isNodeType('End', $node)) {
			return TRUE;
		} else {
			throw new FatalError('Unknown object type', $this->abstractNode($node));
		}
		
		$Object = new Object(array(
			'CoachingId' => $this->getCurrentCoaching()->getId(),
			'title' => $title,
			'description' => isset($description) ? $description : NULL,
			'type' => $type,
			'key' => isset($key) ? $key : NULL,
			'properties' => isset($properties) ? Json::encode($properties) : NULL
		));
		$Object->create();
		$this->Objects[$id] = $Object;
		
		return $Object;
	}
	
	protected function getCondition($key, $value, $operator = 'is') {
		return sprintf('%s %s %s', $key, $operator, is_int($value) ? $value : sprintf('\'%s\'', $value));
	}
	
	protected function handleSetTransitions($node) {
		$result = TRUE;
		$setId = $this->getNodeProperty('setId', $node);
		if (!$start = $this->findStartNode(sprintf($this->getPattern('SetByIdStart'), $setId))) {
			throw new FatalError('No start node defined', $this->abstractNode($node));
		}
		foreach ($this->findNodesTransitions($node, $this->getPattern('TransitionTo')) as $transitionTo) {
			$from = $this->getNodeProperty('from', $transitionTo);
			if (($ancestor = $this->findTargetNode(sprintf($this->getPattern('NodeById'), $from))) &&
					$this->registerNode($ancestor)) {
				$this->setNodePointer($node);
				$this->traverseNodes($start);
				foreach ($this->findNodesTransitions($node, $this->getPattern('TransitionFrom')) as $transitionFrom) {
					$to = $this->getNodeProperty('to', $transitionFrom);
					if (($descendant = $this->findTargetNode(sprintf($this->getPattern('NodeById'), $to))) &&
							$this->registerNode($descendant)) {
						$transition = $transitionTo;
						$this->setNodeProperty('to', $this->getNodeProperty('id', $descendant), $transition);
						$condition = $this->getNodeProperty('condition', $transitionFrom);
						$this->setNodeProperty('condition', $condition, $transition);
						$result = $result && $this->registerTransition($transition);
					}
				}
			}
		}
		return $result;
	}
	
	protected function handleSplitterTransitions($node) {
		$result = TRUE;
		foreach ($this->findNodesTransitions($node, $this->getPattern('TransitionTo')) as $transitionTo) {
			foreach ($this->findNodesTransitions($node, $this->getPattern('TransitionFrom')) as $transitionFrom) {
				$to = $this->getNodeProperty('to', $transitionFrom);
				if (($descendant = $this->findTargetNode(sprintf($this->getPattern('NodeById'), $to))) &&
						$this->registerNode($descendant)) {
					$transition = $transitionTo;
					$this->setNodeProperty('to', $this->getNodeProperty('id', $descendant), $transition);
					$condition = $this->getNodeProperty('condition', $transitionFrom);
					$this->setNodeProperty('condition', $condition, $transition);
					$result = $result && $this->registerTransition($transition);
				}
			}
		}
		return $result;
	}
	
	protected function handleOptionTransitions($node) {
		$result = TRUE;
		foreach ($this->findNodesTransitions($node, $this->getPattern('TransitionTo')) as $transitionTo) {
			foreach ($this->findNodesTransitions($node, $this->getPattern('TransitionFrom')) as $transitionFrom) {
				$to = $this->getNodeProperty('to', $transitionFrom);
				if (($descendant = $this->findTargetNode(sprintf($this->getPattern('NodeById'), $to))) &&
						$this->registerNode($descendant)) {
					$transition = $transitionTo;
					$this->setNodeProperty('to', $this->getNodeProperty('id', $descendant), $transition);
					$from = $this->getNodeProperty('from', $transitionTo);
					if (($Object = $this->Objects[$from]) && $Object->getType() != 'Options') {
						throw new FatalError('Invalid option object', $this->abstractNode($node));
					}
					$value = $this->getNodeProperty('condition', $transitionTo);
					$condition = $this->getCondition($Object->getKey(), $value);
					$this->setNodeProperty('condition', $condition, $transition);
					$result = $result && $this->registerTransition($transition);
				}
			}
		}
		return $result;
	}
	
	protected function registerTransition($transition) {
		if (isset($this->ObjectTransitions[$id = $this->getNodeProperty('id', $transition)])) {
			return $this->ObjectTransitions[$id];
		}
		
		$LeftId = 0;
		$RightId = 0;
		$condition = $this->getNodeProperty('condition', $transition);
		
		if (isset($this->Objects[$from = $this->getNodeProperty('from', $transition)])) {
			$LeftId = $this->Objects[$from]->getId();
		} else if ($node = $this->findNodeById($from)) {
			if ($this->isNodeType('Set', $node) ||
				$this->isNodeType('Splitter', $node) ||
				$this->isNodeType('Option', $node)) {
				return FALSE;
			}
		}
		
		if (isset($this->Objects[$to = $this->getNodeProperty('to', $transition)])) {
			$RightId = $this->Objects[$to]->getId();
		} else if ($node = $this->findNodeById($to)) {
			if ($this->isNodeType('Set', $node)) {
				return $this->handleSetTransitions($node);
			} else if ($this->isNodeType('Splitter', $node)) {
				return $this->handleSplitterTransitions($node);
			} else if ($this->isNodeType('Option', $node)) {
				return $this->handleOptionTransitions($node);
			}
		}
		
		$ObjectTransition = new ObjectTransition(array(
			'CoachingId' => $this->getCurrentCoaching()->getId(),
			'LeftId' => $LeftId,
			'RightId' => $RightId,
			'condition' => $condition
		));
		$ObjectTransition->createSafely();
		$this->ObjectTransition[$id] = $ObjectTransition;
		
		return $ObjectTransition;
	}
}