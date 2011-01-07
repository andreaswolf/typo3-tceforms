<?php

require_once(PATH_t3lib.'tceforms/element/class.t3lib_tceforms_element_abstract.php');


class t3lib_TCEforms_Element_Check extends t3lib_TCEforms_Element_Abstract {
	protected function renderField() {
		$disabled = '';
		if ($this->isReadOnly()) {
			$disabled = ' disabled="disabled"';
		}

			// Traversing the array of items:
		$selItems = $this->initItemArray();
		if ($this->fieldTSConfig['itemsProcFunc']) {
			$selItems = $this->procItems($selItems);
		}

		if (!count($selItems)) {
			$selItems[]=array('', '');
		}
		$value = intval($this->itemFormElValue);

		$cols = intval($this->config['cols']);
		if ($cols > 1) {
			$item.= '<table border="0" cellspacing="0" cellpadding="0" class="typo3-TCEforms-checkboxArray">';
			for ($c=0;$c<count($selItems);$c++) {
				$p = $selItems[$c];
				if(!($c%$cols))	{ $item.='<tr>'; }
				$cBP = $this->checkBoxParams($this->formFieldName,$value,$c,count($selItems),implode('',$this->fieldChangeFunc));
				$cBName = $this->formFieldName.'_'.$c;
				$cBID = $this->formFieldId.'_'.$c;
				$item.= '<td nowrap="nowrap">'.
						'<input type="checkbox"'.$this->insertDefaultElementStyle('check').' value="1" name="'.$cBName.'"'.$cBP.$disabled.' id="'.$cBID.'" />'.
						$this->wrapLabels('<label for="'.$cBID.'">'.htmlspecialchars($p[0]).'</label>&nbsp;').
						'</td>';
				if(($c%$cols)+1==$cols)	{$item.='</tr>';}
			}
			if ($c % $cols) {
				$rest=$cols - ($c % $cols);
				for ($c = 0; $c < $rest; $c++) {
					$item.= '<td></td>';
				}
				if ($c > 0) {
					$item.= '</tr>';
				}
			}
			$item.= '</table>';
		} else {
			for ($c = 0; $c < count($selItems); $c++) {
				$p = $selItems[$c];
				$cBP = $this->checkBoxParams($this->formFieldName, $value, $c, count($selItems), implode('', $this->fieldChangeFunc));
				$cBName = $this->formFieldName . '_' . $c;
				$cBID = $this->formFieldId . '_' . $c;
				$item .= ($c > 0 ? '<br />' : '').
				  '<input type="checkbox"'.$this->insertDefaultElementStyle('check').' value="1" name="'.$cBName.'"'.$cBP.$this->onFocus.$disabled.' id="'.$cBID.'" />'.
				  htmlspecialchars($p[0]);
			}
		}
		if (!$disabled) {
			$item.= '<input type="hidden" name="'.$this->formFieldName.'" value="'.htmlspecialchars($value).'" />';
		}

		return $item;
	}

	/**
	 * Creates checkbox parameters
	 *
	 * @param	string		Form element name
	 * @param	integer		The value of the checkbox (representing checkboxes with the bits)
	 * @param	integer		Checkbox # (0-9?)
	 * @param	integer		Total number of checkboxes in the array.
	 * @param	string		Additional JavaScript for the onclick handler.
	 * @return	string		The onclick attribute + possibly the checked-option set.
	 */
	protected function checkBoxParams($itemName,$thisValue,$c,$iCount,$addFunc='') {
		$onClick = $this->contextObject->elName($itemName).'.value=this.checked?('.$this->contextObject->elName($itemName).'.value|'.pow(2,$c).'):('.$this->contextObject->elName($itemName).'.value&'.(pow(2,$iCount)-1-pow(2,$c)).');'.
					$addFunc;
		$str = ' onclick="'.htmlspecialchars($onClick).'"'.
				(($thisValue&pow(2,$c))?' checked="checked"':'');
		return $str;
	}
}

?>