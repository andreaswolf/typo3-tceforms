<?php

require_once (PATH_t3lib.'tceforms/class.t3lib_tceforms_record.php');
require_once (PATH_t3lib.'interfaces/interface.t3lib_tceforms_context.php');
require_once (PATH_t3lib.'tca/class.t3lib_tca_datastructure.php');
require_once (PATH_t3lib.'tca/datastructure/class.t3lib_tca_datastructure_tcaresolver.php');

// TODO: check if docLarge is needed
class t3lib_TCEforms_Form implements t3lib_TCEforms_Context {

	/**
	 * All record objects belonging to this form
	 *
	 * @var array
	 */
	protected $recordObjects = array();

	/**
	 * If all palettes are collapsed (default) or expanded
	 *
	 * @var boolean
	 */
	protected $palettesCollapsed;

	protected $formName;

	/**
	 * Prefix for form field name
	 *
	 * @var string
	 */
	protected $formFieldNamePrefix;
	protected $formFieldFileNamePrefix;

	protected $formFieldIdPrefix;

	/**
	 *
	 *
	 * @var array
	 */
	protected $fieldList;

	/**
	 * Fields on this form marked as required
	 *
	 * @var array
	 */
	protected $requiredFields = array();

	/**
	 * Fields on this form which are limited to a specific range
	 *
	 * @var array
	 */
	protected $rangeFields = array();

	/**
	 *
	 * @var string
	 */
	protected $backPath;

		// Internal, registers for user defined functions etc.
	protected $additionalCodePreForm = array();			// Additional HTML code, printed before the form.
	protected $additionalJSPreForm = array();			// Additional JavaScript, printed before the form
	protected $additionalJSPostForm = array();			// Additional JavaScript printed after the form
	protected $additionalJSSubmit = array();			// Additional JavaScript executed on submit; If you set "OK" variable it will raise an error about RTEs not being loaded and offer to block further submission.

	/**
	 * Used to indicate the mode of CSH (Context Sensitive Help), whether it should be icons-only
	 * ('icon'), full description ('text') or not at all (blank).
	 *
	 * @var  string
	 */
	// t3lib_tceforms->edit_showFieldHelp
	protected $editFieldHelpMode;

	/**
	 * The page renderer object
	 *
	 * @var t3lib_PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * Whether or not the RTE is enabled.
	 *
	 * @var boolean
	 */
	protected $RteEnabled;

	/**
	 * Counter of all RTE elements on this form
	 *
	 * @var integer
	 */
	protected $RTEcounter = 0;

	protected $readOnly = FALSE;

	/**
	 * @var boolean
	 */
	protected $clickmenuEnabled = FALSE;

	protected $doSaveFieldName = '';

	/**
	 * Triggers loading the Javascript file with the md5 hash algorithm implementation
	 * @boolean
	 */
	protected $loadMd5Javascript = TRUE;

	/**
	 * This objects top-level context (i.e., the root of the object tree this form belongs to
	 *
	 * @var t3lib_TCEforms_Context
	 */
	protected $contextObject;

	/**
	 * The fields to hide on this form. This is a two-dimensional array, the first level are table
	 * names, the second are the field names on this table.
	 *
	 * @var array
	 */
	protected $hiddenFields = array();

	/**
	 * Code to render into the form for hidden fields.
	 *
	 * @var array
	 */
	protected $hiddenFieldsHtmlCode = array();

	protected static $cachedTSconfig;

	/**
	 * The inline elements used in this form (and all subforms)
	 *
	 * @var array
	 */
	protected $inlineElementObjects = array();


	// TODO implement variable defaultStyle + getter/setter (replacement for defStyle of old tceforms)


	public function __construct() {
		t3lib_div::devLog('Instantiated new TCEforms form.', 't3lib_TCEforms', t3lib_div::SYSLOG_SEVERITY_INFO);

			// TODO: make this adjustable!
		$this->formName = 'editform';

		$this->setFormFieldNamePrefix('data');//$this->TCEformsObject->prependFormFieldNames;
		$this->setFormFieldIdPrefix('data');

		$this->contextObject = $this;
	}

	public function init() {
		$this->pageRenderer = $GLOBALS['SOBE']->doc->getPageRenderer();
	}

