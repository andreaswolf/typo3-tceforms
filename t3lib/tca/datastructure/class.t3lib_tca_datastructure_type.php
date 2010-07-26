<?php

/**
 * Definition of a type in a TCA data structure.
 *
 * A type is a collection of fields that should be shown for a certain record type. A good example
 * are the various types for the tt_content table (text, text w/image etc.)
 *
 * This class is instantiated once for each type value.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_TCA_DataStructure_Type {

	/**
	 * The data structure this type belongs to
	 *
	 * @var t3lib_TCA_DataStructure
	 */
	protected $dataStructure;

	/**
	 * The number of this type.
	 *
	 * @var integer
	 */
	protected $typeNum;

	/**
	 * The fields included in this type.
	 *
	 * @var array
	 */
	protected $fieldList = array();

	/**
	 *
	 * @var array
	 */
	protected $sheets = array();

	/**
	 * Name of the field where the column indicating the record subtype is stored
	 *
	 * @var string
	 */
	protected $subtypeValueField;

	/**
	 * The sheet that contains the subtype value field.
	 *
	 * @var t3lib_TCA_DataStructure_Sheet
	 */
	protected $subtypeValueFieldSheet;

	/**
	 * A list of all fields to exclude for the different subtypes
	 *
	 * @var array<string>
	 */
	protected $subtypesExcludeList = array();

	/**
	 * A list of all fields to add to the different subtypes
	 *
	 * @var array<string>
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
	 * @var array<string>
	 */
	protected $bitmaskExcludelistBits = array();

	/**
	 * The list of fields to display for this type. This value misses all calculations done for subtypes
	 * and bitmasks!
	 *
	 * @var string
	 */
	protected $showitemString;

	/**
	 *
	 * @param t3lib_TCA_DataStructure $dataStructure The data structure this type belongs to
	 * @param integer $typeNum The number of this type
	 * @param integer $subtypeNum the subtype number of this type
	 * @param array $configuration The configuration array as defined by TCA; don't set this if you set $sheets
	 * @param array $sheets Already preconfigured sheet definitions. Use this if you get sheet information from your source
	 *
	 * @return void
	 *
	 * TODO create some kind of cache here (or let the data structure object do the caching)
	 */
	protected function __construct(t3lib_TCA_DataStructure $dataStructure, $typeNum) {
		$this->dataStructure = $dataStructure;
		$this->typeNum = $typeNum;
	}

	/**
	 * Handles construction from a complex TCA configuration array (with subarrays ctrl, columns, types etc.)
	 *
	 * @param t3lib_TCA_DataStructure $dataStructure
	 * @param unknown_type $typeNum
	 * @param array $configuration
	 */
	public static function createFromConfiguration(t3lib_TCA_DataStructure $dataStructure, $typeNum, array $configuration) {
		$obj = new t3lib_TCA_DataStructure_Type($dataStructure, $typeNum);
		$obj->resolveConfiguration($configuration);

		return $obj;
	}

	/**
	 * Handles construction from a simple array of sheets containing fields. This will be used for e.g. FlexForm data structures
	 *
	 * @param t3lib_TCA_DataStructure $dataStructure
	 * @param integer $typeNum
	 * @param array $sheets
	 */
	public static function createFromSheets(t3lib_TCA_DataStructure $dataStructure, $typeNum, array $sheets) {
		$obj = new t3lib_TCA_DataStructure_Type($dataStructure, $typeNum);
		$obj->resolveSheetsDefinition($sheets);

		return $obj;
	}

	/**
	 * Creates an object structure out of an array describing sheets and their elements
	 *
	 * @param array $sheetDefinition The array containing sheets and their elements
	 * @return void
	 *
	 * TODO document the format expected by this function
	 */
	protected function resolveSheetsDefinition(array $sheetDefinition) {
		$sheetCounter = 0;

		foreach ($sheetDefinition as $sheet) {
			++$sheetCounter;
			$currentSheet = $this->createSheetObject($sheet['title'], $sheet['name']);

			foreach ($sheet['elements'] as $element) {
				$elementObject = $this->createElementObject($element, '',
				  $this->dataStructure->getFieldConfiguration($element));
				$currentSheet->addElement($elementObject);
			}

			$this->sheets[] = $currentSheet;
		}
	}

	/**
	 *
	 *
	 * @param array $configuration The type configuration array from TCA
	 * @return void
	 */
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

		$this->showitemString = $configuration['showitem'];
	}

	protected function createSheetObject($label, $name = '') {
		$sheetObject = new t3lib_TCA_DataStructure_Sheet($label, $name);

		return $sheetObject;
	}

	/**
	 * TODO check if this may be moved to the datastructure class
	 *
	 * @param $name
	 * @param $label
	 * @param $configuration
	 * @param $specialConfiguration
	 * @return t3lib_TCA_DataStructure_Field
	 */
	protected function createElementObject($name, $label, $configuration, $specialConfiguration) {
		$object = new t3lib_TCA_DataStructure_Field($this->dataStructure, $name, $label, $configuration,
		  $specialConfiguration);

		return $object;
	}

	/**
	 *
	 *
	 * @param integer $paletteNumber
	 * @param string $label
	 * @return
	 */
	protected function createPaletteObject($paletteNumber, $label = '') {
		$paletteConfiguration = $this->dataStructure->getPaletteConfiguration($paletteNumber);
		$paletteFieldNames = t3lib_div::trimExplode(',', $paletteConfiguration['showitem']);

		foreach ($paletteFieldNames as $fieldName) {
			$paletteElements[] = $fieldName;
		}

		$paletteObject = new t3lib_TCA_DataStructure_Palette($this->dataStructure, $label, $paletteNumber, $paletteElements);

		return $paletteObject;
	}

	/**
	 * Returns language label from locallang_core.php
	 * Labels must be prefixed with either "l_" or "m_".
	 * The prefix "l_" maps to the prefix "labels." inside locallang_core.php
	 * The prefix "m_" maps to the prefix "mess." inside locallang_core.php
	 *
	 * @param   string  The label key
	 * @return  string  The value of the label, fetched for the current backend language.
	 */
	protected function getLL($str) {
		$content = '';

		switch(substr($str, 0, 2)) {
			case 'l_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.' . substr($str,2));
			break;
			case 'm_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.' . substr($str,2));
			break;
		}
		return $content;
	}

	/**
	 * Returns the sheet definitions for a given subtype. If the record has no subtype, the default sheetset
	 * is returned.
	 *
	 * @param unknown_type $subtypeNumber
	 * @TODO remove parameter and subtype handling
	 */
	public function getSheets($subtypeNumber = '') {
		if (!$this->hasSubtypeValueField() || empty($subtypeNumber)) {
			return $this->sheets;
		}
		if ($this->sheetsForSubtype[$subtypeNumber]) {
			return $this->sheetsForSubtype[$subtypeNumber];
		}

		$sheets = $this->sheets;
		$this->addSubtypeElementsToSheets($sheets, $subtypeNumber);
		$this->removeSubtypeExcludedElementsFromSheets($sheets, $subtypeNumber);

		$this->sheetsForSubtype[$subtypeNumber] = $sheets;

		return $sheets;
	}

	/**
	 * @deprecated
	 */
	protected function addSubtypeElementsToSheets(&$sheets, $subtypeNumber) {
		if (!empty($this->subtypesAddList[$subtypeNumber])) {
			$addElements = t3lib_div::trimExplode(',', $this->subtypesAddList[$subtypeNumber], TRUE);
			$subtypeValueFieldSheet = clone $this->subtypeValueFieldSheet;

			$subtypeValueFieldIndex = $subtypeValueFieldSheet->getElementIndex($this->subtypeValueField);
			$lastElement = $subtypeValueFieldIndex + 1;
			foreach ($addElements as $field) {
				$fieldObject = $this->createElementObject($field, '', $this->dataStructure->getFieldConfiguration($field));
				$subtypeValueFieldSheet->addElement($fieldObject, $lastElement);
				++$lastElement;
			}

			$sheets[array_search($this->subtypeValueFieldSheet, $sheets)] = $subtypeValueFieldSheet;
		}
	}

	/**
	 * @deprecated
	 */
	protected function removeSubtypeExcludedElementsFromSheets(&$sheets, $subtypeNumber) {
		if (!empty($this->subtypesExcludeList[$subtypeNumber])) {
			$excludeElements = t3lib_div::trimExplode(',', $this->subtypesExcludeList[$subtypeNumber], TRUE);

			foreach ($sheets as &$sheet) {
				foreach ($sheet->getElements() as $element) {
					if (in_array($element->getName(), $excludeElements)) {
						$sheet->removeElement($element);
					}
				}
			}
		}
	}

	public function getShowitemString() {
		return $this->showitemString;
	}

	public function hasSubtypeValueField() {
		return !empty($this->subtypeValueField);
	}

	public function getSubtypeValueField() {
		return $this->subtypeValueField;
	}

	public function getAddListForSubtype($subtypeValue) {
		return (array)t3lib_div::trimExplode(',', $this->subtypesAddList[$subtypeValue]);
	}

	/**
	 * Enter description here ...
	 *
	 * @param string $subtypeValue
	 * @return array
	 */
	public function getExcludeListForSubtype($subtypeValue) {
		return (array)t3lib_div::trimExplode(',', $this->subtypesExcludeList[$subtypeValue]);
	}

	/**
	 * @deprecated
	 */
	public function getFieldListForSubtype($subtype) {
		$fieldList = $this->fieldList;

		$fieldList = array_diff($fieldList, $this->subtypesExcludeList[$subtype]);
		$fieldList = array_merge($fieldList, $this->subtypesAddList[$subtype]);

		return $fieldList;
	}

	public function hasBitmaskValueField() {
		return !empty($this->bitmaskValueField);
	}

	public function getBitmaskValueField() {
		return $this->bitmaskValueField;
	}

	public function getBitmaskExcludeList($bitmaskValue) {
		$excludedList = array();

		foreach ($this->bitmaskExcludelistBits as $bitKey => $fieldList) {
			$bit = substr($bitKey, 1);
			if (t3lib_div::testInt($bit)) {
				$bit = t3lib_div::intInRange($bit, 0, 30);
				if ((substr($bitKey, 0, 1) == '-' && !($sTValue & pow(2, $bit))) ||
				    (substr($bitKey, 0, 1) == '+' &&  ($sTValue & pow(2, $bit)))
				    ) {

					$excludedList = array_merge($excludedList, t3lib_div::trimExplode(',', $fieldList, 1));
				}
			}
		}

		return $excludedList;
	}
}

?>