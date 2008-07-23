<?php


class t3lib_tceforms_form {

	/**
	 * @var string  The fieldname of the save button
	 */
	protected $doSaveFieldName = 'doSave';

	/**
	 * @var string  The render mode. May be any of "mainFields", "soloField" or "listedFields"
	 */
	protected $renderMode = 'mainFields';

	/**
	 * @var string  The table of the record to render
	 */
	protected $table;
	/**
	 * @var array  The record to render
	 */
	protected $record;

	/**
	 * @var boolean  Whether or not the palettes (secondary options) are collapsed
	 */
	protected $palettesCollapsed;

	/**
	 * The constructor of this class
	 *
	 * @param unknown_type $table
	 * @param unknown_type $row
	 */
	public function __construct($table, $row) {
		$this->prependFormFieldNames = 'data';
		$this->formName = 'editform';
		$this->setNewBEDesign();
		$this->docLarge = $GLOBALS['BE_USER']->uc['edit_wideDocument'] ? 1 : 0;
		$this->edit_showFieldHelp = $GLOBALS['BE_USER']->uc['edit_showFieldHelp'];

		$this->edit_docModuleUpload = $GLOBALS['BE_USER']->uc['edit_docModuleUpload'];
		$this->titleLen = $GLOBALS['BE_USER']->uc['titleLen'];		// @deprecated

		$this->inline->init($this);

			// set table and row
		$this->table = $table;
		$this->record = $row;
	}

	/**
	 * Renders the form; this function is the successor of the old
	 * t3lib_tceforms::getSoloField()/getMainFields()/...
	 *
	 *
	 */
	public function render() {
			// Hook: getMainFields_preProcess (requested by Thomas Hempel for use with the "dynaflex" extension)
		foreach ($this->hookObjectsMainFields as $hookObj)	{
			if (method_exists($hookObj,'getMainFields_preProcess'))	{
				$hookObj->getMainFields_preProcess($table,$row,$this);
			}
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