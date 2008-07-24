<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractform.php');


class t3lib_TCEforms_MultiFieldForm extends t3lib_TCEforms_AbstractForm {
	/**
	 * @var string  The list of fields to display
	 */
	protected $fieldList;

	public function __construct($table, $row) {
		parent::__construct($table, $row);

		$this->createFieldsList();
	}

	public function render() {

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

		$fields = $this->mergeFieldsWithAddedFields($fields, $this->getFieldsToAdd());

		foreach ($fields as $fieldInfo) {
			// Exploding subparts of the field configuration:
			$parts = explode(';', $fieldInfo);

				// Getting the style information out:
			/* TODO: Make this work again.
			$color_style_parts = t3lib_div::trimExplode('-',$parts[4]);
			if (strcmp($color_style_parts[0], ''))	{
				$this->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])]);
			}
			if (strcmp($color_style_parts[1], ''))	{
				$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])];
				if (!isset($this->fieldStyle)) {
					$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
				}
			}
			if (strcmp($color_style_parts[2], ''))	{
				$this->wrapBorder($out_array[$out_sheet],$out_pointer);
				$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])];
				if (!isset($this->borderStyle)) {
					$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
				}
			}*/

			$theField = $parts[0];
			if ($this->isExcludeElement($theField))	{
				continue;
			}

			if ($this->tableTCAconfig['columns'][$theField]) {
				// ToDo: Handle field configuration here.
			}
		}
	}
}

?>