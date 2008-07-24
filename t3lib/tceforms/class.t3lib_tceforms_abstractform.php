<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_tab.php');

abstract class t3lib_TCEforms_AbstractForm {

	/**
	 * @var string  The fieldname of the save button
	 */
	protected $doSaveFieldName = 'doSave';

	/**
	 * @var string  The render mode. May be any of "mainFields", "soloField" or "listedFields"
	 */
	//protected $renderMode = 'mainFields';

	/**
	 * @var string  The table of the record to render
	 */
	protected $table;
	/**
	 * @var array  The record to render
	 */
	protected $record;

	/**
	 * @var array  TCA configuration for the table (reference!)
	 */
	protected $tableTCAconfig;

	protected $typeNumber;

	/**
	 * @var array  The list of items to display
	 */
	protected $itemList;

	/**
	 * @var boolean  Whether or not the palettes (secondary options) are collapsed
	 */
	protected $palettesCollapsed;

	/**
	 * @var array  The array of elements to exclude from rendering
	 * @see setExcludeElements()
	 */
	protected $excludeElements;

	/**
	 * @var boolean  Whether or not to use tabs
	 */
	protected $useTabs;

	/**
	 * @var t3lib_TCEforms_Tab  The currently used tab
	 */
	protected $currentTab;


	protected $formFieldObjects;

	/**
	 * May be safely removed as soon as all dependencies on the old TCEforms are removed!
	 *
	 * @var t3lib_TCEforms
	 */
	protected $TCEformsObject;


	/**
	 * The constructor of this class
	 *
	 * @param string   $table
	 * @param array    $row
	 * @param integer  $typeNumber
	 */
	public function __construct($table, $row) {
		global $TCA;

			// TODO: Refactor this!
		$this->prependFormFieldNames = 'data';
		$this->formName = 'editform';
		//$this->setNewBEDesign();
		$this->docLarge = $GLOBALS['BE_USER']->uc['edit_wideDocument'] ? 1 : 0;
		$this->edit_showFieldHelp = $GLOBALS['BE_USER']->uc['edit_showFieldHelp'];

		$this->edit_docModuleUpload = $GLOBALS['BE_USER']->uc['edit_docModuleUpload'];
		$this->titleLen = $GLOBALS['BE_USER']->uc['titleLen'];		// @deprecated

		//$this->inline->init($this);


			// check if table exists in TCA. This is required!
		if (!$TCA[$table]) {
			// TODO: throw exception here
			die('Table '.$table.'does not exist! [1216891229]');
		}

			// set table and row
		$this->table = $table;
		$this->record = $row;


		$this->tableTCAconfig = &$TCA[$this->table];

			// set type number
		$this->setRecordTypeNumber();

		/*if ($this->typeNumber === '') {
			// TODO: throw exception here
			die('typeNumber '.$this->typeNumber.' does not exist for table '.$this->table.' [1216891550]');
		}*/



		$this->setExcludeElements();

	}

	/**
	 * Finds possible field to add to the form, based on subtype fields.
	 *
	 * @return	array		An array containing two values: 1) Another array containing fieldnames to add and 2) the subtype value field.
	 * @see getMainFields()
	 */
	protected function getFieldsToAdd()	{
		global $TCA;

			// Init:
		$addElements = array();

			// If a subtype field is defined for the type
		if ($this->tableTCAconfig['types'][$this->typeNumber]['subtype_value_field'])	{
			$subtypeField = $this->tableTCAconfig['types'][$this->typeNumber]['subtype_value_field'];
			if (trim($this->tableTCAconfig['types'][$this->typeNumber]['subtypes_addlist'][$this->record[$subtypeField]]))	{
				$addElements = t3lib_div::trimExplode(',', $this->tableTCAconfig['types'][$this->typeNumber]['subtypes_addlist'][$this->record[$subtypeField]], 1);
			}
		}

			// Return the array
		return array($addElements, $subtypeField);
	}

