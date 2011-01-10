<?php


class t3lib_TCEforms_Element_None extends t3lib_TCEforms_Element_Abstract {
	protected function renderField() {
			// Init:
		$config = $this->fieldSetup['config'];
		$itemValue = $this->itemFormElValue;

			// is colorScheme[0] the right value?
		$divStyle = 'border:solid 1px '.t3lib_div::modifyHTMLColorAll($this->colorScheme[0],-30).';'.$this->defStyle./*$this->formElStyle('none').*/' background-color: '.$this->colorScheme[0].'; padding-left:1px;color:#555;';

		if ($config['format'])	{
			$itemValue = $this->formatValue($config, $itemValue);
		}

		$rows = intval($config['rows']);
		if ($rows > 1) {
			if(!$config['pass_content']) {
				$itemValue = nl2br(htmlspecialchars($itemValue));
			}
				// like textarea
			$cols = t3lib_div::intInRange($config['cols'] ? $config['cols'] : 30, 5, $this->maxTextareaWidth);
			if (!$config['fixedRows']) {
				$origRows = $rows = t3lib_div::intInRange($rows, 1, 20);
				if (strlen($itemValue)>$this->charsPerRow*2)	{
					$cols = $this->maxTextareaWidth;
					$rows = t3lib_div::intInRange(round(strlen($itemValue)/$this->charsPerRow),count(explode(chr(10),$itemValue)),20);
					if ($rows<$origRows)	$rows=$origRows;
				}
			}

			if ($this->docLarge)	$cols = round($cols*$this->form_largeComp);
			$width = ceil($cols*$this->form_rowsToStylewidth);
				// hardcoded: 12 is the height of the font
			$height=$rows*12;

			$item='
				<div style="'.htmlspecialchars($divStyle.' overflow:auto; height:'.$height.'px; width:'.$width.'px;').'" class="'./*htmlspecialchars($this->formElClass('none')).*/'">'.
				$itemValue.
				'</div>';
		} else {
			if(!$config['pass_content']) {
				$itemValue = htmlspecialchars($itemValue);
			}

			$cols = $config['cols']?$config['cols']:($config['size']?$config['size']:$this->maxInputWidth);
			if ($this->docLarge)	$cols = round($cols*$this->form_largeComp);
			$width = ceil($cols*$this->form_rowsToStylewidth);

				// overflow:auto crashes mozilla here. Title tag is usefull when text is longer than the div box (overflow:hidden).
			$item = '
				<div style="'.htmlspecialchars($divStyle.' overflow:hidden; width:'.$width.'px;').'" class="'./*htmlspecialchars($this->formElClass('none')).*/'" title="'.$itemValue.'">'.
				'<span class="nobr">'.(strcmp($itemValue,'')?$itemValue:'&nbsp;').'</span>'.
				'</div>';
		}

		return $item;
	}
}

?>