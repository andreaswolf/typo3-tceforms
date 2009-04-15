<?php

require_once(PATH_t3lib.'tceforms/element/class.t3lib_tceforms_element_abstract.php');

class t3lib_TCEforms_Element_Palette extends t3lib_TCEforms_Element_Abstract {
	protected $paletteNumber;

	public function __construct($paletteNumber, $label) {
		$this->paletteNumber = $paletteNumber;

		$this->label = $label;

		if ($this->label !== '') {
			$this->_wrapBorder = TRUE;
		}

		$this->resetStyles();
	}

	public function init() {
		if ($this->paletteNumber) {
			$this->initializePalette($this->paletteNumber);

			// always display palettes displayed in their own element
			$this->paletteObject->setDisplayed(TRUE);
		}
	}

	public function render() {
		$item = $this->paletteObject->render();

		if ($this->label !== '') {
			$out=array(
				'NAME'=>$this->label,
				'ITEM'=>$item,
				'TABLE'=>$this->table,
				'ID'=>$this->record['uid'],
				'HELP_ICON'=> '',
				'HELP_TEXT'=> '',
				'PAL_LINK_ICON'=> '',
				'FIELD'=>$this->field
			);
			$out = $this->intoTemplate($out);

			return $out;
		} else {
			return $item;
		}
	}
}

?>