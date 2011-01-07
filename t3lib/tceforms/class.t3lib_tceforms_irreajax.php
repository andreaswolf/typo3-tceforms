<?php

class t3lib_TCEforms_IrreAjax implements t3lib_TCEforms_Element {

	/**
	 * The prefix for form field names
	 * @var string
	 */
	protected $formFieldNamePrefix = 'data';

	protected $inlineStructure = array();

	/**
	 * The TCEforms form object
	 *
	 * @var t3lib_TCEforms_IrreAjaxForm
	 */
	protected $TCEforms;

	protected function init() {

	}

	/**
	 * General processor for AJAX requests concerning IRRE.
	 * (called by typo3/ajax.php)
	 *
	 * @param	array		$params: additional parameters (not used here)
	 * @param	TYPO3AJAX	&$ajaxObj: the TYPO3AJAX object of this request
	 * @return	void
	 */
	public function processAjaxRequest($params, &$ajaxObj) {
		$this->init();

		$ajaxArguments = t3lib_div::_GP('ajax');
		$ajaxIdParts = explode('::', $GLOBALS['ajaxID'], 2);

		if (isset($ajaxArguments) && is_array($ajaxArguments) && count($ajaxArguments)) {
			$ajaxMethod = $ajaxIdParts[1];
			switch ($ajaxMethod) {
				case 'createNewRecord':
				case 'synchronizeLocalizeRecords':
						// Construct runtime environment for Inline Relational Record Editing:
					$this->constructFormContext($ajaxArguments);
						// Parse the DOM identifier (string), add the levels to the structure stack (array) and load the TCA config:
					$this->parseStructureString($ajaxArguments[1], true);

					$this->TCEforms->setFormFieldNamePrefix($this->formFieldNamePrefix);
					$this->TCEforms->setFormFieldIdPrefix($this->formFieldNamePrefix);

					$this->loadContextRecord();

					array_shift($ajaxArguments);
						// Render content:
					$ajaxObj->setContentFormat('jsonbody');
					$ajaxObj->setContent(
						call_user_func_array(array(&$this, $ajaxMethod), $ajaxArguments)
					);
					break;
				case 'setExpandedCollapsedState':
					$ajaxObj->setContentFormat('jsonbody');
					call_user_func_array(array(&$this, $ajaxMethod), $ajaxArguments);
					break;
			}
		}
	}

	/**
	 * Loads the top-most record on the current form that has a direct relation to the record
	 * this script was called from (caller). This is not neccessarily the direct parent of the caller,
	 * as multiple IRRE forms may be nested inside each other.
	 *
	 * @return void
	 */
	protected function loadContextRecord() {
			// Get the outermost level of the IRRE tree - this is where the currently
			// edited record is contained in, perhaps within other inline elements
		$contextLevel = $this->getStructureLevel(0);
		$contextRecord = t3lib_BEfunc::getRecord($contextLevel['table'], $contextLevel['uid']);
		$contextRecordObject = new t3lib_TCEforms_Record($contextLevel['table'], $contextRecord, $GLOBALS['TCA'][$contextLevel['table']]);

		$this->TCEforms->setContextRecordObject($contextRecordObject);
	}

	/**
	 *
	 */
	protected function constructFormContext(&$ajaxArguments) {
		global $SOBE, $BE_USER, $TYPO3_CONF_VARS;

		require_once(PATH_typo3.'template.php');

		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_alt_doc.xml');

			// Create a new anonymous object:
		$SOBE = new stdClass();
		$SOBE->MOD_MENU = array(
			'showPalettes' => '',
			'showDescriptions' => '',
			'disableRTE' => ''
		);
			// Setting virtual document name
		$SOBE->MCONF['name']='xMOD_alt_doc.php';
			// CLEANSE SETTINGS
		$SOBE->MOD_SETTINGS = t3lib_BEfunc::getModuleData(
			$SOBE->MOD_MENU,
			t3lib_div::_GP('SET'),
			$SOBE->MCONF['name']
		);
			// Create an instance of the document template object
		$SOBE->doc = t3lib_div::makeInstance('template');
		$SOBE->doc->backPath = $GLOBALS['BACK_PATH'];
			// Initialize TCEforms (rendering the forms)
		$this->TCEforms = new t3lib_TCEforms_IrreAjaxForm();
		$this->TCEforms->setTemplateFile(PATH_typo3 . 'templates/tceforms.html')
		               ->setContainingElement($this);
		//$SOBE->tceforms->inline =& $this;
		//$SOBE->tceforms->setRTEcounter(intval($ajaxArguments[0]));
		//$SOBE->tceforms->initDefaultBEMode();
		$this->TCEforms->setPalettesCollapsed(!$SOBE->MOD_SETTINGS['showPalettes']);
		$this->TCEforms->setRteEnabled($SOBE->MOD_SETTINGS['disableRTE']);
		//$SOBE->tceforms->enableClickMenu = TRUE;
		//$SOBE->tceforms->enableTabMenu = TRUE;
			// Clipboard is initialized:
		//$SOBE->tceforms->clipObj = t3lib_div::makeInstance('t3lib_clipboard');		// Start clipboard
		//$SOBE->tceforms->clipObj->initializeClipboard();	// Initialize - reads the clipboard content from the user session
			// Setting external variables:
		if ($BE_USER->uc['edit_showFieldHelp']!='text' && $SOBE->MOD_SETTINGS['showDescriptions']) {
			$SOBE->tceforms->edit_showFieldHelp = 'text';
		}
	}

