<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractform.php');


class t3lib_TCEforms_Flexform extends t3lib_TCEforms_AbstractForm {
	/**
	 * @var array  The list of fields to display, with their configuration
	 */
	protected $fieldList;

	/**
	 * @var array  The palettes for this form
	 */
	protected $palettes;

	public $PA;

	protected $dataStructureArray;


	public function __construct($table, $record, $parentFieldConfig) {
			// Data Structure:
		$this->dataStructureArray = t3lib_BEfunc::getFlexFormDS($parentFieldConfig, $record , $table);

		parent::__construct($table, $record);
	}


	/**
	 * Creates the language menu for FlexForms:
	 *
	 * @param	[type]		$languages: ...
	 * @param	[type]		$elName: ...
	 * @param	[type]		$selectedLanguage: ...
	 * @param	[type]		$multi: ...
	 * @return	string		HTML for menu
	 */
	protected function getLanguageMenu($languages,$elName,$selectedLanguage,$multi=1) {
		$opt=array();
		foreach($languages as $lArr) {
			$opt[]='<option value="'.htmlspecialchars($lArr['ISOcode']).'"'.(in_array($lArr['ISOcode'],$selectedLanguage)?' selected="selected"':'').'>'.htmlspecialchars($lArr['title']).'</option>';
		}

		$output = '<select name="'.$elName.'[]"'.($multi ? ' multiple="multiple" size="'.count($languages).'"' : '').'>'.implode('',$opt).'</select>';

		return $output;
	}

	/**
	 * Creates the menu for selection of the sheets:
	 *
	 * @param	array		Sheet array for which to render the menu
	 * @param	string		Form element name of the field containing the sheet pointer
	 * @param	string		Current sheet key
	 * @return	string		HTML for menu
	 */
	function createSheetMenu($sArr,$elName,$sheetKey) {

		$tCells =array();
		$pct = round(100/count($sArr));
		foreach($sArr as $sKey => $sheetCfg) {
			if ($GLOBALS['BE_USER']->jsConfirmation(1)) {
				$onClick = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){'.$this->TCEformsObject->elName($elName).".value='".$sKey."'; TBE_EDITOR.submitForm()};";
			} else {
				$onClick = 'if(TBE_EDITOR.checkSubmit(-1)){ '.$this->TCEformsObject->elName($elName).".value='".$sKey."'; TBE_EDITOR.submitForm();}";
			}


			$tCells[]='<td width="'.$pct.'%" style="'.($sKey==$sheetKey ? 'background-color: #9999cc; font-weight: bold;' : 'background-color: #aaaaaa;').' cursor: hand;" onclick="'.htmlspecialchars($onClick).'" align="center">'.
					($sheetCfg['ROOT']['TCEforms']['sheetTitle'] ? $this->TCEformsObject->sL($sheetCfg['ROOT']['TCEforms']['sheetTitle']) : $sKey).
					'</td>';
		}

		return '<table border="0" cellpadding="0" cellspacing="2" class="typo3-TCEforms-flexForm-sheetMenu"><tr>'.implode('',$tCells).'</tr></table>';
	}

