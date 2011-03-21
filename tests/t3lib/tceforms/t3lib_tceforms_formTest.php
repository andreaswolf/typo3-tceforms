<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Testcase for the Form class in TCEforms.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms_FormTest extends Tx_Phpunit_TestCase {
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