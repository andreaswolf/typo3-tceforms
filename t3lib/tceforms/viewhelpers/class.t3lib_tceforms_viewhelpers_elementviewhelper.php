<?php

class t3lib_TCEforms_ViewHelpers_ElementViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {
	public function initialize() {
		parent::initialize();
	}

	/**
	 * @param  t3lib_TCEforms_Element_Abstract $element The element
	 * @param  t3lib_TCEforms_Record $record
	 * @param  t3lib_TCEforms_Container_Palette $palette The palette the element belongs to. Optional
	 * @return void
	 */
	public function render(t3lib_TCEforms_Element_Abstract $element, t3lib_TCEforms_Record $record, t3lib_TCEforms_Container_Palette $palette = NULL) {
		if ($element instanceof t3lib_TCEforms_Element_Palette) {
			return $this->viewHelperVariableContainer->getView()->renderPartial('elements', 'palette',
				array(
					'element' => $element,
					'record' => $record
				)
			);
		} else {
			if ($palette) {
				if (is_a($element, 't3lib_TCEforms_Element_Linebreak')) {
					return $element->renderContents();
				} else {
					return $this->viewHelperVariableContainer->getView()->renderPartial('elements', 'paletteelement',
						array(
							'element' => $element,
							'record' => $record,
							'palette' => $palette
						)
					);
				}
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
