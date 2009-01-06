<?php

require_once(PATH_t3lib.'tceforms/container/class.t3lib_tceforms_container_sheet.php');

class t3lib_TCEforms_Formbuilder {

	protected $formFieldNamePrefix;

	public function getFormFieldNamePrefix() {
		return $this->formFieldNamePrefix;
	}

	public function setFormFieldNamePrefix($prefix) {
		$this->formFieldNamePrefix = $prefix;

		if (is_object($this->formBuilder)) {
			$this->formBuilder->setFormFieldNamePrefix($prefix);
		}

		return $this;
	}

	/**
	 * Returns the object representation for a database table field.
	 *
	 * @param   string   $field    The field name
	 * @param   string   $altName  Alternative field name label to show.
	 * @param   boolean  $palette  Set this if the field is on a palette (in top frame), otherwise not. (if set, field will render as a hidden field).
	 * @param   string   $extra    The "extra" options from "Part 4" of the field configurations found in the "types" "showitem" list. Typically parsed by $this->getSpecConfFromString() in order to get the options as an associative array.
	 * @param   integer  $pal      The palette pointer.
	 * @param   string   $formFieldName  The name of the field on the form
	 * @return  t3lib_TCEforms_AbstractElement
	 */
	public function getSingleField($theField, $fieldConf, $altName='', $extra='', $formFieldName = '') {
		// Using "form_type" locally in this script
		$fieldConf['config']['form_type'] = $fieldConf['config']['form_type'] ? $fieldConf['config']['form_type'] : $fieldConf['config']['type'];

		$elementClassname = $this->createElementObject($fieldConf['config']['form_type']);
		//$this->table, $this->record
		$elementObject = new $elementClassname($theField, $fieldConf, $altName, $extra, $this);
		/*$elementObject->setTable($this->table)
		              ->setRecord($this->record);*/
			// don't set the container here because we can't be sure if this item
			// will be attached to $this->currentSheet or another sheet
		//              ->setTCEformsObject($this->TCEformsObject)
		//              ->set_TCEformsObject($this);
		if (is_array($this->defaultLanguageData)) {
			$elementObject->setDefaultLanguageValue($this->defaultLanguageData[$theField]);
		}

		// TODO: don't call init here, call it in the container after the element has been added to it
		//$elementObject->init();

		return $elementObject;
	}

	public function createPaletteElement($fieldConf, $altName = '', $extra = '') {
		$classname = $this->createElementObject('palette');
		return new $classname($fieldConf, $altName, $extra, $this);
	}

	/**
	 * Factory method for form element objects. Defaults to type "unknown" if the class(file)
	 * is not found.
	 *
	 * @param  string  $type  The type of record to create - directly taken from TCA
	 * @return t3lib_TCEforms_AbstractElement  The element object
	 */
	// TODO: refactor this as soon as the autoloader is available in core
	protected function createElementObject($type) {
		switch ($type) {
			default:
				$className = 't3lib_TCEforms_Element_'.$type;
				break;
		}

		if (!class_exists($className)) {
				// if class(file) does not exist, resolve to type "unknown"
			if (!@file_exists(PATH_t3lib.'tceforms/element/class.'.strtolower($className).'.php')) {
				return $this->createElementObject('unknown');
			}
			include_once PATH_t3lib.'tceforms/element/class.'.strtolower($className).'.php';
		}

		return t3lib_div::makeInstanceClassName($className);
	}

	/**
	 * Factory method for sheet objects on forms.
	 *
	 * @param   string  $sheetIdentString  The identifier of the sheet. Must be unique for the whole form
	 *                                     (and all sub-forms!)
	 * @param   string  $header  The name of the sheet (e.g. displayed as the title in tabs
	 * @return  t3lib_TCEforms_Sheet
	 */
	public function createSheetObject($sheetIdentString, $header) {
		$sheetObject = new t3lib_TCEforms_Container_Sheet($sheetIdentString, $header);

		return $sheetObject;
	}

	/**
	 * Create dynamic tab menu
	 *
	 * @param	array		Parts for the tab menu, fed to template::getDynTabMenu()
	 * @param	string		ID string for the tab menu
	 * @param	integer		If set to '1' empty tabs will be removed, If set to '2' empty tabs will be disabled
	 * @return	string		HTML for the menu
	 */
	public function getDynTabMenu($parts, $idString, $dividersToTabsBehaviour = 1) {
		if (is_object($GLOBALS['TBE_TEMPLATE'])) {
			return $GLOBALS['TBE_TEMPLATE']->getDynTabMenu($parts, $idString, 0, false, 50, 1, false, 1, $dividersToTabsBehaviour);
		} else {
			$output = '';
			foreach($parts as $singlePad) {
				$output .= '
				<h3>' . htmlspecialchars($singlePad['label']) . '</h3>
				' . ($singlePad['description'] ? '<p class="c-descr">' . nl2br(htmlspecialchars($singlePad['description'])) . '</p>' : '') . '
				' . $singlePad['content'];
			}

			return '<div class="typo3-dyntabmenu-divs">' . $output . '</div>';
		}
	}
}

?>