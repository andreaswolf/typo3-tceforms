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

	public function init($identString, $header, $parentObject) {
		$this->identString = $identString;

		$this->header = $header;

		$this->childObjects = array();

		$this->parentObject = $parentObject;
	}

	public function addChildObject(t3lib_TCEforms_AbstractElement $childObject) {
		$this->childObjects[] = $childObject;
	}

	public function render() {
		$contentStack = array();
		foreach ($this->childObjects as $childObject) {
			if ($childObject->_wrapBorder == true) {
				$content .= $this->wrapBorder(implode('', $contentStack), $tempBorderStyle);

				$tempBorderStyle = $childObject->wrapBorder;

				$contentStack = array();
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

	/**
	 * Wraps an element in the $out_array with the template row for a "section" ($this->sectionWrap)
	 *
	 * @param	string
	 * @param	array
	 * @return	void
	 */
	protected function wrapBorder($content, $borderStyle)	{
		$wrap = t3lib_parsehtml::getSubpart($this->parentObject->getTemplateContent(), '###SECTION_WRAP###');

		$tableAttribs=' class="wrapperTable1"';
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
}

?>