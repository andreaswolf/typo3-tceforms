<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_FlexElement extends t3lib_TCEforms_AbstractElement {
	protected $item;

	public function render() {

			// Data Structure:
		$dataStructArray = t3lib_BEfunc::getFlexFormDS($PA['fieldConf']['config'],$row,$table);

			// Get data structure:
		if (is_array($dataStructArray))	{

				// Get data:
			$xmlData = $PA['itemFormElValue'];
			$xmlHeaderAttributes = t3lib_div::xmlGetHeaderAttribs($xmlData);
			$storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
			if ($storeInCharset)	{
				$currentCharset=$GLOBALS['LANG']->charSet;
				$xmlData = $GLOBALS['LANG']->csConvObj->conv($xmlData,$storeInCharset,$currentCharset,1);
			}
			$editData=t3lib_div::xml2array($xmlData);
			if (!is_array($editData))	{	// Must be XML parsing error...
				$editData=array();
			} elseif (!isset($editData['meta']) || !is_array($editData['meta']))	{
			    $editData['meta'] = array();
			}

				// Find the data structure if sheets are found:
			$sheet = $editData['meta']['currentSheetId'] ? $editData['meta']['currentSheetId'] : 'sDEF';	// Sheet to display

				// Create sheet menu:
//			if (is_array($dataStructArray['sheets']))	{
//				#$item.=$this->getSingleField_typeFlex_sheetMenu($dataStructArray['sheets'], $PA['itemFormElName'].'[meta][currentSheetId]', $sheet).'<br />';
//			}

				// Create language menu:
			$langChildren = $dataStructArray['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $dataStructArray['meta']['langDisable'] ? 1 : 0;

			$editData['meta']['currentLangId']=array();
			$languages = $this->TCEformsObject->getAvailableLanguages();

			foreach($languages as $lInfo)	{
				if ($GLOBALS['BE_USER']->checkLanguageAccess($lInfo['uid']))	{
					$editData['meta']['currentLangId'][] = 	$lInfo['ISOcode'];
				}
			}
			if (!is_array($editData['meta']['currentLangId']) || !count($editData['meta']['currentLangId']))	{
				$editData['meta']['currentLangId']=array('DEF');
			}

			$editData['meta']['currentLangId'] = array_unique($editData['meta']['currentLangId']);


//			if (!$langDisabled && count($languages) > 1)	{
//				$item.=$this->getLanguageMenu($languages, $PA['itemFormElName'].'[meta][currentLangId]', $editData['meta']['currentLangId']).'<br />';
//			}

			$PA['_noEditDEF'] = FALSE;
			if ($langChildren || $langDisabled)	{
				$rotateLang = array('DEF');
			} else {
				if (!in_array('DEF',$editData['meta']['currentLangId']))	{
					array_unshift($editData['meta']['currentLangId'],'DEF');
					$PA['_noEditDEF'] = TRUE;
				}
				$rotateLang = $editData['meta']['currentLangId'];
			}

				// Tabs sheets
			if (is_array($dataStructArray['sheets']))	{
				$tabsToTraverse = array_keys($dataStructArray['sheets']);
			} else {
				$tabsToTraverse = array($sheet);
			}

			foreach ($rotateLang as $lKey)	{
				if (!$langChildren && !$langDisabled)	{
					$item.= '<b>'.$this->TCEformsObject->getLanguageIcon($table,$row,'v'.$lKey).$lKey.':</b>';
				}

				$tabParts = array();
				foreach ($tabsToTraverse as $sheet)	{
					list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructArray,$sheet);

						// Render sheet:
					if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el']))		{
						$lang = 'l'.$lKey;	// Default language, other options are "lUK" or whatever country code (independant of system!!!)
						$PA['_valLang'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : 'DEF';	// Default language, other options are "lUK" or whatever country code (independant of system!!!)
						$PA['_lang'] = $lang;
						$PA['_cshFile'] = ((isset($dataStruct['ROOT']['TCEforms']) && isset($dataStruct['ROOT']['TCEforms']['cshFile'])) ? $dataStruct['ROOT']['TCEforms']['cshFile'] : '');

							// Render flexform:
						$tRows = $this->drawFlexForm(
									$dataStruct['ROOT']['el'],
									$editData['data'][$sheet][$lang],
									$table,
									$field,
									$row,
									$PA,
									'[data]['.$sheet.']['.$lang.']'
								);
						#$sheetContent= '<table border="0" cellpadding="1" cellspacing="1" class="typo3-TCEforms-flexForm">'.implode('',$tRows).'</table>';
						$sheetContent = '<div class="typo3-TCEforms-flexForm">'.$tRows.'</div>';

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

				if (is_array($dataStructArray['sheets']))	{
					$dividersToTabsBehaviour = (isset($GLOBALS['TCA'][$table]['ctrl']['dividers2tabs']) ? $GLOBALS['TCA'][$table]['ctrl']['dividers2tabs'] : 1);
					$item.= $this->TCEformsObject->getDynTabMenu($tabParts, 'TCEFORMS:flexform:'.$PA['itemFormElName'].$PA['_lang'], $dividersToTabsBehaviour);
				} else {
					$item.= $sheetContent;
				}
			}
		} else $item='Data Structure ERROR: '.$dataStructArray;

		return $item;
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
		foreach($languages as $lArr)	{
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
	function createSheetMenu($sArr,$elName,$sheetKey)	{

		$tCells =array();
		$pct = round(100/count($sArr));
		foreach($sArr as $sKey => $sheetCfg)	{
			if ($GLOBALS['BE_USER']->jsConfirmation(1))	{
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

	/**
	 * Recursive rendering of flexforms
	 *
	 * @param	array		(part of) Data Structure for which to render. Keys on first level is flex-form fields
	 * @param	array		(part of) Data array of flexform corresponding to the input DS. Keys on first level is flex-form field names
	 * @param	string		Table name, eg. tt_content
	 * @param	string		Field name, eg. tx_templavoila_flex
	 * @param	array		The particular record from $table in which the field $field is found
	 * @param	array		Array of standard information for rendering of a form field in TCEforms, see other rendering functions too
	 * @param	string		Form field prefix, eg. "[data][sDEF][lDEF][...][...]"
	 * @param	integer		Indicates nesting level for the function call
	 * @param	string		Prefix for ID-values
	 * @param	boolean		Defines whether the next flexform level is open or closed. Comes from _TOGGLE pseudo field in FlexForm xml.
	 * @return	string		HTMl code for form.
	 */
	function drawFlexForm($dataStruct,$editData,$table,$field,$row,&$PA,$formPrefix='',$level=0,$idPrefix='ID',$toggleClosed=FALSE)	{

		$output = '';
		$mayRestructureFlexforms = $GLOBALS['BE_USER']->checkLanguageAccess(0);

			// Data Structure array must be ... and array of course...
		if (is_array($dataStruct))	{
			foreach($dataStruct as $key => $value)	{	// Traversing fields in structure:
				if (is_array($value))	{	// The value of each entry must be an array.

						// ********************
						// Making the row:
						// ********************
						// Title of field:
					$theTitle = htmlspecialchars(t3lib_div::fixed_lgd_cs($this->TCEformsObject->sL($value['tx_templavoila']['title']),30));

						// If it's a "section" or "container":
					if ($value['type']=='array')	{

							// Creating IDs for form fields:
							// It's important that the IDs "cascade" - otherwise we can't dynamically expand the flex form because this relies on simple string substitution of the first parts of the id values.
						$thisId = t3lib_div::shortMd5(uniqid('id',true));	// This is a suffix used for forms on this level
						$idTagPrefix = $idPrefix.'-'.$thisId;	// $idPrefix is the prefix for elements on lower levels in the hierarchy and we combine this with the thisId value to form a new ID on this level.

							// If it's a "section" containing other elements:
						if ($value['section'])	{

								// Render header of section:
							$output.= '<div class="bgColor2"><strong>'.$theTitle.'</strong></div>';

								// Render elements in data array for section:
							$tRows = array();
							$cc=0;
							if (is_array($editData[$key]['el']))	{
								foreach ($editData[$key]['el'] as $k3 => $v3)	{
									$cc=$k3;
									if (is_array($v3))	{
										$theType = key($v3);
										$theDat = $v3[$theType];
										$newSectionEl = $value['el'][$theType];
										if (is_array($newSectionEl))	{
											$tRows[]= $this->drawFlexForm(
												array($theType => $newSectionEl),
												array($theType => $theDat),
												$table,
												$field,
												$row,
												$PA,
												$formPrefix.'['.$key.'][el]['.$cc.']',
												$level+1,
												$idTagPrefix,
												$v3['_TOGGLE']
											);
										}
									}
								}
							}

								// Now, we generate "templates" for new elements that could be added to this section by traversing all possible types of content inside the section:
								// We have to handle the fact that requiredElements and such may be set during this rendering process and therefore we save and reset the state of some internal variables - little crude, but works...

								// Preserving internal variables we don't want to change:
							$TEMP_requiredElements = $this->TCEformsObject->requiredElements;

								// Traversing possible types of new content in the section:
							$newElementsLinks = array();
							foreach($value['el'] as $nnKey => $nCfg)	{
								$newElementTemplate = $this->drawFlexForm(
									array($nnKey => $nCfg),
									array(),
									$table,
									$field,
									$row,
									$PA,
									$formPrefix.'['.$key.'][el]['.$idTagPrefix.'-form]',
									$level+1,
									$idTagPrefix
								);

									// Makes a "Add new" link:
								$onClickInsert = 'new Insertion.Bottom($("'.$idTagPrefix.'"), unescape("'.rawurlencode($newElementTemplate).'").replace(/'.$idTagPrefix.'-/g,"'.$idTagPrefix.'-idx"+Math.floor(Math.random()*100000+1)+"-")); setActionStatus("'.$idTagPrefix.'"); return false;';	// Maybe there is a better way to do this than store the HTML for the new element in rawurlencoded format - maybe it even breaks with certain charsets? But for now this works...
								$newElementsLinks[]= '<a href="#" onclick="'.htmlspecialchars($onClickInsert).'"><img'.t3lib_iconWorks::skinImg($this->TCEformsObject->backPath,'gfx/new_el.gif','width="11" height="12"').' alt="New" title="New" align="absmiddle" />'.htmlspecialchars(t3lib_div::fixed_lgd_cs($this->TCEformsObject->sL($nCfg['tx_templavoila']['title']),30)).'</a>';
							}

								// Reverting internal variables we don't want to change:
							$this->TCEformsObject->requiredElements = $TEMP_requiredElements;

								// Adding the sections:
							$output.= '
							<div style="padding: 2px 0px 2px 20px;">
							<a href="#" onclick="'.htmlspecialchars('flexFormToggleSubs("'.$idTagPrefix.'"); return false;').'">
								<img'.t3lib_iconWorks::skinImg($this->TCEformsObject->backPath,'gfx/pil2right.gif','width="7" height="12"').' align="absmiddle" alt="Toggle All" title="Toggle All" /><img'.t3lib_iconWorks::skinImg($this->TCEformsObject->backPath,'gfx/pil2right.gif','width="7" height="12"').' align="absmiddle" alt="Toggle All" title="Toggle All" />Toggle All
							</a>
							</div>

							<div id="'.$idTagPrefix.'" style="padding-left: 20px;">'.implode('',$tRows).'</div>';
							$output.= $mayRestructureFlexforms ? '<div style="padding: 10px 5px 5px 20px;"><b>Add new:</b> '.implode(' | ',$newElementsLinks).'</div>' : '';
							// If it's a container:
						} else {

							$toggleIcon_open = '<img'.t3lib_iconWorks::skinImg($this->TCEformsObject->backPath,'gfx/pil2down.gif','width="12" height="7"').' hspace="2" alt="Open" title="Open" />';
							$toggleIcon_close = '<img'.t3lib_iconWorks::skinImg($this->TCEformsObject->backPath,'gfx/pil2right.gif','width="7" height="12"').' hspace="2" alt="Close" title="Close" />';

								// Create on-click actions.
						#	$onClickCopy = 'new Insertion.After($("'.$idTagPrefix.'"), getOuterHTML("'.$idTagPrefix.'").replace(/'.$idTagPrefix.'-/g,"'.$idTagPrefix.'-copy"+Math.floor(Math.random()*100000+1)+"-")); return false;';	// Copied elements doesn't work (well) in Safari while they do in Firefox and MSIE! UPDATE: It turned out that copying doesn't work for any browser, simply because the data from the copied form never gets submitted to the server for some reason! So I decided to simply disable copying for now. If it's requested by customers we can look to enable it again and fix the issue. There is one un-fixable problem though; Copying an element like this will violate integrity if files are attached inside that element because the file reference doesn't get an absolute path prefixed to it which would be required to have TCEmain generate a new copy of the file.
							$onClickRemove = 'if (confirm("Are you sure?")){$("'.$idTagPrefix.'").hide();setActionStatus("'.$idPrefix.'");} return false;';
							$onClickToggle = 'flexFormToggle("'.$idTagPrefix.'"); return false;';

							$onMove = 'flexFormSortable("'.$idPrefix.'")';
								// Notice: Creating "new" elements after others seemed to be too difficult to do and since moving new elements created in the bottom is now so easy with drag'n'drop I didn't see the need.


								// Putting together header of a section. Sections can be removed, copied, opened/closed, moved up and down:
								// I didn't know how to make something right-aligned without a table, so I put it in a table. can be made into <div>'s if someone like to.
								// Notice: The fact that I make a "Sortable.create" right onmousedown is that if we initialize this when rendering the form in PHP new and copied elements will not be possible to move as a sortable. But this way a new sortable is initialized everytime someone tries to move and it will always work.
							$ctrlHeader= '
								<table border="0" cellpadding="0" cellspacing="0" width="100%" onmousedown="'.($mayRestructureFlexforms?htmlspecialchars($onMove):'').'">
								<tr>
									<td>
										<a href="#" onclick="'.htmlspecialchars($onClickToggle).'" id="'.$idTagPrefix.'-toggle">
											'.($toggleClosed?$toggleIcon_close:$toggleIcon_open).'
										</a>
										<strong>'.$theTitle.'</strong> <em><span id="'.$idTagPrefix.'-preview"></span></em>
									</td>
									<td align="right">'.
										($mayRestructureFlexforms ? '<img'.t3lib_iconWorks::skinImg($this->TCEformsObject->backPath,'gfx/move.gif','width="16" height="16"').' alt="Drag to Move" title="Drag to Move" />' : '').
									#	'<a href="#" onclick="'.htmlspecialchars($onClickCopy).'"><img'.t3lib_iconWorks::skinImg($this->TCEformsObject->backPath,'gfx/clip_copy.gif','width="12" height="12"').' alt="Copy" title="Copy" /></a>'.	// DISABLED - see what above in definition of variable $onClickCopy
										($mayRestructureFlexforms ? '<a href="#" onclick="'.htmlspecialchars($onClickRemove).'"><img'.t3lib_iconWorks::skinImg($this->TCEformsObject->backPath,'gfx/garbage.gif','width="11" height="12"').' alt="Delete" title="Delete" /></a>' : '').
									'</td>
									</tr>
								</table>';

							$s = t3lib_div::revExplode('[]',$formPrefix,2);
							$actionFieldName = '_ACTION_FLEX_FORM'.$PA['itemFormElName'].$s[0].'][_ACTION]['.$s[1];

								// Putting together the container:
							$output.= '
								<div id="'.$idTagPrefix.'" class="bgColor2">
									<input id="'.$idTagPrefix.'-action" type="hidden" name="'.htmlspecialchars($actionFieldName).'" value=""/>

									'.$ctrlHeader.'
									<div id="'.$idTagPrefix.'-content"'.($toggleClosed?' style="display:none;"':'').'>'.$this->drawFlexForm(
										$value['el'],
										$editData[$key]['el'],
										$table,
										$field,
										$row,
										$PA,
										$formPrefix.'['.$key.'][el]',
										$level+1,
										$idTagPrefix
									).'
									</div>
									<input id="'.$idTagPrefix.'-toggleClosed" type="hidden" name="'.htmlspecialchars('data['.$table.']['.$row['uid'].']['.$field.']'.$formPrefix.'[_TOGGLE]').'" value="'.($toggleClosed?1:0).'" />
								</div>';
									// NOTICE: We are saving the toggle-state directly in the flexForm XML and "unauthorized" according to the data structure. It means that flexform XML will report unclean and a cleaning operation will remove the recorded togglestates. This is not a fatal problem. Ideally we should save the toggle states in meta-data but it is much harder to do that. And this implementation was easy to make and with no really harmful impact.
						}

						// If it's a "single form element":
					} elseif (is_array($value['TCEforms']['config'])) {	// Rendering a single form element:

						if (is_array($PA['_valLang']))	{
							$rotateLang = $PA['_valLang'];
						} else {
							$rotateLang = array($PA['_valLang']);
						}

						$tRows = array();
						foreach($rotateLang as $vDEFkey)	{
							$vDEFkey = 'v'.$vDEFkey;

							if (!$value['TCEforms']['displayCond'] || $this->TCEformsObject->isDisplayCondition($value['TCEforms']['displayCond'],$editData,$vDEFkey)) {
								$fakePA=array();
								$fakePA['fieldConf']=array(
									'label' => $this->TCEformsObject->sL(trim($value['TCEforms']['label'])),
									'config' => $value['TCEforms']['config'],
									'defaultExtras' => $value['TCEforms']['defaultExtras'],
                                    'onChange' => $value['TCEforms']['onChange']
								);
								if ($PA['_noEditDEF'] && $PA['_lang']==='lDEF') {
									$fakePA['fieldConf']['config'] = array(
										'type' => 'none',
										'rows' => 2
									);
								}

								if (
                                    $fakePA['fieldConf']['onChange'] == 'reload' ||
									($GLOBALS['TCA'][$table]['ctrl']['type'] && !strcmp($key,$GLOBALS['TCA'][$table]['ctrl']['type'])) ||
									($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'] && t3lib_div::inList($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'],$key))) {
									if ($GLOBALS['BE_USER']->jsConfirmation(1))	{
										$alertMsgOnChange = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
									} else {
										$alertMsgOnChange = 'if(TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm();}';
									}
								} else {
									$alertMsgOnChange = '';
								}

								$fakePA['fieldChangeFunc']=$PA['fieldChangeFunc'];
								if (strlen($alertMsgOnChange)) {
									$fakePA['fieldChangeFunc']['alert']=$alertMsgOnChange;
								}
								$fakePA['onFocus']=$PA['onFocus'];
								$fakePA['label']=$PA['label'];

								$fakePA['itemFormElName']=$PA['itemFormElName'].$formPrefix.'['.$key.']['.$vDEFkey.']';
								$fakePA['itemFormElName_file']=$PA['itemFormElName_file'].$formPrefix.'['.$key.']['.$vDEFkey.']';

								if(isset($editData[$key][$vDEFkey])) {
									$fakePA['itemFormElValue']=$editData[$key][$vDEFkey];
								} else {
									$fakePA['itemFormElValue']=$fakePA['fieldConf']['config']['default'];
								}

								$theFormEl= $this->TCEformsObject->getSingleField_SW($table,$field,$row,$fakePA);
								$theTitle= htmlspecialchars($fakePA['fieldConf']['label']);

								if (!in_array('DEF',$rotateLang))	{
									$defInfo = '<div class="typo3-TCEforms-originalLanguageValue">'.$this->TCEformsObject->getLanguageIcon($table,$row,0).$this->TCEformsObject->previewFieldValue($editData[$key]['vDEF'], $fakePA['fieldConf']).'&nbsp;</div>';
								} else {
									$defInfo = '';
								}

								if (!$PA['_noEditDEF'])	{
									$prLang = $this->TCEformsObject->getAdditionalPreviewLanguages();
									foreach($prLang as $prL)	{
										$defInfo.= '<div class="typo3-TCEforms-originalLanguageValue">'.$this->TCEformsObject->getLanguageIcon($table,$row,'v'.$prL['ISOcode']).$this->TCEformsObject->previewFieldValue($editData[$key]['v'.$prL['ISOcode']], $fakePA['fieldConf']).'&nbsp;</div>';
									}
								}

									// Put row together
									// possible linebreaks in the label through xml: \n => <br/>, usage of nl2br() not possible, so it's done through str_replace
								$processedTitle = str_replace('\n', '<br />', $theTitle);
								$helpText = $this->TCEformsObject->helpText_typeFlex($key, $processedTitle, $PA['_cshFile']);
								$tRows[]='<div>' .
									'<div class="bgColor5">' .
									($helpText ?
										($vDEFkey=='vDEF' ? '' : $this->TCEformsObject->getLanguageIcon($table, $row, $vDEFkey)) . '<strong>' . $processedTitle . '</strong>' . $helpText :
										$this->TCEformsObject->helpTextIcon_typeFlex($key, $processedTitle, $PA['_cshFile']) . ($vDEFkey == 'vDEF' ? '' : $this->TCEformsObject->getLanguageIcon($table, $row, $vDEFkey)) . $processedTitle
									) .
									'</div>
									<div class="bgColor4">'.$theFormEl.$defInfo.$this->TCEformsObject->renderVDEFDiff($editData[$key],$vDEFkey).'</div>
								</div>';
							}
						}
						if (count($tRows))	$output.= implode('',$tRows);
					}
				}
			}
		}

		return $output;
	}
}
