<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_InputElement extends t3lib_TCEforms_AbstractElement {
	protected $item;
	
	public function init($table, $field, $row, &$PA) {
		$config = $PA['fieldConf']['config'];

#		$specConf = $this->TCEformsObject->getSpecConfForField($table,$row,$field);
		$specConf = $this->TCEformsObject->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);
		$size = t3lib_div::intInRange($config['size']?$config['size']:30,5,$this->TCEformsObject->maxInputWidth);
		$evalList = t3lib_div::trimExplode(',',$config['eval'],1);

		if($this->TCEformsObject->renderReadonly || $config['readOnly'])  {
			$itemFormElValue = $PA['itemFormElValue'];
			if (in_array('date',$evalList))	{
				$config['format'] = 'date';
			} elseif (in_array('date',$evalList))	{
				$config['format'] = 'date';
			} elseif (in_array('datetime',$evalList))	{
				$config['format'] = 'datetime';
			} elseif (in_array('time',$evalList))	{
				$config['format'] = 'time';
			}
			if (in_array('password',$evalList))	{
				$itemFormElValue = $itemFormElValue ? '*********' : '';
			}
			return $this->TCEformsObject->getSingleField_typeNone_render($config, $itemFormElValue);
		}

		foreach ($evalList as $func) {
			switch ($func) {
				case 'required':
					$this->TCEformsObject->registerRequiredPropertyExternal('field', $table.'_'.$row['uid'].'_'.$field, $PA['itemFormElName']);
						// Mark this field for date/time disposal:
					if (array_intersect($evalList, array('date', 'datetime', 'time'))) {
						 $this->TCEformsObject->requiredAdditional[$PA['itemFormElName']]['isPositiveNumber'] = true;
					}
					break;
				default:
					if (substr($func, 0, 3) == 'tx_')	{
						// Pair hook to the one in t3lib_TCEmain::checkValue_input_Eval()
						$evalObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func].':&'.$func);
						if (is_object($evalObj) && method_exists($evalObj, 'deevaluateFieldValue'))	{
							$_params = array(
								'value' => $PA['itemFormElValue']
							);
							$PA['itemFormElValue'] = $evalObj->deevaluateFieldValue($_params);
						}
					}
					break;
			}
		}

		$paramsList = "'".$PA['itemFormElName']."','".implode(',',$evalList)."','".trim($config['is_in'])."',".(isset($config['checkbox'])?1:0).",'".$config['checkbox']."'";
		if (isset($config['checkbox']))	{
				// Setting default "click-checkbox" values for eval types "date" and "datetime":
			$thisMidnight = gmmktime(0,0,0);
			if (in_array('date',$evalList))	{
				$checkSetValue = $thisMidnight;
			} elseif (in_array('datetime',$evalList))	{
				$checkSetValue = time();
			} elseif (in_array('year',$evalList))	{
				$checkSetValue = gmdate('Y');
			}
			$cOnClick = 'typo3form.fieldGet('.$paramsList.',1,\''.$checkSetValue.'\');'.implode('',$PA['fieldChangeFunc']);
			$item.='<input type="checkbox"'.$this->TCEformsObject->insertDefStyle('check').' name="'.$PA['itemFormElName'].'_cb" onclick="'.htmlspecialchars($cOnClick).'" />';
		}
		if ((in_array('date',$evalList) || in_array('datetime',$evalList)) && $PA['itemFormElValue']>0){
				// Add server timezone offset to UTC to our stored date
			$PA['itemFormElValue'] += date('Z', $PA['itemFormElValue']);
		}

		$PA['fieldChangeFunc'] = array_merge(array('typo3form.fieldGet'=>'typo3form.fieldGet('.$paramsList.');'), $PA['fieldChangeFunc']);
		$mLgd = ($config['max']?$config['max']:256);
		$iOnChange = implode('',$PA['fieldChangeFunc']);
		$item.='<input type="text" name="'.$PA['itemFormElName'].'_hr" value=""'.$this->TCEformsObject->formWidth($size).' maxlength="'.$mLgd.'" onchange="'.htmlspecialchars($iOnChange).'"'.$PA['onFocus'].' />';	// This is the EDITABLE form field.
		$item.='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';			// This is the ACTUAL form field - values from the EDITABLE field must be transferred to this field which is the one that is written to the database.
		$this->TCEformsObject->extJSCODE.='typo3form.fieldSet('.$paramsList.');';

			// going through all custom evaluations configured for this field
		foreach ($evalList as $evalData) {
			if (substr($evalData, 0, 3) == 'tx_')	{
				$evalObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$evalData].':&'.$evalData);
				if(is_object($evalObj) && method_exists($evalObj, 'returnFieldJS'))	{
					$this->TCEformsObject->extJSCODE .= "\n\nfunction ".$evalData."(value) {\n".$evalObj->returnFieldJS()."\n}\n";
				}
			}
		}

			// Creating an alternative item without the JavaScript handlers.
		$altItem  = '<input type="hidden" name="'.$PA['itemFormElName'].'_hr" value="" />';
		$altItem .= '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';

			// Wrap a wizard around the item?
		$this->item = $this->TCEformsObject->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'].'_hr',$specConf);
	}
	
	public function render() {
		return $this->item;
	}
}