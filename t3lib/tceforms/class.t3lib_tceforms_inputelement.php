<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_InputElement extends t3lib_TCEforms_AbstractElement {
	protected $item;

	public function renderField() {
		$config = $this->fieldConfig['config'];

#		$specConf = $this->TCEformsObject->getSpecConfForField($this->table,$this->record,$this->field);
		$specConf = $this->TCEformsObject->getSpecConfFromString($this->extra, $this->fieldConfig['defaultExtras']);
		$size = t3lib_div::intInRange($config['size']?$config['size']:30,5,$this->TCEformsObject->maxInputWidth);
		$evalList = t3lib_div::trimExplode(',',$config['eval'],1);

		if($this->TCEformsObject->renderReadonly || $config['readOnly'])  {
			$itemFormElValue = $this->itemFormElValue;
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
					$this->containingTab->registerRequiredProperty('field', $this->table.'_'.$this->record['uid'].'_'.$this->field, $this->itemFormElName);
						// Mark this field for date/time disposal:
					if (array_intersect($evalList, array('date', 'datetime', 'time'))) {
						 $this->TCEformsObject->requiredAdditional[$this->itemFormElName]['isPositiveNumber'] = true;
					}
					break;
				default:
					if (substr($func, 0, 3) == 'tx_')	{
						// Pair hook to the one in t3lib_TCEmain::checkValue_input_Eval()
						$evalObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func].':&'.$func);
						if (is_object($evalObj) && method_exists($evalObj, 'deevaluateFieldValue'))	{
							$_params = array(
								'value' => $this->itemFormElValue
							);
							$this->itemFormElValue = $evalObj->deevaluateFieldValue($_params);
						}
					}
					break;
			}
		}

		$this->paramsList = "'".$this->itemFormElName."','".implode(',',$evalList)."','".trim($config['is_in'])."',".(isset($config['checkbox'])?1:0).",'".$config['checkbox']."'";
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
			$cOnClick = 'typo3form.fieldGet('.$this->paramsList.',1,\''.$checkSetValue.'\');'.implode('',$this->fieldChangeFunc);
			$item.='<input type="checkbox"'.$this->TCEformsObject->insertDefStyle('check').' name="'.$this->itemFormElName.'_cb" onclick="'.htmlspecialchars($cOnClick).'" />';
		}
		if ((in_array('date',$evalList) || in_array('datetime',$evalList)) && $this->itemFormElValue>0){
				// Add server timezone offset to UTC to our stored date
			$this->itemFormElValue += date('Z', $this->itemFormElValue);
		}

		$this->fieldChangeFunc = array_merge(array('typo3form.fieldGet'=>'typo3form.fieldGet('.$this->paramsList.');'), $this->fieldChangeFunc);
		$mLgd = ($config['max']?$config['max']:256);
		$iOnChange = implode('',$this->fieldChangeFunc);
		$item.='<input type="text" name="'.$this->itemFormElName.'_hr" value=""'.$this->TCEformsObject->formWidth($size).' maxlength="'.$mLgd.'" onchange="'.htmlspecialchars($iOnChange).'"'.$this->onFocus.' />';	// This is the EDITABLE form field.
		$item.='<input type="hidden" name="'.$this->itemFormElName.'" value="'.htmlspecialchars($this->itemFormElValue).'" />';			// This is the ACTUAL form field - values from the EDITABLE field must be transferred to this field which is the one that is written to the database.
		$this->TCEformsObject->extJSCODE.='typo3form.fieldSet('.$this->paramsList.');';

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
		$altItem  = '<input type="hidden" name="'.$this->itemFormElName.'_hr" value="" />';
		$altItem .= '<input type="hidden" name="'.$this->itemFormElName.'" value="'.htmlspecialchars($this->itemFormElValue).'" />';

			// Wrap a wizard around the item?
		return $this->TCEformsObject->renderWizards(array($item,$altItem),$config['wizards'],$this->table,$this->record,$this->field,$this->PA,$this->itemFormElName.'_hr',$specConf);
	}
}