<?php

class OptionsNode extends Node {
	protected function handle() {
		$pattern = Element::getPattern('OptionById');
		$options = array();
		foreach (self::findTransitions() as $Transition) {
			if ($Option = self::findTarget(sprintf($pattern, $Transition->getProperty('to')))) {
				$options[] = array(
					'key' => $Transition->getProperty('condition'),
					'value' => $Option->getProperty('title')
				);
			}
		}
		
		list(, $key, $properties, $videoUrl) = parent::handle(TRUE);
		$properties = array('options' => array());
		if ($videoUrl) {
			$properties = array_merge($properties, array(
				'video' => array(
					'url' => $videoUrl
				)
			));
		}
		
		$o = 1;
		foreach ($options as $option) {
			$properties['options'][] = array(
				'key' => $option['key'] ? $option['key'] : $o,
				'value' => $option['value']
			);
			$o++;
		}
		
		if (!$key) {
			$key = preg_replace('/[^a-z0-9]/i', '', $this->getProperty('id'));
		}
		
		return array_values(array(
			'key' => $key,
			'properties' => Json::encode($properties)
		));
	}
}