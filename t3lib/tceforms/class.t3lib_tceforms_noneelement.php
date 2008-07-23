<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_NoneElement extends t3lib_TCEforms_AbstractElement {
	protected $item;

	public function init($table, $field, $row, &$PA) {

		$this->item = $item;
	}

	public function render() {
		return $this->item;
	}
}
