<?php

class t3lib_TCEforms_FormTest extends tx_phpunit_testcase {
	/**
	 * @test
	 */
	public function addRecordInjectsElementIdentifierStackToRecordObject() {
		$uid = 1;
		$fakeRecord = array(
			'uid' => $uid,
			'pid' => 0
		);
		$formObject = new t3lib_TCEforms_Form();
		$recordObject = $formObject->addRecord('pages', $fakeRecord);

		$identifierStack = $this->readAttribute($recordObject, 'elementIdentifierStack');
		$this->assertEquals(array('pages', $uid), $identifierStack);
	}

	/**
	 * @test
	 * @dataProvider createElementIdentifierDataProviderForElements
	 */
	public function createElementIdentifierCreatesCorrectIdentifiersForElements($elementIdentifierPrefix,
	  array $elementIdentifierStack, $fieldName, $type, $expectedIdentifier, $message = '') {

		$formObject = new t3lib_TCEforms_Form();
		$formObject->setElementIdentifierPrefix($elementIdentifierPrefix)
		           ->setElementIdentifierStack($elementIdentifierStack);
		$mockedElement = $this->getMockForAbstractClass('t3lib_TCEforms_Element_Abstract', array($fieldName, array()));

		$this->assertEquals($expectedIdentifier, $formObject->createElementIdentifier($mockedElement, $type), $message);
	}

	public static function createElementIdentifierDataProviderForElements() {
		return array(
			array(
				'formname',
				array(
					'table',
					'uid'
				),
				'testfield',
				'name',
				'formname[table][uid][testfield]'
			),
			array(
				'formname',
				array(
					'table',
					'uid'
				),
				'testfield',
				'id',
				'formname-table-uid-testfield'
			)
		);
	}
}