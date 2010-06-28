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

	protected $palettes = array();

	/**
	 * The sheets defined in this data structure. This contains a multi-dimensional array like this:
	 * sheet1 => array(name, title, elements => array())
	 *
	 * @var array
	 * TODO define how to store the styling and grouping information of elements
	 */
	protected $sheets;

	/**
	 * The raw information on the types for this data structure. See $types for a parsed version.
	 * Parsing is done automatically on access.
	 *
	 * @var array
	 */
	protected $rawTypes = array();

	/**
	 * The different types defined for this data structure.
	 * The array contains a subarray for each defined type, which contains the sheet definitions for the type
	 *
	 * @var array
	 */
	protected $types = array();

	protected $definedTypeValues = array();

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

	public function hasField($fieldName) {
		return array_key_exists($fieldName, $this->fields);
	}

	public function getFieldConfigurations() {
		return $this->fields;
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