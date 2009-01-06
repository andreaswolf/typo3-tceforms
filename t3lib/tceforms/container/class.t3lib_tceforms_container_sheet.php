<?php

require_once(PATH_t3lib.'interfaces/interface.t3lib_tceforms_container.php');

class t3lib_TCEforms_Container_Sheet implements t3lib_TCEforms_Container {
	/**
	 * @var array  The sub-elements of this sheet
	 */
	protected $childObjects = array();

	protected $identString;

	protected $header;

	/**
	 * The parent form this sheet belongs to
	 *
	 * @var t3lib_TCEforms_Form
	 *
	 * TODO: perhaps rename to parentForm
	 */
	protected $parentObject;

	/**
	 * The global context object this sheet belongs to
	 *
	 * @var unknown_type
	 */
	protected $contextObject;

	/**
	 * @var array
	 */
	protected $requiredFields = array();
	protected $requiredElements = array();

	/**
	 * Set to true to start a new row in the tab menu (i.e., this and all following sheets will be
	 * put on a separate line)
	 *
	 * @var boolean
	 */
	protected $startNewRowInTabmenu;


	public function __construct($identString, $header) {
		$this->identString = $identString;

		$this->header = $header;

		$this->parentObject = $parentObject;
	}

	public function init() {

	}

	// TODO: add type hinting for $parentObject
	public function setParentObject($parentObject) {
		$this->parentObject = $parentObject;

		return $this;
	}

	public function setParentFormObject(t3lib_TCEforms_Form $parentFormObject) {
		$this->contextObject = $parentFormObject;

		return $this;
	}

	public function addChildObject(t3lib_TCEforms_Element $childObject) {
		$childObject->setContainer($this);
		$this->childObjects[] = $childObject;
	}

	public function render() {
			// just return nothing if there are no child objects
		if (count($this->childObjects) == 0) {
			return '';
		}

		$contentStack = array();
		$currentBorderStyle = $this->childObjects[0]->getBorderStyle();
		foreach ($this->childObjects as $childObject) {
			if ($childObject->_wrapBorder == true) {
				if (count($contentStack) > 0) {
					$content .= $this->wrapBorder(implode('', $contentStack), $currentBorderStyle);

					$contentStack = array();
				}

				$currentBorderStyle = $childObject->getBorderStyle();
			}

			$contentStack[] = $childObject->render();
		}

		$content .= $this->wrapBorder(implode('', $contentStack), $currentBorderStyle);
		// TODO move this to CSS file
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
		$wrap = t3lib_parsehtml::getSubpart($this->contextObject->getTemplateContent(), '###SECTION_WRAP###');

		$tableAttribs='';
		$tableAttribs.= $borderStyle[0] ? ' style="'.htmlspecialchars($borderStyle[0]).'"':'';
		$tableAttribs.= $borderStyle[2] ? ' background="'.htmlspecialchars($this->backPath.$borderStyle[2]).'"':'';
		$tableAttribs.= $borderStyle[3] ? ' class="'.htmlspecialchars($borderStyle[3]).'"':'';
		if ($tableAttribs)	{
			// TODO: move this to CSS file and add the class above
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
	// TODO: make this interact with the form object or move it there
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

	public function setStartingNewRowInTabmenu($startNewRow) {
		$this->startNewRowInTabmenu = $startNewRow;

		return $this;
	}

	public function isStartingNewRowInTabmenu() {
		return $this->startNewRowInTabmenu;
	}
}

?>