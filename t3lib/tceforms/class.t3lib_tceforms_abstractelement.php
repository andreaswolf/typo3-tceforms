<?php

abstract class t3lib_TCEforms_AbstractElement {
	/**
	 * @var t3lib_TCEforms  The parent TCEforms object
	 */
	protected $TCEformsObject;
	
	public function construct() {
		
	}
	
	abstract public function render();
	
	
	public function setTCEformsObject(t3lib_TCEforms $TCEformsObject) {
		$this->TCEformsObject = $TCEformsObject;
	}
}