	/**
	 * Processor for AJAX requests setting the expanded/collapsed state of records within the backend
	 * user's record.
	 *
	 * @param	array		$params: additional parameters (not used here)
	 * @param	TYPO3AJAX	&$ajaxObj: the TYPO3AJAX object of this request
	 * @return	void
	 */
	public function setExpandedCollapsedState($domObjectId, $expand, $collapse) {
t3lib_div::devLog('Entered setExpandedCollapsedState', 't3lib_TCEforms_IrreAjax');
			// parse the DOM identifier (string), add the levels to the structure stack (array), but don't load TCA config
		$this->parseStructureString($domObjectId, false);
			// the current table - for this table we should add/import records
		$current = $this->inlineStructure['unstable'];
			// the top parent table - this table embeds the current table
		$top = $this->getStructureLevel(0);
t3lib_div::devLog('inlineStructure: ' . serialize($this->inlineStructure), 't3lib_TCEforms_IrreAjax');
t3lib_div::devLog('top: ' . serialize($top), 't3lib_TCEforms_IrreAjax');
t3lib_div::devLog('this: ' . serialize($this), 't3lib_TCEforms_IrreAjax');

			// only do some action if the top record and the current record were saved before
		if (t3lib_div::testInt($top['uid'])) {
			$inlineView = (array)unserialize($GLOBALS['BE_USER']->uc['inlineView']);
			$inlineViewCurrent =& $inlineView[$top['table']][$top['uid']];

			$expandUids = t3lib_div::trimExplode(',', $expand);
			$collapseUids = t3lib_div::trimExplode(',', $collapse);

				// set records to be expanded
			foreach ($expandUids as $uid) {
				$inlineViewCurrent[$current['table']][] = $uid;
			}
				// set records to be collapsed
			foreach ($collapseUids as $uid) {
				$inlineViewCurrent[$current['table']] = $this->removeFromArray($uid, $inlineViewCurrent[$current['table']]);
			}
t3lib_div::devLog('inlineViewCurrent: ' . serialize($inlineViewCurrent), 't3lib_TCEforms_IrreAjax');
				// save states back to database
			if (is_array($inlineViewCurrent[$current['table']])) {
				$inlineViewCurrent = array_unique($inlineViewCurrent);
				$GLOBALS['BE_USER']->uc['inlineView'] = serialize($inlineView);
				t3lib_div::devLog('Users\'s viewstate: ' . $GLOBALS['BE_USER']->uc['inlineView'], 't3lib_TCEforms_IrreAjax');
				$GLOBALS['BE_USER']->writeUC();
			}
		}
	}

