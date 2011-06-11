<?php

class t3lib_TCEforms_Container_Palette implements t3lib_TCEforms_Container {
	/**
	 * The name of this palette, as used as key in the palettes array of the TCA configuration
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The form this palette belongs to
	 *
	 * @var t3lib_TCEforms_Form
	 */
	protected $parentFormObject;

	/**
	 * The record this palette belongs to
	 *
	 * @var t3lib_TCEforms_Record
	 */
	protected $recordObject;

	/**
	 * The context this element is in (i.e., the top-level form)
	 *
	 * @var t3lib_TCEforms_Context
	 */
	protected $contextObject;

	/**
	 * The elements in this palette
	 *
	 * @var array<t3lib_TCEforms_Element_Abstract>
	 */
	protected $paletteElements;

	/**
	 * The data structure definition of this palette
	 *
	 * @var t3lib_DataStructure_Element_Palette
	 */
	protected $paletteDefinition;


	public function __construct(t3lib_DataStructure_Element_Palette $paletteDefinition, $paletteName) {
		$this->paletteDefinition = $paletteDefinition;
		$this->name = $paletteName;
	}

	/**
	 * @deprecated
	 */
	public function injectFormBuilder(t3lib_TCEforms_FormBuilder $formBuilder) {
		$this->formBuilder = $formBuilder;

		return $this;
	}

	public function setContainingObject(t3lib_TCEforms_Element $containingObject) {
		$this->containingObject = $containingObject;

		return $this;
	}

	public function setContextObject(t3lib_TCEforms_Form $formObject) {
		$this->contextObject = $formObject;

		return $this;
	}

	public function setRecordObject(t3lib_TCEforms_Record $recordObject) {
		$this->recordObject = $recordObject;

		return $this;
	}

	public function init() {
		$this->recordObject->setPaletteCreated($this->name);
	}

	public function addElement(t3lib_TCEforms_Element_Abstract $elementObject) {
		$elementObject->setIsInPalette(TRUE);
		$elementObject->setContainer($this);
		$this->paletteElements[] = $elementObject;
	}

	public function getElements() {
		return $this->paletteElements;
	}

	public function getName() {
		return $this->name;
	}

	/**
	 * @deprecated
	 */
	protected function loadElements() {
		$parts = array();

		$TCAdefinition = $this->recordObject->getTCAdefinitionForTable();

			// Getting excluded elements, if any.
		// TODO: check if there was a possibility to define own excluded elements for a palette in the past
		/*if (!is_array($this->excludedElements))	{
			$this->excludedElements = $this->parentFormObject->getExcludedElements();
		}*/

			// Load the palette TCEform elements
		if ($TCAdefinition && (is_array($TCAdefinition['palettes'][$this->name]) || $itemList)) {
			$itemList = ($itemList ? $itemList : $TCAdefinition['palettes'][$this->name]['showitem']);
			if ($itemList) {
				$fields = t3lib_div::trimExplode(',',$itemList,1);
				foreach($fields as $info) {
					$fieldParts = t3lib_div::trimExplode(';',$info);
					$theField = $fieldParts[0];

					if ($theField === '--linebreak--') {
						$parts[]['NAME'] = '--linebreak--';
					} elseif (!$this->recordObject->isExcludeElement($theField) && $this->recordObject->getTCAdefinitionForField($theField)) {
						$this->fieldArr[] = $theField;
						$elem = $this->formBuilder->getSingleField($theField, $this->recordObject->getTCAdefinitionForField($theField), $fieldParts[1], $fieldParts[3]);

						$elem->setContextObject($this->contextObject)
						     ->setRecordObject($this->recordObject)
						     ->setTable($this->recordObject->getTable())
						     ->setRecord($this->recordObject->getRecordData())
						     ->injectFormBuilder($this->formBuilder)
						     ->setIsInPalette(TRUE);
						$elem->init();

						if ($elem instanceof t3lib_TCEforms_Element_Abstract) {
							$parts[] = $elem;
						}
					}
				}
			}
		}
		return $parts;
	}

