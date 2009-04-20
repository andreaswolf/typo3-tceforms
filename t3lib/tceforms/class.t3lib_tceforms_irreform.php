<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_form.php');
require_once(PATH_t3lib.'interfaces/interface.t3lib_tceforms_nestableform.php');

/*
 * TODO check if inlineFirstPid may be replaced by a call to context object to get the pid
 */
class t3lib_TCEforms_IRREForm extends t3lib_TCEforms_Form implements t3lib_TCEforms_NestableForm {

	/**
	 * The element object containing this form.
	 * @var t3lib_TCEforms_Element_Abstract
	 */
	protected $containingElement;

	public function __construct() {
		parent::__construct();
	}

	public function init() {
		$this->formBuilder = clone($this->formBuilder);
	}

	/**
	 * Sets the element containing this form.
	 * @param t3lib_TCEforms_Element $elementObject
	 * @return t3lib_TCEforms_NestableForm A reference to $this, for easier use
	 */
	public function setContainingElement(t3lib_TCEforms_Element_Abstract $elementObject) {
		$this->containingElement = $elementObject;
		return $this;
	}

	/**
	 * Returns the element containing the nestable form.
	 * @return t3lib_TCEforms_Element
	 */
	public function getContainingElement() {
		return $this->containingElement;
	}

	public function getTemplateContent() {
		return $this->contextObject->getTemplateContent();
	}
	
	/**
	 * Renders a record object into a HTML form.
	 *
	 * @param t3lib_TCEforms_Record $recordObject
	 * @return string The rendered record form, ready to be put on a page
	 */
	protected function renderRecordObject(t3lib_TCEforms_Record $recordObject) {
		global $TCA;

		$recordContent = $recordObject->render();

		$wrap = t3lib_parsehtml::getSubpart($this->getTemplateContent(), '###TOTAL_WRAP_IRRE###');
		if ($wrap == '') {
			throw new RuntimeException('No template wrap for record found.');
		}

		$appendFormFieldNames = '['.$recordObject->getTable().']['.$recordObject->getValue('uid').']';
		$irreFieldNames = $this->getFormFieldNamePrefix() . $this->getIrreIdentifier($recordObject);
		$formFieldNames = $this->getFormFieldNamePrefix() . $this->getFieldIdentifier($recordObject);
		t3lib_div::devLog('pid: ' . $this->containingElement->getValue('pid'), __CLASS__);

		$fieldsStyle = ($this->getExpandedCollapsedState($recordObject) ? 'display:none;' : '');

		$markerArray = array(
			'###TITLE###' => htmlspecialchars($recordObject->getTitle()),

			'###ICON###' => t3lib_iconWorks::getIconImage($recordObject->getTable(), $recordObject->getRecordData(), $this->getBackpath(), 'class="absmiddle"' . $titleA),
			'###WRAP_CONTENT###' => $recordContent,
			'###BACKGROUND###' => htmlspecialchars($this->backPath.$this->containingElement->getBorderStyle()),
			'###FORMFIELDNAMES###' => $formFieldNames,
			'###IRREFIELDNAMES###' => $irreFieldNames,
			'###CLASS###' => 'wrapperTable',//htmlspecialchars($this->containingElement->getClassScheme())
			'###ONCLICK###' => " onClick=\"return inline.expandCollapseRecord('" . htmlspecialchars($irreFieldNames) . "', " . ($this->containingElement->expandOnlyOneRecordAtATime() ? '1' : '0') . ");\"",
			'###FIELDS_STYLE###' => $fieldsStyle
		);

		$content = t3lib_parsehtml::substituteMarkerArray($wrap, $markerArray);

		return $content;
	}

	protected function getIrreIdentifier(t3lib_TCEforms_Record $recordObject) {
		$identifierParts[] = $recordObject->getValue('uid');
		$identifierParts[] = $recordObject->getTable();
		$identifierParts[] = $this->containingElement->getFieldname();
		$identifierParts[] = $this->containingElement->getValue('uid');
		$identifierParts[] = $this->containingElement->getTable();
		$elementObject = $this->containingElement;
		while ($elementObject->getParentFormObject() instanceof t3lib_TCEforms_NestableForm) {
			$identifierParts[] = $elementObject->getFieldname();
			$identifierParts[] = $elementObject->getValue('uid');
			$identifierParts[] = $elementObject->getTable();
			$elementObject = $elementObject->getParentFormObject()->getContainingElement();
			t3lib_div::devLog('Loop ' . ++$i, 't3lib_TCEforms_IrreForm');
		};
		$identifierParts[] = $this->containingElement->getContextRecordObject()->getValue('pid');
		return '[' . implode('][', array_reverse($identifierParts)) . ']';
	}

	protected function getFieldIdentifier(t3lib_TCEforms_Record $recordObject) {
		
	}

	/**
	 * Checks if a uid of a child table is in the inline view settings.
	 *
	 * @return boolean true=expand, false=collapse
	 */
	function getExpandedCollapsedState(t3lib_TCEforms_Record $recordObject) {
		if ($this->inlineViewState == NULL) {
			$inlineView = unserialize($GLOBALS['BE_USER']->uc['inlineView']);
			$this->inlineViewState = (array)$inlineView[$this->containingElement->getTable()][$this->containingElement->getValue('uid')];
		}

		$table = $recordObject->getTable();
		$uid = $recordObject->getValue('uid');
		if (isset($this->inlineViewState[$table]) && is_array($this->inlineViewState[$table])) {
			if (in_array($uid, $this->inlineViewState[$table]) !== false) {
				return true;
			}
		}
		return false;
	}
}
