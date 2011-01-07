<?php

require_once(PATH_t3lib.'tceforms/element/class.t3lib_tceforms_element_abstract.php');


class t3lib_TCEforms_Element_Unknown extends t3lib_TCEforms_Element_Abstract {
	protected function renderField() {

		$item = 'Unknown type: '.$this->fieldSetup['config']['form_type'].'<br />';

		return $item;
	}
}
?>