	// Code copied from t3lib_tceforms::getSingleField_typeFlex_draw()
	public function render() {
			// Get data structure:
		if (is_array($this->dataStructureArray)) {
			/*$this->formObject = new t3lib_TCEforms_Flexform($this->table, $this->record, $this->dataStructureArray);
			$this->formObject->setTCEformsObject($this->TCEformsObject);*/

				// Get data:
			$xmlData = $this->record['pi_flexform'];
			$xmlHeaderAttributes = t3lib_div::xmlGetHeaderAttribs($xmlData);
			$storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
			if ($storeInCharset) {
				$currentCharset = $GLOBALS['LANG']->charSet;
				$xmlData = $GLOBALS['LANG']->csConvObj->conv($xmlData, $storeInCharset, $currentCharset,1);
			}
			$editData=t3lib_div::xml2array($xmlData);
			if (!is_array($editData)) { // Must be XML parsing error...
				$editData=array();
			} elseif (!isset($editData['meta']) || !is_array($editData['meta'])) {
				$editData['meta'] = array();
			}

				// Find the data structure if sheets are found:
			$sheet = $editData['meta']['currentSheetId'] ? $editData['meta']['currentSheetId'] : 'sDEF';	// Sheet to display

				// Create sheet menu:
//			if (is_array($this->dataStructureArray['sheets'])) {
//				#$item.=$this->getSingleField_typeFlex_sheetMenu($this->dataStructureArray['sheets'], $this->itemFormElName.'[meta][currentSheetId]', $sheet).'<br />';
//			}

				// Create language menu:
			$langChildren = $this->dataStructureArray['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $this->dataStructureArray['meta']['langDisable'] ? 1 : 0;

			$editData['meta']['currentLangId']=array();
			$languages = $this->TCEformsObject->getAvailableLanguages();

			foreach($languages as $lInfo) {
				if ($GLOBALS['BE_USER']->checkLanguageAccess($lInfo['uid'])) {
					$editData['meta']['currentLangId'][] = 	$lInfo['ISOcode'];
				}
			}
			if (!is_array($editData['meta']['currentLangId']) || !count($editData['meta']['currentLangId'])) {
				$editData['meta']['currentLangId']=array('DEF');
			}

			$editData['meta']['currentLangId'] = array_unique($editData['meta']['currentLangId']);


//			if (!$langDisabled && count($languages) > 1) {
//				$item.=$this->getLanguageMenu($languages, $this->itemFormElName.'[meta][currentLangId]', $editData['meta']['currentLangId']).'<br />';
//			}

			$this->PA['_noEditDEF'] = FALSE;
			if ($langChildren || $langDisabled) {
				$rotateLang = array('DEF');
			} else {
				if (!in_array('DEF',$editData['meta']['currentLangId'])) {
					array_unshift($editData['meta']['currentLangId'],'DEF');
					$this->PA['_noEditDEF'] = TRUE;
				}
				$rotateLang = $editData['meta']['currentLangId'];
			}

				// Tabs sheets
			if (is_array($this->dataStructureArray['sheets'])) {
				$tabsToTraverse = array_keys($this->dataStructureArray['sheets']);
			} else {
				$tabsToTraverse = array($sheet);
			}

			foreach ($rotateLang as $lKey) {
				if (!$langChildren && !$langDisabled) {
					$item.= '<b>'.$this->TCEformsObject->getLanguageIcon($this->table,$this->record,'v'.$lKey).$lKey.':</b>';
				}
				$sheetIdentString = 'TCEFORMS:flexform:'.$this->itemFormElName.$lKey;
				$sheetIdentStringMD5 = $GLOBALS['TBE_TEMPLATE']->getDynTabMenuId($sheetIdentString);

				$tabParts = array();
				foreach ($tabsToTraverse as $sheet) {
					list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($this->dataStructureArray,$sheet);

						// Render sheet:
					if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el']))	 {
						$lang = 'l'.$lKey;	// Default language, other options are "lUK" or whatever country code (independant of system!!!)
						// TODO: hand these over as single parameters, not in PA - and especially not in a global PA!
						$this->PA['_valLang'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : 'DEF';	// Default language, other options are "lUK" or whatever country code (independant of system!!!)
						$this->PA['_lang'] = $lang;
						$this->PA['_cshFile'] = ((isset($dataStruct['ROOT']['TCEforms']) && isset($dataStruct['ROOT']['TCEforms']['cshFile'])) ? $dataStruct['ROOT']['TCEforms']['cshFile'] : '');

						$sheetObject = $this->createSheetObject($tabIdentStringMD5.'-'.$sheetCounter, $dataStruct['ROOT']['TCEforms']['sheetTitle']);
						$this->currentSheet = $sheetObject;
//print_r($editData['data'][$sheet][$lang])."\n\n\n=======================================\n\n\n";
							// Render flexform:
						$tRows = $this->resolveDatastructureIntoObjects(
									$dataStruct['ROOT']['el'],
									$editData['data'][$sheet][$lang],
									'[data]['.$sheet.']['.$lang.']'
								);
						#$sheetContent= '<table border="0" cellpadding="1" cellspacing="1" class="typo3-TCEforms-flexForm">'.implode('',$tRows).'</table>';
						$sheetContent = $sheetObject->render();
						$sheetContent = '<div class="typo3-TCEforms-flexForm">'.$sheetContent.'</div>';

			#			$item = '<div style=" position:absolute;">'.$item.'</div>';
						//visibility:hidden;
					} else $sheetContent='Data Structure ERROR: No ROOT element found for sheet "'.$sheet.'".';

						// Add to tab:
					$tabParts[] = array(
						'label' => ($dataStruct['ROOT']['TCEforms']['sheetTitle'] ? $this->TCEformsObject->sL($dataStruct['ROOT']['TCEforms']['sheetTitle']) : $sheet),
						'description' => ($dataStruct['ROOT']['TCEforms']['sheetDescription'] ? $this->TCEformsObject->sL($dataStruct['ROOT']['TCEforms']['sheetDescription']) : ''),
						'linkTitle' => ($dataStruct['ROOT']['TCEforms']['sheetShortDescr'] ? $this->TCEformsObject->sL($dataStruct['ROOT']['TCEforms']['sheetShortDescr']) : ''),
						'content' => $sheetContent
					);
				}

				if (is_array($this->dataStructureArray['sheets'])) {
					$dividersToTabsBehaviour = (isset($this->tableTCAConfig['ctrl']['dividers2tabs']) ? $this->tableTCAConfig['ctrl']['dividers2tabs'] : 1);
					$item.= $this->TCEformsObject->getDynTabMenu($tabParts, 'TCEFORMS:flexform:'.$this->itemFormElName.$this->PA['_lang'], $dividersToTabsBehaviour);
				} else {
					$item.= $sheetContent;
				}
			}
		} else $item='Data Structure ERROR: '.$this->dataStructureArray;

		return $item;
	}


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



	/**
	 * Recursive rendering of flexforms
	 *
	 * @param	array		(part of) Data Structure for which to render. Keys on first level is flex-form fields
	 * @param	array		(part of) Data array of flexform corresponding to the input DS. Keys on first level is flex-form field names
	 * @param	array		Array of standard information for rendering of a form field in TCEforms, see other rendering functions too
	 * @param	string		Form field prefix, eg. "[data][sDEF][lDEF][...][...]"
	 * @param	integer		Indicates nesting level for the function call
	 * @param	string		Prefix for ID-values
	 * @param	boolean		Defines whether the next flexform level is open or closed. Comes from _TOGGLE pseudo field in FlexForm xml.
	 * @return	string		HTMl code for form.
	 */
	public function resolveDatastructureIntoObjects($dataStructArray,$editData,$formPrefix='',$level=0,$idPrefix='ID',$toggleClosed=FALSE) {

		if (is_array($this->PA['_valLang']))	{
			$rotateLang = $this->PA['_valLang'];
		} else {
			$rotateLang = array($this->PA['_valLang']);
		}

		$tRows = array();
		foreach($rotateLang as $vDEFkey)	{
			$vDEFkey = 'v'.$vDEFkey;


			foreach ($dataStructArray as $itemKey => $itemDefinition) {
				if (!$itemDefinition['TCEforms']['displayCond'] || $this->isDisplayCondition($itemDefinition['TCEforms']['displayCond'],$editData,$vDEFkey)) {
					$fakePA=array();
					// TODO: find a better way for this - there might be name collisions between real fields in the table and "faked" fields in the flexform!
					$this->tableTCAconfig['columns'][$itemKey]=array(
						'label' => $this->sL(trim($itemDefinition['TCEforms']['label'])),
						'config' => $itemDefinition['TCEforms']['config'],
						'defaultExtras' => $itemDefinition['TCEforms']['defaultExtras'],
						'onChange' => $itemDefinition['TCEforms']['onChange']
					);
					if ($this->PA['_noEditDEF'] && $this->PA['_lang']==='lDEF') {
						$fakePA['fieldConf']['config'] = array(
							'type' => 'none',
							'rows' => 2
						);
					}

					if (
						$fakePA['fieldConf']['onChange'] == 'reload' ||
						($GLOBALS['TCA'][$this->table]['ctrl']['type'] && !strcmp($itemKey, $GLOBALS['TCA'][$this->table]['ctrl']['type'])) ||
						($GLOBALS['TCA'][$this->table]['ctrl']['requestUpdate'] && t3lib_div::inList($GLOBALS['TCA'][$this->table]['ctrl']['requestUpdate'], $itemKey))) {
						if ($GLOBALS['BE_USER']->jsConfirmation(1))	{
							$alertMsgOnChange = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
						} else {
							$alertMsgOnChange = 'if(TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm();}';
						}
					} else {
						$alertMsgOnChange = '';
					}

					$fakePA['fieldChangeFunc']=$this->PA['fieldChangeFunc'];
					if (strlen($alertMsgOnChange)) {
						$fakePA['fieldChangeFunc']['alert']=$alertMsgOnChange;
					}
					$fakePA['onFocus']=$this->PA['onFocus'];
					$fakePA['label']=$this->PA['label'];

					$fakePA['itemFormElName']=$this->formFieldNamePrefix.$this->PA['itemFormElName'].$formPrefix.'['.$itemKey.']['.$vDEFkey.']';
					$fakePA['itemFormElName_file']=$this->formFieldNamePrefix.$this->PA['itemFormElName_file'].$formPrefix.'['.$itemKey.']['.$vDEFkey.']';

					if(isset($editData[$itemKey][$vDEFkey])) {
						$fakePA['itemFormElValue']=$editData[$itemKey][$vDEFkey];
					} else {
						$fakePA['itemFormElValue']=$fakePA['fieldConf']['config']['default'];
					}

					$theFormEl = $this->getSingleField($itemKey);
					$theFormEl->setItemFormElementName($fakePA['itemFormElName']);
					$theFormEl->setItemFormElementValue($fakePA['itemFormElValue']);
					$theFormEl->set_TCEformsObject($this->parentFormObject);
					$theTitle = htmlspecialchars($fakePA['fieldConf']['label']);

					if (!in_array('DEF',$rotateLang))	{
						//$defInfo = '<div class="typo3-TCEforms-originalLanguageValue">'.$this->getLanguageIcon($this->table,$this->record,0).$this->previewFieldValue($editData[$itemKey]['vDEF'], $fakePA['fieldConf']).'&nbsp;</div>';
					} else {
						$defInfo = '';
					}

					if (!$this->PA['_noEditDEF'])	{
						$prLang = $this->getAdditionalPreviewLanguages();
						foreach($prLang as $prL)	{
							//$defInfo.= '<div class="typo3-TCEforms-originalLanguageValue">'.$this->getLanguageIcon($this->table,$this->record,'v'.$prL['ISOcode']).$this->previewFieldValue($editData[$itemKey]['v'.$prL['ISOcode']], $fakePA['fieldConf']).'&nbsp;</div>';
						}
					}

						// Put row together
						// possible linebreaks in the label through xml: \n => <br/>, usage of nl2br() not possible, so it's done through str_replace
					$processedTitle = str_replace('\n', '<br />', $theTitle);
					$helpText = $this->helpText($ky, $processedTitle, $this->PA['_cshFile']);
					$tRows[]='<div>' .
						'<div class="bgColor5">' .
						($helpText ?
							($vDEFkey=='vDEF' ? '' : $this->getLanguageIcon($this->table, $this->record, $vDEFkey)) . '<strong>' . $processedTitle . '</strong>' . $helpText :
							$this->helpTextIcon($itemKey, $processedTitle, $this->PA['_cshFile']) . ($vDEFkey == 'vDEF' ? '' : $this->getLanguageIcon($this->table, $this->record, $vDEFkey)) . $processedTitle
						) .
						'</div>
						<div class="bgColor4">'.$theFormEl->render().$defInfo.$this->renderVDEFDiff($editData[$itemKey],$vDEFkey).'</div>
					</div>';
				}
			}
		}
		if (count($tRows))	$output.= implode('',$tRows);

		return $output;
	}


	/**
	 * Returns help-text ICON if configured for.
	 *
	 * @param	string		Field name
	 * @param	string		Field title
	 * @param	string		File name with CSH labels
	 * @return	string		HTML, <a>-tag with
	 */
	function helpTextIcon($field, $fieldTitle, $cshFile) {
		if ($this->globalShowHelp && $cshFile) {
			$value = $GLOBALS['LANG']->sL($cshFile . ':' . $field . '.description');
			if (trim($value)) {
				if (substr($fieldTitle, -1, 1) == ':') {
					$fieldTitle = substr($fieldTitle, 0, strlen($fieldTitle) - 1);
				}
				// CSH exists
				$params = base64_encode(serialize(array(
					'cshFile' => $cshFile,
					'field' => $field,
					'title' => $fieldTitle
				)));
				$aOnClick = 'vHWin=window.open(\''.$this->backPath.'view_help.php?ffID=' . $params . '\',\'viewFieldHelp\',\'height=400,width=600,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
				return '<a href="#" class="typo3-csh-link" onclick="'.htmlspecialchars($aOnClick).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/helpbubble.gif','width="14" height="14"').' hspace="2" border="0" class="absmiddle"'.($GLOBALS['CLIENT']['FORMSTYLE']?' style="cursor:help;"':'').' alt="" />'.
						'</a>';
			}
		}
		return '';
	}

	/**
	 * Returns help text DESCRIPTION, if configured for.
	 *
	 * @param	string		Field name
	 * @param	string		CSH file name
	 * @return	string		Description for the field with cion or empty string
	 */
	function helpText($field, $fieldTitle, $cshFile) {
		if ($this->globalShowHelp && $cshFile && $this->edit_showFieldHelp == 'text') {
			$value = $GLOBALS['LANG']->sL($cshFile . ':' . $field . '.description');
			if (trim($value)) {
				return '<table border="0" cellpadding="2" cellspacing="0" width="90%"><tr><td valign="top" width="14">' .
					$this->helpTextIcon(
						$field,
						$fieldTitle,
						$cshFile
					).
					'</td><td valign="top"><span class="typo3-TCEforms-helpText-flexform">' .
					$GLOBALS['LANG']->hscAndCharConv(strip_tags($value), 1) .
					'</span></td></tr></table>';
			}
		}
		return '';
	}

	/**
	 * Renders the diff-view of vDEF fields in flexforms
	 *
	 * @param	string		Table name of the record being edited
	 * @param	string		Field name represented by $item
	 * @param	array		Record array of the record being edited
	 * @param	string		HTML of the form field. This is what we add the content to.
	 * @return	string		Item string returned again, possibly with the original value added to.
	 * @see getSingleField(), registerDefaultLanguageData()
	 */
	function renderVDEFDiff($vArray,$vDEFkey)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] && isset($vArray[$vDEFkey.'.vDEFbase']) && strcmp($vArray[$vDEFkey.'.vDEFbase'],$vArray['vDEF']))	{

				// Create diff-result:
			$t3lib_diff_Obj = t3lib_div::makeInstance('t3lib_diff');
			$diffres = $t3lib_diff_Obj->makeDiffDisplay($vArray[$vDEFkey.'.vDEFbase'],$vArray['vDEF']);

			$item.='<div class="typo3-TCEforms-diffBox">'.
				'<div class="typo3-TCEforms-diffBox-header">'.htmlspecialchars($this->getLL('l_changeInOrig')).':</div>'.
				$diffres.
			'</div>';
		}

		return $item;
	}

	/**
	 * Returns true, if the evaluation of the required-field code is OK.
	 *
	 * @param	string		The required-field code
	 * @param	array		The record to evaluate
	 * @param	string		FlexForm value key, eg. vDEF
	 * @return	boolean
	 */
	// TODO: find a way to use this from a centralized place (static function in AbstractForm?)
	function isDisplayCondition($displayCond,$row,$ffValueKey='')	{
		$output = FALSE;

		$parts = explode(':',$displayCond);
		switch((string)$parts[0])	{	// Type of condition:
			case 'FIELD':
				$theFieldValue = $ffValueKey ? $row[$parts[1]][$ffValueKey] : $row[$parts[1]];

				switch((string)$parts[2])	{
					case 'REQ':
						if (strtolower($parts[3])=='true')	{
							$output = $theFieldValue ? TRUE : FALSE;
						} elseif (strtolower($parts[3])=='false') {
							$output = !$theFieldValue ? TRUE : FALSE;
						}
					break;
					case '>':
						$output = $theFieldValue > $parts[3];
					break;
					case '<':
						$output = $theFieldValue < $parts[3];
					break;
					case '>=':
						$output = $theFieldValue >= $parts[3];
					break;
					case '<=':
						$output = $theFieldValue <= $parts[3];
					break;
					case '-':
					case '!-':
						$cmpParts = explode('-',$parts[3]);
						$output = $theFieldValue >= $cmpParts[0] && $theFieldValue <= $cmpParts[1];
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
					case 'IN':
					case '!IN':
						$output = t3lib_div::inList($parts[3],$theFieldValue);
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
					case '=':
					case '!=':
						$output = t3lib_div::inList($parts[3],$theFieldValue);
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
				}
			break;
			case 'EXT':
				switch((string)$parts[2])	{
					case 'LOADED':
						if (strtolower($parts[3])=='true')	{
							$output = t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						} elseif (strtolower($parts[3])=='false') {
							$output = !t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'REC':
				switch((string)$parts[1])	{
					case 'NEW':
						if (strtolower($parts[2])=='true')	{
							$output = !(intval($row['uid']) > 0) ? TRUE : FALSE;
						} elseif (strtolower($parts[2])=='false') {
							$output = (intval($row['uid']) > 0) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'HIDE_L10N_SIBLINGS':
				if ($ffValueKey==='vDEF')	{
					$output = TRUE;
				} elseif ($parts[1]==='except_admin' && $GLOBALS['BE_USER']->isAdmin())	{
					$output = TRUE;
				}
			break;
			case 'HIDE_FOR_NON_ADMINS':
				$output = $GLOBALS['BE_USER']->isAdmin() ? TRUE : FALSE;
			break;
			case 'VERSION':
				switch((string)$parts[1])	{
					case 'IS':
						if (strtolower($parts[2])=='true')	{
							$output = intval($row['pid'])==-1 ? TRUE : FALSE;
						} elseif (strtolower($parts[2])=='false') {
							$output = !(intval($row['pid'])==-1) ? TRUE : FALSE;
						}
					break;
				}
			break;
		}

		return $output;
	}

	protected function elementObjectFactory($type) {
		$elementObject = parent::elementObjectFactory($type);

		$this->currentSheet->addChildObject($elementObject);

		return $elementObject;
	}
}

?>