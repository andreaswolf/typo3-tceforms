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
 * The builder that combines a datastructure and a record object into a tree of widgets
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms_WidgetBuilder {

	/**
	 * @var t3lib_TCEforms_WidgetFactory
	 */
	protected $widgetFactory;

	public function setWidgetFactory(t3lib_TCEforms_WidgetFactory $widgetFactory) {
		$this->widgetFactory = $widgetFactory;
		return $this;
	}

	/**
	 * Builds an abstract widget tree for a record type. This abstract tree can be combined with a record object,
	 * creating a concrete tree with real field widgets (instead of FieldProxy widgets).
	 *
	 * @return void
	 *
	 * @see bindWidgetTreeToRecord()
	 */
	public function buildWidgetTreeForType(t3lib_DataStructure_Type $type) {
		/*
		 * TODO for caching:
		 *  - check cache; if entry is present, return it
		 *  - cache built arrays
		 *
		 * TODO _Type should add display conditions to subtype add/exclude fields (instead of fiddling around with add/exclude lists)
		 */
		$widgetConfiguration = $type->getWidgetConfiguration();
		$abstractWidgetTree = $this->buildRecursiveWidgetArray($widgetConfiguration);

		return $abstractWidgetTree;
	}

	// (presumably) called by Form/Context after a record has been added
	// TODO document
	public function bindWidgetTreeToRecord(t3lib_TCEforms_ContainerWidget $widgetTreeRoot, t3lib_TCEforms_Record $record, $wp = NULL) {
		/*
		 * TODO:
		 *  - honor subtype value if any -> this should be done by display conditions inside the widget objects
		 */
			// a two-dimensional stack: the first level contains an array for each nesting level, the second level contains
			// the config elements with a reference back to their parent widget
		$widgetStack = new SplDoublyLinkedList();
		$widgetStack->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_DELETE);

		$widgetStack->push($widgetTreeRoot);

		foreach ($widgetStack as $widgetObject) {
			/** @var $widgetObject t3lib_TCEforms_Widget */
			if (is_a($widgetObject, 't3lib_TCEforms_ContainerWidget')) {
				$childWidgets = $widgetObject->getChildWidgets();

				foreach ($childWidgets as $childWidget) {
					$widgetStack->push($childWidget);
				}
			}

			if (is_a($widgetObject, 't3lib_TCEforms_Widget_FieldProxy')) {
				$newWidgetObject = $this->createWidgetObjectFromProxy($widgetObject);

				/** @var $parentObject t3lib_TCEforms_ContainerWidget */
				$parentObject = $widgetObject->getParentWidget();
				$parentObject->replaceChildWidget($widgetObject, $newWidgetObject);
			}
		}
	}

	/**
	 * Creates a real field widget from a field proxy.
	 *
	 * @param t3lib_TCEforms_Widget_FieldProxy $proxyObject
	 * @return t3lib_TCEforms_FieldWidget
	 */
	public function createWidgetObjectFromProxy(t3lib_TCEforms_Widget_FieldProxy $proxyObject) {
		// TODO implement
		return t3lib_div::makeInstance('t3lib_TCEforms_Widget_AbstractField');
	}

	public function buildRecursiveWidgetArray(array $widgetConfigurations) {
			// a two-dimensional stack: the first level contains one array for each nesting level, the second level contains
			// the config elements with a reference back to their parent widget
		$widgetStack = new SplDoublyLinkedList();
		$widgetStack->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_DELETE);

		foreach ($widgetConfigurations as $widgetConfig) {
			$widgetStack->push(array(
				'config' => $widgetConfig
			));
		}

			// the widgets on the outermost level
		$topLevelWidgets = array();
		foreach ($widgetStack as $stackEntry) {
			$widgetObject = $this->widgetFactory->buildWidget($stackEntry['config'], FALSE);

			if (isset($stackEntry['parent'])) {
				$stackEntry['parent']->addChildWidget($widgetObject);
			} else {
				$topLevelWidgets[] = $widgetObject;
			}

				// test for child widgets
			if (is_a($widgetObject, 't3lib_TCEforms_ContainerWidget') && isset($stackEntry['config']['items'])) {
				foreach ($stackEntry['config']['items'] as $subitemConfig) {
					$widgetStack->push(array(
						'config' => $subitemConfig,
						'parent' => $widgetObject
					));
				}
			}
		}

		return $topLevelWidgets;
	}
}

?>