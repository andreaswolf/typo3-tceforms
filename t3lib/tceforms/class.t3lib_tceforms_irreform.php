<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_form.php');
require_once(PATH_t3lib.'interfaces/interface.t3lib_tceforms_nestableform.php');

/*
 * TODO check if inlineFirstPid may be replaced by a call to context object to get the pid
 */
class t3lib_TCEforms_IRREForm extends t3lib_TCEforms_Form implements t3lib_TCEforms_NestableForm {

	/**
	 * The element object containing this form.
	 * @var t3lib_TCEforms_Element_Abstract
	 */
	protected $containingElement;

	protected $firstRecordUid;

	protected $lastRecordUid;

	public function __construct() {
		parent::__construct();
	}

	public function init() {
		$this->formBuilder = clone($this->formBuilder);
		$this->foreignTable = $this->containingElement->getForeignTable();
	}

	public function addRecord($table, $record) {
		if (!$this->firstRecordUid) {
			$this->firstRecordUid = $record['uid'];
		}
		parent::addRecord($table, $record);
	}

	/**
	 * Sets the element containing this form.
	 * @param t3lib_TCEforms_Element $elementObject
	 * @return t3lib_TCEforms_NestableForm A reference to $this, for easier use
	 */
	public function setContainingElement(t3lib_TCEforms_Element_Abstract $elementObject) {
		$this->containingElement = $elementObject;
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
		return $this->contextObject->getTemplateContent();
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
		$irreFieldNames = $this->getFormFieldNamePrefix() . $this->getIrreIdentifierForRecord($recordObject);
		$formFieldNames = $this->getFormFieldNamePrefix() . $this->getFieldIdentifier($recordObject);
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
			'###ONCLICK###' => " onClick=\"return inline.expandCollapseRecord('" . htmlspecialchars($irreFieldNames) . "', " . ($this->containingElement->expandOnlyOneRecordAtATime() ? '1' : '0') . ");\"",
			'###FIELDS_STYLE###' => $fieldsStyle,
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
			$this->inlineViewState = (array)$inlineView[$this->containingElement->getTable()][$this->containingElement->getValue('uid')];
			t3lib_div::devLog('inlineViewState for ' . $this->containingElement->getTable() . ':' . $this->containingElement->getValue('uid') . ': ' . serialize($this->inlineViewState), __CLASS__);
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

		$tcaTableCtrl =& $GLOBALS['TCA'][$this->foreignTable]['ctrl'];
		$tcaTableCols =& $GLOBALS['TCA'][$this->foreignTable]['columns'];

		$isPagesTable = $this->foreignTable == 'pages' ? true : false;
		$isOnSymmetricSide = t3lib_loadDBGroup::isOnSymmetricSide($this->record['uid'], $config, $recordObject->getValue('uid'));
		$enableManualSorting = $tcaTableCtrl['sortby'] || $config['MM'] || (!$isOnSymmetricSide && $config['foreign_sortby']) || ($isOnSymmetricSide && $config['symmetric_sortby']) ? true : false;

		$nameObject = $this->getIrreIdentifierForRecord($recordObject);
		$nameObjectFt = $nameObject . '[' . $this->foreignTable . ']';
		$nameObjectFtId = $nameObjectFt . '[' . $recordObject->getValue('uid') . ']';

		$calcPerms = $GLOBALS['BE_USER']->calcPerms(
			t3lib_BEfunc::readPageAccess($recordObject->getValue('pid'), $GLOBALS['BE_USER']->getPagePermsClause(1))
		);

			// If the listed table is 'pages' we have to request the permission settings for each page:
		if ($isPagesTable)	{
			$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages', $recordObject->getValue('uid')));
		}

			// This expresses the edit permissions for this particular element:
		$permsEdit = ($isPagesTable && ($localCalcPerms&2)) || (!$isPagesTable && ($calcPerms&16));

			// Controls: Defines which controls should be shown
		$enabledControls = array(
			'info'		=> true,
			'new'		=> true,
			'dragdrop'	=> true,
			'sort'		=> true,
			'hide'		=> true,
			'delete'	=> true,
			'localize'	=> true,
		);
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
}
