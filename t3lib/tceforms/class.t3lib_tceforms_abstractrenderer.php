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
 *  the Free Software Foundation; either version 2 of the License, or
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
 * Abstract base class for all renderers
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
abstract class t3lib_TCEforms_AbstractRenderer implements t3lib_TCEforms_Renderer {

	/**
	 * @var t3lib_TCEforms_WidgetRegistry
	 */
	protected $registry;

	/**
	 * The format used by this renderer. This may be an arbitrary string, but it should be lowercase and ASCII only,
	 * as it may be used in filenames.
	 *
	 * Set this in inherited renderer classes.
	 *
	 * @var string
	 */
	protected $format = '';

	/**
	 * The file extension used by this renderer. This should conform to the usual rules for filenames.
	 *
	 * Set this in inherited renderer classes.
	 *
	 * @var string
	 */
	protected $fileExtension = '';

	/**
	 * Sets the widget registry.
	 *
	 * @param t3lib_TCEforms_WidgetRegistry $registry
	 * @return t3lib_TCEforms_AbstractRenderer
	 */
	public function setRegistry(t3lib_TCEforms_WidgetRegistry $registry) {
		$this->registry = $registry;
		return $this;
	}

	public function renderWidgetTree(t3lib_TCEforms_ContainerWidget $treeRoot) {
		$renderedContents = '';

		/** @var $childWidgets t3lib_TCEforms_Widget[] */
		$childWidgets = $treeRoot->getChildWidgets();
		/** @var $currentWidget t3lib_TCEforms_Widget */
		foreach ($childWidgets as $currentWidget) {
			if (is_a($currentWidget, 't3lib_TCEforms_ContainerWidget')) {
				$renderedContents .= $this->renderWidgetTree($currentWidget);
			} else {
				$renderedContents .= $this->renderWidget($currentWidget);
			}
		}

		$renderedContents = $this->renderContainerWidget($treeRoot, $renderedContents);

		return $renderedContents;
	}

	protected function renderWidget(t3lib_TCEforms_Widget $widget) {
		$template = $this->getTemplateFileForWidget($widget);
		return $widget->render($this, $template);
	}

	protected function renderContainerWidget(t3lib_TCEforms_ContainerWidget $containerWidget, $subwidgetContents) {
		$template = $this->getTemplateFileForWidget($containerWidget);
		return $containerWidget->renderContainer($this, $template, $subwidgetContents);
	}

	protected function getTemplateFileForWidget(t3lib_TCEforms_Widget $widget) {
		$type = $this->registry->getWidgetType($widget);

		$templatePath = PATH_typo3 . 'templates/tceforms/';
		$template = sprintf('%s%s%s.%s.%s', $templatePath, DIRECTORY_SEPARATOR, $type, $this->format, $this->fileExtension);

		return $template;
	}
}

?>