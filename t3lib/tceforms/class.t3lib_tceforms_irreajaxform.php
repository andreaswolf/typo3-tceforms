<?php

class t3lib_TCEforms_IrreAjaxForm extends t3lib_TCEforms_IrreForm {

	/**
	 * The table of the record containing this form.
	 *
	 * @var string
	 * @see $containerRecord
	 * TODO check if naming should be container* or containing*
	 */
	protected $containerTable;

	/**
	 * The record containing this form
	 *
	 * @var t3lib_TCEforms_Record
	 */
	protected $containerRecord;

	protected $isAjaxCall = TRUE;

	/**
	 * Overrides the TCA field configuration by TSconfig settings.
	 *
	 * Example TSconfig: TCEform.<table>.<field>.config.appearance.useSortable = 1
	 * This overrides the setting in $TCA[<table>]['columns'][<field>]['config']['appearance']['useSortable'].
	 *
	 * @param	array		$TSconfig: TSconfig
	 * @return	array		Changed TCA field configuration
	 */
	public function overrideFieldConf($TSconfig) {
		$fieldConfig = $this->fieldConfig['config'];

		if (is_array($TSconfig)) {
			$TSconfig = t3lib_div::removeDotsFromTS($TSconfig);
			$type = $fieldConfig['type'];
			if (is_array($TSconfig['config']) && is_array($this->allowOverrideMatrix[$type])) {
					// Check if the keys in TSconfig['config'] are allowed to override TCA field config:
				foreach (array_keys($TSconfig['config']) as $key) {
					if (!in_array($key, $this->allowOverrideMatrix[$type], true)) {
						unset($TSconfig['config'][$key]);
					}
				}
					// Override TCA field config by remaining TSconfig['config']:
				if (count($TSconfig['config'])) {
					$fieldConfig = t3lib_div::array_merge_recursive_overrule($fieldConfig, $TSconfig['config']);
				}
			}
		}

		$this->fieldConfig['config'] = $fieldConfig;
	}

	/**
	 *
	 *
	 * @return
	 */
	public function getContainer() {
		//return $this;
		return null;
	}

	public function setContainerRecord(array $record) {
		$this->containerRecord = $record;
		return $this;
	}

	/**
	 * Proxy function for accessing the table of the record this form is placed on.
	 *
	 * @return string
	 */
	protected function getContainerTable() {
		return $this->containerTable;
	}

	/**
	 * Proxy function for accessing data from the record this form is placed on.
	 *
	 * @param string $key The key of the value to get
	 * @return mixed
	 */
	protected function getContainingRecordValue($key) {
		return $this->record[$key];
	}

	public function setContainingRecord(array $recordObject) {
		$this->record = $recordObject;
	}

	public function setContainerTable($table) {
		$this->containerTable = $table;
		return $this;
	}

	public function getContainingTable() {
		return $this->table;
	}

	protected function getIrreIdentifierForRecord(t3lib_TCEforms_Record $recordObject) {
		$identifierParts[] = $recordObject->getValue('uid');
		$identifierParts[] = $recordObject->getTable();
		return $this->containingElement->getStructurePath() . '[' . implode('][', array_reverse($identifierParts)) . ']';
	}
}

?>