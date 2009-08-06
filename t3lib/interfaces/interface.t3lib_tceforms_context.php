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

	/**
	 * Registers an IRRE object with the context object
	 *
	 * @param t3lib_TCEforms_Element_Inline $inlineElementObject
	 * @return void
	 */
	public function registerInlineElement(t3lib_TCEforms_Element_Inline $inlineElementObject);

	/**
	 * Returns TRUE if any IRRE elements have been registered for the form.
	 *
	 * @return boolean
	 */
	public function hasInlineElements();
}

?>