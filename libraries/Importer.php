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
	
	protected function setCurrentCoaching(Coaching $Coaching) {
		return $this->Coachings[] = $Coaching;
	}
	
	protected function getCurrentCoaching() {
		return end($this->Coachings);
	}
	
	//TODO: delete doesn't work
	protected function cleanTables() {
		return Database::query('TRUNCATE `object`') && Database::query('TRUNCATE `objecttransition`');
		/*$condition = array(
			'CoachingId' => $this->getCurrentCoaching()->getId()
		);
		$result = FALSE;
		foreach (Object::findAll($condition) as $Object) {
			$result = $result && $Object->delete();
		}
		foreach (ObjectTransition::findAll($condition) as $ObjectTransition) {
			$result = $result && $ObjectTransition->delete();
		}
		return $result;*/
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
	
	protected function import($directory) {
		$path = $this->getConfiguration('pathModeling') . $directory;
		$path .= substr($path, -1) == '/' ? '' : '/';
		$this->scanFile($path . $this->getConfiguration('startFileNameModeling'));
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
	
	protected function getPattern($type = NULL) {
		switch ($type) {
			default:
			case 'NodeById':
				return '//Activity[@Id="%1$s"]';
			case 'TransitionFrom':
				return '//Transition[@From="%1$s"]';
			case 'TransitionTo':
				return '//Transition[@To="%s"]';
			case 'GatewayById':
				return '|//Activity[@Id="%1$s"]/Route/parent::*';
			case 'OptionById':
				return '//Activity[@Id="%1$s"]/Implementation/Task/parent::*/parent::*';
			case 'Start':
				return '//Activity/Event/StartEvent/parent::*/parent::*';
			case 'EndById':
				return '|//Activity[@Id="%1$s"]/Event/EndEvent/parent::*/parent::*';
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
		$id = $node->attributes()->Id;
		return $this->getXmlBuffer()->xpath(sprintf($pattern, $id));
	}
	
	protected function findStartNode($pattern = NULL) {
		if (is_null($pattern)) {
			$pattern = $this->getPattern('Start');
		}
		return $this->findNode($pattern);
	}
	
	protected function findNextNodes($pattern = NULL) {
		if (is_null($pattern)) {
			$pattern = $this->getPattern('NodeById');
			$pattern .= $this->getPattern('GatewayById');
			$pattern .= $this->getPattern('EndById');
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
	
	protected function handleObject($node) {
		preg_match('/\$(\w+)(:(\w+))?\((.*)\)(.*)/is', (string)$node->Description, $matches);
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
		foreach ($this->findNodesTransitions() as $transition) {
			if ($option = $this->findTargetNode(sprintf($pattern, $transition->attributes()->To))) {
				$options[(string)$transition->attributes()->Name] = (string)$option->attributes()->Name;
			}
		}
		
		$properties = array('options' => array());
		$o = 1;
		foreach ($options as $key => $value) {
			$properties['options'][] = array(
				'key' => $key ? $key : $o,
				'value' => $value
			);
			$o++;
		}
		
		if (!$key = (string)$node->Description) {
			$key = preg_replace('/[^a-z0-9]/i', '', (string)$node->attributes()->Id);
		}
		
		return array_values(array(
			'key' => $key,
			'properties' => $properties
		));
	}
	
	protected function handleText($node) {
		$title = (string)$node->attributes()->Name;
		$description = (string)$node->Description;
		
		if ($title && !$description) {
			$description = $title;
			$title = '';
		}
		
		return array_values(array(
			'title' => $title,
			'description' => $description
		));
	}
	
	protected function isNodeType($node, $type = NULL) {
		switch ($type) {
			default:
				return isset($node->Implementation->Task->TaskManual);
			case 'Options':
				return isset($node->Implementation->Task->TaskReference);
			case 'Text':
				return isset($node->Implementation->Task->TaskScript);
			case 'Gateway':
				return isset($node->Route);
			case 'Option':
				return isset($node->Implementation->Task) &&
					!$this->isNodeType($node) &&
					!$this->isNodeType($node, 'Options') &&
					!$this->isNodeType($node, 'Text') &&
					!$this->isNodeType($node, 'Gateway');
			case 'End':
				return isset($node->Event->EndEvent);
		}
	}
	
	protected function abstractNode($node) {
		return array_map(function($node) {
			foreach ($node as $key => $value) {
				$node[$key] = $value != ($v = substr($value, 0, 200)) ? $v . '...' : $value;
			}
			return $node;
		}, array_slice((array)$node, 0, 3));
	}
	
	protected function registerNode($node) {
		if (isset($this->Objects[$id = (string)$node->attributes()->Id])) {
			return $this->Objects[$id];
		}
		
		$title = (string)$node->attributes()->Name;
		$description = (string)$node->Description;
		
		if ($this->isNodeType($node)) {
			list($type, $key, $properties, $description) = $this->handleObject($node);
			$properties = Json::decode(sprintf('{%s}', $properties));
		} else if ($this->isNodeType($node, 'Options')) {
			$type = 'Options';
			list($key, $properties) = $this->handleOptions($node);
			$description = '';
		} else if ($this->isNodeType($node, 'Text')) {
			$type = 'Text';
			list($title, $description) = $this->handleText($node);
		} else if ($this->isNodeType($node, 'Gateway') ||
					$this->isNodeType($node, 'Option') ||
					$this->isNodeType($node, 'End')) {
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
	
	//TODO: somehow only the ObjectTransitions of the first LeftObject are saved
	protected function registerTransition($transition) {
		if (isset($this->ObjectTransitions[$id = (string)$transition->attributes()->Id])) {
			return $this->ObjectTransitions[$id];
		}
		
		$LeftId = 0;
		$RightId = 0;
		$condition = (string)$transition->attributes()->Name;
		
		if (isset($this->Objects[$from = (string)$transition->attributes()->From])) {
			$LeftId = $this->Objects[$from]->getId();
		} else if ($node = $this->findNodeById($from)) {
			if ($this->isNodeType($node, 'Gateway') || $this->isNodeType($node, 'Option')) {
				return FALSE;
			}
		}
		
		if (isset($this->Objects[$to = (string)$transition->attributes()->To])) {
			$RightId = $this->Objects[$to]->getId();
		} else if ($node = $this->findNodeById($to)) {
			if ($this->isNodeType($node, 'Gateway')) {
				$result = FALSE;
				foreach ($this->findNodesTransitions($node, $this->getPattern('TransitionTo')) as $transitionTo) {
					foreach ($this->findNodesTransitions($node, $this->getPattern('TransitionFrom')) as $transitionFrom) {
						if ($descendant = $this->findTargetNode(sprintf($this->getPattern('NodeById'), $transitionFrom->attributes()->To))) {
							if ($this->registerNode($descendant)) {
								$transition = $transitionTo;
								$transition->attributes()->To = $descendant->attributes()->Id;
								$transition->attributes()->Name = $transitionFrom->attributes()->Name;
								$result = $this->registerTransition($transition);
							}
						}
					}
				}
				return $result;
			} else if ($this->isNodeType($node, 'Option')) {
				$result = FALSE;
				foreach ($this->findNodesTransitions($node, $this->getPattern('TransitionTo')) as $transitionTo) {
					foreach ($this->findNodesTransitions($node, $this->getPattern('TransitionFrom')) as $transitionFrom) {
						if ($descendant = $this->findTargetNode(sprintf($this->getPattern('NodeById'), $transitionFrom->attributes()->To))) {
							if ($this->registerNode($descendant)) {
								$transition = $transitionTo;
								$transition->attributes()->To = $descendant->attributes()->Id;
								if (($Object = $this->Objects[(string)$transitionTo->attributes()->From]) && $Object->getType() != 'Options') {
									throw new FatalError('Invalid option object', $this->abstractNode($node));
								}
								$transition->attributes()->Name = $this->getCondition($Object->getKey(), $transitionTo->attributes()->Name);
								$result = $this->registerTransition($transition);
							}
						}
					}
				}
				return $result;
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