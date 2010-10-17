<?php

class t3lib_TCA_DataStructure_Field {

	/**
	 * The data structure this field belongs to
	 *
	 * @var t3lib_TCA_DataStructure
	 */
	protected $dataStructure;

	/**
	 * The name of this field. Used as key in the "columns" array of TCA
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Configuration of this field as defined in TCA section "columns".
	 *
	 * @var array
	 */
	protected $configuration;

	protected $label;

	/**
	 * The special configuration of this field.
	 *
	 * May be stored in TCA as the fourth part of a showitem entry in a type.
	 *
	 * @var string
	 */
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
	public function __construct(t3lib_TCA_DataStructure $dataStructure, $name, array $configuration) {
		$this->dataStructure = $dataStructure;
		$this->name = $name;
		$this->configuration = $configuration;

		$this->label = $this->configuration['label'];
	}

	/**
	 * The fieldname of this field
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the configuration of this field
	 *
	 * @return array
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * Returns a value from the field configuration
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getConfigurationValue($key) {
		return $this->configuration[$key];
	}

	/**
	 * Returns true if this field has a configuration value with name $key
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function hasConfigurationValue($key) {
		return array_key_exists($key, $this->configuration);
	}

	/**
	 * Returns the label of this field
	 *
	 * @return string
	 */
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