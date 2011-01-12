<?php

class t3lib_TCA_FlexFormDataStructure extends t3lib_TCA_DataStructure {
	/**
	 * Determines whether localization is enabled or not. This value comes from meta[langDisabled] (inverted, of course)
	 * The default value is FALSE
	 *
	 * @var boolean
	 */
	protected $localizationEnabled = FALSE;

	/**
	 * The localization method for this record. This is determined by the value of meta[langChildren].
	 * The default value is 0.
	 *
	 * value 0 means one record for each language (language codes on language part)  example: [lEN][fieldname][vDEF]
	 * value 1 means all languages in one record  (language codes on value part)     example: [lDEF][fieldname][vEN]
	 *
	 * @var integer
	 */
	protected $localizationMethod;

	public function __construct($TCAinformation) {
		$this->fields = $TCAinformation['columns'];
		$this->control = $TCAinformation['ctrl'];
		$this->palettes = $TCAinformation['palettes'];
		$this->meta = $TCAinformation['meta'];

		$this->localizationEnabled = $this->meta['langDisabled'] ? TRUE : FALSE;
		$this->localizationMethod = $this->meta['langChildren'] ? 1 : 0;

		$typeObject = $this->createTypeObjectFromSheets($TCAinformation['sheets']);
		$this->types["0"] = $typeObject;
		$this->definedTypeValues = array("0");
	}

	public function getDisplayConfigurationForRecord(t3lib_TCEforms_Record $record) {
		$displayConfiguration = t3lib_TCA_DisplayConfiguration::createFromSheets($this, $this->types["0"]->getSheets());

		return $displayConfiguration;
	}

	protected function createTypeObjectFromSheets($sheets) {
		$typeObject = t3lib_TCA_DataStructure_Type::createFromSheets($this, 1, $sheets);

		return $typeObject;
	}

	public function getMetaValue($key) {
		return array_key_exists($key, $this->meta) ? $this->meta[$key] : '';
	}

	/**
	 * @return bool TRUE if localization is enabled.
	 */
	public function isLocalizationEnabled() {
		return $this->localizationEnabled;
	}

	/**
	 * @return int The localization method; 0 for localization on "structure level", 1 for localization on field level
	 */
	public function getLocalizationMethod() {
		return $this->localizationMethod;
	}
}

?>
