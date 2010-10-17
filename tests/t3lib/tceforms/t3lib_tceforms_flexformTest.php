<?php

class t3lib_TCEforms_FlexformTest extends tx_phpunit_testcase {
	/**
	 * @test
	 * @dataProvider elementIdentifiersForFlexformElementsAreCreatedCorrectlyDataProvider
	 */
	public function elementIdentifiersForFlexformElementsAreCreatedCorrectly($elementIdentifierPrefix, array $elementIdentifierStack,
		$language, $fieldName, $localizationMethod, $sheetName, $identifierType, $expectedIdentifier) {

		$mockedSheet = $this->getMock('t3lib_TCEforms_Container_Sheet', array('getName'), array(uniqid(), uniqid()));
		$mockedSheet->expects($this->any())->method('getName')->will($this->returnValue($sheetName));
		$mockedRecord = $this->getMock('t3lib_TCEforms_FlexRecord', array('getLanguage'), array(), '', FALSE);
		$mockedRecord->expects($this->atLeastOnce())->method('getLanguage')->will($this->returnValue($language));
		$mockedElement = $this->getMockForAbstractClass('t3lib_TCEforms_Element_Abstract', array($fieldName));
		$mockedElement->setContainer($mockedSheet)
		              ->setRecordObject($mockedRecord);
		$formObject = new t3lib_TCEforms_Flexform();
		$formObject->setElementIdentifierStack($elementIdentifierStack);

		$this->assertEquals($expectedIdentifier, $formObject->createElementIdentifier($mockedElement, $identifierType));
	}

	public function elementIdentifiersForFlexformElementsAreCreatedCorrectlyDataProvider() {
		return array(
			array(
				'data',
				array(
					'table',
					'uid',
					'field'
				),
				'DE',
				'flexFormfield',
				0,
				'sheet',
				'name',
				'data[table][uid][field][sheet][lDE][flexFormfield][vDEF]'
			)
		);
	}
}