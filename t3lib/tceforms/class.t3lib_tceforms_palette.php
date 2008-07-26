<?php

require_once(PATH_t3lib.'interfaces/interface.t3lib_tceforms_container.php');

class t3lib_TCEforms_Palette implements t3lib_TCEforms_Container {
	protected $table;
	protected $record;
	protected $paletteNumber;
	protected $field;


	/**
	 * @var t3lib_TCEforms_AbstractForm
	 */
	protected $TCEformsObject;

	protected $containingObject;


	public function init($table, $row, $typeNumer, $paletteNumber, $field, $fieldDescriptionParts, $header='', $itemList='', $collapsedHeader=NULL) {
		$this->table = $table;
		$this->record = $row;
		$this->typeNumber = $typeNumber;
		$this->paletteNumber = $paletteNumber;
		$this->field = $field;
	}

	public function setTCEformsObject(t3lib_TCEforms_AbstractForm $formObject) {
		$this->TCEformsObject = $formObject;
	}

	public function setContainingObject(t3lib_TCEforms_AbstractElement $containingObject) {
		$this->containingObject = $containingObject;
	}

	protected function loadElements() {
		global $TCA;

		t3lib_div::loadTCA($this->table);
		$parts = array();

			// Getting excludeElements, if any.
		if (!is_array($this->excludeElements))	{
			$this->excludeElements = $this->TCEformsObject->getExcludeElements();
		}

			// Load the palette TCEform elements
		if ($TCA[$this->table] && (is_array($TCA[$this->table]['palettes'][$this->paletteNumber]) || $itemList))	{
			$itemList = ($itemList ? $itemList : $TCA[$this->table]['palettes'][$this->paletteNumber]['showitem']);
			if ($itemList)	{
				$fields = t3lib_div::trimExplode(',',$itemList,1);
				foreach($fields as $info)	{
					$fieldParts = t3lib_div::trimExplode(';',$info);
					$theField = $fieldParts[0];

					if (!in_array($theField, $this->excludeElements) && $TCA[$this->table]['columns'][$theField])	{
						$this->fieldArr[] = $theField;
						$elem = $this->TCEformsObject->getSingleField($this->table,$theField,$this->record,$fieldParts[1],1,'',$fieldParts[2]);

						if ($elem instanceof t3lib_TCEforms_AbstractElement) {
							$parts[] = $elem;
						}
					}
				}
			}
		}
		return $parts;
	}

	public function render() {
		$paletteElements = $this->loadElements();

		if (count($paletteElements) == 0) {
			return '';
		}

		$template = t3lib_parsehtml::getSubpart($this->TCEformsObject->getTemplateContent(), '###PALETTE_FIELD###');

		foreach ($paletteElements as $paletteElement) {
			$paletteContents[] = $paletteElement->render();
		}

		$content = $this->printPalette($paletteContents);

		$paletteHtml = $this->wrapPaletteField($content, $table, $row, $palette, $collapsed);

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

			// Traverse palette fields and render them into table rows:
		foreach($palArr as $content)	{
			$hRow[]='<td'.$ccAttr2.'>&nbsp;</td>
					<td nowrap="nowrap"'.$ccAttr2.'>'.
						'<span'.$ccAttr4.'>'.
							$content['NAME'].
						'</span>'.
					'</td>';
			$iRow[]='<td valign="top">'.
						'<img name="req_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" width="10" height="10" vspace="4" alt="" />'.
						'<img name="cm_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" width="7" height="10" vspace="4" alt="" />'.
					'</td>
					<td nowrap="nowrap" valign="top">'.
						$content['ITEM'].
						$content['HELP_ICON'].
					'</td>';
		}

			// Final wrapping into the table:
		$out='<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-palette">
			<tr>
				<td><img src="clear.gif" width="'.intval($this->paletteMargin).'" height="1" alt="" /></td>'.
					implode('
				',$hRow).'
			</tr>
			<tr>
				<td></td>'.
					implode('
				',$iRow).'
			</tr>
		</table>';

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
		$display = $this->isPalettesCollapsed() ? 'none' : 'block';
		$id = 'TCEFORMS_'.$this->table.'_'.$this->paletteNumber.'_'.$this->record['uid'];
		$code = '<div id="'.$id.'" style="display:'.$display.';" >'.$code.'</div>';
		return $code;
	}

	/**
	 * Returns true if the palette is collapsed (not shown, but may be displayed via an icon)
	 *
	 * @return	boolean
	 */
	protected function isPalettesCollapsed()	{
		global $TCA;

		if ($TCA[$this->table]['ctrl']['canNotCollapse']) return 0;
		if (is_array($TCA[$this->table]['palettes'][$this->palette]) && $TCA[$this->table]['palettes'][$this->palette]['canNotCollapse'])	return 0;
		return $this->TCEformsObject->getPalettesCollapsed();
	}
}

?>