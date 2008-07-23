<?php

abstract class t3lib_TCEforms_AbstractElement {
	/**
	 * @var t3lib_TCEforms  The parent TCEforms object
	 */
	protected $TCEformsObject;

	protected $alternativeName;

	/**
	 * @var array  The TCA config for the field
	 */
	protected $fieldConfig;

	protected $hiddenFieldListArr = array();

	protected static $cachedTSconfig;

	protected static $hooksInitialized = false;

	protected static $hookObjects = array();

	public function __construct() {
		global $TYPO3_CONF_VARS;

		if (!self::$hooksInitialized) {
				// Prepare user defined objects (if any) for hooks which extend this function:
			self::$hookObjects['getMainFields'] = array();
			if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass']))	{
				foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'] as $classRef)	{
					self::$hookObjects['getMainFields'][] = &t3lib_div::getUserObj($classRef);
				}
			}

			self::$hookObjects['getSingleFields'] = array();
			if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass']))	{
				foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'] as $classRef)	{
					self::$hookObjects['getSingleFields'][] = &t3lib_div::getUserObj($classRef);
				}
			}

			self::$hooksInitialized = true;
		}
	}

	public function init($table, $field, $row, $fieldConfig, $alternativeName='', $palette=0, $extra='', $pal=0) {
		// code mainly copied/moved from t3lib_tceforms::getSingleField

		$this->fieldConfig = $fieldConfig;
		$this->table = $table;
		$this->row = $row;
		$this->field = $field;

		$this->prependFormFieldNames = $this->TCEformsObject->prependFormFieldNames;
		$this->prependFormFieldNames_file = $this->TCEformsObject->prependFormFieldNames_file;

		$this->alternativeName = $alternativeName;
		$this->palette = $palette;
		$this->extra = $extra;
		$this->pal = $pal;


			// Make sure to load full $TCA array for the table:
		// @TODO: perhaps move this somewhere else, at least out of this class - it seems to
		//        be double and triple work if done with every element
		t3lib_div::loadTCA($table);

			// only used temporarily, should be removed later on
		$PA = array();
		$PA['altName'] = $altName;
		$PA['palette'] = $palette;
		$PA['extra'] = $extra;
		$PA['pal'] = $pal;

			// Get the TCA configuration for the current field:
		$PA['fieldConf'] = $TCA[$table]['columns'][$field];
		$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ? $PA['fieldConf']['config']['form_type'] : $PA['fieldConf']['config']['type'];	// Using "form_type" locally in this script
		$this->PA = $PA;

			// Hook: getSingleField_preProcess
		foreach (self::$hookObjects['getSingleFields'] as $hookObj)	{
			if (method_exists($hookObj,'getSingleField_preProcess'))	{
				$hookObj->getSingleField_preProcess($this->table, $this->field, $this->row, $this->alternativeName, $this->palette, $this->extra, $this->pal, $this);
			}
		}


			// commented out because IRRE is not enabled by now -- andreaswolf, 23.07.2008
		//$skipThisField = $this->inline->skipField($table, $field, $this->row, $fieldConf['config']);


	}

	public function renderField() {
		global $BE_USER, $TCA;

		// Now, check if this field is configured and editable (according to excludefields + other configuration)
		if (	is_array($this->fieldConfig) &&
				!$skipThisField &&
				(!$this->fieldConfig['exclude'] || $BE_USER->check('non_exclude_fields',$this->table.':'.$field)) &&
				$this->fieldConfig['config']['form_type']!='passthrough' &&
				($this->RTEenabled || !$this->fieldConfig['config']['showIfRTE']) &&
				(!$this->fieldConfig['displayCond'] || $this->isDisplayCondition($this->fieldConfig['displayCond'], $this->row)) &&
				(!$TCA[$this->table]['ctrl']['languageField'] || $this->fieldConfig['l10n_display'] || strcmp($this->fieldConfig['l10n_mode'],'exclude') || $this->row[$TCA[$this->table]['ctrl']['languageField']]<=0) &&
				(!$TCA[$this->table]['ctrl']['languageField'] || !$this->localizationMode || $this->localizationMode===$this->fieldConfig['l10n_cat'])
			) {

				// Fetching the TSconfig for the current table/field. This includes the $this->row which means that
			$fieldTSConfig = self::setTSconfig($this->table,$this->row,$this->field);

				// If the field is NOT disabled from TSconfig (which it could have been) then render it
			if (!$fieldTSConfig['disabled'])	{
					// Override fieldConf by fieldTSconfig:
				$this->fieldConfig['config'] = $this->overrideFieldConf($this->fieldConfig['config'], $fieldTSConfig);

					// Init variables:
				$this->itemFormElName = $this->prependFormFieldNames.'['.$this->table.']['.$this->row['uid'].']['.$this->field.']'; // Form field name
				$this->itemFormElName_file = $this->prependFormFieldNames_file.'['.$this->table.']['.$this->row['uid'].']['.$this->field.']'; // Form field name, in case of file uploads
				$this->itemFormElValue = $this->row[$this->field]; // The value to show in the form field.
				$this->itemFormElID = $this->prependFormFieldNames.'_'.$this->table.'_'.$this->row['uid'].'_'.$this->field;

					// set field to read-only if configured for translated records to show default language content as readonly
				if ($this->fieldConfig['l10n_display'] && t3lib_div::inList($this->fieldConfig['l10n_display'], 'defaultAsReadonly') && $this->row[$TCA[$this->table]['ctrl']['languageField']]) {
					$this->fieldConfig['config']['readOnly'] =  true;
					$this->itemFormElValue = $this->defaultLanguageData[$this->table.':'.$this->row['uid']][$field];
				}

					// Create a JavaScript code line which will ask the user to save/update the form due to changing the element. This is used for eg. "type" fields and others configured with "requestUpdate"
				if (
				    ($TCA[$this->table]['ctrl']['type'] && !strcmp($field,$TCA[$this->table]['ctrl']['type'])) ||
				    ($TCA[$this->table]['ctrl']['requestUpdate'] && t3lib_div::inList($TCA[$this->table]['ctrl']['requestUpdate'],$field))
				    ) {

					if($GLOBALS['BE_USER']->jsConfirmation(1))	{
						$alertMsgOnChange = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
					} else {
						$alertMsgOnChange = 'if (TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
					}
				} else {
					$alertMsgOnChange = '';
				}

					// Render as a hidden field?
				if (in_array($field,$this->hiddenFieldListArr))	{
					$this->hiddenFieldAccum[]='<input type="hidden" name="'.$this->itemFormElName.'" value="'.htmlspecialchars($this->itemFormElValue).'" />';
				} else {	// Render as a normal field:

						// If the field is NOT a palette field, then we might create an icon which links to a palette for the field, if one exists.
					if (!$this->palette)	{
						$paletteFields = $this->TCEformsObject->loadPaletteElements($this->table, $this->row, $pal);
						if ($pal && $this->TCEformsObject->isPalettesCollapsed($this->table,$pal) && count($paletteFields))	{
							list($thePalIcon,$palJSfunc) = $this->TCEformsObject->wrapOpenPalette('<img'.t3lib_iconWorks::skinImg($this->TCEformsObject->backPath,'gfx/options.gif','width="18" height="16"').' border="0" title="'.htmlspecialchars($this->getLL('l_moreOptions')).'" alt="" />',$this->table,$this->row,$pal,1);
						} else {
							$thePalIcon = '';
							$palJSfunc = '';
						}
					}
						// onFocus attribute to add to the field:
					$onFocus = ($palJSfunc && !$BE_USER->uc['dontShowPalettesOnFocusInAB']) ? ' onfocus="'.htmlspecialchars($palJSfunc).'"' : '';

						// Find item
					$item='';
					$this->label = ($this->alternativeName ? $this->alternativeName : $this->fieldConfig['label']);
					$this->label = ($fieldTSConfig['label'] ? $fieldTSConfig['label'] : $this->label);
					$this->label = ($fieldTSConfig['label.'][$GLOBALS['LANG']->lang] ? $fieldTSConfig['label.'][$GLOBALS['LANG']->lang] : $this->label);
					$this->label = $this->sL($this->label);
						// JavaScript code for event handlers:
					$this->fieldChangeFunc=array();
					$this->fieldChangeFunc['TBE_EDITOR_fieldChanged'] = "TBE_EDITOR.fieldChanged('".$this->table."','".$this->row['uid']."','".$field."','".$this->itemFormElName."');";
					$this->fieldChangeFunc['alert']=$alertMsgOnChange;
						// if this is the child of an inline type and it is the field creating the label
					// disabled while IRRE is not supported -- andreaswolf, 23.07.2008
					/*if ($this->inline->isInlineChildAndLabelField($this->table, $field)) {
						$fieldChangeFunc['inline'] = "inline.handleChangedField('".$this->itemFormElName."','".$this->inline->inlineNames['object']."[$this->table][".$this->row['uid']."]');";
					}*/

						// Based on the type of the item, call a render function:
					//$item = $this->getSingleField_SW($this->table,$field,$this->row,$PA);
					$item = $this->render();

						// Add language + diff
					if ($this->fieldConfig['l10n_display'] && (t3lib_div::inList($this->fieldConfig['l10n_display'], 'hideDiff') || t3lib_div::inList($this->fieldConfig['l10n_display'], 'defaultAsReadonly'))) {
						$renderLanguageDiff = false;
					} else {
						$renderLanguageDiff = true;
					}

					if ($renderLanguageDiff) {
						$item = $this->TCEformsObject->renderDefaultLanguageContent($this->table,$this->field,$this->row,$item);
						$item = $this->TCEformsObject->renderDefaultLanguageDiff($this->table,$this->field,$this->row,$item);
					}

						// If the record has been saved and the "linkTitleToSelf" is set, we make the field name into a link, which will load ONLY this field in alt_doc.php
					$this->label = t3lib_div::deHSCentities(htmlspecialchars($this->label));
					if (t3lib_div::testInt($this->row['uid']) && $fieldTSConfig['linkTitleToSelf'] && !t3lib_div::_GP('columnsOnly'))	{
						$lTTS_url = $this->TCEformsObject->backPath.'alt_doc.php?edit['.$this->table.']['.$this->row['uid'].']=edit&columnsOnly='.$field.'&returnUrl='.rawurlencode($this->thisReturnUrl());
						$this->label = '<a href="'.htmlspecialchars($lTTS_url).'">'.$this->label.'</a>';
					}

						// Create output value:
					if ($this->fieldConfig['config']['form_type']=='user' && $this->fieldConfig['config']['noTableWrapping'])	{
						$out = $item;
					} elseif ($this->palette)	{
							// Array:
						$out=array(
							'NAME'=>$this->label,
							'ID'=>$this->row['uid'],
							'FIELD'=>$field,
							'TABLE'=>$this->table,
							'ITEM'=>$item,
							'HELP_ICON' => $this->TCEformsObject->helpTextIcon($this->table,$field,1)
						);
						$out = $this->TCEformsObject->addUserTemplateMarkers($out,$this->table,$field,$this->row,$PA);
					} else {
							// String:
						$out=array(
							'NAME'=>$this->label,
							'ITEM'=>$item,
							'TABLE'=>$this->table,
							'ID'=>$this->row['uid'],
							'HELP_ICON'=>$this->TCEformsObject->helpTextIcon($this->table,$field),
							'HELP_TEXT'=>$this->TCEformsObject->helpText($this->table,$field),
							'PAL_LINK_ICON'=>$thePalIcon,
							'FIELD'=>$field
						);
						$out = $this->TCEformsObject->addUserTemplateMarkers($out,$this->table,$field,$this->row,$PA);
							// String:
						$out = $this->TCEformsObject->intoTemplate($out);
					}
				}
			} else $this->commentMessages[]=$this->prependFormFieldNames.'['.$this->table.']['.$this->row['uid'].']['.$field.']: Disabled by TSconfig';
		}

			// Hook: getSingleField_postProcess
		foreach (self::$hookObjects['getSingleFields'] as $hookObj)	{
			if (method_exists($hookObj,'getSingleField_postProcess'))	{
				$hookObj->getSingleField_postProcess($this->table, $this->field, $this->row, $this->alternativeName, $this->palette, $this->extra, $this->pal, $this);
			}
		}

		return $out;
	}

	abstract public function render();

	public function setTCEformsObject(t3lib_TCEforms $TCEformsObject) {
		$this->TCEformsObject = $TCEformsObject;
	}

	/**
	 * Calculate and return the current "types" pointer value for a record
	 *
	 * @param	string		The table name. MUST be in $TCA
	 * @param	array		The row from the table, should contain at least the "type" field, if applicable.
	 * @return	string		Return the "type" value for this record, ready to pick a "types" configuration from the $TCA array.
	 */
	// copied from t3lib_tceforms -- andreaswolf, 23.07.2008
	function getRTypeNum()	{
		global $TCA;

			// If there is a "type" field configured...
		if ($TCA[$this->table]['ctrl']['type'])	{
			$typeFieldName = $TCA[$this->table]['ctrl']['type'];
			$typeNum=$this->row[$typeFieldName];  // Get value of the row from the record which contains the type value.
			if (!strcmp($typeNum,''))	$typeNum=0;	 // If that value is an empty string, set it to "0" (zero)
		} else {
			$typeNum = 0;  // If no "type" field, then set to "0" (zero)
		}

		$typeNum = (string)$typeNum;  // Force to string. Necessary for eg '-1' to be recognized as a type value.
		if (!$TCA[$this->table]['types'][$typeNum])	{  // However, if the type "0" is not found in the "types" array, then default to "1" (for historical reasons)
			$typeNum = 1;
		}

		return $typeNum;
	}

	/**
	 * Returns TSconfig for table/row
	 * Multiple requests to this function will return cached content so there is no performance loss in calling this many times since the information is looked up only once.
	 *
	 * @param	string		The table name
	 * @param	array		The table row (Should at least contain the "uid" value, even if "NEW..." string. The "pid" field is important as well, and negative values will be intepreted as pointing to a record from the same table.)
	 * @param	string		Optionally you can specify the field name as well. In that case the TSconfig for the field is returned.
	 * @return	mixed		The TSconfig values (probably in an array)
	 * @see t3lib_BEfunc::getTCEFORM_TSconfig()
	 */
	static function setTSconfig($table,$row,$field='')	{
		$mainKey = $table.':'.$row['uid'];
		if (!isset(self::$cachedTSconfig[$mainKey]))	{
			self::$cachedTSconfig[$mainKey] = t3lib_BEfunc::getTCEFORM_TSconfig($table,$row);
		}
		if ($field)	{
			return self::$cachedTSconfig[$mainKey][$field];
		} else {
			return self::$cachedTSconfig[$mainKey];
		}
	}

	/**
	 * Overrides the TCA field configuration by TSconfig settings.
	 *
	 * Example TSconfig: TCEform.<table>.<field>.config.appearance.useSortable = 1
	 * This overrides the setting in $TCA[<table>]['columns'][<field>]['config']['appearance']['useSortable'].
	 *
	 * @param	array		$fieldConfig: TCA field configuration
	 * @param	array		$TSconfig: TSconfig
	 * @return	array		Changed TCA field configuration
	 */
	function overrideFieldConf($fieldConfig, $TSconfig) {
		if (is_array($TSconfig)) {
			$TSconfig = t3lib_div::removeDotsFromTS($TSconfig);
			$type = $fieldConfig['type'];
			if (is_array($TSconfig['config']) && is_array($this->allowOverrideMatrix[$type])) {
					// Check if the keys in TSconfig['config'] are allowed to override TCA field config:
				foreach (array_keys($TSconfig['config']) as $key) {
					if (!in_array($key, $this->allowOverrideMatrix[$type], true)) {
						unset($TSconfig['config'][$key]);
					}
				}
					// Override TCA field config by remaining TSconfig['config']:
				if (count($TSconfig['config'])) {
					$fieldConfig = t3lib_div::array_merge_recursive_overrule($fieldConfig, $TSconfig['config']);
				}
			}
		}

		return $fieldConfig;
	}

	/**
	 * Fetches language label for key
	 *
	 * @param	string		Language label reference, eg. 'LLL:EXT:lang/locallang_core.php:labels.blablabla'
	 * @return	string		The value of the label, fetched for the current backend language.
	 */
	function sL($str)	{
		return $GLOBALS['LANG']->sL($str);
	}

	/**
	 * Returns true, if the evaluation of the required-field code is OK.
	 *
	 * @param	string		The required-field code
	 * @param	array		The record to evaluate
	 * @param	string		FlexForm value key, eg. vDEF
	 * @return	boolean
	 */
	function isDisplayCondition($displayCond,$row,$ffValueKey='')	{
		$output = FALSE;

		$parts = explode(':',$displayCond);
		switch((string)$parts[0])	{	// Type of condition:
			case 'FIELD':
				$theFieldValue = $ffValueKey ? $row[$parts[1]][$ffValueKey] : $row[$parts[1]];

				switch((string)$parts[2])	{
					case 'REQ':
						if (strtolower($parts[3])=='true')	{
							$output = $theFieldValue ? TRUE : FALSE;
						} elseif (strtolower($parts[3])=='false') {
							$output = !$theFieldValue ? TRUE : FALSE;
						}
					break;
					case '>':
						$output = $theFieldValue > $parts[3];
					break;
					case '<':
						$output = $theFieldValue < $parts[3];
					break;
					case '>=':
						$output = $theFieldValue >= $parts[3];
					break;
					case '<=':
						$output = $theFieldValue <= $parts[3];
					break;
					case '-':
					case '!-':
						$cmpParts = explode('-',$parts[3]);
						$output = $theFieldValue >= $cmpParts[0] && $theFieldValue <= $cmpParts[1];
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
					case 'IN':
					case '!IN':
						$output = t3lib_div::inList($parts[3],$theFieldValue);
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
					case '=':
					case '!=':
						$output = t3lib_div::inList($parts[3],$theFieldValue);
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
				}
			break;
			case 'EXT':
				switch((string)$parts[2])	{
					case 'LOADED':
						if (strtolower($parts[3])=='true')	{
							$output = t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						} elseif (strtolower($parts[3])=='false') {
							$output = !t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'REC':
				switch((string)$parts[1])	{
					case 'NEW':
						if (strtolower($parts[2])=='true')	{
							$output = !(intval($row['uid']) > 0) ? TRUE : FALSE;
						} elseif (strtolower($parts[2])=='false') {
							$output = (intval($row['uid']) > 0) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'HIDE_L10N_SIBLINGS':
				if ($ffValueKey==='vDEF')	{
					$output = TRUE;
				} elseif ($parts[1]==='except_admin' && $GLOBALS['BE_USER']->isAdmin())	{
					$output = TRUE;
				}
			break;
			case 'HIDE_FOR_NON_ADMINS':
				$output = $GLOBALS['BE_USER']->isAdmin() ? TRUE : FALSE;
			break;
			case 'VERSION':
				switch((string)$parts[1])	{
					case 'IS':
						if (strtolower($parts[2])=='true')	{
							$output = intval($row['pid'])==-1 ? TRUE : FALSE;
						} elseif (strtolower($parts[2])=='false') {
							$output = !(intval($row['pid'])==-1) ? TRUE : FALSE;
						}
					break;
				}
			break;
		}

		return $output;
	}
}