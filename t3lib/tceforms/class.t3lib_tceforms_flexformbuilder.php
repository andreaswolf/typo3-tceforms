<?php

class t3lib_TCEforms_FlexFormBuilder extends t3lib_TCEforms_FormBuilder {

	/**
	 * @var t3lib_DataStructure_FlexForm
	 */
	protected $dataStructure;

	/**
	 * @var t3lib_TCEforms_FlexRecord
	 */
	protected $recordObject;

	public static function createInstanceForRecordObject(t3lib_TCEforms_FlexRecord $recordObject) {
		return new t3lib_TCEforms_FlexFormBuilder($recordObject);
	}

	public function extendIdentifierStackForField(t3lib_TCEforms_Element $field) {
		$stack = $this->elementIdentifierStack;
		$stackEntrySheet = array(
			'data',
			$this->currentSheetObject->getName()
		);

		if ($this->dataStructure->isLocalizationEnabled()) {
			if ($this->dataStructure->getLocalizationMethod() == 0) {
				$stackEntrySheet[] = 'l' . $this->recordObject->getLanguage();
				$stackEntryField = array(
					$field->getFieldname(),
					'vDEF'
				);
			} else {
				$stackEntrySheet[] = 'lDEF';
				$stackEntryField = array(
					$field->getFieldname(),
					'v' . $this->recordObject->getLanguage()
				);
			}
		} else {
			$stackEntrySheet[] = 'lDEF';
			$stackEntryField = array(
				$field->getFieldname(),
				'vDEF'
			);
		}
		$stack[] = $stackEntrySheet;
		$stack[] = $stackEntryField;

		return $stack;
	}

	protected function setFieldValue(t3lib_TCEforms_Element_Abstract $elementObject) {
		$elementObject->setValue($this->recordObject->getValue($this->currentSheetObject->getName() . '.' . $elementObject->getFieldname()));
	}
}
