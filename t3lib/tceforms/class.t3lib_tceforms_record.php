<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * This class serves as a record abstraction for TCEforms. Is instantiated by a form object and
 * responsible for creating and rendering its own HTML form.
 *
 * @author     Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package    TYPO3
 * @subpackage t3lib_TCEforms
 */
// TODO: add getters for field values, implement ArrayAccess interface
class t3lib_TCEforms_Record extends t3lib_TCA_Record {

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

	/**
	 * Long identification string for the sheets.
	 *
	 * @var string
	 */
	protected $sheetIdentifier;

	/**
	 * Short identification string for the sheets. This is guaranteed to be not longer than 8 characters.
	 *
	 * @var unknown_type
	 */
	protected $shortSheetIdentifier;
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
	 * Holds the numbers of all palettes that objects have been created for. Used to check that no
	 * fields are double rendered.
	 *
	 * @var array
	 */
	protected $createdPalettes = array();

	/**
	 * @var boolean
	 */
	protected $new = FALSE;

	/**
	 * The SQL clause for read permissions to a record
	 *
	 * @var string
	 */
	protected $readPermissionsClause;

	/**
	 * The stack of element identifier parts used for creating element identifiers.
	 *
	 * This will usually be imploded with a separator to create an identifier.
	 *
	 * @var array<string>
	 */
	protected $elementIdentifierStack = array();


	/**
	 * The constructor for this class.
	 *
	 * @param string $table The table this record belongs to
	 * @param array $recordData
	 * @param t3lib_TCA_DataStructure $dataStructure
	 */
	public function __construct($table, array $recordData, t3lib_TCA_DataStructure $dataStructure) {
		parent::__construct($table, $recordData, $dataStructure);
		$this->contextRecordObject = $this;
	}

	public function setContextObject(t3lib_TCEforms_Context $contextObject) {
		$this->contextObject = $contextObject;

		return $this;
	}

	public function setParentFormObject(t3lib_TCEforms_Form $formObject) {
		$this->parentFormObject = $formObject;

		return $this;
	}

	public function getParentFormObject() {
		return $this->parentFormObject;
	}

	public function hasLanguage() {
		return FALSE;
	}

	/**
	 * Sets all information that is required for proper element identifier generation.
	 *
	 * @param  array $elementIdentifierStack
	 * @return t3lib_TCEforms_Record
	 */
	public function setElementIdentifierStack(array $elementIdentifierStack) {
		$this->elementIdentifierStack = $elementIdentifierStack;

		$this->elementIdentifierStack[] = array(
			$this->getTable(),
			$this->recordData['uid']
		);

		return $this;
	}

	/**
	 * Returns the stack for building element identifiers
	 *
	 * @return array<string>
	 */
	public function getElementIdentifierStack() {
		return $this->elementIdentifierStack;
	}

	public function init() {
		$this->createFormBuilderInstance();

		$this->buildFormFieldPrefixes();
		$this->registerDefaultLanguageData();

		$this->formBuilder->buildObjectStructure($this);
	}

	protected function createFormBuilderInstance() {
		$this->formBuilder = t3lib_TCEforms_FormBuilder::createInstanceForRecordObject($this);
	}

	/**
	 *
	 * @return void
	 */
	protected function buildFormFieldPrefixes() {
		$this->formFieldNamePrefix = $this->contextObject->createElementIdentifier($this, 'name');
		$this->formFieldIdPrefix = $this->contextObject->createElementIdentifier($this, 'id');
	}

