<?php

class SetTransition extends Transition {
	protected static function handle($Node = NULL) {
		$result = TRUE;
		$setId = $Node->getProperty('setId');
		if (!$start = self::findStart(sprintf(Element::getPattern('SetByIdStart'), $setId))) {
			throw new FatalError('No start node defined', $Node->summarize());
		}
		foreach (self::findAll($Node, Element::getPattern('TransitionTo')) as $TransitionTo) {
			$from = $TransitionTo->getProperty('from');
			if (($Ancestor = self::findTarget(sprintf(Element::getPattern('NodeById'), $from))) &&
					$Ancestor->register()) {
				self::setPointer($Node);
				self::traverse($start);
				foreach (self::findAll($Node, Element::getPattern('TransitionFrom')) as $TransitionFrom) {
					$to = $TransitionFrom->getProperty('to');
					if (($Descendant = self::findTarget(sprintf(Element::getPattern('NodeById'), $to))) &&
							$Descendant->register()) {
						$Transition = clone $TransitionTo;
						$Transition->setProperty('to', $Descendant->getProperty('id'));
						$condition = $TransitionFrom->getProperty('condition');
						$Transition->setProperty('condition', $condition);
						$result = $result && $Transition->register();
					}
				}
			}
		}
		return $result;
	}
}