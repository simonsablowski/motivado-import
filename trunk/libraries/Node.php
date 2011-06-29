<?php

class Node extends Importer {
	protected static $Coaching;
	protected static $Nodes = array();
	protected static $Objects = array();
	protected static $Pointer;
	protected static $Collections = array();
	protected static $saveEnds = TRUE;
	protected $Element;
	
	protected static function pushCollection($Collection) {
		return self::$Collections[] = $Collection;
	}
	
	protected static function popCollection() {
		return end(self::$Collections);
	}
	
	protected static function traverse($Node = NULL, $pattern = NULL) {
		if (is_null($Node)) {
			$Node = self::getPointer();
		}
		if (!$Node) return FALSE;
		
		$Nodes = self::findNext($pattern, $Node);
		foreach ($Nodes as $Node) {
			if (!in_array($Node, self::$Nodes)) {
				self::$Nodes[] = $Node;
				$Nodes = array_merge($Nodes, self::traverse($Node, $pattern));
			}
		}
		return $Nodes;
	}
	
	protected static function search($pattern) {
		return self::popCollection()->search($pattern);
	}
	
	public static function findAll($pattern) {
		$Nodes = array();
		foreach (self::search($pattern) as $Element) {
			$Nodes[] = new Node($Element);
		}
		return $Nodes;
	}
	
	public static function findTarget($pattern) {
		if ($Nodes = self::findAll($pattern)) {
			return pos($Nodes);
		}
	}
	
	public static function find($pattern, $setPointer = TRUE) {
		$Node = self::findTarget($pattern);
		return $setPointer ? self::setPointer($Node) : $Node;
	}
	
	public static function findById($id, $setPointer = TRUE) {
		return self::find(sprintf(Element::getPattern('NodeById'), $id), $setPointer);
	}
	
	public static function findNext($pattern = NULL, $Node = NULL) {
		if (is_null($pattern)) {
			$pattern = Element::getPattern('NodeById');
			$pattern .= '|' . Element::getPattern('SplitterById');
			$pattern .= '|' . Element::getPattern('EndById');
		}
		if (is_null($Node)) {
			$Node = self::getPointer();
		}
		$Nodes = array();
		foreach (Transition::findAllOfNode($Node) as $Transition) {
			if ($Descendant = self::findTarget(sprintf($pattern, $Transition->getProperty('to')))) {
				$Descendant->register();
				$Transition->register();
				$Nodes[] = $Descendant;
			}
		}
		return $Nodes;
	}
	
	public static function analyze(Node $Node, $disableTypeCheck = FALSE) {
		preg_match('/(.*)\$(\w+)(:(\w+))?\((.*)\)(.*)/s', $Node->getProperty('description'), $matches);
		if (!$disableTypeCheck && !$matches) {
			throw new FatalError('Object type undefined', $Node->summarize());
		}
		
		return array_map('trim', array_values(array(
			'type' => isset($matches[2]) ? $matches[2] : NULL,
			'key' => isset($matches[4]) ? $matches[4] : NULL,
			'properties' => isset($matches[5]) ? sprintf('{%s}', $matches[5]) : NULL,
			'description' => isset($matches[6]) && $matches[6] ? $matches[6] : (isset($matches[1]) ? $matches[1] : NULL)
		)));
	}
	
	public function __construct(Element $Element) {
		$this->setElement($Element);
	}
	
	public function getProperty($property) {
		return $this->getElement()->getProperty($property);
	}
	
	public function setProperty($property, $value) {
		return $this->getElement()->setProperty($property, $value);
	}
	
	public function isType($type) {
		return $this->getElement()->isType($type);
	}
	
	protected function summarize() {
		return array_slice((array)$this, 0, 3);
	}
	
	protected function register() {
		if (isset(self::$Objects[$id = $this->getProperty('id')])) {
			return self::$Objects[$id];
		}
		
		$key = NULL;
		$properties = NULL;
		$title = $this->getProperty('title');
		$description = $this->getProperty('description');
		
		if ($this->isType(NULL)) {
			list($type, $key, $properties, $description) = self::analyze($this);
		} else if ($this->isType('Options')) {
			$type = 'Options';
			list($key, $properties) = OptionsNode::analyze($this);
			$description = '';
		} else if ($this->isType('Text')) {
			$type = 'Text';
			list(, , $properties, $description) = self::analyze($this, TRUE);
		} else if ($this->isType('Set') ||
					$this->isType('Splitter') ||
					$this->isType('Option') ||
					$this->isType('End')) {
			return TRUE;
		} else {
			throw new FatalError('Unknown object type', $this->summarize());
		}
		
		if ($properties && !\Motivado\Api\Json::decode($properties)) {
			throw new FatalError('Invalid JSON code', $this->summarize());
		}
		if ($title && strlen($title) > 255 && !$description) {
			$description = $title;
			$title = '';
		}
		
		$Object = new \Motivado\Api\Object(array(
			'CoachingId' => self::$Coaching->getId(),
			'type' => $type,
			'key' => $key,
			'properties' => $properties,
			'title' => $title,
			'description' => $description
		));
		$Object->create();
		self::$Objects[$id] = $Object;
		
		return $Object;
	}
}
