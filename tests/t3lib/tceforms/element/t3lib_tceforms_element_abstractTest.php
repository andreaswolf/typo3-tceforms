<?php

class t3lib_TCEforms_Element_AbstractTest extends tx_phpunit_testcase {
	/**
	 * @test
	 */
	public function elementIdentifierStackGetsExtended() {
		$fieldName = 'field';
		$elementObject = $this->getMockForAbstractClass('t3lib_TCEforms_Element_Abstract', array($fieldName, array()),
			'', TRUE);
		$elementObject->setElementIdentifierStack(array('table', 'uid'));

		$elementIdentifierStack = $this->readAttribute($elementObject, 'elementIdentifierStack');
		$this->assertEquals(3, count($elementIdentifierStack));
		$this->assertEquals($fieldName, $elementIdentifierStack[2]);
	}
}