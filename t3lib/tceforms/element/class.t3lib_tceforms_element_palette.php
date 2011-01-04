<?php

require_once(PATH_t3lib.'tceforms/element/class.t3lib_tceforms_element_abstract.php');

class t3lib_TCEforms_Element_Palette extends t3lib_TCEforms_Element_Abstract {
	protected $paletteNumber;

	public function __construct($label) {
		$this->label = $label;

		if ($this->label !== '') {
			$this->_wrapBorder = TRUE;
		}

		$this->resetStyles();
	}

	public function init() {
	}

	public function getPaletteObject() {
		return $this->paletteObject;
	}

	public function render() {
		$this->paletteObject->setDisplayed(TRUE);

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

	protected function renderField() {
	}
}

?>