<?php

require_once(PATH_t3lib.'tceforms/container/class.t3lib_tceforms_container_sheet.php');

class t3lib_TCEforms_Formbuilder {

	/**
	 * The context object this builder builds elements for.
	 *
	 * @var t3lib_TCEforms_Context
	 */
	protected $contextObject;

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

	public function setContextObject(t3lib_TCEforms_Form $contextObject) {
		$this->contextObject = $contextObject;

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
		if (!$this->contextObject) {
			throw new RuntimeException('No context object defined. Can\'t build new form element objects.', 1234711129);
		}

		// Using "form_type" locally in this script
		$fieldConf['config']['form_type'] = $fieldConf['config']['form_type'] ? $fieldConf['config']['form_type'] : $fieldConf['config']['type'];

		$elementClassname = $this->createElementObject($fieldConf['config']['form_type']);
		$elementObject = new $elementClassname($theField, $fieldConf, $altName, $extra);

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