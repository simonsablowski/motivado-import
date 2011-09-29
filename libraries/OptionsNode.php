<?php

class OptionsNode extends Node {
	public static function analyze(Node $Node, $disableTypeCheck = TRUE) {
		list(, $key, $properties, $description) = parent::analyze($Node, $disableTypeCheck);
		$properties = $properties && ($decoded = (array)\Motivado\Api\Json::decode($properties)) ? $decoded : array();
		
		$properties = array_merge($properties, array(
			'options' => array()
		));
		foreach (Transition::findAllOfNode($Node) as $Transition) {
			if ($Option = self::findTarget(sprintf(Element::getPattern('OptionById'), $Transition->getProperty('to')))) {
				$k = ($k = $Transition->getProperty('condition')) ? $k : (count($properties['options']) + 1);
				$properties['options'][$k] = array(
					'key' => $k,
					'value' => $Option->getProperty('title')
				);
			}
		}
		ksort($properties['options']);
		$properties['options'] = array_values($properties['options']);
		
		return array_values(array(
			'key' => $key ? $key : preg_replace('/[^a-z0-9]/i', '', $Node->getProperty('id')),
			'properties' => \Motivado\Api\Json::encode($properties),
			'description' => $description
		));
	}
}