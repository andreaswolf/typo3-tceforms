<?php

class t3lib_TCA_DataStructure_Field {

	protected $dataStructure;

	protected $name;

	protected $configuration;

	protected $label;

	protected $specialConfiguration;

	/**
	 * The palette object attached to this field, if any
	 *
	 * @var t3lib_TCA_DataStructure_Palette
	 */
	protected $palette = NULL;

	/**
	 * @param t3lib_TCA_DataStructure $dataStructure
	 * @param string $name The field name of this field
	 * @return void
	 */
	public function __construct(t3lib_TCA_DataStructure $dataStructure, $name, $configuration) {
		$this->dataStructure = $dataStructure;
		$this->name = $name;
		$this->configuration = $configuration;

		$this->label = $this->configuration['label'];
	}

	public function getName() {
		return $this->name;
	}

	public function getConfiguration() {
		return $this->configuration;
	}

	public function getLabel() {
		return $this->label;
	}

	/**
	 * Sets the label of this field
	 *
	 * @param string $label The new label
	 * @return void
	 */
	public function setLabel($label) {
		$this->label = $label;
	}

	/**
	 *
	 *
	 * @param unknown_type $specialConfiguration
	 */
	public function setSpecialConfiguration($specialConfiguration) {
		// TODO do something useful with this configuration
		$this->specialConfiguration = $specialConfiguration;
	}

	public function addPalette(t3lib_TCA_DataStructure_Palette $paletteObject) {
		// TODO throw exception if palette has already been set
		$this->palette = $paletteObject;
	}

	public function hasPalette() {
		return isset($this->palette);
	}

	public function getPalette() {
		return $this->palette;
	}
}

?>