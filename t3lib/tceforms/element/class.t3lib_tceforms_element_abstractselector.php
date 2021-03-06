<?php

/**
 * Abstract base class for all TCEforms elements that allow selecting from various values
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
abstract class t3lib_TCEforms_Element_AbstractSelector extends t3lib_TCEforms_Element_Abstract {
	/**
	 * Default style for the selector boxes used for multiple items in "select" and "group" types.
	 *
	 * @var string
	 */
	protected $defaultMultipleSelectorStyle = 'width:250px;';

	/**
	 * The items that are selected in this element
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * The mode - may be db, file or folder
	 *
	 * @var string
	 * TODO move to Element_Group
	 */
	protected $mode = '';

	/**
	 * FIXME
	 *
	 * @var string
	 * TODO move to Element_Group
	 */
	protected $allowed = '';

	/**
	 * The uids of the records used in this selector
	 *
	 * @var array
	 */
	protected $uidList = array();


	/**
	 * @param string $selector
	 * @return string
	 *
	 * @see t3lib_TCEforms::dbFileIcons()
	 *
	 * @TODO cleanup rendering in this method
	 */
	protected function renderItemList($selector = '', $params = array()) {
			// Sets a flag which means some JavaScript is included on the page to support this element.
		$this->printNeededJS['dbFileIcons'] = TRUE;

		$disabled = $this->getDisabledCode();
		$options = $this->renderOptions();
		$icons = array(
			'L' => array(),
			'R' => array()
		);

		$selectorSize = $params['autoSizeMax'] ? t3lib_div::intInRange(count($this->items) + 1, t3lib_div::intInRange($params['size'], 1), $params['autoSizeMax']) : $params['size'];
		if (!$this->isReadOnly() && $this->shouldListBeRendered()) {
				// check against inline uniqueness
			// TODO: re-implement for IRRE
			/*$inlineParent = $this->inline->getStructureLevel(-1);
			if(is_array($inlineParent) && $inlineParent['uid']) {
				if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
					$objectPrefix = $this->inline->inlineNames['object'].'['.$table.']';
					$aOnClickInline = $objectPrefix.'|inline.checkUniqueElement|inline.setUniqueElement';
					$rOnClickInline = 'inline.revertUnique(\''.$objectPrefix.'\',null,\''.$uid.'\');';
				}
			}*/
			// TODO move this code to group field, as it is only required there
			if ($this->shouldRecordBrowserBeRendered() && !$params['noBrowser']) {
				$aOnClick = 'setFormValueOpenBrowser(\'' . $this->mode.'\',\'' . ($this->formFieldName . '|||' . $this->allowed . '|' . $aOnClickInline) . '\'); return false;';
				$icons['R'][] = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-insert-record', array('title' => htmlspecialchars(t3lib_TCEforms_Helper::getLL('l_browse_' . ($this->fieldSetup['config']['internal_type'] == 'db' ? 'db' : 'file'))))) .
						'</a>';
			}
			if ($this->shouldMoveIconsBeShown()) {
				if ($selectorSize >= 5) {
					$icons['L'][] = '<a href="#" onclick="setFormValueManipulate(\''.$this->formFieldName.'\',\'Top\'); return false;">'.
							t3lib_iconWorks::getSpriteIcon('actions-move-to-top', array('title' => htmlspecialchars(t3lib_TCEforms_Helper::getLL('l_move_to_top')))) .
							'</a>';
				}
				$icons['L'][] = '<a href="#" onclick="setFormValueManipulate(\''.$this->formFieldName.'\',\'Up\'); return false;">'.
						t3lib_iconWorks::getSpriteIcon('actions-move-up', array('title' => htmlspecialchars(t3lib_TCEforms_Helper::getLL('l_move_up')))) .
						'</a>';
				$icons['L'][] = '<a href="#" onclick="setFormValueManipulate(\''.$this->formFieldName.'\',\'Down\'); return false;">'.
						t3lib_iconWorks::getSpriteIcon('actions-move-down', array('title' => htmlspecialchars(t3lib_TCEforms_Helper::getLL('l_move_down')))) .
						'</a>';
				if ($selectorSize >= 5) {
					$icons['L'][] = '<a href="#" onclick="setFormValueManipulate(\''.$this->formFieldName.'\',\'Bottom\'); return false;">'.
							t3lib_iconWorks::getSpriteIcon('actions-move-to-bottom', array('title' => htmlspecialchars(t3lib_TCEforms_Helper::getLL('l_move_to_bottom')))) .
							'</a>';
				}
			}

			$this->checkClipboardElements();

			$rOnClick = $rOnClickInline . 'setFormValueManipulate(\'' . $this->formFieldName . '\',\'Remove\'); return false';
			$icons['L'][] = '<a href="#" onclick="' . htmlspecialchars($rOnClick) . '">'.
					t3lib_iconWorks::getSpriteIcon('actions-selection-delete', array('title' => htmlspecialchars(t3lib_TCEforms_Helper::getLL('l_remove_selected')))) .
					'</a>';
		}

		if (!$selector) {
			$selector = '<select id="' . uniqid('tceforms-multiselect-') . '" ' . (!$this->shouldListBeRendered() ? 'style="display: none"' : 'size="' . $selectorSize . '"' .
			  $this->insertDefaultElementStyle('group', 'tceforms-multiselect')) . ' multiple="multiple" name="' . $this->formFieldName . '_list" ' . $this->onFocus .
			  $params['style'] . $disabled . '>' . implode('', $options) . '</select>';
		}

		// TODO insert some kind of hook here to manipulate the icons (maybe neccessary for moving the browser functionality to Element_Group, see above)

			// TODO move this code to template
		$str = '<table border="0" cellpadding="0" cellspacing="0" width="1">
			' . ($params['headers'] ? '
				<tr>
					<td>' . $this->wrapLabels($params['headers']['selector']) . '</td>
					<td></td>
					<td></td>
					<td>' . ($params['thumbnails'] ? $this->wrapLabels($params['headers']['items']) : '') . '</td>
				</tr>' : '') .
			'
			<tr>
				<td valign="top">' .
					$selector .
					($this->shouldListBeRendered() ? '' : '<br />' . $this->wrapLabels($params['info'])) .
				'</td>
				<td valign="top" class="icons">' .
					implode('<br />', $icons['L']) . '</td>
				<td valign="top" class="icons">' .
					implode('<br />', $icons['R']) . '</td>
				<td valign="top" class="thumbnails">' .
					$this->wrapLabels($params['thumbnails']) .
				'</td>
			</tr>
		</table>';

			// Creating the hidden field which contains the actual value as a comma list.
		$str .= '<input type="hidden" name="' . $this->formFieldName . '" value="' . htmlspecialchars(implode(',', $this->uidList)) . '" />';

		return $str;
	}

	protected function shouldListBeRendered() {
		return !(isset($this->fieldSetup['config']['disable_controls']) && t3lib_div::inList($this->fieldSetup['config']['disable_controls'], 'list'));
	}

	protected function shouldRecordBrowserBeRendered() {
		return $this->shouldListBeRendered() && !(isset($this->fieldSetup['config']['disable_controls']) && t3lib_div::inList($this->fieldSetup['config']['disable_controls'], 'browser'));
	}

	protected function shouldMoveIconsBeShown() {
		return ($this->fieldSetup['config']['maxitems'] > 1);
	}

	protected function hasItems() {
		return count($this->items) > 0;
	}

	/**
	 * Renders the options for the selector.
	 *
	 * @return array
	 */
	abstract protected function renderOptions();

	protected function checkClipboardElements() {
		// TODO: move to Element_Group
		$mode = $this->fieldSetup['config']['internal_type'];

		$clipElements = $this->getClipboardElements($allowed,$mode);
		if (count($clipElements)) {
			$aOnClick = '';
			foreach($clipElements as $elValue) {
				if ($mode == 'db') {
					list($itemTable, $itemUid) = explode('|', $elValue);
					$itemTitle = $GLOBALS['LANG']->JScharCode(t3lib_BEfunc::getRecordTitle($itemTable, t3lib_BEfunc::getRecordWSOL($itemTable,$itemUid)));
					$elValue = $itemTable . '_' . $itemUid;
				} else {	// 'file', 'file_reference' and 'folder' mode
					$itemTitle = 'unescape(\'' . rawurlencode(basename($elValue)) . '\')';
				}
				$aOnClick .= 'setFormValueFromBrowseWin(\'' . $this->formFieldName . '\',unescape(\'' . rawurlencode(str_replace('%20', ' ', $elValue)) . '\'),' . $itemTitle . ');';
			}
			$aOnClick .= 'return false;';
			$icons['R'][] = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' .
					t3lib_iconWorks::getSpriteIcon('actions-document-paste-into', array('title' => htmlspecialchars(sprintf(t3lib_TCEforms_Helper::getLL('l_clipInsert_' . ($mode == 'db' ? 'db' : 'file')), count($clipElements))))) .
					'</a>';
		}
	}
}