	/**
	 * Returns the label and a special label for new records or the wrapped uid for existing records
	 *
	 * @return array the record label and the "new" label
	 */
	protected function getLabels() {
		if (strstr($this->getValue('uid'), 'NEW')) {
			#t3lib_BEfunc::fixVersioningPid($this->table,$this->record);	// Kasper: Should not be used here because NEW records are not offline workspace versions...

			$truePid = t3lib_BEfunc::getTSconfig_pidValue($this->getTable(), $this->getValue('uid'), $this->getValue('pid'));
			$prec = t3lib_BEfunc::getRecordWSOL('pages', $truePid, 'title');
			$pageTitle = t3lib_BEfunc::getRecordTitle('pages', $prec, TRUE, FALSE);
			$rLabel = '<em>[PID: ' . $truePid . '] ' . $pageTitle . '</em>';

		        // Fetch translated title of the table
			$tableTitle = $GLOBALS['LANG']->sL($this->getDataStructure()->getControlValue('title'));
			if ($this->getTable() === 'pages') {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.createNewPage', TRUE);
				$pageTitle = sprintf($label, $tableTitle);
			} else {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.createNewRecord', TRUE);

				if ($this->getValue('pid') == 0) {
					$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.createNewRecordRootLevel', TRUE);
				}
				$pageTitle = sprintf($label, $tableTitle, $pageTitle);
			}

			$recordLabels = array(
				'###ID_NEW_INDICATOR###' => ' <span class="typo3-TCEforms-newToken">'.
				  $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.new', 1).
				  '</span>',
				'###PAGE_TITLE###' => $pageTitle,
				'###RECORD_LABEL###' => $rLabel
			);
		} else {
			$newLabel = ' <span class="typo3-TCEforms-recUid">[' . $this->getValue('uid') . ']</span>';
			$rLabel   = t3lib_BEfunc::getRecordTitle($this->getTable(), $this->getRecordData(), TRUE, FALSE);
			$prec = t3lib_BEfunc::getRecordWSOL('pages', $this->getValue('pid'), 'title');

				// Fetch translated title of the table
			$tableTitle = $GLOBALS['LANG']->sL($this->getDataStructure()->getControlValue('title'));
			if ($this->getTable() === 'pages') {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.editPage', TRUE);

					// Just take the record title and prepend an edit label.
				$pageTitle = sprintf($label, $tableTitle, $rLabel);
			} else {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.editRecord', TRUE);

				$pageTitle = t3lib_BEfunc::getRecordTitle('pages', $prec, TRUE, FALSE);
				if ($rLabel === t3lib_BEfunc::getNoRecordTitle(TRUE)) {
					$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.editRecordNoTitle', TRUE);
				}
				if ($this->getValue('pid') == 0) {
					$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.editRecordRootLevel', TRUE);
				}

				if ($rLabel !== t3lib_BEfunc::getNoRecordTitle(TRUE)) {

						// Just take the record title and preped an edit label.
					$pageTitle = sprintf($label, $tableTitle, $rLabel, $pageTitle);
				} else {

						// Leave out the record title since it is not set.
					$pageTitle = sprintf($label, $tableTitle, $pageTitle);
				}
			}

			$recordLabels = array(
				'###ID_NEW_INDICATOR###' => $newLabel,
				'###PAGE_TITLE###' => $pageTitle,
				'###RECORD_LABEL###' => $rLabel
			);
		}
		return $recordLabels;
	}


	public function render() {
		global $TCA;

		$wrap = t3lib_parsehtml::getSubpart($this->contextObject->getTemplateContent(), '###TOTALWRAP###');
		if ($wrap == '') {
			throw new RuntimeException('No template wrap for record found.');
		}

		$recordLabels = $this->getLabels();
		$recordContent = $this->getSheetContents();

		$markerArray = t3lib_div::array_merge($recordLabels, array(
			'###TABLE_TITLE###' => htmlspecialchars($GLOBALS['LANG']->sL($TCA[$this->getTable()]['ctrl']['title'])),
			'###RECORD_ICON###' => t3lib_iconWorks::getSpriteIconForRecord($this->getTable(), $this->getRecordData(), array('title' => $this->getPath())),
			'###WRAP_CONTENT###' => $recordContent
		));

		$content = t3lib_parsehtml::substituteMarkerArray($wrap, $markerArray);

		return $content;
	}

	protected function getSheetContents() {
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
			$content = $this->getDynTabMenu($tabContents);
		} else {
			$content = $tabContents[0]['content'];
		}

