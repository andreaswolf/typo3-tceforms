<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_UserElement extends t3lib_TCEforms_AbstractElement {
	protected $item;

	public function render() {
		// TODO: rebuild $PA here because it does not exist by default anymore

		$PA['table']=$table;
		$PA['field']=$field;
		$PA['row']=$row;

		$PA['pObj']=&$this->TCEformsObject;

		$item = t3lib_div::callUserFunction($this->fieldConfig['config']['userFunc'], $PA, $this->TCEformsObject);

		return $item;
	}
}
