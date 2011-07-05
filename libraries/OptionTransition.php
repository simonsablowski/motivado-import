<?php

class OptionTransition extends Transition {
	protected static function handle(Node $Node) {
		$result = TRUE;
		foreach (self::findAllOfNode($Node, Element::getPattern('TransitionTo')) as $TransitionTo) {
			foreach (self::findAllOfNode($Node, Element::getPattern('TransitionFrom')) as $TransitionFrom) {
				$to = $TransitionFrom->getProperty('to');
				$Descendant = self::findById($to);
				$Descendant->register();
				$from = $TransitionTo->getProperty('from');
				if (!isset(Node::$Objects[$from]) || ($Object = Node::$Objects[$from]) && $Object->getType() != 'Options') {
					throw new FatalError('Invalid option object', $Node->summarize());
				}
				foreach (self::findAllOfNode(Node::findById($from)) as $n => $TransitionToSibling) {
					if ($TransitionToSibling->getProperty('to') != $Node->getProperty('id')) continue;
					$value = ($value = $TransitionTo->getProperty('condition')) ? $value : ($n + 1);
				}
				$Transition = clone $TransitionTo;
				$Transition->setProperty('to', $Descendant->getProperty('id'));
				$condition = $TransitionFrom->getProperty('condition');
				$extension = self::getCondition($Object->getKey(), $value);
				$condition = str_replace(' and ' . $extension, '', $condition);
				$condition .= ($condition && $extension ? ' and ' : '') . $extension;
				$Transition->setProperty('condition', $condition);
				$result = $result && $Transition->register();
			}
		}
		return $result;
	}
	
	protected static function getCondition($key, $value, $operator = 'is') {
		return sprintf('%s %s %s', $key, $operator, is_int($value) ? $value : sprintf('\'%s\'', $value));
	}
}