	/**
	 * Convert the DOM object-id of an inline container to an array.
	 * The object-id could look like 'data[inline][tx_mmftest_company][1][employees]'.
	 * The result is written to $this->inlineStructure.
	 * There are two keys:
	 *  - 'stable': Containing full qualified identifiers (table, uid and field)
	 *  - 'unstable': Containting partly filled data (e.g. only table and possibly field)
	 *
	 * @param	string		$domObjectId: The DOM object-id
	 * @param	boolean		$loadConfig: Load the TCA configuration for that level (default: true)
	 * @return	void
	 */
	protected function parseStructureString($string, $loadConfig=true) {
		$unstable = array();
		t3lib_div::devLog('string: ' . $string, __CLASS__);
		$vector = array('table', 'uid', 'field');
		$pattern = '/^(.+?)\[(.+?)\]\[(.+)\]$/';
		if (preg_match($pattern, $string, $match)) {
			$this->formFieldNamePrefix = $match[1];
			$this->inlineFirstPid = $match[2];
			$parts = explode('][', $match[3]);
			$partsCnt = count($parts);
			for ($i = 0; $i < $partsCnt; $i++) {
				if ($i > 0 && $i % 3 == 0) {
						// load the TCA configuration of the table field and store it in the stack
					if ($loadConfig) {
						t3lib_div::loadTCA($unstable['table']);
						$unstable['config'] = $GLOBALS['TCA'][$unstable['table']]['columns'][$unstable['field']]['config'];
							// Fetch TSconfig:
						$TSconfig = t3lib_TCEforms_Form::getTSconfig(
							$unstable['table'],
							array('uid' => $unstable['uid'], 'pid' => $this->inlineFirstPid),
							$unstable['field']
						);
							// Override TCA field config by TSconfig:
						if (!$TSconfig['disabled']) {
							$unstable['config'] = self::overrideFieldConf($unstable['config'], $TSconfig);
						}
						$unstable['localizationMode'] = t3lib_BEfunc::getInlineLocalizationMode($unstable['table'], $unstable['config']);
					}
					$this->inlineStructure['stable'][] = $unstable;
					$unstable = array();
				}
				$unstable[$vector[$i % 3]] = $parts[$i];
			}
			$this->updateStructureNames();
			if (count($unstable)) $this->inlineStructure['unstable'] = $unstable;
		}
	}

	/**
	 * Overrides the TCA field configuration by TSconfig settings.
	 *
	 * Example TSconfig: TCEform.<table>.<field>.config.appearance.useSortable = 1
	 * This overrides the setting in $TCA[<table>]['columns'][<field>]['config']['appearance']['useSortable'].
	 *
	 * @param	array		$fieldConfig: TCA field configuration
	 * @param	array		$TSconfig: TSconfig
	 * @return	array		Changed TCA field configuration
	 *
	 * TODO Remove duplication (this function also exists in Element_Abstract, but it is non-static and directly
	 *      changes $this->fieldSetup)
	 */
	protected static function overrideFieldConf($fieldConfig, $TSconfig) {
		if (is_array($TSconfig)) {
			$TSconfig = t3lib_div::removeDotsFromTS($TSconfig);
			$type = $fieldConfig['type'];
			if (is_array($TSconfig['config']) && is_array($this->allowOverrideMatrix[$type])) {
					// Check if the keys in TSconfig['config'] are allowed to override TCA field config:
				foreach (array_keys($TSconfig['config']) as $key) {
					if (!in_array($key, $this->allowOverrideMatrix[$type], true)) {
						unset($TSconfig['config'][$key]);
					}
				}
					// Override TCA field config by remaining TSconfig['config']:
				if (count($TSconfig['config'])) {
					$fieldConfig = t3lib_div::array_merge_recursive_overrule($fieldConfig, $TSconfig['config']);
				}
			}
		}

		return $fieldConfig;
	}

	/**
	 * Get a level from the stack and return the data.
	 * If the $level value is negative, this function works top-down,
	 * if the $level value is positive, this function works bottom-up.
	 *
	 * @param	integer		$level: Which level to return
	 * @return	array		The item of the stack at the requested level
	 */
	protected function getStructureLevel($level) {
		$inlineStructureCount = count($this->inlineStructure['stable']);
		if ($level < 0) $level = $inlineStructureCount+$level;
		if ($level >= 0 && $level < $inlineStructureCount)
			return $this->inlineStructure['stable'][$level];
		else
			return false;
	}

	/**
	 * Remove an element from an array.
	 *
	 * @param	mixed		$needle: The element to be removed.
	 * @param	array		$haystack: The array the element should be removed from.
	 * @param	mixed		$strict: Search elements strictly.
	 * @return	array		The array $haystack without the $needle
	 */
	protected function removeFromArray($needle, $haystack, $strict=null) {
		$pos = array_search($needle, $haystack, $strict);
		if ($pos !== false) unset($haystack[$pos]);
		return $haystack;
	}


