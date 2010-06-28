<?php

class t3lib_TCEforms_FlexRecord extends t3lib_TCEforms_Record {

	/**
	 * The language of this record as used in lABC keys in FlexForm field names
	 *
	 * @var string
	 */
	protected $language;

	public function __construct(array $recordData, t3lib_TCA_DataStructure $dataStructure, $language = 'DEF') {
		parent::__construct('', $recordData, array(), $dataStructure);

		$this->language = $language;
	}

	public function hasLanguage() {
		return TRUE;
	}

	public function getLanguage() {
		return $this->language;
	}

	/**
	 * Build the
	 *
	 * @see t3lib/tceforms/t3lib_TCEforms_Record#buildFormFieldPrefixes()
	 */
	protected function buildFormFieldPrefixes() {
		$this->formFieldNamePrefix = $this->parentFormObject->getFormFieldNamePrefix();
		$this->formFieldIdPrefix = $this->parentFormObject->getFormFieldIdPrefix();
	}

	protected function buildSheetIdentifiers() {
		$this->sheetIdentifier = 'TCEforms:' . $this->getIdentifier() . $this->parentFormObject->getContainingElement()->getFieldname() . $this->language;
		$this->shortSheetIdentifier = $GLOBALS['TBE_TEMPLATE']->getDynTabMenuId($this->sheetIdentifier);
	}
}

?>