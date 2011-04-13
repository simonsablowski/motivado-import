<?php

class Node extends Importer {
	protected static $Coaching;
	protected static $Objects = array();
	protected static $Pointer;
	protected static $Collections = array();
	protected $Element;
	
	protected static function pushCollection($Collection) {
		return self::$Collections[] = $Collection;
	}
	
	protected static function popCollection() {
		return end(self::$Collections);
	}
	
	protected static function traverse($Node = NULL) {
		if (is_null($Node)) {
			$Node = self::getPointer();
		}
		if (!$Node) return FALSE;
		$Nodes = self::findNext(NULL, $Node);
		foreach ($Nodes as $Node) {
			self::setPointer($Node);
			$Nodes = array_merge($Nodes, self::traverse());
		}
		return $Nodes;
	}
	
	protected static function search($pattern) {
		return self::popCollection()->search($pattern);
	}
	
	public static function findTarget($pattern) {
		if ($array = self::search($pattern)) {
			$Element = pos($array);
			return new Node($Element);
		}
	}
	
	protected static function find($pattern) {
		return self::setPointer(self::findTarget($pattern));
	}
	
	protected static function findById($id) {
		return self::find(sprintf(Element::getPattern('NodeById'), $id));
	}
	
	protected static function findStart($pattern = NULL) {
		if (is_null($pattern)) {
			$pattern = Element::getPattern('Start');
		}
		return self::find($pattern);
	}
	
	protected static function findNext($pattern = NULL, $Node = NULL) {
		if (is_null($pattern)) {
			$pattern = Element::getPattern('NodeById');
			$pattern .= '|' . Element::getPattern('SplitterById');
			$pattern .= '|' . Element::getPattern('EndById');
		}
		if (is_null($Node)) {
			$Node = self::getPointer();
		}
		$Nodes = array();
		foreach (Transition::findAll($Node) as $Transition) {
			if ($Descendant = self::findTarget(sprintf($pattern, $Transition->getProperty('to')))) {
				if ($Descendant->register()) {
					$Transition->register();
					$Nodes[] = $Descendant;
				}
			}
		}
		return $Nodes;
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
	
	protected function analyze($disableTypeCheck = FALSE) {
		preg_match('/\$(\w+)(:(\w+))?\((.*)\)(.*)/is', $this->getProperty('description'), $matches);
		if (!$disableTypeCheck && !$matches) {
			throw new FatalError('Object type undefined', $this->summarize());
		}
		
		return array_map('trim', array_values(array(
			'type' => isset($matches[1]) ? $matches[1] : NULL,
			'key' => isset($matches[3]) ? $matches[3] : NULL,
			'properties' => isset($matches[4]) ? sprintf('{%s}', $matches[4]) : NULL,
			'description' => isset($matches[5]) ? $matches[5] : NULL
		)));
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
			list($type, $key, $properties, $description) = $this->analyze();
		} else if ($this->isType('Options')) {
			$type = 'Options';
			list($key, $properties) = $this->analyze(TRUE);
			$description = '';
		} else if ($this->isType('Text')) {
			$type = 'Text';
			list(, , $properties, $description) = $this->analyze(TRUE);
		} else if ($this->isType('Set') ||
					$this->isType('Splitter') ||
					$this->isType('Option') ||
					$this->isType('End')) {
			return TRUE;
		} else {
			throw new FatalError('Unknown object type', $this->summarize());
		}
		
		if ($properties && !Json::decode($properties)) {
			throw new FatalError('Invalid JSON code', $this->summarize());
		}
		if ($title && strlen($title) > 255 && !$description) {
			$description = $title;
			$title = '';
		}
		
		$Object = new Object(array(
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