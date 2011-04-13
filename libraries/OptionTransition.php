<?php

class OptionTransition extends Transition {
	protected function handle(/*$Node = NULL, */$disableTypeCheck = FALSE) {
		$result = TRUE;
		foreach (self::findTransitions($Node, Element::getPattern('TransitionTo')) as $TransitionTo) {
			foreach (self::findTransitions($Node, Element::getPattern('TransitionFrom')) as $TransitionFrom) {
				$to = $TransitionFrom->getProperty('to');
				if (($Descendant = self::findTarget(sprintf(Element::getPattern('NodeById'), $to))) &&
						$Descendant->register()) {
					$Transition = clone $TransitionTo;
					$Transition->setProperty('to', $Descendant->getProperty('id'));
					$from = $Transition->getProperty('from');
					if (!isset(Node::$Objects[$from]) || ($Object = Node::$Objects[$from]) && $Object->getType() != 'Options') {
						throw new FatalError('Invalid option object', $Node->summarize());
					}
					$value = $Transition->getProperty('condition');
					$condition = $TransitionFrom->getProperty('condition');
					$extension = $value ? $this->getCondition($Object->getKey(), $value) : '';
					$condition = str_replace(' and ' . $extension, '', $condition);
					$condition .= ($condition && $extension ? ' and ' : '') . $extension;
					$Transition->setProperty('condition', $condition);
					$result = $result && $Transition->register();
				}
			}
		}
		return $result;
	}
	
	protected static function getCondition($key, $value, $operator = 'is') {
		return sprintf('%s %s %s', $key, $operator, is_int($value) ? $value : sprintf('\'%s\'', $value));
	}
}