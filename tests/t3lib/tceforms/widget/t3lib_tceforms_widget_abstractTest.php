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
 * Testcase for the abstract widget
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms_Widget_AbstractTest extends Tx_Phpunit_TestCase {

	/**
	 * @var t3lib_TCEforms_Widget_Abstract
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = $this->getMockForAbstractClass('t3lib_TCEforms_Widget_Abstract');
	}

	/**
	 * @test
	 */
	public function parentWidgetMayBeSetAndRetrieved() {
		$mockedWidget = $this->getMock('t3lib_TCEforms_ContainerWidget');

		$this->assertFalse($this->fixture->hasParentWidget(), 'Widget said it has parent widget though it should not have one now.');

		$this->fixture->setParentWidget($mockedWidget);
		$this->assertTrue($this->fixture->hasParentWidget(), 'Widget said it has no parent widget though it should have one.');

		$this->assertEquals($mockedWidget, $this->fixture->getParentWidget(), 'Widget did not correctly return its parent widget.');
	}
}

?>