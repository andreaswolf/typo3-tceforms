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
 * Abstract data structure class
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
abstract class t3lib_DataStructure_Abstract {

	/**
	 * An identifier for this data structure.
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * The control section of this data structure
	 * This holds information from e.g. $TCA[$tableName]['ctrl']
	 *
	 * @var array
	 */
	protected $control = array();

	/**
	 * The definition of the fields this data structure contains
	 * This holds information from e.g. $TCA[$tableName]['columns']
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Cache for field objects created by getFieldObject()
	 *
	 * @var t3lib_DataStructure_Element_Field[]
	 */
	protected $fieldObjects = array();

	/**
	 * The different types defined for this data structure.
	 * The array contains an entry for each defined type, with a reference to the type object
     * This holds information from e.g. $TCA[$tableName]['types']
	 *
	 * @var t3lib_DataStructure_Type[]
	 */
	protected $types = array();

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

	/**
	 * Returns true if the data structure has a field called $fieldName
	 *
	 * @param string $fieldName
	 * @return bool
	 */
	public function hasField($fieldName) {
		return array_key_exists($fieldName, $this->fields);
	}

	/**
	 * Returns the names of all fields in this data structure
	 *
	 * @return array
	 */
	public function getFieldNames() {
		return array_keys($this->fields);
	}

	/**
	 * Returns the configuration object for a field
	 *
	 * @param string $fieldName
	 * @return array
	 */
	public function getFieldConfiguration($fieldName) {
		return $this->fields[$fieldName];
	}

	/**
	 * Returns the raw configuration of all fields
	 *
	 * @return array
	 */
	public function getFieldConfigurations() {
		return $this->fields;
	}

	/**
	 * Returns the object representation of a field.
	 *
	 * Note however that this is only the bare configuration as specified in the data structure definition.
	 * Any special configuration added by types is not considered; look at the type object for these field definitions
	 *
	 * @param string $fieldName
	 * @return t3lib_DataStructure_Element_Field
	 *
	 * @access package
	 */
	public function getFieldObject($fieldName) {
		if (!$this->fieldObjects[$fieldName]) {
			/** @var $fieldObject t3lib_DataStructure_Element_Field */
			$fieldObject = t3lib_div::makeInstance('t3lib_DataStructure_Element_Field', $fieldName);
			$fieldObject->setDataStructure($this);

			$this->fieldObjects[$fieldName] = $fieldObject;
		}

		return $this->fieldObjects[$fieldName];
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

	/**
	 * Returns TRUE if the given type value exists.
	 *
	 * @param  $typeValue
	 * @return bool
	 */
	public function typeExists($typeValue) {
		return in_array($typeValue, $this->definedTypeValues);
	}

	/**
	 * Returns the configuration for a record type.
	 *
	 * These are defined in the [types]-section of the TCA.
	 *
	 * @param string/integer $typeNum
	 * @return array
	 */
	public function getTypeConfiguration($typeValue = '0') {
			// See "TYPO3 Core APIs, section "$TCA array reference", subsection "['types'][key] section"
		if (!$this->typeExists($typeValue)) {
			$typeValue = 1;
		}

		if (!array_key_exists($typeValue, $this->types)) {
			$this->createTypeObject($typeValue);
		}

		return $this->types[$typeValue];
	}

	abstract protected function createTypeObject($typeValue);

	/**
	 * Returns the identifier for this data structure; this might be a table name or some arbitrary unique string.
	 *
	 * @abstract
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

}

?>