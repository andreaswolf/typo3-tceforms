<?php

class t3lib_TCEforms_Container_SheetTest extends tx_phpunit_testcase {
	/**
	 * @test
	 */
	public function elementIdentifierStackIsExtendedIfSheetHasName() {
		$sheetName = 'sheetName';
		$sheetObject = new t3lib_TCEforms_Container_Sheet(uniqid(), uniqid(), $sheetName);
		$sheetObject->setElementIdentifierStack(array('elementBeforeSheet'));

		$elementIdentifierStack = $this->readAttribute($sheetObject, 'elementIdentifierStack');
		$this->assertEquals(2, count($elementIdentifierStack));
		$this->assertEquals($sheetName, $elementIdentifierStack[1]);
	}

	/**
	 * @test
	 */
	public function elementIdentifierStackIsUntouchedIfSheetHasNoName() {
		$sheetObject = new t3lib_TCEforms_Container_Sheet(uniqid(), uniqid(), '');
		$sheetObject->setElementIdentifierStack(array('elementBeforeSheet'));

		$elementIdentifierStack = $this->readAttribute($sheetObject, 'elementIdentifierStack');
		$this->assertEquals(1, count($elementIdentifierStack));
	}
}