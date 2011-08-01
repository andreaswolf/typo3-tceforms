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
 * Testcase for the abstract renderer class.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms_AbstractRendererTest extends Tx_Phpunit_TestCase {
	/**
	 * @var t3lib_TCEforms_AbstractRenderer
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = $this->getMock('t3lib_TCEforms_AbstractRenderer', array('getTemplateFileForWidget'));
	}

	/**
	 * @test
	 */
	public function childWidgetsAreRenderedInCorrectOrder() {
		$treeRoot = $this->getMock('t3lib_TCEforms_ContainerWidget');
		$childA = $this->getMock('t3lib_TCEforms_Widget');
		$childB = $this->getMock('t3lib_TCEforms_Widget');

		$treeRoot->expects($this->once())->method('getChildWidgets')->will($this->returnValue(array($childA, $childB)));
		$childA->expects($this->once())->method('render')->will($this->returnValue('childA'));
		$childB->expects($this->once())->method('render')->will($this->returnValue('childB'));

		$treeRoot->expects($this->once())->method('renderContainer')->with($this->anything(), $this->anything(), $this->equalTo('childAchildB'));

		$this->fixture->renderWidgetTree($treeRoot);
	}

	/**
	 * @test
	 */
	public function contentsOfWidgetsAreInsertedIntoDirectParentWidget() {
		$treeRoot = $this->getMock('t3lib_TCEforms_ContainerWidget');
		$childA = $this->getMock('t3lib_TCEforms_ContainerWidget');
		$childAA = $this->getMock('t3lib_TCEforms_Widget');

		$treeRoot->expects($this->once())->method('getChildWidgets')->will($this->returnValue(array($childA)));
		$treeRoot->expects($this->once())->method('renderContainer')->with($this->anything(), $this->anything(), $this->equalTo('childA'));
		$childA->expects($this->once())->method('renderContainer')->with($this->anything(), $this->anything(), $this->equalTo('childAA'))->will($this->returnValue('childA'));
		$childA->expects($this->once())->method('getChildWidgets')->will($this->returnValue(array($childAA)));
		$childAA->expects($this->once())->method('render')->will($this->returnValue('childAA'));

		$this->fixture->renderWidgetTree($treeRoot);
	}
}

?>