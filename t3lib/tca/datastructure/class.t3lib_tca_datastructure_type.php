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
	 * @var array
	 */
	protected $subtypesExcludeList;

	/**
	 * A list of all fields to add to the different subtypes
	 *
	 * @var array
	 */
	protected $subtypesAddList;

	/**
	 *
	 *
	 * @see $sheets
	 * @var array
	 */
	protected $sheetsForSubtypes = array();

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
	public function __construct(t3lib_TCA_DataStructure $dataStructure, $typeNum, array $configuration = array(), array $sheets = array()) {
		$this->dataStructure = $dataStructure;
		$this->typeNum = $typeNum;

		if (!empty($sheets)) {
			$this->resolveSheetsDefinition($sheets);
		}
		if (!empty($configuration)) {
			$this->resolveConfiguration($configuration);
		}
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

		// TODO store styling information from the showitem subarray
		$fields = $configuration['showitem'];

		$fieldList = t3lib_div::trimExplode(',', $fields);
		$sheetCounter = 0;

		if (isset($fieldList[0]) && strpos($fieldList[0], '--div--') !== 0) {
			++$sheetCounter;
			$currentSheet = $this->createSheetObject($this->getLL('l_generalTab'));
			$this->sheets[] = $currentSheet;
		}

		foreach ($fieldList as $fieldInfo) {
			// Exploding subparts of the field configuration:
			$parts = explode(';', $fieldInfo);

			$theField = $parts[0];

			if ($theField == '--div--') {
				++$sheetCounter;

				$currentSheet = $this->createSheetObject($GLOBALS['LANG']->sL($parts[1]));
				$this->sheets[] = $currentSheet;
			} else {
				if ($theField !== '') {
					if ($theField == '--palette--') {
						t3lib_div::devLog('Adding palette element in data structure for type ' . $this->typeNum . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

						// TODO create object for palette here
						//$formFieldObject = $this->createPaletteElement($parts[2], $GLOBALS['LANG']->sL($parts[1]));
						$elementObject = array(
							'type' => 'palette',
							'palette' => $parts[2],
							'label' => $GLOBALS['LANG']->sL($parts[1])
						);
					} elseif ($this->dataStructure->hasField($theField)) {
						t3lib_div::devLog('Adding standard element for field "' . $theField . '" in type ' . $this->typeNum . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

						$elementObject = $this->createElementObject($theField, $GLOBALS['LANG']->sL($parts[1]),
						  $this->dataStructure->getFieldConfiguration($theField), $parts[3]);
					} else {
						// if this is no field, just continue with the next entry in the field list.
						continue;
					}

					$this->fieldList[] = $theField;
					$currentSheet->addElement($elementObject);

					if ($this->hasSubtypeValueField() && $this->subtypeValueField == $theField) {
						$this->subtypeValueFieldSheet = $currentSheet;
					}
				}

				// Getting the style information out:
				// TODO: Make this really object oriented
				if (isset($parts[4])) {
					$color_style_parts = t3lib_div::trimExplode('-',$parts[4]);
				} else {
					$color_style_parts = array();
				}
				if (strcmp($color_style_parts[0], '')) {
					//$formFieldObject->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])]);
					if (!isset($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])])) {
						//$formFieldObject->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][0]);
					}
				}
				// TODO: add getter and setter for _wrapBorder
				if (strcmp($color_style_parts[1], '')) {
					//$formFieldObject->setFieldStyle($GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])]);
					// TODO check if this check is still neccessary
					if (!isset($GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])])) {
						//$formFieldObject->setFieldStyle($GLOBALS['TBE_STYLES']['styleschemes'][0]);
					}
				}
				if (strcmp($color_style_parts[2], '')) {
					if (isset($parts[4])) $formFieldObject->_wrapBorder = true;
					//$formFieldObject->setBorderStyle($GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])]);
					// TODO check if this check is still neccessary
					if (!isset($GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])])) {
						//$formFieldObject->setBorderStyle($GLOBALS['TBE_STYLES']['borderschemes'][0]);
					}
				}
			}
		}

		//$this->resolveMainPalettes();
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

	public function hasSubtypeValueField() {
		return !empty($this->subtypeValueField);
	}

	public function getSubtypeValueField() {
		return $this->subtypeValueField;
	}

	public function getFieldListForSubtype($subtype) {
		$fieldList = $this->fieldList;

		$fieldList = array_diff($fieldList, $this->subtypesExcludeList[$subtype]);
		$fieldList = array_merge($fieldList, $this->subtypesAddList[$subtype]);

		return $fieldList;
	}
}

?>