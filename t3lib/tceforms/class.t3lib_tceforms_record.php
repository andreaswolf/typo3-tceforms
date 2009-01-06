<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2008 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Contains the record abstraction class for TCEforms.
 *
 * @author  Andreas Wolf <andreas.wolf@ikt-werk.de>
 */

/**
 * This class serves as a record abstraction for TCEforms. Is instantiated by a form object and
 * responsible for creating and rendering its own HTML form.
 *
 * @author     Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package    TYPO3
 * @subpackage t3lib_TCEforms
 */
// TODO: add getters for field values, implement ArrayAccess interface
class t3lib_TCEforms_Record {

	/**
	 * The table
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The data of the record
	 *
	 * @var array
	 */
	protected $recordData;

	/**
	 * The TCA definition for the record
	 *
	 * @var array
	 */
	protected $TCAdefinition;

	/**
	 * An array holding the names of all fields to be rendered
	 *
	 * @var array
	 */
	protected $fieldList;

	/**
	 * The form builder object constructing the
	 *
	 * @var t3lib_TCEforms_FormBuilder
	 */
	protected $formBuilder;

	/**
	 * The currently used sheet
	 *
	 * @var t3lib_TCEforms_Sheet
	 */
	protected $currentSheet;

	/**
	 * A collection of all sheet objects
	 *
	 * @var array
	 */
	protected $sheetObjects = array();


	/**
	 * The form object this record belongs to. The form is the global context of the whole form and will
	 * hold all information about Javascript etc.
	 *
	 * @var t3lib_TCEforms_Form
	 */
	protected $parentFormObject;

	protected $sheetIdentString;
	protected $sheetIdentStringMD5;
	protected $sheetCounter = 0;

	/**
	 *
	 *
	 * @var integer
	 */
	protected $typeNumber;

	protected $contextObject;


	public function __construct($table, array $recordData, array $TCAdefinition) {
		$this->table = $table;
		$this->recordData = $recordData;
		$this->TCAdefinition = $TCAdefinition;

		$this->setRecordTypeNumber();
	}

	public function init() {
		$this->formFieldNamePrefix = $this->formBuilder->getFormFieldNamePrefix();

		$this->createFieldsList();

		$this->setExcludeElements();

		$this->buildTCAObjectTree();

		$this->resolveMainPalettes();
	}

	public function render() {
		$tabContents = array();

		$c = 0;
		foreach ($this->sheetObjects as $sheetObject) {
			++$c;
			$tabContents[$c] = array(
				'newline' => $sheetObject->isStartingNewRowInTabmenu(),
				'label' => $sheetObject->getHeader(),
				'content' => $sheetObject->render()
			);
		}
		if (count($tabContents) > 1) {
			$content = $this->formBuilder->getDynTabMenu($tabContents, $this->sheetIdentString);
		} else {
			$content = $tabContents[1]['content'];
		}

		return $content;
	}


	public function injectFormBuilder(t3lib_TCEforms_FormBuilder $formBuilder) {
		$this->formBuilder = $formBuilder;

		return $this;
	}


	/**
	 * Creates the list of fields to display
	 *
	 * This function is mainly copied from t3lib_TCEforms::getMainFields()
	 */
	protected function createFieldsList() {
		$itemList = $this->TCAdefinition['types'][$this->typeNumber]['showitem'];

		$fields = t3lib_div::trimExplode(',', $itemList, 1);
		/* TODO: reenable this
		if ($this->fieldOrder)	{
			$fields = $this->rearrange($fields);
		}*/

		// TODO:
		//$this->fieldList = $this->mergeFieldsWithAddedFields($fields, $this->getFieldsToAdd());
		$this->fieldList = $fields;
	}

