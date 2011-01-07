<?php

require_once(PATH_t3lib.'tceforms/element/class.t3lib_tceforms_element_abstract.php');


class t3lib_TCEforms_Element_Radio extends t3lib_TCEforms_Element_Abstract {
	protected function renderField() {
		$config = $this->fieldConfig['config'];

		$disabled = '';
		if ($this->isReadOnly()) {
			$disabled = ' disabled="disabled"';
		}

			// Get items for the array:
		$selItems = $this->initItemArray();
		if ($config['itemsProcFunc']) {
			$selItems = $this->procItems($selItems);
		}

			// Traverse the items, making the form elements:
		for ($c=0;$c<count($selItems);$c++) {
			$p = $selItems[$c];
			$rID = $this->formFieldId.'_'.$c;
			$rOnClick = implode('',$this->fieldChangeFunc);
			$rChecked = (!strcmp($p[1],$this->itemFormElValue)?' checked="checked"':'');
			$item.= '<input type="radio"'.$this->insertDefaultElementStyle('radio').' name="'.$this->formFieldName.'" value="'.htmlspecialchars($p[1]).'" onclick="'.htmlspecialchars($rOnClick).'"'.$rChecked.$this->onFocus.$disabled.' id="'.$rID.'" />
					<label for="'.$rID.'">'.htmlspecialchars($p[0]).'</label>
					<br />';
		}

		return $item;
	}
}
