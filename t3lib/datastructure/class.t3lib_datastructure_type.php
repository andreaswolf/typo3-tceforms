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
 * Definition of a type in a TCA data structure.
 *
 * A type is a collection of fields that should be shown for a certain record type. A good example
 * are the various types for the tt_content table (text, text w/image etc.)
 *
 * This class is instantiated once for each type value.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_DataStructure_Type {
	/**
	 * The data structure this type belongs to
	 *
	 * @var \t3lib_DataStructure_Tca
	 */
	protected $dataStructure;

	/**
	 * The unique value used in records for this type.
	 *
	 * @var mixed
	 */
	protected $typeValue;

	/**
	 * @var string[]
	 */
	protected $fieldList = array();

	/**
	 * The field that contains the subtype value for this type, if any.
	 *
	 * @var string
	 */
	protected $subtypeValueField = NULL;

	/**
	 * A list of all fields to exclude for the different subtypes
	 *
	 * @var string[]
	 */
	protected $subtypesExcludeList = array();

	/**
	 * A list of all fields to add to the different subtypes
	 *
	 * @var string[]
	 */
	protected $subtypesAddList = array();

	/**
	 * The field the bitmask value is stored in
	 *
	 * @var string
	 */
	protected $bitmaskValueField;

	/**
	 * The list of bits in a bitmask to exclude fields from.
	 *
	 * See TYPO3 Core API, section TCA, subsection "['types'][key] section" for more information
	 *
	 * @var string[]
	 */
	protected $bitmaskExcludelistBits = array();

	/**
	 * The widget configuration for this type
	 *
	 * @var array
	 */
	protected $widgetConfiguration = array();

	/**
	 * Constructor method for this class.
	 *
	 * @param t3lib_DataStructure_Tca $dataStructure The data structure this type belongs to
	 * @param mixed $typeValue The unique value used in records for this type.
	 * @param array $configuration The configuration array for this type. Has to contain some sort of display configuration (showitem string, widget configuration array/string)
	 */
	public function __construct(t3lib_DataStructure_Abstract $dataStructure, $typeValue, array $configuration = array()) {
		$this->dataStructure = $dataStructure;
		$this->typeValue = $typeValue;

		if (!empty($configuration)) {
			$this->resolveConfiguration($configuration);
		}
	}

	/*
	 * TODO remove this method, resolve sheets to widget config in FlexForm resolver instead
	 */
	public static function createFromSheets($sheets) {
		//
	}

	protected function resolveConfiguration(array $configuration) {
		if (isset($configuration['subtype_value_field'])) {
			$this->subtypeValueField = $configuration['subtype_value_field'];
			$this->subtypesExcludeList = $configuration['subtypes_excludelist'];
			$this->subtypesAddList = $configuration['subtypes_addlist'];
		}
		if (array_key_exists('bitmask_value_field', $configuration)) {
			$this->bitmaskValueField = $configuration['bitmask_value_field'];
			$this->bitmaskExcludelistBits = $configuration['bitmask_excludelist_bits'];
		}

		if (isset($configuration['widgetConfiguration'])) {
			$this->widgetConfiguration = t3lib_DataStructure_Tca::parseWidgetConfiguration($configuration['widgetConfiguration']);
		} elseif (isset($configuration['showitem'])) {
			$this->widgetConfiguration = $this->dataStructure->convertTypeShowitemStringToWidgetConfigurationArray($configuration['showitem']);
		}
	}


	/********************************************
	 * Widget configuration
	 ********************************************/

	public function hasWidgetConfiguration() {
		return !empty($this->widgetConfiguration);
	}

	public function getWidgetConfiguration() {
		return $this->widgetConfiguration;
	}

	/********************************************
	 * Subtype handling
	 ********************************************/

	/**
	 * Returns TRUE if this type contains a subtype value field
	 *
	 * @return bool
	 */
	public function hasSubtypeValueField() {
		return !empty($this->subtypeValueField);
	}

	/**
	 * Returns the name of the subtype value field, if any.
	 *
	 * @return string
	 * @see hasSubtypeValueField()
	 */
	public function getSubtypeValueField() {
		return (string)$this->subtypeValueField;
	}

	/**
	 * Returns a list of fields that should be displayed additionally for the given record subtype.
	 *
	 * @param mixed $subtype
	 * @return array
	 */
	public function getAdditionalFieldsForSubtype($subtype) {
		return (array)t3lib_div::trimExplode(',', $this->subtypesAddList[$subtype]);
	}

	/**
	 * Returns a list of fields that should be hidden for the given record subtype
	 *
	 * @param mixed $subtype
	 * @return array
	 */
	public function getExcludedFieldsForSubtype($subtype) {
		return (array)t3lib_div::trimExplode(',', $this->subtypesExcludeList[$subtype]);
	}

	/********************************************
	 * Bitmask handling
	 ********************************************/

	/**
	 * Returns TRUE if the type has a bitmask field by which other fields are excluded from being displayed
	 *
	 * @return bool
	 */
	public function hasBitmaskValueField() {
		return !empty($this->bitmaskValueField);
	}

	/**
	 * Returns the name of the field where the bitmask value is stored in.
	 *
	 * @return string
	 */
	public function getBitmaskValueField() {
		return $this->bitmaskValueField;
	}

	/**
	 * Get a list of fields that should be excluded according to a given bitmask value.
	 *
	 * This method looks at each bit and matches the given value against an array of masks. The entries in the array have
	 * the sign and bit number as key and the comma-separated list of excluded fields as value, like this:
	 * array('+0' => 'foo', '-1' => 'bar')
	 *
	 * @param integer $bitmaskValue The mask to check against
	 * @return array The fields to exclude
	 */
	public function getExcludedFieldsForBitmask($bitmaskValue) {
		$excludedList = array();

		foreach ($this->bitmaskExcludelistBits as $bitKey => $fieldList) {
			$bit = substr($bitKey, 1);
			if (t3lib_div::testInt($bit)) {
				$bit = t3lib_div::intInRange($bit, 0, 30);
				if ( (substr($bitKey, 0, 1) == '-' && !($bitmaskValue & pow(2, $bit)))
				  || (substr($bitKey, 0, 1) == '+' &&  ($bitmaskValue & pow(2, $bit)))
				   ) {

					$excludedList = array_merge($excludedList, t3lib_div::trimExplode(',', $fieldList, 1));
				}
			}
		}

		return $excludedList;
	}
}

?>