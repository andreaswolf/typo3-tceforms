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

	/**
	 * @test
	 */
	public function initCallsParentFormObjectToCreateIdentifiers() {
		/* @var $elementObject t3lib_TCEforms_Element_Abstract */
		$elementObject = $this->getMockForAbstractClass('t3lib_TCEforms_Element_Abstract', array($fieldName, array()),
			'', TRUE);
		$mockedParentForm = $this->getMock('t3lib_TCEforms_Form');
		$mockedParentForm->expects($this->atLeastOnce())->method('createElementIdentifier');
		$mockedContext = $this->getMock('t3lib_TCEforms_Form');
		$mockedContext->expects($this->any())->method('createElementIdentifier')->will($this->throwException(new RuntimeException('Called createElementIdentifier() on contextObject.')));

		$elementObject->setParentFormObject($mockedParentForm)
		              ->setContextObject($mockedContext)
		              ->setRecordObject($this->getMock('t3lib_TCEforms_Record', array(), array(), '', FALSE))
		              ->setElementIdentifierStack(array('table', 'uid'));

		$elementObject->init();
	}
}