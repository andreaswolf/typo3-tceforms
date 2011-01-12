<?php


class t3lib_TCEforms_Element_Input extends t3lib_TCEforms_Element_Abstract {

	/** the entry for <input type="..." /> */
	protected $inputType = 'text';

	/** @var array an associative array of additional attributes for the HTML input field  */
	protected $additionalAttributes = array();

	/**
	 * renders the input field, depending on the configuration
	 * @return string
	 */
	protected function renderField() {
		// this will be returned
		$item = '';

		$config = $this->fieldSetup['config'];

		$specConf = $this->getSpecConfFromString($this->extra, $this->fieldSetup['defaultExtras']);
		$size = t3lib_div::intInRange($config['size'] ? $config['size'] : 30, 5, $this->maxInputWidth);
		$evalList = t3lib_div::trimExplode(',',$config['eval'], TRUE);
		$classAndStyleAttributes = $this->formWidthAsArray($size);

		$fieldAppendix = '';
		$cssClasses    = array($classAndStyleAttributes['class'], 'tceforms-textfield');
		$cssStyle      = $classAndStyleAttributes['style'];

		$this->additionalAttributes['style'] = $cssStyle;
		$this->additionalAttributes['maxlength'] = ($config['max'] ? $config['max'] : 256);


	        // css class and id will show the kind of field
		if (in_array('date', $evalList)) {
			$this->inputType = 'date';
			$inputId = uniqid('tceforms-datefield-');
			$cssClasses[] = 'tceforms-datefield';
			$fieldAppendix = t3lib_iconWorks::getSpriteIcon(
				'actions-edit-pick-date',
				array(
					'style' => 'cursor:pointer;',
					'id' => 'picker-' . $inputId
				)
			);
			$this->treatZeroAsNull();
			unset($this->additionalAttributes['maxlength']);

		} elseif (in_array('datetime', $evalList)) {
			$this->inputType = 'datetime';
			$inputId = uniqid('tceforms-datetimefield-');
			$cssClasses[] = 'tceforms-datetimefield';
			$fieldAppendix = t3lib_iconWorks::getSpriteIcon(
				'actions-edit-pick-date',
				array(
					'style' => 'cursor:pointer;',
					'id' => 'picker-' . $inputId
				)
			);
			$this->treatZeroAsNull();
			unset($this->additionalAttributes['maxlength']);

		} elseif (in_array('timesec', $evalList)) {
			$this->inputType = 'time';
			$inputId = uniqid('tceforms-timesecfield-');
			$cssClasses[] = 'tceforms-timesecfield';
			$this->treatZeroAsNull();
			unset($this->additionalAttributes['maxlength']);

		} elseif (in_array('year', $evalList)) {
				// TODO: use the input type "date" with a custom format
			$inputId = uniqid('tceforms-yearfield-');
			$cssClasses[] = 'tceforms-yearfield';
			$this->treatZeroAsNull();
			unset($this->additionalAttributes['maxlength']);

		} elseif (in_array('time', $evalList)) {
			// TODO: only show the time without the seconds
			$this->inputType = 'time';
			$inputId = uniqid('tceforms-timefield-');
			$cssClasses[] = 'tceforms-timefield';
			$this->treatZeroAsNull();
			unset($this->additionalAttributes['maxlength']);

		} elseif (in_array('int', $evalList)) {
			$this->inputType = 'number';
			$inputId = uniqid('tceforms-intfield-');
			$cssClasses[] = 'tceforms-intfield';

		} elseif (in_array('double2', $evalList)) {
			$inputId = uniqid('tceforms-double2field-');
			$cssClasses[] = 'tceforms-double2field';

		} elseif (in_array('email', $evalList)) {
			$this->inputType = 'email';
			$inputId = uniqid('tceforms-emailfield-');
			$cssClasses[] = 'tceforms-emailfield';

		} else {
			$inputId = uniqid('tceforms-textfield-');
			$cssClasses[] = 'tceforms-textfield';
		}

		if (isset($config['wizards']['link'])) {
			$this->inputType = 'url';
			$inputId = uniqid('tceforms-linkfield-');
			$cssClasses[] = 'tceforms-linkfield';

		} elseif (isset($config['wizards']['color'])) {
			$this->inputType = 'color';
			$inputId = uniqid('tceforms-colorfield-');
			$cssClasses[] = 'tceforms-colorfield';
		}


		if (is_array($config['range'])) {
			if (isset($config['range']['lower'])) {
				$this->additionalAttributes['min'] = $config['range']['lower'];
				if (isset($config['range']['step'])) {
					$this->additionalAttributes['step'] = $config['range']['step'];
				}
			}
			if (isset($config['range']['upper'])) {
				$this->additionalAttributes['max'] = $config['range']['upper'];
			}
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
						// TODO: do this differently, via "$this->isRequired()"
					$this->additionalAttributes['required'] = 'required';
						// Mark this field for date/time disposal:
						// TODO: add this as an attribute
/*					if (array_intersect($evalList, array('date', 'datetime', 'time'))) {
						 $this->TCEformsObject->requiredAdditional[$this->formFieldName]['isPositiveNumber'] = true;
					}*/
					break;
				default:
					// TODO: not checked yet if this would work
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

			// compile the additional HTML attributes for the input field that is visible
		$this->additionalAttributes['class'] = implode(' ', $cssClasses);
		$this->additionalAttributes['onchange'] = htmlspecialchars(implode('', $fieldChangeFunc));
		$additionalAttributes = t3lib_div::implodeAttributes($this->additionalAttributes);

			// This is the EDITABLE form field.
		$item .= '<input type="' . $this->inputType . '" id="' . $inputId . '" name="' . $this->formFieldName . '_hr" value="' . htmlspecialchars($this->itemFormElValue) . '" ' . $additionalAttributes . $this->onFocus . ' />';
			// This is the ACTUAL form field - values from the EDITABLE field must be transferred to
			// this field which is the one that is written to the database.
		$item .= '<input type="hidden" name="'.$this->formFieldName.'" value="'.htmlspecialchars($this->itemFormElValue).'" />';
		$item .= $fieldAppendix;

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

	/**
	 * separate helper function to check if it's allowed to have "0" as value (not usable for date or timefields
	 */
	protected function treatZeroAsNull() {
		if (is_numeric($this->itemFormElValue) && $this->itemFormElValue == 0) {
			$this->itemFormElValue = '';
		}
	}
}

?>