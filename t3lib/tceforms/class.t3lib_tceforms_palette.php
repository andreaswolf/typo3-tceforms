<?php

require_once(PATH_t3lib.'interfaces/interface.t3lib_tceforms_element.php');

class t3lib_TCEforms_Palette implements t3lib_TCEforms_Element {
	protected $table;
	protected $record;
	protected $paletteNumber;
	protected $field;


	protected $formObject;

	/**
	 * @var t3lib_TCEforms_AbstractForm
	 */
	protected $TCEformsObject;


	public function init($table, $row, $typeNumer, $paletteNumber, $field, $fieldDescriptionParts, $header='', $itemList='', $collapsedHeader=NULL) {
		$this->table = $table;
		$this->record = $row;
		$this->typeNumber = $typeNumber;
		$this->paletteNumber = $paletteNumber;
		$this->field = $field;
	}

	public function setTCEformsObject(t3lib_TCEforms_AbstractForm $formObject) {
		$this->TCEformsObject = $formObject;
	}

	protected function loadElements() {
		global $TCA;

		t3lib_div::loadTCA($this->table);
		$parts = array();

			// Getting excludeElements, if any.
		if (!is_array($this->excludeElements))	{
			$this->excludeElements = $this->TCEformsObject->getExcludeElements();
		}

			// Load the palette TCEform elements
		if ($TCA[$this->table] && (is_array($TCA[$this->table]['palettes'][$this->paletteNumber]) || $itemList))	{
			$itemList = ($itemList ? $itemList : $TCA[$this->table]['palettes'][$this->paletteNumber]['showitem']);
			if ($itemList)	{
				$fields = t3lib_div::trimExplode(',',$itemList,1);
				foreach($fields as $info)	{
					$fieldParts = t3lib_div::trimExplode(';',$info);
					$theField = $fieldParts[0];

					if (!in_array($theField, $this->excludeElements) && $TCA[$this->table]['columns'][$theField])	{
						$this->fieldArr[] = $theField;
						$elem = $this->TCEformsObject->getSingleField($this->table,$theField,$this->record,$fieldParts[1],1,'',$fieldParts[2]);

						if ($elem instanceof t3lib_TCEforms_AbstractElement) {
							$parts[] = $elem;
						}
					}
				}
			}
		}
		return $parts;
	}

	public function render() {
		$paletteElements = $this->loadElements();

		foreach ($paletteElements as $paletteElement) {
			$paletteContents[] = $paletteElement->render();
		}

		return $paletteContents;
	}
}

?>