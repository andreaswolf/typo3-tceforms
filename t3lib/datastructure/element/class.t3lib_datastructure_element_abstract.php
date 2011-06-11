<?php

/**
 * A common base class for fields and palettes used in TCA
 */
abstract class t3lib_DataStructure_Element_Abstract {
	/**
	 * The styling information for this field
	 *
	 * @var t3lib_TCA_FieldStyle
	 */
	protected $style;

	public function setStyle(t3lib_TCA_FieldStyle $fieldStyle) {
		$this->style = $fieldStyle;
	}

	public function getStyle() {
		return $this->style;
	}

	public function hasStyle() {
		return $this->style !== NULL;
	}
}