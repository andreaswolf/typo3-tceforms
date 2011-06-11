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
 * A FlexForm data structure. The information for this class is extracted from XML by the FlexForm resolver class.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_DataStructure_FlexForm extends t3lib_DataStructure_Tca {
	/**
	 * Determines whether localization is enabled or not. This value comes from meta[langDisabled] (inverted, of course)
	 * The default value is FALSE
	 *
	 * @var boolean
	 */
	protected $localizationEnabled = FALSE;

	/**
	 * The localization method for this record. This is determined by the value of meta[langChildren].
	 * The default value is 0.
	 *
	 * value 0 means one record for each language (language codes on language part)  example: [lEN][fieldname][vDEF]
	 * value 1 means all languages in one record  (language codes on value part)     example: [lDEF][fieldname][vEN]
	 *
	 * @var integer
	 */
	protected $localizationMethod;

	public function __construct($TCAinformation) {
		$this->fields = $TCAinformation['columns'];
		$this->control = $TCAinformation['ctrl'];
		$this->palettes = $TCAinformation['palettes'];
		$this->meta = $TCAinformation['meta'];
			// TODO extract fields from sheets, store them + a representation of how the form should look
		$this->sheets = $TCAinformation['sheets'];

		$this->setLocalizationConfig();

		$this->definedTypeValues = array("0");
	}

	/**
	 * Checks whether localization is enabled and sets the localization mode if it is enabled.
	 *
	 * @return void
	 */
	protected function setLocalizationConfig() {
		$this->localizationEnabled = $this->meta['langDisabled'] ? FALSE : TRUE;
		$this->localizationMethod = $this->meta['langChildren'] ? 1 : 0;
	}

	public function getDisplayConfigurationForRecord(t3lib_TCEforms_Record $record) {
		$displayConfiguration = t3lib_TCA_DisplayConfiguration::createFromSheets($this, $this->types["0"]->getSheets());

		return $displayConfiguration;
	}

	protected function createTypeObject($typeValue) {
		// TODO: create type object from field information here
		$this->types[$typeValue] = t3lib_div::makeInstance('t3lib_DataStructure_Type', $this, $typeValue, $this->rawTypes[$typeValue]);
	}

	public function getMetaValue($key) {
		return array_key_exists($key, $this->meta) ? $this->meta[$key] : '';
	}

	/**
	 * @return bool TRUE if localization is enabled.
	 */
	public function isLocalizationEnabled() {
		return $this->localizationEnabled;
	}

	/**
	 * @return int The localization method; 0 for localization on "structure level", 1 for localization on field level
	 */
	public function getLocalizationMethod() {
		return $this->localizationMethod;
	}
}

?>
