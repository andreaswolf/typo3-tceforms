<?php

class t3lib_TCEforms_IrreAjax {
	
	protected $inlineStructure = array();

	protected function init() {
		
	}

	/**
	 * General processor for AJAX requests concerning IRRE.
	 * (called by typo3/ajax.php)
	 *
	 * @param	array		$params: additional parameters (not used here)
	 * @param	TYPO3AJAX	&$ajaxObj: the TYPO3AJAX object of this request
	 * @return	void
	 */
	public function processAjaxRequest($params, &$ajaxObj) {
		$this->init();

		$ajaxArguments = t3lib_div::_GP('ajax');
		$ajaxIdParts = explode('::', $GLOBALS['ajaxID'], 2);

		if (isset($ajaxArguments) && is_array($ajaxArguments) && count($ajaxArguments)) {
			$ajaxMethod = $ajaxIdParts[1];
			switch ($ajaxMethod) {
				case 'createNewRecord':
				case 'synchronizeLocalizeRecords':
					$this->isAjaxCall = true;
						// Construct runtime environment for Inline Relational Record Editing:
					$this->processAjaxRequestConstruct($ajaxArguments);
						// Parse the DOM identifier (string), add the levels to the structure stack (array) and load the TCA config:
					$this->parseStructureString($ajaxArguments[0], true);
						// Render content:
					$ajaxObj->setContentFormat('jsonbody');
					$ajaxObj->setContent(
						call_user_func_array(array(&$this, $ajaxMethod), $ajaxArguments)
					);
					break;
				case 'setExpandedCollapsedState':
					$ajaxObj->setContentFormat('jsonbody');
					call_user_func_array(array(&$this, $ajaxMethod), $ajaxArguments);
					break;
			}
		}
	}

	/**
	 * Processor for AJAX requests setting the expanded/collapsed state of records within the backend
	 * user's record.
	 *
	 * @param	array		$params: additional parameters (not used here)
	 * @param	TYPO3AJAX	&$ajaxObj: the TYPO3AJAX object of this request
	 * @return	void
	 */
	public function setExpandedCollapsedState($domObjectId, $expand, $collapse) {
t3lib_div::devLog('Entered setExpandedCollapsedState', 't3lib_TCEforms_IrreAjax');
			// parse the DOM identifier (string), add the levels to the structure stack (array), but don't load TCA config
		$this->parseStructureString($domObjectId, false);
			// the current table - for this table we should add/import records
		$current = $this->inlineStructure['unstable'];
			// the top parent table - this table embeds the current table
		$top = $this->getStructureLevel(0);
t3lib_div::devLog('inlineStructure: ' . serialize($this->inlineStructure), 't3lib_TCEforms_IrreAjax');
t3lib_div::devLog('top: ' . serialize($top), 't3lib_TCEforms_IrreAjax');
t3lib_div::devLog('this: ' . serialize($this), 't3lib_TCEforms_IrreAjax');

			// only do some action if the top record and the current record were saved before
		if (t3lib_div::testInt($top['uid'])) {
			$inlineView = (array)unserialize($GLOBALS['BE_USER']->uc['inlineView']);
			$inlineViewCurrent =& $inlineView[$top['table']][$top['uid']];

			$expandUids = t3lib_div::trimExplode(',', $expand);
			$collapseUids = t3lib_div::trimExplode(',', $collapse);

				// set records to be expanded
			foreach ($expandUids as $uid) {
				$inlineViewCurrent[$current['table']][] = $uid;
			}
				// set records to be collapsed
			foreach ($collapseUids as $uid) {
				$inlineViewCurrent[$current['table']] = $this->removeFromArray($uid, $inlineViewCurrent[$current['table']]);
			}
t3lib_div::devLog('inlineViewCurrent: ' . serialize($inlineViewCurrent), 't3lib_TCEforms_IrreAjax');
				// save states back to database
			if (is_array($inlineViewCurrent[$current['table']])) {
				$inlineViewCurrent = array_unique($inlineViewCurrent);
				$GLOBALS['BE_USER']->uc['inlineView'] = serialize($inlineView);
				t3lib_div::devLog('Users\'s viewstate: ' . $GLOBALS['BE_USER']->uc['inlineView'], 't3lib_TCEforms_IrreAjax');
				$GLOBALS['BE_USER']->writeUC();
			}
		}
	}

