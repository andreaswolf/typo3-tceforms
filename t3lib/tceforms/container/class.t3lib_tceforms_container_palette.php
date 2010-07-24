<?php

require_once(PATH_t3lib.'interfaces/interface.t3lib_tceforms_container.php');

class t3lib_TCEforms_Container_Palette implements t3lib_TCEforms_Container {
	protected $table;
	protected $record;
	protected $paletteNumber;
	protected $field;


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


	public function __construct($paletteNumber) {
		$this->paletteNumber = $paletteNumber;
	}

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
		$this->recordObject->setPaletteCreated($this->paletteNumber);
	}

	public function addElement(t3lib_TCEforms_Element_Abstract $elementObject) {
		$elementObject->setIsInPalette(TRUE);
		$elementObject->setContainer($this);
		$this->paletteElements[] = $elementObject;
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
		if ($TCAdefinition && (is_array($TCAdefinition['palettes'][$this->paletteNumber]) || $itemList)) {
			$itemList = ($itemList ? $itemList : $TCAdefinition['palettes'][$this->paletteNumber]['showitem']);
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

	public function render() {
		if (count($this->paletteElements) == 0) {
			return '';
		}

		$template = t3lib_parsehtml::getSubpart($this->contextObject->getTemplateContent(), '###PALETTE_FIELD###');

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

	/**
	 * Creates HTML output for a palette
	 *
	 * @param	array		The palette array to print
	 * @return	string		HTML output
	 */
	protected function printPalette($palArr)	{

			// Init color/class attributes:
		$ccAttr2 = $this->colorScheme[2] ? ' bgcolor="'.$this->colorScheme[2].'"' : '';
		$ccAttr2.= $this->classScheme[2] ? ' class="'.$this->classScheme[2].'"' : '';
		$ccAttr4 = $this->colorScheme[4] ? ' style="color:'.$this->colorScheme[4].'"' : '';
		$ccAttr4.= $this->classScheme[4] ? ' class="'.$this->classScheme[4].'"' : '';

		$row = 0;
		$hRow = $iRow = array();
		$lastLineWasLinebreak = FALSE;

			// Traverse palette fields and render them into table rows:
		foreach($palArr as $content) {
			if ($content['NAME'] === '--linebreak--') {
				if (!$lastLineWasLinebreak) {
					$row++;
					$lastLineWasLinebreak = TRUE;
				}
			} else {
				$lastLineWasLinebreak = FALSE;
				$hRow[$row][] = '<td' . $ccAttr2 . '>&nbsp;</td>
					<td nowrap="nowrap"'.$ccAttr2.'>'.
						'<span'.$ccAttr4.'>'.
							$content['NAME'].
						'</span>'.
					'</td>';
				$iRow[$row][] = '<td valign="top">' .
						'<img name="req_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" class="t3-TCEforms-reqPaletteImg" alt="" />'.
						'<img name="cm_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" class="t3-TCEforms-contentchangedPaletteImg" alt="" />'.
					'</td>
					<td nowrap="nowrap" valign="top">'.
						$content['ITEM'].
						$content['HELP_ICON'].
					'</td>';
			}
		}

			// Final wrapping into the table:
		$out='<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-palette">';
		for ($i=0; $i<=$row; $i++) {
			$out .= '
			<tr>
				<td><img src="clear.gif" width="'.intval($this->paletteMargin).'" height="1" alt="" /></td>'.
					implode('
					',$hRow[$i]).'
			</tr>
			<tr>
				<td></td>'.
					implode('
					',$iRow[$i]).'
			</tr>';
		}
		$out .= '</table>';

		return $out;
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
	public function wrapOpenPalette($header, $retFunc)	{
		$res = '<a href="#" onclick="TBE_EDITOR.toggle_display_states(\''.$this->getHtmlId().'\',\'block\',\'none\'); return false;" >'.$header.'</a>';
		return array($res,'');
	}

	/**
	 * Returns the HTML id for this palette
	 *
	 * @return string
	 */
	protected function getHtmlId() {
		return 'TCEFORMS_'.$this->recordObject->getTable().'_'.$this->paletteNumber.'_'.$this->recordObject->getValue('uid');
	}

	/**
	 * Returns true if the palette is collapsed (not shown, but may be displayed via an icon)
	 *
	 * @return	boolean
	 */
	public function isCollapsed()	{
		$tableDataStructure = $this->recordObject->getDataStructure();

		if ($this->displayed)
			return 0;
		if ($tableTCAdefinition['ctrl']['canNotCollapse'])
			return 0;
		if (is_array($tableTCAdefinition['palettes'][$this->paletteNumber]) && $tableTCAdefinition['palettes'][$this->paletteNumber]['canNotCollapse'])
			return 0;

		return $this->contextObject->getPalettesCollapsed();
	}

	public function setDisplayed($displayed) {
		$this->displayed = $displayed;
	}

	public function getNestedStackEntry() {
		return false;
	}
}

?>