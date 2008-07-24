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
		$this->setNewBEDesign();
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
		$this->setRecordTypeNum();

		if (!$this->tableTCAconfig['types'][$this->typeNumber]) {
			// TODO: throw exception here
			die('TypeNumber '.$this->typeNumber.' does not exist for table '.$this->table.' [1216891550]');
		}



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
		if ($TCA[$this->table]['types'][$this->typeNum]['subtype_value_field'])	{
			$sTfield = $TCA[$this->table]['types'][$this->typeNum]['subtype_value_field'];
			if (trim($TCA[$this->table]['types'][$this->typeNum]['subtypes_excludelist'][$this->row[$sTfield]]))	{
				$this->excludeElements=t3lib_div::trimExplode(',',$TCA[$this->table]['types'][$this->typeNum]['subtypes_excludelist'][$this->row[$sTfield]],1);
			}
		}

			// If a bitmask-value field has been configured, then find possible fields to exclude based on that:
		if ($TCA[$this->table]['types'][$this->typeNum]['bitmask_value_field'])	{
			$sTfield = $TCA[$this->table]['types'][$this->typeNum]['bitmask_value_field'];
			$sTValue = t3lib_div::intInRange($this->row[$sTfield],0);
			if (is_array($TCA[$this->table]['types'][$this->typeNum]['bitmask_excludelist_bits']))	{
				reset($TCA[$this->table]['types'][$this->typeNum]['bitmask_excludelist_bits']);
				while(list($bitKey,$eList)=each($TCA[$this->table]['types'][$this->typeNum]['bitmask_excludelist_bits']))	{
					$bit=substr($bitKey,1);
					if (t3lib_div::testInt($bit))	{
						$bit = t3lib_div::intInRange($bit,0,30);
						if (
								(substr($bitKey,0,1)=='-' && !($sTValue&pow(2,$bit))) ||
								(substr($bitKey,0,1)=='+' && ($sTValue&pow(2,$bit)))
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
	 * Sets $this->typeNum to the types pointer value.
	 *
	 * @return	void
	 */
	protected function setRecordTypeNum()	{
		global $TCA;

			// If there is a "type" field configured...
		if ($TCA[$this->table]['ctrl']['type'])	{
			$typeFieldName = $TCA[$this->table]['ctrl']['type'];
			$typeNum=$this->row[$typeFieldName];	// Get value of the row from the record which contains the type value.
			if (!strcmp($this->typeNum,''))	$this->typeNum = 0;			// If that value is an empty string, set it to "0" (zero)
		} else {
			$this->typeNum = 0;	// If no "type" field, then set to "0" (zero)
		}

		$this->typeNum = (string)$this->typeNum;		// Force to string. Necessary for eg '-1' to be recognized as a type value.
		if (!$TCA[$this->table]['types'][$this->typeNum])	{	// However, if the type "0" is not found in the "types" array, then default to "1" (for historical reasons)
			$this->typeNum = 1;
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