<?php

/**
 * This class holds all information necessary for
 * e.g. building a complete editing form for a record.
 *
 * A display configuration holds the right information of a record
 * on what to display and what not to display. It takes the "type" field
 * of the TCA records (defined in the "ctrl" section of a TCA table) and
 * the subtype field of a record into account.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_TCA_DisplayConfiguration {

	/**
	 * @var array<t3lib_TCA_DataStructure_Sheet>
	 */
	protected $sheets;

	/**
	 * @var t3lib_DataStructure_Tca
	 */
	protected $dataStructure;

	/**
	 * The sheet the subtype value field is located on
	 *
	 * @var t3lib_TCA_DataStructure_Sheet
	 */
	protected $subtypeValueFieldSheet;

	/**
	 * @var t3lib_DataStructure_Type
	 */
	protected $typeConfiguration;

	/**
	 * @var array<string>
	 */
	protected $excludeFieldList;

	/**
	 * The list of fields this display configuration contains
	 *
	 * @var array
	 */
	protected $fieldList;


	/**
	 * constructor, should not be used publicly, although has to be public due to
	 * the use of t3lib_div::makeInstance().
	 * For instantiating the class, please have a look at the createFromConfiguration
	 * function when there is a concrete typeConfiguration (with every TCA record)
	 * or createFromSheets when having a Flexform.
	 *
	 * @param t3lib_DataStructure_Tca $dataStructure
	 * @see createFromConfiguration(), createFromSheets()
	 */
	public function __construct(t3lib_DataStructure_Tca $dataStructure) {
		$this->dataStructure = $dataStructure;
	}

	/**
	 * Factory method to create a new display configuration
	 * based on the data structure and a single entry of the typeConfiguration
	 *
	 * @param array $fieldList The list of fields to display
	 */
	public static function createFromConfiguration(t3lib_DataStructure_Tca $dataStructure, t3lib_DataStructure_Type $typeConfiguration, array $addFieldList, array $excludeFieldList, array $fieldList = NULL) {
		/** @var $obj t3lib_TCA_DisplayConfiguration */
		$obj = t3lib_div::makeInstance('t3lib_TCA_DisplayConfiguration', $dataStructure);
		$obj->typeConfiguration = $typeConfiguration;
		$obj->excludeFieldList = $excludeFieldList;
		if (count($fieldList) > 0) {
			$currentSheet = $obj->createSheetObject('');
			foreach ($fieldList as $field) {
				if ($dataStructure->hasField($field)) {
					$elementObject = $dataStructure->getFieldObject($field);
					$currentSheet->addElement($elementObject);
					$obj->fieldList[] = $elementObject;
				}
		    }
		} else {
			$obj->resolveConfiguration();
			$obj->addElements($addFieldList);
		}

		return $obj;
	}

	/**
	 * Factory method to create a new display configuration
	 * based on the data structure and the sheets, used when having flexforms
	 */
	public static function createFromSheets(t3lib_DataStructure_Tca $dataStructure, array $sheets) {
		/** @var $obj t3lib_TCA_DisplayConfiguration */
		$obj = t3lib_div::makeInstance('t3lib_TCA_DisplayConfiguration', $dataStructure);
		$obj->sheets = $sheets;

		return $obj;
	}

	protected function resolveConfiguration() {
		// TODO store styling information from the showitem subarray
		$fields = $this->typeConfiguration->getShowitemString();

		$fieldList = t3lib_div::trimExplode(',', $fields);
		$sheetCounter = 0;

		if (isset($fieldList[0]) && strpos($fieldList[0], '--div--') !== 0) {
			++$sheetCounter;
			$currentSheet = $this->createSheetObject($this->getLL('l_generalTab'));
		}

		foreach ($fieldList as $fieldInfo) {
			// Exploding subparts of the field configuration:
			// the parts are:
			// 0: label
			// 1: alternative fieldlabel
			// 2: palette number
			// 3: Special configuration, see TCA ref
			// 4: Form style codes
			$parts = explode(';', $fieldInfo);

			$theField = $parts[0];

			if ($theField == '--div--') {
				++$sheetCounter;

				$currentSheet = $this->createSheetObject($GLOBALS['LANG']->sL($parts[1]));
			} else {

				if (in_array($theField, $this->excludeFieldList)) {
					continue;
				}

				if ($theField !== '') {
					// Getting the style information out:
					$fieldStyle = t3lib_TCA_FieldStyle::createFromDefinition($parts[4], $fieldStyle);

					if ($theField == '--palette--') {
						$elementObject = $this->createPaletteObject($parts[2], $GLOBALS['LANG']->sL($parts[1]), $fieldStyle);
					} elseif ($this->dataStructure->hasField($theField)) {
						// we possibly modify the object, so create a clone
						$elementObject = clone $this->dataStructure->getFieldObject($theField);

						if ($parts[2]) {
							$paletteObject = $this->createPaletteObject($parts[2], '', $fieldStyle);
							$elementObject->addPalette($paletteObject);
						}

						$elementObject->setSpecialConfiguration($parts[3]);
						$elementObject->setLabel($GLOBALS['LANG']->sL($parts[1]));
						$elementObject->setStyle($fieldStyle);
					} else {
						// if this is no field, just continue with the next entry in the field list.
						continue;
					}

					$this->fieldList[] = $theField;
					$currentSheet->addElement($elementObject);

					if ($this->typeConfiguration->hasSubtypeValueField() && $this->typeConfiguration->getSubtypeValueField() == $theField) {
						$this->subtypeValueFieldSheet = $currentSheet;
					}
				}
			}
		}

		//$this->resolveMainPalettes();
	}

	/**
	 * Adds elements defined in a subtype configuration. They were handed over to this object by the
	 * datastructure class.
	 *
	 * @TODO check if additional configuration (alt. label etc.) may exist in these items
	 */
	protected function addElements($addFieldList) {
		foreach ($addFieldList as $fieldName) {
			if (trim($fieldName) == '') {
				continue;
			}
			$fieldObject = $this->dataStructure->getFieldObject($fieldName);

			$this->subtypeValueFieldSheet->addElement($fieldObject);
		}
	}

	public function getSheets() {
		return $this->sheets;
	}

	protected function createSheetObject($label, $name = '') {
		$sheetObject = new t3lib_TCA_DataStructure_Sheet($label, $name);
		$this->sheets[] = $sheetObject;

		return $sheetObject;
	}

	/**
	 *
	 *
	 * @param integer $paletteNumber
	 * @param string $label
	 * @param t3lib_TCA_FieldStyle $fieldStyle The style for the field that contains the palette. Neccessary as a starting point for
	 * @return t3lib_DataStructure_Element_Palette
	 *
	 * TODO: respect label if given as second element of $fieldDescriptorParts.
	 * TODO: implement handling of linebreak
	 */
	protected function createPaletteObject($paletteNumber, $label = '', t3lib_TCA_FieldStyle $fieldStyle = NULL) {
		$paletteConfiguration = $this->dataStructure->getPaletteConfiguration($paletteNumber);
		$paletteFieldNames = t3lib_div::trimExplode(',', $paletteConfiguration['showitem']);

		$paletteObject = new t3lib_DataStructure_Element_Palette($paletteConfiguration, $this->dataStructure, $label, $paletteNumber);
		$paletteObject->setStyle($fieldStyle);

		foreach ($paletteFieldNames as $fieldDescriptor) {
			$fieldDescriptorParts = t3lib_div::trimExplode(';', $fieldDescriptor);
			$fieldName = $fieldDescriptorParts[0];
			if (in_array($fieldName, $this->excludeFieldList) || $fieldName == '') {
				continue;
			}
			switch ($fieldName) {
				case '--linebreak--':
					$linebreakObject = t3lib_div::makeInstance('t3lib_TCA_DataStructure_LinebreakElement');
					$paletteObject->addElement($linebreakObject);
					// TODO implement linebreak handling
					break;

				default:
					$fieldObject = $this->dataStructure->getFieldObject($fieldName);
					$fieldObject->setLabel($fieldDescriptorParts[1]);
					$fieldObject->setStyle($fieldStyle);
					$paletteObject->addElement($fieldObject);
			}
		}

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
}

?>
