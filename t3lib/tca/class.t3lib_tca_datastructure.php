<?php

/**
 * Abstraction class for the data structures used in the TYPO3 Table Configuration Array (TCA).
 *
 * This object is read-only after it has been constructed.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_TCA_DataStructure {
	/**
	 * The definition of the fields this data structure contains
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * The control section of this data structure
	 *
	 * @var array
	 */
	protected $control = array();

	/**
	 * The configuration objects of all defined palettes
	 *
	 * @var array<t3lib_TCA_DataStructure_Palette>
	 */
	protected $palettes = array();

	/**
	 * All type values defined for this data structure.
	 *
	 * Default type is zero
	 *
	 * @var array<string>
	 */
	protected $definedTypeValues = array();

	/**
	 * The raw information on the types for this data structure. See $types for a parsed version.
	 * Parsing is done automatically on access.
	 *
	 * @var array
	 */
	protected $rawTypes = array();

	/**
	 * The different types defined for this data structure.
	 * The array contains an entry for each defined type, with a reference to the type object
	 *
	 * @var array<t3lib_TCA_DataStructure_Type>
	 */
	protected $types = array();

	/**
	 * @var array<t3lib_TCA_DisplayConfiguration>
	 */
	protected $displayConfigurations = array();

	/**
	 *
	 *
	 * @param array $TCAinformation
	 * @return void
	 */
	public function __construct($TCAinformation) {
		$this->fields = $TCAinformation['columns'];
		$this->control = $TCAinformation['ctrl'];
		$this->palettes = $TCAinformation['palettes'];

		$this->rawTypes = $TCAinformation['types'];
		$this->definedTypeValues = array_keys($this->rawTypes);
	}

	public function getDisplayConfigurationForRecord(t3lib_TCEforms_Record $record) {
		// TODO: check if record has a field list (hasFieldList()/getFieldList()) - if yes, create a display
		// config for this
		$fieldAddList = array();
		$subtypeExcludeList = array();
		$bitmaskExcludeList = array();

		if ($this->hasTypeField()) {
			$typeValue = $record->getValue($this->getTypeField());
		} else {
			$typeValue = "1";
		}

		/* @var $typeConfiguration t3lib_TCA_DataStructure_Type */
		$typeConfiguration = $this->getTypeConfiguration($typeValue);

		if ($typeConfiguration->hasSubtypeValueField()) {
			$subtypeValue = $record->getValue($typeConfiguration->getSubtypeValueField());
		}

		if ($typeConfiguration->hasBitmaskValueField()) {
			$bitmaskValue = $record->getValue($typeConfiguration->bitmaskValueField());
		}

		$displayConfigurationHash = md5($typeValue . ';' . $subtypeValue . ';' . $bitmaskValue);
		if (array_key_exists($displayConfigurationHash, $this->displayConfigurations)) {
			return $this->displayConfigurations[$displayConfigurationHash];
		}

		// Create config
		if (isset($subtypeValue)) {
			$subtypeExcludeList = $typeConfiguration->getExcludeListForSubtype($subtypeValue);
			$fieldAddList = $typeConfiguration->getAddListForSubtype($subtypeValue);
		}
		if (isset($bitmaskValue)) {
			$bitmaskExcludeList = $typeConfiguration->getBitmaskExcludeList($bitmaskValue);
		}

		$fieldExcludeList = array_merge($subtypeExcludeList, $bitmaskExcludeList);
		$displayConfiguration = t3lib_TCA_DisplayConfiguration::createFromConfiguration($this, $typeConfiguration, $fieldAddList, $fieldExcludeList);

		$this->displayConfigurations[$displayConfigurationHash] = $displayConfiguration;
		return $displayConfiguration;
	}

	/**
	 *
	 * @param string $key
	 * @return mixed/string
	 *
	 * TODO define access to [ctrl][enablecolumns]
	 * TODO define access to ['EXT']['myext']
	 * TODO define if this function only returns strings or also arrays (connected to previous questions)
	 *
	 */
	public function getControlValue($key) {
		return $this->control[$key];
	}

	public function getFieldNames() {
		return array_keys($this->fields);
	}

	public function getFieldConfiguration($fieldName) {
		return $this->fields[$fieldName];
	}

	/**
	 * Returns the object representation of a TCA field.
	 *
	 * This is only the bare variant of this field, as defined in the TCA columns section. Any special
	 * configuration added in type configurations has to be applied separately
	 *
	 * @param t3lib_TCA_DataStructure_Field $fieldName
	 * @return t3lib_TCA_DataStructure_Field
	 *
	 * @access package
	 *
	 * @TODO add caching
	 */
	public function getFieldObject($fieldName) {
		return new t3lib_TCA_DataStructure_Field($this, $fieldName, $this->getFieldConfiguration($fieldName));
	}

	public function hasField($fieldName) {
		return array_key_exists($fieldName, $this->fields);
	}

	public function getFieldConfigurations() {
		return $this->fields;
	}

	/**
	 * Returns TRUE if a certain palette exists in this datastructure
	 *
	 * @param integer $paletteNumber The number of the palette as used in TCA configuration
	 */
	public function hasPalette($paletteNumber) {
		return array_key_exists($paletteNumber, $this->palettes);
	}

	/**
	 * Returns configuration for a palette
	 *
	 * @param integer $paletteNumber
	 * @return array The palette configuration as specified in TCA
	 */
	public function getPaletteConfiguration($paletteNumber) {
		if (!$this->hasPalette($paletteNumber)) {
			throw new InvalidArgumentException("Palette $paletteNumber does not exist.");
		}
		// TODO create palette

		return $this->palettes[$paletteNumber];
	}

	/**
	 * Returns a value from the interface definition part of the TCA
	 *
	 * @param string $key
	 * @return string
	 */
	public function getInterfaceValue($key) {
		//
	}

	/**
	 * Returns TRUE if a field for differentiating between different types of the record exists
	 *
	 * @return boolean
	 */
	public function hasTypeField() {
		return (array_key_exists('type', $this->control) && $this->control['type'] !== '');
	}

	/**
	 * Returns the fieldname of the type field.
	 *
	 * @return string
	 */
	public function getTypeField() {
		return $this->control['type'];
	}

	public function typeExists($typeNumber) {
		return in_array($typeNumber, $this->definedTypeValues);
	}

	/**
	 * Returns the configuration for a record type.
	 *
	 * These are defined in the [types]-section of the TCA.
	 *
	 * @param string/integer $typeNum
	 * @return array
	 */
	public function getTypeConfiguration($typeNum = 0) {
			// See "TYPO3 Core APIs, section "$TCA array reference", subsection "['types'][key] section"
		if (!in_array($typeNum, $this->getPossibleTypeValues())) {
			$typeNum = 1;
		}

		if (!array_key_exists($typeNum, $this->types)) {
			$this->createTypeObject($typeNum);
		}

		return $this->types[$typeNum];
	}

	public function getPossibleTypeValues() {
		return $this->definedTypeValues;
	}

	protected function createTypeObject($typeNum) {
		$this->types[$typeNum] = t3lib_TCA_DataStructure_Type::createFromConfiguration($this, $typeNum, $this->rawTypes[$typeNum]);
	}
}

?>