	/**
	 * needs to be removed, not called anymore
	 * @deprecated
	 */
	public function render() {
		if (count($this->paletteElements) == 0) {
			return '';
		}

		$template = t3lib_parsehtml::getSubpart($this->contextObject->getTemplateContent(), '###PALETTE_FIELDTEMPLATE###');

		foreach ($this->paletteElements as $paletteElement) {
			$paletteContents[] = $paletteElement->render();
		}

		$content = $this->printPalette($paletteContents);

		$paletteHtml = $this->wrapPaletteField($content);

		$out = $this->containingObject->intoTemplate(
			array('PALETTE' => $thePalIcon . $paletteHtml),
			$template
		);

		return $out;
	}

	public function getLabelAttributes() {
		$labelAttributes = '';

			// Init color/class attributes:
		if ($this->colorScheme[2]) {
			$labelAttributes .= ' bgcolor="' . $this->colorScheme[2] . '"';
		}

		if ($this->classScheme[2]) {
			$labelAttributes .= ' class="t3-form-palette-field-label ' . $this->classScheme[2] . '"';
		} else {
			$labelAttributes .= ' class="t3-form-palette-field-label"';
		}
		return $labelAttributes;
	}

	public function getFieldAttributes() {
		if ($this->colorScheme[4]) {
			$fieldAttributes .= ' style="color: ' . $this->colorScheme[4] . '"';
		}

		if ($this->classScheme[4]) {
			$fieldAttributes .= ' class="t3-form-palette-field' . $this->classScheme[4] . '"';
		}

		return $fieldAttributes;
	}

 	/**
	 * Add the id and the style property to the field palette
	 *
	 * @param	string		Palette Code
	 * @param	string		The table name for which to open the palette.
	 * @param	string		Palette ID
	 * @param	string		The record array
	 * @return	boolean		is collapsed
	 */
	protected function wrapPaletteField($code)	{
		$display = $this->isCollapsed() ? 'none' : 'block';
		$code = '<div id="'.$this->getHtmlId().'" style="display:'.$display.';" >'.$code.'</div>';
		return $code;
	}

	/**
	 * Wraps a string with a link to the palette.
	 *
	 * @param	string		The string to wrap in an A-tag
	 * @param	string		The table name for which to open the palette.
	 * @param	array		The palette pointer.
	 * @param	integer		The record array
	 */
	public function wrapOpenPalette($header, $retFunc) {
		$res = '<a href="#" onclick="TBE_EDITOR.toggle_display_states(\''.$this->getHtmlId().'\',\'block\',\'none\'); return false;" >'.$header.'</a>';
		return array($res,'');
	}

	/**
	 * Returns the HTML id for this palette
	 *
	 * @return string
	 */
	protected function getHtmlId() {
		return 'TCEFORMS_'.$this->recordObject->getTable().'_'.$this->name.'_'.$this->recordObject->getValue('uid');
	}

	/**
	 * Returns true if the palette is collapsed (not shown, but may be displayed via an icon)
	 *
	 * @return	boolean
	 */
	public function isCollapsed() {
		$tableDataStructure = $this->recordObject->getDataStructure();

		if ($this->displayed) {
			return FALSE;
		}
		if ($tableDataStructure->getControlValue('canNotCollapse') == TRUE) {
			return FALSE;
		}
		if (!$this->paletteDefinition->isCollapsible()) {
			return FALSE;
		}

		return $this->contextObject->getPalettesCollapsed();
	}

	public function getToggleIcon() {
		$icon = t3lib_iconWorks::getSpriteIcon('actions-system-options-view', array('title' => htmlspecialchars(t3lib_TCEforms_Helper::getLL('l_moreOptions'))));
		$res = '<a href="#" onclick="TBE_EDITOR.toggle_display_states(\'' . $this->getHtmlId() . '\',\'block\',\'none\'); return false;" >' . $icon . '</a>';
		return $res;
	}

	public function setDisplayed($displayed) {
		$this->displayed = $displayed;
	}

	public function getNestedStackEntry() {
		return false;
	}
}

?>