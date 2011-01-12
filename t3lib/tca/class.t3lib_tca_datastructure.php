<?php

/**
 * Abstraction class for the data structures used in the TYPO3 Table Configuration Array (TCA).
 *
 * This object is read-only after it has been constructed.
 *
 * @see Document "TYPO3 Core API", section "$TCA array reference"
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_TCA_DataStructure {
	/**
	 * The definition of the fields this data structure contains
     * This holds information about e.g. $TCA[$tableName]['columns']
	 *
	 * @var array<t3lib_TCA_DataStructure_Field>
	 */
	protected $fields = array();

	/**
	 * The control section of this data structure
     * This holds information from e.g. $TCA[$tableName]['ctrl']
	 *
	 * @var array
	 */
	protected $control = array();

	/**
	 * The configuration objects of all defined palettes
     * This holds information from e.g. $TCA[$tableName]['palettes']
	 *
	 * @var array<t3lib_TCA_DataStructure_Palette>
	 */
	protected $palettes = array();

	/**
	 * All type values defined for this data structure.
     * This holds information from e.g. $TCA[$tableName]['types']
	 *
	 * Default type is zero
	 *
	 * @var array<string>
	 */
	protected $definedTypeValues = array();

	/**
	 * The raw information on the types for this data structure. See $types for a parsed version.
	 * Parsing is done automatically on access.
     * This holds information from e.g. $TCA[$tableName]['types']
	 *
	 * @var array
	 */
	protected $rawTypes = array();

	/**
	 * The different types defined for this data structure.
	 * The array contains an entry for each defined type, with a reference to the type object
     * This holds information from e.g. $TCA[$tableName]['types']
	 *
	 * @var array<t3lib_TCA_DataStructure_Type>
	 */
	protected $types = array();

	/**
	 * @var array<t3lib_TCA_DisplayConfiguration>
	 */
	protected $displayConfigurations = array();

	/**
	 * Constructor for this class.
	 *
	 * Expects a TCA configuration as used in the normal PHP-based TCA. It has to have these sections: ctrl, columns,
	 * palettes, types; each should be an array as used in the TCA.
	 *
	 * @param array $TCAinformation The information from the Table Control Array
	 * @return void
	 */
	public function __construct($TCAinformation) {
		$this->fields = $TCAinformation['columns'];
		$this->control = $TCAinformation['ctrl'];
		$this->palettes = $TCAinformation['palettes'];

		$this->rawTypes = $TCAinformation['types'];
		$this->definedTypeValues = array_keys($this->rawTypes);
	}

	public function getDisplayConfigurationForRecord(t3lib_TCEforms_Record $record, array $fieldList = NULL) {
		// TODO: check if record has a field list (hasFieldList()/getFieldList()) - if yes, create a display
		// config for this
		$fieldAddList = array();
		$subtypeExcludeList = array();
		$bitmaskExcludeList = array();

		if ($this->hasTypeField() && !$record->isNew()) {
			$typeValue = $record->getValue($this->getTypeField());

			if (!$this->typeExists($typeValue)) {
				$typeValue = "1";
			}
		} else {
			$typeValue = $this->getDefaultTypeForRecord($record);
		}

		/** @var $typeConfiguration t3lib_TCA_DataStructure_Type */
		$typeConfiguration = $this->getTypeConfiguration($typeValue);

		if ($typeConfiguration->hasSubtypeValueField()) {
			$subtypeValue = $record->getValue($typeConfiguration->getSubtypeValueField());
		}

		if ($typeConfiguration->hasBitmaskValueField()) {
			$bitmaskValue = $record->getValue($typeConfiguration->bitmaskValueField());
		}

		$displayConfigurationHash = md5($typeValue . ';' . $subtypeValue . ';' . $bitmaskValue . ';' . implode(',', (array)$fieldList));
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

		$displayConfiguration = t3lib_TCA_DisplayConfiguration::createFromConfiguration($this, $typeConfiguration, $fieldAddList, $fieldExcludeList, $fieldList);

		$this->displayConfigurations[$displayConfigurationHash] = $displayConfiguration;
		//print_r($displayConfiguration);
		return $displayConfiguration;
	}

	/**
	 * Returns TRUE if a key inside the control section ($TCA[$table]['ctrl']) exists.
	 *
	 * @param string $key The key to check
	 * @return bool
	 */
	public function hasControlValue($key) {
		return array_key_exists($key, $this->control);
	}

	/**
	 * Returns the value from an entry in the control section ($TCA[$table]['ctrl']).
	 *
	 * @param string $key The key to get
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

	/**
	 * Returns the configuration object for a field
	 *
	 * @param string $fieldName
	 * @return t3lib_TCA_DataStructure_Field
	 */
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
	 * @param integer $paletteName The name of the palette as used in TCA configuration
	 */
	public function hasPalette($paletteName) {
		return array_key_exists($paletteName, $this->palettes);
	}

	/**
	 * Returns configuration for a palette
	 *
	 * @param   integer  $paletteName  The name of the palette as used in TCA configuration
	 * @return  array  The palette configuration as specified in TCA
	 */
	public function getPaletteConfiguration($paletteName) {
		if (!$this->hasPalette($paletteName)) {
			throw new InvalidArgumentException("Palette '$paletteName' does not exist.");
		}
		// TODO create palette

		return $this->palettes[$paletteName];
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

	public function hasLanguageField() {
		return $this->hasControlValue('languageField');
	}

	public function getLanguageField() {
		return $this->getControlValue('languageField');
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
	public function getTypeConfiguration($typeNumber = '0') {
			// See "TYPO3 Core APIs, section "$TCA array reference", subsection "['types'][key] section"
		if (!$this->typeExists($typeNumber)) {
			$typeNumber = 1;
		}

		if (!array_key_exists($typeNumber, $this->types)) {
			$this->createTypeObject($typeNumber);
		}

		return $this->types[$typeNumber];
	}

	/**
	 * Returns the default type number for a record.
	 *
	 * If a type field exists, a default value may be set. If it is not or there is no type field,
	 * the default value is always '0'.
	 *
	 * @param t3lib_TCEforms_Record $record
	 * @return mixed The default type value for the record
	 */
	protected function getDefaultTypeForRecord(t3lib_TCEforms_Record $record) {
		if ($this->hasTypeField()) {
			$typeFieldConfiguration = $this->getFieldConfiguration($this->getTypeField());
			if (array_key_exists('default', $typeFieldConfiguration['config'])) {
				return $typeFieldConfiguration['config']['default'];
			}
		}

		return '0';
	}

	public function getPossibleTypeValues() {
		return $this->definedTypeValues;
	}

	protected function createTypeObject($typeNum) {
		$this->types[$typeNum] = t3lib_TCA_DataStructure_Type::createFromConfiguration($this, $typeNum, $this->rawTypes[$typeNum]);
	}
}

?>
