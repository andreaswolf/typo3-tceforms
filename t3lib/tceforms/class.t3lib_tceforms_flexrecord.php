<?php

class t3lib_TCEforms_FlexRecord extends t3lib_TCEforms_Record {

	public function __construct(array $recordData, t3lib_TCA_DataStructure $dataStructure, $language = 'DEF') {
		parent::__construct('', $recordData, array(), $dataStructure);

		$this->language = $language;
	}
}

?>