<?php

class t3lib_TCA_DataStructure_TCAResolver extends t3lib_TCA_DataStructure_Resolver {
	public static function resolveDataStructure($table) {
		t3lib_div::loadTCA($table);
		$dataStructureObject = new t3lib_TCA_DataStructure($GLOBALS['TCA'][$table]);

		return $dataStructureObject;
	}
}

?>
