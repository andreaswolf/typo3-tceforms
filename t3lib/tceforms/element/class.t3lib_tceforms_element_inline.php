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
	
	public function __construct($field, $fieldConfig, $alternativeName='', $extra='') {
		parent::__construct($field, $fieldConfig, $alternativeName, $extra);

		$this->initHookObjects();

		$this->formObject = new t3lib_TCEforms_IRREForm();
	}

	public function init() {
		global $TCA;

		parent::init();

			// check the TCA configuration - if false is returned, something was wrong
		if ($this->checkConfiguration() === false) {
			return false;
		}

		$this->formObject->setContextObject($this->contextObject)
		                 ->setContainingElement($this);

		//$this->formObject->setFormFieldNamePrefix($this->formObject->getFormFieldNamePrefix());
		//$this->formObject->setFormFieldIdPrefix($this->formObject->getFormFieldIdPrefix());

			// Register this element with the context
		$this->contextObject->registerInlineElement($this);

			// Init:
		$foreign_table = $this->fieldConfig['config']['foreign_table'];
		t3lib_div::loadTCA($foreign_table);

		if (t3lib_BEfunc::isTableLocalizable($this->table)) {
			$languageField = $TCA[$this->table]['ctrl']['languageField'];
			$language = intval($this->recordData[$languageField]);
		}
		$minitems = t3lib_div::intInRange($this->fieldConfig['config']['minitems'],0);
		$maxitems = t3lib_div::intInRange($this->fieldConfig['config']['maxitems'],0);
		if (!$maxitems) $maxitems=100000;

			// Register the required number of elements:
		//$this->fObj->requiredElements[$PA['itemFormElName']] = array($minitems,$maxitems,'imgName'=>$this->table.'_'.$row['uid'].'_'.$field);
		//$this->contextObject->registerRequiredFieldRange($this->itemFormElName, array($minitems, $maxitems, 'imgName' => $this->table . '_' . $this->record['uid'] . '_' . $this->field));

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
			// add the current inline job to the structure stack
		//$this->pushStructure($this->table, $this->record['uid'], $this->field, $this->fieldConfig['config']);
			// e.g. inline[<table>][<uid>][<field>]
		$nameForm = $this->inlineNames['form'];
			// e.g. inline[<pid>][<table1>][<uid1>][<field1>][<table2>][<uid2>][<field2>]
		$nameObject = $this->inlineNames['object'];
			// get the records related to this inline record
		$relatedRecords = $this->getRelatedRecords();
			// set the first and last record to the config array
		$relatedRecordsUids = array_keys($relatedRecords['records']);
		$this->fieldConfig['config']['inline']['first'] = reset($relatedRecordsUids);
		$this->fieldConfig['config']['inline']['last'] = end($relatedRecordsUids);

		foreach ($relatedRecords['records'] as $record) {
			$this->formObject->addRecord($foreign_table, $record);
		}

			// Tell the browser what we have (using JSON later):
		$top = $this->getStructureLevel(0);
		$this->inlineData['config'][$nameObject] = array(
			'table' => $foreign_table,
			'md5' => md5($nameObject),
		);
		$this->inlineData['config'][$nameObject.'['.$foreign_table.']'] = array(
			'min' => $minitems,
			'max' => $maxitems,
			'sortable' => $config['appearance']['useSortable'],
			'top' => array(
				'table' => $top['table'],
				'uid'	=> $top['uid'],
			),
		);
			// Set a hint for nested IRRE and tab elements:
		/*$this->inlineData['nested'][$nameObject] = $this->getDynNestedStack(false, $this->isAjaxCall);

			// if relations are required to be unique, get the uids that have already been used on the foreign side of the relation
		if ($this->fieldConfig['config']['foreign_unique']) {
				// If uniqueness *and* selector are set, they should point to the same field - so, get the configuration of one:
			$selConfig = $this->getPossibleRecordsSelectorConfig($config, $config['foreign_unique']);
				// Get the used unique ids:
			$uniqueIds = $this->getUniqueIds($relatedRecords['records'], $config, $selConfig['type']=='groupdb');
			$possibleRecords = $this->getPossibleRecords($this->table,$field,$row,$config,'foreign_unique');
			$uniqueMax = $config['appearance']['useCombination'] || $possibleRecords === false ? -1	: count($possibleRecords);
			$this->inlineData['unique'][$nameObject.'['.$foreign_table.']'] = array(
				'max' => $uniqueMax,
				'used' => $uniqueIds,
				'type' => $selConfig['type'],
				'table' => $config['foreign_table'],
				'elTable' => $selConfig['table'], // element/record table (one step down in hierarchy)
				'field' => $config['foreign_unique'],
				'selector' => $selConfig['selector'],
				'possible' => $this->getPossibleRecordsFlat($possibleRecords),
			);
		}

			// if it's required to select from possible child records (reusable children), add a selector box
		if ($config['foreign_selector']) {
				// if not already set by the foreign_unique, set the possibleRecords here and the uniqueIds to an empty array
			if (!$config['foreign_unique']) {
				$possibleRecords = $this->getPossibleRecords($this->table,$field,$row,$config);
				$uniqueIds = array();
			}
			$selectorBox = $this->renderPossibleRecordsSelector($possibleRecords,$config,$uniqueIds);
			$item .= $selectorBox;
		}

			// wrap all inline fields of a record with a <div> (like a container)
		$item .= '<div id="'.$nameObject.'">';

			// define how to show the "Create new record" link - if there are more than maxitems, hide it
		if ($relatedRecords['count'] >= $maxitems || ($uniqueMax > 0 && $relatedRecords['count'] >= $uniqueMax)) {
			$config['inline']['inlineNewButtonStyle'] = 'display: none;';
		}

			// Render the level links (create new record, localize all, synchronize):
		if ($config['appearance']['levelLinksPosition']!='none') {
			$levelLinks = $this->getLevelInteractionLink('newRecord', $nameObject.'['.$foreign_table.']', $config);
			if ($language>0) {
					// Add the "Localize all records" link before all child records:
				if (isset($config['appearance']['showAllLocalizationLink']) && $config['appearance']['showAllLocalizationLink']) {
					$levelLinks.= $this->getLevelInteractionLink('localize', $nameObject.'['.$foreign_table.']', $config);
				}
					// Add the "Synchronize with default language" link before all child records:
				if (isset($config['appearance']['showSynchronizationLink']) && $config['appearance']['showSynchronizationLink']) {
					$levelLinks.= $this->getLevelInteractionLink('synchronize', $nameObject.'['.$foreign_table.']', $config);
				}
			}
		}
			// Add the level links before all child records:
		if (in_array($config['appearance']['levelLinksPosition'], array('both', 'top'))) {
			$item.= $levelLinks;
		}

		$item .= '<div id="'.$nameObject.'_records">';
		$relationList = array();
		if (count($relatedRecords['records'])) {
			foreach ($relatedRecords['records'] as $rec) {
				$item .= $this->renderForeignRecord($row['uid'],$rec,$config);
				if (!isset($rec['__virtual']) || !$rec['__virtual']) {
					$relationList[] = $rec['uid'];
				}
			}
		}
		$item .= '</div>';

			// Add the level links after all child records:
		if (in_array($config['appearance']['levelLinksPosition'], array('both', 'bottom'))) {
			$item.= $levelLinks;
		}

			// add Drag&Drop functions for sorting to TCEforms::$additionalJS_post
		if (count($relationList) > 1 && $config['appearance']['useSortable'])
			$this->addJavaScriptSortable($nameObject.'_records');
			// publish the uids of the child records in the given order to the browser
		$item .= '<input type="hidden" name="'.$nameForm.'" value="'.implode(',', $relationList).'" class="inlineRecord" />';
			// close the wrap for all inline fields (container)
		$item .= '</div>';

			// on finishing this section, remove the last item from the structure stack
		$this->popStructure();

			// if this was the first call to the inline type, restore the values
		if (!$this->getStructureDepth()) {
			unset($this->inlineFirstPid);
		}*/
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
		$foreignTable = $this->fieldConfig['config']['foreign_table'];

		$localizationMode = t3lib_BEfunc::getInlineLocalizationMode($this->table, $this->fieldConfig['config']);

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
					$recordsOriginal = $this->getRelatedRecordsArray($pid, $foreignTable, $transOrigRec[$field]);
				}
			}
		}*/

		$records = $this->getRelatedRecordsArray($pid, $foreignTable, $elements);
		$relatedRecords = array('records' => $records, 'count' => count($records));

			// Merge original language with current localization and show differences:
		if (is_array($recordsOriginal)) {
			$options = array(
				'showPossible' => (isset($config['appearance']['showPossibleLocalizationRecords']) && $config['appearance']['showPossibleLocalizationRecords']),
				'showRemoved' => (isset($config['appearance']['showRemovedLocalizationRecords']) && $config['appearance']['showRemovedLocalizationRecords']),
			);
			if ($options['showPossible'] || $options['showRemoved']) {
				$relatedRecords['records'] = $this->getLocalizationDifferences($foreignTable, $options, $recordsOriginal, $records);
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
	function getStructureLevel($level) {
		$inlineStructureCount = count($this->inlineStructure['stable']);
		if ($level < 0) $level = $inlineStructureCount+$level;
		if ($level >= 0 && $level < $inlineStructureCount)
			return $this->inlineStructure['stable'][$level];
		else
			return false;
	}

	public function renderField() {

		return $this->formObject->render();
	}


	/*******************************************************
	 *
	 * Helper functions
	 *
	 *******************************************************/

	/**
	 * Does some checks on the TCA configuration of the inline field to render.
	 *
	 * @param	array		$config: Reference to the TCA field configuration
	 * @param	string		$table: The table name of the record
	 * @param	string		$field: The field name which this element is supposed to edit
	 * @param	array		$row: The record data array of the parent
	 * @return	boolean		If critical configuration errors were found, false is returned
	 */
	function checkConfiguration() {
		$foreign_table = $this->fieldConfig['config']['foreign_table'];

			// An inline field must have a foreign_table, if not, stop all further inline actions for this field:
		if (!$foreign_table || !is_array($GLOBALS['TCA'][$foreign_table])) {
			return false;
		}
			// Init appearance if not set:
		if (!isset($this->fieldConfig['config']['appearance']) || !is_array($config['appearance'])) {
			$this->fieldConfig['config']['appearance'] = array();
		}
			// 'newRecordLinkPosition' is deprecated since TYPO3 4.2.0-beta1, this is for backward compatibility:
		if (!isset($this->fieldConfig['config']['appearance']['levelLinksPosition']) && isset($this->fieldConfig['config']['appearance']['newRecordLinkPosition']) && $this->fieldConfig['config']['appearance']['newRecordLinkPosition']) {
			$this->fieldConfig['config']['appearance']['levelLinksPosition'] = $this->fieldConfig['config']['appearance']['newRecordLinkPosition'];
		}
			// Set the position/appearance of the "Create new record" link:
		if (isset($this->fieldConfig['config']['foreign_selector']) && $this->fieldConfig['config']['foreign_selector'] && (!isset($this->fieldConfig['config']['appearance']['useCombination']) || !$this->fieldConfig['config']['appearance']['useCombination'])) {
			$this->fieldConfig['config']['appearance']['levelLinksPosition'] = 'none';
		} elseif (!isset($this->fieldConfig['config']['appearance']['levelLinksPosition']) || !in_array($this->fieldConfig['config']['appearance']['levelLinksPosition'], array('top', 'bottom', 'both', 'none'))) {
			$this->fieldConfig['config']['appearance']['levelLinksPosition'] = 'top';
		}
			// Defines which controls should be shown in header of each record:
		$enabledControls = array(
			'info'		=> true,
			'new'		=> true,
			'dragdrop'	=> true,
			'sort'		=> true,
			'hide'		=> true,
			'delete'	=> true,
			'localize'	=> true,
		);
		if (isset($this->fieldConfig['config']['appearance']['enabledControls']) && is_array($this->fieldConfig['config']['appearance']['enabledControls'])) {
			$this->fieldConfig['config']['appearance']['enabledControls'] = array_merge($enabledControls, $this->fieldConfig['config']['appearance']['enabledControls']);
		} else {
			$this->fieldConfig['config']['appearance']['enabledControls'] = $enabledControls;
		}

		return true;
	}

	/**
	 * Returns true if expanding a record in the TCEforms view should collapse all other records.
	 * This setting is configured via [appearance][expandSingle] in this columns TCA-config section
	 *
	 * @return boolean
	 */
	public function expandOnlyOneRecordAtATime() {
		return $this->fieldConfig['config']['appearance']['expandSingle'];
	}

	public function getValue($key) {
		return $this->record[$key];
	}

	public function getTable() {
		return $this->table;
	}
}


?>
