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
	 * Renders the form; this function is the successor of the old
	 * t3lib_tceforms::getSoloField()/getMainFields()/...
	 *
	 *
	 */
	public function render() {
			// Hook: getMainFields_preProcess (requested by Thomas Hempel for use with the "dynaflex" extension)
		/*foreach ($this->hookObjectsMainFields as $hookObj)	{
			if (method_exists($hookObj,'getMainFields_preProcess'))	{
				$hookObj->getMainFields_preProcess($table,$row,$this);
			}
		}*/

			// always create at least one default tab
		if (isset($this->fieldList[0]) && strpos($this->fieldList[0], '--div--') !== 0) {
			$sheetIdentString = 'TCEforms:'.$table.':'.$row['uid'];
			$sheetIdentStringMD5 = $GLOBALS['TBE_TEMPLATE']->getDynTabMenuId($sheetIdentString);

			$this->currentSheet = $this->createSheetObject($sheetIdentStringMD5.'-1', $this->getLL('l_generalTab'));
		}

		$sheetCounter = 1;
		foreach ($this->fieldList as $fieldInfo) {
			// Exploding subparts of the field configuration:
			$parts = explode(';', $fieldInfo);


			$theField = $parts[0];
			if ($this->isExcludeElement($theField))	{
				continue;
			}

			if ($this->tableTCAconfig['columns'][$theField]) {
				// TODO: Handle field configuration here.
				$formFieldObject = $this->getSingleField($theField, $parts[1], 0, $parts[3], $parts[2]);
				$this->currentSheet->addChildObject($formFieldObject);


					// Getting the style information out:
				// TODO: Make this really object oriented
				$color_style_parts = t3lib_div::trimExplode('-',$parts[4]);
				if (strcmp($color_style_parts[0], ''))	{
					$formFieldObject->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])]);
				}
				if (strcmp($color_style_parts[1], ''))	{
					$formFieldObject->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])];
					if (!isset($this->fieldStyle)) {
						$formFieldObject->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
					}
				}
				if (strcmp($color_style_parts[2], ''))	{
					$formFieldObject->_wrapBorder = true;
					$formFieldObject->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])];
					if (!isset($this->borderStyle)) {
						$formFieldObject->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
					}
				}
			} elseif ($theField == '--div--') {
				++$sheetCounter;

				$sheetObject = $this->createSheetObject($tabIdentStringMD5.'-'.$sheetCounter, $this->sL($parts[1]));
				$this->currentSheet = $sheetObject;
			} // TODO: add top-level palette handling!
		}

		$tabContents = array();

		$c = 0;
		foreach ($this->sheets as $sheetObject) {
			++$c;
			$tabContents[$c] = array(
				'newline' => false, // TODO: make this configurable again
				'label' => $sheetObject->getHeader(),
				'content' => $sheetObject->render()
			);
		}
		if (count($tabContents) > 1) {
			$content = $this->getDynTabMenu($tabContents, $sheetIdentString);
		} else {
			$content = $tabContents[1]['content'];
		}
			// TODO: move the wrap to alt_doc.php
		return $this->wrapTotal($content);
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