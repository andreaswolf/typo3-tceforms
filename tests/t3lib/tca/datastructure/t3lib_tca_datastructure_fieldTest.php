<?php


/**
 * Testcase for the field abstraction class in TCA.
 */
class t3lib_tca_datastructure_fieldTest extends tx_phpunit_testcase {

	protected static $localizationModeFixtures = array(
		'exclude' => array(
			array(array('l10n_mode' => 'exclude'))
		),
		'mergeIfNotBlank' => array(
			array(array('l10n_mode' => 'mergeIfNotBlank'))
		),
		'noCopy' => array(
			array(array('l10n_mode' => 'noCopy'))
		),
		'prefixLangTitle' => array(
			array(array('l10n_mode' => 'prefixLangTitle'))
		)
	);

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
		$field = new t3lib_TCA_DataStructure_Field($mockedDataStructure, uniqid(), $configuration);

		foreach ($configuration as $name => $value) {
			$this->assertTrue($field->hasConfigurationValue($name));
			$this->assertEquals($value, $field->getConfigurationValue($name));
		}
	}

	/**
	 * @test
	 */
	public function localizationModeIsNotReturnedIfLanguageFieldIsNotSetInDataStructure() {
		$fieldConfiguration = self::$localizationModeFixtures['exclude'];
		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure', array('hasControlValue'));
		$mockedDataStructure->expects($this->any())->method('hasControlValue')->with('languageField')->will($this->returnValue(FALSE));
		$field = new t3lib_TCA_DataStructure_Field($mockedDataStructure, uniqid(), $fieldConfiguration);

		$this->assertEquals('', $field->getLocalizationMode());
	}

	/**
	 * @test
	 * @dataProvider validLocalizationModes
	 */
	public function getLocalizationModeReturnsAllValidValues($fieldConfiguration) {
		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure', array('hasControlValue'));
		$mockedDataStructure->expects($this->any())->method('hasControlValue')->with('languageField')->will($this->returnValue(TRUE));
		$field = new t3lib_TCA_DataStructure_Field($mockedDataStructure, uniqid(), $fieldConfiguration);

		$this->assertEquals($fieldConfiguration['l10n_mode'], $field->getLocalizationMode());
	}

	public static function validLocalizationModes() {
		return self::$localizationModeFixtures;
	}

	/**
	 * @test
	 */
	public function invalidLocalizationModesAreNotReturned() {
		$fieldConfiguration = array('l10n_mode' => 'invalidValue');
		$mockedDataStructure = $this->getMock('t3lib_TCA_DataStructure', array('hasControlValue'));
		$mockedDataStructure->expects($this->any())->method('hasControlValue')->with('languageField')->will($this->returnValue(TRUE));
		$field = new t3lib_TCA_DataStructure_Field($mockedDataStructure, uniqid(), $fieldConfiguration);

		$this->assertEquals('', $field->getLocalizationMode());
	}
}

?>