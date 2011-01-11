<?php

class t3lib_TCEforms_Element_Linebreak extends t3lib_TCEforms_Element_Abstract {

	public function __construct() {
	}

	protected function renderField() {
		return '<br />';
	}

	public function getFieldname() {
		return '--linebreak--';
	}
}
?>
