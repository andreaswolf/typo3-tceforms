<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractform.php');


class t3lib_TCEforms_MultiFieldForm extends t3lib_TCEforms_AbstractForm {
	/**
	 * @var array  The list of fields to display, with their configuration
	 */
	protected $fieldList;

	/**
	 * @var array  The palettes for this form
	 */
	protected $palettes;

	public function __construct($table, $row) {
		parent::__construct($table, $row);

		$this->createFieldsList();
	}


	/**
	 * Creates the list of fields to display
	 *
	 * This function is mainly copied from t3lib_TCEforms::getMainFields()
	 */
	protected function createFieldsList() {
		$itemList = $this->tableTCAconfig['types'][$this->typeNumber]['showitem'];

		$fields = t3lib_div::trimExplode(',', $itemList, 1);
		/* TODO: reenable this
		if ($this->fieldOrder)	{
			$fields = $this->rearrange($fields);
		}*/

		$this->fieldList = $this->mergeFieldsWithAddedFields($fields, $this->getFieldsToAdd());
	}
}

?>