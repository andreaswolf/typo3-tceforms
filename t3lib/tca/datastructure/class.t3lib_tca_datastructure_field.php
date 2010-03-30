<?php

class t3lib_TCA_DataStructure_Field {

	protected $dataStructure;

	protected $name;

	protected $configuration;

	protected $label;

	protected $specialConfiguration;

	/**
	 * @param t3lib_TCA_DataStructure $dataStructure
	 * @param string $fieldName
	 * @return void
	 */
	public function __construct(t3lib_TCA_DataStructure $dataStructure, $name, $configuration, $label, $specialConfiguration) {
		$this->dataStructure = $dataStructure;
		$this->name = $name;
		$this->configuration = $configuration;
		$this->label = $label;
		$this->specialConfiguration = $specialConfiguration;

		if ($this->label == '') {
			$this->label = $configuration['label'];
		}
	}

	public function getName() {
		return $this->name;
	}

	public function getConfiguration() {
		return $this->dataStructure->getFieldConfiguration($this->name);
	}

	public function getLabel() {
		return $this->label;
	}
}

?>