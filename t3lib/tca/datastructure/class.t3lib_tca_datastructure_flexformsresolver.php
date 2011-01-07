<?php

require_once(PATH_t3lib.'tca/datastructure/class.t3lib_tca_datastructure_resolver.php');
require_once(PATH_t3lib.'tca/class.t3lib_tca_datastructure.php');

/**
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_TCA_DataStructure_FlexFormsResolver extends t3lib_TCA_DataStructure_Resolver {
	/**
	 *
	 * @var t3lib_flexformtools
	 */
	protected static $flexFormTools;

	/**
	 * Resolves a FlexForm XML data structure into a matching DataStructure object.
	 *
	 * @param t3lib_TCEforms_Element_Flex $flexElementObject
	 * @return t3lib_TCA_DataStructure The DataStructure object
	 */
	public static function resolveDataStructure(t3lib_TCEforms_Element_Flex $flexElementObject) {
		if (!is_object(self::$flexFormTools)) {
			self::$flexFormTools = new t3lib_flexformtools();
		}

		$record = $flexElementObject->getRecordObject()->getRecordData();
		$table = $flexElementObject->getRecordObject()->getTable();
		$field = $flexElementObject->getFieldname();
		$fieldConfig = $flexElementObject->getFieldSetup();

		$dataStructureArray = t3lib_BEfunc::getFlexFormDS($fieldConfig['config'], $record, $table);

		if (!is_array($dataStructureArray)) {
			throw new RuntimeException('Decoding FlexForm DataStructure failed: ' . $dataStructureArray);
		}

		$TCAcolumnsArray = self::extractColumnsFromDataStructureArray($dataStructureArray);
		$TCAcontrolArray = self::extractControlInformationFromDataStructureArray($dataStructureArray);
		$TCAsheetsArray = self::extractSheetInformation($dataStructureArray);

		$TCAentry = array(
			'ctrl' => $TCAcontrolArray,
			'columns' => $TCAcolumnsArray,
			'sheets' => $TCAsheetsArray,
			'meta' => $dataStructureArray['meta']
		);

		$dataStructureObject = new t3lib_TCA_FlexFormDataStructure($TCAentry);

		return $dataStructureObject;
	}

	protected static function extractColumnsFromDataStructureArray($dataStructureArray) {
		$TCAcolumns = array();
		foreach ($dataStructureArray['sheets'] as $sheet) {
			foreach ($sheet['ROOT']['el'] as $elementName => $elementConfig) {
				$TCAcolumns[$elementName] = $elementConfig['TCEforms'];
			}
		}

		return $TCAcolumns;
	}


	/**
	 * Extracts the information about the sheets this record offers and the fields they have.
	 *
	 * This function is the only one neccessary for extracting record structure information, as
	 * there is only one type with flexform records (instead of possibly many types and subtypes with
	 * TCA based records)
	 *
	 * @param array $dataStructureArray
	 * @return array
	 */
	protected static function extractSheetInformation($dataStructureArray) {
		$sheets = array();
		foreach ($dataStructureArray['sheets'] as $sheetName => $sheet) {
			$currentSheet = array(
				'name' => $sheetName,
				'title' => $GLOBALS['LANG']->sL($sheet['ROOT']['TCEforms']['sheetTitle']),
				'elements' => array()
			);

			foreach (array_keys($sheet['ROOT']['el']) as $elementName) {
				$currentSheet['elements'][] = $elementName;
			}

			$sheets[] = $currentSheet;
		}

		return $sheets;
	}

	/**
	 * Extracts all available information for a TCA control array from a given Flexform XML DataStructure.
	 *
	 * @param unknown_type $dataStructureArray
	 * @return unknown_type
	 */
	protected static function extractControlInformationFromDataStructureArray($dataStructureArray) {
		$TCAcontrolInformation = array();

		return $TCAcontrolInformation;
	}
}

?>