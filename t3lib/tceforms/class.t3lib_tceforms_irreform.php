<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_form.php');
require_once(PATH_t3lib.'interfaces/interface.t3lib_tceforms_nestableform.php');

/*
 * TODO check if inlineFirstPid may be replaced by a call to context object to get the pid
 */
class t3lib_TCEforms_IRREForm extends t3lib_TCEforms_Form implements t3lib_TCEforms_NestableForm {

	/**
	 * The element object containing this form.
	 *
	 * @var t3lib_TCEforms_Element
	 */
	protected $containingElement;

	protected $firstRecordUid;

	protected $lastRecordUid;

	protected $fieldConfig;

	protected $foreignTable;

	protected $isAjaxCall = FALSE;

	public function __construct() {
		parent::__construct();
	}

	public function init() {
		$this->formBuilder = clone($this->formBuilder);

			// Register this element with the context
		$this->contextObject->registerInlineElement($this);

			// Init:
		$this->foreignTable = $this->fieldConfig['config']['foreign_table'];
		t3lib_div::loadTCA($this->foreignTable);

		if (t3lib_BEfunc::isTableLocalizable($this->table)) {
			$languageField = $TCA[$this->table]['ctrl']['languageField'];
			$this->languageUid = intval($this->recordData[$languageField]);
			$this->formObject->setLanguage($this->languageUid);
		}
		$minitems = t3lib_div::intInRange($this->fieldConfig['config']['minitems'],0);
		$maxitems = t3lib_div::intInRange($this->fieldConfig['config']['maxitems'],0);
		if (!$maxitems) $maxitems=100000;

			// Register the required number of elements:
		//$this->fObj->requiredElements[$PA['itemFormElName']] = array($minitems,$maxitems,'imgName'=>$this->table.'_'.$row['uid'].'_'.$field);
		//$this->contextObject->registerRequiredFieldRange($this->itemFormElName, array($minitems, $maxitems, 'imgName' => $this->table . '_' . $this->record['uid'] . '_' . $this->field));

			// add the current inline job to the structure stack
		//$this->pushStructure($this->table, $this->record['uid'], $this->field, $this->fieldSetup['config']);
			// e.g. inline[<table>][<uid>][<field>]
		$nameForm = $this->inlineNames['form'];
			// e.g. inline[<pid>][<table1>][<uid1>][<field1>][<table2>][<uid2>][<field2>]
		$nameObject = $this->getIrreIdentifier();//$this->inlineNames['object'];

			// Tell the browser what we have (using JSON later):
		$this->inlineData['config'][$nameObject] = array(
			'table' => $this->foreignTable,
			'md5' => md5($nameObject),
		);
		$this->inlineData['config'][$this->getIrreIdentifier() . '[' . $this->foreignTable . ']'] = array(
			'min' => $minitems,
			'max' => $maxitems,
			'sortable' => $config['appearance']['useSortable'],
			'top' => array(
				'table' => $this->contextRecordObject->getTable(),
				'uid'   => $this->contextRecordObject->getValue('uid'),
			),
		);

			// Set a hint for nested IRRE and tab elements:
		$this->inlineData['nested'][$nameObject] = $this->getNestedStack(false, $this->isAjaxCall);

		/*
			// if relations are required to be unique, get the uids that have already been used on the foreign side of the relation
		if ($this->fieldSetup['config']['foreign_unique']) {
				// If uniqueness *and* selector are set, they should point to the same field - so, get the configuration of one:
			$selConfig = $this->getPossibleRecordsSelectorConfig($config, $config['foreign_unique']);
				// Get the used unique ids:
			$uniqueIds = $this->getUniqueIds($relatedRecords['records'], $config, $selConfig['type']=='groupdb');
			$possibleRecords = $this->getPossibleRecords($this->table,$field,$row,$config,'foreign_unique');
			$uniqueMax = $config['appearance']['useCombination'] || $possibleRecords === false ? -1	: count($possibleRecords);
			$this->inlineData['unique'][$nameObject.'['.$this->foreignTable.']'] = array(
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
			$levelLinks = $this->getLevelInteractionLink('newRecord', $nameObject.'['.$this->foreignTable.']', $config);
			if ($language>0) {
					// Add the "Localize all records" link before all child records:
				if (isset($config['appearance']['showAllLocalizationLink']) && $config['appearance']['showAllLocalizationLink']) {
					$levelLinks.= $this->getLevelInteractionLink('localize', $nameObject.'['.$this->foreignTable.']', $config);
				}
					// Add the "Synchronize with default language" link before all child records:
				if (isset($config['appearance']['showSynchronizationLink']) && $config['appearance']['showSynchronizationLink']) {
					$levelLinks.= $this->getLevelInteractionLink('synchronize', $nameObject.'['.$this->foreignTable.']', $config);
				}
			}
		}
			// Add the level links before all child records:
		if (in_array($config['appearance']['levelLinksPosition'], array('both', 'top'))) {
			$item.= $levelLinks;
		}

			// Add the level links after all child records:
		if (in_array($config['appearance']['levelLinksPosition'], array('both', 'bottom'))) {
			$item.= $levelLinks;
		}

			// on finishing this section, remove the last item from the structure stack
		$this->popStructure();

			// if this was the first call to the inline type, restore the values
		if (!$this->getStructureDepth()) {
			unset($this->inlineFirstPid);
		}*/
	}

