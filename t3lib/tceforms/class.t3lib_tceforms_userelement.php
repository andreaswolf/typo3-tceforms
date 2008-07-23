<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_UserElement extends t3lib_TCEforms_AbstractElement {
	protected $item;

	public function init($table, $field, $row, &$PA) {
		$PA['table']=$table;
		$PA['field']=$field;
		$PA['row']=$row;

		$PA['pObj']=&$this->TCEformsObject;

		$item = t3lib_div::callUserFunction($PA['fieldConf']['config']['userFunc'],$PA,$this->TCEformsObject);

		$this->item = $item;
	}

	public function render() {
		return $this->item;
	}
}
