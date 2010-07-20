<?php

require_once(PATH_t3lib.'tceforms/element/class.t3lib_tceforms_element_abstract.php');


class t3lib_TCEforms_Element_Select extends t3lib_TCEforms_Element_Abstract {

	/**
	 * @var string
	 */
	protected $nonMatchingValueLabel;

	protected $selectItems;


	protected function renderField() {
		global $TCA;

		$disabled = '';
		if($this->contextObject->isReadOnly() || $this->fieldConfig['config']['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

		// "Extra" configuration; Returns configuration for the field based
		// on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		$specConf = $this->getSpecConfFromString($this->extra, $this->fieldConfig['defaultExtras']);

		// Getting the selector box items from the system
		$this->selectItems = $this->addSelectOptionsToItemArray(
			$this->initItemArray($this->fieldConfig),
			$this->getTSconfig(FALSE)
		);
		$this->selectItems = $this->addItems($this->selectItems, $this->fieldTSConfig['addItems.']);
		if ($this->fieldConfig['config']['itemsProcFunc']) {
			$this->selectItems = $this->procItems($this->selectItems, $this->fieldTSConfig['itemsProcFunc.'], $this->fieldConfig['config'], $this->table, $this->record, $this->field);
		}
			// Possibly filter some items:
		$keepItemsFunc = create_function('$value', 'return $value[1];');
		$this->selectItems = t3lib_div::keepItemsInArray($this->selectItems, $this->fieldTSConfig['keepItems'], $keepItemsFunc);
			// Process items by a user function:
		if (isset($this->fieldConfig['config']['itemsProcFunc']) && $this->fieldConfig['config']['itemsProcFunc']) {
			$this->selectItems = $this->procItems($this->selectItems, $this->fieldTSConfig['itemsProcFunc.'], $this->fieldConfig['config'], $this->table, $this->record, $this->field);
		}

			// Possibly remove some items:
		$removeItems = t3lib_div::trimExplode(',',$this->fieldTSConfig['removeItems'],1);

		foreach ($this->selectItems as $tk => $p)	{

			// Checking languages and authMode:
			$languageDeny = ($TCA[$this->table]['ctrl']['languageField'] && !strcmp($TCA[$this->table]['ctrl']['languageField'], $this->field) && !$GLOBALS['BE_USER']->checkLanguageAccess($p[1]));
			$authModeDeny = ($this->fieldConfig['config']['form_type'] == 'select' && $this->fieldConfig['config']['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode($this->table, $this->field, $p[1], $this->fieldConfig['config']['authMode']));
			if (in_array($p[1], $removeItems) || $languageDeny || $authModeDeny) {
				unset($this->selectItems[$tk]);
			} elseif (isset($this->fieldTSConfig['altLabels.'][$p[1]])) {
				$this->selectItems[$tk][0]=$this->sL($this->fieldTSConfig['altLabels.'][$p[1]]);
			}

			// Removing doktypes with no access:
			if ($this->table.'.'.$this->field == 'pages.doktype') {
				if (!($GLOBALS['BE_USER']->isAdmin() || t3lib_div::inList($GLOBALS['BE_USER']->groupData['pagetypes_select'],$p[1]))) {
					unset($this->selectItems[$tk]);
				}
			}
		}

		// Creating the label for the "No Matching Value" entry.
		$this->nonMatchingValueLabel = (isset($this->fieldTSConfig['noMatchingValue_label']) ? $this->sL($this->fieldTSConfig['noMatchingValue_label']) : '[ ' . $this->getLL('l_noMatchingValue') . ' ]');

		// If a SINGLE selector box...
		if (intval($this->fieldConfig['config']['maxitems']) <= 1) {
			$item = $this->initSubtypeSingle();
		} elseif (!strcmp($this->fieldConfig['config']['renderMode'], 'checkbox')) {
			// Checkbox renderMode
			$item = $this->initSubtypeCheckbox();
		} elseif (!strcmp($this->fieldConfig['config']['renderMode'], 'singlebox')) {
			// Single selector box renderMode
			$item = $this->initSubtypeSinglebox();
		} else {
			// Traditional multiple selector box:
			$item = $this->initSubtypeMultiple();
		}

			// Wizards:
		if (!$disabled) {
			$altItem = '<input type="hidden" name="' . $this->itemFormElName . '" value="' . htmlspecialchars($this->itemFormElValue)  .'" />';
			$item = $this->renderWizards(array($item, $altItem), $this->fieldConfig['config']['wizards'], $this->itemFormElName, $specConf);
		}

		return $item;
	}


	protected function initSubtypeSingle() {
			// check against inline uniqueness
		/* TODO reenable for IRRE
		if(is_array($inlineParent) && $inlineParent['uid']) {
			if ($inlineParent['config']['foreign_table'] == $this->table && $inlineParent['config']['foreign_unique'] == $this->field) {
				$uniqueIds = $this->TCEformsObject->inline->inlineData['unique'][$this->TCEformsObject->inline->inlineNames['object'].'['.$this->table.']']['used'];
				$this->fieldChangeFunc['inlineUnique'] = "inline.updateUnique(this,'".$this->TCEformsObject->inline->inlineNames['object'].'['.$this->table."]','".$this->TCEformsObject->inline->inlineNames['form']."','".$this->record['uid']."');";
			}
				// hide uid of parent record for symmetric relations
			if ($inlineParent['config']['foreign_table'] == $this->table && ($inlineParent['config']['foreign_field'] == $this->field || $inlineParent['config']['symmetric_field'] == $this->field)) {
				$uniqueIds[] = $inlineParent['uid'];
			}
		}
		 */

			// Initialization:
		$c = 0;
		$sI = 0;
		$noMatchingValue = 1;
		$opt = array();
		$selicons = array();
		$onlySelectedIconShown = 0;
		$size = intval($this->fieldConfig['config']['size']);
		$selectedStyle = ''; // Style set on <select/>

		$disabled = '';
		if ($this->isReadonly()) {
			$disabled = ' disabled="disabled"';
			$onlySelectedIconShown = 1;
		}

			// Icon configuration:
		if ($this->fieldConfig['config']['suppress_icons']=='IF_VALUE_FALSE')	{
			$suppressIcons = !$this->itemFormElValue ? 1 : 0;
		} elseif ($this->fieldConfig['config']['suppress_icons']=='ONLY_SELECTED')	{
			$suppressIcons=0;
			$onlySelectedIconShown=1;
		} elseif ($this->fieldConfig['config']['suppress_icons']) 	{
			$suppressIcons = 1;
		} else $suppressIcons = 0;

			// Traverse the Array of selector box items:
		$optGroupStart = array();
		foreach($this->selectItems as $p)	{
			$sM = (!strcmp($this->itemFormElValue,$p[1])?' selected="selected"':'');
			if ($sM)	{
				$sI = $c;
				$noMatchingValue = 0;
			}

				// Getting style attribute value (for icons):
			if ($this->fieldConfig['config']['iconsInOptionTags'])	{
				$styleAttrValue = $this->optionTagStyle($p[2]);
				if ($sM) {
					list($selectIconFile,$selectIconInfo) = $this->getIcon($p[2]);
						if (!empty($selectIconInfo)) {
							$selectedStyle = ' style="background: #fff url(' . $selectIconFile . ') 0% 50% no-repeat; padding: 1px 1px 1px 24px; -webkit-background-size: 0;"';
						}
				}
			}

				// Compiling the <option> tag:
			if (!($p[1] != $this->itemFormElValue && is_array($uniqueIds) && in_array($p[1], $uniqueIds))) {
				if(!strcmp($p[1],'--div--')) {
					$optGroupStart[0] = $p[0];
					$optGroupStart[1] = $styleAttrValue;

				} else {
					if (count($optGroupStart)) {
						if($optGroupOpen) { // Closing last optgroup before next one starts
							$opt[]='</optgroup>' . "\n";
						}
						$opt[]= '<optgroup label="'.t3lib_div::deHSCentities(htmlspecialchars($optGroupStart[0])).'"'.
								($optGroupStart[1] ? ' style="'.htmlspecialchars($optGroupStart[1]).'"' : '').
								' class="c-divider">' . "\n";
						$optGroupOpen = true;
						$c--;
						$optGroupStart = array();
					}
					$opt[]= '<option value="'.htmlspecialchars($p[1]).'"'.
							$sM.
							($styleAttrValue ? ' style="'.htmlspecialchars($styleAttrValue).'"' : '').
							'>' . t3lib_div::deHSCentities($p[0])  .'</option>' . "\n";
				}
			}

				// If there is an icon for the selector box (rendered in table under)...:
			if ($p[2] && !$suppressIcons && (!$onlySelectedIconShown || $sM))	{
				list($selIconFile,$selIconInfo)=$this->getIcon($p[2]);
				$iOnClick = $this->contextObject->elName($this->itemFormElName) . '.selectedIndex='.$c.'; ' .
					$this->contextObject->elName($this->itemFormElName) . '.style.backgroundImage=' . $this->contextObject->elName($this->itemFormElName) . '.options[' . $c .'].style.backgroundImage; ' .
					implode('', $this->fieldChangeFunc) . $this->contextObject->blur().'return false;';
				$selicons[]=array(
					(!$onlySelectedIconShown ? '<a href="#" onclick="'.htmlspecialchars($iOnClick).'">' : '').
					'<img src="'.$selIconFile.'" '.$selIconInfo[3].' vspace="2" border="0" title="'.htmlspecialchars($p[0]).'" alt="'.htmlspecialchars($p[0]).'" />'.
					(!$onlySelectedIconShown ? '</a>' : ''),
					$c,$sM);
				$onChangeIcon = 'this.style.backgroundImage=this.options[this.selectedIndex].style.backgroundImage;';
			}
			$c++;
		}

		if($optGroupOpen) { // Closing optgroup if open
			$opt[]='</optgroup>';
			$optGroupOpen = false;
		}

			// No-matching-value:
		if ($this->itemFormElValue && $noMatchingValue && !$this->fieldTSConfig['disableNoMatchingValueElement'] && !$this->fieldConfig['config']['disableNoMatchingValueElement'])	{
			$nMV_label = @sprintf($nMV_label, $this->itemFormElValue);
			$opt[]= '<option value="'.htmlspecialchars($this->itemFormElValue).'" selected="selected">'.htmlspecialchars($nMV_label).'</option>';
		}

			// Create item form fields:
		$sOnChange = 'if (this.options[this.selectedIndex].value==\'--div--\') {this.selectedIndex='.$sI.';} '.implode('',$this->fieldChangeFunc);
		if(!$disabled) {
			$item.= '<input type="hidden" name="'.$this->itemFormElName.'_selIconVal" value="'.htmlspecialchars($sI).'" />';	// MUST be inserted before the selector - else is the value of the hiddenfield here mysteriously submitted...
		}
		$item.= '<select'.$selectedStyle.' id="' . uniqid('tceforms-select-') . '" name="'.$this->itemFormElName.'"'.
					($config['iconsInOptionTags'] ? ' class="icon-select"' : '') .
					$this->insertDefaultElementStyle('select', 'tceforms-multiselect').
					($size?' size="'.$size.'"':'').
					' onchange="'.htmlspecialchars($onChangeIcon . $sOnChange).'"'.
					$this->onFocus.$disabled.'>';
		$item.= implode('',$opt);
		$item.= '</select>';

			// Create icon table:
		if (count($selicons) && !$this->fieldConfig['config']['noIconsBelowSelect'])	{
			$item.='<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-selectIcons">';
			$selicon_cols = intval($this->fieldConfig['config']['selicon_cols']);
			if (!$selicon_cols)	$selicon_cols=count($selicons);
			$sR = ceil(count($selicons)/$selicon_cols);
			$selicons = array_pad($selicons,$sR*$selicon_cols,'');
			for($sa=0;$sa<$sR;$sa++)	{
				$item.='<tr>';
				for($sb=0;$sb<$selicon_cols;$sb++)	{
					$sk=($sa*$selicon_cols+$sb);
					$imgN = 'selIcon_'.$this->table.'_'.$this->record['uid'].'_'.$this->field.'_'.$selicons[$sk][1];
					$imgS = ($selicons[$sk][2]?$this->backPath.'gfx/content_selected.gif':'clear.gif');
					$item.='<td><img name="'.htmlspecialchars($imgN).'" src="'.$imgS.'" width="7" height="10" alt="" /></td>';
					$item.='<td>'.$selicons[$sk][0].'</td>';
				}
				$item.='</tr>';
			}
			$item.='</table>';
		}

		return $item;
	}

	protected function initSubtypeCheckbox() {

			// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->extractValuesOnlyFromValueLabelList($this->itemFormElValue));

		$disabled = '';
		if($this->contextObject->isReadOnly() || $this->fieldConfig['config']['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Traverse the Array of selector box items:
		$tRows = array();
		$c=0;
		if (!$disabled) {
			$sOnChange = implode('',$this->fieldChangeFunc);
			$setAll = array();	// Used to accumulate the JS needed to restore the original selection.
			foreach($this->selectItems as $p)	{
					// Non-selectable element:
				if (!strcmp($p[1],'--div--'))	{
					if (count($setAll))	{
							$tRows[] = '
								<tr class="c-header-checkbox-controls">
									<td colspan="3">' .
										'<a href="#" onclick="' . htmlspecialchars(implode('', $setAll).' return false;') . '">' .
										htmlspecialchars($this->getLL('l_checkAll')) .
										'</a>
										<a href="#" onclick="' . htmlspecialchars(implode('', $unSetAll).' return false;').'">' .
										htmlspecialchars($this->getLL('l_uncheckAll')) .
										'</a>
									</td>
								</tr>';
							$setAll = array();
							$unSetAll = array();
					}

					$tRows[] = '
						<tr class="c-header">
							<td colspan="3">'.htmlspecialchars($p[0]).'</td>
						</tr>';
				} else {
						// Selected or not by default:
					$sM = '';
					if (isset($itemArray[$p[1]]))	{
						$sM = ' checked="checked"';
						unset($itemArray[$p[1]]);
					}

						// Icon:
					$selIconFile = '';
					if ($p[2])	{
						list($selIconFile,$selIconInfo) = $this->getIcon($p[2]);
					}

						// Compile row:
					$this->recordId = uniqid('select_checkbox_row_');
					$onClickCell = $this->contextObject->elName($this->itemFormElName . '[' . $c . ']') . '.checked=!' . $this->contextObject->elName($this->itemFormElName . '[' . $c . ']') . '.checked;';
					$onClick = 'this.attributes.getNamedItem("class").nodeValue = ' . $this->contextObject->elName($this->itemFormElName . '[' . $c . ']') . '.checked ? "c-selectedItem" : "c-unselectedItem";';
					$setAll[] = $this->contextObject->elName($this->itemFormElName . '[' . $c . ']') . '.checked=1;';
					$setAll[] .= '$(\'' . $this->recordId . '\').removeClassName(\'c-unselectedItem\');$(\'' . $this->recordId . '\').addClassName(\'c-selectedItem\');';
					$unSetAll[] = $this->contextObject->elName($this->itemFormElName.'['.$c.']').'.checked=0;';
					$unSetAll[] .= '$(\'' . $this->recordId . '\').removeClassName(\'c-selectedItem\');$(\'' . $this->recordId . '\').addClassName(\'c-unselectedItem\');';
					$restoreCmd[] = $this->contextObject->elName($this->itemFormElName . '[' . $c . ']') . '.checked=' . ($sM ? 1 : 0) . ';' .
								'$(\'' . $this->recordId . '\').removeClassName(\'c-selectedItem\');$(\'' . $this->recordId . '\').removeClassName(\'c-unselectedItem\');' .
								'$(\'' . $this->recordId . '\').addClassName(\'c-' . ($sM ? '' : 'un') . 'selectedItem\');';

					$hasHelp = ($p[3] !='');

					$label = t3lib_div::deHSCentities(htmlspecialchars($p[0]));
					$help = $hasHelp ? '<span class="typo3-csh-inline show-right"><span class="header">' . $label . '</span>' .
						'<span class="paragraph">' . $GLOBALS['LANG']->hscAndCharConv(nl2br(trim(htmlspecialchars($p[3]))), false) . '</span></span>' : '';

					if ($hasHelp && $this->contextObject->getEditFieldHelpMode == 'icon') {
						$helpIcon  = '<a class="typo3-csh-link" href="#">';
						$helpIcon .= '<img' . t3lib_iconWorks::skinImg($this->contextObject->getBackPath(), 'gfx/helpbubble.gif', 'width="14" height="14"');
						$helpIcon .= ' hspace="2" border="0" class="absmiddle"' . ($GLOBALS['CLIENT']['FORMSTYLE'] ? ' style="cursor:help;"' : '') . ' alt="" />' . $help;
						$helpIcon .= '</a>';
						$help = $helpIcon;
					}

					$tRows[] = '
						<tr id="' . $this->recordId . '" class="'.($sM ? 'c-selectedItem' : 'c-unselectedItem').'" onclick="'.htmlspecialchars($onClick).'" style="cursor: pointer;">
							<td width="12"><input type="checkbox"'.$this->insertDefaultElementStyle('check').' name="'.htmlspecialchars($this->itemFormElName.'['.$c.']').'" value="'.htmlspecialchars($p[1]).'"'.$sM.' onclick="'.htmlspecialchars($sOnChange).'"'.$this->onFocus.' /></td>
							<td class="c-labelCell" onclick="'.htmlspecialchars($onClickCell).'">'.
								($selIconFile ? '<img src="'.$selIconFile.'" '.$selIconInfo[3].' vspace="2" border="0" class="absmiddle" style="margin-right: 4px;" alt="" />' : '').
								$label .
 								'</td>
								<td class="c-descr" onclick="'.htmlspecialchars($onClickCell).'">' . (strcmp($p[3],'') ? $help : '') . '</td>
						</tr>';
					$c++;
				}
			}

				// Remaining checkboxes will get their set-all link:
			if (count($setAll))	{
					$tRows[] = '
						<tr class="c-header-checkbox-controls">
							<td colspan="3">'.
								'<a href="#" onclick="' . htmlspecialchars(implode('', $setAll).' return false;') . '">' .
								htmlspecialchars($this->getLL('l_checkAll')) .
								'</a>
								<a href="#" onclick="' . htmlspecialchars(implode('', $unSetAll).' return false;') . '">' .
								htmlspecialchars($this->getLL('l_uncheckAll')) .
								'</a>
							</td>
						</tr>';
			}
		}

			// Remaining values (invalid):
		if (count($itemArray) && !$this->fieldTSConfig['disableNoMatchingValueElement'] && !$this->fieldConfig['config']['disableNoMatchingValueElement'])	{
			foreach($itemArray as $theNoMatchValue => $temp)	{
					// Compile <checkboxes> tag:
				array_unshift($tRows,'
						<tr class="c-invalidItem">
							<td><input type="checkbox"'.$this->insertDefaultElementStyle('check').' name="'.htmlspecialchars($this->itemFormElName.'['.$c.']').'" value="'.htmlspecialchars($theNoMatchValue).'" checked="checked" onclick="'.htmlspecialchars($sOnChange).'"'.$this->onFocus.$disabled.' /></td>
							<td class="c-labelCell">'.
								t3lib_div::deHSCentities(htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue))).
								'</td><td>&nbsp;</td>
						</tr>');
				$c++;
			}
		}

			// Add an empty hidden field which will send a blank value if all items are unselected.
		$item .= '<input type="hidden" name="'.htmlspecialchars($this->itemFormElName) . '" value="" />';

			// Add revert icon
		if (is_array($restoreCmd)) {
			$item .= '<a href="#" onclick="' . implode('', $restoreCmd).' return false;' . '">' .
				'<img'.t3lib_iconWorks::skinImg($this->contextObject->getBackPath(),'gfx/undo.gif','width="13" height="12"') . ' title="' .
				htmlspecialchars($this->getLL('l_revertSelection')) . '" alt="" />' .'</a>';
		}
 			// Implode rows in table:
		$item .= '
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-select-checkbox">'.
				implode('',$tRows).'
			</table>
			';

		return $item;
	}

	protected function initSubtypeSinglebox() {
			// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->TCEformsObject->extractValuesOnlyFromValueLabelList($this->itemFormElValue));

		$disabled = '';
		if($this->contextObject->isReadOnly() || $this->fieldConfig['config']['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Traverse the Array of selector box items:
		$opt = array();
		$restoreCmd = array();	// Used to accumulate the JS needed to restore the original selection.
		$c = 0;
		foreach($this->selectItems as $p)	{
				// Selected or not by default:
			$sM = '';
			if (isset($itemArray[$p[1]]))	{
				$sM = ' selected="selected"';
				$restoreCmd[] = $this->contextObject->elName($this->itemFormElName.'[]').'.options['.$c.'].selected=1;';
				unset($itemArray[$p[1]]);
			}

				// Non-selectable element:
			$nonSel = '';
			if (!strcmp($p[1],'--div--'))	{
				$nonSel = ' onclick="this.selected=0;" class="c-divider"';
			}

				// Icon style for option tag:
			if ($this->fieldConfig['config']['iconsInOptionTags']) {
				$styleAttrValue = $this->optionTagStyle($p[2]);
			}

				// Compile <option> tag:
			$opt[] = '<option value="'.htmlspecialchars($p[1]).'"'.
						$sM.
						$nonSel.
						($styleAttrValue ? ' style="'.htmlspecialchars($styleAttrValue).'"' : '').
						'>'.t3lib_div::deHSCentities(htmlspecialchars($p[0])).'</option>';
			$c++;
		}

			// Remaining values:
		if (count($itemArray) && !$this->fieldTSConfig['disableNoMatchingValueElement'] && !$this->fieldConfig['config']['disableNoMatchingValueElement'])	{
			foreach($itemArray as $theNoMatchValue => $temp)	{
					// Compile <option> tag:
				array_unshift($opt,'<option value="'.htmlspecialchars($theNoMatchValue).'" selected="selected">'.t3lib_div::deHSCentities(htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue))).'</option>');
			}
		}

			// Compile selector box:
		$sOnChange = implode('',$this->fieldChangeFunc);
		$selector_itemListStyle = isset($this->fieldConfig['config']['itemListStyle']) ? ' style="'.htmlspecialchars($this->fieldConfig['config']['itemListStyle']).'"' : ' style="'.$this->defaultMultipleSelectorStyle.'"';
		$size = intval($this->fieldConfig['config']['size']);
		$size = $this->fieldConfig['config']['autoSizeMax'] ? t3lib_div::intInRange(count($this->selectItems)+1,t3lib_div::intInRange($size,1),$this->fieldConfig['config']['autoSizeMax']) : $size;
		$selectBox = '<select id="' . uniqid('tceforms-multiselect-') . '" name="'.$this->itemFormElName.'[]"'.
						$this->insertDefaultElementStyle('select', 'tceforms-multiselect').
						($size ? ' size="'.$size.'"' : '').
						' multiple="multiple" onchange="'.htmlspecialchars($sOnChange).'"'.
						$this->onFocus.
						$selector_itemListStyle.
						$disabled.'>
						'.
					implode('
						',$opt).'
					</select>';

			// Add an empty hidden field which will send a blank value if all items are unselected.
		if (!$disabled) {
			$item.='<input type="hidden" name="'.htmlspecialchars($this->itemFormElName).'" value="" />';
		}

			// Put it all into a table:
		$item.= '
			<table border="0" cellspacing="0" cellpadding="0" width="1" class="typo3-TCEforms-select-singlebox">
				<tr>
					<td>
					'.$selectBox.'
					<br/>
					<em>'.
						htmlspecialchars($this->getLL('l_holdDownCTRL')).
						'</em>
					</td>
					<td valign="top">
					<a href="#" onclick="'.htmlspecialchars($this->contextObject->elName($this->itemFormElName.'[]').'.selectedIndex=-1;'.implode('',$restoreCmd).' return false;').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->contextObject->getBackPath(),'gfx/undo.gif','width="13" height="12"').' title="'.htmlspecialchars($this->getLL('l_revertSelection')).'" alt="" />'.
						'</a>
					</td>
				</tr>
			</table>
				';

		return $item;
	}

	protected function initSubtypeMultiple() {

		$disabled = '';
		if ($this->isReadOnly())  {
			$disabled = ' disabled="disabled"';
		}

			// Setting this hidden field (as a flag that JavaScript can read out)
		if (!$disabled) {
			$item.= '<input type="hidden" name="'.$this->itemFormElName.'_mul" value="'.($this->fieldConfig['config']['multiple']?1:0).'" />';
		}

			// Set max and min items:
		$maxitems = t3lib_div::intInRange($this->fieldConfig['config']['maxitems'],0);
		if (!$maxitems)	$maxitems=100000;
		$minitems = t3lib_div::intInRange($this->fieldConfig['config']['minitems'],0);

			// Register the required number of elements:
		// TODO: check if this works
		$this->contextObject->registerRequiredFieldRange($this->itemFormElName,
			array($minitems, $maxitems, 'imgName' => $this->table.'_'.$this->record['uid'].'_'.$this->field));

			// Get "removeItems":
		$removeItems = t3lib_div::trimExplode(',',$this->fieldTSConfig['removeItems'],1);

			// Get the array with selected items:
		$itemArray = t3lib_div::trimExplode(',', $this->itemFormElValue, 1);

			// Possibly filter some items:
		$keepItemsFunc = create_function('$value', '$parts=explode(\'|\',$value,2); return rawurldecode($parts[0]);');
		$itemArray = t3lib_div::keepItemsInArray($itemArray, $this->fieldTSConfig['keepItems'], $keepItemsFunc);

			// Perform modification of the selected items array:
		$itemArray = t3lib_div::trimExplode(',',$this->itemFormElValue,1);
		foreach($itemArray as $tk => $tv) {
			$tvP = explode('|',$tv,2);
			$evalValue = $tvP[0];
			$isRemoved = in_array($evalValue,$removeItems)  || ($this->fieldConfig['config']['form_type']=='select' && $this->fieldConfig['config']['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode($this->table,$this->field,$evalValue,$this->fieldConfig['config']['authMode']));
			if ($isRemoved && !$this->fieldTSConfig['disableNoMatchingValueElement'] && !$this->fieldConfig['config']['disableNoMatchingValueElement'])	{
				$tvP[1] = rawurlencode(@sprintf($nMV_label, $evalValue));
			} elseif (isset($this->fieldTSConfig['altLabels.'][$evalValue])) {
				$tvP[1] = rawurlencode($this->sL($this->fieldTSConfig['altLabels.'][$evalValue]));
			}
			if ($tvP[1] == '') {
					// Case: flexform, default values supplied, no label provided (bug #9795)
				foreach ($selItems as $selItem) {
					if ($selItem[1] == $tvP[0]) {
						$tvP[1] = html_entity_decode($selItem[0]);
						break;
					}
				}
			}
			$itemArray[$tk] = implode('|',$tvP);
		}
		$itemsToSelect = '';

		if(!$disabled) {
				// Create option tags:
			$opt = array();
			$styleAttrValue = '';
			foreach($this->selectItems as $p)	{
				if ($this->fieldConfig['config']['iconsInOptionTags'])	{
					$styleAttrValue = $this->optionTagStyle($p[2]);
				}
				$opt[]= '<option value="'.htmlspecialchars($p[1]).'"'.
								($styleAttrValue ? ' style="'.htmlspecialchars($styleAttrValue).'"' : '').
								'>' . $p[0] . '</option>';
			}

				// Put together the selector box:
			$selector_itemListStyle = isset($this->fieldConfig['config']['itemListStyle']) ? ' style="'.htmlspecialchars($this->fieldConfig['config']['itemListStyle']).'"' : ' style="'.$this->defaultMultipleSelectorStyle.'"';
			$size = intval($this->fieldConfig['config']['size']);
			$size = $this->fieldConfig['config']['autoSizeMax'] ? t3lib_div::intInRange(count($itemArray)+1,t3lib_div::intInRange($size,1),$this->fieldConfig['config']['autoSizeMax']) : $size;
			if ($this->fieldConfig['config']['exclusiveKeys'])	{
				$sOnChange = 'setFormValueFromBrowseWin(\''.$this->itemFormElName.'\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text,\''.$this->fieldConfig['config']['exclusiveKeys'].'\'); ';
			} else {
				$sOnChange = 'setFormValueFromBrowseWin(\''.$this->itemFormElName.'\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text); ';
			}
			$sOnChange .= implode('',$this->fieldChangeFunc);
			$itemsToSelect = '
				<select id="' . uniqid('tceforms-multiselect-') . '" name="'.$this->itemFormElName.'_sel"'.
							$this->insertDefaultElementStyle('select', 'tceforms-multiselect tceforms-itemstoselect').
							($size ? ' size="'.$size.'"' : '').
							' onchange="'.htmlspecialchars($sOnChange).'"'.
							$this->onFocus.
							$selector_itemListStyle.'>
					'.implode('
					',$opt).'
				</select>';
		}

			// Pass to "dbFileIcons" function:
		$params = array(
			'size' => $size,
			'autoSizeMax' => t3lib_div::intInRange($this->fieldConfig['config']['autoSizeMax'],0),
			'style' => isset($this->fieldConfig['config']['selectedListStyle']) ? ' style="'.htmlspecialchars($this->fieldConfig['config']['selectedListStyle']).'"' : ' style="'.$this->defaultMultipleSelectorStyle.'"',
			'dontShowMoveIcons' => ($maxitems<=1),
			'maxitems' => $maxitems,
			'info' => '',
			'headers' => array(
				'selector' => $this->getLL('l_selected').':<br />',
				'items' => $this->getLL('l_items').':<br />'
			),
			'noBrowser' => 1,
			'thumbnails' => $itemsToSelect,
			'readOnly' => $disabled
		);
		$item .= $this->dbFileIcons($this->itemFormElName,'','',$itemArray,'',$params,$this->onFocus);

		return $item;
	}



	/**
	 * Add selector box items of more exotic kinds.
	 *
	 * @param	array		The array of items (label,value,icon)
	 * @param	array		The "columns" array for the field (from TCA)
	 * @param	array		TSconfig for the table/row
	 * @param	string		The fieldname
	 * @return	array		The $items array modified.
	 */
	protected function addSelectOptionsToItemArray($items, $TSconfig) {
		global $TCA;

			// Values from foreign tables:
		if ($this->fieldConfig['config']['foreign_table']) {
			$items = $this->foreignTable($items, $TSconfig);
			if ($this->fieldConfig['config']['neg_foreign_table'])	{
				$items = $this->foreignTable($items, $TSconfig, 1);
			}
		}

			// Values from a file folder:
		if ($this->fieldConfig['config']['fileFolder'])	{
			$fileFolder = t3lib_div::getFileAbsFileName($this->fieldConfig['config']['fileFolder']);
			if (@is_dir($fileFolder))	{

					// Configurations:
				$extList = $this->fieldConfig['config']['fileFolder_extList'];
				$recursivityLevels = isset($this->fieldConfig['config']['fileFolder_recursions']) ? t3lib_div::intInRange($this->fieldConfig['config']['fileFolder_recursions'],0,99) : 99;

					// Get files:
				$fileFolder = ereg_replace('\/$','',$fileFolder).'/';
				$fileArr = t3lib_div::getAllFilesAndFoldersInPath(array(),$fileFolder,$extList,0,$recursivityLevels);
				$fileArr = t3lib_div::removePrefixPathFromList($fileArr, $fileFolder);

				foreach($fileArr as $fileRef)	{
					$fI = pathinfo($fileRef);
					$icon = t3lib_div::inList('gif,png,jpeg,jpg', strtolower($fI['extension'])) ? '../'.substr($fileFolder,strlen(PATH_site)).$fileRef : '';
					$items[] = array(
						$fileRef,
						$fileRef,
						$icon
					);
				}
			}
		}

			// If 'special' is configured:
		if ($this->fieldConfig['config']['special'])	{
			switch ($this->fieldConfig['config']['special'])	{
				case 'tables':
					$temp_tc = array_keys($TCA);
					$descr = '';

					foreach($temp_tc as $theTableNames)	{
						if (!$TCA[$theTableNames]['ctrl']['adminOnly'])	{

								// Icon:
							$icon = '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,t3lib_iconWorks::getIcon($theTableNames, array()),'',1);

								// Add description texts:
							if ($this->edit_showFieldHelp)	{
								$GLOBALS['LANG']->loadSingleTableDescription($theTableNames);
								$fDat = $GLOBALS['TCA_DESCR'][$theTableNames]['columns'][''];
								$descr = $fDat['description'];
							}

								// Item configuration:
							$items[] = array(
								$this->sL($TCA[$theTableNames]['ctrl']['title']),
								$theTableNames,
								$icon,
								$descr
							);
						}
					}
				break;
				case 'pagetypes':
					$theTypes = $TCA['pages']['columns']['doktype']['config']['items'];

					foreach($theTypes as $theTypeArrays)	{
							// Icon:
						$icon = $theTypeArrays[1]!='--div--' ? '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,t3lib_iconWorks::getIcon('pages', array('doktype' => $theTypeArrays[1])),'',1) : '';

							// Item configuration:
						$items[] = array(
							$this->sL($theTypeArrays[0]),
							$theTypeArrays[1],
							$icon
						);
					}
				break;
				case 'exclude':
					$theTypes = t3lib_BEfunc::getExcludeFields();
					$descr = '';

					foreach($theTypes as $theTypeArrays)	{
						list($theTable, $theField) = explode(':', $theTypeArrays[1]);

							// Add description texts:
						if ($this->edit_showFieldHelp)	{
							$GLOBALS['LANG']->loadSingleTableDescription($theTable);
							$fDat = $GLOBALS['TCA_DESCR'][$theTable]['columns'][$theField];
							$descr = $fDat['description'];
						}

							// Item configuration:
						$items[] = array(
							ereg_replace(':$','',$theTypeArrays[0]),
							$theTypeArrays[1],
							'',
							$descr
						);
					}
				break;
				case 'explicitValues':
					$theTypes = t3lib_BEfunc::getExplicitAuthFieldValues();

							// Icons:
					$icons = array(
						'ALLOW' => '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,'gfx/icon_ok2.gif','',1),
						'DENY' => '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,'gfx/icon_fatalerror.gif','',1),
					);

						// Traverse types:
					foreach($theTypes as $tableFieldKey => $theTypeArrays)	{

						if (is_array($theTypeArrays['items']))	{
								// Add header:
							$items[] = array(
								$theTypeArrays['tableFieldLabel'],
								'--div--',
							);

								// Traverse options for this field:
							foreach($theTypeArrays['items'] as $itemValue => $itemContent)	{
									// Add item to be selected:
								$items[] = array(
									'['.$itemContent[2].'] '.$itemContent[1],
									$tableFieldKey.':'.ereg_replace('[:|,]','',$itemValue).':'.$itemContent[0],
									$icons[$itemContent[0]]
								);
							}
						}
					}
				break;
				case 'languages':
					$items = array_merge($items,t3lib_BEfunc::getSystemLanguages());
				break;
				case 'custom':
						// Initialize:
					$customOptions = $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'];
					if (is_array($customOptions))	{
						foreach($customOptions as $coKey => $coValue) {
							if (is_array($coValue['items']))	{
									// Add header:
								$items[] = array(
									$GLOBALS['LANG']->sl($coValue['header']),
									'--div--',
								);

									// Traverse items:
								foreach($coValue['items'] as $itemKey => $itemCfg)	{
										// Icon:
									if ($itemCfg[1])	{
										list($icon) = $this->getIcon($itemCfg[1]);
										if ($icon)	$icon = '../'.TYPO3_mainDir.$icon;
									} else $icon = '';

										// Add item to be selected:
									$items[] = array(
										$GLOBALS['LANG']->sl($itemCfg[0]),
										$coKey.':'.ereg_replace('[:|,]','',$itemKey),
										$icon,
										$GLOBALS['LANG']->sl($itemCfg[2]),
									);
								}
							}
						}
					}
				break;
				case 'modListGroup':
				case 'modListUser':
					$loadModules = t3lib_div::makeInstance('t3lib_loadModules');
					$loadModules->load($GLOBALS['TBE_MODULES']);

					$modList = $this->fieldConfig['config']['special']=='modListUser' ? $loadModules->modListUser : $loadModules->modListGroup;
					if (is_array($modList))	{
						$descr = '';

						foreach($modList as $theMod)	{

								// Icon:
							$icon = $GLOBALS['LANG']->moduleLabels['tabs_images'][$theMod.'_tab'];
							if ($icon)	{
								$icon = '../'.substr($icon,strlen(PATH_site));
							}

								// Description texts:
							if ($this->edit_showFieldHelp)	{
								$descr = $GLOBALS['LANG']->moduleLabels['labels'][$theMod.'_tablabel'].
								  chr(10).
								  $GLOBALS['LANG']->moduleLabels['labels'][$theMod.'_tabdescr'];
							}

								// Item configuration:
							$items[] = array(
								$this->addSelectOptionsToItemArray_makeModuleData($theMod),
								$theMod,
								$icon,
								$descr
							);
						}
					}
				break;
			}
		}

			// Return the items:
		return $items;
	}

	/**
	 * Creates value/label pair for a backend module (main and sub)
	 *
	 * @param	string		The module key
	 * @return	string		The rawurlencoded 2-part string to transfer to interface
	 * @access private
	 * @see addSelectOptionsToItemArray()
	 */
	protected function addSelectOptionsToItemArray_makeModuleData($value) {
		$label = '';
			// Add label for main module:
		$pp = explode('_',$value);
		if (count($pp)>1)	$label.=$GLOBALS['LANG']->moduleLabels['tabs'][$pp[0].'_tab'].'>';
			// Add modules own label now:
		$label.= $GLOBALS['LANG']->moduleLabels['tabs'][$value.'_tab'];

		return $label;
	}

	/**
	 * Adds records from a foreign table (for selector boxes)
	 *
	 * @param	array		The array of items (label,value,icon)
	 * @param	array		TSconfig for the table/row
	 * @param	boolean		If set, then we are fetching the 'neg_' foreign tables.
	 * @return	array		The $items array modified.
	 * @see addSelectOptionsToItemArray(), t3lib_BEfunc::exec_foreign_table_where_query()
	 */
	protected function foreignTable($items, $TSconfig, $pFFlag=0) {
		global $TCA;

			// Init:
		$pF=$pFFlag?'neg_':'';
		$f_table = $this->fieldConfig['config'][$pF.'foreign_table'];
		$uidPre = $pFFlag?'-':'';

			// Get query:
		$res = t3lib_BEfunc::exec_foreign_table_where_query($this->fieldConfig, $this->field, $TSconfig, $pF);

			// Perform lookup
		if ($GLOBALS['TYPO3_DB']->sql_error())	{
			echo($GLOBALS['TYPO3_DB']->sql_error()."\n\nThis may indicate a table defined in tables.php is not existing in the database!");
			return array();
		}

			// Get label prefix.
		$lPrefix = $this->sL($this->fieldConfig['config'][$pF.'foreign_table_prefix']);

			// Get icon field + path if any:
		$iField = $TCA[$f_table]['ctrl']['selicon_field'];
		$iPath = trim($TCA[$f_table]['ctrl']['selicon_field_path']);

			// Traverse the selected rows to add them:
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			t3lib_BEfunc::workspaceOL($f_table, $row);

			if (is_array($row))	{
					// Prepare the icon if available:
				if ($iField && $iPath && $row[$iField])	{
					$iParts = t3lib_div::trimExplode(',',$row[$iField],1);
					$icon = '../'.$iPath.'/'.trim($iParts[0]);
				} elseif (t3lib_div::inList('singlebox,checkbox',$this->fieldConfig['config']['renderMode'])) {
					$icon = '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,t3lib_iconWorks::getIcon($f_table, $row),'',1);
				} else $icon = '';

					// Add the item:
				$items[] = array(
					$lPrefix . htmlspecialchars(t3lib_BEfunc::getRecordTitle($f_table, $row)),
					$uidPre . $row['uid'],
					$icon
				);
			}
		}
		return $items;
	}

	/**
	 * Extracting values from a value/label list (as made by transferData class)
	 *
	 * @param	string		Value string where values are comma separated, intermixed with labels and rawurlencoded (this is what is delivered to TCEforms normally!)
	 * @param	array		Values in an array
	 * @return	array		Input string exploded with comma and for each value only the label part is set in the array. Keys are numeric
	 */
	function extractValuesOnlyFromValueLabelList($itemFormElValue)	{
			// Get values of selected items:
		$itemArray = t3lib_div::trimExplode(',',$itemFormElValue,1);
		foreach($itemArray as $tk => $tv) {
			$tvP = explode('|',$tv,2);
			$tvP[0] = rawurldecode($tvP[0]);

			$itemArray[$tk] = $tvP[0];
		}
		return $itemArray;
	}
}

?>
