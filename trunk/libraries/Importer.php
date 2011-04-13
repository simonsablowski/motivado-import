<?php

class Importer extends Application {
	protected $Coachings = array();
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
		return Coaching::truncate() && Object::truncate() && ObjectTransition::truncate();
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
	
	protected function scanFile($pathFile) {
		if (!mb_detect_encoding($contents = file_get_contents($pathFile), 'UTF-8', TRUE)) {
			throw new FatalError('Wrong character encoding', $pathFile);
		}
		$data = preg_replace('/(xmlns=")(.+)(")/', '$1$3', $contents);
		Node::setCoaching($this->getCurrentCoaching());
		Node::pushCollection(new Element($data));
		if (!Node::findStart()) {
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
		
		foreach ($Coachings as $key => $pathFile) {
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
			$this->scanFile($pathFile);
		}
	}
}