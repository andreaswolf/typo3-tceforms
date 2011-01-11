<?php


class t3lib_TCEforms_Element_Input extends t3lib_TCEforms_Element_Abstract {
	protected function renderField() {
		$config = $this->fieldSetup['config'];

		$specConf = $this->getSpecConfFromString($this->extra, $this->fieldSetup['defaultExtras']);
		$size = t3lib_div::intInRange($config['size'] ? $config['size'] : 30, 5, $this->maxInputWidth);
		$evalList = t3lib_div::trimExplode(',',$config['eval'], TRUE);
		$classAndStyleAttributes = $this->formWidthAsArray($size);

		$fieldAppendix = '';
		$cssClasses    = array($classAndStyleAttributes['class']);
		$cssStyle      = $classAndStyleAttributes['style'];

	        // css class and id will show the kind of field
		if (in_array('date', $evalList)) {
			$inputId = uniqid('tceforms-datefield-');
			$cssClasses[] = 'tceforms-textfield tceforms-datefield';
			$fieldAppendix = t3lib_iconWorks::getSpriteIcon(
				'actions-edit-pick-date',
				array(
					'style' => 'cursor:pointer;',
					'id' => 'picker-' . $inputId
				)
			);

		} elseif (in_array('datetime', $evalList)) {
			$inputId = uniqid('tceforms-datetimefield-');
			$cssClasses[] = 'tceforms-textfield tceforms-datetimefield';
			$fieldAppendix = t3lib_iconWorks::getSpriteIcon(
				'actions-edit-pick-date',
				array(
					'style' => 'cursor:pointer;',
					'id' => 'picker-' . $inputId
				)
			);

		} elseif (in_array('timesec', $evalList)) {
			$inputId = uniqid('tceforms-timesecfield-');
			$cssClasses[] = 'tceforms-textfield tceforms-timesecfield';
		} elseif (in_array('year', $evalList)) {
			$inputId = uniqid('tceforms-yearfield-');
			$cssClasses[] = 'tceforms-textfield tceforms-yearfield';
		} elseif (in_array('time', $evalList)) {
			$inputId = uniqid('tceforms-timefield-');
			$cssClasses[] = 'tceforms-textfield tceforms-timefield';
		} elseif (in_array('int', $evalList)) {
			$inputId = uniqid('tceforms-intfield-');
			$cssClasses[] = 'tceforms-textfield tceforms-intfield';
		} elseif (in_array('double2', $evalList)) {
			$inputId = uniqid('tceforms-double2field-');
			$cssClasses[] = 'tceforms-textfield tceforms-double2field';
		} else {
			$inputId = uniqid('tceforms-textfield-');
			$cssClasses[] = 'tceforms-textfield';
		}
		if (isset($config['wizards']['link'])) {
			$inputId = uniqid('tceforms-linkfield-');
			$cssClasses[] = 'tceforms-textfield tceforms-linkfield';
		} elseif (isset($config['wizards']['color'])) {
			$inputId = uniqid('tceforms-colorfield-');
			$cssClasses[] = 'tceforms-textfield tceforms-colorfield';
		}

		if($this->contextObject->isReadOnly() || $config['readOnly'])  {
			$itemFormElValue = $this->itemFormElValue;
			if (in_array('date',$evalList)) {
				$config['format'] = 'date';
			} elseif (in_array('datetime',$evalList)) {
				$config['format'] = 'datetime';
			} elseif (in_array('time', $evalList)) {
				$config['format'] = 'time';
			}
			if (in_array('password', $evalList)) {
				$itemFormElValue = $itemFormElValue ? '*********' : '';
			}
			// TODO: fix this
			return 'I need to be fixed (t3lib_TCEforms_Element_Input::renderField())';
			//return $this->TCEformsObject->getSingleField_typeNone_render($config, $itemFormElValue);
		}

		foreach ($evalList as $func) {
			switch ($func) {
				case 'required':
					//$this->container->registerRequiredProperty('field', $this->table.'_'.$this->record['uid'].'_'.$this->field, $this->itemFormElName);
					$this->contextObject->registerRequiredField($this->table.'_'.$this->record['uid'].'_'.$this->field, $this->formFieldName);
						// Mark this field for date/time disposal:
					if (array_intersect($evalList, array('date', 'datetime', 'time'))) {
						 $this->TCEformsObject->requiredAdditional[$this->formFieldName]['isPositiveNumber'] = true;
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

		$this->paramsList = "'" . $this->formFieldName . "','" . implode(',', $evalList) . "','" . trim($config['is_in']) . "',".(isset($config['checkbox']) ? 1 : 0) . ",'" . $config['checkbox'] . "'";
		if (!empty($config['checkbox'])) {
				// Setting default "click-checkbox" values for eval types "date" and "datetime":
			$thisMidnight = gmmktime(0,0,0);
			if (in_array('date', $evalList)) {
				$checkSetValue = $thisMidnight;
			} elseif (in_array('datetime', $evalList)) {
				$checkSetValue = time();
			} elseif (in_array('year', $evalList)) {
				$checkSetValue = gmdate('Y');
			}
			$cOnClick = 'typo3form.fieldGet(' . $this->paramsList . ',1,\'' . $checkSetValue . '\');' . implode('', $this->fieldChangeFunc);
			$item .= '<input type="checkbox" id="' . uniqid('tceforms-check-') . '" class="' . $this->formElStyleClassValue('check', TRUE) . '" name="' . $this->formFieldName . '_cb" onclick="' . htmlspecialchars($cOnClick) . '" />';
		}
		if ((in_array('date', $evalList) || in_array('datetime', $evalList)) && $this->itemFormElValue > 0) {
				// Add server timezone offset to UTC to our stored date
			$this->itemFormElValue += date('Z', $this->itemFormElValue);
		}

		$fieldChangeFunc = array_merge(array('typo3form.fieldGet'=>'typo3form.fieldGet('.$this->paramsList.');'), $this->fieldChangeFunc);
		$mLgd = ($config['max'] ? $config['max'] : 256);
		$iOnChange = implode('', $fieldChangeFunc);

			// This is the EDITABLE form field.
		$item.='<input type="text" id="' . $inputId . '" class="' . implode(' ', $cssClasses) . '" name="' . $this->formFieldName . '_hr" value="" style="' . $cssStyle . '" maxlength="' . $mLgd . '" onchange="' . htmlspecialchars($iOnChange) . '"' . $this->onFocus . ' />';
			// This is the ACTUAL form field - values from the EDITABLE field must be transferred to
			// this field which is the one that is written to the database.
		$item.='<input type="hidden" name="'.$this->formFieldName.'" value="'.htmlspecialchars($this->itemFormElValue).'" />';
		$item .= $fieldAppendix;

		$this->contextObject->addToEvaluationJS('typo3form.fieldSet('.$this->paramsList.');');

			// going through all custom evaluations configured for this field
		foreach ($evalList as $evalData) {
			if (substr($evalData, 0, 3) == 'tx_')	{
				$evalObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$evalData].':&'.$evalData);

				if(is_object($evalObj) && method_exists($evalObj, 'returnFieldJS'))	{
					$this->contextObject->addToValidationJavascriptCode("\n\nfunction ".$evalData."(value) {\n".$evalObj->returnFieldJS()."\n}\n");
				}
			}
		}

			// Creating an alternative item without the JavaScript handlers.
		$altItem  = '<input type="hidden" name="'.$this->formFieldName.'_hr" value="" />';
		$altItem .= '<input type="hidden" name="'.$this->formFieldName.'" value="'.htmlspecialchars($this->itemFormElValue).'" />';

			// Wrap a wizard around the item?
		return $this->renderWizards(array($item,$altItem),$config['wizards'],$this->formFieldName.'_hr',$specConf);
	}
}

?>