<?php

class SplitterTransition extends Transition {
	protected static function handle($Node = NULL) {
		$result = TRUE;
		foreach (self::findAll($Node, Element::getPattern('TransitionTo')) as $TransitionTo) {
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
		return $result;
	}
}