<?php

class t3lib_TCA_DataStructure_Palette extends t3lib_TCA_DataStructure_Element {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 *
	 *
	 * @var array<t3lib_TCA_DataStructure_Field>
	 */
	protected $elements = array();

	/**
	 * @var t3lib_TCA_DataStructure
	 */
	protected $dataStructure;

	protected $paletteConfiguration = array();

	/**
	 * @param array  $paletteConfiguration  The configuration of this palette
	 * @param t3lib_TCA_DataStructure $dataStructure
	 * @param string $label
	 * @param integer $name The name used as the key in the TCA palettes array
	 *
	 * @TODO get the complete configuration from DataStructure, handle canNotCollapse
	 */
	public function __construct(array $paletteConfiguration, t3lib_TCA_DataStructure $dataStructure, $label, $name) {
		$this->paletteConfiguration = $paletteConfiguration;
		$this->dataStructure = $dataStructure;
		$this->label = $label;
		$this->name = $name;
	}

	/**
	 * Adds a datastructure definition for a field to this palette
	 *
	 * @param t3lib_TCA_DataStructure_Field $element
	 * @return void
	 */
	public function addElement(t3lib_TCA_DataStructure_Field $element) {
		$this->elements[] = $element;
	}

	/**
	 * Returns the datastructure definitions of the fields this palette contains
	 *
	 * @return array<t3lib_TCA_DataStructure_Field>
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