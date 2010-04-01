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

	/**
	 * The data structure the target record is based on
	 *
	 * @var t3lib_TCA_DataStructure
	 */
	protected $dataStructure;

	/**
	 * The record object this form builder belongs to
	 *
	 * @var t3lib_TCEforms_Record
	 */
	protected $recordObject;


	protected function __construct(t3lib_TCEforms_Record $recordObject) {
		t3lib_div::devLog('Created new formbuilder object for record ' . $recordObject->getIdentifier() . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

		$this->recordObject = $recordObject;
		$this->contextObject = $recordObject->getContextObject();
		$this->dataStructure = $recordObject->getDataStructure();
	}

	public static function createInstanceForRecordObject(t3lib_TCEforms_Record $recordObject) {
		return new t3lib_TCEforms_Formbuilder($recordObject);
	}

	/**
	 * Takes a record object and builds the TCEforms object structure for it.
	 *
	 * @return void
	 */
	public function buildObjectStructure() {
		t3lib_div::devLog('Started building object tree for record ' . $this->recordObject->getIdentifier() . '.', 't3lib_TCEforms_FormBuilder', t3lib_div::SYSLOG_SEVERITY_INFO);

		// TODO use the data structure object here -- URGENT
		$sheets = $this->recordObject->getDisplayConfiguration();
		//$fieldList = $this->recordObject->getFieldList();

		foreach ($sheets as $sheet) {
			$sheetObject = $this->createSheetObjectFromDefinition($sheet);
		}
	}

	protected function createSheetObjectFromDefinition($sheetDefinition) {
		$sheetObject = $this->createSheetObject($this->recordObject->getSheetCount() + 1, $sheetDefinition);
		$this->recordObject->addSheetObject($sheetObject);

		foreach ($sheetDefinition->getElements() as $element) {
			if (is_a($element, 't3lib_TCA_DataStructure_Field')) {
				$elementObject = $this->getSingleField($element->getName(), $element->getConfiguration(), $element->getLabel());
				$elementObject->setContextObject($this->contextObject)
				              ->setContextRecordObject($this->recordObject->getContextRecordObject())
				              ->setRecordObject($this->recordObject)
				              ->setParentFormObject($this->recordObject->getParentFormObject())
				              ->setTable($this->recordObject->getTable())
				              ->setRecord($this->recordObject->getRecordData())
				              ->injectFormBuilder($this)
				              ->init();

				$sheetObject->addChildObject($elementObject);
			}
		}
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
	 * @param   string   $theField  The field name
	 * @param   string   $fieldConf The field configuration
	 * @param   string   $altName   Alternative field name label to show.
	 * @return  t3lib_TCEforms_AbstractElement
	 */
	public function getSingleField($theField, $fieldConf, $altName='') {
		// Using "form_type" locally in this script
		$fieldConf['config']['form_type'] = $fieldConf['config']['form_type'] ? $fieldConf['config']['form_type'] : $fieldConf['config']['type'];

		$elementObject = $this->createElementObject($fieldConf['config']['form_type'], $theField, $fieldConf, $altName, $extra);

		return $elementObject;
	}

	public function createPaletteElement($paletteNumber, $label) {
		$classname = $this->createElementObject('palette', array());
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
	protected function createElementObject($type, $theField = '', $fieldConf = array()) {
		switch ($type) {
			default:
				$className = 't3lib_TCEforms_Element_'.$type;
				break;
		}

		if (!class_exists($className)) {
				// if class(file) does not exist, resolve to type "unknown"
			if (!@file_exists(PATH_t3lib.'tceforms/element/class.'.strtolower($className).'.php')) {
				return $this->createElementObject('unknown', $theField, $fieldConf);
			}
			include_once PATH_t3lib.'tceforms/element/class.'.strtolower($className).'.php';
		}

		return t3lib_div::makeInstance($className, $theField, $fieldConf);
	}

	/**
	 * Factory method for sheet objects on forms.
	 *
	 * @param   string  $sheetIdentString  The identifier of the sheet. Must be unique for the whole form
	 *                                     (and all sub-forms!)
	 * @param   t3lib_TCA_DataStructure_Sheet  $sheetDefinition
	 * @return  t3lib_TCEforms_Sheet
	 */
	public function createSheetObject($number, t3lib_TCA_DataStructure_Sheet $sheetDefinition) {
		$sheetIdentString = $this->recordObject->getShortSheetIdentifier() . '-' . $number;

		$sheetObject = new t3lib_TCEforms_Container_Sheet($sheetIdentString, $sheetDefinition->getLabel(),
		  $sheetDefinition->getName());

		return $sheetObject;
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

		$mainPalettesArray = t3lib_div::trimExplode(',', $this->dataStructure->getControlValue('mainpalette'), TRUE);

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
			                   ->setRecordObject($this->recordObject)
			                   ->setParentFormObject($this->recordObject->getParentFormObject())
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