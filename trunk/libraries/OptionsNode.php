<?php

class OptionsNode extends Node {
	public static function analyze(Node $Node, $disableTypeCheck = FALSE) {
		list(, $key, $properties, $videoUrl) = parent::analyze($Node, TRUE);
		$properties = $properties && ($decoded = (array)\Motivado\Api\Json::decode($properties)) ? $decoded : array();
		if ($videoUrl) {
			$properties['video'] = array(
				'url' => $videoUrl
			);
		}
		
		$properties = array_merge($properties, array(
			'options' => array()
		));
		foreach (Transition::findAllOfNode($Node) as $Transition) {
			if ($Option = self::findTarget(sprintf(Element::getPattern('OptionById'), $Transition->getProperty('to')))) {
				$properties['options'][] = array(
					'key' => ($k = $Transition->getProperty('condition')) ? $k : (count($properties['options']) + 1),
					'value' => $Option->getProperty('title')
				);
			}
		}
		
		return array_values(array(
			'key' => $key ? $key : preg_replace('/[^a-z0-9]/i', '', $Node->getProperty('id')),
			'properties' => \Motivado\Api\Json::encode($properties)
		));
	}
}