	/**
	 * Merges the current [types][showitem] array with the array of fields to add for the current subtype field of the "type" value.
	 *
	 * @param	array		A [types][showitem] list of fields, exploded by ","
	 * @param	array		The output from getFieldsToAdd()
	 * @return	array		Return the modified $fields array.
	 * @see getMainFields(),getFieldsToAdd()
	 */
	protected function mergeFieldsWithAddedFields($fields,$fieldsToAdd)	{
		if (count($fieldsToAdd[0]))	{
			reset($fields);
			$c=0;
			while(list(,$fieldInfo)=each($fields))	{
				$parts = explode(';',$fieldInfo);
				if (!strcmp(trim($parts[0]),$fieldsToAdd[1]))	{
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

	/**
	 * Renders the form; this function is the successor of the old
	 * t3lib_tceforms::getSoloField()/getMainFields()/...
	 *
	 *
	 */
	public function render() {
			// Hook: getMainFields_preProcess (requested by Thomas Hempel for use with the "dynaflex" extension)
		/*foreach ($this->hookObjectsMainFields as $hookObj)	{
			if (method_exists($hookObj,'getMainFields_preProcess'))	{
				$hookObj->getMainFields_preProcess($table,$row,$this);
			}
		}*/
//die('hier');
			// check if there are dividers in this form and if yes, create tabs
		if (count(preg_grep('/--div--/', $this->fieldList)) > 0) { // && $this->enableTabMenu && $dividers2Tabs
			$this->useTabs = true;

			if (isset($this->fieldList[0]) && strpos($this->fieldList[0], '--div--') !== 0) {
				$tabIdentString = 'TCEforms:'.$table.':'.$row['uid'];
				$tabIdentStringMD5 = $GLOBALS['TBE_TEMPLATE']->getDynTabMenuId($tabIdentString);

				$this->currentTab = $this->createTabObject($tabIdentStringMD5.'-1', $this->getLL('l_generalTab'));
				$this->addFormFieldObject($this->currentTab);
			}
		}

		$tabCounter = 1;
		foreach ($this->fieldList as $fieldInfo) {
			// Exploding subparts of the field configuration:
			$parts = explode(';', $fieldInfo);

				// Getting the style information out:
			/* TODO: Make this work again.
			$color_style_parts = t3lib_div::trimExplode('-',$parts[4]);
			if (strcmp($color_style_parts[0], ''))	{
				$this->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])]);
			}
			if (strcmp($color_style_parts[1], ''))	{
				$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])];
				if (!isset($this->fieldStyle)) {
					$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
				}
			}
			if (strcmp($color_style_parts[2], ''))	{
				$this->wrapBorder($out_array[$out_sheet],$out_pointer);
				$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])];
				if (!isset($this->borderStyle)) {
					$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
				}
			}*/

			$theField = $parts[0];
			if ($this->isExcludeElement($theField))	{
				continue;
			}

			if ($this->tableTCAconfig['columns'][$theField]) {
				// ToDo: Handle field configuration here.
				$formFieldObject = $this->getSingleField($this->table, $theField, $this->record);

				$this->addFormFieldObject($formFieldObject);
			} elseif ($theField == '--div--') {
				++$tabCounter;

				$tabObject = $this->createTabObject($tabIdentStringMD5.'-'.$tabCounter, $this->sL($parts[1]));
				$this->addFormFieldObject($tabObject);
				$this->currentTab = $tabObject;
			}
		}
	}

	/**
	 * Returns the object representation for a database table field.
	 *
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	array		The record to edit from the database table.
	 * @param	string		Alternative field name label to show.
	 * @param	boolean		Set this if the field is on a palette (in top frame), otherwise not. (if set, field will render as a hidden field).
	 * @param	string		The "extra" options from "Part 4" of the field configurations found in the "types" "showitem" list. Typically parsed by $this->getSpecConfFromString() in order to get the options as an associative array.
	 * @param	integer		The palette pointer.
	 * @return	t3lib_TCEforms_AbstractElement
	 */
	// TODO: remove the extra parameters/use them if neccessary
	function getSingleField($table,$field,$row,$altName='',$palette=0,$extra='',$pal=0)	{
		global $TCA,$BE_USER;

		$fieldConf = $this->tableTCAconfig['columns'][$field];
		$fieldConf['config']['form_type'] = $fieldConf['config']['form_type'] ? $fieldConf['config']['form_type'] : $fieldConf['config']['type'];	// Using "form_type" locally in this script

		switch($fieldConf['config']['form_type'])	{
			case 'input':
			case 'text':
			case 'check':
			case 'radio':
			case 'select':
			case 'group':
			case 'user':
			case 'flex':
				$elementObject = $this->elementObjectFactory($fieldConf['config']['form_type']);
				$elementObject->setTCEformsObject($this->TCEformsObject);
				$elementObject->init($table, $field, $row, $fieldConf, $altName, $palette, $extra, $pal);
			break;
			case 'inline':
				//$item = $this->inline->getSingleField_typeInline($table,$field,$row,$PA);
			break;
			case 'none':
				//$item = $this->getSingleField_typeNone($table,$field,$row,$PA);
			break;
			default:
				//$item = $this->getSingleField_typeUnknown($table,$field,$row,$PA);
			break;
		}

		return $elementObject;
	}

	/**
	 * Factory method for
	 *
	 * @param  string  $type  The type of record to create - directly taken from TCA
	 * @return t3lib_TCEforms_AbstractElement  The element object
	 */
	protected function elementObjectFactory($type) {
		switch ($type) {
			default:
				$className = 't3lib_TCEforms_'.$type.'Element';
				break;
		}

		if (!class_exists($className)) {
			include_once PATH_t3lib.'tceforms/class.'.strtolower($className).'.php';
		}

		return t3lib_div::makeInstance($className);
	}

	protected function addFormFieldObject(t3lib_TCEforms_Element $formFieldObject) {
			// if we are using tabs, add the element to the current tab
		if ($this->useTabs == true && !($formFieldObject instanceof t3lib_TCEforms_Tab)) {
			$this->currentTab->addChildObject($formFieldObject);
		} else {
			$this->formFieldObjects[] = $formFieldObject;
		}
	}

	public function isExcludeElement($elementName) {
		// TODO: check $elementName against array of excluded elements
		return t3lib_div::inArray($this->excludeElements, $elementName);
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
	 * @return	void
	 */
	protected function setExcludeElements() {
		global $TCA;

			// Init:
		$this->excludeElements = array();

			// If a subtype field is defined for the type
		if ($this->tableTCAconfig['types'][$this->typeNumber]['subtype_value_field'])	{
			$subtypeField = $this->tableTCAconfig['types'][$this->typeNumber]['subtype_value_field'];
			if (trim($this->tableTCAconfig['types'][$this->typeNumber]['subtypes_excludelist'][$this->record[$subtypeField]]))	{
				$this->excludeElements=t3lib_div::trimExplode(',',$this->tableTCAconfig['types'][$this->typeNumber]['subtypes_excludelist'][$this->record[$subtypeField]],1);
			}
		}

			// If a bitmask-value field has been configured, then find possible fields to exclude based on that:
		if ($this->tableTCAconfig['types'][$this->typeNumber]['bitmask_value_field'])	{
			$subtypeField = $this->tableTCAconfig['types'][$this->typeNumber]['bitmask_value_field'];
			$subtypeValue = t3lib_div::intInRange($this->record[$subtypeField],0);
			if (is_array($this->tableTCAconfig['types'][$this->typeNumber]['bitmask_excludelist_bits']))	{
				reset($this->tableTCAconfig['types'][$this->typeNumber]['bitmask_excludelist_bits']);
				while(list($bitKey,$eList)=each($this->tableTCAconfig['types'][$this->typeNumber]['bitmask_excludelist_bits']))	{
					$bit=substr($bitKey,1);
					if (t3lib_div::testInt($bit))	{
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
	 * Calculate the current "types" pointer value for the record this form is instantiated for
	 *
	 * Sets $this->typeNumber to the types pointer value.
	 *
	 * @return	void
	 */
	protected function setRecordTypeNumber()	{
		global $TCA;

			// If there is a "type" field configured...
		if ($this->tableTCAconfig['ctrl']['type'])	{
			$typeFieldName = $this->tableTCAconfig['ctrl']['type'];
			$this->typeNumber=$this->record[$typeFieldName];	// Get value of the row from the record which contains the type value.
			if (!strcmp($this->typeNumber,''))	$this->typeNumber = 0;			// If that value is an empty string, set it to "0" (zero)
		} else {
			$this->typeNumber = 0;	// If no "type" field, then set to "0" (zero)
		}

		$this->typeNumber = (string)$this->typeNumber;		// Force to string. Necessary for eg '-1' to be recognized as a type value.
		if (!$this->tableTCAconfig['types'][$this->typeNumber])	{	// However, if the type "0" is not found in the "types" array, then default to "1" (for historical reasons)
			$this->typeNumber = 1;
		}
	}

	public function __set($key, $value) {
			// DANGEROUS, may be used to overwrite *EVERYTHING* in this class! Should be secured later on
		if (substr($key, 0, 3) == 'set') {
			$varKey = substr($key, 3);
			$this->$varKey = $value;
		}
	}

	protected function createTabObject($tabIdentString, $header) {
		$tabObject = new t3lib_TCEforms_Tab;
		$tabObject->init($tabIdentString, $header);

		return $tabObject;
	}

	/**
	 * Fetches language label for key
	 *
	 * @param	string		Language label reference, eg. 'LLL:EXT:lang/locallang_core.php:labels.blablabla'
	 * @return	string		The value of the label, fetched for the current backend language.
	 */
	protected function sL($str)	{
		return $GLOBALS['LANG']->sL($str);
	}

	/**
	 * Returns language label from locallang_core.php
	 * Labels must be prefixed with either "l_" or "m_".
	 * The prefix "l_" maps to the prefix "labels." inside locallang_core.php
	 * The prefix "m_" maps to the prefix "mess." inside locallang_core.php
	 *
	 * @param	string		The label key
	 * @return	string		The value of the label, fetched for the current backend language.
	 */
	protected function getLL($str)	{
		$content = '';

		switch(substr($str, 0, 2))	{
			case 'l_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.' . substr($str,2));
			break;
			case 'm_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.' . substr($str,2));
			break;
		}
		return $content;
	}

	public function setTCEformsObject(t3lib_TCEforms $TCEformsObject) {
		$this->TCEformsObject = $TCEformsObject;
	}
}

?>