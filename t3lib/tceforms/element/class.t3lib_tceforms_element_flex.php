<?php


class t3lib_TCEforms_Element_Flex extends t3lib_TCEforms_Element_Abstract {

	/**
	 * The record(s) stored inside this element, already converted from XML to arrays.
	 *
	 * NOTE: We only have more than one record if we have some section/container structure inside our
	 *       DataStructure
	 *
	 * @var array
	 */
	protected $recordData = array();

	/**
	 * @var t3lib_TCA_DataStructure_FlexFormsResolver
	 */
	protected $dataStructureResolver;

	/**
	 * @var t3lib_TCEforms_FlexForm
	 */
	protected $formObject;

	/**
	 *
	 * @var t3lib_TCA_FlexFormDataStructure
	 */
	protected $dataStructure;

	public function init() {
		parent::init();
		/**
		 * TODO in this method:
		 *  - create an instance of t3lib_TCEforms_FlexForm
		 *  - create an instance of t3lib_TCA_DataStructure_FlexFormsResolver and call it with this
		 *    element as a parameter (maybe this call should be moved to FormBuilder, to separate concerns)
		 *  - create Record objects from the data the FlexFormsResolver injected via setFlexRecordData()
		 *  - take care of initializing the FlexForm object and hand our records to it
		 */

		$this->dataStructureResolver = new t3lib_TCA_DataStructure_FlexFormsResolver();
		$this->dataStructure = $this->dataStructureResolver->resolveDataStructure($this);

		$this->formObject = new t3lib_TCEforms_Flexform();
		$this->formObject->setContainingElement($this)
		                 ->setDataStructure($this->dataStructure)
		                 ->setContextObject($this->contextObject)
		                 ->setLocalizationEnabled($this->dataStructure->isLocalizationEnabled())
		                 ->setLocalizationMethod($this->dataStructure->getLocalizationMethod())
		                 ->setElementIdentifierStack($this->elementIdentifierStack)
		                 ->init();

		// Code copied from t3lib_TCEforms::getSingleField_typeFlex()
		$xmlData = $this->itemFormElValue;
		$xmlHeaderAttributes = t3lib_div::xmlGetHeaderAttribs($xmlData);
		$storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
		if ($storeInCharset)	{
			$currentCharset = $GLOBALS['LANG']->charSet;
			$xmlData = $GLOBALS['LANG']->csConvObj->conv($xmlData, $storeInCharset, $currentCharset,1);
		}
		$editData = t3lib_div::xml2array($xmlData);
		if (!is_array($editData))	{	// Must be XML parsing error...
			//throw new RuntimeException('Parsing error. ' . $editData);
			$editData = array();
		} elseif (!isset($editData['meta']) || !is_array($editData['meta']))	{
		    $editData['meta'] = array();
		}

		if (count($editData) == 0) {
			$recordKeys = $this->dataStructure->getFieldNames();
			$recordValues = array_pad(array(), count($recordKeys), '');
			$emptyRecord = array_combine($recordKeys, $recordValues);
			//$this->records[] = new t3lib_TCEforms_Record($this->table . ':' . $this->field, array(), array(), $this->dataStructure);
			$this->formObject->addRecord($emptyRecord);
		} else {
			//$flexRecord = new t3lib_TCEforms_FlexRecord($editData, $this->dataStructure);
			$this->formObject->addRecord($editData['data']);
			//throw new RuntimeException('Editing existing flex records is not implemented yet.');
			// Traverse records and create objects for them
		}
	}

	protected function renderField() {
		/**
		 * TODO in this method:
		 *  - render language menu (see t3lib_TCEforms::getSingleField_typeFlex())
		 */

		return $this->formObject->render();
	}
}

?>
