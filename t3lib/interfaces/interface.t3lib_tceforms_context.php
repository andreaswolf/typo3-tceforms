<?php

interface t3lib_TCEforms_Context {

	public function addHtmlForHiddenField($elementName, $code);

	public function getEditFieldHelpMode();

	public function setEditFieldHelpMode($mode);

	/**
	 * Registers fields from a table to be hidden in the form. Their values will be passed via
	 * hidden form fields.
	 *
	 * @param string $table
	 * @param array $fields
	 * @return void
	 */
	public function registerHiddenFields($table, array $fields);

	/**
	 * Returns TRUE if the specified field will be hidden on the form. The field value will be passed
	 * via a hidden HTML field.
	 *
	 * @param string $table
	 * @param string $fieldName
	 * @return boolean
	 */
	public function isFieldHidden($table, $fieldName);

	//public function registerRequiredField(t3lib_TCEforms_Element_Abstract $fields);

	/**
	 * Registers an IRRE object with the context object
	 *
	 * @param t3lib_TCEforms_IrreForm $inlineElementObject
	 * @return void
	 */
	public function registerInlineElement(t3lib_TCEforms_IrreForm $inlineElementObject);

	/**
	 * Returns TRUE if any IRRE elements have been registered for the form.
	 *
	 * @return boolean
	 */
	public function hasInlineElements();

	/**
	 * Returns an array of all languages configured for the TYPO3 website.
	 *
	 * Technically speaking, these are all languages that have a record in sys_language.
	 *
	 * @return array
	 */
	public function getAvailableLanguages($onlyIsoCoded = TRUE, $setDefault = TRUE);

	/**
	 * Returns TRUE if the context menu for record icons should be enabled.
	 *
	 * @return boolean
	 */
	public function isClickmenuEnabled();

	/**
	 * Sets the prefix for all element identifiers inside this TCEforms context.
	 *
	 * @param  string $elementIdentifierPrefix
	 * @return t3lib_TCEforms_Context
	 */
	public function setElementIdentifierPrefix($elementIdentifierPrefix);

	/**
	 * Creates an identifier for an element from a given element identifier stack.
	 *
	 * @param  array  $elementIdentifierStack  The stack with identifier parts for all elements in the hierarchy
	 * @param  string $type  'name': all parts wrapped in []; 'id': elements separated by '-'
	 * @return string
	 */
	public function createElementIdentifier(array $elementIdentifierStack, $type);
}

?>