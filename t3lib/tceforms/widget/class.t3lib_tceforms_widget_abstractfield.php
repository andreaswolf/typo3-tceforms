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
 * Base class for all field type implementations; some subclasses may also implement _ContainerWidget if they allow
 * widgets inside them (especially type=flex, inline or flexsection/flexcontainer
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
abstract class t3lib_TCEforms_Widget_AbstractField extends t3lib_TCEforms_Widget_Abstract implements t3lib_TCEforms_FieldWidget {
	/**
	 * The name of this field in the data structure.
	 *
	 * TODO: does this always represent the lowest level or does it contain upper levels if there may be ambiguities
	 *       (e.g. in FlexForms, where the same field name may appear on different sheets)?
	 *
	 * @var string
	 */
	protected $fieldName;

	public function getIdentity() {
		// TODO: Implement getIdentity() method.
	}

	public function getFieldName() {
		return $this->fieldName;
	}

	public function setFieldName($fieldName) {
		$this->fieldName = $fieldName;
	}

	public function hasWizards() {
		// TODO: Implement hasWizards() method.
	}

	public function getWizards() {
		// TODO: Implement getWizards() method.
	}

	public function addWizard() {
		// TODO: Implement addWizard() method.
	}
}

?>