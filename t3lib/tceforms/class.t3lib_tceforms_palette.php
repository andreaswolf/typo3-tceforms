<?php

class t3lib_TCEforms_Palette {
	protected $table;
	protected $row;
	protected $paletteNumber;
	protected $field;


	protected $formObject;


	public function init($table, $row, $paletteNumber, $field, $fieldDescriptionParts, $header='', $itemList='', $collapsedHeader=NULL) {

	}

	public function setTCEformsObject(t3lib_TCEforms_AbstractForm $formObject) {
		$this->TCEformsObject = $formObject;
	}

	protected function loadElements() {

	}
}

?>