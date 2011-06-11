<?php

class t3lib_DataStructure_Element_Palette extends t3lib_DataStructure_Element_Abstract {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 *
	 *
	 * @var t3lib_DataStructure_Element_Field[]
	 */
	protected $elements = array();

	/**
	 * @var t3lib_DataStructure_Tca
	 */
	protected $dataStructure;

	protected $paletteConfiguration = array();

	/**
	 * @param array  $paletteConfiguration  The configuration of this palette
	 * @param t3lib_DataStructure_Tca $dataStructure
	 * @param string $label
	 * @param integer $name The name used as the key in the TCA palettes array
	 *
	 * @TODO get the complete configuration from DataStructure, handle canNotCollapse
	 */
	public function __construct(array $paletteConfiguration, t3lib_DataStructure_Tca $dataStructure, $label, $name) {
		$this->paletteConfiguration = $paletteConfiguration;
		$this->dataStructure = $dataStructure;
		$this->label = $label;
		$this->name = $name;
	}

	/**
	 * Adds a datastructure definition for a field to this palette
	 *
	 * @param t3lib_DataStructure_Element_Abstract $element
	 * @return void
	 */
	public function addElement(t3lib_DataStructure_Element_Abstract $element) {
		$this->elements[] = $element;
	}

	/**
	 * Returns the datastructure definitions of the fields this palette contains
	 *
	 * @return t3lib_DataStructure_Element_Field[]
	 */
	public function getElements() {
		return $this->elements;
	}

	/**
	 * Returns the key this palette has inside the TCA palette definitions
	 *
	 * @return integer
	 */
	public function getName() {
		return $this->name;
	}

	public function getLabel() {
		return $this->label;
	}

	/**
	 * @return bool TRUE if this palette is collapsible
	 */
	public function isCollapsible() {
		return (!array_key_exists('canNotCollapse', $this->paletteConfiguration) || !$this->paletteConfiguration['canNotCollapse']);
	}
}

?>
