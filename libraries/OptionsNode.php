<?php

class OptionsNode extends Node {
	public static function analyze(Node $Node, $disableTypeCheck = FALSE) {
		$pattern = Element::getPattern('OptionById');
		$options = array();
		foreach (Transition::findAllOfNode($Node) as $Transition) {
			if ($Option = self::findTarget(sprintf($pattern, $Transition->getProperty('to')))) {
				$options[] = array(
					'key' => $Transition->getProperty('condition'),
					'value' => $Option->getProperty('title')
				);
			}
		}
		
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
		$o = 1;
		foreach ($options as $option) {
			$properties['options'][] = array(
				'key' => $option['key'] ? $option['key'] : $o,
				'value' => $option['value']
			);
			$o++;
		}
		
		if (!$key) {
			$key = preg_replace('/[^a-z0-9]/i', '', $Node->getProperty('id'));
		}
		
		return array_values(array(
			'key' => $key,
			'properties' => \Motivado\Api\Json::encode($properties)
		));
	}
}