	public function addRecord($table, $record) {
		if (!$this->firstRecordUid) {
			$this->firstRecordUid = $record['uid'];
		}
		t3lib_div::devLog('Adding record for table ' . $table, __CLASS__);
		parent::addRecord($table, $record);
	}

	/**
	 * Sets the element containing this form.
	 * @param t3lib_TCEforms_Element $elementObject
	 * @return t3lib_TCEforms_NestableForm A reference to $this, for easier use
	 */
	public function setContainingElement(t3lib_TCEforms_Element $elementObject) {
		$this->containingElement = $elementObject;
		return $this;
	}

	public function setContextRecordObject(t3lib_TCEforms_Record $contextRecordObject) {
		$this->contextRecordObject = $contextRecordObject;

		return $this;
	}

	/**
	 * Returns the element containing the nestable form.
	 * @return t3lib_TCEforms_Element
	 */
	public function getContainingElement() {
		return $this->containingElement;
	}

	public function getTemplateContent() {
		if ($this->templateContent != '') {
			return $this->templateContent;
		} else {
			return $this->contextObject->getTemplateContent();
		}
	}

	/**
	 * Returns the data that is required to be included for this inline element.
	 *
	 * @return array
	 */
	public function getInlineData() {
		return $this->inlineData;
	}

	/**
	 * The configuration of this field
	 *
	 * @param $fieldConfig
	 * @return t3lib_TCEforms_IRREForm $this
	 */
	public function setFieldConfig(&$fieldConfig) {
		$this->fieldConfig =& $fieldConfig;
		return $this;
	}

	public function setForeignTable($foreignTable) {
		$this->foreignTable = $foreignTable;
		return $this;
	}

	public function render() {
		if (count($this->recordObjects) > 0) {
			$lastRecord = end($this->recordObjects);
			$this->lastRecordUid = $lastRecord->getValue('uid');
		}
		return parent::render();
	}

	/**
	 * Renders a record object into a HTML form.
	 *
	 * @param t3lib_TCEforms_Record $recordObject
	 * @return string The rendered record form, ready to be put on a page
	 */
	protected function renderRecordObject(t3lib_TCEforms_Record $recordObject) {
		global $TCA;

		$recordContent = $recordObject->render();

		$wrap = t3lib_parsehtml::getSubpart($this->getTemplateContent(), '###RECORD_WRAP_IRRE###');
		if ($wrap == '') {
			throw new RuntimeException('No template wrap for record found.');
		}

		$appendFormFieldNames = '['.$recordObject->getTable().']['.$recordObject->getValue('uid').']';
		$irreFieldNames = $this->getIrreIdentifierForRecord($recordObject);
		$formFieldNames = $this->getFieldIdentifier($recordObject);
		t3lib_div::devLog('pid: ' . $this->containingElement->getValue('pid'), __CLASS__);

		$fieldsStyle = (!$this->getExpandedCollapsedState($recordObject) ? 'display:none;' : '');

		$markerArray = array(
			'###TITLE###' => htmlspecialchars($recordObject->getTitle()),
			'###ICON###' => t3lib_iconWorks::getIconImage($recordObject->getTable(), $recordObject->getRecordData(), $this->getBackpath(), 'class="absmiddle"' . $titleA),
			'###WRAP_CONTENT###' => $recordContent,
			'###BACKGROUND###' => htmlspecialchars($this->backPath.$this->containingElement->getBorderStyle()),
			'###FORMFIELDNAMES###' => $formFieldNames,
			'###IRREFIELDNAMES###' => $irreFieldNames,
			'###CLASS###' => 'wrapperTable',//htmlspecialchars($this->containingElement->getClassScheme())
			'###ONCLICK###' => " onClick=\"return inline.expandCollapseRecord('" . htmlspecialchars($irreFieldNames) . "', " . ($this->expandOnlyOneRecordAtATime() ? '1' : '0') . ");\"",
			'###FIELDS_STYLE###' => $fieldsStyle,
				// TODO use isVirtualRecord as parameter
			'###CONTROL_BUTTONS###' => $this->renderForeignRecordHeaderControl($recordObject)
		);

		$content = t3lib_parsehtml::substituteMarkerArray($wrap, $markerArray);

		return $content;
	}