	public function render() {
		t3lib_div::devLog('Started rendering TCEforms form.', 't3lib_TCEforms', t3lib_div::SYSLOG_SEVERITY_INFO);

		foreach ($this->recordObjects as $recordObject) {
			$content .= $this->renderRecordObject($recordObject);
		}

		return $content;
	}


	public function addRecord($table, $record) {
		t3lib_div::devLog('Added record ' . $table . ':' . $record['uid'] . ' to TCEforms form.', 't3lib_TCEforms', t3lib_div::SYSLOG_SEVERITY_INFO);

		if (!isset($GLOBALS['TCA'][$table])) {
			t3lib_div::loadTCA($table);
		}

		$dataStructure = t3lib_TCA_DataStructure_TCAResolver::resolveDataStructure($table);

			// TODO move this to a more appropriate place
		$GLOBALS['LANG']->loadSingleTableDescription($table);

		$recordObject = new t3lib_TCEforms_Record($table, $record, $GLOBALS['TCA'][$table], $dataStructure);

		if (count($this->fieldList) > 0) {
			$recordObject->setFieldList($this->fieldList);
		}

		$recordObject->setParentFormObject($this)
		             ->setContextObject($this->contextObject)
		             ->init();

		$this->recordObjects[] = $recordObject;

		return $recordObject;
	}


	public function getFormFieldNamePrefix() {
		return $this->formFieldNamePrefix;
	}

	public function setFormFieldNamePrefix($prefix) {
		$this->formFieldNamePrefix = $prefix;
		$this->formFieldFileNamePrefix = $prefix;

		return $this;
	}

	public function getFormFieldIdPrefix() {
		return $this->formFieldIdPrefix;
	}

	public function setFormFieldIdPrefix($prefix) {
		$this->formFieldIdPrefix = $prefix;
	}

	/**
	 * Registers fields from a table to be hidden in the form. Their values will be passed via
	 * hidden form fields.
	 *
	 * @param string $table
	 * @param array $fields
	 * @return void
	 */
	public function registerHiddenFields($table, array $fields) {
		$this->hiddenFields[$table] = t3lib_div::array_merge((array)$this->hiddenFields[$table], $fields);
	}

	/**
	 * Returns TRUE if the specified field will be hidden on the form. The field value will be passed
	 * via a hidden HTML field.
	 *
	 * @param string $table
	 * @param string $fieldName
	 * @return boolean
	 */
	public function isFieldHidden($table, $fieldName) {
		if (!isset($this->hiddenFields[$table])) {
			return FALSE;
		}

		return in_array($fieldName, $this->hiddenFields[$table]);
	}

	/**
	 * Registers an IRRE object with the context object
	 *
	 * @param t3lib_TCEforms_IrreForm $inlineElementObject
	 * @return void
	 */
	public function registerInlineElement(t3lib_TCEforms_IrreForm $inlineElementObject) {
		$this->inlineElementObjects[] = $inlineElementObject;
	}

	/**
	 * Returns TRUE if any IRRE elements have been registered for the form.
	 *
	 * @return boolean
	 */
	public function hasInlineElements() {
		return (count($this->inlineElementObjects) > 0);
	}

