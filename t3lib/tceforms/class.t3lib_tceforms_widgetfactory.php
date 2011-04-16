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
 * Factory class for widget objects.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 *
 * TODO: should this be a singleton?
 */
class t3lib_TCEforms_WidgetFactory {
	/**
	 * @var t3lib_TCEforms_WidgetRegistry
	 */
	protected $registry;

	public function __construct() {
		$this->registry = t3lib_div::makeInstance('t3lib_TCEforms_WidgetRegistry');
	}

	/**
	 * Creates a widget from a configuration array
	 *
	 * @param array $widgetConfiguration
	 * @return void
	 */
	public function buildWidget(array $widgetConfiguration, $buildRecursive = TRUE) {
		if (isset($widgetConfiguration['class'])) {
			$widgetClass = $widgetConfiguration['class'];
		} elseif (isset($widgetConfiguration['type'])) {
			$widgetClass = $this->registry->getWidgetClass($widgetConfiguration['type']);
		} else {
			// TODO throw exception
		}

		$widgetObject = t3lib_div::makeInstance($widgetClass, $widgetConfiguration);

		if (is_a($widgetObject, 't3lib_TCEforms_ContainerWidget') && isset($widgetConfiguration['items']) && $buildRecursive) {
			$subwidgets = (array)$this->buildWidgetArray($widgetConfiguration['items']);

			$widgetObject->addChildWidgets($subwidgets);
		}

		return $widgetObject;
	}

	/**
	 * Builds widgets from an array of widget configurations. A typical example are the widgets inside a container,
	 * e.g. on one tab of a tab panel
	 *
	 * @param array $widgetConfigurations
	 * @return array The objects that were built
	 */
	public function buildWidgetArray(array $widgetConfigurations) {
		$builtWidgets = array();

		foreach ($widgetConfigurations as $configuration) {
			$builtWidgets[] = $this->buildWidget($configuration);
		}
		return $builtWidgets;
	}
}

?>