	protected function getIrreIdentifierForRecord(t3lib_TCEforms_Record $recordObject) {
		$identifierParts[] = $recordObject->getValue('uid');
		$identifierParts[] = $recordObject->getTable();
		return $this->containingElement->getIrreIdentifier() . '[' . implode('][', array_reverse($identifierParts)) . ']';
	}

	protected function getFieldIdentifier(t3lib_TCEforms_Record $recordObject) {

	}

	/**
	 * Checks if a uid of a child table is in the inline view settings.
	 *
	 * @return boolean true=expand, false=collapse
	 */
	function getExpandedCollapsedState(t3lib_TCEforms_Record $recordObject) {
		if ($this->inlineViewState == NULL) {
			$inlineView = unserialize($GLOBALS['BE_USER']->uc['inlineView']);
			$this->inlineViewState = (array)$inlineView[$this->getContainerTable()][$this->getContainingRecordValue('uid')];
			t3lib_div::devLog('inlineViewState for ' . $this->getContainerTable() . ':' . $this->getContainingRecordValue('uid') . ': ' . serialize($this->inlineViewState), __CLASS__);
		}

		$collapseAll = (isset($config['appearance']['collapseAll']) && $config['appearance']['collapseAll']);
		if ($collapseAll) {
			return false;
		}

		$table = $recordObject->getTable();
		$uid = $recordObject->getValue('uid');
		if (isset($this->inlineViewState[$table]) && is_array($this->inlineViewState[$table])) {
			if (in_array($uid, $this->inlineViewState[$table]) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Render the control-icons for a record header (create new, sorting, delete, disable/enable).
	 * Most of the parts are copy&paste from class.db_list_extra.inc and modified for the JavaScript calls here
	 *
	 * @param	string		$parentUid: The uid of the parent (embedding) record (uid or NEW...)
	 * @param	string		$this->foreignTable: The table (foreign_table) we create control-icons for
	 * @param	array		$rec: The current record of that foreign_table
	 * @param	array		$config: (modified) TCA configuration of the field
	 * @return	string		The HTML code with the control-icons
	 */
	protected function renderForeignRecordHeaderControl($recordObject, $config = array(), $isVirtualRecord=false) {
			// Initialize:
		$cells = array();
		$isNewItem = substr($recordObject->getValue('uid'), 0, 3) == 'NEW';

		$config = $this->fieldConfig['config'];
		$tcaTableCtrl =& $GLOBALS['TCA'][$this->foreignTable]['ctrl'];
		$tcaTableCols =& $GLOBALS['TCA'][$this->foreignTable]['columns'];

		$isPagesTable = $this->foreignTable == 'pages' ? true : false;
		$isOnSymmetricSide = t3lib_loadDBGroup::isOnSymmetricSide($this->record['uid'], $config, $recordObject->getValue('uid'));
		$enableManualSorting = $tcaTableCtrl['sortby'] || $config['MM'] || (!$isOnSymmetricSide && $config['foreign_sortby']) || ($isOnSymmetricSide && $config['symmetric_sortby']) ? true : false;

		$nameObject = $this->getIrreIdentifierForRecord($recordObject);
		$nameObjectFt = $nameObject . '[' . $foreignTable . ']';
		$nameObjectFtId = $this->getIrreIdentifierForRecord($recordObject); //$nameObjectFt . '[' . $recordObject->getValue('uid') . ']';

		$calcPerms = $GLOBALS['BE_USER']->calcPerms(
			t3lib_BEfunc::readPageAccess($recordObject->getValue('pid'), $GLOBALS['BE_USER']->getPagePermsClause(1))
		);

			// If the listed table is 'pages' we have to request the permission settings for each page:
		if ($isPagesTable)	{
			$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages', $recordObject->getValue('uid')));
		}

			// This expresses the edit permissions for this particular element:
		$permsEdit = ($isPagesTable && ($localCalcPerms&2)) || (!$isPagesTable && ($calcPerms&16));

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
		if (isset($config['appearance']['enabledControls']) && is_array($config['appearance']['enabledControls'])) {
			$config['appearance']['enabledControls'] = array_merge($enabledControls, $config['appearance']['enabledControls']);
		} else {
			$config['appearance']['enabledControls'] = $enabledControls;
		}
			// Hook: Can disable/enable single controls for specific child records:
		/* TODO reenable this
		foreach ($this->hookObjects as $hookObj) {
			$hookObj->renderForeignRecordHeaderControl_preProcess($parentUid, $this->foreignTable, <, $config, $isVirtual, $enabledControls);
		}*/

			// Icon to visualize that a required field is nested in this inline level:
		$cells['required'] = '<img name="'.$nameObjectFtId.'_req" src="clear.gif" width="10" height="10" hspace="4" vspace="3" alt="" />';

		if (isset($rec['__create'])) {
			$cells['localize.isLocalizable'] = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/localize_green.gif','width="16" height="16"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xml:localize.isLocalizable', 1).'" alt="" />';
		} elseif (isset($rec['__remove'])) {
			$cells['localize.wasRemovedInOriginal'] = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/localize_red.gif','width="16" height="16"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xml:localize.wasRemovedInOriginal', 1).'" alt="" />';
		}

			// "Info": (All records)
		if ($enabledControls['info'] && !$isNewItem) {
			$cells['info']='<a href="#" onclick="'.htmlspecialchars('top.launchView(\'' . $this->foreignTable . '\', \'' . $recordObject->getValue('uid') . '\'); return false;').'">'.
				'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/zoom2.gif','width="12" height="12"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xml:showInfo',1).'" alt="" />'.
				'</a>';
		}
			// If the table is NOT a read-only table, then show these links:
		if (!$tcaTableCtrl['readOnly'] && !$isVirtualRecord)	{

				// "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row or if default values can depend on previous record):
			if ($enabledControls['new'] && ($enableManualSorting || $tcaTableCtrl['useColumnsForDefaultValues']))	{
				if (
					(!$isPagesTable && ($calcPerms&16)) || 	// For NON-pages, must have permission to edit content on this parent page
					($isPagesTable && ($calcPerms&8))		// For pages, must have permission to create new pages here.
					)	{
					$onClick = "return inline.createNewRecord('".$nameObjectFt."','" . $recordObject->getValue('uid') . "')";
					$class = ' class="inlineNewButton '.$this->inlineData['config'][$nameObject]['md5'].'"';
					if ($config['inline']['inlineNewButtonStyle']) {
						$style = ' style="'.$config['inline']['inlineNewButtonStyle'].'"';
					}
					$cells['new']='<a href="#" onclick="'.htmlspecialchars($onClick).'"'.$class.$style.'>'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/new_'.($isPagesTable?'page':'el').'.gif','width="'.($isPagesTable?13:11).'" height="12"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xml:new'.($isPagesTable?'Page':'Record'),1).'" alt="" />'.
							'</a>';
				}
			}

				// Drag&Drop Sorting: Sortable handler for script.aculo.us
			if ($enabledControls['dragdrop'] && $permsEdit && $enableManualSorting && $config['appearance']['useSortable'])	{
				$cells['dragdrop'] = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/move.gif','width="16" height="16" hspace="2"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.move',1).'" alt="" style="cursor: move;" class="sortableHandle" />';
			}

				// "Up/Down" links
			if ($enabledControls['sort'] && $permsEdit && $enableManualSorting)	{
				$onClick = "return inline.changeSorting('".$nameObjectFtId."', '1')";	// Up
				$style = $recordObject->getValue('uid') == $this->firstRecordUid ? 'style="visibility: hidden;"' : '';
				$cells['sort.up']='<a href="#" onclick="'.htmlspecialchars($onClick).'" class="sortingUp" '.$style.'>'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_up.gif','width="11" height="10"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xml:moveUp',1).'" alt="" />'.
						'</a>';

				$onClick = "return inline.changeSorting('".$nameObjectFtId."', '-1')";	// Down
				$style = $recordObject->getValue('uid') == $this->lastRecordUid ? 'style="visibility: hidden;"' : '';
				$cells['sort.down']='<a href="#" onclick="'.htmlspecialchars($onClick).'" class="sortingDown" '.$style.'>'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_down.gif','width="11" height="10"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xml:moveDown',1).'" alt="" />'.
						'</a>';
			}

				// "Hide/Unhide" links:
			$hiddenField = $tcaTableCtrl['enablecolumns']['disabled'];
			if ($enabledControls['hide'] && $permsEdit && $hiddenField && $tcaTableCols[$hiddenField] && (!$tcaTableCols[$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields',$this->foreignTable.':'.$hiddenField)))	{
				$onClick = "return inline.enableDisableRecord('".$nameObjectFtId."')";
				if ($recordObject->getValue($hiddenField)) {
					$cells['hide.unhide']='<a href="#" onclick="'.htmlspecialchars($onClick).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_unhide.gif','width="11" height="10"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xml:unHide'.($isPagesTable?'Page':''),1).'" alt="" id="'.$nameObjectFtId.'_disabled" />'.
							'</a>';
				} else {
					$cells['hide.hide']='<a href="#" onclick="'.htmlspecialchars($onClick).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/button_hide.gif','width="11" height="10"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xml:hide'.($isPagesTable?'Page':''),1).'" alt="" id="'.$nameObjectFtId.'_disabled" />'.
							'</a>';
				}
			}

				// "Delete" link:
			if ($enabledControls['delete'] && ($isPagesTable && $localCalcPerms&4 || !$isPagesTable && $calcPerms&16)) {
				$onClick = "inline.deleteRecord('".$nameObjectFtId."');";
				$cells['delete']='<a href="#" onclick="'.htmlspecialchars('if (confirm('.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('deleteWarning')).')) {	'.$onClick.' } return false;').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/garbage.gif','width="11" height="12"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xml:delete',1).'" alt="" />'.
						'</a>';
			}
			// If this is a virtual record offer a minimized set of icons for user interaction:
		} elseif ($isVirtualRecord) {
			if ($enabledControls['localize'] && isset($rec['__create'])) {
				$onClick = "inline.synchronizeLocalizeRecords('".$nameObjectFt."', " . $recordObject->getValue('uid') . ");";
				$cells['localize'] = '<a href="#" onclick="'.htmlspecialchars($onClick).'">' .
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/localize_el.gif','width="16" height="16"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xml:localize', 1).'" alt="" />' .
					'</a>';
			}
		}

			// If the record is edit-locked	by another user, we will show a little warning sign:
		if ($lockInfo=t3lib_BEfunc::isRecordLocked($this->foreignTable, $recordObject->getValue('uid'))) {
			$cells['locked']='<a href="#" onclick="'.htmlspecialchars('alert('.$GLOBALS['LANG']->JScharCode($lockInfo['msg']).');return false;').'">'.
					'<img'.t3lib_iconWorks::skinImg('','gfx/recordlock_warning3.gif','width="17" height="12"').' title="'.htmlspecialchars($lockInfo['msg']).'" alt="" />'.
					'</a>';
		}

			// Hook: Post-processing of single controls for specific child records:
		/* TODO reenable this
		foreach ($this->hookObjects as $hookObj)	{
			$hookObj->renderForeignRecordHeaderControl_postProcess($parentUid, $this->foreignTable, $rec, $config, $isVirtual, $cells);
		}*/
			// Compile items into a DIV-element:
		return '
											<!-- CONTROL PANEL: ' . $this->foreignTable . ':' . $recordObject->getValue('uid') . ' -->
											<div class="typo3-DBctrl">' . implode('', $cells) . '</div>';
	}


	/*******************************************************
	 *
	 * Helper functions
	 *
	 *******************************************************/

	protected function getIrreIdentifier() {
		return $this->containingElement->getIrreIdentifier();
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

	public function getNestedStackEntry() {
		return array(
			'inline',
			$this->getIrreIdentifierForRecord($this->containingElement->getRecordObject())
		);
	}

	/**
	 * Builds the current nesting stack of forms, sheets and element objects.
	 *
	 * @param boolean $json: Return a JSON string instead of an array - default: false
	 * @param boolean $skipFirst: Skip the first element in the dynNestedStack - default: false
	 * @return mixed Returns an associative array by default. If $json is true, it will be returned as JSON string.
	 */
	protected function getNestedStack($json=false, $skipFirst=false) {
		if (!is_array($this->nestedStack)) {
			$currentLevelObject = $this->containingElement;
			while (is_object($currentLevelObject)) {
				if ($currentStackLevel = $currentLevelObject->getNestedStackEntry()) {
					$stack[] = $currentStackLevel;
				}

				if ($currentLevelObject instanceof t3lib_TCEforms_Element) {
					$currentLevelObject = $currentLevelObject->getContainer();
				} elseif ($currentLevelObject instanceof t3lib_TCEforms_Container_Sheet) {
					$currentLevelObject = $currentLevelObject->getFormObject();
				} elseif ($currentLevelObject instanceof t3lib_TCEforms_Form) {
						// We have reached the top of the object tree
					if ($currentLevelObject == $this->contextObject) {
						break;
					} elseif ($currentLevelObject instanceof t3lib_TCEforms_NestableForm) {
						$currentLevelObject = $currentLevelObject->getContainingElement();
					}
				} else {
					throw new RuntimeException('Whoops. Ran into a dead end while traversing the object tree to get the nested stack of elements.');
				}
			}
			$stack = array_reverse($stack);

			$this->nestedStack = $stack;
		}

		$result = $this->nestedStack;
		if ($skipFirst) {
			array_shift($result);
		}
		return ($json ? json_encode($result) : $result);
	}

	/**
	 * Proxy function for accessing the table of the record this form is placed on.
	 *
	 * @return string
	 */
	protected function getContainerTable() {
		return $this->containingElement->getTable();
	}

	/**
	 * Proxy function for accessing data from the record this form is placed on.
	 *
	 * @param string $key The key of the value to get
	 * @return mixed
	 */
	protected function getContainingRecordValue($key) {
		return $this->containingElement->getValue($key);
	}

	/**
	 * Does some checks on the TCA configuration of the inline field to render.
	 *
	 * @param	array		$config: Reference to the TCA field configuration
	 * @param	string		$table: The table name of the record
	 * @param	string		$field: The field name which this element is supposed to edit
	 * @param	array		$row: The record data array of the parent
	 * @return	boolean		If critical configuration errors were found, false is returned
	 */
	public function checkConfiguration(&$config) {
		$foreign_table = $config['foreign_table'];

			// An inline field must have a foreign_table, if not, stop all further inline actions for this field:
		if (!$foreign_table || !is_array($GLOBALS['TCA'][$foreign_table])) {
			return false;
		}
			// Init appearance if not set:
		if (!isset($config['appearance']) || !is_array($config['appearance'])) {
			$config['appearance'] = array();
		}
			// 'newRecordLinkPosition' is deprecated since TYPO3 4.2.0-beta1, this is for backward compatibility:
		if (!isset($config['appearance']['levelLinksPosition']) && isset($config['appearance']['newRecordLinkPosition']) && $config['appearance']['newRecordLinkPosition']) {
			$config['appearance']['levelLinksPosition'] = $config['appearance']['newRecordLinkPosition'];
		}
			// Set the position/appearance of the "Create new record" link:
		if (isset($config['foreign_selector']) && $config['foreign_selector'] && (!isset($config['appearance']['useCombination']) || !$config['appearance']['useCombination'])) {
			$config['appearance']['levelLinksPosition'] = 'none';
		} elseif (!isset($config['appearance']['levelLinksPosition']) || !in_array($config['appearance']['levelLinksPosition'], array('top', 'bottom', 'both', 'none'))) {
			$config['appearance']['levelLinksPosition'] = 'top';
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
		if (isset($config['appearance']['enabledControls']) && is_array($config['appearance']['enabledControls'])) {
			$config['appearance']['enabledControls'] = array_merge($enabledControls, $config['appearance']['enabledControls']);
		} else {
			$config['appearance']['enabledControls'] = $enabledControls;
		}

		return true;
	}
}
