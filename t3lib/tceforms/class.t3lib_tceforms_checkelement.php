<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_CheckElement extends t3lib_TCEforms_AbstractElement {
	protected $item;

	public function init($table, $field, $row, &$PA) {
		$config = $PA['fieldConf']['config'];

		$disabled = '';
		if($this->TCEformsObject->renderReadonly || $config['readOnly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Traversing the array of items:
		$selItems = $this->TCEformsObject->initItemArray($PA['fieldConf']);
		if ($config['itemsProcFunc']) $selItems = $this->TCEformsObject->procItems($selItems,$PA['fieldTSConfig']['itemsProcFunc.'],$config,$table,$row,$field);

		if (!count($selItems))	{
			$selItems[]=array('','');
		}
		$this->TCEformsObjectValue = intval($PA['itemFormElValue']);

		$cols = intval($config['cols']);
		if ($cols > 1)	{
			$item.= '<table border="0" cellspacing="0" cellpadding="0" class="typo3-TCEforms-checkboxArray">';
			for ($c=0;$c<count($selItems);$c++) {
				$p = $selItems[$c];
				if(!($c%$cols))	{ $item.='<tr>'; }
				$cBP = $this->TCEformsObject->checkBoxParams($PA['itemFormElName'],$this->TCEformsObjectValue,$c,count($selItems),implode('',$PA['fieldChangeFunc']));
				$cBName = $PA['itemFormElName'].'_'.$c;
				$cBID = $PA['itemFormElID'].'_'.$c;
				$item.= '<td nowrap="nowrap">'.
						'<input type="checkbox"'.$this->TCEformsObject->insertDefStyle('check').' value="1" name="'.$cBName.'"'.$cBP.$disabled.' id="'.$cBID.'" />'.
						$this->wrapLabels('<label for="'.$cBID.'">'.htmlspecialchars($p[0]).'</label>&nbsp;').
						'</td>';
				if(($c%$cols)+1==$cols)	{$item.='</tr>';}
			}
			if ($c%$cols)	{
				$rest=$cols-($c%$cols);
				for ($c=0;$c<$rest;$c++) {
					$item.= '<td></td>';
				}
				if ($c>0)	{ $item.= '</tr>'; }
			}
			$item.= '</table>';
		} else {
			for ($c=0;$c<count($selItems);$c++) {
				$p = $selItems[$c];
				$cBP = $this->TCEformsObject->checkBoxParams($PA['itemFormElName'],$this->TCEformsObjectValue,$c,count($selItems),implode('',$PA['fieldChangeFunc']));
				$cBName = $PA['itemFormElName'].'_'.$c;
				$cBID = $PA['itemFormElID'].'_'.$c;
				$item.= ($c>0?'<br />':'').
						'<input type="checkbox"'.$this->TCEformsObject->insertDefStyle('check').' value="1" name="'.$cBName.'"'.$cBP.$PA['onFocus'].$disabled.' id="'.$cBID.'" />'.
						htmlspecialchars($p[0]);
			}
		}
		if (!$disabled) {
			$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($this->TCEformsObjectValue).'" />';
		}

		$this->item = $item;
	}

	public function render() {
		return $this->item;
	}
}