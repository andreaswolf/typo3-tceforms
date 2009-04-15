<?php

require_once(PATH_t3lib.'tceforms/container/class.t3lib_tceforms_container_sheet.php');

class t3lib_TCEforms_FormBuilder {

	/**
	 * The context object this builder builds elements for.
	 *
	 * @var t3lib_TCEforms_Context
	 */
	protected $contextObject;

	protected $formFieldNamePrefix;

	protected function __construct(t3lib_TCEforms_Record $recordObject) {
		t3lib_div::devLog('Created new formbuilder object for record ' . $recordObject->getIdentifier() . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

		$this->recordObject = $recordObject;
		$this->contextObject = $recordObject->getContextObject();
		$this->TCAdefinition = $recordObject->getTCAdefinitionForTable();
	}

	public static function createInstanceForRecordObject(t3lib_TCEforms_Record $recordObject) {
		return new t3lib_TCEforms_Formbuilder($recordObject);
	}

	/**
	 * Takes a record object and builds the TCEforms object structure for it.
	 *
	 * @return void
	 */
	public function buildObjectStructure(t3lib_TCEforms_Record $recordObject) {
		t3lib_div::devLog('Started building object tree for record ' . $this->recordObject->getIdentifier() . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

		$fieldList = $recordObject->getFieldList();

		if (isset($fieldList[0]) && strpos($fieldList[0], '--div--') !== 0) {
			$this->currentSheet = $this->createSheetObject($sheetIdentString, $this->getLL('l_generalTab'));
			$recordObject->addSheetObject($this->currentSheet);
		}

		foreach ($fieldList as $fieldInfo) {
			// Exploding subparts of the field configuration:
			$parts = explode(';', $fieldInfo);

			$theField = $parts[0];
			if ($recordObject->isExcludeElement($theField)) {
				continue;
			}

			if ($theField == '--div--') {
				++$sheetCounter;

				$this->currentSheet = $this->createSheetObject($sheetIdentString, $this->sL($parts[1]));
				$recordObject->addSheetObject($this->currentSheet);
			} else {
				if ($theField !== '') {
					if ($this->TCAdefinition['columns'][$theField]) {
						t3lib_div::devLog('Adding standard element for field "' . $theField . '" in record ' . $this->recordObject->getIdentifier() . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

						// TODO: Handle field configuration here.
						$formFieldObject = $this->getSingleField($theField, $this->TCAdefinition['columns'][$theField], $parts[1], $parts[3]);

					} elseif ($theField == '--palette--') {
						t3lib_div::devLog('Adding palette element for record ' . $this->recordObject->getIdentifier() . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

						$formFieldObject = $this->createPaletteElement($parts[2], $this->sL($parts[1]));
					} else {
						// if this is no field, just continue with the next entry in the field list.
						continue;
					}

					$this->currentSheet->addChildObject($formFieldObject);

					$formFieldObject->setContextObject($this->contextObject)
					                ->setParentRecordObject($this->recordObject)
					                ->setTable($this->recordObject->getTable())
					                ->setRecord($this->recordObject->getRecordData())
					                ->injectFormBuilder($this)
					                ->init();

					if (isset($parts[2]) && t3lib_div::testInt($parts[2])) {
						$formFieldObject->initializePalette($parts[2]);
					}
				}

				// Getting the style information out:
				// TODO: Make this really object oriented
				if (isset($parts[4])) {
					$color_style_parts = t3lib_div::trimExplode('-',$parts[4]);
				} else {
					$color_style_parts = array();
				}
				if (strcmp($color_style_parts[0], '')) {
					$formFieldObject->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])]);
					if (!isset($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])])) {
						$formFieldObject->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][0]);
					}
				}
				// TODO: add getter and setter for _wrapBorder
				if (strcmp($color_style_parts[1], '')) {
					$formFieldObject->setFieldStyle($GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])]);
					// TODO check if this check is still neccessary
					if (!isset($GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])])) {
						$formFieldObject->setFieldStyle($GLOBALS['TBE_STYLES']['styleschemes'][0]);
					}
				}
				if (strcmp($color_style_parts[2], '')) {
					if (isset($parts[4])) $formFieldObject->_wrapBorder = true;
					$formFieldObject->setBorderStyle($GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])]);
					// TODO check if this check is still neccessary
					if (!isset($GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])])) {
						$formFieldObject->setBorderStyle($GLOBALS['TBE_STYLES']['borderschemes'][0]);
					}
				}
			}
		}

		$this->resolveMainPalettes($recordObject);
	}

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
		// Using "form_type" locally in this script
		$fieldConf['config']['form_type'] = $fieldConf['config']['form_type'] ? $fieldConf['config']['form_type'] : $fieldConf['config']['type'];

		$elementClassname = $this->createElementObject($fieldConf['config']['form_type']);
		$elementObject = new $elementClassname($theField, $fieldConf, $altName, $extra);

		return $elementObject;
	}

	public function createPaletteElement($paletteNumber, $label) {
		$classname = $this->createElementObject('palette');
		return new $classname($paletteNumber, $label);
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

	/**
	 * Creates objects for all main palettes (palettes existing side-by-side with normal elements,
	 * in contrast to palettes that are tied to an element). These palettes are defined in the control
	 * section of TCA, key "mainpalette". Multiple palettes are separated by commas.
	 *
	 * @return void
	 */
	protected function resolveMainPalettes() {
		t3lib_div::devLog('Building top-level palette elements for ' . $this->recordObject->getIdentifier() . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

		$mainPalettesArray = t3lib_div::trimExplode(',', $this->TCAdefinition['ctrl']['mainpalette']);

		$i = 0;
		foreach ($mainPalettesArray as $paletteNumber) {
			++$i;

			if ($this->recordObject->isPaletteCreated($paletteNumber)) {
				t3lib_div::devLog("Palette no $paletteNumber in record from table " . $this->recordObject->getTable() . ' has already been created, so it won\'t be created a second time. Please check the TCA definition for any wrong/double palette assignments.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_WARNING);

				continue;
			}
			$label = $i==1 ? $this->getLL('l_generalOptions') : $this->getLL('l_generalOptions_more');

			$paletteFieldObject = $this->createPaletteElement($paletteNumber, $label);

			$paletteFieldObject->setContextObject($this->contextObject)
			                   ->setParentRecordObject($this->recordObject)
			                   ->setTable($this->recordObject->getTable())
			                   ->setRecord($this->recordObject->getRecordData())
			                   ->injectFormBuilder($this)
			                   ->init();

			$this->currentSheet->addChildObject($paletteFieldObject);
			/*$this->wrapBorder($out_array[$out_sheet],$out_pointer);
			if ($this->renderDepth)	{
				$this->renderDepth--;
			}*/
		}
	}

	/**
	 * Fetches language label for key
	 *
	 * @param   string  Language label reference, eg. 'LLL:EXT:lang/locallang_core.php:labels.blablabla'
	 * @return  string  The value of the label, fetched for the current backend language.
	 */
	protected function sL($str) {
		return $GLOBALS['LANG']->sL($str);
	}

	/**
	 * Returns language label from locallang_core.php
	 * Labels must be prefixed with either "l_" or "m_".
	 * The prefix "l_" maps to the prefix "labels." inside locallang_core.php
	 * The prefix "m_" maps to the prefix "mess." inside locallang_core.php
	 *
	 * @param   string  The label key
	 * @return  string  The value of the label, fetched for the current backend language.
	 */
	protected function getLL($str) {
		$content = '';

		switch(substr($str, 0, 2)) {
			case 'l_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.' . substr($str,2));
			break;
			case 'm_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.' . substr($str,2));
			break;
		}
		return $content;
	}
}

?>