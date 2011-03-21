<?php

class t3lib_TCA_DataStructure_Field extends t3lib_TCA_DataStructure_Element {

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

	/**
	 * The label of this entry.
	 *
	 * Either comes from
	 *
	 * @var string
	 */
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
	public function __construct(t3lib_TCA_DataStructure $dataStructure, $name) {
		$this->dataStructure = $dataStructure;
		$this->name = $name;
		$this->configuration = $dataStructure->getFieldConfiguration($name);

		$this->setLabel($this->configuration['label']);

		$this->setStyle(new t3lib_TCA_FieldStyle());
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

	/**
	 * Returns TRUE if this field has a localization mode set.
	 *
	 * @return bool
	 * @see getLocalizationMode()
	 */
	public function hasLocalizationMode() {
		return $this->dataStructure->hasControlValue('languageField') && $this->hasConfigurationValue('l10n_mode') &&
		    (in_array($this->getConfigurationValue('l10n_mode'), array('exclude', 'mergeIfNotBlank', 'noCopy', 'prefixLangTitle')));
	}

	/**
	 * Returns the localization mode of this field.
	 *
	 * Possible modes are:
	 *  - exclude: Field will not be displayed in localized records
	 *  - mergeIfNotBlank: Value from the default translation will be used if this field is blank. In the backend the contents of this field are not copied when creating a translation.
	 *  - noCopy: Like mergeIfNotBlank but without the implications for the frontend; the field is just not copied.
	 *  - prefixLangTitle: Content of the field is prefixed with the language on copying. Works only for types text and input
	 *
	 * @return string
	 */
	public function getLocalizationMode() {
		if ($this->hasLocalizationMode()) {
			return $this->getConfigurationValue('l10n_mode');
		}
	}
}

?>
