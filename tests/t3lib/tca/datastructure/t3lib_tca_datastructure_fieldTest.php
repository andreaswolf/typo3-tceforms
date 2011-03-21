<?php


/**
 * Testcase for the field abstraction class in TCA.
 */
class t3lib_tca_datastructure_fieldTest extends tx_phpunit_testcase {

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
	 */
	public function fieldNameIsCorrectlyStoredAndReturned() {
		$name = uniqid();
		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure');
		$field = new t3lib_TCA_DataStructure_Field($mockedDataStructure, $name, array());

		$this->assertEquals($name, $field->getName());
	}

	/**
	 * @test
	 */
	public function configurationValuesAreCorrectlyStoredAndReturned() {
		$configuration = array(
			'key-' . uniqid() => 'value ' . uniqid(),
			'key-' . uniqid() => 'value ' . uniqid()
		);

		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure');
		$mockedDataStructure->expects($this->once())->method('getFieldConfiguration')->will($this->returnValue($configuration));
		$field = new t3lib_TCA_DataStructure_Field($mockedDataStructure, uniqid());

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
		$mockedDataStructure->expects($this->once())->method('hasControlValue')->with('languageField')->will($this->returnValue(FALSE));
		$mockedDataStructure->expects($this->once())->method('getFieldConfiguration')->will($this->returnValue($fieldConfiguration));

		/** @var $mockedField t3lib_TCA_DataStructure_Field */
		$mockedField = $this->getMock('t3lib_TCA_DataStructure_Field', array('hasConfigurationValue'), array($mockedDataStructure, uniqid()));
		$mockedField->expects($this->never())->method('hasConfigurationValue');

		$this->assertFalse($mockedField->hasLocalizationMode());
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Field::hasLocalizationMode
	 */
	public function hasLocalizationModeReturnsFalseIfConfigurationValueIsNotSet() {
		$fieldConfiguration = self::$localizationModeFixtures['exclude'][0];
		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure', array('hasControlValue', 'getFieldConfiguration'));
		$mockedDataStructure->expects($this->once())->method('hasControlValue')->with('languageField')->will($this->returnValue(TRUE));
		$mockedDataStructure->expects($this->once())->method('getFieldConfiguration')->will($this->returnValue($fieldConfiguration));

		/** @var $mockedField t3lib_TCA_DataStructure_Field */
		$mockedField = $this->getMock('t3lib_TCA_DataStructure_Field', array('getConfigurationValue', 'hasConfigurationValue'), array($mockedDataStructure, uniqid()));
		$mockedField->expects($this->atLeastOnce())->method('hasConfigurationValue')->with('l10n_mode')->will($this->returnValue(FALSE));
		$mockedField->expects($this->any())->method('getConfigurationValue')->will($this->returnValue($fieldConfiguration['l10n_mode']));

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
}

?>