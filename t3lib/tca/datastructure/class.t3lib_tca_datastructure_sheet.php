<?php

/**
 * A sheet belonging to a data structure.
 *
 * A sheet is a collection of elements grouped together. Usually the different sheets in
 * a data structure will be displayed on different tabs in an editing window.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_TCA_DataStructure_Sheet {

	protected $label;

	protected $name;

	protected $elements = array();

	/**
	 *
	 *
	 * @param string $label
	 * @param string $name The name of the sheet; this is optional, as it is only really required for FlexForms
	 * @return void
	 */
	public function __construct($label, $name = '') {
		$this->label = $label;
		$this->name = $name;
	}

	/**
	 * Adds an element to this sheet.
	 *
	 * @param array $element
	 * @param integer $index The index to insert at.
	 * @return void
	 */
	public function addElement($element, $index = -1) {
		if ($index >= 0) {
			array_splice($this->elements, $index, 0, array($element));
		} else {
			$this->elements[] = $element;
		}
	}

	public function removeElement($element) {
		$index = array_search($element, $this->elements);
		array_splice($this->elements, $index, 1);
	}

	/**
	 *
	 *
	 * @param string/object $element The element to get the index of
	 * @return void
	 */
	public function getElementIndex($element) {
		if (is_object($element)) {
			return array_search($element, $this->elements);
		} elseif (is_string($element)) {
			foreach ($this->elements as $key => $el) {
				if ($el->getName() == $element) {
					return $key;
				}
			}
		} else {
			throw new InvalidArgumentException('Index was neither an object nor a field name. Cannot determine index.');
		}

		return -1;
	}

	public function getElements() {
		return $this->elements;
	}

	public function getLabel() {
		return $this->label;
	}

	public function getName() {
		return $this->name;
	}
}

?>