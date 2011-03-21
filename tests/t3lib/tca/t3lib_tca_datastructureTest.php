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
class t3lib_TCA_DataStructureTest extends Tx_Phpunit_TestCase {
	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure::hasControlValue
	 * @covers t3lib_TCA_DataStructure::getControlValue
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

		$fixture = new t3lib_TCA_DataStructure($TcaFixture);

		foreach ($TcaFixture['ctrl'] as $key => $value) {
			$this->assertTrue($fixture->hasControlValue($key));
			$this->assertEquals($value, $fixture->getControlValue($key));
		}

		$this->assertFalse($fixture->hasControlValue(uniqid()));
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure::getFieldObject
	 */
	public function getFieldObjectReturnsProperFieldObject() {
		/** @var $mockedField t3lib_TCA_DataStructure_Field */
		$mockedField = $this->getMock('t3lib_TCA_DataStructure_Field', array(), array(), '', FALSE);
		t3lib_div::addInstance('t3lib_TCA_DataStructure_Field', $mockedField);

		/** @var $fixture t3lib_TCA_DataStructure */
		$fixture = $this->getMock('t3lib_TCA_DataStructure', NULL, array(), '', FALSE);

		$this->assertSame($mockedField, $fixture->getFieldObject(uniqid()));
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure::getFieldObject
	 */
	public function getFieldObjectCachesObjects() {
		/** @var $mockedField t3lib_TCA_DataStructure_Field */
		$mockedField = $this->getMock('t3lib_TCA_DataStructure_Field', array(), array(), '', FALSE);
		t3lib_div::addInstance('t3lib_TCA_DataStructure_Field', $mockedField);
		$fieldName1 = uniqid();

		/** @var $mockedField t3lib_TCA_DataStructure_Field */
		$mockedField2 = $this->getMock('t3lib_TCA_DataStructure_Field', array(), array(), '', FALSE);
		t3lib_div::addInstance('t3lib_TCA_DataStructure_Field', $mockedField2);
		$fieldName2 = uniqid();

		/** @var $fixture t3lib_TCA_DataStructure */
		$fixture = $this->getMock('t3lib_TCA_DataStructure', NULL, array(), '', FALSE);

		$obj1 = $fixture->getFieldObject($fieldName1);
		$obj2 = $fixture->getFieldObject($fieldName1);
		$obj3 = $fixture->getFieldObject($fieldName2);

		$this->assertSame($mockedField, $obj1);
		$this->assertSame($obj1, $obj2);
		$this->assertNotSame($obj1, $obj3);
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure::hasTypeField
	 * @covers t3lib_TCA_DataStructure::getTypeField
	 */
	public function typeFieldIsCorrectlyHandled() {
		$TcaFixture = array(
			'ctrl' => array(
				'type' => uniqid()
			)
		);

		$fixture = new t3lib_TCA_DataStructure($TcaFixture);

		$this->assertTrue($fixture->hasTypeField());
		$this->assertEquals($TcaFixture['ctrl']['type'], $fixture->getTypeField());
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure::hasLanguageField
	 * @covers t3lib_TCA_DataStructure::getLanguageField
	 */
	public function languageFieldIsCorrectlyHandled() {
		$TcaFixture = array(
			'ctrl' => array(
				'languageField' => uniqid()
			)
		);

		$fixture = new t3lib_TCA_DataStructure($TcaFixture);

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

		$fixture = new t3lib_TCA_DataStructure($TcaFixture);

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

		$fixture = new t3lib_TCA_DataStructure($TcaFixture);

		$fieldConfigurations = $fixture->getFieldConfigurations();
		foreach ($TcaFixture['columns'] as $fieldName => $configuration) {
			$this->assertArrayHasKey($fieldName, $fieldConfigurations);
			$this->assertEquals($configuration, $fieldConfigurations[$fieldName]);
			$this->assertEquals($configuration, $fixture->getFieldConfiguration($fieldName));
		}
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure::getTypeConfiguration
	 */
	public function getTypeConfigurationFallsBackToDefaultTypeNumberForInvalidTypeNumbers() {
		/** @var $fixture t3lib_TCA_DataStructure */
		$fixture = $this->getMock('t3lib_TCA_DataStructure', array('typeExists', 'createTypeObject'), array(), '', FALSE);
		$fixture->expects($this->once())->method('typeExists')->will($this->returnValue(FALSE));
		$fixture->expects($this->once())->method('createTypeObject')->with($this->equalTo(1));

		$fixture->getTypeConfiguration(0);
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure::getTypeConfiguration
	 * @covers t3lib_TCA_DataStructure::createTypeObject
	 */
	public function getTypeConfiurationCachesCreatedObjects() {
		$mockedType = $this->getMock('t3lib_TCA_DataStructure_Type', array(), array(), '', FALSE);
		t3lib_div::addInstance('t3lib_TCA_DataStructure_Type', $mockedType);

		$mockedTca = array('types' => array(
			'1' => array()
		));

		/** @var $fixture t3lib_TCA_DataStructure */
		$fixture = $this->getMock('t3lib_TCA_DataStructure', array('typeExists'), array($mockedTca));
		$fixture->expects($this->any())->method('typeExists')->will($this->returnValue(FALSE));

		$obj1 = $fixture->getTypeConfiguration(1);
		$obj2 = $fixture->getTypeConfiguration(1);

		$this->assertSame($obj1, $obj2);
	}
}

?>