<?php

class t3lib_TCA_FlexFormDataStructure extends t3lib_TCA_DataStructure {

	public function __construct($TCAinformation) {
		$this->fields = $TCAinformation['columns'];
		$this->control = $TCAinformation['ctrl'];
		$this->palettes = $TCAinformation['palettes'];
		$this->meta = $TCAinformation['meta'];

		$typeObject = $this->createTypeObjectFromSheets($TCAinformation['sheets']);
		$this->types[1] = $typeObject;
		$this->definedTypeValues = array(1);
	}

	protected function createTypeObjectFromSheets($sheets) {
		$typeObject = t3lib_TCA_DataStructure_Type::createFromSheets($this, 1, $sheets);

		return $typeObject;
	}

	public function getMetaValue($key) {
		return array_key_exists($key, $this->meta) ? $this->meta[$key] : '';
	}
}

?>