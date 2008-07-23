<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_RadioElement extends t3lib_TCEforms_AbstractElement {
	protected $item;
	
	public function init($table, $field, $row, &$PA) {
		$config = $PA['fieldConf']['config'];
		
		$disabled = '';
		if($this->TCEformsObject->renderReadonly || $config['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Get items for the array:
		$selItems = $this->TCEformsObject->initItemArray($PA['fieldConf']);
		if ($config['itemsProcFunc']) $selItems = $this->TCEformsObject->procItems($selItems,$PA['fieldTSConfig']['itemsProcFunc.'],$config,$table,$row,$field);

			// Traverse the items, making the form elements:
		for ($c=0;$c<count($selItems);$c++) {
			$p = $selItems[$c];
			$rID = $PA['itemFormElID'].'_'.$c;
			$rOnClick = implode('',$PA['fieldChangeFunc']);
			$rChecked = (!strcmp($p[1],$PA['itemFormElValue'])?' checked="checked"':'');
			$item.= '<input type="radio"'.$this->TCEformsObject->insertDefStyle('radio').' name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($p[1]).'" onclick="'.htmlspecialchars($rOnClick).'"'.$rChecked.$PA['onFocus'].$disabled.' id="'.$rID.'" />
					<label for="'.$rID.'">'.htmlspecialchars($p[0]).'</label>
					<br />';
		}
		
		$this->item = $item;
	}
	
	public function render() {
		return $this->item;
	}
}
