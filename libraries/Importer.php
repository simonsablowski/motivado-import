<?php

class Importer extends Application {
	protected $Coachings = array();
	protected $clearTables;
	
	public function __construct($configuration) {
		$this->setConfiguration($configuration);
		$this->setClearTables((bool)$this->getConfiguration('clearTables'));
	}
	
	protected function setCurrentCoaching(\Motivado\Api\Coaching $Coaching) {
		return $this->Coachings[] = $Coaching;
	}
	
	protected function getCurrentCoaching() {
		return end($this->Coachings);
	}
	
	protected function lockTables() {
		return Database::lock(array('coaching', 'object', 'objecttransition'));
	}
	
	protected function unlockTables() {
		return Database::unlock();
	}
	
	protected function clearTables() {
		return \Motivado\Api\Coaching::truncate() && \Motivado\Api\Object::truncate() && \Motivado\Api\ObjectTransition::truncate();
	}
	
	protected function cleanTables() {
		$condition = array(
			'CoachingId' => $this->getCurrentCoaching()->getId()
		);
		$result = TRUE;
		foreach (\Motivado\Api\Object::findAll($condition) as $Object) {
			$result = $result && $Object->delete(TRUE);
		}
		foreach (\Motivado\Api\ObjectTransition::findAll($condition) as $ObjectTransition) {
			$result = $result && $ObjectTransition->delete(TRUE);
		}
		return $result;
	}
	
	protected function scanFile($pathFile) {
		if (!mb_detect_encoding($contents = file_get_contents($pathFile), 'UTF-8', TRUE)) {
			throw new FatalError('Wrong character encoding', $pathFile);
		}
		$data = preg_replace('/(xmlns=")(.+)(")/', '$1$3', $contents);
		Node::flushObjects();
		Node::setCoaching($this->getCurrentCoaching());
		Node::pushCollection(new Element($data));
		if (!Node::find(Element::getPattern('Start'))) {
			throw new FatalError('No start node defined', $pathFile);
		}
		return Node::traverse();
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
		
		$this->lockTables();
		
		foreach ($Coachings as $pathFile => $key) {
			$this->validate('CoachingKey', $key);
			
			try {
				$Coaching = \Motivado\Api\Coaching::findByKey($key);
			} catch (\Error $Error) {
				$Coaching = new \Motivado\Api\Coaching(array(
					'key' => $key
				));
				$Coaching->create();
			}
			$this->setCurrentCoaching($Coaching);
			
			$this->cleanTables();
			$this->scanFile($pathFile);
		}
		
		$this->unlockTables();
	}
}