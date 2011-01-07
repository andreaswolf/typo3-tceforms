<?php

class t3lib_TCEforms_ViewHelpers_ElementViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {
	public function initialize() {
		parent::initialize();
	}

	/**
	 * @param  t3lib_TCEforms_Element_Abstract $element The element
	 * @param  t3lib_TCEforms_Record $record
	 * @param  boolean $inPalette
	 * @return void
	 */
	public function render(t3lib_TCEforms_Element_Abstract $element, t3lib_TCEforms_Record $record, $inpalette = FALSE) {
		//t3lib_div::debug($this->renderingContext);
		if ($element instanceof t3lib_TCEforms_Element_Palette) {
			return $this->viewHelperVariableContainer->getView()->renderPartial('elements', 'palette',
				array(
					'element' => $element,
					'record' => $record
				)
			);
		} else {
			if ($inpalette) {
				return $element->getContents();
			} else {
				return $this->viewHelperVariableContainer->getView()->renderPartial('elements', 'default',
					array(
						'element' => $element,
						'record' => $record
					)
				);
			}
		}
	}
}

?>