	/**
	 * For common use of DOM object-ids and form field names of a several inline-level,
	 * these names/identifiers are preprocessed and set to $this->inlineNames.
	 * This function is automatically called if a level is pushed to or removed from the
	 * inline structure stack.
	 *
	 * @return	void
	 */
	protected function updateStructureNames() {
		$current = $this->getStructureLevel(-1);
			// if there are still more inline levels available
		if ($current !== false) {
			$lastItemName = $this->getStructureItemName($current);
			$this->inlineNames = array(
				'form' => $this->prependFormFieldNames . $lastItemName,
				'object' => $this->formFieldNamePrefix.'['.$this->inlineFirstPid.']'.$this->getStructurePath(),
			);
			// if there are no more inline levels available
		} else {
			$this->inlineNames = array();
		}
	}


	/**
	 * Get the identifiers of a given depth of level, from the top of the stack to the bottom.
	 * An identifier consists looks like [<table>][<uid>][<field>].
	 *
	 * @param	integer		$structureDepth: How much levels to output, beginning from the top of the stack
	 * @return	string		The path of identifiers
	 */
	public function getStructurePath($structureDepth = -1) {
		$structureCount = count($this->inlineStructure['stable']);
		if ($structureDepth < 0 || $structureDepth > $structureCount) $structureDepth = $structureCount;

		for ($i = 1; $i <= $structureDepth; $i++) {
			$current = $this->getStructureLevel(-$i);
			$string = $this->getStructureItemName($current).$string;
		}

		return $string;
	}

	/**
	 * Create a name/id for usage in HTML output of a level of the structure stack.
	 *
	 * @param	array		$levelData: Array of a level of the structure stack (containing the keys table, uid and field)
	 * @return	string		The name/id of that level, to be used for HTML output
	 */
	protected function getStructureItemName($levelData) {
		if (is_array($levelData)) {
			$name =	'['.$levelData['table'].']' .
					'['.$levelData['uid'].']' .
					(isset($levelData['field']) ? '['.$levelData['field'].']' : '');
		}
		return $name;
	}

