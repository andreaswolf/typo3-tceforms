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
 * Testcase for the field abstraction class in TCA.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCA_DataStructure_FieldTest extends Tx_Phpunit_TestCase {
	/**
	 * @var t3lib_TCA_DataStructure_Field
	 */
	private $fixture;

	/**
	 * @var string
	 */
	private $fieldName;

	public function setUp() {
		$this->fieldName = uniqid();
		$this->fixture = new t3lib_TCA_DataStructure_Field($this->fieldName);
	}

	protected static $localizationModeFixtures = array(
		'exclude' => array(
			array('l10n_mode' => 'exclude')
		),
		'mergeIfNotBlank' => array(
			array('l10n_mode' => 'mergeIfNotBlank')
		),
		'noCopy' => array(
			array('l10n_mode' => 'noCopy')
		),
		'prefixLangTitle' => array(
			array('l10n_mode' => 'prefixLangTitle')
		)
	);

	/**
	 * Data provider methods
	 */

	public static function validLocalizationModes() {
		return self::$localizationModeFixtures;
	}

	/**
	 * This provider adds an additional field configuration with a random, invalid localization mode, plus it adds a second,
	 * boolean parameter that is TRUE if the l10n mode is valid and otherwise FALSE.
	 *
	 * @static
	 * @return array
	 */
	public static function validLocalizationModesPlusOneInvalid() {
		return array_merge(
			self::$localizationModeFixtures,
			array(
				'invalidOne' => array(
					array('l10n_mode' => uniqid()),
					FALSE
				)
			)
		);
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Field::getName
	 */
	public function fieldNameIsCorrectlyStoredAndReturned() {
		$this->assertEquals($this->fieldName, $this->fixture->getName());
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Field::getLabel
	 */
	public function labelIsProperlyExtractedFromConfiguration() {
		$name = uniqid();
		$configuration = array(
			'label' => uniqid()
		);
		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure');
		$mockedDataStructure->expects($this->once())->method('getFieldConfiguration')->with($name)->will($this->returnValue($configuration));
		$field = new t3lib_TCA_DataStructure_Field($name);
		$field->setDataStructure($mockedDataStructure);

		$this->assertEquals($configuration['label'], $field->getLabel());
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Field::hasConfigurationValue
	 * @covers t3lib_TCA_DataStructure_Field::getConfigurationValue
	 */
	public function configurationValuesAreCorrectlyStoredAndReturned() {
		$configuration = array(
			'key-' . uniqid() => 'value ' . uniqid(),
			'key-' . uniqid() => 'value ' . uniqid()
		);

		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure');
		$mockedDataStructure->expects($this->once())->method('getFieldConfiguration')->will($this->returnValue($configuration));
		$field = new t3lib_TCA_DataStructure_Field(uniqid());
		$field->setDataStructure($mockedDataStructure);

		foreach ($configuration as $name => $value) {
			$this->assertTrue($field->hasConfigurationValue($name));
			$this->assertEquals($value, $field->getConfigurationValue($name));
		}
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Field::hasLocalizationMode
	 */
	public function hasLocalizationModeReturnsFalseIfLanguageFieldInDataStructureIsNotSet() {
		$fieldConfiguration = self::$localizationModeFixtures['exclude'];
		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure', array('hasControlValue', 'getFieldConfiguration'));
		$mockedDataStructure->expects($this->once())->method('getFieldConfiguration')->will($this->returnValue($fieldConfiguration));
		$mockedDataStructure->expects($this->once())->method('hasControlValue')->with('languageField')->will($this->returnValue(FALSE));

		/** @var $mockedField t3lib_TCA_DataStructure_Field */
		$mockedField = $this->getMock('t3lib_TCA_DataStructure_Field', array('hasConfigurationValue'), array($mockedDataStructure, uniqid()));
		$mockedField->expects($this->never())->method('hasConfigurationValue');
		$mockedField->setDataStructure($mockedDataStructure);

		$this->assertFalse($mockedField->hasLocalizationMode());
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Field::hasLocalizationMode
	 */
	public function hasLocalizationModeReturnsFalseIfConfigurationValueIsNotSet() {
		$fieldConfiguration = self::$localizationModeFixtures['exclude'][0];
		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure', array('hasControlValue', 'getFieldConfiguration'));
		$mockedDataStructure->expects($this->once())->method('getFieldConfiguration')->will($this->returnValue($fieldConfiguration));
		$mockedDataStructure->expects($this->once())->method('hasControlValue')->with('languageField')->will($this->returnValue(TRUE));

		/** @var $mockedField t3lib_TCA_DataStructure_Field */
		$mockedField = $this->getMock('t3lib_TCA_DataStructure_Field', array('getConfigurationValue', 'hasConfigurationValue'), array($mockedDataStructure, uniqid()));
		$mockedField->expects($this->atLeastOnce())->method('hasConfigurationValue')->with('l10n_mode')->will($this->returnValue(FALSE));
		$mockedField->expects($this->any())->method('getConfigurationValue')->will($this->returnValue($fieldConfiguration['l10n_mode']));
		$mockedField->setDataStructure($mockedDataStructure);

		$this->assertFalse($mockedField->hasLocalizationMode());
	}

	/**
	 * @test
	 * @dataProvider validLocalizationModesPlusOneInvalid
	 * @covers t3lib_TCA_DataStructure_Field::hasLocalizationMode
	 */
	public function hasLocalizationModeReturnsTrueOnlyForValidLocalizationModes($fieldConfiguration, $localizationModeValidity = TRUE) {
		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure');
		$mockedDataStructure->expects($this->once())->method('getFieldConfiguration')->will($this->returnValue($fieldConfiguration));
		$mockedDataStructure->expects($this->once())->method('hasControlValue')->will($this->returnValue(TRUE));

		/** @var $mockedField t3lib_TCA_DataStructure_Field */
		$mockedField = $this->getMock('t3lib_TCA_DataStructure_Field', array('hasConfigurationValue'), array($mockedDataStructure, uniqid()));
		$mockedField->expects($this->any())->method('hasConfigurationValue')->will($this->returnValue(TRUE));
		$mockedField->setDataStructure($mockedDataStructure);

		$this->assertEquals($localizationModeValidity, $mockedField->hasLocalizationMode());
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Field::getLocalizationMode
	 */
	public function getLocalizationModeReturnsCorrectValueIfLocalizationModeIsPresent() {
		$fieldConfiguration = array(
			'l10n_mode' => uniqid()
		);
		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure');
		$mockedDataStructure->expects($this->once())->method('getFieldConfiguration')->will($this->returnValue($fieldConfiguration));
		/** @var $mockedField t3lib_TCA_DataStructure_Field */
		$mockedField = $this->getMock('t3lib_TCA_DataStructure_Field', array('hasLocalizationMode'), array($mockedDataStructure, uniqid()));
		$mockedField->expects($this->any())->method('hasLocalizationMode')->will($this->returnValue(TRUE));
		$mockedField->setDataStructure($mockedDataStructure);

		$this->assertEquals($fieldConfiguration['l10n_mode'], $mockedField->getLocalizationMode());
	}

	/**
	 * @test
	 */
	public function getLocalizationModeChecksIfLocalizationModeIsPresent() {
		$fieldMock = $this->getMock('t3lib_TCA_DataStructure_Field', array('hasLocalizationMode'), array(), '', FALSE);
		$fieldMock->expects($this->once())->method('hasLocalizationMode');

		$fieldMock->getLocalizationMode();
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Field::getConfiguration
	 */
	public function getConfigurationReturnsStoredConfigurationArray() {
		$configurationArray = array(
			'some' => uniqid(),
			'random' => uniqid(),
			'configuration' => uniqid(),
			'values' => uniqid(),
		);

		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure');
		$mockedDataStructure->expects($this->once())->method('getFieldConfiguration')->will($this->returnValue($configurationArray));
		$this->fixture->setDataStructure($mockedDataStructure);

		$this->assertEquals($configurationArray, $this->fixture->getConfiguration());
	}

	/**
	 * @test
	 */
	public function paletteMethodsCorrectlyHandlePalette() {
		/** @var $fixture t3lib_TCA_DataStructure_Field */
		$fixture = $this->getMock('t3lib_TCA_DataStructure_Field', NULL, array(), '', FALSE);
		/** @var $mockedPalette t3lib_TCA_DataStructure_Palette */
		$mockedPalette = $this->getMock('t3lib_TCA_DataStructure_Palette', array(), array(), '', FALSE);

		$this->assertFalse($fixture->hasPalette());
		$fixture->addPalette($mockedPalette);
		$this->assertTrue($fixture->hasPalette());
		$this->assertSame($mockedPalette, $fixture->getPalette());
	}
}

?>