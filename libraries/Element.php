<?php

class Element extends SimpleXMLElement {
	public static function getPattern($type) {
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
				return sprintf('//%s/Activities/Activity/Event/StartEvent/parent::*/parent::*', self::getPattern('SetById'));
			case 'SetByIdEnd':
				return sprintf('//%s/Activities/Activity/Event/EndEvent/parent::*/parent::*', self::getPattern('SetById'));
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
	
	public function getProperty($property) {
		switch ($property) {
			case 'id':
				return (string)$this->attributes()->Id;
			case 'title':
			case 'condition':
				return (string)$this->attributes()->Name;
			case 'description':
				return (string)$this->Description;
			case 'setId':
				return (string)$this->BlockActivity->attributes()->ActivitySetId;
			case 'to':
				return (string)$this->attributes()->To;
			case 'from':
				return (string)$this->attributes()->From;
		}
	}
	
	public function setProperty($property, $value) {
		switch ($property) {
			case 'to':
				return $this->attributes()->To = $value;
			case 'from':
				return $this->attributes()->From = $value;
			case 'condition':
				return $this->attributes()->Name = $value;
		}
	}
	
	public function isType($type) {
		switch ($type) {
			default:
				return isset($this->Implementation->Task->TaskManual);
			case 'Options':
				return isset($this->Implementation->Task->TaskReference);
			case 'Text':
				return isset($this->Implementation->Task->TaskScript);
			case 'Set':
				return isset($this->BlockActivity) &&
					($setId = $this->BlockActivity->attributes()->ActivitySetId) &&
					Node::findTarget(sprintf(self::getPattern('SetById'), $setId));
			case 'Splitter':
				return isset($this->Route);
			case 'Option':
				return isset($this->Implementation->Task) &&
					!$this->isType(NULL) &&
					!$this->isType('Options') &&
					!$this->isType('Text') &&
					!$this->isType('Set') &&
					!$this->isType('Splitter');
			case 'Start':
				return isset($this->Event->StartEvent);
			case 'End':
				return isset($this->Event->EndEvent);
		}
	}
	
	public function search($pattern) {
		return ($result = $this->xpath($pattern)) ? $result : array();
	}
}