	/**
	 * Handle AJAX calls to show a new inline-record of the given table.
	 * Normally this method is never called from inside TYPO3. Always from outside by AJAX.
	 *
	 * @param	string		$domObjectId: The calling object in hierarchy, that requested a new record.
	 * @param	string		$foreignUid: If set, the new record should be inserted after that one.
	 * @return	array		An array to be used for JSON
	 */
	function createNewRecord($domObjectId, $foreignUid = 0) {
			// the current table - for this table we should add/import records
		$current = $this->inlineStructure['unstable'];
			// the parent table - this table embeds the current table
		$parent = $this->getStructureLevel(-1);
			// get TCA 'config' of the parent table
		if (!$this->TCEforms->checkConfiguration($parent['config'])) {
			return t3lib_TCEforms_Irre::getErrorMessageForAJAX('Wrong configuration in table ' . $parent['table']);
		}
		$config = $parent['config'];
t3lib_div::devLog('current: ' . serialize($current), 't3lib_TCEforms_IrreAjax');
t3lib_div::devLog('parent: ' . serialize($parent), 't3lib_TCEforms_IrreAjax');
t3lib_div::devLog('foreignUid: ' . $foreignUid, 't3lib_TCEforms_IrreAjax');

		$collapseAll = (isset($config['appearance']['collapseAll']) && $config['appearance']['collapseAll']);
		$expandSingle = (isset($config['appearance']['expandSingle']) && $config['appearance']['expandSingle']);

			// Put the current level also to the dynNestedStack of TCEforms:
		//$this->fObj->pushToDynNestedStack('inline', $this->inlineNames['object']);

			// dynamically create a new record using t3lib_transferData
		if (!$foreignUid || !t3lib_div::testInt($foreignUid) || $config['foreign_selector']) {
			$record = $this->getNewRecord($this->inlineFirstPid, $current['table']);
				// Set language of new child record to the language of the parent record:
			if ($config['localizationMode'] == 'select') {
				$parentRecord = $this->getRecord(0, $parent['table'], $parent['uid']);
				$parentLanguageField = $GLOBALS['TCA'][$parent['table']]['ctrl']['languageField'];
				$childLanguageField = $GLOBALS['TCA'][$current['table']]['ctrl']['languageField'];
				if ($parentRecord[$languageField]>0) {
					$record[$childLanguageField] = $parentRecord[$languageField];
				}
			}

			// dynamically import an existing record (this could be a call from a select box)
		} else {
			$record = $this->getRecord($this->inlineFirstPid, $current['table'], $foreignUid);
		}

		$this->TCEforms->addRecord($current['table'], $record);

			// now there is a foreign_selector, so there is a new record on the intermediate table, but
			// this intermediate table holds a field, which is responsible for the foreign_selector, so
			// we have to set this field to the uid we get - or if none, to a new uid
		if ($config['foreign_selector'] && $foreignUid) {
			$selConfig = $this->getPossibleRecordsSelectorConfig($config, $config['foreign_selector']);
				// For a selector of type group/db, prepend the tablename (<tablename>_<uid>):
			$record[$config['foreign_selector']] = $selConfig['type'] != 'groupdb' ? '' : $selConfig['table'].'_';
			$record[$config['foreign_selector']] .= $foreignUid;
		}

			// the HTML-object-id's prefix of the dynamically created record
		$objectPrefix = $this->inlineNames['object'].'['.$current['table'].']';
		$objectId = $objectPrefix.'['.$record['uid'].']';

			// render the foreign record that should passed back to browser
		$item = $this->TCEforms->render();
		if ($item === false) {
			return t3lib_TCEforms_Irre::getErrorMessageForAJAX('Access denied');
		}

			// Encode TCEforms AJAX response with utf-8:
		$item = $GLOBALS['LANG']->csConvObj->utf8_encode($item, $GLOBALS['LANG']->charSet);

		if (!$current['uid']) {
			$jsonArray = array(
				'data'	=> $item,
				'scriptCall' => array(
					"inline.domAddNewRecord('bottom','".$this->inlineNames['object']."_records','$objectPrefix',json.data);",
					"inline.memorizeAddRecord('$objectPrefix','".$record['uid']."',null,'$foreignUid');"
				)
			);

			// append the HTML data after an existing record in the container
		} else {
			$jsonArray = array(
				'data'	=> $item,
				'scriptCall' => array(
					"inline.domAddNewRecord('after','".$domObjectId.'_div'."','$objectPrefix',json.data);",
					"inline.memorizeAddRecord('$objectPrefix','".$record['uid']."','".$current['uid']."','$foreignUid');"
				)
			);
		}
		$this->getCommonScriptCalls($jsonArray, $config);
			// Collapse all other records if requested:
		if (!$collapseAll && $expandSingle) {
			$jsonArray['scriptCall'][] = "inline.collapseAllRecords('$objectId', '$objectPrefix', '".$record['uid']."');";
		}
			// tell the browser to scroll to the newly created record
		$jsonArray['scriptCall'][] = "Element.scrollTo('".$objectId."_div');";
			// fade out and fade in the new record in the browser view to catch the user's eye
		$jsonArray['scriptCall'][] = "inline.fadeOutFadeIn('".$objectId."_div');";

			// Remove the current level also from the dynNestedStack of TCEforms:
		//$this->fObj->popFromDynNestedStack();

			// Return the JSON array:
		return $jsonArray;
	}


	/**
	 * Determines the corrected pid to be used for a new record.
	 * The pid to be used can be defined by a Page TSconfig.
	 *
	 * @param	string		$table: The table name
	 * @param	integer		$parentPid: The pid of the parent record
	 * @return	integer		The corrected pid to be used for a new record
	 */
	protected function getNewRecordPid($table, $parentPid=null) {
		$newRecordPid = $this->inlineFirstPid;
		$pageTS = t3lib_beFunc::getPagesTSconfig($parentPid, true);
		if (isset($pageTS['TCAdefaults.'][$table.'.']['pid']) && t3lib_div::testInt($pageTS['TCAdefaults.'][$table.'.']['pid'])) {
			$newRecordPid = $pageTS['TCAdefaults.'][$table.'.']['pid'];
		} elseif (isset($parentPid) && t3lib_div::testInt($parentPid)) {
			$newRecordPid = $parentPid;
		}
		return $newRecordPid;
	}


