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
 * Test case for the Widget tree builder class
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms_WidgetBuilderTest extends Tx_Phpunit_TestCase {
	/**
	 * @var t3lib_TCEforms_WidgetBuilder
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new t3lib_TCEforms_WidgetBuilder();
	}

	/**
	 * @test
	 */
	public function buildRecursiveWidgetArrayUsesFactoryToBuildWidgets() {
		$widgetConfig = array(
			array('type' => 'foo'),
			array('type' => 'bar')
		);

		$mockedWidgetFactory = $this->getMock('t3lib_TCEforms_WidgetFactory');
		$mockedWidgetFactory->expects($this->at(0))->method('buildWidget')->with($this->equalTo($widgetConfig[0]));
		$mockedWidgetFactory->expects($this->at(1))->method('buildWidget')->with($this->equalTo($widgetConfig[1]));
		$this->fixture->setWidgetFactory($mockedWidgetFactory);

		$this->fixture->buildRecursiveWidgetArray($widgetConfig);
	}

	/**
	 * @test
	 */
	public function buildRecursiveWidgetArrayAddsSubwidgetsToCorrectWidget() {
		$widgetConfig = array(
			array(
				'type' => 'foo',
				'items' => array(
					array('type' => 'baz')
				)
			),
			array(
				'type' => 'bar',
				'items' => array(
					array('type' => 'bat')
				)
			)
		);
		$mockedWidgets = array(
			$this->getMock('t3lib_TCEforms_ContainerWidget', array(), array(), '', FALSE),
			$this->getMock('t3lib_TCEforms_ContainerWidget', array(), array(), '', FALSE),
			$this->getMock('t3lib_TCEforms_Widget', array(), array(), '', FALSE),
			$this->getMock('t3lib_TCEforms_Widget', array(), array(), '', FALSE)
		);

		$mockedWidgetFactory = $this->getMock('t3lib_TCEforms_WidgetFactory');
		$mockedWidgetFactory->expects($this->at(0))->method('buildWidget')->with($this->equalTo($widgetConfig[0]))
		  ->will($this->returnValue($mockedWidgets[0]));
		$mockedWidgetFactory->expects($this->at(1))->method('buildWidget')->with($this->equalTo($widgetConfig[1]))
		  ->will($this->returnValue($mockedWidgets[1]));
		$mockedWidgetFactory->expects($this->at(2))->method('buildWidget')->with($this->equalTo($widgetConfig[0]['items'][0]))
		  ->will($this->returnValue($mockedWidgets[2]));
		$mockedWidgetFactory->expects($this->at(3))->method('buildWidget')->with($this->equalTo($widgetConfig[1]['items'][0]))
		  ->will($this->returnValue($mockedWidgets[3]));
		$this->fixture->setWidgetFactory($mockedWidgetFactory);

		$mockedWidgets[0]->expects($this->once())->method('addChildWidget')->with($this->equalTo($mockedWidgets[2]));
		$mockedWidgets[1]->expects($this->once())->method('addChildWidget')->with($this->equalTo($mockedWidgets[3]));

		$this->fixture->buildRecursiveWidgetArray($widgetConfig);
	}

	/**
	 * @test
	 */
	public function buildRecursiveWidgetArrayReturnsArrayWithToplevelWidgets() {
		$widgetConfig = array(
			array('type' => 'foo', 'items' => array(array('type' => 'baz'))),
			array('type' => 'bar')
		);
		$mockedWidgets = array(
			$this->getMock('t3lib_TCEforms_ContainerWidget', array(), array(), '', FALSE),
			$this->getMock('t3lib_TCEforms_Widget', array(), array(), '', FALSE),
			$this->getMock('t3lib_TCEforms_Widget', array(), array(), '', FALSE)
		);

		$mockedWidgetFactory = $this->getMock('t3lib_TCEforms_WidgetFactory');
		$mockedWidgetFactory->expects($this->at(0))->method('buildWidget')->with($this->equalTo($widgetConfig[0]))
		  ->will($this->returnValue($mockedWidgets[0]));
		$mockedWidgetFactory->expects($this->at(1))->method('buildWidget')->with($this->equalTo($widgetConfig[1]))
		  ->will($this->returnValue($mockedWidgets[1]));
		$mockedWidgetFactory->expects($this->at(2))->method('buildWidget')->with($this->equalTo($widgetConfig[0]['items'][0]))
		  ->will($this->returnValue($mockedWidgets[2]));
		$this->fixture->setWidgetFactory($mockedWidgetFactory);

		$widgetTree = $this->fixture->buildRecursiveWidgetArray($widgetConfig);

		$this->assertEquals(array($mockedWidgets[0], $mockedWidgets[1]), $widgetTree);
	}

	/**
	 * @test
	 */
	public function buildRecursiveWidgetArrayBuildsAllSubwidgetsOfAWidget() {
		$widgetConfig = array(
			array('type' => 'foo', 'items' => array(
				array('type' => 'bar'),
				array('type' => 'baz')
			))
		);
		$mockedWidgets = array(
			$this->getMock('t3lib_TCEforms_ContainerWidget', array(), array(), '', FALSE),
			$this->getMock('t3lib_TCEforms_Widget', array(), array(), uniqid('bar'), FALSE),
			$this->getMock('t3lib_TCEforms_Widget', array(), array(), uniqid('baz'), FALSE)
		);

		$mockedWidgets[0]->expects($this->at(0))->method('addChildWidget')->with($this->equalTo($mockedWidgets[1]));
		$mockedWidgets[0]->expects($this->at(1))->method('addChildWidget')->with($this->equalTo($mockedWidgets[2]));

		$mockedWidgetFactory = $this->getMock('t3lib_TCEforms_WidgetFactory');
		$mockedWidgetFactory->expects($this->at(0))->method('buildWidget')->with($this->equalTo($widgetConfig[0]))
		  ->will($this->returnValue($mockedWidgets[0]));
		$mockedWidgetFactory->expects($this->at(1))->method('buildWidget')->with($this->equalTo($widgetConfig[0]['items'][0]))
		  ->will($this->returnValue($mockedWidgets[1]));
		$mockedWidgetFactory->expects($this->at(2))->method('buildWidget')->with($this->equalTo($widgetConfig[0]['items'][1]))
		  ->will($this->returnValue($mockedWidgets[2]));
		$this->fixture->setWidgetFactory($mockedWidgetFactory);

		$this->fixture->buildRecursiveWidgetArray($widgetConfig);
	}
}

?>