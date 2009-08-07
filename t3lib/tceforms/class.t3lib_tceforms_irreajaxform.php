<?php

class t3lib_TCEforms_IrreAjaxForm extends t3lib_TCEforms_IrreForm {
	/**
	 * Overrides the TCA field configuration by TSconfig settings.
	 *
	 * Example TSconfig: TCEform.<table>.<field>.config.appearance.useSortable = 1
	 * This overrides the setting in $TCA[<table>]['columns'][<field>]['config']['appearance']['useSortable'].
	 *
	 * @param	array		$TSconfig: TSconfig
	 * @return	array		Changed TCA field configuration
	 */
	public function overrideFieldConf($TSconfig) {
		$fieldConfig = $this->fieldConfig['config'];

		if (is_array($TSconfig)) {
			$TSconfig = t3lib_div::removeDotsFromTS($TSconfig);
			$type = $fieldConfig['type'];
			if (is_array($TSconfig['config']) && is_array($this->allowOverrideMatrix[$type])) {
					// Check if the keys in TSconfig['config'] are allowed to override TCA field config:
				foreach (array_keys($TSconfig['config']) as $key) {
					if (!in_array($key, $this->allowOverrideMatrix[$type], true)) {
						unset($TSconfig['config'][$key]);
					}
				}
					// Override TCA field config by remaining TSconfig['config']:
				if (count($TSconfig['config'])) {
					$fieldConfig = t3lib_div::array_merge_recursive_overrule($fieldConfig, $TSconfig['config']);
				}
			}
		}

		$this->fieldConfig['config'] = $fieldConfig;
	}
}

?>