<?php

require_once(PATH_t3lib.'tceforms/element/class.t3lib_tceforms_element_abstract.php');
//require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_flexform.php');


class t3lib_TCEforms_Element_Flex extends t3lib_TCEforms_Element_Abstract {
	protected function renderField() {
		/*$formObject = new t3lib_TCEforms_Flexform($this->table, $this->record, $this->fieldConfig['config']);
		$formObject->setTCEformsObject($this->TCEformsObject);
		$formObject->registerDefaultLanguageData();
		$formObject->setFormFieldNamePrefix($this->itemFormElName);
		$formObject->setParentFormObject($this->_TCEformsObject);
		$formObject->PA = $this->PA; // TODO remove this ugly hack

		$output = $formObject->render();

		return $output;*/

		return 'Flexforms are not implemented yet.';
	}

	/**
	 * Recursive rendering of flexforms
	 *
	 * @param	array		(part of) Data Structure for which to render. Keys on first level is flex-form fields
	 * @param	array		(part of) Data array of flexform corresponding to the input DS. Keys on first level is flex-form field names
	 * @param	array		Array of standard information for rendering of a form field in TCEforms, see other rendering functions too
	 * @param	string		Form field prefix, eg. "[data][sDEF][lDEF][...][...]"
	 * @param	integer		Indicates nesting level for the function call
	 * @param	string		Prefix for ID-values
	 * @param	boolean		Defines whether the next flexform level is open or closed. Comes from _TOGGLE pseudo field in FlexForm xml.
	 * @return	string		HTMl code for form.
	 */
	public function resolveDatastructureIntoObjects($dataStructArray,$editData,$formPrefix='',$level=0,$idPrefix='ID',$toggleClosed=FALSE) {

		$output = '';
		$mayRestructureFlexforms = $GLOBALS['BE_USER']->checkLanguageAccess(0);


		//print_r($dataStructArray['sheets']);
		foreach ($dataStructArray as $sheetTitle => $sheetDefinition) {
			echo $sheetTitle;
			/*if (is_array($sheetDefinition)) {
					// ********************
					// Making the row:
					// ********************
					// Title of field:
				$theTitle = htmlspecialchars(t3lib_div::fixed_lgd_cs($this->TCEformsObject->sL($sheetDefinition['tx_templavoila']['title']),30));

					// If it's a "section" or "container":
				if ($sheetDefinition['type']=='array')	{

						// Creating IDs for form fields:
						// It's important that the IDs "cascade" - otherwise we can't dynamically expand the flex form because this relies on simple string substitution of the first parts of the id values.
					$thisId = t3lib_div::shortMd5(uniqid('id',true));	// This is a suffix used for forms on this level
					$idTagPrefix = $idPrefix.'-'.$thisId;	// $idPrefix is the prefix for elements on lower levels in the hierarchy and we combine this with the thisId value to form a new ID on this level.

						// If it's a "section" containing other elements:
					if ($sheetDefinition['section'])	{
						// TODO: create _flexformsection

						// If it's a container:
					} else {
						// TODO: create _flexformcontainer here

					}

					// If it's a "single form element":
				} elseif (is_array($sheetDefinition['TCEforms']['config'])) {	// Rendering a single form element:
					$formObject = new t3lib_TCEforms_Flexform($this->table, $this->record, $dataStructArray);
					$formObject->setTCEformsObject($this->TCEformsObject);
					$formObject->registerDefaultLanguageData();
					$formObject->PA = $this->PA; // TODO remove this ugly hack
				}
				$formObject->setKey($sheetTitle);

				$output .= $formObject->render();
			}*/
		}
	}
}

?>