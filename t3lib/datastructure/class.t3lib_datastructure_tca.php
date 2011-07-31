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
 * Abstraction class for the data structures used in the TYPO3 Table Configuration Array (TCA).
 *
 * This object is read-only after it has been constructed.
 *
 * @see Document "TYPO3 Core API", section "$TCA array reference"
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_DataStructure_Tca extends t3lib_DataStructure_Abstract {
	/**
	 * Cache for field objects created by getFieldObject
	 *
	 * @var t3lib_DataStructure_Tcas[]
	 */
	protected $fieldObjects = array();

	/**
	 * The configuration objects of all defined palettes
	 * This holds information from e.g. $TCA[$tableName]['palettes']
	 *
	 * @var t3lib_DataStructure_Element_Palette[]
	 */
	protected $palettes = array();

	/**
	 * The raw information on the types for this data structure. See $types for a parsed version.
	 * Parsing is done automatically on access.
	 * This holds information from e.g. $TCA[$tableName]['types']
	 *
	 * @var array
	 */
	protected $rawTypes = array();

	/**
	 * @var array<t3lib_TCA_DisplayConfiguration>
	 */
	protected $displayConfigurations = array();

	/**
	 * The widget blocks defined for this data structure.
	 *
	 * @var array
	 */
	protected $widgetBlocks = array();

	/**
	 * Constructor for this class.
	 *
	 * Expects a TCA configuration as used in the normal PHP-based TCA. It has to have these sections: ctrl, columns,
	 * palettes, types; each should be an array as used in the TCA.
	 *
	 * @param array $TCAinformation The information from the Table Control Array
	 * @return void
	 */
	public function __construct($table, $TCAinformation) {
		$this->identifier = $table;
		$this->fields = $TCAinformation['columns'];
		$this->control = $TCAinformation['ctrl'];
		$this->palettes = $TCAinformation['palettes'];
		$this->widgetBlocks = $TCAinformation['widgetBlocks'];

		foreach ($this->widgetBlocks as $blockName => $blockConfiguration) {
			$this->widgetBlocks[$blockName]['widgetConfiguration'] = self::parseWidgetConfiguration($blockConfiguration['widgetConfiguration']);
		}

		$this->rawTypes = $TCAinformation['types'];
		$this->definedTypeValues = array_keys($this->rawTypes);
	}

	public function getDisplayConfigurationForRecord(t3lib_TCEforms_Record $record, array $fieldList = NULL) {
		// TODO: check if record has a field list (hasFieldList()/getFieldList()) - if yes, create a display
		// config for this
		$fieldAddList = array();
		$subtypeExcludeList = array();
		$bitmaskExcludeList = array();

		if ($this->hasTypeField() && !$record->isNew()) {
			$typeValue = $record->getValue($this->getTypeField());

			if (!$this->typeExists($typeValue)) {
				$typeValue = "1";
			}
		} else {
			$typeValue = $this->getDefaultTypeForRecord($record);
		}

		/** @var $typeConfiguration t3lib_DataStructure_Type */
		$typeConfiguration = $this->getTypeConfiguration($typeValue);

		if ($typeConfiguration->hasSubtypeValueField()) {
			$subtypeValue = $record->getValue($typeConfiguration->getSubtypeValueField());
		}

		if ($typeConfiguration->hasBitmaskValueField()) {
			$bitmaskValue = $record->getValue($typeConfiguration->bitmaskValueField());
		}

		$displayConfigurationHash = md5($typeValue . ';' . $subtypeValue . ';' . $bitmaskValue . ';' . implode(',', (array)$fieldList));
		if (array_key_exists($displayConfigurationHash, $this->displayConfigurations)) {
			return $this->displayConfigurations[$displayConfigurationHash];
		}

		// Create config
		if (isset($subtypeValue)) {
			$subtypeExcludeList = $typeConfiguration->getExcludeListForSubtype($subtypeValue);
			$fieldAddList = $typeConfiguration->getAddListForSubtype($subtypeValue);
		}
		if (isset($bitmaskValue)) {
			$bitmaskExcludeList = $typeConfiguration->getBitmaskExcludeList($bitmaskValue);
		}

		$fieldExcludeList = array_merge($subtypeExcludeList, $bitmaskExcludeList);

		$displayConfiguration = t3lib_TCA_DisplayConfiguration::createFromConfiguration($this, $typeConfiguration, $fieldAddList, $fieldExcludeList, $fieldList);

		$this->displayConfigurations[$displayConfigurationHash] = $displayConfiguration;
		//print_r($displayConfiguration);
		return $displayConfiguration;
	}

	/**
	 * Returns the object representation of a TCA field.
	 *
	 * This is only the bare variant of this field, as defined in the TCA columns section. Any special
	 * configuration added in type configurations has to be applied separately
	 *
	 * @param string $fieldName
	 * @return t3lib_DataStructure_Element_Field
	 *
	 * @access package
	 *
	 * @TODO add caching
	 */
	public function getFieldObject($fieldName) {
		if (!$this->fieldObjects[$fieldName]) {
			/** @var $fieldObject t3lib_DataStructure_Element_Field */
			$fieldObject = t3lib_div::makeInstance('t3lib_DataStructure_Element_Field', $fieldName);
			$fieldObject->setDataStructure($this);

			$this->fieldObjects[$fieldName] = $fieldObject;
		}

		return $this->fieldObjects[$fieldName];
	}

	/**
	 * Returns TRUE if a certain palette exists in this datastructure
	 *
	 * @param integer $paletteName The name of the palette as used in TCA configuration
	 */
	public function hasPalette($paletteName) {
		return array_key_exists($paletteName, $this->palettes);
	}

	/**
	 * Returns configuration for a palette
	 *
	 * @param   integer  $paletteName  The name of the palette as used in TCA configuration
	 * @return  array  The palette configuration as specified in TCA
	 */
	public function getPaletteConfiguration($paletteName) {
		if (!$this->hasPalette($paletteName)) {
			throw new InvalidArgumentException("Palette '$paletteName' does not exist.");
		}
		// TODO create palette

		return $this->palettes[$paletteName];
	}

	/**
	 * Returns a value from the interface definition part of the TCA
	 *
	 * @param string $key
	 * @return string
	 */
	public function getInterfaceValue($key) {
		//
	}

	public function hasLanguageField() {
		return $this->hasControlValue('languageField');
	}

	public function getLanguageField() {
		return $this->getControlValue('languageField');
	}

	/**
	 * Returns the default type number for a record.
	 *
	 * If a type field exists, a default value may be set. If it is not or there is no type field,
	 * the default value is always '0'.
	 *
	 * @param t3lib_TCEforms_Record $record
	 * @return mixed The default type value for the record
	 */
	protected function getDefaultTypeForRecord(t3lib_TCEforms_Record $record) {
		if ($this->hasTypeField()) {
			$typeFieldConfiguration = $this->getFieldConfiguration($this->getTypeField());
			if (array_key_exists('default', $typeFieldConfiguration['config'])) {
				return $typeFieldConfiguration['config']['default'];
			}
		}

		return '0';
	}

	public function getPossibleTypeValues() {
		return $this->definedTypeValues;
	}

	protected function createTypeObject($typeValue) {
		$this->types[$typeValue] = t3lib_div::makeInstance('t3lib_DataStructure_Type', $this, $typeValue, $this->rawTypes[$typeValue]);
	}

	public function createElementObject($name, $label, $specialConfiguration) {
		$object = new t3lib_DataStructure_Element_Field($this, $name);

		return $object;
	}

	/**
	 * Parses a widget configuration from TCA to the unified array based widget tree format that is used by WidgetBuilder
	 * in TCEforms.
	 *
	 * @param mixed $widgetConfiguration
	 * @return array
	 *
	 * @throws RuntimeException  if an invalid JSON string is passed
	 * @throws InvalidArgumentException  if an invalid widget configuration is given
	 */
	public static function parseWidgetConfiguration($widgetConfiguration) {
		if (is_string($widgetConfiguration)) {
			$parsedConfiguration = json_decode($widgetConfiguration, TRUE);
			if ($parsedConfiguration === NULL) {
				throw new RuntimeException('Decoding JSON widget configuration failed: ' . json_last_error(), 1303662376);
			}
		} elseif (is_array($widgetConfiguration)) {
			$parsedConfiguration = $widgetConfiguration;
		} else {
			throw new RuntimeException('Invalid widget configuration format: expected JSON encoded string or array, got '
			                           . gettype($widgetConfiguration), 1303845871);
		}

		return $parsedConfiguration;
	}

	/********************************************
	 * Widget block handling
	 ********************************************/

	/**
	 * Returns TRUE if a specified widget block exists in this data structure
	 *
	 * @param string $name The widget block name
	 * @return bool
	 */
	public function hasWidgetBlock($name) {
		return isset($this->widgetBlocks[$name]);
	}

	/**
	 * Gets the configuration array for a widget block.
	 *
	 * @param string $name The widget block name
	 * @return array
	 */
	public function getWidgetBlock($name) {
		return $this->widgetBlocks[$name];
	}

	/**
	 * Returns the widget configuration tree array for a block.
	 *
	 * @param string $name
	 * @return array
	 */
	public function getWidgetConfigurationForBlock($name) {
		return $this->widgetBlocks[$name]['widgetConfiguration'];
	}

	/********************************************
	 * Showitem string handling
	 ********************************************/

	/**
	 * Converts a showitem string from TCA to an array-based tree of widget configurations.
	 *
	 * @param string $showitemString The showitem string as taken from TCA[$table][types]
	 * @return array
	 */
	public function convertTypeShowitemStringToWidgetConfigurationArray($showitemString) {
		return $this->convertShowitemStringToWidgetConfigurationArray($showitemString, '--div--', 'vbox', 'tab', 'tabpanel',
		                                                              array($this, 'typeShowitemElementCallbackHandler'));
	}

	/**
	 * Converts the showitem string from a palette configuration to an array-based tree of widget configurations
	 *
	 * @param  $showitemString The showitem string as taken from TCA[$table][palettes]
	 * @return array
	 */
	public function convertPaletteShowitemStringToWidgetConfigurationArray($showitemString) {
		return $this->convertShowitemStringToWidgetConfigurationArray($showitemString, '--linebreak--', 'hbox', 'hbox', 'vbox');
	}

	/**
	 * Internal helper for converting a showitem string to a widget configuration array.
	 *
	 * @param string $showitemString  The showitem string, directly taken from $TCA
	 * @param string $dividerElement  The pseudo-field used to divide the fields into different groups (tabs, lines)
	 * @param string $wrapTypeWithoutDivider  The widget type to wrap around all fields if no divider is present
	 * @param string $elementWrapTypeWithDivider  The widget type to wrap around the fields in one group
	 * @param string $outerWrapTypeWithDivider  The widget type to wrap around all groups
	 * @param callback $elementCallback  Callback for handling elements. Arguments are: the complete item string
	 * @return array
	 */
	protected function convertShowitemStringToWidgetConfigurationArray($showitemString, $dividerElement,
		$wrapTypeWithoutDivider, $elementWrapTypeWithDivider, $outerWrapTypeWithDivider, $elementCallback = NULL) {
		$items = t3lib_div::trimExplode(',', $showitemString);

		$hasDivider = (substr_count($showitemString, $dividerElement) > 0);

		$widgetConfigurations = array();
		$lines = array();
		foreach ($items as $item) {
			list ($fieldname) = t3lib_div::trimExplode(';', $item);

			switch ($fieldname) {
				case $dividerElement:
					$lines[] = array(
						'type' => $elementWrapTypeWithDivider,
						'items' => $widgetConfigurations
					);
					$widgetConfigurations = array();

					continue 2;
					break;

				default:
					$widgetConfiguration = NULL;
					if (is_callable($elementCallback)) {
						$widgetConfiguration = call_user_func($elementCallback, $item);
					}

					if (empty($widgetConfiguration)) {
						$widgetConfigurations[] = array(
							'type' => 'field',
							'field' => $fieldname
						);
					} else {
						$widgetConfigurations[] = $widgetConfiguration;
						$widgetConfiguration = NULL;
					}

					break;
			}
		}

		if ($hasDivider) {
			$boxType = $outerWrapTypeWithDivider;

			$lines[] = array(
				'type' => $elementWrapTypeWithDivider,
				'items' => $widgetConfigurations
			);
			$widgetConfigurations = $lines;
		} else {
			$boxType = $wrapTypeWithoutDivider;
		}

		return array(
			'type' => $boxType,
			'items' => $widgetConfigurations
		);
	}

	/**
	 * Callback handler for fields in a regular type showitem string. This handler is neccessary for e.g. handling top-level
	 * palettes, which only occur in TCA[$table][types], not TCA[$table][palettes]
	 *
	 * @param string $item semicolon separated string taken from the showitem string
	 * @return array
	 */
	protected function typeShowitemElementCallbackHandler($item) {
		list ($fieldname, $label, $part3, $part4, $part5) = t3lib_div::trimExplode(';', $item);

		$widgetConfig = array();
		switch ($fieldname) {
			case '--palette--':
				$paletteName = $part3;

				$widgetConfig = array(
					'block' => $paletteName,
					'label' => $label
				);

				break;
		}

		return $widgetConfig;
	}
}

?>
