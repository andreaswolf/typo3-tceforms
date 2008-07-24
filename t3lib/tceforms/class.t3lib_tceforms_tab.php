<?php

require_once(PATH_t3lib.'interfaces/interface.t3lib_tceforms_element.php');

class t3lib_TCEforms_Tab implements t3lib_TCEforms_Element {
	/**
	 * @var array  The sub-elements of this tab
	 */
	protected $childObjects;

	protected $identString;

	public function init($identString, $header) {
		$this->identString = $identString;

		$this->childObjects = array();
	}

	public function addChildObject(t3lib_TCEforms_AbstractElement $childObject) {
		$this->childObjects[] = $childObject;
	}

	public function render() {

	}
}

?>