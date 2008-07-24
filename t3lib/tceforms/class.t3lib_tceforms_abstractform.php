<?php


abstract class t3lib_TCEforms_AbstractForm {

	/**
	 * @var string  The fieldname of the save button
	 */
	protected $doSaveFieldName = 'doSave';

	/**
	 * @var string  The render mode. May be any of "mainFields", "soloField" or "listedFields"
	 */
	//protected $renderMode = 'mainFields';

	/**
	 * @var string  The table of the record to render
	 */
	protected $table;
	/**
	 * @var array  The record to render
	 */
	protected $record;

	/**
	 * @var array  TCA configuration for the table (reference!)
	 */
	protected $tableTCAconfig;

	protected $typeNumber;

	/**
	 * @var array  The list of items to display
	 */
	protected $itemList;

	/**
	 * @var boolean  Whether or not the palettes (secondary options) are collapsed
	 */
	protected $palettesCollapsed;

	/**
	 * @var array  The array of elements to exclude from rendering
	 * @see setExcludeElements()
	 */
	protected $excludeElements;

	/**
	 * The constructor of this class
	 *
	 * @param string   $table
	 * @param array    $row
	 * @param integer  $typeNumber
	 */
	public function __construct($table, $row) {
		global $TCA;

			// TODO: Refactor this!
		$this->prependFormFieldNames = 'data';
		$this->formName = 'editform';
		//$this->setNewBEDesign();
		$this->docLarge = $GLOBALS['BE_USER']->uc['edit_wideDocument'] ? 1 : 0;
		$this->edit_showFieldHelp = $GLOBALS['BE_USER']->uc['edit_showFieldHelp'];

		$this->edit_docModuleUpload = $GLOBALS['BE_USER']->uc['edit_docModuleUpload'];
		$this->titleLen = $GLOBALS['BE_USER']->uc['titleLen'];		// @deprecated

		//$this->inline->init($this);


			// check if table exists in TCA. This is required!
		if (!$TCA[$table]) {
			// TODO: throw exception here
			die('Table '.$table.'does not exist! [1216891229]');
		}

			// set table and row
		$this->table = $table;
		$this->record = $row;


		$this->tableTCAconfig = &$TCA[$this->table];

			// set type number
		$this->setRecordTypeNumber();

		/*if ($this->typeNumber === '') {
			// TODO: throw exception here
			die('typeNumber '.$this->typeNumber.' does not exist for table '.$this->table.' [1216891550]');
		}*/



	}

	/**
	 * Finds possible field to add to the form, based on subtype fields.
	 *
	 * @return	array		An array containing two values: 1) Another array containing fieldnames to add and 2) the subtype value field.
	 * @see getMainFields()
	 */
	protected function getFieldsToAdd()	{
		global $TCA;

			// Init:
		$addElements = array();

			// If a subtype field is defined for the type
		if ($this->tableTCAconfig['types'][$this->typeNumber]['subtype_value_field'])	{
			$subtypeField = $this->tableTCAconfig['types'][$this->typeNumber]['subtype_value_field'];
			if (trim($this->tableTCAconfig['types'][$this->typeNumber]['subtypes_addlist'][$this->record[$subtypeField]]))	{
				$addElements = t3lib_div::trimExplode(',', $this->tableTCAconfig['types'][$this->typeNumber]['subtypes_addlist'][$this->record[$subtypeField]], 1);
			}
		}

			// Return the array
		return array($addElements, $subtypeField);
	}

	/**
	 * Merges the current [types][showitem] array with the array of fields to add for the current subtype field of the "type" value.
	 *
	 * @param	array		A [types][showitem] list of fields, exploded by ","
	 * @param	array		The output from getFieldsToAdd()
	 * @return	array		Return the modified $fields array.
	 * @see getMainFields(),getFieldsToAdd()
	 */
	protected function mergeFieldsWithAddedFields($fields,$fieldsToAdd)	{
		if (count($fieldsToAdd[0]))	{
			reset($fields);
			$c=0;
			while(list(,$fieldInfo)=each($fields))	{
				$parts = explode(';',$fieldInfo);
				if (!strcmp(trim($parts[0]),$fieldsToAdd[1]))	{
					array_splice(
						$fields,
						$c+1,
						0,
						$fieldsToAdd[0]
					);
					break;
				}
				$c++;
			}
		}
		return $fields;
	}

