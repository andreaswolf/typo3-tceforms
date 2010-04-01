<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractform.php');


class t3lib_TCEforms_Flexform extends t3lib_TCEforms_Form implements t3lib_TCEforms_NestableForm {
	/**
	 * @var array  The list of fields to display, with their configuration
	 */
	protected $fieldList;

	/**
	 * @var array  The palettes for this form
	 */
	protected $palettes;

	public $PA;

	protected $dataStructureArray;

	public function init() {
		parent::init();

		$this->setFormFieldNamePrefix($this->containingElement->getFormFieldName() . '[data]');
	}

	/**
	 * Creates the language menu for FlexForms:
	 *
	 * @param	[type]		$languages: ...
	 * @param	[type]		$elName: ...
	 * @param	[type]		$selectedLanguage: ...
	 * @param	[type]		$multi: ...
	 * @return	string		HTML for menu
	 */
	protected function getLanguageMenu($languages,$elName,$selectedLanguage,$multi=1) {
		$opt=array();
		foreach($languages as $lArr) {
			$opt[]='<option value="'.htmlspecialchars($lArr['ISOcode']).'"'.(in_array($lArr['ISOcode'],$selectedLanguage)?' selected="selected"':'').'>'.htmlspecialchars($lArr['title']).'</option>';
		}

		$output = '<select name="'.$elName.'[]"'.($multi ? ' multiple="multiple" size="'.count($languages).'"' : '').'>'.implode('',$opt).'</select>';

		return $output;
	}

	/**
	 * Creates the menu for selection of the sheets:
	 *
	 * @param	array		Sheet array for which to render the menu
	 * @param	string		Form element name of the field containing the sheet pointer
	 * @param	string		Current sheet key
	 * @return	string		HTML for menu
	 */
	function createSheetMenu($sArr,$elName,$sheetKey) {

		$tCells =array();
		$pct = round(100/count($sArr));
		foreach($sArr as $sKey => $sheetCfg) {
			if ($GLOBALS['BE_USER']->jsConfirmation(1)) {
				$onClick = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){'.$this->TCEformsObject->elName($elName).".value='".$sKey."'; TBE_EDITOR.submitForm()};";
			} else {
				$onClick = 'if(TBE_EDITOR.checkSubmit(-1)){ '.$this->TCEformsObject->elName($elName).".value='".$sKey."'; TBE_EDITOR.submitForm();}";
			}


			$tCells[]='<td width="'.$pct.'%" style="'.($sKey==$sheetKey ? 'background-color: #9999cc; font-weight: bold;' : 'background-color: #aaaaaa;').' cursor: hand;" onclick="'.htmlspecialchars($onClick).'" align="center">'.
					($sheetCfg['ROOT']['TCEforms']['sheetTitle'] ? $this->TCEformsObject->sL($sheetCfg['ROOT']['TCEforms']['sheetTitle']) : $sKey).
					'</td>';
		}

		return '<table border="0" cellpadding="0" cellspacing="2" class="typo3-TCEforms-flexForm-sheetMenu"><tr>'.implode('',$tCells).'</tr></table>';
	}

	public function addRecord($recordData) {
		t3lib_div::devLog('Added flex record to form in element ' . $this->containingElement->getIdentifier(), 't3lib_TCEforms', t3lib_div::SYSLOG_SEVERITY_INFO);

		// TODO: remove the third parameter from this call once we have moved to using the data structure objects
		//       instead of the weird arrays
		$recordObject = new t3lib_TCEforms_FlexRecord($this->containingElement->getIdentifier(), $recordData, array(), $this->dataStructure);

		if (count($this->fieldList) > 0) {
			$recordObject->setFieldList($this->fieldList);
		}

		$recordObject->setParentFormObject($this)
		             ->setContextObject($this->contextObject)
		             ->init();

		$this->recordObjects[] = $recordObject;

		return $recordObject;
	}

	/**
	 * FIXME This function has been copied 1:1 from IrreForm. We should introduce a common class between
	 *       Form and IrreForm/FlexForm where we store these methods (see also get/setContainingElement())
	 */
	public function getTemplateContent() {
		if ($this->templateContent != '') {
			return $this->templateContent;
		} else {
			return $this->contextObject->getTemplateContent();
		}
	}

	public function setDataStructure(t3lib_TCA_DataStructure $dataStructure) {
		$this->dataStructure = $dataStructure;

		return $this;
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

	/**
	 * Returns the element containing the nestable form.
	 * @return t3lib_TCEforms_Element
	 */
	public function getContainingElement() {
		return $this->containingElement;
	}
}

?>