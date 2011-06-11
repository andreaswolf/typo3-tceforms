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
 * Testcase for the data structure abstraction class in TCA.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_DataStructure_TcaTest extends Tx_Phpunit_TestCase {

	/**
	 * @var t3lib_DataStructure_Tca
	 */
	private $fixture;

	/**
	 * @var array
	 */
	private $tcaFixture = array();

	protected function setUpFixture($Tca) {
		$this->fixture = new t3lib_DataStructure_Tca($Tca);
	}

	/**
	 * @test
	 * @covers t3lib_DataStructure_Tca::hasControlValue
	 * @covers t3lib_DataStructure_Tca::getControlValue
	 */
	public function controlValuesCanBeRetrievedFromDataStructure() {
		$TcaFixture = array(
			'ctrl' => array(
				'some' => uniqid(),
				'random' => uniqid(),
				'config' => uniqid(),
				'values' => uniqid()
			)
		);

		$fixture = new t3lib_DataStructure_Tca($TcaFixture);

		foreach ($TcaFixture['ctrl'] as $key => $value) {
			$this->assertTrue($fixture->hasControlValue($key));
			$this->assertEquals($value, $fixture->getControlValue($key));
		}

		$this->assertFalse($fixture->hasControlValue(uniqid()));
	}

	/**
	 * @test
	 * @covers t3lib_DataStructure_Tca::getFieldObject
	 */
	public function getFieldObjectReturnsProperFieldObject() {
		/** @var $mockedField t3lib_DataStructure_Elements_Field */
		$mockedField = $this->getMock('t3lib_TCA_DataStructure_Field', array(), array(), '', FALSE);
		t3lib_div::addInstance('t3lib_TCA_DataStructure_Field', $mockedField);

		/** @var $fixture t3lib_DataStructure_Tca */
		$fixture = $this->getMock('t3lib_DataStructure_Tca', NULL, array(), '', FALSE);

		$this->assertSame($mockedField, $fixture->getFieldObject(uniqid()));
	}

	/**
	 * @test
	 * @covers t3lib_DataStructure_Tca::getFieldObject
	 */
	public function getFieldObjectCachesObjects() {
		/** @var $mockedField t3lib_DataStructure_Elements_Field */
		$mockedField = $this->getMock('t3lib_TCA_DataStructure_Field', array(), array(), '', FALSE);
		t3lib_div::addInstance('t3lib_TCA_DataStructure_Field', $mockedField);
		$fieldName1 = uniqid();

		/** @var $mockedField t3lib_DataStructure_Elements_Field */
		$mockedField2 = $this->getMock('t3lib_TCA_DataStructure_Field', array(), array(), '', FALSE);
		t3lib_div::addInstance('t3lib_TCA_DataStructure_Field', $mockedField2);
		$fieldName2 = uniqid();

		/** @var $fixture t3lib_DataStructure_Tca */
		$fixture = $this->getMock('t3lib_DataStructure_Tca', NULL, array(), '', FALSE);

		$obj1 = $fixture->getFieldObject($fieldName1);
		$obj2 = $fixture->getFieldObject($fieldName1);
		$obj3 = $fixture->getFieldObject($fieldName2);

		$this->assertSame($mockedField, $obj1);
		$this->assertSame($obj1, $obj2);
		$this->assertNotSame($obj1, $obj3);
	}

	/**
	 * @test
	 * @covers t3lib_DataStructure_Tca::hasTypeField
	 * @covers t3lib_DataStructure_Tca::getTypeField
	 */
	public function typeFieldIsCorrectlyHandled() {
		$TcaFixture = array(
			'ctrl' => array(
				'type' => uniqid()
			)
		);

		$fixture = new t3lib_DataStructure_Tca($TcaFixture);

		$this->assertTrue($fixture->hasTypeField());
		$this->assertEquals($TcaFixture['ctrl']['type'], $fixture->getTypeField());
	}

	/**
	 * @test
	 * @covers t3lib_DataStructure_Tca::hasLanguageField
	 * @covers t3lib_DataStructure_Tca::getLanguageField
	 */
	public function languageFieldIsCorrectlyHandled() {
		$TcaFixture = array(
			'ctrl' => array(
				'languageField' => uniqid()
			)
		);

		$fixture = new t3lib_DataStructure_Tca($TcaFixture);

		$this->assertTrue($fixture->hasLanguageField());
		$this->assertEquals($TcaFixture['ctrl']['languageField'], $fixture->getLanguageField());
	}

	/**
	 * @test
	 */
	public function hasFieldReturnsProperValues() {
		$TcaFixture = array(
			'columns' => array(
				'some' => array('foo' => uniqid()),
				'random' => array('bar' => uniqid()),
				'columns' => array('baz' => uniqid())
			)
		);

		$fixture = new t3lib_DataStructure_Tca($TcaFixture);

		foreach (array_keys($TcaFixture['columns']) as $fieldName) {
			$this->assertTrue($fixture->hasField($fieldName));
		}

		$this->assertFalse($fixture->hasField(uniqid('foo')));
	}

	/**
	 * @test
	 */
	public function fieldConfigurationsCanBeRetrievedFromDataStructure() {
		$TcaFixture = array(
			'columns' => array(
				'some' => array('foo' => uniqid()),
				'random' => array('bar' => uniqid()),
				'columns' => array('baz' => uniqid())
			)
		);

		$fixture = new t3lib_DataStructure_Tca($TcaFixture);

		$fieldConfigurations = $fixture->getFieldConfigurations();
		foreach ($TcaFixture['columns'] as $fieldName => $configuration) {
			$this->assertArrayHasKey($fieldName, $fieldConfigurations);
			$this->assertEquals($configuration, $fieldConfigurations[$fieldName]);
			$this->assertEquals($configuration, $fixture->getFieldConfiguration($fieldName));
		}
	}

	/**
	 * @test
	 * @covers t3lib_DataStructure_Tca::getTypeConfiguration
	 */
	public function getTypeConfigurationFallsBackToDefaultTypeNumberForInvalidTypeNumbers() {
		/** @var $fixture t3lib_DataStructure_Tca */
		$fixture = $this->getMock('t3lib_DataStructure_Tca', array('typeExists', 'createTypeObject'), array(), '', FALSE);
		$fixture->expects($this->once())->method('typeExists')->will($this->returnValue(FALSE));
		$fixture->expects($this->once())->method('createTypeObject')->with($this->equalTo(1));

		$fixture->getTypeConfiguration(0);
	}

	/**
	 * @test
	 * @covers t3lib_DataStructure_Tca::getTypeConfiguration
	 * @covers t3lib_DataStructure_Tca::createTypeObject
	 */
	public function getTypeConfiurationCachesCreatedObjects() {
		$mockedType = $this->getMock('t3lib_DataStructure_Type', array(), array(), '', FALSE);
		t3lib_div::addInstance('t3lib_DataStructure_Type', $mockedType);

		$mockedTca = array('types' => array(
			'1' => array()
		));

		/** @var $fixture t3lib_DataStructure_Tca */
		$fixture = $this->getMock('t3lib_DataStructure_Tca', array('typeExists'), array($mockedTca));
		$fixture->expects($this->any())->method('typeExists')->will($this->returnValue(FALSE));

		$obj1 = $fixture->getTypeConfiguration(1);
		$obj2 = $fixture->getTypeConfiguration(1);

		$this->assertSame($obj1, $obj2);
	}


	/********************************************
	 * Widget block handling
	 ********************************************/

	protected function prepareWidgetBlockFixture() {
		$this->tcaFixture = array(
			'widgetBlocks' => array(
				'blockOne' => array(
					'widgetConfiguration' => '{"foo": "bar"}'
				),
				'blockTwo' => array(
					'widgetConfiguration' => array('foo' => 'baz')
				)
			)
		);
		$this->setUpFixture($this->tcaFixture);
	}

	/**
	 * @test
	 * @group widgetBlocks
	 */
	public function widgetBlocksCanBeRetrievedFromDataStructure() {
		$this->prepareWidgetBlockFixture();

		$this->assertTrue($this->fixture->hasWidgetBlock('blockOne'));
		$this->assertTrue($this->fixture->hasWidgetBlock('blockTwo'));

		$this->assertEquals(array('widgetConfiguration' => array('foo' => 'bar')), $this->fixture->getWidgetBlock('blockOne'));
		$this->assertEquals(array('widgetConfiguration' => array('foo' => 'baz')), $this->fixture->getWidgetBlock('blockTwo'));
	}

	/**
	 * @test
	 * @depends widgetBlocksCanBeRetrievedFromDataStructure
	 * @group widgetBlocks
	 */
	public function widgetBlockConfigurationsCanBeRetrievedFrom() {
		$this->prepareWidgetBlockFixture();

		$this->assertEquals(array('foo' => 'bar'), $this->fixture->getWidgetConfigurationForBlock('blockOne'));
		$this->assertEquals(array('foo' => 'baz'), $this->fixture->getWidgetConfigurationForBlock('blockTwo'));
	}


	/********************************************
	 * Showitem string handling
	 ********************************************/

	/**
	 * @test
	 * @group showitemString
	 */
	public function showitemStringWithoutTabsIsConvertedToVbox() {
		$showitemFixture = 'field1, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertTypeShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('vbox', $widgetConfiguration['type']);
	}

	/**
	 * @test
	 * @depends showitemStringWithoutTabsIsConvertedToVbox
	 * @group showitemString
	 */
	public function fieldnamesInShowitemStringAreConvertedToFieldWidgets() {
		$showitemFixture = 'field1, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertTypeShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('field', $widgetConfiguration['items'][0]['type']);
		$this->assertEquals('field', $widgetConfiguration['items'][1]['type']);
	}

	/**
	 * @test
	 * @depends showitemStringWithoutTabsIsConvertedToVbox
	 * @group showitemString
	 */
	public function fieldnamesFromShowitemStringAreSetInWidgetConfiguration() {
		$showitemFixture = 'field1, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertTypeShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('field1', $widgetConfiguration['items'][0]['field']);
		$this->assertEquals('field2', $widgetConfiguration['items'][1]['field']);
	}

	/**
	 * @test
	 * @depends showitemStringWithoutTabsIsConvertedToVbox
	 * @group showitemString
	 */
	public function additionalDataIsRemovedFromFieldnameWhenConvertingShowitemString() {
		$showitemFixture = 'field1;;;';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertTypeShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('field1', $widgetConfiguration['items'][0]['field']);
	}

	/**
	 * @test
	 * @group showitemString
	 */
	public function sheetSeparatorsInShowitemStringAreResolvedToTabpanelAndTabs() {
		$showitemFixture = 'field1, --div--, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertTypeShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('tabpanel', $widgetConfiguration['type']);
		$this->assertEquals('tab', $widgetConfiguration['items'][0]['type']);
		$this->assertEquals('tab', $widgetConfiguration['items'][1]['type']);
	}

	/**
	 * @test
	 * @depends sheetSeparatorsInShowitemStringAreResolvedToTabpanelAndTabs
	 * @group showitemString
	 */
	public function sheetSeparatorsInShowitemStringAreNotAddedAsFields() {
		$showitemFixture = 'field1, --div--, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertTypeShowitemStringToWidgetConfigurationArray($showitemFixture);

		foreach ($widgetConfiguration['items'] as $tab) {
			foreach ($tab['items'] as $item) {
				$this->assertNotEquals('--div--', $item['field']);
			}
		}
	}

	/**
	 * @test
	 * @depends sheetSeparatorsInShowitemStringAreResolvedToTabpanelAndTabs
	 * @group showitemString
	 */
	public function fieldsFromShowitemStringAreStoredOnCorrectTab() {
		$showitemFixture = 'field1, --div--, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertTypeShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('field1', $widgetConfiguration['items'][0]['items'][0]['field']);
		$this->assertEquals('field2', $widgetConfiguration['items'][1]['items'][0]['field']);
	}

	/**
	 * @test
	 * @group showitemString
	 */
	public function palettesFromShowitemStringAreResolvedToBlocks() {
		// TODO implement palette and block handling in DataStructure
		$showitemFixture = '--palette--;;paletteRef';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertTypeShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('paletteRef', $widgetConfiguration['items'][0]['block']);
	}

	/**
	 * @test
	 * @group showitemString
	 */
	public function paletteShowitemStringWithoutLinebreakIsConvertedToHbox() {
		$showitemFixture = 'field1, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertPaletteShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('hbox', $widgetConfiguration['type']);
	}

	/**
	 * @test
	 * @depends paletteShowitemStringWithoutLinebreakIsConvertedToHbox
	 * @group showitemString
	 */
	public function fieldnamesInPaletteShowitemStringAreConvertedToFieldWidgets() {
		$showitemFixture = 'field1, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertPaletteShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('field', $widgetConfiguration['items'][0]['type']);
		$this->assertEquals('field', $widgetConfiguration['items'][1]['type']);
	}

	/**
	 * @test
	 * @depends paletteShowitemStringWithoutLinebreakIsConvertedToHbox
	 * @group showitemString
	 */
	public function fieldsFromPaletteShowitemStringAreStoredInCorrectLine() {
		$showitemFixture = 'field1, --linebreak--, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertPaletteShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('field1', $widgetConfiguration['items'][0]['items'][0]['field']);
		$this->assertEquals('field2', $widgetConfiguration['items'][1]['items'][0]['field']);
	}

	/**
	 * @test
	 * @group showitemString
	 */
	public function paletteLinebreaksAreConvertedToVbox() {
		$showitemFixture = 'field1, --linebreak--, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertPaletteShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('vbox', $widgetConfiguration['type']);
	}

	/**
	 * @test
	 * @depends paletteLinebreaksAreConvertedToVbox
	 * @group showitemString
	 */
	public function paletteLinebreaksAreNotAddedAsFields() {
		$showitemFixture = 'field1, --linebreak--, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertPaletteShowitemStringToWidgetConfigurationArray($showitemFixture);

		foreach ($widgetConfiguration['items'] as $line) {
			foreach ($line['items'] as $item) {
				$this->assertNotEquals('--linebreak--', $item['field']);
			}
		}
	}

	/**
	 * @test
	 * @depends paletteLinebreaksAreConvertedToVbox
	 * @group showitemString
	 */
	public function lineHboxesInPaletteAreWrappedInVbox() {
		$showitemFixture = 'field1, --linebreak--, field2';
		$this->setUpFixture(array());
		$widgetConfiguration = $this->fixture->convertPaletteShowitemStringToWidgetConfigurationArray($showitemFixture);

		$this->assertEquals('hbox', $widgetConfiguration['items'][0]['type']);
		$this->assertEquals('hbox', $widgetConfiguration['items'][1]['type']);
	}
}

?>