<?php

require_once(PATH_t3lib.'tca/datastructure/class.t3lib_tca_datastructure_resolver.php');
require_once(PATH_t3lib.'tca/class.t3lib_tca_datastructure.php');

class t3lib_TCA_DataStructure_TCAResolver extends t3lib_TCA_DataStructure_Resolver {
	public static function resolveDataStructure($table) {
		t3lib_div::loadTCA($table);
		$dataStructureObject = new t3lib_TCA_DataStructure($GLOBALS['TCA'][$table]);

		return $dataStructureObject;
	}
}

?>