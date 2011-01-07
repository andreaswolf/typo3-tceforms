<?php

require_once(PATH_t3lib.'tceforms/element/class.t3lib_tceforms_element_abstract.php');
require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_irreform.php');

class t3lib_TCEforms_Element_Inline extends t3lib_TCEforms_Element_Abstract {
	/**
	 * The IRRE form object
	 *
	 * @var t3lib_TCEforms_IRREForm
	 */
	protected $formObject;

	/**
	 * A list of all relations
	 * @var array
	 */
	protected $relationList = array();

	public function __construct($field, $fieldConfig, $alternativeName='', $extra='') {
		parent::__construct($field, $fieldConfig, $alternativeName, $extra);

		$this->initHookObjects();

		$this->formObject = new t3lib_TCEforms_IRREForm();
	}

	public function init() {
		global $TCA;

		parent::init();

		$this->formObject->setContextObject($this->contextObject)
		                 ->setContextRecordObject($this->contextRecordObject)
		                 ->setContainingElement($this)
		                 ->injectFormBuilder($this->formBuilder)
		                 ->setFieldConfig($this->fieldSetup)
		                 ->init();

		$this->foreignTable = $this->fieldSetup['config']['foreign_table'];
		t3lib_div::loadTCA($this->foreignTable);

			// check the TCA configuration - if false is returned, something was wrong
		if ($this->formObject->checkConfiguration($this->fieldSetup['config']) === false) {
			unset($this->formObject); // TODO: do proper garbage collection here
			return false;
		}

			// remember the page id (pid of record) where inline editing started first
			// we need that pid for ajax calls, so that they would know where the action takes place on the page structure
		if (!isset($this->inlineFirstPid)) {
				// if this record is not new, try to fetch the inlineView states
				// @TODO: Add checking/cleaning for unused tables, records, etc. to save space in uc-field
			if (t3lib_div::testInt($this->record['uid'])) {
				$inlineView = unserialize($GLOBALS['BE_USER']->uc['inlineView']);
				$this->inlineView = $inlineView[$this->table][$this->record['uid']];
			}
				// If the parent is a page, use the uid(!) of the (new?) page as pid for the child records:
			if ($this->table == 'pages') {
				$this->inlineFirstPid = $this->record['uid'];
				// If pid is negative, fetch the previous record and take its pid:
			} elseif ($row['pid'] < 0) {
				$prevRec = t3lib_BEfunc::getRecord($this->table, abs($this->record['pid']));
				$this->inlineFirstPid = $prevRec['pid'];
				// Take the pid as it is:
			} else {
				$this->inlineFirstPid = $this->record['pid'];
			}
		}

			// get the records related to this inline record
		$relatedRecords = $this->getRelatedRecords();

			// set the first and last record to the config array
		$relatedRecordsUids = array_keys($relatedRecords['records']);
		$this->fieldSetup['config']['inline']['first'] = reset($relatedRecordsUids);
		$this->fieldSetup['config']['inline']['last'] = end($relatedRecordsUids);

		foreach ($relatedRecords['records'] as $record) {
			$this->formObject->addRecord($this->foreignTable, $record);
			if (!isset($record['__virtual']) || !$record['__virtual']) {
				$this->relationList[] = $record['uid'];
			}
		}
	}

