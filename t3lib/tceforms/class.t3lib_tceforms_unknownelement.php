<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_UnknownElement extends t3lib_TCEforms_AbstractElement {
	protected function renderField() {

		$item = 'Unknown type: '.$this->fieldConfig['config']['form_type'].'<br />';

		return $item;
	}
}
?>