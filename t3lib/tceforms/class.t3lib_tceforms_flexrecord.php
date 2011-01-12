<?php

class t3lib_TCEforms_FlexRecord extends t3lib_TCEforms_Record {

	/**
	 * The language of this record as used in lABC keys in FlexForm field names
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * @var t3lib_TCEforms_Flexform
	 */
	protected $parentFormObject;

	protected $localizationMethod;

	public function __construct(array $recordData, t3lib_TCA_FlexFormDataStructure $dataStructure, $language = 'DEF') {
		// TODO check for localization type
		// TODO create some kind of identifier for using instead of a table name

		$this->dataStructure = $dataStructure;

		parent::__construct('', $recordData, $dataStructure);

		$this->language = $language;
	}

	public function setElementIdentifierStack(array $elementIdentifierStack) {
		$this->elementIdentifierStack = $elementIdentifierStack;

		return $this;
	}

	public function init() {
		parent::init();

		$this->localizationMethod = $this->parentFormObject->getLocalizationMethod();
	}

	protected function createFormBuilderInstance() {
		$this->formBuilder = t3lib_TCEforms_FlexFormBuilder::createInstanceForRecordObject($this);
	}

	public function hasLanguage() {
		return TRUE;
	}

	public function getLanguage() {
		return $this->language;
	}

	protected function buildSheetIdentifiers() {
		$this->sheetIdentifier = 'TCEforms:' . $this->getIdentifier() . $this->parentFormObject->getContainingElement()->getFieldname() . $this->language;
		$this->shortSheetIdentifier = $GLOBALS['TBE_TEMPLATE']->getDynTabMenuId($this->sheetIdentifier);
	}
}

?>