	public function getTemplateContent() {
		return $this->contextObject->getTemplateContent();
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
	function getRecord($pid, $table, $uid, $cmd='') {
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
	 * Get the related records of the embedding item, this could be 1:n, m:n.
	 * Returns an associative array with the keys records and count. 'count' contains only real existing records on the current parent record.
	 *
	 * @return	array		The records related to the parent item as associative array.
	 */
	protected function getRelatedRecords() {
		$records = array();
		$pid = $this->record['pid'];
		$elements = $this->itemFormElValue;
		$foreignTable = $this->fieldSetup['config']['foreign_table'];

		$localizationMode = t3lib_BEfunc::getInlineLocalizationMode($this->table, $this->fieldSetup['config']);

		/*if ($localizationMode!=false) {
			$language = intval($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
			$transOrigPointer = intval($row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]);
			if ($language>0 && $transOrigPointer) {
					// Localization in mode 'keep', isn't a real localization, but keeps the children of the original parent record:
				if ($localizationMode=='keep') {
					$transOrigRec = $this->getRecord(0, $table, $transOrigPointer);
					$elements = $transOrigRec[$field];
					$pid = $transOrigRec['pid'];
					// Localization in modes 'select', 'all' or 'sync' offer a dynamic localization and synchronization with the original language record:
				} elseif ($localizationMode=='select') {
					$transOrigRec = $this->getRecord(0, $table, $transOrigPointer);
					$pid = $transOrigRec['pid'];
					$recordsOriginal = $this->getRelatedRecordsArray($pid, $this->foreignTable, $transOrigRec[$field]);
				}
			}
		}*/

		$records = $this->getRelatedRecordsArray($pid, $this->foreignTable, $elements);
		$relatedRecords = array('records' => $records, 'count' => count($records));

			// Merge original language with current localization and show differences:
		if (is_array($recordsOriginal)) {
			$options = array(
				'showPossible' => (isset($config['appearance']['showPossibleLocalizationRecords']) && $config['appearance']['showPossibleLocalizationRecords']),
				'showRemoved' => (isset($config['appearance']['showRemovedLocalizationRecords']) && $config['appearance']['showRemovedLocalizationRecords']),
			);
			if ($options['showPossible'] || $options['showRemoved']) {
				$relatedRecords['records'] = $this->getLocalizationDifferences($this->foreignTable, $options, $recordsOriginal, $records);
			}
		}

		return $relatedRecords;
	}


	/**
	 * Gets the related records of the embedding item, this could be 1:n, m:n.
	 *
	 * @param	integer		$pid: The pid of the parent record
	 * @param	string		$table: The table name of the record
	 * @param	string		$itemList: The list of related child records
	 * @return	array		The records related to the parent item
	 */
	protected function getRelatedRecordsArray($pid, $table, $itemList) {
		$records = array();
		$itemArray = $this->getRelatedRecordsUidArray($itemList);
			// Perform modification of the selected items array:
		foreach($itemArray as $uid) {
				// Get the records for this uid using t3lib_transferdata:
			if ($record = $this->getRecord($pid, $table, $uid)) {
				$records[$uid] = $record;
			}
		}
		return $records;
	}


	/**
	 * Gets an array with the uids of related records out of a list of items.
	 * This list could contain more information than required. This methods just
	 * extracts the uids.
	 *
	 * @param	string		$itemList: The list of related child records
	 * @return	array		An array with uids
	 */
	protected function getRelatedRecordsUidArray($itemList) {
		$itemArray = t3lib_div::trimExplode(',', $itemList, 1);
			// Perform modification of the selected items array:
		foreach($itemArray as $key => &$value) {
			$parts = explode('|', $value, 2);
			$value = $parts[0];
		}
		return $itemArray;
	}

	/**
	 * Initialized the hook objects for this class.
	 * Each hook object has to implement the interface t3lib_tceformsInlineHook.
	 *
	 * @return	void
	 */
	protected function initHookObjects() {
		$this->hookObjects = array();
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'])) {
			$tceformsInlineHook =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'];
			if (is_array($tceformsInlineHook)) {
				foreach($tceformsInlineHook as $classData) {
					$processObject = &t3lib_div::getUserObj($classData);

					if(!($processObject instanceof t3lib_tceformsInlineHook)) {
						throw new UnexpectedValueException('$processObject must implement interface t3lib_tceformsInlineHook', 1202072000);
					}

					$processObject->init($this);
					$this->hookObjects[] = $processObject;
				}
			}
		}
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

	public function renderField() {
		$wrap = t3lib_parsehtml::getSubpart($this->getTemplateContent(), '###TOTAL_WRAP_IRRE###');

		$levelLinks = $this->renderLevelLinks();

		$formContent = $this->formObject->render();

			// add Drag&Drop functions for sorting to TCEforms::$additionalJS_post
		if (count($this->relationList) > 1 && $this->fieldSetup['config']['appearance']['useSortable']) {
			$this->addJavaScriptSortable($this->getIrreIdentifier() . '_records');
			// publish the uids of the child records in the given order to the browser
		}
		$formContent .= '<input type="hidden" name="' . $this->formObject->getFormFieldNamePrefix() . $this->getIrreCurrentLevelIdentifier() . '" value="' . implode(',', $this->relationList) . '" class="inlineRecord" />';

		$markerArray = array(
			'###IRREFIELDNAMES###' => $this->getIrreIdentifier(),
			'###LEVELLINKS_TOP###' => (in_array($this->fieldSetup['config']['appearance']['levelLinksPosition'], array('both', 'top')) ? $levelLinks : ''),
			'###LEVELLINKS_BOTTOM###' => (in_array($this->fieldSetup['config']['appearance']['levelLinksPosition'], array('both', 'bottom')) ? $levelLinks : ''),
			'###FORM_CONTENT###' => $formContent
		);

		return t3lib_parsehtml::substituteMarkerArray($wrap, $markerArray);
	}

	protected function renderLevelLinks() {
		//if ($this->fieldSetup['appearance']['levelLinksPosition']!='none') {
			$levelLinks = $this->getLevelInteractionLink('newRecord', $this->getIrreIdentifier() . '['.$this->foreignTable.']', $this->fieldSetup['config']);
			if ($language > 0) { // TODO fix this
					// Add the "Localize all records" link before all child records:
				if (isset($this->fieldSetup['appearance']['showAllLocalizationLink']) && $this->fieldSetup['appearance']['showAllLocalizationLink']) {
					$levelLinks .= $this->getLevelInteractionLink('localize', $this->getIrreIdentifier() . '['.$this->foreignTable.']', $this->fieldSetup);
				}
					// Add the "Synchronize with default language" link before all child records:
				if (isset($config['appearance']['showSynchronizationLink']) && $config['appearance']['showSynchronizationLink']) {
					$levelLinks .= $this->getLevelInteractionLink('synchronize', $this->getIrreIdentifier() . '['.$this->foreignTable.']', $this->fieldSetup);
				}
			}
		//}

		return $levelLinks;
	}

	public function getIrreIdentifier($includePid = TRUE) {
		return $this->getIrreFormIdentifier($includePid) . $this->getIrreCurrentLevelIdentifier();
	}

	public function getIrreCurrentLevelIdentifier() {
		$identifierParts[] = $this->getFieldname();
		$identifierParts[] = $this->getValue('uid');
		$identifierParts[] = $this->getTable();

		return '[' . implode('][', array_reverse($identifierParts)) . ']';
	}

	protected function getIrreFormIdentifier($includePid = TRUE) {
		$elementObject = $this;
		$identifierParts = array();
		while ($elementObject->getParentFormObject() instanceof t3lib_TCEforms_NestableForm
			&& $elementObject->getParentFormObject() != $this->contextObject) {
				$elementObject = $elementObject->getParentFormObject()->getContainingElement();
				$identifierParts[] = $elementObject->getFieldname();
				$identifierParts[] = $elementObject->getValue('uid');
				$identifierParts[] = $elementObject->getTable();
				t3lib_div::devLog('Loop ' . ++$i, 't3lib_TCEforms_IrreForm');
		}
		if ($includePid) {
			$identifierParts[] = $this->inlineFirstPid;
		}

		return $this->formObject->getFormFieldNamePrefix() . '[' . implode('][', array_reverse($identifierParts)) . ']';
	}


	/*******************************************************
	 *
	 * Helper functions
	 *
	 *******************************************************/

	public function getValue($key) {
		return $this->record[$key];
	}

	public function getTable() {
		return $this->table;
	}

	/**
	 * Creates the HTML code of a general link to be used on a level of inline children.
	 * The possible keys for the parameter $type are 'newRecord', 'localize' and 'synchronize'.
	 *
	 * @param	string		$type: The link type, values are 'newRecord', 'localize' and 'synchronize'.
	 * @param	string		$objectPrefix: The "path" to the child record to create (e.g. 'data[parten_table][parent_uid][parent_field][child_table]')
	 * @param	array		$conf: TCA configuration of the parent(!) field
	 * @return	string		The HTML code of the new link, wrapped in a div
	 */
	protected function getLevelInteractionLink($type, $objectPrefix, $conf=array()) {
		$nameObject = $this->inlineNames['object'];
		$attributes = array();
		switch($type) {
			case 'newRecord':
				$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:cm.createnew', 1);
				$iconFile = 'gfx/new_el.gif';
				// $iconAddon = 'width="11" height="12"';
				$className = 'typo3-newRecordLink';
				$attributes['class'] = 'inlineNewButton '.$this->inlineData['config'][$nameObject]['md5'];
				$attributes['onclick'] = "return inline.createNewRecord('$objectPrefix')";
				if (isset($conf['inline']['inlineNewButtonStyle']) && $conf['inline']['inlineNewButtonStyle']) {
					$attributes['style'] = $conf['inline']['inlineNewButtonStyle'];
				}
				if (isset($conf['appearance']['newRecordLinkAddTitle']) && $conf['appearance']['newRecordLinkAddTitle']) {
					$titleAddon = ' '.$GLOBALS['LANG']->sL($GLOBALS['TCA'][$conf['foreign_table']]['ctrl']['title'], 1);
				}
				break;
			case 'localize':
				$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xml:localizeAllRecords', 1);
				$iconFile = 'gfx/localize_el.gif';
				$className = 'typo3-localizationLink';
				$attributes['onclick'] = "return inline.synchronizeLocalizeRecords('$objectPrefix', 'localize')";
				break;
			case 'synchronize':
				$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xml:synchronizeWithOriginalLanguage', 1);
				$iconFile = 'gfx/synchronize_el.gif';
				$className = 'typo3-synchronizationLink';
				$attributes['class'] = 'inlineNewButton '.$this->inlineData['config'][$nameObject]['md5'];
				$attributes['onclick'] = "return inline.synchronizeLocalizeRecords('$objectPrefix', 'synchronize')";
				break;
		}
			// Create the link:
		$icon = ($iconFile ? '<img'.t3lib_iconWorks::skinImg($this->backPath, $iconFile, $iconAddon).' alt="'.htmlspecialchars($title.$titleAddon).'" />' : '');
		$link = $this->wrapWithAnchor($icon.$title.$titleAddon, '#', $attributes);
		return '<div'.($className ? ' class="'.$className.'"' : '').'>'.$link.'</div>';
	}

	/**
	 * Wraps a text with an anchor and returns the HTML representation.
	 *
	 * @param	string		$text: The text to be wrapped by an anchor
	 * @param	string		$link: The link to be used in the anchor
	 * @param	array		$attributes: Array of attributes to be used in the anchor
	 * @return	string		The wrapped texted as HTML representation
	 */
	protected function wrapWithAnchor($text, $link, $attributes=array()) {
		$link = trim($link);
		$result = '<a href="'.($link ? $link : '#').'"';
		foreach ($attributes as $key => $value) {
			$result.= ' '.$key.'="'.htmlspecialchars(trim($value)).'"';
		}
		$result.= '>'.$text.'</a>';
		return $result;
	}

	public function getForeignTable() {
		return $this->foreignTable;
	}

	/**
	 * Add Sortable functionality using script.acolo.us "Sortable".
	 *
	 * @param	string		$objectId: The container id of the object - elements inside will be sortable
	 * @return	void
	 */
	protected function addJavaScriptSortable($objectId) {
		$this->contextObject->addToAdditionalJSPostForm('
			inline.createDragAndDropSorting("'.$objectId.'");
		');
	}
}

?>
