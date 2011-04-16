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
 *  the Freef Software Foundation; either version 2 of the License, or
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
 * Test case for the field proxy class
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms_Widget_FieldProxyTest extends Tx_Phpunit_TestCase {
	/**
	 * @test
	 */
	public function replaceByReplacesWidgetInParentWidget() {
		$fixture = new t3lib_TCEforms_Widget_FieldProxy(array());
		$newFixture = $this->getMock('t3lib_TCEforms_FieldWidget');

		/** @var $parentWidget t3lib_TCEforms_ContainerWidget */
		$parentWidget = $this->getMock('t3lib_TCEforms_ContainerWidget');
		$parentWidget->expects($this->once())->method('replaceChildWidget')->with($this->equalTo($fixture), $this->equalTo($newFixture));

		$fixture->setParentWidget($parentWidget);
		$fixture->replaceBy($newFixture);
	}
}

?>