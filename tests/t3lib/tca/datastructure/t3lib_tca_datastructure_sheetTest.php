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
 * Testcase for the sheet abstraction class in TCA.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCA_DataStructure_SheetTest extends Tx_Phpunit_TestCase {
	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Sheet::addElement
	 */
	public function addElementAddsObjectsInCorrectOrderToInternalArray() {
		/** @var $fixture t3lib_TCA_DataStructure_Sheet */
		$fixture = $this->getMock('t3lib_TCA_DataStructure_Sheet', NULL, array(), '', FALSE);

		$el1 = new StdClass();
		$el1->index = 1;
		$el2 = new StdClass();
		$el2->index = 2;

		$fixture->addElement($el1);
		$fixture->addElement($el2);

		$this->assertEquals(array($el1, $el2), $fixture->getElements());
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Sheet::addElement
	 */
	public function addElementCanInsertElementAtGivenIndex() {
		/** @var $fixture t3lib_TCA_DataStructure_Sheet */
		$fixture = $this->getMock('t3lib_TCA_DataStructure_Sheet', NULL, array(), '', FALSE);

		$el1 = new StdClass();
		$el1->index = 1;
		$el2 = new StdClass();
		$el2->index = 2;
		$el3 = new StdClass();
		$el3->index = 3;

		$fixture->addElement($el1);
		$fixture->addElement($el2);
		$fixture->addElement($el3, 1);
		$this->assertEquals(array($el1, $el3, $el2), $fixture->getElements());

		$el4 = new StdClass();
		$el4->index = 4;
		$fixture->addElement($el4, 1);

		$this->assertEquals(array($el1, $el4, $el3, $el2), $fixture->getElements());
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Sheet::removeElement
	 */
	public function removeElementCorrectlyRemovesElement() {
		/** @var $fixture t3lib_TCA_DataStructure_Sheet */
		$fixture = $this->getMock('t3lib_TCA_DataStructure_Sheet', NULL, array(), '', FALSE);

		$el = array();
		for ($i = 0; $i <= 2; ++$i) {
			$el[$i] = new StdClass();
			$el[$i]->index = $i;

			$fixture->addElement($el[$i]);
		}

		$this->assertEquals(3, count($fixture->getElements()));

		$fixture->removeElement($el[1]);
		$this->assertEquals(2, count($fixture->getElements()));
		$this->assertEquals(array($el[0], $el[2]), $fixture->getElements());
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Sheet::getElementIndex
	 */
	public function getElementIndexReturnsCorrectIndexForGivenObject() {
		/** @var $fixture t3lib_TCA_DataStructure_Sheet */
		$fixture = $this->getMock('t3lib_TCA_DataStructure_Sheet', NULL, array(), '', FALSE);

		$el1 = new StdClass();
		$el1->index = 1;
		$el2 = new StdClass();
		$el2->index = 2;
		$el3 = new StdClass();
		$el3->index = 3;

		$fixture->addElement($el1);
		$fixture->addElement($el2);
		$fixture->addElement($el3);

		$this->assertEquals(0, $fixture->getElementIndex($el1));
		$this->assertEquals(1, $fixture->getElementIndex($el2));
		$this->assertEquals(2, $fixture->getElementIndex($el3));
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Sheet::getElementIndex
	 */
	public function getElementIndexReturnsCorrectIndexForGivenFieldName() {
		/** @var $fixture t3lib_TCA_DataStructure_Sheet */
		$fixture = $this->getMock('t3lib_TCA_DataStructure_Sheet', NULL, array(), '', FALSE);

		$el = array();
		for ($i = 0; $i <= 2; ++$i) {
			$el[$i] = $this->getMock('t3lib_TCA_DataStructure_Field', array(), array(), '', FALSE);
			$el[$i]->expects($this->any())->method('getName')->will($this->returnValue('field' . $i));

			$fixture->addElement($el[$i]);
		}

			// we're not testing this inside the loop because we want to be sure that it works even if our desired
			// element isn't the last one
		$this->assertEquals(0, $fixture->getElementIndex('field0'));
		$this->assertEquals(1, $fixture->getElementIndex('field1'));
		$this->assertEquals(2, $fixture->getElementIndex('field2'));
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_Sheet::getElementIndex
	 */
	public function getElementIndexThrowsExceptionForInvalidElement() {
		$this->setExpectedException('InvalidArgumentException', '', 1300741441);

		/** @var $fixture t3lib_TCA_DataStructure_Sheet */
		$fixture = $this->getMock('t3lib_TCA_DataStructure_Sheet', NULL, array(), '', FALSE);

		$fixture->getElementIndex(1);
	}
}

?>