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

	public function __construct($TCAinformation) {
		$this->fields = $TCAinformation['columns'];
		$this->control = $TCAinformation['ctrl'];
		$this->sheets = $TCAinformation['sheets'];
	}

	/**
	 *
	 * @param string $key
	 * @return mixed/string
	 *
	 * @TODO define access to [ctrl][enablecolumns]
	 * @TODO define access to ['EXT']['myext']
	 * @TODO define if this function only returns strings or also arrays (connected to previous questions)
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
	 * Returns the configuration for a record subtype.
	 *
	 * These are defined in the [types]-section of the TCA.
	 * This method returns the "unfiltered" information from the TCA. See .... for methods
	 * that return more usable and structured information.
	 *
	 * @param string/integer $typeNum
	 * @return array
	 */
	public function getTypeConfiguration($typeKey) {
		//
	}
}

?>