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
 * Test case for the widget registry.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms_WidgetRegistryTest extends Tx_Phpunit_TestCase {
	/**
	 * @var t3lib_TCEforms_WidgetRegistry
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new t3lib_TCEforms_WidgetRegistry();
	}

	/**
	 * @test
	 */
	public function getRegisteredWidgetTypesReturnsAllRegisteredWidgetTypes() {
		$type1 = uniqid();
		$type2 = uniqid();
		$GLOBALS['TYPO3_CONF_VARS']['TCEFORMS']['widgetTypes'] = array(
			$type2 => uniqid(),
			$type1 => uniqid()
		);

		$this->assertContains($type1, $this->fixture->getRegisteredWidgetTypes());
		$this->assertContains($type2, $this->fixture->getRegisteredWidgetTypes());
	}

	/**
	 * @test
	 */
	public function getWidgetClassReturnsCorrectClassForTypeAndClassname() {
		$type = uniqid();
		$classname = uniqid('t3lib_TCEforms_MockedWidget');
		$GLOBALS['TYPO3_CONF_VARS']['TCEFORMS']['widgetTypes'] = array(
			$type => $classname
		);

			// mock a class for type checks
		$this->getMockClass('t3lib_TCEforms_Widget', array(), array(), $classname);

		$this->assertEquals($classname, $this->fixture->getWidgetClass($type));
		$this->assertEquals($classname, $this->fixture->getWidgetClass($classname));
	}

	/**
	 * @test
	 */
	public function getWidgetTypeReturnsCorrectTypeForClassnameAndObject() {
		$type = uniqid();
		$classname = uniqid('t3lib_TCEforms_MockedWidget');
		$GLOBALS['TYPO3_CONF_VARS']['TCEFORMS']['widgetTypes'] = array(
			$type => $classname
		);

			// mock a class for type checks
		$mockedWidget = $this->getMock('t3lib_TCEforms_Widget', array(), array(), $classname);

		$this->assertEquals($type, $this->fixture->getWidgetType($mockedWidget));
		$this->assertEquals($type, $this->fixture->getWidgetType($classname));
	}
}

?>