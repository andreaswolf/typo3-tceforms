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

	/**
	 * @var t3lib_DataStructure_FlexForm
	 */
	protected $dataStructure;

	protected $localizationMethod;

	public function __construct(array $recordData, t3lib_DataStructure_FlexForm $dataStructure, $language = 'DEF') {
		// TODO check for localization type
		// TODO create some kind of identifier for using instead of a table name

		$this->dataStructure = $dataStructure;
		$this->language = $language;

		parent::__construct('', $recordData, $dataStructure);

	}

	public function setElementIdentifierStack(array $elementIdentifierStack) {
		$this->elementIdentifierStack = $elementIdentifierStack;

		return $this;
	}

	public function init() {
		parent::init();

		$this->localizationMethod = $this->parentFormObject->getLocalizationMethod();
	}

	/**
	 * Returns the value of a field from this record.
	 *
	 * @param  mixed $field May be a string (sheetname.fieldname) or an element object. In the second case, the object is examined to determine the field value
	 * @return array
	 */
	public function getValue($field) {
		if (is_string($field)) {
			list($sheetIdentifier, $fieldName) = explode('.', $field);
			return $this->recordData[$sheetIdentifier][$fieldName];
		} elseif (is_a($field, 't3lib_TCEforms_Element_Abstract')) {
			/** @var $field t3lib_TCEforms_Element_Abstract */
			return $this->recordData[$field->getContainer()->getName()][$field->getFieldname()];
		}
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