	/**
	 * Renders the form; this function is the successor of the old
	 * t3lib_tceforms::getSoloField()/getMainFields()/...
	 *
	 *
	 */
	public function render() {
			// Hook: getMainFields_preProcess (requested by Thomas Hempel for use with the "dynaflex" extension)
		/*foreach ($this->hookObjectsMainFields as $hookObj)	{
			if (method_exists($hookObj,'getMainFields_preProcess'))	{
				$hookObj->getMainFields_preProcess($table,$row,$this);
			}
		}*/
	}

	public function isExcludeElement($elementName) {
		// TODO: check $elementName against array of excluded elements
		return t3lib_div::inArray($this->excludeElements, $elementName);
	}

	/**
	 * Producing an array of field names NOT to display in the form, based on settings
	 * from subtype_value_field, bitmask_excludelist_bits etc.
	 *
	 * NOTICE: This list is in NO way related to the "excludeField" flag
	 *
	 * Sets $this->excludeElements to an array with fieldnames as values. The fieldnames are
	 * those which should NOT be displayed "anyways"
	 *
	 * @return	void
	 */
	protected function setExcludeElements() {
		global $TCA;

			// Init:
		$this->excludeElements = array();

			// If a subtype field is defined for the type
		if ($this->tableTCAconfig['types'][$this->typeNumber]['subtype_value_field'])	{
			$subtypeField = $this->tableTCAconfig['types'][$this->typeNumber]['subtype_value_field'];
			if (trim($this->tableTCAconfig['types'][$this->typeNumber]['subtypes_excludelist'][$this->record[$subtypeField]]))	{
				$this->excludeElements=t3lib_div::trimExplode(',',$this->tableTCAconfig['types'][$this->typeNumber]['subtypes_excludelist'][$this->record[$subtypeField]],1);
			}
		}

			// If a bitmask-value field has been configured, then find possible fields to exclude based on that:
		if ($this->tableTCAconfig['types'][$this->typeNumber]['bitmask_value_field'])	{
			$subtypeField = $this->tableTCAconfig['types'][$this->typeNumber]['bitmask_value_field'];
			$subtypeValue = t3lib_div::intInRange($this->record[$subtypeField],0);
			if (is_array($this->tableTCAconfig['types'][$this->typeNumber]['bitmask_excludelist_bits']))	{
				reset($this->tableTCAconfig['types'][$this->typeNumber]['bitmask_excludelist_bits']);
				while(list($bitKey,$eList)=each($this->tableTCAconfig['types'][$this->typeNumber]['bitmask_excludelist_bits']))	{
					$bit=substr($bitKey,1);
					if (t3lib_div::testInt($bit))	{
						$bit = t3lib_div::intInRange($bit,0,30);
						if (
								(substr($bitKey,0,1)=='-' && !($subtypeValue&pow(2,$bit))) ||
								(substr($bitKey,0,1)=='+' && ($subtypeValue&pow(2,$bit)))
							) {
							$this->excludeElements = array_merge($this->excludeElements,t3lib_div::trimExplode(',',$eList,1));
						}
					}
				}
			}
		}
	}

	/**
	 * Calculate the current "types" pointer value for the record this form is instantiated for
	 *
	 * Sets $this->typeNumber to the types pointer value.
	 *
	 * @return	void
	 */
	protected function setRecordTypeNumber()	{
		global $TCA;

			// If there is a "type" field configured...
		if ($this->tableTCAconfig['ctrl']['type'])	{
			$typeFieldName = $this->tableTCAconfig['ctrl']['type'];
			$this->typeNumber=$this->record[$typeFieldName];	// Get value of the row from the record which contains the type value.
			if (!strcmp($this->typeNumber,''))	$this->typeNumber = 0;			// If that value is an empty string, set it to "0" (zero)
		} else {
			$this->typeNumber = 0;	// If no "type" field, then set to "0" (zero)
		}

		$this->typeNumber = (string)$this->typeNumber;		// Force to string. Necessary for eg '-1' to be recognized as a type value.
		if (!$this->tableTCAconfig['types'][$this->typeNumber])	{	// However, if the type "0" is not found in the "types" array, then default to "1" (for historical reasons)
			$this->typeNumber = 1;
		}
	}

	public function __set($key, $value) {
			// DANGEROUS, may be used to overwrite *EVERYTHING* in this class! Should be secured later on
		if (substr($key, 0, 3) == 'set') {
			$varKey = substr($key, 3);
			$this->$varKey = $value;
		}
	}
}

?>