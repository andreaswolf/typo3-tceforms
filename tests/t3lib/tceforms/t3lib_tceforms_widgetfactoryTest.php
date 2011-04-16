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
 * Test case for the widget factory
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms_WidgetFactoryTest extends Tx_Phpunit_TestCase {
	/**
	 * @var t3lib_TCEforms_WidgetFactory
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new t3lib_TCEforms_WidgetFactory();
	}

	/**
	 * @test
	 * @covers t3lib_TCEforms_WidgetFactory::buildWidget
	 */
	public function buildWidgetCorrectlyResolvesType() {
		$widgetType = uniqid();
		$widgetClass = uniqid('t3lib_TCEforms_MockedWidget');
		$mockedWidget = $this->getMock('t3lib_TCEforms_Widget', array(), array(), $widgetClass);
		t3lib_div::addInstance($widgetClass, $mockedWidget);

		$mockedRegistry = $this->getMock('t3lib_TCEforms_WidgetRegistry');
		$mockedRegistry->expects($this->any())->method('getWidgetClass')->with($this->equalTo($widgetType))
		  ->will($this->returnValue($widgetClass));
		t3lib_div::setSingletonInstance('t3lib_TCEforms_WidgetRegistry', $mockedRegistry);
		$fixture = new t3lib_TCEforms_WidgetFactory();

		$widgetConfig = array(
			'type' => $widgetType
		);
		$fixture->buildWidget($widgetConfig);
	}

	/**
	 * @test
	 * @covers t3lib_TCEforms_WidgetFactory::buildWidget
	 */
	public function buildWidgetReturnsCorrectObjectForGivenType() {
		$widgetType = uniqid();
		$widgetClass = uniqid('t3lib_TCEforms_MockedWidget');
		$mockedWidget = $this->getMock('t3lib_TCEforms_Widget', array(), array(), $widgetClass);
		t3lib_div::addInstance($widgetClass, $mockedWidget);

		$mockedRegistry = $this->getMock('t3lib_TCEforms_WidgetRegistry');
		$mockedRegistry->expects($this->any())->method('getWidgetClass')->with($this->equalTo($widgetType))
		  ->will($this->returnValue($widgetClass));
		t3lib_div::setSingletonInstance('t3lib_TCEforms_WidgetRegistry', $mockedRegistry);
		$fixture = new t3lib_TCEforms_WidgetFactory();

		$widgetConfig = array(
			'type' => $widgetType
		);
		$widgetObject = $fixture->buildWidget($widgetConfig);

		$this->assertSame($mockedWidget, $widgetObject);
	}

	/**
	 * @test
	 * @covers t3lib_TCEforms_WidgetFactory::buildWidget
	 */
	public function buildWidgetReturnsCorrectObjectForGivenClass() {
		$widgetClass = uniqid('t3lib_TCEforms_MockedWidget');
		$mockedWidget = $this->getMock('t3lib_TCEforms_Widget', array(), array(), $widgetClass);
		t3lib_div::addInstance($widgetClass, $mockedWidget);

		$widgetConfig = array(
			'class' => $widgetClass
		);
		$widgetObject = $this->fixture->buildWidget($widgetConfig);

		$this->assertSame($mockedWidget, $widgetObject);
	}

	/**
	 * The aspect tested in this test helps preventing unneccessary lookups in the widget registry
	 *
	 * @test
	 * @covers t3lib_TCEforms_WidgetFactory::buildWidget
	 */
	public function buildWidgetPrefersClassIfClassAndTypeAreGiven() {
		$widgetType = uniqid();
		$widgetClass = uniqid('t3lib_TCEforms_MockedWidget');
		$mockedWidget = $this->getMock('t3lib_TCEforms_Widget', array(), array(), $widgetClass);
		t3lib_div::addInstance($widgetClass, $mockedWidget);

		$mockedRegistry = $this->getMock('t3lib_TCEforms_WidgetRegistry');
			// if widget class lookup is never called, we can be sure the class was directly used
		$mockedRegistry->expects($this->never())->method('getWidgetClass');
		t3lib_div::setSingletonInstance('t3lib_TCEforms_WidgetRegistry', $mockedRegistry);
		$fixture = new t3lib_TCEforms_WidgetFactory();

		$widgetConfig = array(
			'type' => $widgetType,
			'class' => $widgetClass
		);
		$fixture->buildWidget($widgetConfig);
	}

	/**
	 * @test
	 */
	public function buildWidgetBuildsSubitemsForContainers() {
		$widgetClassName = uniqid('t3lib_TCEforms_MockedWidget');
		$mockedWidget = $this->getMock('t3lib_TCEforms_ContainerWidget', array(), array(), $widgetClassName);
		t3lib_div::addInstance($widgetClassName, $mockedWidget);

		$widgetConfig = array(
			'class' => $widgetClassName,
			'items' => array(
				array(
					'class' => 'foo'
				),
				array(
					'class' => 'bar'
				)
			)
		);

		/** @var t3lib_TCEforms_WidgetFactory $fixture */
		$fixture = $this->getMock('t3lib_TCEforms_WidgetFactory', array('buildWidgetArray'));
		$fixture->expects($this->once())->method('buildWidgetArray')->with($this->equalTo($widgetConfig['items']));

		$fixture->buildWidget($widgetConfig);
	}

	/**
	 * @test
	 */
	public function buildWidgetDoesNotBuildSubitemsIfWidgetIsNoContainer() {
		$widgetClassName = uniqid('t3lib_TCEforms_MockedWidget');
		$mockedWidget = $this->getMock('t3lib_TCEforms_Widget', array(), array(), $widgetClassName);
		t3lib_div::addInstance($widgetClassName, $mockedWidget);

		$widgetConfig = array(
			'class' => $widgetClassName,
			'items' => array(
			)
		);

		/** @var t3lib_TCEforms_WidgetFactory $fixture */
		$fixture = $this->getMock('t3lib_TCEforms_WidgetFactory', array('buildWidgetArray'));
		$fixture->expects($this->never())->method('buildWidgetArray');

		$fixture->buildWidget($widgetConfig);
	}

	/**
	 * @test
	 */
	public function buildWidgetDoesNotBuildSubitemsIfBuildRecursiveIsNotSet() {
		$widgetClassName = uniqid('t3lib_TCEforms_MockedWidget');
		$mockedWidget = $this->getMock('t3lib_TCEforms_ContainerWidget', array(), array(), $widgetClassName);
		t3lib_div::addInstance($widgetClassName, $mockedWidget);

		$widgetConfig = array(
			'class' => $widgetClassName,
			'items' => array(
			)
		);

		/** @var t3lib_TCEforms_WidgetFactory $fixture */
		$fixture = $this->getMock('t3lib_TCEforms_WidgetFactory', array('buildWidgetArray'));
		$fixture->expects($this->never())->method('buildWidgetArray');

		$fixture->buildWidget($widgetConfig, FALSE);
	}

	/**
	 * @test
	 * @covers t3lib_TCEforms_WidgetFactory::buildWidget
	 */
	public function buildWidgetAddsBuiltSubitemsToWidget() {
		$widgetClassName = uniqid('t3lib_TCEforms_MockedWidget');
		$mockedWidget = $this->getMock('t3lib_TCEforms_ContainerWidget', array(), array(), $widgetClassName);
		t3lib_div::addInstance($widgetClassName, $mockedWidget);

		$mockedSubwidgets = array(
			$this->getMock('t3lib_TCEforms_Widget'),
			$this->getMock('t3lib_TCEforms_Widget')
		);

		$widgetConfig = array(
			'class' => $widgetClassName,
			'items' => array(
			)
		);

		/** @var t3lib_TCEforms_WidgetFactory $fixture */
		$fixture = $this->getMock('t3lib_TCEforms_WidgetFactory', array('buildWidgetArray'));
		$fixture->expects($this->once())->method('buildWidgetArray')->will($this->returnValue($mockedSubwidgets));

		$mockedWidget->expects($this->once())->method('addChildWidgets')->with($this->equalTo($mockedSubwidgets));

		$fixture->buildWidget($widgetConfig);
	}

	/**
	 * @test
	 */
	public function buildWidgetArrayBuildsAllWidgets() {
		$subwidgetConfig = array(
			array(
				'class' => uniqid()
			),
			array(
				'class' => uniqid()
			)
		);

		/** @var $fixture t3lib_TCEforms_WidgetFactory */
		$fixture = $this->getMock('t3lib_TCEforms_WidgetFactory', array('buildWidget'));
		$fixture->expects($this->at(0))->method('buildWidget')->with($subwidgetConfig[0]);
		$fixture->expects($this->at(1))->method('buildWidget')->with($subwidgetConfig[1]);

		$fixture->buildWidgetArray($subwidgetConfig);
	}

	/**
	 * @test
	 */
	public function buildWidgetArrayReturnsBuiltWidgets() {
		$subwidgetConfig = array(
			array(
				'class' => uniqid()
			),
			array(
				'class' => uniqid()
			)
		);
		$mockedWidgets = array(
			$this->getMock('t3lib_TCEforms_Widget'),
			$this->getMock('t3lib_TCEforms_Widget')
		);

		/** @var $fixture t3lib_TCEforms_WidgetFactory */
		$fixture = $this->getMock('t3lib_TCEforms_WidgetFactory', array('buildWidget'));
		$fixture->expects($this->at(0))->method('buildWidget')->with($subwidgetConfig[0])->will($this->returnValue($mockedWidgets[0]));
		$fixture->expects($this->at(1))->method('buildWidget')->with($subwidgetConfig[1])->will($this->returnValue($mockedWidgets[1]));

		$builtWidgets = $fixture->buildWidgetArray($subwidgetConfig);

		$this->assertSame($mockedWidgets[0], $builtWidgets[0]);
		$this->assertSame($mockedWidgets[1], $builtWidgets[1]);
	}
}

?>