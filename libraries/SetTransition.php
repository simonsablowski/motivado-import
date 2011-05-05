<?php

class SetTransition extends Transition {
	protected static function handle(Node $Node) {
		$result = TRUE;
		$setId = $Node->getProperty('setId');
		if (!$Start = self::findTarget(sprintf(Element::getPattern('SetByIdStart'), $setId))) {
			throw new FatalError('No start node defined', $Node->summarize());
		}
		if (!$Ends = self::findAll(sprintf(Element::getPattern('SetByIdEnd'), $setId))) {
			throw new FatalError('No end node defined', $Node->summarize());
		}
		foreach (self::findAllOfNode($Node, Element::getPattern('TransitionTo')) as $TransitionTo) {
			$from = $TransitionTo->getProperty('from');
			$Ancestor = self::findById($from);
			self::$saveEnds = FALSE;
			$Nodes = self::traverse($Start);
			foreach (self::findAllOfNode($Node, Element::getPattern('TransitionFrom')) as $TransitionFrom) {
				$to = $TransitionFrom->getProperty('to');
				$Descendant = self::findById($to);
				$Descendant->register();
				foreach ($Ends as $End) {
					$EndId = $End->getProperty('id');
					$TransitionToEnd = self::findTarget(sprintf(Element::getPattern('TransitionTo'), $EndId));
					$EndsAncestorId = $TransitionToEnd->getProperty('from');
					$EndsAncestor = self::findTarget(sprintf(Element::getPattern('NodeById'), $EndsAncestorId));
					$Transition = clone $TransitionFrom;
					$Transition->setProperty('from', $EndsAncestor->getProperty('id'));
					$condition = $TransitionToEnd->getProperty('condition');
					$Transition->setProperty('condition', $condition);
					$result = $result && $Transition->register();
				}
			}
		}
		return $result;
	}
}