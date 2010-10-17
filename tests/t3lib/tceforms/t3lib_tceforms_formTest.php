<?php

class t3lib_TCEforms_FormTest extends tx_phpunit_testcase {
	/**
	 * @test
	 * @dataProvider createElementIdentifierDataProvider
	 */
	public function createElementIdentifierCreatesCorrectIdentifiers($elementIdentifierPrefix,
	  array $elementIdentifierStack, $type, $elementIdentifier, $message = '') {

		$formObject = new t3lib_TCEforms_Form();
		$formObject->setElementIdentifierPrefix($elementIdentifierPrefix)
		           ->setElementIdentifierStack($elementIdentifierStack);

		$this->assertEquals($elementIdentifier, $formObject->createElementIdentifier($elementIdentifierStack, $type), $message);
	}

	public static function createElementIdentifierDataProvider() {
		return array(
			array(
				'formname',
				array(
					'table',
					'uid'
				),
				'name',
				'formname[table][uid]'
			),
			array(
				'formname',
				array(
					'table',
					'uid'
				),
				'id',
				'formname-table-uid'
			),
			array(
				'formname',
				array(
				),
				'name',
				'formname',
				'Empty braces [] are added if element identifier stack is empty.'
			)
		);
	}
}