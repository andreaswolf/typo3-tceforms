<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Freef Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Registry for widget classes
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms_WidgetRegistry implements t3lib_Singleton {
	public function registerWidget($class, $configuration) {
		// TODO implement method
	}

	/**
	 * Returns an array with all registered widget types (only the shorthand form). This does not neccessarily return
	 * all registered/available widgets!
	 *
	 * @return array
	 * @static
	 */
	public function getRegisteredWidgetTypes() {
		return array_keys($GLOBALS['TYPO3_CONF_VARS']['TCEFORMS']['widgetTypes']);
	}

	public function isRegisteredWidgetType($type) {
		return array_key_exists($type, $GLOBALS['TYPO3_CONF_VARS']['TCEFORMS']['widgetTypes']);
	}

	public function getWidgetClass($classNameOrType) {
		$className = '';

		// TODO check if class really is a widget
		if (class_exists($classNameOrType)) {
			$className = $classNameOrType;
		} elseif ($this->isRegisteredWidgetType($classNameOrType)) {
			$className = $GLOBALS['TYPO3_CONF_VARS']['TCEFORMS']['widgetTypes'][$classNameOrType];
		} else {
			// TODO throw exception
		}

		return $className;
	}

	/**
	 * Returns the widget type for a given widget class name or an object.
	 *
	 * @param string|object $classNameOrObject
	 * @return string
	 *
	 * @throws InvalidArgumentException If no widget type is found for the argument.
	 */
	public function getWidgetType($classNameOrObject) {
		$type = '';

		if (is_object($classNameOrObject)) {
			$classNameOrObject = get_class($classNameOrObject);
		}

		if (class_exists($classNameOrObject)) {
			$type = array_search($classNameOrObject, $GLOBALS['TYPO3_CONF_VARS']['TCEFORMS']['widgetTypes']);

			if ($type === FALSE) {
				throw new RuntimeException("No type could be found for class $classNameOrObject", 1304518958);
			}
		} else {
			// TODO throw exception
		}

		return $type;
	}
}

?>