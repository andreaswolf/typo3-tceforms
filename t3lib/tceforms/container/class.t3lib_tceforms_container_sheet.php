<?php

class t3lib_TCEforms_Container_Sheet implements t3lib_TCEforms_Container {
	/**
	 * @var array  The sub-elements of this sheet
	 */
	protected $childObjects = array();

	protected $identString;

	protected $header;

	/**
	 * The global context object this sheet belongs to
	 *
	 * @var t3lib_TCEforms_Context
	 */
	protected $contextObject;

	/**
	 * The form object this sheet belongs to
	 *
	 * @var t3lib_TCEforms_Form
	 */
	protected $formObject;

	/**
	 * The record this sheet belongs to
	 *
	 * @var t3lib_TCEforms_Record
	 */
	protected $recordObject;

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

	/**
	 * The name of this sheet. This is only relevant for FlexForm sheets.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * The stack of element identifier parts used for creating element identifiers.
	 *
	 * This will usually be imploded with a separator to create an identifier.
	 *
	 * @var array<string>
	 */
	protected $elementIdentifierStack = array();


	public function __construct($identString, $header, $name = '') {
		$this->identString = $identString;
		$this->header = $header;
		$this->name = $name;
	}

	public function init() {

	}

	public function setContextObject(t3lib_TCEforms_Context $contextObject) {
		$this->contextObject = $contextObject;

		return $this;
	}

	public function setFormObject(t3lib_TCEforms_Form $formObject) {
		$this->formObject = $formObject;

		return $this;
	}

	public function getFormObject() {
		return $this->formObject;
	}

	public function setRecordObject(t3lib_TCEforms_Record $recordObject) {
		$this->recordObject = $recordObject;

		return $this;
	}

	/**
	 * Sets all information that is required for proper element identifier generation.
	 *
	 * @param  array $elementIdentifierStack
	 * @return t3lib_TCEforms_Container_Sheet
	 */
	public function setElementIdentifierStack(array $elementIdentifierStack) {
		$this->elementIdentifierStack = $elementIdentifierStack;

		return $this;
	}

	public function getElementIdentifierStack(array $elementIdentifierStack) {
		return $this->elementIdentifierStack;
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

			$childContent = $childObject->render();

			if ($childContent != '') {
				$contentStack[] = $childContent;
			}
		}

		if (count($contentStack) == 0) {
			return '';
		}

		$content .= $this->wrapBorder(implode('', $contentStack), $currentBorderStyle);
		// TODO move this to CSS file
		$content = '<table border="0" cellspacing="0" cellpadding="0" width="100%">'.$content.'</table>';

		return $content;
	}

	public function getHeader() {
		return $this->header;
	}

	public function getElements() {
		return $this->childObjects;
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
	 * @deprecated  Remove before merging new TCEforms to core
	 */
	public function registerRequiredProperty($type, $name, $value) {
		throw new RuntimeException('Call to ' . __CLASS__ . '::' . __METHOD__ . ', which is deprecated. Please fix! Call came from ' . t3lib_div::debug_trail());
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

	public function getNestedStackEntry() {
		return array(
			'tab',
			$this->identString
		);
	}
}

?>
