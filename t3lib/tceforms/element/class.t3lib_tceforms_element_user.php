<?php

require_once(PATH_t3lib.'tceforms/element/class.t3lib_tceforms_element_abstract.php');


class t3lib_TCEforms_Element_User extends t3lib_TCEforms_Element_Abstract {
	protected function renderField() {
		// TODO: make this a proper TCEforms object (i.e. fill it with contents etc.)
		$TCEformsObject = new t3lib_tceforms();

		// we need to rebuild $PA here because it does not exist by default anymore
		$PA = $this->PA;
		$PA['pal'] = $this->pal;
		$PA['fieldConf'] = $this->fieldSetup;
		$PA['fieldTSConfig'] = $this->fieldTSConfig; // not filled?
		$PA['itemFormElName']      = $this->formFieldName;
		$PA['itemFormElName_file'] = $this->fileFormFieldName;
		$PA['itemFormElValue']     = $this->itemFormElValue;
		$PA['itemFormElID']        = $this->formFieldId;
		$PA['onFocus']             = $this->onFocus;
		$PA['label']               = $this->label;
		$PA['itemFormElValue']     = $this->itemFormElValue;
		$PA['fieldChangeFunc']     = $this->fieldChangeFunc;
		$PA['table'] = $this->table;
		$PA['field'] = $this->field;
		$PA['row']   = $this->record;
		$PA['pObj']  = $TCEformsObject;//&$this->TCEformsObject;

		$item = t3lib_div::callUserFunction($this->fieldSetup['config']['userFunc'], $PA, $TCEformsObject);
		return $item;
	}
}
