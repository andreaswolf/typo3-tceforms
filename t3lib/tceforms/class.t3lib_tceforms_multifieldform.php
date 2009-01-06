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
			// TODO: check typical uses of this, and re-implement the initialization of the hook objects
			// Hook: getMainFields_preProcess (requested by Thomas Hempel for use with the "dynaflex" extension)
		/*foreach ($this->hookObjectsMainFields as $hookObj)	{
			if (method_exists($hookObj,'getMainFields_preProcess'))	{
				$hookObj->getMainFields_preProcess($table,$row,$this);
			}
		}*/

		// TODO: move this to a new initialize method (abstract in AbstractForm)
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

			if ($theField == '--div--') {
				++$sheetCounter;

				$sheetObject = $this->createSheetObject($tabIdentStringMD5.'-'.$sheetCounter, $this->sL($parts[1]));
				$sheetObject->setStartingNewRowInTabmenu(($parts[2] == "newline"));
				$this->currentSheet = $sheetObject;
			} else {
				if ($this->tableTCAconfig['columns'][$theField]) {
					// TODO: Handle field configuration here.
					$formFieldObject = $this->getSingleField($theField, $parts[1], 0, $parts[3], $parts[2]);
					$this->currentSheet->addChildObject($formFieldObject);
				} elseif ($theField == '--palette--') {
					// TODO: add top-level palette handling! (--palette--, see TYPO3 Core API, section 4.2)
					//       steps: create a new element type "palette" as a dumb wrapper for a palette
					//       for testing see tt_content, type text w/image, image dimensions and links
				}

					// Getting the style information out:
				// TODO: Make this really object oriented
				$color_style_parts = t3lib_div::trimExplode('-',$parts[4]);
				if (strcmp($color_style_parts[0], ''))	{
					$formFieldObject->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])]);
				}
				// TODO: add getters and setters for fieldStyle, _wrapBorder and borderStyle
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
			}
		}

		$tabContents = array();

		$c = 0;
		foreach ($this->sheets as $sheetObject) {
			++$c;
			$tabContents[$c] = array(
				'newline' => $sheetObject->isStartingNewRowInTabmenu(),
				'label' => $sheetObject->getHeader(),
				'content' => $sheetObject->render()
			);
		}
		if (count($tabContents) > 1) {
			$content = $this->getDynTabMenu($tabContents, $sheetIdentString);
		} else {
			$content = $tabContents[1]['content'];
		}

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