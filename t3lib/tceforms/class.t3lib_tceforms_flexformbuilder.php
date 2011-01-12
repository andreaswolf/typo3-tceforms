<?php

class t3lib_TCEforms_FlexFormBuilder extends t3lib_TCEforms_FormBuilder {

	/**
	 * @var t3lib_TCA_FlexFormDataStructure
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
		$stack[] = $this->currentSheetObject->getName();

		if ($this->dataStructure->isLocalizationEnabled()) {
			if ($this->dataStructure->getLocalizationMethod() == 0) {
				$stack[] = 'l' . $this->recordObject->getLanguage();
				$stack[] = $field->getFieldname();
				$stack[] = 'vDEF';
			} else {
				$stack[] = 'lDEF';
				$stack[] = $field->getFieldname();
				$stack[] = 'v' . $this->recordObject->getLanguage();
			}
		} else {
			$stack[] = 'lDEF';
			$stack[] = $field->getFieldname();
			$stack[] = 'vDEF';
		}
		return $stack;
	}

	protected function setFieldValue(t3lib_TCEforms_Element_Abstract $elementObject) {
		$elementObject->setValue($this->recordObject->getValue($this->currentSheetObject->getName() . '.' . $elementObject->getFieldname()));
	}
}