	/**
	 * Convert the DOM object-id of an inline container to an array.
	 * The object-id could look like 'data[inline][tx_mmftest_company][1][employees]'.
	 * The result is written to $this->inlineStructure.
	 * There are two keys:
	 *  - 'stable': Containing full qualified identifiers (table, uid and field)
	 *  - 'unstable': Containting partly filled data (e.g. only table and possibly field)
	 *
	 * @param	string		$domObjectId: The DOM object-id
	 * @param	boolean		$loadConfig: Load the TCA configuration for that level (default: true)
	 * @return	void
	 */
	protected function parseStructureString($string, $loadConfig=true) {
		$unstable = array();
		t3lib_div::devLog('string: ' . $string, __CLASS__);
		$vector = array('table', 'uid', 'field');
		$pattern = '/^'.$this->prependNaming.'\[(.+?)\]\[(.+)\]$/';
		if (preg_match($pattern, $string, $match)) {
			$this->inlineFirstPid = $match[1];
			$parts = explode('][', $match[2]);
			t3lib_div::devLog('match[2]: ' . $match[2], __CLASS__);
			$partsCnt = count($parts);
			for ($i = 0; $i < $partsCnt; $i++) {
				if ($i > 0 && $i % 3 == 0) {
						// load the TCA configuration of the table field and store it in the stack
					if ($loadConfig) {
						t3lib_div::loadTCA($unstable['table']);
						$unstable['config'] = $GLOBALS['TCA'][$unstable['table']]['columns'][$unstable['field']]['config'];
							// Fetch TSconfig:
						$TSconfig = $this->fObj->setTSconfig(
							$unstable['table'],
							array('uid' => $unstable['uid'], 'pid' => $this->inlineFirstPid),
							$unstable['field']
						);
							// Override TCA field config by TSconfig:
						if (!$TSconfig['disabled']) {
							$unstable['config'] = $this->fObj->overrideFieldConf($unstable['config'], $TSconfig);
						}
						$unstable['localizationMode'] = t3lib_BEfunc::getInlineLocalizationMode($unstable['table'], $unstable['config']);
					}
					$this->inlineStructure['stable'][] = $unstable;
					$unstable = array();
				}
				$unstable[$vector[$i % 3]] = $parts[$i];
			}
			$this->updateStructureNames();
			if (count($unstable)) $this->inlineStructure['unstable'] = $unstable;
		}
	}

	/**
	 * Get a level from the stack and return the data.
	 * If the $level value is negative, this function works top-down,
	 * if the $level value is positive, this function works bottom-up.
	 *
	 * @param	integer		$level: Which level to return
	 * @return	array		The item of the stack at the requested level
	 */
	protected function getStructureLevel($level) {
		$inlineStructureCount = count($this->inlineStructure['stable']);
		if ($level < 0) $level = $inlineStructureCount+$level;
		if ($level >= 0 && $level < $inlineStructureCount)
			return $this->inlineStructure['stable'][$level];
		else
			return false;
	}

	/**
	 * Remove an element from an array.
	 *
	 * @param	mixed		$needle: The element to be removed.
	 * @param	array		$haystack: The array the element should be removed from.
	 * @param	mixed		$strict: Search elements strictly.
	 * @return	array		The array $haystack without the $needle
	 */
	protected function removeFromArray($needle, $haystack, $strict=null) {
		$pos = array_search($needle, $haystack, $strict);
		if ($pos !== false) unset($haystack[$pos]);
		return $haystack;
	}


	/**
	 * For common use of DOM object-ids and form field names of a several inline-level,
	 * these names/identifiers are preprocessed and set to $this->inlineNames.
	 * This function is automatically called if a level is pushed to or removed from the
	 * inline structure stack.
	 *
	 * @return	void
	 */
	function updateStructureNames() {
		$current = $this->getStructureLevel(-1);
			// if there are still more inline levels available
		if ($current !== false) {
			$lastItemName = $this->getStructureItemName($current);
			$this->inlineNames = array(
				'form' => $this->prependFormFieldNames.$lastItemName,
				'object' => $this->prependNaming.'['.$this->inlineFirstPid.']'.$this->getStructurePath(),
			);
			// if there are no more inline levels available
		} else {
			$this->inlineNames = array();
		}
	}
}

?>