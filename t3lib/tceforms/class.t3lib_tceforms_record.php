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
	 * The top-level form object of this TCEforms object tree.
	 *
	 * @var t3lib_TCEforms_Context
	 */
	protected $contextObject;

	/**
	 * The record object on the top level, right below the context form. Only used in IRRE context.
	 *
	 * @var t3lib_TCEforms_Record
	 */
	protected $contextRecordObject;

	/**
	 * The form object this record belongs to.
	 *
	 * @var t3lib_TCEforms_Form
	 */
	protected $parentFormObject;

	protected $sheetIdentString;
	protected $sheetIdentStringMD5;
	protected $sheetCounter = 0;

	/**
	 * The prefix for form field names, used to build a hierarchical structure.
	 *
	 * @var string
	 */
	protected $formFieldNamePrefix;

	/**
	 * The prefix for form field ids. May only contain A-Z, a-z, 0-9, _, -, : and .
	 *
	 * @var string
	 */
	protected $formFieldIdPrefix;

	/**
	 *
	 *
	 * @var integer
	 */
	protected $typeNumber;

	/**
	 * Holds the numbers of all palettes that objects have been created for. Used to check that no
	 * fields are double rendered.
	 *
	 * @var array
	 */
	protected $createdPalettes = array();


	public function __construct($table, array $recordData, array $TCAdefinition) {
		$this->table = $table;
		$this->recordData = $recordData;
		$this->TCAdefinition = $TCAdefinition;
		$this->contextRecordObject = $this;

		$this->setRecordTypeNumber();
	}

	public function setContextObject(t3lib_TCEforms_Context $contextObject) {
		$this->contextObject = $contextObject;

		return $this;
	}

	public function setParentFormObject(t3lib_TCEforms_Form $formObject) {
		$this->parentFormObject = $formObject;

		return $this;
	}

	public function init() {
		$this->formBuilder = t3lib_TCEforms_Formbuilder::createInstanceForRecordObject($this);

		$this->formFieldNamePrefix = $this->parentFormObject->getFormFieldNamePrefix().'[' . $this->getTable() . '][' . $this->recordData['uid'] . ']';
		$this->formFieldIdPrefix = $this->parentFormObject->getFormFieldIdPrefix() . '_' . $this->getTable() . '_' . $this->recordData['uid'];

		$this->createFieldsList();

		$this->setExcludeElements();

		$this->registerDefaultLanguageData();

		$this->formBuilder->buildObjectStructure($this);
	}


	public function render() {
		$tabContents = array();

		$c = 0;
		foreach ($this->sheetObjects as $sheetObject) {
			$tabContents[$c] = array(
				'newline' => $sheetObject->isStartingNewRowInTabmenu(),
				'label' => $sheetObject->getHeader(),
				'content' => $sheetObject->render()
			);
			++$c;
		}
		if (count($tabContents) > 1) {
			$content = $this->formBuilder->getDynTabMenu($tabContents, $this->sheetIdentString);
		} else {
			$content = $tabContents[0]['content'];
		}

		return $content;
	}


	/**
	 * Creates the list of fields to display
	 *
	 * This function is mainly copied from t3lib_TCEforms::getMainFields()
	 */
	protected function createFieldsList() {
		if (count($this->fieldList) > 0) {
			return;
		}

		$itemList = $this->TCAdefinition['types'][$this->typeNumber]['showitem'];

		$fields = t3lib_div::trimExplode(',', $itemList, 1);
		/* TODO: reenable this
		if ($this->fieldOrder)	{
			$fields = $this->rearrange($fields);
		}*/

		$this->fieldList = $this->mergeFieldsWithAddedFields($fields, $this->getFieldsToAdd());
	}

	public function getFieldList() {
		return $this->fieldList;
	}

	public function setFieldList($fieldList) {
		if (!is_array($fieldList)) {
			$fieldList = t3lib_div::trimExplode(',', $fieldList, 1);
		}

		$this->fieldList = $fieldList;

		return $this;
	}

	public function getFormFieldNamePrefix() {
		return $this->formFieldNamePrefix;
	}

	public function getFormFieldIdPrefix() {
		return $this->formFieldIdPrefix;
	}

	/**
	 * Returns a unique identifier of the form <table>:<uid> for the record
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->getTable() . ':' . $this->recordData['uid'];
	}

	/**
	 * Returns the title of the record.
	 *
	 * @return string
	 */
	public function getTitle() {
		return t3lib_BEfunc::getRecordTitle($this->table, $this->recordData);
	}

	/**
	 * Returns the context object this form is in.
	 *
	 * @return t3lib_TCEforms_ContextInterface
	 */
	public function getContextObject() {
		return $this->contextObject;
	}

	/**
	 * Checks if a palette has been created before for this record
	 *
	 * @param integer $paletteNumber
	 * @return boolean TRUE if the palette object has already been created
	 */
	public function isPaletteCreated($paletteNumber) {
		return in_array($paletteNumber, $this->createdPalettes);
	}

	/**
	 * @param integer $paletteNumber
	 * @return t3lib_TCEforms_Record
	 */
	public function setPaletteCreated($paletteNumber) {
		if ($this->isPaletteCreated($paletteNumber)) {
			throw new RuntimeException('Palette number ' . $paletteNumber . ' has already been created.');
		}

		$this->palettesCreated[] = $paletteNumber;

		return $this;
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

	/**
	 * Finds possible field to add to the form, based on subtype fields.
	 *
	 * @return	array		An array containing two values: 1. Another array containing fieldnames to add and 2. the subtype value field.
	 * @see getMainFields()
	 */
	protected function getFieldsToAdd()	{
			// Init:
		$addElements = array();

			// If a subtype field is defined for the type
		if ($this->TCAdefinition['types'][$this->typeNumber]['subtype_value_field']) {
			$subtypeValueField = $this->TCAdefinition['types'][$this->typeNumber]['subtype_value_field'];
			if (trim($this->TCAdefinition['types'][$this->typeNumber]['subtypes_addlist'][$this->recordData[$subtypeValueField]])) {
				$addElements = t3lib_div::trimExplode(',', $this->TCAdefinition['types'][$this->typeNumber]['subtypes_addlist'][$this->recordData[$subtypeValueField]],1);
			}
		}

			// Return the return
		return array($addElements, $subtypeValueField);
	}

	/**
	 * Merges the current [types][showitem] array with the array of fields to add for the current subtype field of the "type" value.
	 *
	 * @param	array		A [types][showitem] list of fields, exploded by ","
	 * @param	array		The output from getFieldsToAdd()
	 * @return	array		Return the modified $fields array.
	 * @see getMainFields(),getFieldsToAdd()
	 */
	protected function mergeFieldsWithAddedFields($fields, $fieldsToAdd)	{
		if (count($fieldsToAdd[0])) {
			reset($fields);
			$c=0;
			while(list(,$fieldInfo)=each($fields)) {
				$parts = explode(';',$fieldInfo);
				if (!strcmp(trim($parts[0]),$fieldsToAdd[1])) {
					array_splice(
						$fields,
						$c+1,
						0,
						$fieldsToAdd[0]
					);
					break;
				}
				$c++;
			}
		}
		return $fields;
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

	// TODO: load data if neccessary before returning anything
	public function getDefaultLanguageData() {
		return $this->defaultLanguageData;
	}

	public function getDefaultLanguageValue($key) {
		return $this->defaultLanguageData[$key];
	}

	public function getDefaultLanguageDiffData() {
		return $this->defaultLanguageData_diff;
	}

	public function getDefaultLanguageDiffValue($key) {
		return $this->defaultLanguageData_diff[$key];
	}

	public function getValue($key) {
		return $this->recordData[$key];
	}

	public function setContextRecordObject(t3lib_TCEforms_Record $contextRecordObject) {
		$this->contextRecordObject = $contextRecordObject;

		return $this;
	}

	public function getContextRecordObject() {
		return $this->contextRecordObject;
	}


	/********************************************
	 *
	 * Sheet functions
	 *
	 ********************************************/

	public function addSheetObject(t3lib_TCEforms_Container_Sheet $sheetObject) {
		$sheetObject->setContextObject($this->contextObject);
		$this->sheetObjects[] = $sheetObject;

		t3lib_div::devLog('Added sheet no. ' . count($this->sheetObjects) . ' to record ' . $this->getIdentifier() . '.', 't3lib_TCEforms_Record', t3lib_div::SYSLOG_SEVERITY_INFO);
	}

	protected function createNewSheetObject($header) {
		if ($this->sheetIdentString == '') {
			$this->sheetIdentString = $this->getSheetIdentString();
			$this->sheetIdentStringMD5 = $GLOBALS['TBE_TEMPLATE']->getDynTabMenuId($this->getSheetIdentString());
		}

		++$this->sheetCounter;

		$sheetObject = $this->formBuilder->createSheetObject($this->sheetIdentStringMD5.'-'.$this->sheetCounter, $header);
		$sheetObject->setContextObject($this->contextObject);

		$this->sheetObjects[] = $sheetObject;

		return $sheetObject;
	}

	public function getSheetIdentString() {
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


	/************************************************************
	 *
	 * Display of localized content etc.
	 *
	 ************************************************************/

	/**
	 * Will register data from original language records if the current record is a translation of another.
	 * The original data is shown with the edited record in the form. The information also includes possibly diff-views of what changed in the original record.
	 * Function called from outside (see alt_doc.php + quick edit) before rendering a form for a record
	 *
	 * @return	void
	 */
	protected function registerDefaultLanguageData()	{
			// Add default language:
		if ($this->TCAdefinition['ctrl']['languageField']
				&& $this->recordData[$this->TCAdefinition['ctrl']['languageField']] > 0
				&& $this->TCAdefinition['ctrl']['transOrigPointerField']
				&& intval($this->recordData[$this->TCAdefinition['ctrl']['transOrigPointerField']]) > 0) {

			$lookUpTable = $this->TCAdefinition['ctrl']['transOrigPointerTable'] ? $this->TCAdefinition['ctrl']['transOrigPointerTable'] : $this->table;

				// Get data formatted:
			$this->defaultLanguageData = t3lib_BEfunc::getRecordWSOL($lookUpTable, intval($this->recordData[$this->TCAdefinition['ctrl']['transOrigPointerField']]));

				// Get data for diff:
			if ($this->TCAdefinition['ctrl']['transOrigDiffSourceField']) {
				$this->defaultLanguageData_diff = unserialize($this->recordData[$this->TCAdefinition['ctrl']['transOrigDiffSourceField']]);
			}

				// If there are additional preview languages, load information for them also:
			$prLang = $this->getAdditionalPreviewLanguages();
			foreach($prLang as $prL) {
				$t8Tools = t3lib_div::makeInstance('t3lib_transl8tools');
				$tInfo = $t8Tools->translationInfo($lookUpTable,intval($this->recordData[$this->TCAdefinition['ctrl']['transOrigPointerField']]),$prL['uid']);
				if (is_array($tInfo['translations'][$prL['uid']]))	{
					$this->additionalPreviewLanguageData[$prL['uid']] = t3lib_BEfunc::getRecordWSOL($this->table, intval($tInfo['translations'][$prL['uid']]['uid']));
				}
			}
		}
	}

	/**
	 * Generates and return information about which languages the current user should see in preview, configured by options.additionalPreviewLanguages
	 *
	 * return array	Array of additional languages to preview
	 */
	public function getAdditionalPreviewLanguages()	{
		if (!isset($this->cachedAdditionalPreviewLanguages)) 	{
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.additionalPreviewLanguages'))	{
				$uids = t3lib_div::intExplode(',',$GLOBALS['BE_USER']->getTSConfigVal('options.additionalPreviewLanguages'));
				foreach($uids as $uid)	{
					if ($sys_language_rec = t3lib_BEfunc::getRecord('sys_language',$uid))	{
						$this->cachedAdditionalPreviewLanguages[$uid] = array('uid' => $uid);

						if ($sys_language_rec['static_lang_isocode'] && t3lib_extMgm::isLoaded('static_info_tables'))	{
							$staticLangRow = t3lib_BEfunc::getRecord('static_languages',$sys_language_rec['static_lang_isocode'],'lg_iso_2');
							if ($staticLangRow['lg_iso_2']) {
								$this->cachedAdditionalPreviewLanguages[$uid]['uid'] = $uid;
								$this->cachedAdditionalPreviewLanguages[$uid]['ISOcode'] = $staticLangRow['lg_iso_2'];
							}
						}
					}
				}
			} else {
					// None:
				$this->cachedAdditionalPreviewLanguages = array();
			}
		}
		return $this->cachedAdditionalPreviewLanguages;
	}

	/**
	 * Initializes language icons etc.
	 *
	 * param	string	Sys language uid OR ISO language code prefixed with "v", eg. "vDA"
	 * @return	void
	 */
	public function getLanguageIcon($sys_language_uid)	{
		global $TCA, $LANG;

		$mainKey = $this->table.':'.$this->recordData['uid'];

		if (!isset($this->cachedLanguageFlag[$mainKey])) {
			t3lib_BEfunc::fixVersioningPid($this->table, $this->recordData);
			list($tscPID,$thePidValue) = $this->getTSCpid($this->table, $this->recordData['uid'], $this->recordData['pid']);

			$t8Tools = t3lib_div::makeInstance('t3lib_transl8tools');
			$this->cachedLanguageFlag[$mainKey] = $t8Tools->getSystemLanguages($tscPID, $this->backPath);
		}

			// Convert sys_language_uid to sys_language_uid if input was in fact a string (ISO code expected then)
		if (!t3lib_div::testInt($sys_language_uid)) {
			foreach($this->cachedLanguageFlag[$mainKey] as $rUid => $cD) {
				if ('v' . $cD['ISOcode'] === $sys_language_uid) {
					$sys_language_uid = $rUid;
				}
			}
		}

		return ($this->cachedLanguageFlag[$mainKey][$sys_language_uid]['flagIcon'] ? '<img src="'.$this->cachedLanguageFlag[$mainKey][$sys_language_uid]['flagIcon'].'" class="absmiddle" alt="" />' : ($this->cachedLanguageFlag[$mainKey][$sys_language_uid]['title'] ? '['.$this->cachedLanguageFlag[$mainKey][$sys_language_uid]['title'].']' : '')).'&nbsp;';
	}

	/**
	 * Return TSCpid (cached)
	 * Using t3lib_BEfunc::getTSCpid()
	 *
	 * @return	integer		Returns the REAL pid of the record, if possible. If both $uid and $pid is strings, then pid=-1 is returned as an error indication.
	 * @see t3lib_BEfunc::getTSCpid()
	 */
	function getTSCpid() {
		$key = $this->table.':'.$this->recordData['uid'].':'.$this->recordData['pid'];
		if (!isset($this->cache_getTSCpid[$key])) {
			$this->cache_getTSCpid[$key] = t3lib_BEfunc::getTSCpid($this->table, $this->recordData['uid'], $this->recordData['pid']);
		}
		return $this->cache_getTSCpid[$key];
	}
}

?>
