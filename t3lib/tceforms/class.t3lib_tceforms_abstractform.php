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
	 * @var array  All tabs directly "owned" by this form (=> does not include sub-tabs of these tabs)
	 */
	protected $tabs = array();

	/**
	 * @var t3lib_TCEforms_Tab  The currently used tab
	 */
	protected $currentTab;

	/**
	 * May be safely removed as soon as all dependencies on the old TCEforms are removed!
	 *
	 * @var t3lib_TCEforms
	 */
	protected $TCEformsObject;

	protected $templateFile;
	protected $templateContent;


	protected $additionalCode_pre = array();			// Additional HTML code, printed before the form.
	protected $additionalJS_pre = array();			// Additional JavaScript, printed before the form
	protected $additionalJS_post = array();			// Additional JavaScript printed after the form
	protected $additionalJS_submit = array();			// Additional JavaScript executed on submit; If you set "OK" variable it will raise an error about RTEs not being loaded and offer to block further submission.

	/**
	 * @var array  The JavaScript code to add to various parts of the form. Contains e.g. the following
	 *             keys: evaluation, submit, pre (before the form), post (after the form)
	 */
	protected $JScode;

	protected $formName;
	protected $prependFormFieldNames;

	protected $hiddenFields = array();

	/**
	 * @var array  HTML code rendered for fields which are marked as hidden. Previously called hiddenFieldAccum
	 */
	protected $hiddenFields_HTMLcode = array();

	protected $backPath;



	/**
	 * The constructor of this class
	 *
	 * @param  string   $table
	 * @param  array    $row
	 * @param  integer  $typeNumber
	 */
	public function __construct($table, $row) {
		global $TCA;

		$this->setTemplateFile(PATH_typo3 . 'templates/tceforms.html');


			// TODO: Refactor this!
		$this->prependFormFieldNames = 'data';
		$this->formName = 'editform';
		//$this->setNewBEDesign();
		$this->docLarge = $GLOBALS['BE_USER']->uc['edit_wideDocument'] ? 1 : 0;
		$this->edit_showFieldHelp = $GLOBALS['BE_USER']->uc['edit_showFieldHelp'];

		$this->edit_docModuleUpload = $GLOBALS['BE_USER']->uc['edit_docModuleUpload'];
		$this->titleLen = $GLOBALS['BE_USER']->uc['titleLen'];		// @deprecated

		//$this->inline->init($this);


		$this->requiredFields = array();


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
	 * @return  array  An array containing two values: 1) Another array containing fieldnames to add and 2) the subtype value field.
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
	 * @param   array  A [types][showitem] list of fields, exploded by ","
	 * @param   array  The output from getFieldsToAdd()
	 * @return  array  Return the modified $fields array.
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

			// always create at least one default tab
		if (isset($this->fieldList[0]) && strpos($this->fieldList[0], '--div--') !== 0) {
			$tabIdentString = 'TCEforms:'.$table.':'.$row['uid'];
			$tabIdentStringMD5 = $GLOBALS['TBE_TEMPLATE']->getDynTabMenuId($tabIdentString);

			$this->currentTab = $this->createTabObject($tabIdentStringMD5.'-1', $this->getLL('l_generalTab'));
		}

		$tabCounter = 1;
		foreach ($this->fieldList as $fieldInfo) {
			// Exploding subparts of the field configuration:
			$parts = explode(';', $fieldInfo);


			$theField = $parts[0];
			if ($this->isExcludeElement($theField))	{
				continue;
			}

			if ($this->tableTCAconfig['columns'][$theField]) {
				// TODO: Handle field configuration here.
				$formFieldObject = $this->getSingleField($this->table, $theField, $this->record, $parts[1], 0, $parts[3], $parts[2]);
				$this->currentTab->addChildObject($formFieldObject);


					// Getting the style information out:
				// TODO: Make this really object oriented
				$color_style_parts = t3lib_div::trimExplode('-',$parts[4]);
				if (strcmp($color_style_parts[0], ''))	{
					$formFieldObject->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])]);
				}
				if (strcmp($color_style_parts[1], ''))	{
					$formFieldObject->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])];
					if (!isset($this->fieldStyle)) {
						$formFieldObject->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
					}
				}
				if (strcmp($color_style_parts[2], ''))	{
					$formFieldObject->_wrapBorder = true;
					$formFieldObject->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])];
					if (!isset($this->borderStyle)) {
						$formFieldObject->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
					}
				}
			} elseif ($theField == '--div--') {
				++$tabCounter;

				$tabObject = $this->createTabObject($tabIdentStringMD5.'-'.$tabCounter, $this->sL($parts[1]));
				$this->currentTab = $tabObject;
			} // TODO: add top-level palette handling!
		}

		$tabContents = array();

		$c = 0;
		foreach ($this->tabs as $tabObject) {
			++$c;
			$tabContents[$c] = array(
				'newline' => false, // TODO: make this configurable again
				'label' => $tabObject->getHeader(),
				'content' => $tabObject->render()
			);
		}
		if (count($tabContents) > 1) {
			$content = $this->getDynTabMenu($tabContents, $tabIdentString);
		} else {
			$content = $tabContents[1]['content'];
		}
			// TODO: move the wrap to alt_doc.php
		return $this->wrapTotal($content);
	}

	/**
	 * Returns the object representation for a database table field.
	 *
	 * @param   string   $table    The table name
	 * @param   string   $field    The field name
	 * @param   array    $row      The record to edit from the database table.
	 * @param   string   $altName  Alternative field name label to show.
	 * @param   boolean  $palette  Set this if the field is on a palette (in top frame), otherwise not. (if set, field will render as a hidden field).
	 * @param   string   $extra    The "extra" options from "Part 4" of the field configurations found in the "types" "showitem" list. Typically parsed by $this->getSpecConfFromString() in order to get the options as an associative array.
	 * @param   integer  $pal      The palette pointer.
	 * @return  t3lib_TCEforms_AbstractElement
	 */
	// TODO: remove the extra parameters/use them if neccessary
	function getSingleField($table,$field,$row,$altName='',$palette=0,$extra='',$pal=0)	{
		global $TCA,$BE_USER;

		$fieldConf = $this->tableTCAconfig['columns'][$field];
		$fieldConf['config']['form_type'] = $fieldConf['config']['form_type'] ? $fieldConf['config']['form_type'] : $fieldConf['config']['type'];	// Using "form_type" locally in this script

		switch($fieldConf['config']['form_type'])	{
			case 'inline':
				//$item = $this->inline->getSingleField_typeInline($table,$field,$row,$PA);

				break;

			case 'input':
			case 'text':
			case 'check':
			case 'radio':
			case 'select':
			case 'group':
			case 'user':
			case 'flex':
			case 'none':
			default:
				$elementObject = $this->elementObjectFactory($fieldConf['config']['form_type']);
					// don't set the containing tab here because we can't be sure if this item
					// will be attached to $this->currentTab
				$elementObject->setTCEformsObject($this->TCEformsObject);
				$elementObject->set_TCEformsObject($this);
				$elementObject->init($table, $field, $row, $fieldConf, $altName, $palette, $extra, $pal, $this);

				break;
		}

		return $elementObject;
	}

	public function getPaletteField($field) {

	}

	/**
	 * Factory method for form element objects. Defaults to type "unknown" if the class(file)
	 * is not found.
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
				// if class(file) does not exist, resolve to type "unknown"
			if (!@file_exists(PATH_t3lib.'tceforms/class.'.strtolower($className).'.php')) {
				return $this->elementObjectFactory('unknown');
			}
			include_once PATH_t3lib.'tceforms/class.'.strtolower($className).'.php';
		}

		return t3lib_div::makeInstance($className);
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
	 * Returns the "special" configuration of an "extra" string (non-parsed)
	 *
	 * @param  string  The "Part 4" of the fields configuration in "types" "showitem" lists.
	 * @param  string  The ['defaultExtras'] value from field configuration
	 * @return array   An array with the special options in.
	 * @see getSpecConfForField(), t3lib_BEfunc::getSpecConfParts()
	 */
	function getSpecConfFromString($extraString, $defaultExtras)    {
		return t3lib_BEfunc::getSpecConfParts($extraString, $defaultExtras);
	}

	/**
	 * Sets an field's status to "hidden", thus not displaying it visibly on the form
	 *
	 * @param mixed  $fieldName  The fieldname to exclude. May also be an array of fieldnames.
	 */
	public function setHiddenField($fieldName) {
		if (is_array($fieldName)) {
			$this->hiddenFields = t3lib_div::array_merge($this->hiddenFields, $fieldName);
		} else {
			$this->hiddenFields[] = $fieldName;
		}
	}

	/**
	 * Adds HTML code for a hidden form field to the form
	 *
	 * WARNING: You may only add code for a field once. The second time you try to "add" code
	 *          for this field, the first code will be overwritten!
	 *
	 * @param string  $formFieldName  The fieldname for which the HTML is added.
	 * @param string  $code  The complete HTML to render for the field
	 */
	public function addHiddenFieldHTMLCode($formFieldName, $code) {
		$this->hiddenFields_HTMLcode[$formFieldName] = $code;
	}

	/**
	 * Calculate the current "types" pointer value for the record this form is instantiated for
	 *
	 * Sets $this->typeNumber to the types pointer value.
	 *
	 * @return void
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

	/**
	 * Generic setter function
	 *
	 * @param string  $key    The var name to (over)write
	 * @param mixed   $value  The value to write to the variable
	 */
	public function __set($key, $value) {
			// DANGEROUS, may be used to overwrite *EVERYTHING* in this class! Should be secured later on
		$this->$key = $value;
	}

	/**
	 * Generic getter function, will be called by PHP if the given var is protected/private or does
	 * not exist at all. The latter case will fail at the moment, as the function does no mapping of
	 * non-existing keys at the moment.
	 *
	 * @param  string  $key  The name of the variable to return
	 * @return array
	 */
	public function __get($key) {
		// TODO: implement access check based on whitelist
		return $this->$key;
	}

	/**
	 * Factory method for tab objects on forms.
	 *
	 * @param   string  $tabIdentString  The identifier of the tab. Must be unique for the whole form
	 *                                   (and all sub-forms!)
	 * @param   string  $header  The caption of the form
	 * @return  t3lib_TCEforms_Tab
	 */
	protected function createTabObject($tabIdentString, $header) {
		$tabObject = new t3lib_TCEforms_Tab;
		$tabObject->init($tabIdentString, $header, $this);

		$this->tabs[] = $tabObject;

		return $tabObject;
	}

	/**
	 * Fetches language label for key
	 *
	 * @param   string  Language label reference, eg. 'LLL:EXT:lang/locallang_core.php:labels.blablabla'
	 * @return  string  The value of the label, fetched for the current backend language.
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
	 * @param   string  The label key
	 * @return  string  The value of the label, fetched for the current backend language.
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

	/**
	 * Sets the (old) TCEforms-object used by this form.
	 *
	 * @param  t3lib_TCEforms $TCEformsObject
	 * @deprecated  since 4.2
	 */
	public function setTCEformsObject(t3lib_TCEforms $TCEformsObject) {
		$this->TCEformsObject = $TCEformsObject;
	}

	/**
	 * Sets the global status of all palettes to collapsed/uncollapsed
	 *
	 * @param  boolean  $collapsed
	 */
	public function setPalettesCollapsed($collapsed) {
		$this->palettesCollapsed = (bool)$collapsed;
	}

	/**
	 * Returns whether or not the palettes are collapsed
	 *
	 * @return boolean
	 */
	public function getPalettesCollapsed() {
		return $this->palettesCollapsed;
	}



	/********************************************
	 *
	 * Template functions
	 *
	 ********************************************/

	/**
	 * Sets the path to the template file. Also automatically loads the contents of this file.
	 * It may be accessed via getTemplateContent()
	 *
	 * @param  string  $filePath
	 */
	public function setTemplateFile($filePath) {
		$filePath = t3lib_div::getFileAbsFileName($filePath);

		if (!@file_exists($filePath)) {
			die('Template file <em>'.$filePath.'</em> does not exist. [1216911730]');
		}

		$this->templateContent = file_get_contents($filePath);
	}

	public function getTemplateContent() {
		return $this->templateContent;
	}

	/**
	 * Create dynamic tab menu
	 *
	 * @param	array		Parts for the tab menu, fed to template::getDynTabMenu()
	 * @param	string		ID string for the tab menu
	 * @param	integer		If set to '1' empty tabs will be removed, If set to '2' empty tabs will be disabled
	 * @return	string		HTML for the menu
	 */
	protected function getDynTabMenu($parts, $idString, $dividersToTabsBehaviour = 1) {
		if (is_object($GLOBALS['TBE_TEMPLATE'])) {
			return $GLOBALS['TBE_TEMPLATE']->getDynTabMenu($parts, $idString, 0, false, 50, 1, false, 1, $dividersToTabsBehaviour);
		} else {
			$output = '';
			foreach($parts as $singlePad)	{
				$output.='
				<h3>'.htmlspecialchars($singlePad['label']).'</h3>
				'.($singlePad['description'] ? '<p class="c-descr">'.nl2br(htmlspecialchars($singlePad['description'])).'</p>' : '').'
				'.$singlePad['content'];
			}

			return '<div class="typo3-dyntabmenu-divs">'.$output.'</div>';
		}
	}

	/**
	 * Wraps all the table rows into a single table.
	 * Used externally from scripts like alt_doc.php and db_layout.php (which uses TCEforms...)
	 *
	 * @param	string		Code to output between table-parts; table rows
	 * @param	array		The record
	 * @param	string		The table name
	 * @return	string
	 */
	public function wrapTotal($content) {
		$wrap = t3lib_parsehtml::getSubpart($this->templateContent, '###TOTAL_WRAP###');
		$content = $this->replaceTableWrap($wrap, $content);
		return $content.implode('', $this->hiddenFields_HTMLcode);
	}

	/**
	 * This replaces markers in the total wrap
	 *
	 * @param   array    An array of template parts containing some markers.
	 * @param   array    The record
	 * @param   string   The table name
	 * @return  string
	 */
	function replaceTableWrap($wrap, $content)	{
		global $TCA;

			// Make "new"-label
		if (strstr($this->record['uid'],'NEW'))	{
			$newLabel = ' <span class="typo3-TCEforms-newToken">'.
						$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.new',1).
						'</span>';

			#t3lib_BEfunc::fixVersioningPid($this->table,$this->record);	// Kasper: Should not be used here because NEW records are not offline workspace versions...
			$truePid = t3lib_BEfunc::getTSconfig_pidValue($this->table,$this->record['uid'],$this->record['pid']);
			$prec = t3lib_BEfunc::getRecordWSOL('pages',$truePid,'title');
			$rLabel = '<em>[PID: '.$truePid.'] '.htmlspecialchars(trim(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle('pages',$prec),40))).'</em>';
		} else {
			$newLabel = ' <span class="typo3-TCEforms-recUid">['.$this->record['uid'].']</span>';
			$rLabel  = htmlspecialchars(trim(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($this->table,$this->record),40)));
		}

		$titleA = t3lib_BEfunc::titleAltAttrib($this->TCEformsObject->getRecordPath($this->table,$this->record));

		$markerArray = array(
			'###ID_NEW_INDICATOR###' => $newLabel,
			'###RECORD_LABEL###' => $rLabel,
			'###TABLE_TITLE###' => htmlspecialchars($this->sL($TCA[$this->table]['ctrl']['title'])),

			'###RECORD_ICON###' => t3lib_iconWorks::getIconImage($this->table,$this->record,$this->backPath,'class="absmiddle"'.$titleA),
			'###WRAP_CONTENT###' => $content
		);

		$wrap = t3lib_parsehtml::substituteMarkerArray($wrap, $markerArray);

		return $wrap;
	}

	/********************************************
	 *
	 * JavaScript related functions
	 *
	 ********************************************/

	/**
	 * Adds JavaScript code for form field evaluation. Used to be the global var <extJSCode in old t3lib_TCEforms
	 *
	 * @param string $JScode
	 */
	public function addToEvaluationJS($JScode) {
		$this->JScode['evaluation'] .= $JScode;
	}

	/**
	 * JavaScript code added BEFORE the form is drawn:
	 *
	 * @return	string		A <script></script> section with JavaScript.
	 */
	function JStop()	{

		$out = '';

			// Additional top HTML:
		if (count($this->additionalCode_pre))	{
			$out.= implode('

				<!-- NEXT: -->
			',$this->additionalCode_pre);
		}

			// Additional top JavaScript
		if (count($this->additionalJS_pre))	{
			$out.='


		<!--
			JavaScript in top of page (before form):
		-->

		<script type="text/javascript">
			/*<![CDATA[*/

			'.implode('

				// NEXT:
			',$this->additionalJS_pre).'

			/*]]>*/
		</script>
			';
		}

			// Return result:
		return $out;
	}

	/**
	 * JavaScript code used for input-field evaluation.
	 *
	 * 		Example use:
	 *
	 * 		$msg.='Distribution time (hh:mm dd-mm-yy):<br /><input type="text" name="send_mail_datetime_hr" onchange="typo3form.fieldGet(\'send_mail_datetime\', \'datetime\', \'\', 0,0);"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' /><input type="hidden" value="'.time().'" name="send_mail_datetime" /><br />';
	 * 		$this->extJSCODE.='typo3form.fieldSet("send_mail_datetime", "datetime", "", 0,0);';
	 *
	 * 		... and then include the result of this function after the form
	 *
	 * @param	string		$formname: The identification of the form on the page.
	 * @param	boolean		$update: Just extend/update existing settings, e.g. for AJAX call
	 * @return	string		A section with JavaScript - if $update is false, embedded in <script></script>
	 */
	function JSbottom($formname='forms[0]', $update = false)	{
		$jsFile = array();
		$elements = array();

			// required:
		foreach ($this->tabs as $tab) {
			foreach ($tab->getRequiredFields() as $itemImgName => $itemName) {
				$match = array();
				if (preg_match('/^(.+)\[((\w|\d|_)+)\]$/', $itemName, $match)) {
					$record = $match[1];
					$field = $match[2];
					$elements[$record][$field]['required'] = 1;
					$elements[$record][$field]['requiredImg'] = $itemImgName;
					if (isset($this->requiredAdditional[$itemName]) && is_array($this->requiredAdditional[$itemName])) {
						$elements[$record][$field]['additional'] = $this->requiredAdditional[$itemName];
					}
				}
			}
				// range:
			foreach ($tab->getRequiredElements() as $itemName => $range) {
				if (preg_match('/^(.+)\[((\w|\d|_)+)\]$/', $itemName, $match)) {
					$record = $match[1];
					$field = $match[2];
					$elements[$record][$field]['range'] = array($range[0], $range[1]);
					$elements[$record][$field]['rangeImg'] = $range['imgName'];
				}
			}
		}

		$this->TBE_EDITOR_fieldChanged_func='TBE_EDITOR.fieldChanged_fName(fName,formObj[fName+"_list"]);';

		if (!$update) {
			if ($this->loadMD5_JS) {
				$jsFile[] =	'<script type="text/javascript" src="'.$this->backPath.'md5.js"></script>';
			}

			$jsFile[] = '<script type="text/javascript" src="'.$this->backPath.'contrib/prototype/prototype.js"></script>';
			$jsFile[] = '<script type="text/javascript" src="'.$this->backPath.'contrib/scriptaculous/scriptaculous.js"></script>';
			$jsFile[] =	'<script type="text/javascript" src="'.$this->backPath.'../t3lib/jsfunc.evalfield.js"></script>';
			$jsFile[] =	'<script type="text/javascript" src="'.$this->backPath.'jsfunc.tbe_editor.js"></script>';
			$jsFile[] =	'<script type="text/javascript" src="'.$this->backPath.'js/tceforms.js"></script>';

				// if IRRE fields were processed, add the JavaScript functions:
			if ($this->inline->inlineCount) {
				$jsFile[] = '<script src="'.$this->backPath.'contrib/scriptaculous/scriptaculous.js" type="text/javascript"></script>';
				$jsFile[] = '<script src="'.$this->backPath.'../t3lib/jsfunc.inline.js" type="text/javascript"></script>';
				$out .= '
				inline.setPrependFormFieldNames("'.$this->inline->prependNaming.'");
				inline.setNoTitleString("'.addslashes(t3lib_BEfunc::getNoRecordTitle(true)).'");
				';
			}

				// Toggle icons:
			$toggleIcon_open = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/pil2down.gif','width="12" height="7"').' hspace="2" alt="Open" title="Open" />';
			$toggleIcon_close = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/pil2right.gif','width="7" height="12"').' hspace="2" alt="Close" title="Close" />';

			$out .= '
			var toggleIcon_open = \''.$toggleIcon_open.'\';
			var toggleIcon_close = \''.$toggleIcon_close.'\';

			TBE_EDITOR.images.req.src = "'.t3lib_iconWorks::skinImg($this->backPath,'gfx/required_h.gif','',1).'";
			TBE_EDITOR.images.cm.src = "'.t3lib_iconWorks::skinImg($this->backPath,'gfx/content_client.gif','',1).'";
			TBE_EDITOR.images.sel.src = "'.t3lib_iconWorks::skinImg($this->backPath,'gfx/content_selected.gif','',1).'";
			TBE_EDITOR.images.clear.src = "'.$this->backPath.'clear.gif";

			TBE_EDITOR.auth_timeout_field = '.intval($GLOBALS['BE_USER']->auth_timeout_field).';
			TBE_EDITOR.formname = "'.$formname.'";
			TBE_EDITOR.formnameUENC = "'.rawurlencode($formname).'";
			TBE_EDITOR.backPath = "'.addslashes($this->backPath).'";
			TBE_EDITOR.prependFormFieldNames = "'.$this->prependFormFieldNames.'";
			TBE_EDITOR.prependFormFieldNamesUENC = "'.rawurlencode($this->prependFormFieldNames).'";
			TBE_EDITOR.prependFormFieldNamesCnt = '.substr_count($this->prependFormFieldNames,'[').';
			TBE_EDITOR.isPalettedoc = '.($this->isPalettedoc ? addslashes($this->isPalettedoc) : 'null').';
			TBE_EDITOR.doSaveFieldName = "'.($this->doSaveFieldName ? addslashes($this->doSaveFieldName) : '').'";
			TBE_EDITOR.labels.fieldsChanged = '.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.fieldsChanged')).';
			TBE_EDITOR.labels.fieldsMissing = '.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.fieldsMissing')).';
			TBE_EDITOR.labels.refresh_login = '.$GLOBALS['LANG']->JScharCode($this->getLL('m_refresh_login')).';
			TBE_EDITOR.labels.onChangeAlert = '.$GLOBALS['LANG']->JScharCode($this->getLL('m_onChangeAlert')).';
			evalFunc.USmode = '.($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']?'1':'0').';
			';
		}

			// add JS required for inline fields
		if (count($this->inline->inlineData)) {
			$out .=	'
			inline.addToDataArray('.t3lib_div::array2json($this->inline->inlineData).');
			';
		}
			// Registered nested elements for tabs or inline levels:
		if (count($this->requiredNested)) {
			$out .= '
			TBE_EDITOR.addNested('.t3lib_div::array2json($this->requiredNested).');
			';
		}
			// elements which are required or have a range definition:
		if (count($elements)) {
			$out .= '
			TBE_EDITOR.addElements('.t3lib_div::array2json($elements).');
			TBE_EDITOR.initRequired();
			';
		}
			// $this->additionalJS_submit:
		if ($this->additionalJS_submit) {
			$additionalJS_submit = implode('', $this->additionalJS_submit);
			$additionalJS_submit = str_replace("\r", '', $additionalJS_submit);
			$additionalJS_submit = str_replace("\n", '', $additionalJS_submit);
			$out .= '
			TBE_EDITOR.addActionChecks("submit", "'.addslashes($additionalJS_submit).'");
			';
		}

		$out .= chr(10).implode(chr(10),$this->additionalJS_post).chr(10).$this->JScode['evaluation'];
		$out .= '
			TBE_EDITOR.loginRefreshed();
		';

			// Regular direct output:
		if (!$update) {
			$spacer = chr(10).chr(9);
			$out  = $spacer.implode($spacer, $jsFile).t3lib_div::wrapJS($out);
		}

		return $out;
	}

	/**
	 * Prints necessary JavaScript for TCEforms (after the form HTML).
	 *
	 * @return  string  The JavaScript code
	 */
	public function getBottomJavaScript() {
		$javascript = $this->JSbottom($this->formName).'


			<!--
			 	JavaScript after the form has been drawn:
			-->

			<script type="text/javascript">
				/*<![CDATA[*/

				formObj = document.forms[0]
				backPath = "'.$this->backPath.'";

				function TBE_EDITOR_fieldChanged_func(fName, formObj) {
					'.$this->TCEformsObject->TBE_EDITOR_fieldChanged_func.'
				}

				/*]]>*/
			</script>';

		return $javascript;
	}

	/**
	 * Returns necessary JavaScript for the top
	 *
	 * @return	void
	 */
	function printNeededJSFunctions_top()	{
			// JS evaluation:
		$out = $this->JStop($this->formName);
		return $out;
	}
}

?>