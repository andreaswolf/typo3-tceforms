<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This class serves as an abstraction for a record from the database.
 *
 * @author     Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package    TYPO3
 * @subpackage t3lib_TCA
 */
class t3lib_TCA_Record {

	/**
	 * The table
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The data of the record
	 *
	 * @var array
	 */
	protected $recordData;

	/**
	 * The data for the default language (for localized records). This is e.g. neccessary to get the default
	 * value if a field is set to use the default language data for empty fields (mergeIfNotBlank)
	 *
	 * @var array
	 */
	protected $defaultLanguageData = array();

	/**
	 * The data structure object for the record
	 *
	 * @var t3lib_DataStructure_Tca
	 */
	protected $dataStructure;

	/**
	 * The type number for the record. This is calculated from the
	 *
	 * @var integer
	 */
	protected $typeNumber;

	/**
	 * The type configuration for this record.
	 *
	 * @var t3lib_DataStructure_Type
	 */
	protected $typeConfiguration;

	/**
	 * The constructor for this class.
	 *
	 * @param string $table The table this record belongs to
	 * @param array $recordData
	 * @param t3lib_DataStructure_Tca $dataStructure
	 */
	public function __construct($table, array $recordData, t3lib_DataStructure_Tca $dataStructure) {
		$this->table = $table;
		$this->recordData = $recordData;
		$this->dataStructure = $dataStructure;

		if (!is_numeric($recordData['uid'])) {
			$this->new = TRUE;
		}

		$this->setRecordTypeNumber();
	}

	public function getValue($field) {
		return $this->recordData[$field];
	}

	/**
	 * Returns the uid of this record, if any.
	 *
	 * @return integer
	 */
	public function getUid() {
		return $this->getValue('uid');
	}

	/**
	 * Returns the object representation of the data structure for this record.
	 * The source for this data structure could have been PHP-based TCA or an XML-based Flexform
	 * data structure
	 *
	 * @return t3lib_DataStructure_Tca
	 */
	public function getDataStructure() {
		return $this->dataStructure;
	}

	/**
	 * Returns the type object for this record.
	 *
	 * @return t3lib_DataStructure_Type
	 */
	public function getType() {
		return $this->dataStructure->getTypeObject($this->typeNumber);
	}

	/**
	 * Returns the table this record belongs to.
	 *
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * Returns the field values of this record
	 *
	 * @return array
	 */
	public function getRecordData() {
		return $this->recordData;
	}

	/**
	 * Calculate the current "types" pointer value for the record this form is instantiated for
	 *
	 * Sets $this->typeNumber to the types pointer value.
	 *
	 * @return void
	 */
	protected function setRecordTypeNumber() {
			// If there is a "type" field configured...
		if ($this->dataStructure->hasTypeField()) {
			$typeFieldName = $this->dataStructure->getTypeField();
				// Get value of the row from the record which contains the type value.
			$typeFieldConfig = $this->dataStructure->getFieldConfiguration($typeFieldName);
				// for localized records: check the default language for a type value
			$this->typeNumber = $this->getLanguageOverlayRawValue($typeFieldName);
				// If that value is an empty string, set it to "0" (zero)
			if ($this->typeNumber === '') $this->typeNumber = 0;
		} else {
			$this->typeNumber = 0;	// If no "type" field, then set to "0" (zero)
		}

			// Force to string. Necessary for eg '-1' to be recognized as a type value.
		$this->typeNumber = (string)$this->typeNumber;
			// However, if the type "0" is not found in the "types" array, then default to "1" (for historical reasons)
		if (!$this->dataStructure->typeExists($this->typeNumber)) {
			$this->typeNumber = 1;
		}

		$this->typeConfiguration = $this->dataStructure->getTypeObject($this->typeNumber);
	}