	/**
	 * Returns necessary JavaScript for the top
	 *
	 * @return  string  A <script></script> section with JavaScript.
	 */
	public function renderJavascriptBeforeForm() {
		$out = '';

			// Additional top HTML:
		if (count($this->additionalCodePreForm)) {
			$out.= implode('

				<!-- NEXT: -->
			',$this->additionalCodePreForm);
		}

			// Additional top JavaScript
		if (count($this->additionalJSPreForm)) {
			$out.='


		<!--
			JavaScript in top of page (before form):
		-->

		<script type="text/javascript">
			/*<![CDATA[*/

			'.implode('

				// NEXT:
			',$this->additionalJSPreForm).'

			/*]]>*/
		</script>
			';
		}

		return $out;
	}

	public function renderJavascriptAfterForm($formname='forms[0]') {
		$elements = array();

		// TODO: use $this->formName instead (as soon as there are getters/setters for it
		$formname = $this->formName;

			// required:
		//foreach ($this->sheetObjects as $sheet) {
			foreach ($this->requiredFields as $itemImgName => $itemName) {
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
			foreach ($this->rangeFields as $itemName => $range) {
				if (preg_match('/^(.+)\[((\w|\d|_)+)\]$/', $itemName, $match)) {
					$record = $match[1];
					$field = $match[2];
					$elements[$record][$field]['range'] = array($range[0], $range[1]);
					$elements[$record][$field]['rangeImg'] = $range['imgName'];
				}
			}
		//}

		$javaScriptFiles = $this->includeJavascriptFiles();
		$out .= $this->javascriptForUpdate($formname);

		$this->addToTBE_EDITOR_fieldChanged_func('TBE_EDITOR.fieldChanged_fName(fName,formObj[fName+"_list"]);');

			// add JS required for inline fields
		if (count($this->inlineElementObjects)) {
			$inlineData = array();
			foreach ($this->inlineElementObjects as $inlineElement) {
				$inlineData = t3lib_div::array_merge_recursive_overrule($inlineData, $inlineElement->getInlineData());
			}
			$out .= '
			inline.addToDataArray(' . json_encode($inlineData) . ');
			';
		}
			// Registered nested elements for tabs or inline levels:
		if (count($this->requiredNested)) {
			$out .= '
			TBE_EDITOR.addNested(' . json_encode($this->requiredNested) . ');
			';
		}
			// elements which are required or have a range definition:
		if (count($elements)) {
			$out .= '
			TBE_EDITOR.addElements(' . json_encode($elements) . ');
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

		$out .= chr(10) . implode(chr(10), $this->additionalJSPostForm) . chr(10) .
		  $this->JScode['evaluation'] . $this->JScode['validation'];
		$out .= '
			TBE_EDITOR.loginRefreshed();
		';

			// Regular direct output:
		if (!$update) {
			$spacer . implode($spacer, $jsFile);
			$out  = $spacer . implode($spacer, $javaScriptFiles) . t3lib_div::wrapJS($out);
		}


		$out .= '


			<!--
			 	JavaScript after the form has been drawn:
			-->

			<script type="text/javascript">
				/*<![CDATA[*/

				formObj = document.forms[0]
				backPath = "'.$this->backPath.'";

				function TBE_EDITOR_fieldChanged_func(fName, formObj) {
					'.$this->TBE_EDITOR_fieldChanged_func.'
				}

				/*]]>*/
			</script>';

		return $out;
	}

	protected function javascriptForUpdate($formname) {
			// if IRRE fields were processed, add the JavaScript functions:
		if ($this->inline->inlineCount) {
			$out .= '
			inline.setPrependFormFieldNames("' . $this->inline->prependNaming . '");
			inline.setNoTitleString("' . addslashes(t3lib_BEfunc::getNoRecordTitle(true)) . '");
			';
		}

			// Toggle icons:
		$toggleIcon_open = t3lib_iconWorks::getSpriteIcon('actions-move-down');
		$toggleIcon_close = t3lib_iconWorks::getSpriteIcon('actions-move-right');

		$out .= '
		var toggleIcon_open = \'' . $toggleIcon_open . '\';
		var toggleIcon_close = \'' . $toggleIcon_close . '\';

		TBE_EDITOR.images.req.src = "' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/required_h.gif', '', 1) . '";
		TBE_EDITOR.images.cm.src = "' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/content_client.gif', '', 1) . '";
		TBE_EDITOR.images.sel.src = "' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/content_selected.gif', '', 1) . '";
		TBE_EDITOR.images.clear.src = "' . $this->backPath . 'clear.gif";

		TBE_EDITOR.auth_timeout_field = ' . intval($GLOBALS['BE_USER']->auth_timeout_field) . ';
		TBE_EDITOR.formname = "' . $formname . '";
		TBE_EDITOR.formnameUENC = "' . rawurlencode($formname) . '";
		TBE_EDITOR.backPath = "' . addslashes($this->backPath) . '";
		TBE_EDITOR.prependFormFieldNames = "'.$this->formFieldNamePrefix . '";
		TBE_EDITOR.prependFormFieldNamesUENC = "'.rawurlencode($this->formFieldNamePrefix) . '";
		TBE_EDITOR.prependFormFieldNamesCnt = ' . substr_count($this->formFieldNamePrefix, '[') . ';
		TBE_EDITOR.isPalettedoc = ' . ($this->isPalettedoc ? addslashes($this->isPalettedoc) : 'null') . ';
		TBE_EDITOR.doSaveFieldName = "' . ($this->doSaveFieldName ? addslashes($this->doSaveFieldName) : '') . '";
		TBE_EDITOR.labels.fieldsChanged = ' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.fieldsChanged')) . ';
		TBE_EDITOR.labels.fieldsMissing = ' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.fieldsMissing')) . ';
		TBE_EDITOR.labels.refresh_login = ' . $GLOBALS['LANG']->JScharCode($this->getLL('m_refresh_login')) . ';
		TBE_EDITOR.labels.onChangeAlert = ' . $GLOBALS['LANG']->JScharCode($this->getLL('m_onChangeAlert')) . ';
		evalFunc.USmode = ' . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']?'1':'0').';
		TBE_EDITOR.backend_interface = "' . $GLOBALS['BE_USER']->uc['interfaceSetup'] . '";
		';

	        // needed for tceform manipulation (date picker)
		$typo3Settings = array(
			'datePickerUSmode' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? 1 : 0,
			'dateFormat'       => array('j-n-Y', 'G:i j-n-Y'),
			'dateFormatUS'     => array('n-j-Y', 'G:i n-j-Y'),
		);
		$out .= $this->pageRenderer->addInlineSettingArray('', $typo3Settings);

		return $out;
	}

	protected function includeJavascriptFiles() {
		$jsFile = array();

		if ($this->loadMd5Javascript) {
			$jsFile[] =	'<script type="text/javascript" src="' . $this->backPath . 'md5.js"></script>';
		}

		$this->pageRenderer->loadPrototype();
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->loadScriptaculous();
		$GLOBALS['SOBE']->doc->loadJavascriptLib('../t3lib/jsfunc.evalfield.js');

		if (!($GLOBALS['BE_USER']->uc['resizeTextareas'] == '0' && $GLOBALS['BE_USER']->uc['resizeTextareas_Flexible'] == '0')) {
			$this->pageRenderer->addCssFile($this->backPath . '../t3lib/js/extjs/ux/resize.css');
			$GLOBALS['SOBE']->doc->loadJavascriptLib('../t3lib/js/extjs/ux/ext.resizable.js');
		}
		$resizableSettings = array(
			'textareaMaxHeight' => $GLOBALS['BE_USER']->uc['resizeTextareas_MaxHeight'] >0 ? $GLOBALS['BE_USER']->uc['resizeTextareas_MaxHeight'] : '600',
			'textareaFlexible' => (!$GLOBALS['BE_USER']->uc['resizeTextareas_Flexible'] == '0'),
			'textareaResize' => (!$GLOBALS['BE_USER']->uc['resizeTextareas'] == '0'),
		);
		$this->pageRenderer->addInlineSettingArray('', $resizableSettings);

		$GLOBALS['SOBE']->doc->loadJavascriptLib('jsfunc.tbe_editor.js');
		$GLOBALS['SOBE']->doc->loadJavascriptLib('js/tceforms.js');
		$GLOBALS['SOBE']->doc->loadJavascriptLib('../t3lib/js/extjs/tceforms.js');

			// if IRRE fields were processed, add the JavaScript functions:
		if ($this->hasInlineElements()) {
			$GLOBALS['SOBE']->doc->loadJavascriptLib('../t3lib/jsfunc.inline.js');
		}

		return $jsFile;
	}

	/**
	 * Sets the global status of all palettes to collapsed/uncollapsed
	 *
	 * @param  boolean  $collapsed
	 */
	public function setPalettesCollapsed($collapsed) {
		$this->palettesCollapsed = (bool)$collapsed;

		return $this;
	}

	/**
	 * Returns whether or not the palettes are collapsed
	 *
	 * @return  boolean
	 */
	public function getPalettesCollapsed() {
		return $this->palettesCollapsed;
	}

	public function setFieldList($fieldList) {
		$this->fieldList = array_unique(t3lib_div::trimExplode(',', $fieldList, 1));

		return $this;
	}

	public function addHtmlForHiddenField($elementName, $code) {
		// TODO: handle already set keys here
		$this->hiddenFieldsHtmlCode[$elementName] = $code;
	}

	protected function getHtmlForHiddenFields() {
		return $this->hiddenFieldsHtmlCode;
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
			die('Template file <em>' . $filePath . '</em> does not exist. [1216911730]');
		}

		$this->templateContent = file_get_contents($filePath);

		return $this;
	}

	public function getTemplateContent() {
		return $this->templateContent;
	}

	/**
	 * Create dynamic tab menu
	 *
	 * @param   array    Parts for the tab menu, fed to template::getDynTabMenu()
	 * @param   string   ID string for the tab menu
	 * @param   integer  If set to '1' empty tabs will be removed, If set to '2' empty tabs will be disabled
	 * @return  string   HTML for the menu
	 */
	protected function getDynTabMenu($parts, $idString, $dividersToTabsBehaviour = 1) {
		if (is_object($GLOBALS['TBE_TEMPLATE'])) {
			$GLOBALS['TBE_TEMPLATE']->backPath = $this->backPath;
			return $GLOBALS['TBE_TEMPLATE']->getDynTabMenu($parts, $idString, 0, false, 50, 1, false, 1, $dividersToTabsBehaviour);
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

	/**
	 * Renders a record object into a HTML form.
	 *
	 * @param t3lib_TCEforms_Record $recordObject
	 * @return string The rendered record form, ready to be put on a page
	 */
	protected function renderRecordObject(t3lib_TCEforms_Record $recordObject) {
		global $TCA;

		$recordContent = $recordObject->render();

		$wrap = t3lib_parsehtml::getSubpart($this->getTemplateContent(), '###TOTAL_WRAP###');
		if ($wrap == '') {
			throw new RuntimeException('No template wrap for record found.');
		}

		$recordLabels = $this->getRecordLabels($recordObject);

		$markerArray = t3lib_div::array_merge($recordLabels, array(
			'###TABLE_TITLE###' => htmlspecialchars($this->sL($TCA[$recordObject->getTable()]['ctrl']['title'])),
			'###RECORD_ICON###' => t3lib_iconWorks::getIconImage($recordObject->getTable(), $recordObject->getRecordData(), $this->getBackpath(), 'class="absmiddle"' . $titleA),
			'###WRAP_CONTENT###' => $recordContent
		));

		$content = t3lib_parsehtml::substituteMarkerArray($wrap, $markerArray);

		return $content . implode('', $this->getHtmlForHiddenFields());
	}

	/**
	 * Returns the label and a special label for new records or the wrapped uid for existing records
	 *
	 * @param t3lib_TCEforms_Record $recordObject
	 * @return array the record label and the "new" label
	 */
	protected function getRecordLabels(t3lib_TCEforms_Record $recordObject) {
		if (strstr($recordObject->getValue('uid'), 'NEW')) {
			#t3lib_BEfunc::fixVersioningPid($this->table,$this->record);	// Kasper: Should not be used here because NEW records are not offline workspace versions...

			$truePid = t3lib_BEfunc::getTSconfig_pidValue($recordObject->getTable(), $recordObject->getValue('uid'), $recordObject->getValue('pid'));
			$prec = t3lib_BEfunc::getRecordWSOL('pages', $truePid, 'title');
			$pageTitle = t3lib_BEfunc::getRecordTitle('pages', $prec, TRUE, FALSE);

			$recordLabels = array(
				'###ID_NEW_INDICATOR###' => ' <span class="typo3-TCEforms-newToken">'.
				  $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.new', 1).
				  '</span>',
				'###PAGE_TITLE###' => $pageTitle,
				'###RECORD_LABEL###' => '<em>[PID: ' . $truePid . '] ' . $pageTitle . '</em>'
			);

		} else {
			$prec = t3lib_BEfunc::getRecordWSOL('pages', $recordObject->getValue('pid'), 'title');

			$recordLabels = array(
				'###ID_NEW_INDICATOR###' => ' <span class="typo3-TCEforms-recUid">[' . $recordObject->getValue('uid') . ']</span>',
				'###PAGE_TITLE###' => t3lib_BEfunc::getRecordTitle('pages', $prec, TRUE, FALSE),
				'###RECORD_LABEL###' => t3lib_BEfunc::getRecordTitle($recordObject->getTable(), $recordObject->getRecordData(), TRUE, FALSE)
			);
		}
		return $recordLabels;
	}

	/**
	 * Returns element reference for form element name
	 *
	 * @param   string  Form element name
	 * @return  string  Form element reference (JS)
	 */
	// TODO: check compatibility with nested forms
	public function elName($itemName) {
		return 'document.' . $this->formName . "['" . $itemName . "']";
	}

	/**
	 * Returns 'this.blur();' string, if supported.
	 *
	 * @return  string  If the current browser supports styles, the string 'this.blur();' is returned.
	 */
	// TODO: move to Element_Abstract
	public function blur() {
		return $GLOBALS['CLIENT']['FORMSTYLE'] ? 'this.blur();' : '';
	}

	/**
	 * Returns true if descriptions should be loaded always
	 *
	 * @param   string   Table for which to check
	 * @return  boolean
	 */
	public function doLoadTableDescription($table) {
		global $TCA;

		return $TCA[$table]['interface']['always_description'];
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

	/**
	 * Returns the "returnUrl" of the form. Can be set externally or will be taken from "t3lib_div::linkThisScript()"
	 *
	 * @return  string   Return URL of current script
	 */
	function thisReturnUrl()	{
		return $this->returnUrl ? $this->returnUrl : t3lib_div::linkThisScript();
	}


	/********************************************
	 *
	 * JavaScript related functions
	 *
	 ********************************************/
	// TODO: move to context class/interface
	/**
	 * Adds JavaScript code for form field evaluation. Used to be the global var extJSCode in old t3lib_TCEforms
	 *
	 * @param  string  $JScode
	 */
	public function addToEvaluationJS($JScode) {
		$this->JScode['evaluation'] .= $JScode;
	}

	/**
	 * Returns the Javascript code used for form evaluation
	 *
	 * @return string
	 */
	public function getEvaluationJS() {
		return $this->JScode['evaluation'];
	}

	public function addToValidationJavascriptCode($JScode) {
		$this->JScode['validation'] .= $JScode;
	}

	public function addToTBE_EDITOR_fieldChanged_func($JScode) {
		$this->TBE_EDITOR_fieldChanged_func .= $JScode;
	}



	////////////////////////////////////////////////////////

	/**
	 * Takes care of registering required fields
	 *
	 * @param   string  $name: The name of the form field
	 * @param   string  $value:
	 * @return  void
	 */
	public function registerRequiredField($name, $value) {
		$this->requiredFields[$name] = $value;
			// requiredFields have name/value swapped! For backward compatibility we keep this:
		$itemName = $value;
			// Set the situation of nesting for the current field:
		//$this->registerNestedElement($itemName);
	}

	/**
	 * Takes care of registering a required range for a field
	 *
	 * @param   string  $name: The name of the form field
	 * @param   array   $value:
	 * @return  void
	 */
	public function registerRequiredFieldRange($name, array $value) {
		$this->rangeFields[$name] = $value;
		$itemName = $name;
			// Set the situation of nesting for the current field:
		//$this->registerNestedElement($itemName);
	}

	public function getFormName() {
		return $this->formName;
	}

	public function setBackpath($backpath) {
		$this->backPath = $backpath;

		return $this;
	}

	public function getBackpath() {
		return $this->backPath;
	}

	/**
	 * Sets the object this form is in context of
	 *
	 * @param t3lib_TCEforms_Form $contextObject The object on top of the page tree
	 * @return t3lib_TCEforms_Form This form object
	 */
	public function setContextObject(t3lib_TCEforms_Context $contextObject) {
		$this->contextObject = $contextObject;

		return $this;
	}

	public function isHelpGloballyShown() {
			// TODO use config option here and add setter
		return TRUE;
	}

	public function setEditFieldHelpMode($mode) {
		$this->editFieldHelpMode = $mode;

		return $this;
	}

	public function getEditFieldHelpMode() {
		return $this->editFieldHelpMode;
	}

	public function setRteEnabled($enabled) {
		$this->RteEnabled = $enabled;

		return $this;
	}

	public function isRteEnabled() {
		return $this->RteEnabled;
	}

	public function addToAdditionalCodePreForm($key, $code) {
		if ($key != '') {
			$this->additionalCodePreForm[$key] = $code;
		} else {
			$this->additionalCodePreForm[] = $code;
		}
	}

	public function addToAdditionalJSPreForm($key, $code) {
		if ($key != '') {
			$this->additionalJSPreForm[$key] = $code;
		} else {
			$this->additionalJSPreForm[] = $code;
		}
	}

	public function addToAdditionalJSPostForm($key, $code) {
		if ($key != '') {
			$this->additionalJSPostForm[$key] = $code;
		} else {
			$this->additionalJSPostForm[] = $code;
		}
	}

	public function registerRTEWindow($itemName) {
		//
	}

	// TODO: turn this into a proper handler for a new RTE instance
	public function registerRTEinstance() {
		++$this->RTEcounter;
	}

	public function getRTEcounter() {
		return $this->RTEcounter;
	}

	/*public function __set($key, $value) {
		throw new Exception('Write access to protected var '.$key);
	}

	public function __get($key) {
		throw new Exception('Read access to protected var '.$key);
	}*/

	public function setReadOnly($readOnly) {
		$this->readOnly = $readOnly;

		return $this;
	}

	public function isReadOnly() {
		return $this->readOnly;
	}

	public function setDoSaveFieldName($fieldname) {
		$this->doSaveFieldName = $fieldname;

		return $this;
	}

	public function getDoSaveFieldName() {
		return $this->doSaveFieldName;
	}

	/**
	 * @param boolean $clickmenuEnabled
	 *
	 * @TODO add to Context interface
	 */
	public function setClickmenuEnabled($clickmenuEnabled) {
		$this->clickmenuEnabled = $clickmenuEnabled;

		return $this;
	}

	/**
	 * @return boolean
	 *
	 * @TODO add to Context interface
	 */
	public function isClickmenuEnabled() {
		return $this->clickmenuEnabled;
	}

	/**
	 * Returns an array of available languages (to use for FlexForms)
	 *
	 * @param	boolean		If set, only languages which are paired with a static_info_table / static_language record will be returned.
	 * @param	boolean		If set, an array entry for a default language is set.
	 * @return	array
	 */
	public function getAvailableLanguages($onlyIsoCoded = TRUE, $setDefault = TRUE) {
		$isL = t3lib_extMgm::isLoaded('static_info_tables');

			// Find all language records in the system:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('static_lang_isocode, title, uid', 'sys_language', 'pid=0 AND hidden=0'.t3lib_BEfunc::deleteClause('sys_language'), '', 'title');

			// Traverse them:
		$output = array();
		if ($setDefault) {
			$output[0] = array(
				'uid' => 0,
				'title' => 'Default language',
				'ISOcode' => 'DEF'
			);
		}
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$output[$row['uid']] = $row;

			if ($isL && $row['static_lang_isocode']) {
				$rr = t3lib_BEfunc::getRecord('static_languages',$row['static_lang_isocode'],'lg_iso_2');
				if ($rr['lg_iso_2']) {
					$output[$row['uid']]['ISOcode']=$rr['lg_iso_2'];
				}
			}

			if ($onlyIsoCoded && !$output[$row['uid']]['ISOcode']) {
				unset($output[$row['uid']]);
			}
		}
		return $output;
	}

	/**
	 * Returns TSconfig for table/row
	 * Multiple requests to this function will return cached content so there is no performance loss in calling this many times since the information is looked up only once.
	 *
	 * @param   string  The table name
	 * @param   array   The table row (Should at least contain the "uid" value, even if "NEW..." string. The "pid" field is important as well, and negative values will be intepreted as pointing to a record from the same table.)
	 * @param   string  Optionally you can specify the field name as well. In that case the TSconfig for the field is returned.
	 * @return  mixed   The TSconfig values (probably in an array)
	 * @see t3lib_BEfunc::getTCEFORM_TSconfig()
	 */
	public static function getTSconfig($table, $row, $field='') {
		$mainKey = $table.':'.$row['uid'];
		if (!isset(self::$cachedTSconfig[$mainKey])) {
			self::$cachedTSconfig[$mainKey] = t3lib_BEfunc::getTCEFORM_TSconfig($table, $row);
		}
		if ($field) {
			return self::$cachedTSconfig[$mainKey][$field];
		} else {
			return self::$cachedTSconfig[$mainKey];
		}
	}

	public function getNestedStackEntry() {
		return false;
	}
}

?>