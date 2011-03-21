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
 * Testcase for the FlexForm class in TCEforms.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms_FlexformTest extends Tx_Phpunit_TestCase {
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