	/**
	 * Get a single record row for a TCA table from the database.
	 * t3lib_transferData is used for "upgrading" the values, especially the relations.
	 *
	 * @param	integer		$pid: The pid of the page the record should be stored (only relevant for NEW records)
	 * @param	string		$table: The table to fetch data from (= foreign_table)
	 * @param	string		$uid: The uid of the record to fetch, or the pid if a new record should be created
	 * @param	string		$cmd: The command to perform, empty or 'new'
	 * @return	array		A record row from the database post-processed by t3lib_transferData
	 */
	protected function getRecord($pid, $table, $uid, $cmd='') {
		$trData = t3lib_div::makeInstance('t3lib_transferData');
		$trData->addRawData = TRUE;
		$trData->lockRecords=1;
		$trData->disableRTE = $GLOBALS['SOBE']->MOD_SETTINGS['disableRTE'];
			// if a new record should be created
		$trData->fetchRecord($table, $uid, ($cmd === 'new' ? 'new' : ''));
		reset($trData->regTableItems_data);
		$rec = current($trData->regTableItems_data);

		return $rec;
	}


	/**
	 * Wrapper. Calls getRecord in case of a new record should be created.
	 *
	 * @param	integer		$pid: The pid of the page the record should be stored (only relevant for NEW records)
	 * @param	string		$table: The table to fetch data from (= foreign_table)
	 * @return	array		A record row from the database post-processed by t3lib_transferData
	 */
	protected function getNewRecord($pid, $table) {
		$rec = $this->getRecord($pid, $table, $pid, 'new');
		$rec['uid'] = uniqid('NEW');
		$rec['pid'] = $this->getNewRecordPid($table, $pid);
		return $rec;
	}


	/**
	 * Determines and sets several script calls to a JSON array, that would have been executed if processed in non-AJAX mode.
	 *
	 * @param	array		&$jsonArray: Reference of the array to be used for JSON
	 * @param	array		$config: The configuration of the IRRE field of the parent record
	 * @return	void
	 */
	protected function getCommonScriptCalls(&$jsonArray, $config) {
			// Add data that would have been added at the top of a regular TCEforms call:
		if ($headTags = $this->getHeadTags()) {
			$jsonArray['headData'] = $headTags;
		}
			// Add the JavaScript data that would have been added at the bottom of a regular TCEforms call:
		$jsonArray['scriptCall'][] = $this->TCEforms->renderJavascriptAfterForm($this->TCEforms->getFormName(), true);
			// If script.aculo.us Sortable is used, update the Observer to know the record:
		if ($config['appearance']['useSortable']) {
			$jsonArray['scriptCall'][] = "inline.createDragAndDropSorting('".$this->inlineNames['object']."_records');";
		}
			// if TCEforms has some JavaScript code to be executed, just do it
		if ($this->TCEforms->getEvaluationJS()) {
			$jsonArray['scriptCall'][] = $this->TCEforms->getEvaluationJS();
		}
	}

	/**
	 * Parses the HTML tags that would have been inserted to the <head> of a HTML document and returns the found tags as multidimensional array.
	 *
	 * @return	array		The parsed tags with their attributes and innerHTML parts
	 */
	protected function getHeadTags() {
		$headTags = array();
		$headDataRaw = $this->TCEforms->renderJavascriptBeforeForm();

		if ($headDataRaw) {
				// Create instance of the HTML parser:
			$parseObj = t3lib_div::makeInstance('t3lib_parsehtml');
				// Removes script wraps:
			$headDataRaw = str_replace(array('/*<![CDATA[*/', '/*]]>*/'), '', $headDataRaw);
				// Removes leading spaces of a multiline string:
			$headDataRaw = trim(preg_replace('/(^|\r|\n)( |\t)+/', '$1', $headDataRaw));
				// Get script and link tags:
			$tags = array_merge(
				$parseObj->getAllParts($parseObj->splitTags('link', $headDataRaw)),
				$parseObj->getAllParts($parseObj->splitIntoBlock('script', $headDataRaw))
			);

			foreach ($tags as $tagData) {
				$tagAttributes = $parseObj->get_tag_attributes($parseObj->getFirstTag($tagData), true);
				$headTags[] = array(
					'name' => $parseObj->getFirstTagName($tagData),
					'attributes' => $tagAttributes[0],
					'innerHTML'	=> $parseObj->removeFirstAndLastTag($tagData),
				);
			}
		}

		return $headTags;
	}

	public function getIrreIdentifier($includePid = TRUE) {
		return $this->getStructurePath();
	}

	/**
	 * neccessary for this element to behave like an inline element, so it can act
	 * as a container for an IRRE form object (i.e., an IrreAjaxForm object)
	 */
	public function render() {

	}
}

?>