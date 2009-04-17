<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_form.php');

class t3lib_TCEforms_IRREForm extends t3lib_TCEforms_Form {

	protected $container;

	public function __construct() {
		parent::__construct();
	}

	public function init() {
		$this->formBuilder = clone($this->formBuilder);
	}

	/**
	 * Sets the IRRE element containing this form.
	 *
	 * @param t3lib_TCEforms_Element_Inline $container
	 * @return t3lib_TCEforms_IRREForm This form
	 */
	public function setContainer(t3lib_TCEforms_Element_Inline $container) {
		$this->container = $container;

		return $this;
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

		$markerArray = array(
			'###TITLE###' => htmlspecialchars($recordObject->getTitle()),

			'###ICON###' => t3lib_iconWorks::getIconImage($recordObject->getTable(), $recordObject->getRecordData(), $this->getBackpath(), 'class="absmiddle"' . $titleA),
			'###WRAP_CONTENT###' => $recordContent,
			'###BACKGROUND###' => htmlspecialchars($this->backPath.$this->container->getBorderStyle()),
			'###CLASS###' => 'wrapperTable'//htmlspecialchars($this->container->getClassScheme())
		);

		$content = t3lib_parsehtml::substituteMarkerArray($wrap, $markerArray);

		return $content;
	}
}