	/**
	 * Creates language-overlay for a field value
	 * This means the requested field value will be overridden with the data from the default language.
	 * Can be used to render read only fields for example.
	 *
	 * @param   string $fieldName  Field name represented by $item
	 * @return  mixed  Unprocessed field value merged with default language data if needed
	 *
	 * @TODO check if this could replace the method in Element_Abstract. This method has been copied here
	 *       because we need an overlay for determining the record type value (@see setRecordTypeNumber())
	 */
	protected function getLanguageOverlayRawValue($fieldName) {
		$value = $this->recordData[$fieldName];
		$fieldConfig = $this->dataStructure->getFieldObject($fieldName);

		if ($fieldConfig->getLocalizationMode() == 'exclude'
		  || ($fieldConfig->getLocalizationMode() == 'mergeIfNotBlank' && trim($this->defaultLanguageData[$fieldName] !== ''))) {

			$value = $this->defaultLanguageData[$fieldName];
		}

		return $value;
	}

	/**
	 * Will register data from original language records if the current record is a translation of another.
	 * The original data is shown with the edited record in the form. The information also includes possibly diff-views of what changed in the original record.
	 * Function called from outside (see alt_doc.php + quick edit) before rendering a form for a record
	 *
	 * @return void
	 *
	 * @TODO check
	 */
	protected function registerDefaultLanguageData()	{
			// Add default language:
		if ($this->dataStructure->hasLanguageField()
				&& $this->recordData[$this->dataStructure->getLanguageField()] > 0
				&& $this->dataStructure->getControlValue('transOrigPointerField')
				&& intval($this->getValue($this->dataStructure->getControlValue('transOrigPointerField'))) > 0) {

			$lookUpTable = $this->dataStructure->getControlValue('transOrigPointerTable') ? $this->dataStructure->getControlValue('transOrigPointerTable') : $this->table;

				// Get data formatted:
			$this->defaultLanguageData = t3lib_BEfunc::getRecordWSOL($lookUpTable, intval($this->getValue($this->dataStructure->getControlValue('transOrigPointerField'))));

				// Get data for diff:
			if ($this->dataStructure->getControlValue('transOrigDiffSourceField')) {
				$this->defaultLanguageData_diff = unserialize($this->getValue($this->dataStructure->getControlValue('transOrigDiffSourceField')));
			}

				// If there are additional preview languages, load information for them also:
			$previewLanguages = $this->getAdditionalPreviewLanguages();
			foreach($previewLanguages as $previewLanguage) {
				/** @var $t8Tools t3lib_transl8tools */
				$t8Tools = t3lib_div::makeInstance('t3lib_transl8tools');
				$translationInfo = $t8Tools->translationInfo($lookUpTable, intval($this->getValue($this->dataStructure->getControlValue('transOrigPointerField'))), $previewLanguage['uid']);
				if (is_array($translationInfo['translations'][$previewLanguage['uid']]))	{
					$this->additionalPreviewLanguageData[$previewLanguage['uid']] = t3lib_BEfunc::getRecordWSOL($this->table, intval($translationInfo['translations'][$previewLanguage['uid']]['uid']));
				}
			}
		}
	}

	/**
	 * Generates and return information about which languages the current user should see in preview, configured by options.additionalPreviewLanguages
	 *
	 * return array	Array of additional languages to preview
	 */
	public function getAdditionalPreviewLanguages() {
		static $cachedAdditionalPreviewLanguages;

		if (!isset($cachedAdditionalPreviewLanguages)) {
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.additionalPreviewLanguages'))	{
				$uids = t3lib_div::intExplode(',',$GLOBALS['BE_USER']->getTSConfigVal('options.additionalPreviewLanguages'));
				foreach($uids as $uid)	{
					if ($sys_language_rec = t3lib_BEfunc::getRecord('sys_language',$uid))	{
						$cachedAdditionalPreviewLanguages[$uid] = array('uid' => $uid);

						if ($sys_language_rec['static_lang_isocode'] && t3lib_extMgm::isLoaded('static_info_tables'))	{
							$staticLangRow = t3lib_BEfunc::getRecord('static_languages',$sys_language_rec['static_lang_isocode'],'lg_iso_2');
							if ($staticLangRow['lg_iso_2']) {
								$cachedAdditionalPreviewLanguages[$uid]['uid'] = $uid;
								$cachedAdditionalPreviewLanguages[$uid]['ISOcode'] = $staticLangRow['lg_iso_2'];
							}
						}
					}
				}
			} else {
					// None:
				$cachedAdditionalPreviewLanguages = array();
			}
		}
		return $cachedAdditionalPreviewLanguages;
	}
}