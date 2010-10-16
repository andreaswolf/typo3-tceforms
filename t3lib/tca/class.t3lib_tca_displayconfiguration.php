<?php

/**
 * This class holds all information neccessary for e.g. building a complete editing form for a record.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_TCA_DisplayConfiguration {

	/**
	 * @var array<t3lib_TCA_DataStructure_Sheet>
	 */
	protected $sheets;

	/**
	 * @var t3lib_TCA_DataStructure
	 */
	protected $dataStructure;

	/**
	 * The sheet the subtype value field is located on
	 *
	 * @var t3lib_TCA_DataStructure_Sheet
	 */
	protected $subtypeValueFieldSheet;

	/**
	 * @var t3lib_TCA_DataStructure_Type
	 */
	protected $typeConfiguration;

	/**
	 * @var array<string>
	 */
	protected $excludeFieldList;


	protected function __construct(t3lib_TCA_DataStructure $dataStructure) {
		$this->dataStructure = $dataStructure;
	}

	public static function createFromConfiguration(t3lib_TCA_DataStructure $dataStructure, $typeConfiguration, array $addFieldList, array $excludeFieldList) {
		$obj = new t3lib_TCA_DisplayConfiguration($dataStructure);
		$obj->typeConfiguration = $typeConfiguration;
		$obj->resolveConfiguration($configuration, $addFieldList, $excludeFieldList);
		$obj->addElements($addFieldList);

		return $obj;
	}

	public static function createFromSheets(t3lib_TCA_DataStructure $dataStructure, array $sheets) {
		$obj = new t3lib_TCA_DisplayConfiguration($dataStructure);
		$obj->sheets = $sheets;

		return $obj;
	}

	protected function resolveConfiguration($configuration, array $addFieldList, array $excludeFieldList) {
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

				if (in_array($theField, $excludeFieldList)) {
					continue;
				}

				if ($theField !== '') {
					if ($theField == '--palette--') {
						$elementObject = $this->createPaletteObject($parts[2], $GLOBALS['LANG']->sL($parts[1]));
					} elseif ($this->dataStructure->hasField($theField)) {
						// we possibly modify the object, so create a clone
						$elementObject = clone $this->dataStructure->getFieldObject($theField);

						if ($parts[2]) {
							$paletteObject = $this->createPaletteObject($parts[2]);
							$elementObject->addPalette($paletteObject);
						}

						$elementObject->setSpecialConfiguration($parts[3]);
						$elementObject->setLabel($GLOBALS['LANG']->sL($parts[1]));
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

	/**
	 * Adds elements defined in a subtype configuration. They were handed over to this object by the
	 * datastructure class.
	 *
	 * @TODO check if additional configuration (alt. label etc.) may exist in these items
	 */
	protected function addElements($addFieldList) {
		foreach ($addFieldList as $fieldName) {
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
	 * @return t3lib_TCA_DataStructure_Palette
	 */
	protected function createPaletteObject($paletteNumber, $label = '') {
		$paletteConfiguration = $this->dataStructure->getPaletteConfiguration($paletteNumber);
		$paletteFieldNames = t3lib_div::trimExplode(',', $paletteConfiguration['showitem']);

		$paletteObject = new t3lib_TCA_DataStructure_Palette($this->dataStructure, $label, $paletteNumber);

		foreach ($paletteFieldNames as $fieldName) {
			if (in_array($fieldName, $this->excludeFieldList)) {
				continue;
			}

			$fieldObject = $this->dataStructure->getFieldObject($fieldName);
			$paletteObject->addElement($fieldObject);
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