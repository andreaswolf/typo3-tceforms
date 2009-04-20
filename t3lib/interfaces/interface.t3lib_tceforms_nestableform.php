<?php

interface t3lib_TCEforms_NestableForm {
	/**
	 * Sets the element containing this form.
	 * @param t3lib_TCEforms_Element_Abstract $elementObject
	 * @return t3lib_TCEforms_NestableForm A reference to $this, for easier use
	 */
	public function setContainingElement(t3lib_TCEforms_Element_Abstract $elementObject);
	
	/**
	 * Returns the element containing the nestable form.
	 * @return t3lib_TCEforms_Element
	 */
	public function getContainingElement();
}
