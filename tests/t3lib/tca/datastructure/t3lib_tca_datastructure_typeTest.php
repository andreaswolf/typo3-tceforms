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
 * Testcase for the type class in data structures
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCA_DataStructure_TypeTest extends Tx_Phpunit_TestCase {
	/**
	 * @var t3lib_TCA_DataStructure_Type
	 */
	private $fixture;

	/**
	 * @var t3lib_DataStructure_Abstract
	 */
	private $mockedDataStructure;

	private $typeConfigurations = array();

	public function setUp() {
		$this->typeConfigurations = array(
			'subtypeValueField' => array(
				'subtype_value_field' => 'foobar',
				'subtypes_addlist' => array(
					uniqid('subtype-') => uniqid('field-') . ',' . uniqid('field-'),
					0 => uniqid(),
					uniqid('subtype-') => uniqid('field-')
				),
				'subtypes_excludelist' => array(
					uniqid('subtype-') => uniqid('field-') . ',' . uniqid('field-'),
					0 => uniqid(),
					uniqid('subtype-') => uniqid('field-')
				)
			),
			'bitmaskValueField' => array(
				'bitmask_value_field' => 'foo'
			),
			'bitmaskExcludelistBits' => array(
				'bitmask_value_field' => uniqid('field-'),
				'bitmask_excludelist_bits' => array( // + => will be excluded if bit is set, - => will be excluded if it is not set
					'+1' => ''
				)
			),
			'widgetConfigArray' => array(
				'widgetConfiguration' => array(
					'type' => uniqid('type-'),
					'items' => array(
						array(
							'type' => uniqid('type-'),
							'title' => uniqid('title-')
						)
					)
				)
			),
			'widgetConfigJson' => array(
				'widgetConfiguration' => '{
					"type": "foobar",
					"items": [{
						"type": "baz",
						"title": "' . uniqid() . '"
					}]
				}'
			)
		);
	}

	protected function setUpFixtureWithConfiguration(array $configuration, $mockedDataStructure = NULL) {
		// TODO change this mock to t3lib_DataStructure_Abstract as soon as PHPUnit is able to mock concrete methods in abstract classes
		if (!is_object($mockedDataStructure)) {
			$this->mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure');
			$this->fixture = new t3lib_TCA_DataStructure_Type($this->mockedDataStructure, uniqid(), $configuration);
		} else {
			$this->fixture = new t3lib_TCA_DataStructure_Type($mockedDataStructure, uniqid(), $configuration);
		}
	}

	/**
	 * @test
	 */
	public function typeHasNoSubtypeFieldIfItIsNotGivenInConfiguration() {
		$this->setUpFixtureWithConfiguration(array());

		$this->assertFalse($this->fixture->hasSubtypeValueField());
	}

	/**
	 * @test
	 */
	public function typeHasSubtypesIfSubtypeValueFieldIsGivenInConfiguration() {
		$configuration = $this->typeConfigurations['subtypeValueField'];
		$this->setUpFixtureWithConfiguration($configuration);

		$this->assertTrue($this->fixture->hasSubtypeValueField());
		$this->assertEquals($configuration['subtype_value_field'], $this->fixture->getSubtypeValueField());
	}

	/**
	 * @test
	 */
	public function additionalFieldsForSubtypeAreCorrectlyReturnedForDifferentSubtypeValues() {
		$configuration = $this->typeConfigurations['subtypeValueField'];
		$this->setUpFixtureWithConfiguration($configuration);

		foreach ($configuration['subtypes_addlist'] as $subtypeValue => $addList) {
			$this->assertEquals(t3lib_div::trimExplode(',', $addList), $this->fixture->getAdditionalFieldsForSubtype($subtypeValue));
		}
	}

	/**
	 * @test
	 */
	public function excludedFieldsForSubtypeAreCorrectlyReturnedForDifferentSubtypeValues() {
		$configuration = $this->typeConfigurations['subtypeValueField'];
		$this->setUpFixtureWithConfiguration($configuration);

		foreach ($configuration['subtypes_excludelist'] as $subtypeValue => $excludeList) {
			$this->assertEquals(t3lib_div::trimExplode(',', $excludeList), $this->fixture->getExcludedFieldsForSubtype($subtypeValue));
		}
	}

	/**********************************
	 * Bitmask handling
	 **********************************/

	/**
	 * @test
	 */
	public function bitmaskValueFieldIsRecognizedInTypeConfiguration() {
			// first test if there is no bitmask value field in the type if there was none in the config
		$this->setUpFixtureWithConfiguration(array());
		$this->assertFalse($this->fixture->hasBitmaskValueField());

		$configuration = $this->typeConfigurations['bitmaskValueField'];
		$this->setUpFixtureWithConfiguration($configuration);

		$this->assertTrue($this->fixture->hasBitmaskValueField());
		$this->assertEquals($configuration['bitmask_value_field'], $this->fixture->getBitmaskValueField());
	}

	/**
	 * @test
	 */
	public function excludeListForBitIsUsedIfBitIsSetAndArrayEntryHasPlusInFront() {
		$configuration = $this->typeConfigurations['bitmaskValueField'];
		$configuration['bitmask_excludelist_bits'] = array(
			'+1' => 'foo',
			'-1' => 'bar'
		);

		$this->setUpFixtureWithConfiguration($configuration);

		$excludeList = $this->fixture->getExcludedFieldsForBitmask(2);
		$this->assertContains('foo', $excludeList);
		$this->assertNotContains('bar', $excludeList);
	}

	/**
	 * @test
	 */
	public function excludeListForBitIsUsedIfBitIsNotSetAndArrayEntryHasMinusInFront() {
		$configuration = $this->typeConfigurations['bitmaskValueField'];
		$configuration['bitmask_excludelist_bits'] = array(
			'+1' => 'foo',
			'-1' => 'bar'
		);

		$this->setUpFixtureWithConfiguration($configuration);

		$excludeList = $this->fixture->getExcludedFieldsForBitmask(0);
		$this->assertNotContains('foo', $excludeList);
		$this->assertContains('bar', $excludeList);
	}

	/**
	 * @test
	 */
	public function excludeListsForBitmaskAreCorrectlyCombined() {
		$configuration = $this->typeConfigurations['bitmaskValueField'];
		$configuration['bitmask_excludelist_bits'] = array(
			'+1' => 'foo',
			'+2' => 'bar',
			'+3' => 'baz'
		);

		$this->setUpFixtureWithConfiguration($configuration);

		$excludeList = $this->fixture->getExcludedFieldsForBitmask(2 | 4);
		$this->assertContains('foo', $excludeList);
		$this->assertContains('bar', $excludeList);
		$this->assertNotContains('baz', $excludeList);
	}


	/**********************************
	 * Showitem string
	 **********************************/

	/**
	 * @test
	 */
	public function showitemStringIsResolvedToWidgetConfiguration() {
		$configuration = array(
			'showitem' => 'field1, field2'
		);
		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure');
		$mockedDataStructure->expects($this->once())->method('convertTypeShowitemStringToWidgetConfigurationArray')
		  ->with($this->equalTo($configuration['showitem']));
		$this->setUpFixtureWithConfiguration($configuration, $mockedDataStructure);

		$this->fixture->getWidgetConfiguration();
	}


	/**********************************
	 * Widget configurations
	 **********************************/

	/**
	 * @test
	 */
	public function widgetConfigurationInJsonFormatGetsRecognizedAndDecoded() {
		$configuration = $this->typeConfigurations['widgetConfigJson'];
		$this->setUpFixtureWithConfiguration($configuration);

		$this->assertTrue($this->fixture->hasWidgetConfiguration());
	}

	/**
	 * @test
	 * @depends widgetConfigurationInJsonFormatGetsRecognizedAndDecoded
	 */
	public function widgetConfigurationInJsonFormatIsDecodedAsArray() {
		$configuration = $this->typeConfigurations['widgetConfigJson'];
		$this->setUpFixtureWithConfiguration($configuration);

		$this->assertInternalType('array', $this->fixture->getWidgetConfiguration());
		$this->assertEquals(json_decode($configuration['widgetConfiguration'], TRUE), $this->fixture->getWidgetConfiguration());
	}

	/**
	 * @test
	 */
	public function constructorThrowsExceptionIfWidgetConfigurationContainsInvalidJson() {
		$this->setExpectedException('RuntimeException');

		$configuration = array('widgetConfiguration' => 'invalidJSON' . uniqid .'');
		$this->setUpFixtureWithConfiguration($configuration);
	}

	/**
	 * @test
	 */
	public function widgetConfigurationInArrayFormatGetsRecognized() {
		$configuration = $this->typeConfigurations['widgetConfigArray'];
		$this->setUpFixtureWithConfiguration($configuration);

		$this->assertTrue($this->fixture->hasWidgetConfiguration());
		$this->assertEquals($configuration['widgetConfiguration'], $this->fixture->getWidgetConfiguration());
	}

}

?>