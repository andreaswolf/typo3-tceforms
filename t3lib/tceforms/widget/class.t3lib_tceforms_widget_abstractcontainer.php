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
 * Abstract container widget
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
abstract class t3lib_TCEforms_Widget_AbstractContainer extends t3lib_TCEforms_Widget_Abstract implements t3lib_TCEforms_ContainerWidget {
	protected $childWidgets = array();

	public function hasChildWidgets() {
		return !empty($this->childWidgets);
	}

	public function getChildWidgetCount() {
		return count($this->childWidgets);
	}

	public function getChildWidgets() {
		return $this->childWidgets;
	}

	public function addChildWidget(t3lib_TCEforms_Widget $widget) {
		$this->childWidgets[] = $widget;
		$widget->setParentWidget($this);
	}

	public function addChildWidgets(array $widgets) {
		foreach ($widgets as $widget) {
			$this->addChildWidget($widget);
		}
	}

	public function replaceChildWidget(t3lib_TCEforms_Widget $oldWidget, t3lib_TCEforms_Widget $newWidget) {
		$key = array_search($oldWidget, $this->childWidgets);

		if ($key === FALSE) {
			// TODO throw exception
		}

		$this->childWidgets[$key] = $newWidget;
	}

	public function isPossibleChildWidget(t3lib_TCEforms_Widget $widget) {
		// TODO: Implement isPossibleChildWidget() method.
	}

	public function __clone() {
		foreach ($this->childWidgets as $index => $widget) {
			$this->childWidgets[$index] = clone $widget;
		}
	}

	/**
	 * Renders this widgets.
	 *
	 * @param t3lib_TCEforms_Renderer $renderer
	 * @param string $templateFile
	 * @param string $childWidgetContents
	 * @return string
	 */
	public function renderContainer(t3lib_TCEforms_Renderer $renderer, $templateFile, $childWidgetContents) {
		$field = $this;

		ob_start();
		include($templateFile);
		$renderedContents = ob_get_flush();

		return $renderedContents;
	}
}

?>