	public function buildTCAObjectTree() {
		if (isset($this->fieldList[0]) && strpos($this->fieldList[0], '--div--') !== 0) {
			$this->currentSheet = $this->createNewSheetObject($this->getLL('l_generalTab'));
		}

		foreach ($this->fieldList as $fieldInfo) {
			// Exploding subparts of the field configuration:
			$parts = explode(';', $fieldInfo);

			$theField = $parts[0];
			if ($this->isExcludeElement($theField)) {
				continue;
			}

			if ($theField == '--div--') {
				++$sheetCounter;

				$this->currentSheet = $this->createNewSheetObject($this->sL($parts[1]));
			} else {
				if ($theField !== '') {
					if ($this->TCAdefinition['columns'][$theField]) {
						// TODO: Handle field configuration here.
						$formFieldObject = $this->formBuilder->getSingleField($theField, $this->TCAdefinition['columns'][$theField], $parts[1], $parts[3]);

					} elseif ($theField == '--palette--') {
						// TODO: add top-level palette handling! (--palette--, see TYPO3 Core API, section 4.2)
						//       steps: create a new element type "palette" as a dumb wrapper for a palette
						//       for testing see tt_content, type text w/image, image dimensions and links
						$formFieldObject = $this->formBuilder->createPaletteElement($parts[2], $this->sL($parts[1]));
						//print_r($formFieldObject);
					}

					$this->currentSheet->addChildObject($formFieldObject);

					$formFieldObject->setParentFormObject($this->contextObject)
					                ->setParentRecordObject($this)
					                ->setTable($this->table)
					                ->setRecord($this->recordData)
					                ->injectFormBuilder($this->formBuilder)
					                ->init();

					if (isset($parts[2]) && t3lib_div::testInt($parts[2])) {
						$formFieldObject->initializePalette($parts[2]);
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
					$formFieldObject->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])]);
					if (!isset($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])])) {
						$formFieldObject->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][0]);
					}
				}
				// TODO: add getters and setters for fieldStyle, _wrapBorder and borderStyle
				if (strcmp($color_style_parts[1], '')) {
					$formFieldObject->setFieldStyle($GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])]);
					// TODO check if this check is still neccessary
					if (!isset($GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])])) {
						$formFieldObject->setFieldStyle($GLOBALS['TBE_STYLES']['styleschemes'][0]);
					}
				}
				if (strcmp($color_style_parts[2], '')) {
					if (isset($parts[4])) $formFieldObject->_wrapBorder = true;
					$formFieldObject->setBorderStyle($GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])]);
					// TODO check if this check is still neccessary
					if (!isset($GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])])) {
						$formFieldObject->setBorderStyle($GLOBALS['TBE_STYLES']['borderschemes'][0]);
					}
				}
			}
		}
	}


	public function setParentFormObject(t3lib_TCEforms_Form $parentFormObject) {
		$this->contextObject = $parentFormObject;

		return $this;
	}

	protected function resolveMainPalettes() {
		$mainPalettesArray = t3lib_div::trimExplode(',', $this->TCAdefinition['ctrl']['mainpalette']);

		$i = 0;
		foreach ($mainPalettesArray as $paletteNumber) {
			++$i;

			// TODO: check again if palette has been rendered
			//if (!isset($this->palettesRendered[$this->renderDepth][$table][$mP]))	{
				/*$temp_palettesCollapsed=$this->palettesCollapsed;
				$this->palettesCollapsed=0;
				$label = ($i==0?$this->getLL('l_generalOptions'):$this->getLL('l_generalOptions_more'));
				$out_array[$out_sheet][$out_pointer].=$this->getPaletteFields($table,$row,$mP,$label);
				$this->palettesCollapsed=$temp_palettesCollapsed;
				$this->palettesRendered[$this->renderDepth][$table][$mP] = 1;*/
				$label = $i==1 ? $this->getLL('l_generalOptions') : $this->getLL('l_generalOptions_more');

				$paletteFieldObject = $this->formBuilder->createPaletteElement($paletteNumber, $label);

				$this->currentSheet->addChildObject($paletteFieldObject);

				$paletteFieldObject->setParentFormObject($this->contextObject)
				                   ->setParentRecordObject($this)
				                   ->setTable($this->table)
				                   ->setRecord($this->recordData)
				                   ->injectFormBuilder($this->formBuilder)
				                   ->init();
			//}
			/*$this->wrapBorder($out_array[$out_sheet],$out_pointer);
			$i++;
			if ($this->renderDepth)	{
				$this->renderDepth--;
			}*/
		}
	}

	/**
	 * Calculate the current "types" pointer value for the record this form is instantiated for
	 *
	 * Sets $this->typeNumber to the types pointer value.
	 *
	 * @return void
	 */
	protected function setRecordTypeNumber() {
			// If there is a "type" field configured...
		if ($this->TCAdefinition['ctrl']['type']) {
			$typeFieldName = $this->TCAdefinition['ctrl']['type'];
				// Get value of the row from the record which contains the type value.
			$this->typeNumber = $this->recordData[$typeFieldName];
				// If that value is an empty string, set it to "0" (zero)
			if (!strcmp($this->typeNumber,'')) $this->typeNumber = 0;
		} else {
			$this->typeNumber = 0;	// If no "type" field, then set to "0" (zero)
		}

			// Force to string. Necessary for eg '-1' to be recognized as a type value.
		$this->typeNumber = (string)$this->typeNumber;
			// However, if the type "0" is not found in the "types" array, then default to "1" (for historical reasons)
		if (!$this->TCAdefinition['types'][$this->typeNumber]) {
			$this->typeNumber = 1;
		}
	}

	/**
	 * Returns if a given element is among the elements set via setExcludeElements(), i.e.
	 * not displayed in the form
	 *
	 * @param  string  $elementName  The name of the element to check
	 * @return boolean
	 */
	public function isExcludeElement($elementName) {
		return t3lib_div::inArray($this->excludeElements, $elementName);
	}

	/**
	 * Returns the full array of elements which are excluded and thus not displayed on the form
	 *
	 * @return array
	 */
	public function getExcludeElements() {
		return $this->excludeElements;
	}

	/**
	 * Producing an array of field names NOT to display in the form, based on settings
	 * from subtype_value_field, bitmask_excludelist_bits etc.
	 *
	 * NOTICE: This list is in NO way related to the "excludeField" flag
	 *
	 * Sets $this->excludeElements to an array with fieldnames as values. The fieldnames are
	 * those which should NOT be displayed "anyways"
	 *
	 * @return void
	 */
	protected function setExcludeElements() {
		global $TCA;

			// Init:
		$this->excludeElements = array();

			// If a subtype field is defined for the type
		if ($this->TCAdefinition['types'][$this->typeNumber]['subtype_value_field']) {
			$subtypeField = $this->TCAdefinition['types'][$this->typeNumber]['subtype_value_field'];
			if (trim($this->TCAdefinition['types'][$this->typeNumber]['subtypes_excludelist'][$this->recordData[$subtypeField]])) {
				$this->excludeElements=t3lib_div::trimExplode(',',$this->TCAdefinition['types'][$this->typeNumber]['subtypes_excludelist'][$this->recordData[$subtypeField]],1);
			}
		}

			// If a bitmask-value field has been configured, then find possible fields to exclude based on that:
		if ($this->TCAdefinition['types'][$this->typeNumber]['bitmask_value_field']) {
			$subtypeField = $this->TCAdefinition['types'][$this->typeNumber]['bitmask_value_field'];
			$subtypeValue = t3lib_div::intInRange($this->recordData[$subtypeField],0);

			if (is_array($this->TCAdefinition['types'][$this->typeNumber]['bitmask_excludelist_bits'])) {
				reset($this->TCAdefinition['types'][$this->typeNumber]['bitmask_excludelist_bits']);
				while(list($bitKey,$eList)=each($this->TCAdefinition['types'][$this->typeNumber]['bitmask_excludelist_bits'])) {
					$bit=substr($bitKey,1);
					if (t3lib_div::testInt($bit)) {
						$bit = t3lib_div::intInRange($bit,0,30);
						if (
								(substr($bitKey,0,1)=='-' && !($subtypeValue&pow(2,$bit))) ||
								(substr($bitKey,0,1)=='+' && ($subtypeValue&pow(2,$bit)))
							) {
							$this->excludeElements = array_merge($this->excludeElements,t3lib_div::trimExplode(',',$eList,1));
						}
					}
				}
			}
		}
	}

	public function getTCAdefinitionForField($fieldName) {
		return $this->TCAdefinition['columns'][$fieldName];
	}

	public function getTCAdefinitionForTable() {
		return $this->TCAdefinition;
	}

	public function getTable() {
		return $this->table;
	}

	public function getRecordData() {
		return $this->recordData;
	}

	public function getValue($key) {
		return $this->recordData[$key];
	}


	/********************************************
	 *
	 * Sheet functions
	 *
	 ********************************************/

	protected function createNewSheetObject($header) {
		if ($this->sheetIdentString == '') {
			$this->sheetIdentString = $this->getSheetIdentString;
			$this->sheetIdentStringMD5 = $GLOBALS['TBE_TEMPLATE']->getDynTabMenuId($sheetIdentString);
		}

		++$this->sheetCounter;

		$sheetObject = $this->formBuilder->createSheetObject($sheetIdentStringMD5.'-'.$this->sheetCounter, $header);
		$sheetObject->setParentObject($this)
		            ->setParentFormObject($this->contextObject);

		$this->sheetObjects[] = $sheetObject;

		return $sheetObject;
	}

	protected function getSheetIdentString() {
		return 'TCEforms:'.$this->table.':'.$this->recordData['uid'];
	}



	/********************************************
	 *
	 * Localization functions
	 *
	 ********************************************/

	/**
	 * Fetches language label for key
	 *
	 * @param   string  Language label reference, eg. 'LLL:EXT:lang/locallang_core.php:labels.blablabla'
	 * @return  string  The value of the label, fetched for the current backend language.
	 */
	// TODO: refactor the method name
	protected function sL($str) {
		return $GLOBALS['LANG']->sL($str);
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