		return $content;
	}

	/**
	 * Create dynamic tab menu
	 *
	 * @param	array		Parts for the tab menu, fed to template::getDynTabMenu()
	 * @param	string		ID string for the tab menu
	 * @param	integer		If set to '1' empty tabs will be removed, If set to '2' empty tabs will be disabled
	 * @return	string		HTML for the menu
	 */
	public function getDynTabMenu($parts, $dividersToTabsBehaviour = 1) {
		if (is_object($GLOBALS['TBE_TEMPLATE'])) {
			return $GLOBALS['TBE_TEMPLATE']->getDynTabMenu($parts, $this->sheetIdentifier, 0, false, 50, 1, false, 1, $dividersToTabsBehaviour);
		} else {
			$output = '';
			foreach($parts as $singlePad) {
				$output .= '
				<h3>' . htmlspecialchars($singlePad['label']) . '</h3>
				' . ($singlePad['description'] ? '<p class="c-descr">' . nl2br(htmlspecialchars($singlePad['description'])) . '</p>' : '') . '
				' . $singlePad['content'];
			}

			return '<div class="typo3-dyntabmenu-divs">' . $output . '</div>';
		}
	}


	public function setFieldList($fieldList) {
		if (!is_array($fieldList)) {
			$fieldList = t3lib_div::trimExplode(',', $fieldList, TRUE);
		}

		$this->fieldList = $fieldList;

		return $this;
	}

	/**
	 * Returns a unique identifier of the form <table>:<uid> for the record
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->getTable() . ':' . $this->recordData['uid'];
	}

	public function getShortSheetIdentifier() {
		// TODO replace this by call to this method in init()
		if (!$this->shortSheetIdentifier) {
			$this->buildSheetIdentifiers();
		}

		return $this->shortSheetIdentifier;
	}

	protected function buildSheetIdentifiers() {
		$this->sheetIdentifier = 'TCEforms:'.$this->getIdentifier();
		// @TODO generate identifiers another way
		$this->shortSheetIdentifier = 'DTM-' . t3lib_div::shortMD5($this->sheetIdentifier);
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
	 * Returns the value of a field from this record.
	 *
	 * @param  mixed $field May be a string (fieldname) or an element object. In the second case, the object is examined to determine the field value
	 * @return array
	 */
	public function getValue($field) {
		if (is_string($field)) {
			return $this->recordData[$field];
		} elseif (is_a($field, 't3lib_TCEforms_Element_Abstract')) {
			/** @var $field t3lib_TCEforms_Element_Abstract */
			return $this->recordData[$field->getFieldname()];
		}
	}

	public function isNew() {
		return $this->new;
	}

	public function getIcon() {
		return t3lib_iconWorks::getSpriteIconForRecord($this->getTable(), $this->getRecordData(), array('title' => $this->getPath()));
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

	public function getTCAdefinitionForField($fieldName) {
		return $this->dataStructure->getFieldConfiguration($fieldName);
	}

	// TODO: load data if neccessary before returning anything
	public function getDefaultLanguageData() {
		return $this->defaultLanguageData;
	}

	public function getDefaultLanguageValue($field) {
		return $this->defaultLanguageData[$field];
	}

	public function getDefaultLanguageDiffData() {
		return $this->defaultLanguageData_diff;
	}

	public function getDefaultLanguageDiffValue($field) {
		return $this->defaultLanguageData_diff[$field];
	}

	public function setContextRecordObject(t3lib_TCEforms_Record $contextRecordObject) {
		$this->contextRecordObject = $contextRecordObject;

		return $this;
	}

	public function getContextRecordObject() {
		return $this->contextRecordObject;
	}

	/**
	 * Return record path (visually formatted, using t3lib_BEfunc::getRecordPath() )
	 *
	 * @return	string		The record path.
	 * @see t3lib_BEfunc::getRecordPath()
	 *
	 * @TODO Check if using fixVersioningPid (which modifies recordData) could do any harm.
	 *       If not, it may be moved into the constructor.
	 */
	function getPath()	{
		t3lib_BEfunc::fixVersioningPid($this->table, $this->recordData);
		list($tscPID, $thePidValue) = $this->getTSCpid($this->table, $this->recordData['uid'], $this->recordData['pid']);
		if ($thePidValue >= 0) {
			return t3lib_BEfunc::getRecordPath($tscPID, $this->getReadPermissionsClause(), 15);
		}
	}


	/**
	 * Returns the select-page read-access SQL clause.
	 * Returns cached string, so you can call this function as much as you like without performance loss.
	 *
	 * @return	string
	 *
	 * @TODO check if this should be moved to the context object
	 */
	function getReadPermissionsClause() {
		if (!isset($this->readPermissionsClause)) {
			$this->readPermissionsClause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		}
		return $this->readPermissionsClause;
	}


	/********************************************
	 *
	 * Sheet functions
	 *
	 ********************************************/

	public function addSheetObject(t3lib_TCEforms_Container_Sheet $sheetObject) {
		$sheetObject->setContextObject($this->contextObject)
		            ->setFormObject($this->parentFormObject)
		            ->setElementIdentifierStack($this->elementIdentifierStack)
		            ->setRecordObject($this)
		            ->init();
		$this->sheetObjects[] = $sheetObject;

		t3lib_div::devLog('Added sheet no. ' . count($this->sheetObjects) . ' to record ' . $this->getIdentifier() . '.', 't3lib_TCEforms_Record', t3lib_div::SYSLOG_SEVERITY_INFO);
	}

	public function getSheets() {
		return $this->sheetObjects;
	}

	public function getSheetCount() {
		return count($this->sheetObjects);
	}


	/************************************************************
	 *
	 * Display of localized content etc.
	 *
	 ************************************************************/


	/**
	 * Initializes language icons etc.
	 *
	 * param	string	Sys language uid OR ISO language code prefixed with "v", eg. "vDA"
	 * @return	void
	 */
	public function getLanguageIcon($sys_language_uid) {
		global $TCA, $LANG;
		static $cachedLanguageFlag = array();

		$mainKey = $this->table.':'.$this->recordData['uid'];

		if (!isset($cachedLanguageFlag[$mainKey])) {
			t3lib_BEfunc::fixVersioningPid($this->table, $this->recordData);
			list($tscPID,$thePidValue) = $this->getTSCpid($this->table, $this->recordData['uid'], $this->recordData['pid']);

			/** @var $t8Tools t3lib_transl8tools */
			$t8Tools = t3lib_div::makeInstance('t3lib_transl8tools');
			$cachedLanguageFlag[$mainKey] = $t8Tools->getSystemLanguages($tscPID, $this->backPath);
		}

			// Convert sys_language_uid to sys_language_uid if input was in fact a string (ISO code expected then)
		if (!t3lib_div::testInt($sys_language_uid)) {
			foreach($cachedLanguageFlag[$mainKey] as $rUid => $cD) {
				if ('v' . $cD['ISOcode'] === $sys_language_uid) {
					$sys_language_uid = $rUid;
				}
			}
		}

		$out = '';
		if ($cachedLanguageFlag[$mainKey][$sys_language_uid]['flagIcon']) {
			$out .= t3lib_iconWorks::getSpriteIcon($cachedLanguageFlag[$mainKey][$sys_language_uid]['flagIcon']);
			$out .= '&nbsp;';
	   } else if ($cachedLanguageFlag[$mainKey][$sys_language_uid]['title']) {
			$out .= '[' . $cachedLanguageFlag[$mainKey][$sys_language_uid]['title'] . ']';
			$out .= '&nbsp;';
		}
		return $out;
	}

	/**
	 * Return TSCpid (cached)
	 * Using t3lib_BEfunc::getTSCpid()
	 *
	 * @return	integer		Returns the REAL pid of the record, if possible. If both $uid and $pid is strings, then pid=-1 is returned as an error indication.
	 * @see t3lib_BEfunc::getTSCpid()
	 */
	function getTSCpid() {
		static $cache = array();

		$key = $this->table.':'.$this->recordData['uid'].':'.$this->recordData['pid'];
		if (!isset($cache[$key])) {
			$cache[$key] = t3lib_BEfunc::getTSCpid($this->table, $this->recordData['uid'], $this->recordData['pid']);
		}
		return $cache[$key];
	}
}

?>
