<?php

class SplitterTransition extends Transition {
	protected static function handle(Node $Node) {
		$result = TRUE;
		foreach (self::findAllOfNode($Node, Element::getPattern('TransitionTo')) as $TransitionTo) {
			foreach (self::findAllOfNode($Node, Element::getPattern('TransitionFrom')) as $TransitionFrom) {
				$to = $TransitionFrom->getProperty('to');
				$Descendant = self::findById($to);
				$Descendant->register();
				$Transition = clone $TransitionTo;
				$Transition->setProperty('to', $Descendant->getProperty('id'));
				$condition = $TransitionFrom->getProperty('condition');
				$Transition->setProperty('condition', $condition);
				$result = $result && $Transition->register();
			}
		}
		return $result;
	}
}