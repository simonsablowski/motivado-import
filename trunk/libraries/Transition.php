<?php

class Transition extends Node {
	protected static $ObjectTransitions = array();
	
	public static function findAllOfNode(Node $Node, $pattern = NULL) {
		if (is_null($pattern)) {
			$pattern = Element::getPattern('TransitionFrom');
		}
		$Transitions = array();
		foreach (self::search(sprintf($pattern, $Node->getProperty('id'))) as $Element) {
			$Transitions[] = new Transition($Element);
		}
		return $Transitions;
	}
	
	protected function register() {
		if (isset(self::$ObjectTransitions[$id = $this->getProperty('id')])) {
			return self::$ObjectTransitions[$id];
		}
		
		$LeftId = 0;
		$RightId = 0;
		$condition = $this->getProperty('condition');
		
		if (isset(Node::$Objects[$from = $this->getProperty('from')])) {
			$LeftId = Node::$Objects[$from]->getId();
		} else if ($Node = self::findById($from, FALSE)) {
			if ($Node->isType('Set') ||
				$Node->isType('Splitter') ||
				$Node->isType('Option')) {
				return FALSE;
			} else if ($Node->isType('Start') && ($Pointer = self::$Pointer)) {
				if (isset(Node::$Objects[$Pointer->getProperty('id')])) {
					$LeftId = Node::$Objects[$Pointer->getProperty('id')]->getId();
				}
			}
		}
		
		if (isset(Node::$Objects[$to = $this->getProperty('to')])) {
			$RightId = Node::$Objects[$to]->getId();
		} else if ($Node = self::findById($to, FALSE)) {
			if ($Node->isType('Set')) {
				return SetTransition::handle($Node);
			} else if ($Node->isType('Splitter')) {
				return SplitterTransition::handle($Node);
			} else if ($Node->isType('Option')) {
				return OptionTransition::handle($Node);
			}
		}
		
		if ($RightId == 0 && !self::$saveEnds) {
			self::$saveEnds = TRUE;
			return;
		}
		
		$ObjectTransition = new ObjectTransition(array(
			'CoachingId' => Node::$Coaching->getId(),
			'LeftId' => $LeftId,
			'RightId' => $RightId,
			'condition' => $condition
		));
		$ObjectTransition->createSafely();
		self::$ObjectTransitions[$id] = $ObjectTransition;
		
		return $ObjectTransition;
	}
}