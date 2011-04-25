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
class t3lib_TCA_DataStructure extends t3lib_DataStructure_Abstract {
	/**
	 * Cache for field objects created by getFieldObject
	 *
	 * @var t3lib_TCA_DataStructure[]
	 */
	protected $fieldObjects = array();

	/**
	 * The configuration objects of all defined palettes
     * This holds information from e.g. $TCA[$tableName]['palettes']
	 *
	 * @var array<t3lib_TCA_DataStructure_Palette>
	 */
	protected $palettes = array();

	/**
	 * The raw information on the types for this data structure. See $types for a parsed version.
	 * Parsing is done automatically on access.
     * This holds information from e.g. $TCA[$tableName]['types']
	 *
	 * @var array
	 */
	protected $rawTypes = array();

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
	 * Returns the object representation of a TCA field.
	 *
	 * This is only the bare variant of this field, as defined in the TCA columns section. Any special
	 * configuration added in type configurations has to be applied separately
	 *
	 * @param string $fieldName
	 * @return t3lib_TCA_DataStructure_Field
	 *
	 * @access package
	 *
	 * @TODO add caching
	 */
	public function getFieldObject($fieldName) {
		if (!$this->fieldObjects[$fieldName]) {
			/** @var $fieldObject t3lib_TCA_DataStructure_Field */
			$fieldObject = t3lib_div::makeInstance('t3lib_TCA_DataStructure_Field', $fieldName);
			$fieldObject->setDataStructure($this);

			$this->fieldObjects[$fieldName] = $fieldObject;
		}

		return $this->fieldObjects[$fieldName];
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

	protected function createTypeObject($typeValue) {
		$this->types[$typeValue] = t3lib_div::makeInstance('t3lib_TCA_DataStructure_Type', $this, $typeValue, $this->rawTypes[$typeValue]);
	}

	public function createElementObject($name, $label, $specialConfiguration) {
		$object = new t3lib_TCA_DataStructure_Field($this, $name);

		return $object;
	}
}

?>
