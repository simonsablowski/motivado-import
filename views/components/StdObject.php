<?php

if (!function_exists('tabs')) {
	function tabs($number = 1) {
		return str_repeat("\t", $number);
	}
}

if (!function_exists('dumpProperty')) {
	function dumpProperty($property, $value, $indent) {
		if (is_array($value)) {
			$dump = sprintf("%s<tr>\n%s<td>\n%s%s\n%s</td>\n%s<td>\n%s<table>\n", tabs($indent + 1), tabs($indent + 2), tabs($indent + 3), $property, tabs($indent + 2), tabs($indent + 2), tabs($indent + 3));
			foreach ($value as $itemsProperty => $itemsValue) {
				$dump .= dumpProperty(is_string($itemsProperty) ? $itemsProperty : $itemsProperty + 1, $itemsValue, $indent + 3);
			}
			$dump .= sprintf("%s</table>\n%s</td>\n%s</tr>\n", tabs($indent + 3), tabs($indent + 2), tabs($indent + 1));
			return $dump;
		} else if (is_string($value) || is_int($value)) {
			return sprintf("%s<tr>\n%s<td>\n%s%s\n%s</td>\n%s<td>\n%s%s\n%s</td>\n%s</tr>\n", tabs($indent + 1), tabs($indent + 2), tabs($indent + 3), $property, tabs($indent + 2), tabs($indent + 2), tabs($indent + 3), $value, tabs($indent + 2), tabs($indent + 1));
		} else if (is_object($value)) {
			$dump = '';
			foreach (get_object_vars($value) as $p => $v) {
				$dump .= dumpProperty($p, $v, $indent + 1);
			}
			return $dump;
		}
		return NULL;
	}
}

$indent = isset($indent) ? $indent : 1;

if (is_object($StdObject)) {
	printf("%s<table class=\"decoded\">\n", tabs($indent));
	foreach (get_object_vars($StdObject) as $property => $value) {
		print dumpProperty($property, $value, $indent);
	}
	printf("%s</table>", tabs($indent));
}