<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_SelectElement extends t3lib_TCEforms_AbstractElement {
	protected $item;

	public function render() {
		global $TCA;

			// Field configuration from TCA:
		$config = $this->fieldConfig['config'];

		$disabled = '';
		if($this->TCEformsObject->renderReadonly || $config['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

			// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		$specConf = $this->TCEformsObject->getSpecConfFromString($this->extra, $this->fieldConfig['defaultExtras']);

			// Getting the selector box items from the system
		$selItems = $this->TCEformsObject->addSelectOptionsToItemArray($this->TCEformsObject->initItemArray($this->fieldConfig),$this->fieldConfig,$this->TCEformsObject->setTSconfig($table,$row),$field);
		$selItems = $this->TCEformsObject->addItems($selItems,$this->fieldTSConfig['addItems.']);
		if ($config['itemsProcFunc']) $selItems = $this->TCEformsObject->procItems($selItems,$this->fieldTSConfig['itemsProcFunc.'],$config,$table,$row,$field);

			// Possibly remove some items:
		$removeItems = t3lib_div::trimExplode(',',$this->fieldTSConfig['removeItems'],1);
		foreach($selItems as $tk => $p)	{

				// Checking languages and authMode:
			$languageDeny = $TCA[$table]['ctrl']['languageField'] && !strcmp($TCA[$table]['ctrl']['languageField'], $field) && !$GLOBALS['BE_USER']->checkLanguageAccess($p[1]);
			$authModeDeny = $config['form_type']=='select' && $config['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode($table,$field,$p[1],$config['authMode']);
			if (in_array($p[1],$removeItems) || $languageDeny || $authModeDeny)	{
				unset($selItems[$tk]);
			} elseif (isset($this->fieldTSConfig['altLabels.'][$p[1]])) {
				$selItems[$tk][0]=$this->TCEformsObject->sL($this->fieldTSConfig['altLabels.'][$p[1]]);
			}

				// Removing doktypes with no access:
			if ($table.'.'.$field == 'pages.doktype')	{
				if (!($GLOBALS['BE_USER']->isAdmin() || t3lib_div::inList($GLOBALS['BE_USER']->groupData['pagetypes_select'],$p[1])))	{
					unset($selItems[$tk]);
				}
			}
		}

			// Creating the label for the "No Matching Value" entry.
		$nMV_label = isset($this->fieldTSConfig['noMatchingValue_label']) ? $this->TCEformsObject->sL($this->fieldTSConfig['noMatchingValue_label']) : '[ '.$this->TCEformsObject->getLL('l_noMatchingValue').' ]';

			// Prepare some values:
		$maxitems = intval($config['maxitems']);

			// If a SINGLE selector box...
		if ($maxitems<=1)	{
			$item = $this->initSubtypeSingle($table,$field,$row,$PA,$config,$selItems,$nMV_label);
		} elseif (!strcmp($config['renderMode'],'checkbox'))	{	// Checkbox renderMode
			$item = $this->initSubtypeCheckbox($table,$field,$row,$PA,$config,$selItems,$nMV_label);
		} elseif (!strcmp($config['renderMode'],'singlebox'))	{	// Single selector box renderMode
			$item = $this->initSubtypeSinglebox($table,$field,$row,$PA,$config,$selItems,$nMV_label);
		} else {	// Traditional multiple selector box:
			$item = $this->initSubtypeMultiple($table,$field,$row,$PA,$config,$selItems,$nMV_label);
		}

			// Wizards:
		if (!$disabled) {
			$altItem = '<input type="hidden" name="'.$this->itemFormElName.'" value="'.htmlspecialchars($this->itemFormElValue).'" />';
			$item = $this->TCEformsObject->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$this->itemFormElName,$specConf);
		}

		return $item;
	}

	protected function initSubtypeSingle($table,$field,$row,&$PA,$config,$selItems,$nMV_label) {
			// check against inline uniqueness
		$inlineParent = $this->TCEformsObject->inline->getStructureLevel(-1);
		if(is_array($inlineParent) && $inlineParent['uid']) {
			if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
				$uniqueIds = $this->TCEformsObject->inline->inlineData['unique'][$this->TCEformsObject->inline->inlineNames['object'].'['.$table.']']['used'];
				$this->fieldChangeFunc['inlineUnique'] = "inline.updateUnique(this,'".$this->TCEformsObject->inline->inlineNames['object'].'['.$table."]','".$this->TCEformsObject->inline->inlineNames['form']."','".$row['uid']."');";
			}
				// hide uid of parent record for symmetric relations
			if ($inlineParent['config']['foreign_table'] == $table && ($inlineParent['config']['foreign_field'] == $field || $inlineParent['config']['symmetric_field'] == $field)) {
				$uniqueIds[] = $inlineParent['uid'];
			}
		}

			// Initialization:
		$c = 0;
		$sI = 0;
		$noMatchingValue = 1;
		$opt = array();
		$selicons = array();
		$onlySelectedIconShown = 0;
		$size = intval($config['size']);
		$selectedStyle = ''; // Style set on <select/>

		$disabled = '';
		if($this->TCEformsObject->renderReadonly || $config['readOnly'])  {
			$disabled = ' disabled="disabled"';
			$onlySelectedIconShown = 1;
		}

			// Icon configuration:
		if ($config['suppress_icons']=='IF_VALUE_FALSE')	{
			$suppressIcons = !$this->itemFormElValue ? 1 : 0;
		} elseif ($config['suppress_icons']=='ONLY_SELECTED')	{
			$suppressIcons=0;
			$onlySelectedIconShown=1;
		} elseif ($config['suppress_icons']) 	{
			$suppressIcons = 1;
		} else $suppressIcons = 0;

			// Traverse the Array of selector box items:
		$optGroupStart = array();
		foreach($selItems as $p)	{
			$sM = (!strcmp($this->itemFormElValue,$p[1])?' selected="selected"':'');
			if ($sM)	{
				$sI = $c;
				$noMatchingValue = 0;
			}

				// Getting style attribute value (for icons):
			if ($config['iconsInOptionTags'])	{
				$styleAttrValue = $this->TCEformsObject->optionTagStyle($p[2]);
				if ($sM) {
					list($selectIconFile,$selectIconInfo) = $this->TCEformsObject->getIcon($p[2]);
						if (!empty($selectIconInfo)) {
							$selectedStyle = ' style="background-image: url('.$selectIconFile.'); background-repeat: no-repeat; background-position: 0% 50%; padding: 1px; padding-left: 24px; -webkit-background-size: 0;"';
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
							'>'.t3lib_div::deHSCentities(htmlspecialchars($p[0])).'</option>' . "\n";
				}
			}

				// If there is an icon for the selector box (rendered in table under)...:
			if ($p[2] && !$suppressIcons && (!$onlySelectedIconShown || $sM))	{
				list($selIconFile,$selIconInfo)=$this->TCEformsObject->getIcon($p[2]);
				$iOnClick = $this->TCEformsObject->elName($this->itemFormElName).'.selectedIndex='.$c.'; '.implode('',$this->fieldChangeFunc).$this->TCEformsObject->blur().'return false;';
				$selicons[]=array(
					(!$onlySelectedIconShown ? '<a href="#" onclick="'.htmlspecialchars($iOnClick).'">' : '').
					'<img src="'.$selIconFile.'" '.$selIconInfo[3].' vspace="2" border="0" title="'.htmlspecialchars($p[0]).'" alt="'.htmlspecialchars($p[0]).'" />'.
					(!$onlySelectedIconShown ? '</a>' : ''),
					$c,$sM);
			}
			$c++;
		}

		if($optGroupOpen) { // Closing optgroup if open
			$opt[]='</optgroup>';
			$optGroupOpen = false;
		}

			// No-matching-value:
		if ($this->itemFormElValue && $noMatchingValue && !$this->fieldTSConfig['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement'])	{
			$nMV_label = @sprintf($nMV_label, $this->itemFormElValue);
			$opt[]= '<option value="'.htmlspecialchars($this->itemFormElValue).'" selected="selected">'.htmlspecialchars($nMV_label).'</option>';
		}

			// Create item form fields:
		$sOnChange = 'if (this.options[this.selectedIndex].value==\'--div--\') {this.selectedIndex='.$sI.';} '.implode('',$this->fieldChangeFunc);
		if(!$disabled) {
			$item.= '<input type="hidden" name="'.$this->itemFormElName.'_selIconVal" value="'.htmlspecialchars($sI).'" />';	// MUST be inserted before the selector - else is the value of the hiddenfield here mysteriously submitted...
		}
		$item.= '<select'.$selectedStyle.' name="'.$this->itemFormElName.'"'.
					$this->TCEformsObject->insertDefStyle('select').
					($size?' size="'.$size.'"':'').
					' onchange="'.htmlspecialchars($sOnChange).'"'.
					$this->onFocus.$disabled.'>';
		$item.= implode('',$opt);
		$item.= '</select>';

			// Create icon table:
		if (count($selicons) && !$config['noIconsBelowSelect'])	{
			$item.='<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-selectIcons">';
			$selicon_cols = intval($config['selicon_cols']);
			if (!$selicon_cols)	$selicon_cols=count($selicons);
			$sR = ceil(count($selicons)/$selicon_cols);
			$selicons = array_pad($selicons,$sR*$selicon_cols,'');
			for($sa=0;$sa<$sR;$sa++)	{
				$item.='<tr>';
				for($sb=0;$sb<$selicon_cols;$sb++)	{
					$sk=($sa*$selicon_cols+$sb);
					$imgN = 'selIcon_'.$table.'_'.$row['uid'].'_'.$field.'_'.$selicons[$sk][1];
					$imgS = ($selicons[$sk][2]?$this->TCEformsObject->backPath.'gfx/content_selected.gif':'clear.gif');
					$item.='<td><img name="'.htmlspecialchars($imgN).'" src="'.$imgS.'" width="7" height="10" alt="" /></td>';
					$item.='<td>'.$selicons[$sk][0].'</td>';
				}
				$item.='</tr>';
			}
			$item.='</table>';
		}

		return $item;
	}

	protected function initSubtypeCheckbox($table,$field,$row,&$PA,$config,$selItems,$nMV_label) {

			// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->TCEformsObject->extractValuesOnlyFromValueLabelList($this->itemFormElValue));

		$disabled = '';
		if($this->TCEformsObject->renderReadonly || $config['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Traverse the Array of selector box items:
		$tRows = array();
		$c=0;
		if (!$disabled) {
			$sOnChange = implode('',$this->fieldChangeFunc);
			$setAll = array();	// Used to accumulate the JS needed to restore the original selection.
			foreach($selItems as $p)	{
					// Non-selectable element:
				if (!strcmp($p[1],'--div--'))	{
					if (count($setAll))	{
							$tRows[] = '
								<tr class="c-header-checkbox-controls">
									<td colspan="3">' .
										'<a href="#" onclick="' . htmlspecialchars(implode('', $setAll).' return false;') . '">' .
										htmlspecialchars($this->TCEformsObject->getLL('l_checkAll')) .
										'</a>
										<a href="#" onclick="' . htmlspecialchars(implode('', $unSetAll).' return false;').'">' .
										htmlspecialchars($this->TCEformsObject->getLL('l_uncheckAll')) .
										'</a>
									</td>
								</tr>';
							$setAll = array();
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
						list($selIconFile,$selIconInfo) = $this->TCEformsObject->getIcon($p[2]);
					}

						// Compile row:
					$rowId = uniqid('select_checkbox_row_');
					$onClickCell = $this->TCEformsObject->elName($this->itemFormElName . '[' . $c . ']') . '.checked=!' . $this->TCEformsObject->elName($this->itemFormElName . '[' . $c . ']') . '.checked;';
					$onClick = 'this.attributes.getNamedItem("class").nodeValue = ' . $this->TCEformsObject->elName($this->itemFormElName . '[' . $c . ']') . '.checked ? "c-selectedItem" : "c-unselectedItem";';
					$setAll[] = $this->TCEformsObject->elName($this->itemFormElName . '[' . $c . ']') . '.checked=1;';
					$setAll[] .= '$(\'' . $rowId . '\').removeClassName(\'c-unselectedItem\');$(\'' . $rowId . '\').addClassName(\'c-selectedItem\');';
					$unSetAll[] = $this->TCEformsObject->elName($this->itemFormElName.'['.$c.']').'.checked=0;';
					$unSetAll[] .= '$(\'' . $rowId . '\').removeClassName(\'c-selectedItem\');$(\'' . $rowId . '\').addClassName(\'c-unselectedItem\');';
					$restoreCmd[] = $this->TCEformsObject->elName($this->itemFormElName . '[' . $c . ']') . '.checked=' . ($sM ? 1 : 0) . ';' .
								'$(\'' . $rowId . '\').removeClassName(\'c-selectedItem\');$(\'' . $rowId . '\').removeClassName(\'c-unselectedItem\');' .
								'$(\'' . $rowId . '\').addClassName(\'c-' . ($sM ? '' : 'un') . 'selectedItem\');';

					$hasHelp = ($p[3] !='');

					$label = t3lib_div::deHSCentities(htmlspecialchars($p[0]));
					$help = $hasHelp ? '<span class="typo3-csh-inline show-right"><span class="header">' . $label . '</span>' .
						'<span class="paragraph">' . $GLOBALS['LANG']->hscAndCharConv(nl2br(trim(htmlspecialchars($p[3]))), false) . '</span></span>' : '';

					if ($hasHelp && $this->TCEformsObject->edit_showFieldHelp == 'icon') {
						$helpIcon  = '<a class="typo3-csh-link" href="#">';
						$helpIcon .= '<img' . t3lib_iconWorks::skinImg($this->TCEformsObject->backPath, 'gfx/helpbubble.gif', 'width="14" height="14"');
						$helpIcon .= ' hspace="2" border="0" class="absmiddle"' . ($GLOBALS['CLIENT']['FORMSTYLE'] ? ' style="cursor:help;"' : '') . ' alt="" />' . $help;
						$helpIcon .= '</a>';
						$help = $helpIcon;
					}

					$tRows[] = '
						<tr id="' . $rowId . '" class="'.($sM ? 'c-selectedItem' : 'c-unselectedItem').'" onclick="'.htmlspecialchars($onClick).'" style="cursor: pointer;">
							<td width="12"><input type="checkbox"'.$this->TCEformsObject->insertDefStyle('check').' name="'.htmlspecialchars($this->itemFormElName.'['.$c.']').'" value="'.htmlspecialchars($p[1]).'"'.$sM.' onclick="'.htmlspecialchars($sOnChange).'"'.$this->onFocus.' /></td>
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
								htmlspecialchars($this->TCEformsObject->getLL('l_checkAll')) .
								'</a>
								<a href="#" onclick="' . htmlspecialchars(implode('', $unSetAll).' return false;') . '">' .
								htmlspecialchars($this->TCEformsObject->getLL('l_uncheckAll')) .
								'</a>
							</td>
						</tr>';
			}
		}

			// Remaining values (invalid):
		if (count($itemArray) && !$this->fieldTSConfig['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement'])	{
			foreach($itemArray as $theNoMatchValue => $temp)	{
					// Compile <checkboxes> tag:
				array_unshift($tRows,'
						<tr class="c-invalidItem">
							<td><input type="checkbox"'.$this->TCEformsObject->insertDefStyle('check').' name="'.htmlspecialchars($this->itemFormElName.'['.$c.']').'" value="'.htmlspecialchars($theNoMatchValue).'" checked="checked" onclick="'.htmlspecialchars($sOnChange).'"'.$this->onFocus.$disabled.' /></td>
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
				'<img'.t3lib_iconWorks::skinImg($this->TCEformsObject->backPath,'gfx/undo.gif','width="13" height="12"') . ' title="' .
				htmlspecialchars($this->TCEformsObject->getLL('l_revertSelection')) . '" alt="" />' .'</a>';
		}
 			// Implode rows in table:
		$item .= '
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-select-checkbox">'.
				implode('',$tRows).'
			</table>
			';

		return $item;
	}

	protected function initSubtypeSinglebox($table,$field,$row,&$PA,$config,$selItems,$nMV_label) {
			// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->TCEformsObject->extractValuesOnlyFromValueLabelList($this->itemFormElValue));

		$disabled = '';
		if($this->TCEformsObject->renderReadonly || $config['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Traverse the Array of selector box items:
		$opt = array();
		$restoreCmd = array();	// Used to accumulate the JS needed to restore the original selection.
		$c = 0;
		foreach($selItems as $p)	{
				// Selected or not by default:
			$sM = '';
			if (isset($itemArray[$p[1]]))	{
				$sM = ' selected="selected"';
				$restoreCmd[] = $this->TCEformsObject->elName($this->itemFormElName.'[]').'.options['.$c.'].selected=1;';
				unset($itemArray[$p[1]]);
			}

				// Non-selectable element:
			$nonSel = '';
			if (!strcmp($p[1],'--div--'))	{
				$nonSel = ' onclick="this.selected=0;" class="c-divider"';
			}

				// Icon style for option tag:
			if ($config['iconsInOptionTags']) {
				$styleAttrValue = $this->TCEformsObject->optionTagStyle($p[2]);
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
		if (count($itemArray) && !$this->fieldTSConfig['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement'])	{
			foreach($itemArray as $theNoMatchValue => $temp)	{
					// Compile <option> tag:
				array_unshift($opt,'<option value="'.htmlspecialchars($theNoMatchValue).'" selected="selected">'.t3lib_div::deHSCentities(htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue))).'</option>');
			}
		}

			// Compile selector box:
		$sOnChange = implode('',$this->fieldChangeFunc);
		$selector_itemListStyle = isset($config['itemListStyle']) ? ' style="'.htmlspecialchars($config['itemListStyle']).'"' : ' style="'.$this->TCEformsObject->defaultMultipleSelectorStyle.'"';
		$size = intval($config['size']);
		$size = $config['autoSizeMax'] ? t3lib_div::intInRange(count($selItems)+1,t3lib_div::intInRange($size,1),$config['autoSizeMax']) : $size;
		$selectBox = '<select name="'.$this->itemFormElName.'[]"'.
						$this->TCEformsObject->insertDefStyle('select').
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
						htmlspecialchars($this->TCEformsObject->getLL('l_holdDownCTRL')).
						'</em>
					</td>
					<td valign="top">
					<a href="#" onclick="'.htmlspecialchars($this->TCEformsObject->elName($this->itemFormElName.'[]').'.selectedIndex=-1;'.implode('',$restoreCmd).' return false;').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->TCEformsObject->backPath,'gfx/undo.gif','width="13" height="12"').' title="'.htmlspecialchars($this->TCEformsObject->getLL('l_revertSelection')).'" alt="" />'.
						'</a>
					</td>
				</tr>
			</table>
				';

		return $item;
	}

	protected function initSubtypeMultiple($table,$field,$row,&$PA,$config,$selItems,$nMV_label)	{

		$disabled = '';
		if($this->TCEformsObject->renderReadonly || $config['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Setting this hidden field (as a flag that JavaScript can read out)
		if (!$disabled) {
			$item.= '<input type="hidden" name="'.$this->itemFormElName.'_mul" value="'.($config['multiple']?1:0).'" />';
		}

			// Set max and min items:
		$maxitems = t3lib_div::intInRange($config['maxitems'],0);
		if (!$maxitems)	$maxitems=100000;
		$minitems = t3lib_div::intInRange($config['minitems'],0);

			// Register the required number of elements:
		$this->TCEformsObject->registerRequiredPropertyExternal('range', $this->itemFormElName, array($minitems,$maxitems,'imgName'=>$table.'_'.$row['uid'].'_'.$field));

			// Get "removeItems":
		$removeItems = t3lib_div::trimExplode(',',$this->fieldTSConfig['removeItems'],1);

			// Perform modification of the selected items array:
		$itemArray = t3lib_div::trimExplode(',',$this->itemFormElValue,1);
		foreach($itemArray as $tk => $tv) {
			$tvP = explode('|',$tv,2);
			$evalValue = rawurldecode($tvP[0]);
			$isRemoved = in_array($evalValue,$removeItems)  || ($config['form_type']=='select' && $config['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode($table,$field,$evalValue,$config['authMode']));
			if ($isRemoved && !$this->fieldTSConfig['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement'])	{
				$tvP[1] = rawurlencode(@sprintf($nMV_label, $evalValue));
			} elseif (isset($this->fieldTSConfig['altLabels.'][$evalValue])) {
				$tvP[1] = rawurlencode($this->TCEformsObject->sL($this->fieldTSConfig['altLabels.'][$evalValue]));
			}
			$itemArray[$tk] = implode('|',$tvP);
		}
		$itemsToSelect = '';

		if(!$disabled) {
				// Create option tags:
			$opt = array();
			$styleAttrValue = '';
			foreach($selItems as $p)	{
				if ($config['iconsInOptionTags'])	{
					$styleAttrValue = $this->TCEformsObject->optionTagStyle($p[2]);
				}
				$opt[]= '<option value="'.htmlspecialchars($p[1]).'"'.
								($styleAttrValue ? ' style="'.htmlspecialchars($styleAttrValue).'"' : '').
								'>'.htmlspecialchars($p[0]).'</option>';
			}

				// Put together the selector box:
			$selector_itemListStyle = isset($config['itemListStyle']) ? ' style="'.htmlspecialchars($config['itemListStyle']).'"' : ' style="'.$this->TCEformsObject->defaultMultipleSelectorStyle.'"';
			$size = intval($config['size']);
			$size = $config['autoSizeMax'] ? t3lib_div::intInRange(count($itemArray)+1,t3lib_div::intInRange($size,1),$config['autoSizeMax']) : $size;
			if ($config['exclusiveKeys'])	{
				$sOnChange = 'setFormValueFromBrowseWin(\''.$this->itemFormElName.'\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text,\''.$config['exclusiveKeys'].'\'); ';
			} else {
				$sOnChange = 'setFormValueFromBrowseWin(\''.$this->itemFormElName.'\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text); ';
			}
			$sOnChange .= implode('',$this->fieldChangeFunc);
			$itemsToSelect = '
				<select name="'.$this->itemFormElName.'_sel"'.
							$this->TCEformsObject->insertDefStyle('select').
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
			'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
			'style' => isset($config['selectedListStyle']) ? ' style="'.htmlspecialchars($config['selectedListStyle']).'"' : ' style="'.$this->TCEformsObject->defaultMultipleSelectorStyle.'"',
			'dontShowMoveIcons' => ($maxitems<=1),
			'maxitems' => $maxitems,
			'info' => '',
			'headers' => array(
				'selector' => $this->TCEformsObject->getLL('l_selected').':<br />',
				'items' => $this->TCEformsObject->getLL('l_items').':<br />'
			),
			'noBrowser' => 1,
			'thumbnails' => $itemsToSelect,
			'readOnly' => $disabled
		);
		$item .= $this->TCEformsObject->dbFileIcons($this->itemFormElName,'','',$itemArray,'',$params,$this->onFocus);

		return $item;
	}
}

?>