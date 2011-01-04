<?php

class t3lib_TCEforms_ViewHelpers_ElementViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {
	public function initialize() {
		parent::initialize();
	}

	/**
	 * @param  t3lib_TCEforms_Element_Abstract $element The element
	 * @return void
	 */
	public function render(t3lib_TCEforms_Element_Abstract $element, t3lib_TCEforms_Record $record) {
		//t3lib_div::debug($this->renderingContext);
		if ($element instanceof t3lib_TCEforms_Element_Palette) {
			return $this->viewHelperVariableContainer->getView()->renderPartial('elements', 'palette',
				array(
					'element' => $element,
					'record' => $record
				)
			);
		}
		$ret = $element->renderContents();
		return $ret;
	}
}

?>