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
 * Testcase for the FlexForm data structure abstraction class in TCA.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCA_FlexFormDataStructureTest extends Tx_Phpunit_TestCase {

	public function localizationIsEnabledIfMetaValueIsNotSet_dataProvider() {
		return array(
			'langDisabled = 1' => array(
				array('langDisabled' => 1),
				FALSE
			),
			'langDisabled = 0' => array(
				array('langDisabled' => 0),
				TRUE
			),
			'valueNotSet' => array(
				array(),
				TRUE
			),
		);
	}

	/**
	 * This tests whether the entry <langDisable>1</langDisable> inside the FF XML is recognized properly.
	 *
	 * @test
	 * @covers t3lib_TCA_FlexFormDataStructure::isLocalizationEnabled
	 * @dataProvider localizationIsEnabledIfMetaValueIsNotSet_dataProvider
	 */
	public function localizationIsEnabledIfMetaValueIsNotSet($mockedMeta, $expectedValue) {
		$fixture = new t3lib_TCA_FlexFormDataStructure(array('meta' => $mockedMeta, 'sheets' => array()));
		$this->assertEquals($expectedValue, $fixture->isLocalizationEnabled());
	}
}