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
 * Testcase for the data structure abstraction class in TCA.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCA_RecordTest extends Tx_Phpunit_TestCase {
	/**
	 * @test
	 * @covers t3lib_TCA_Record::getValue
	 * @covers t3lib_TCA_Record::getTable
	 * @covers t3lib_TCA_Record::getUid
	 * @covers t3lib_TCA_Record::getRecordData
	 */
	public function recordDataMayBeRetrievedFromClass() {
		$recordData = array(
			'foo' => uniqid(),
			'bar' => uniqid(),
			'uid' => rand(1, 100)
		);
		$table = uniqid();

		$mockedDataStructure = $this->getMock('t3lib_DataStructure_Tca', array(), array(), '', FALSE);

		$fixture = new t3lib_TCA_Record($table, $recordData, $mockedDataStructure);

		$this->assertEquals($recordData['foo'], $fixture->getValue('foo'));
		$this->assertEquals($recordData['bar'], $fixture->getValue('bar'));
		$this->assertEquals($recordData['uid'], $fixture->getUid());
		$this->assertEquals($recordData, $fixture->getRecordData());

		$this->assertEquals($table, $fixture->getTable());
	}
}


?>