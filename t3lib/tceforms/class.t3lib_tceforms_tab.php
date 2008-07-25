<?php

require_once(PATH_t3lib.'interfaces/interface.t3lib_tceforms_element.php');

class t3lib_TCEforms_Tab implements t3lib_TCEforms_Element {
	/**
	 * @var array  The sub-elements of this tab
	 */
	protected $childObjects;

	protected $identString;

	protected $header;

	/**
	 * @var t3lib_TCEforms_AbstractForm  The parent form this tab belongs to
	 */
	protected $parentObject;

	/**
	 * @var array
	 */
	protected $requiredFields = array();
	protected $requiredElements = array();


	public function init($identString, $header, t3lib_TCEforms_AbstractForm $parentObject) {
		$this->identString = $identString;

		$this->header = $header;

		$this->childObjects = array();

		$this->parentObject = $parentObject;
	}

	public function addChildObject(t3lib_TCEforms_AbstractElement $childObject) {
		$this->childObjects[] = $childObject;
	}

	public function render() {
			// just return nothing if there are no child objects
		if (count($this->childObjects) == 0) {
			return '';
		}

		$contentStack = array();
		$tempBorderStyle = $this->childObjects[0]->borderStyle;
		foreach ($this->childObjects as $childObject) {
			if ($childObject->_wrapBorder == true) {
				if (count($contentStack) > 0) {
					$content .= $this->wrapBorder(implode('', $contentStack), $tempBorderStyle);

					$contentStack = array();
				}

				$tempBorderStyle = $childObject->borderStyle;
			}

			$contentStack[] = $childObject->render();
		}

		$content .= $this->wrapBorder(implode('', $contentStack), $tempBorderStyle);
		$content = '<table border="0" cellspacing="0" cellpadding="0" width="100%">'.$content.'</table>';

		return $content;
	}

	public function getHeader() {
		return $this->header;
	}

	public function getRequiredFields() {
		return $this->requiredFields;
	}

	public function getRequiredElements() {
		return $this->requiredElements;
	}

	/**
	 * Wraps an element in the $out_array with the template row for a "section" ($this->sectionWrap)
	 *
	 * @param	string
	 * @param	array
	 * @return	void
	 */
	protected function wrapBorder($content, $borderStyle)	{
		$wrap = t3lib_parsehtml::getSubpart($this->parentObject->getTemplateContent(), '###SECTION_WRAP###');

		$tableAttribs='';
		$tableAttribs.= $borderStyle[0] ? ' style="'.htmlspecialchars($borderStyle[0]).'"':'';
		$tableAttribs.= $borderStyle[2] ? ' background="'.htmlspecialchars($this->backPath.$borderStyle[2]).'"':'';
		$tableAttribs.= $borderStyle[3] ? ' class="'.htmlspecialchars($borderStyle[3]).'"':'';
		if ($tableAttribs)	{
			$tableAttribs='border="0" cellspacing="0" cellpadding="0" width="100%"'.$tableAttribs;
			$markerArray = array(
				'###CONTENT###' => $content,
				'###TABLE_ATTRIBS###' => $tableAttribs,
				'###SPACE_BEFORE###' => intval($this->borderStyle[1])
			);


			$content = t3lib_parsehtml::substituteMarkerArray($wrap, $markerArray);
		}

		return $content;
	 }

	/**
	 * Takes care of registering properties in requiredFields and requiredElements.
	 * The current hierarchy of IRRE and/or Tabs is stored. Thus, it is possible to determine,
	 * which required field/element was filled incorrectly and show it, even if the Tab or IRRE
	 * level is hidden.
	 *
	 * @param	string		$type: Type of requirement ('field' or 'range')
	 * @param	string		$name: The name of the form field
	 * @param	mixed		$value: For type 'field' string, for type 'range' array
	 * @return	void
	 */
	public function registerRequiredProperty($type, $name, $value) {
		if ($type == 'field' && is_string($value)) {
			$this->requiredFields[$name] = $value;
				// requiredFields have name/value swapped! For backward compatibility we keep this:
			$itemName = $value;
		} elseif ($type == 'range' && is_array($value)) {
			$this->requiredElements[$name] = $value;
			$itemName = $name;
		}
			// Set the situation of nesting for the current field:
		//$this->registerNestedElement($itemName);
	}
}

?>