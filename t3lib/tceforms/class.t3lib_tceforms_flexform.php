<?php


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

	/**
	 * If true, localization of the records on this form is enabled.
	 *
	 * @var boolean
	 */
	protected $localizationEnabled = FALSE;

	/**
	 * The localization method for this record. This is determined by the value meta->langChildren
	 * of the FlexForm data structure.
	 *
	 * value 0 means one record for each language (language codes on language part)
	 * value 1 means all languages in one record  (language codes on value part)
	 *
	 * @var integer
	 */
	protected $localizationMethod = 0;

	/**
	 *
	 *
	 * @var array
	 */
	protected $languages = array();

	public function init() {
		parent::init();

		$this->setFormFieldNamePrefix($this->containingElement->getFormFieldName() . '[data]');

		if ($this->localizationEnabled) {
			$languages = $this->getAvailableLanguages();
			foreach ($languages as $language) {
				$this->languages[] = $language['ISOcode'];
			}
		}
	}

	/**
	 * Creates an identifier for an element from a given element identifier stack.
	 *
	 * @param  object $object  The element the identifier should be generated for
	 * @param  string $type  'name': all parts wrapped in []; 'id': elements separated by '-'
	 * @return string
	 */
	public function createElementIdentifier($object, $type) {
		$elementIdentifier = $this->elementIdentifierPrefix;

		$elementIdentifierStack = $this->elementIdentifierStack;
		if (is_a($object, 't3lib_TCEforms_Element_Abstract')) {
			$sheetName = $object->getContainer()->getName();
			if ($this->localizationMethod == 0) {
				$languagePart = 'l' . $object->getRecordObject()->getLanguage();
				$valuePart = 'vDEF';
			} else {
				$languagePart = 'lDEF';
				$valuePart = 'v' . $object->getRecordObject()->getLanguage();
			}
			$elementIdentifierStack[] = $sheetName;
			$elementIdentifierStack[] = $languagePart;
			$elementIdentifierStack[] = $object->getFieldName();
			$elementIdentifierStack[] = $valuePart;
		}

		switch($type) {
			case 'name':
				if (count($elementIdentifierStack) > 0) {
					$elementIdentifier .= '[' . implode('][', $elementIdentifierStack) . ']';
				}

				break;
			case 'id':
				$elementIdentifier .= '-' . implode('-', $elementIdentifierStack);

				break;
		}

		return $elementIdentifier;
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

		if ($this->localizationEnabled) {
			if ($this->localizationMethod == 0) {
				foreach ($this->languages as $lang) {
					$recordObject = new t3lib_TCEforms_FlexRecord($recordData, $this->dataStructure, $lang);

					$this->insertRecordObject($recordObject);
				}
			} else {
				$recordObject = new t3lib_TCEforms_FlexRecord($recordData, $this->dataStructure, $this->languages);

				$this->insertRecordObject($recordObject);
			}
		} else {
			$recordObject = new t3lib_TCEforms_FlexRecord($recordData, $this->dataStructure);

			$this->insertRecordObject($recordObject);
		}

		return $recordObject;
	}

	protected function insertRecordObject(t3lib_TCEforms_FlexRecord $recordObject) {
		if (count($this->fieldList) > 0) {
			$recordObject->setFieldList($this->fieldList);
		}

		$recordObject->setParentFormObject($this)
		             ->setContextObject($this->contextObject)
		             ->init();

		$this->recordObjects[] = $recordObject;
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

	public function setLocalizationEnabled($localization) {
		$this->localizationEnabled = $localization;
		return $this;
	}

	public function isLocalizationEnabled() {
		return $this->localizationEnabled;
	}

	public function setLocalizationMethod($localizationMethod) {
		$this->localizationMethod = $localizationMethod;
		return $this;
	}
}

?>