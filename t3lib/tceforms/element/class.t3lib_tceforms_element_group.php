<?php


class t3lib_TCEforms_Element_Group extends t3lib_TCEforms_Element_AbstractSelector {
	protected function renderField() {
			// Init:
		$config = $this->fieldSetup['config'];
		$internal_type = $config['internal_type'];
		$show_thumbs = $config['show_thumbs'];
		$size = intval($config['size']);
		$maxitems = t3lib_div::intInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		$minitems = t3lib_div::intInRange($config['minitems'], 0);
		$allowed = trim($config['allowed']);
		$disallowed = trim($config['disallowed']);

		$disabled = $this->getDisabledCode();

		$item.= '<input type="hidden" name="'.$this->formFieldName.'_mul" value="'.($config['multiple']?1:0).'"'.$disabled.' />';
		$this->contextObject->registerRequiredFieldRange($this->formFieldName, array($minitems,$maxitems,'imgName'=>$table.'_'.$row['uid'].'_'.$field));
		$info='';

			// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		$specConf = $this->getSpecConfFromString($this->extra, $this->fieldSetup['defaultExtras']);

			// Acting according to either "file" or "db" type:
		switch((string)$config['internal_type'])	{
			case 'file_reference':
				$config['uploadfolder'] = '';
				// Fall through
			case 'file':	// If the element is of the internal type "file":

					// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',',$allowed,1);
				if (!count($tempFT))	{$info.='*';}
				foreach($tempFT as $ext)	{
					if ($ext)	{
						$info.=strtoupper($ext).' ';
					}
				}
					// Creating string, showing disallowed types:
				$tempFT_dis = t3lib_div::trimExplode(',',$disallowed,1);
				if (count($tempFT_dis))	{$info.='<br />';}
				foreach($tempFT_dis as $ext)	{
					if ($ext)	{
						$info.='-'.strtoupper($ext).' ';
					}
				}

					// Making the array of file items:
				$this->items = t3lib_div::trimExplode(',',$this->itemFormElValue,1);

					// Showing thumbnails:
				$thumbsnail = '';
				if ($show_thumbs)	{
					$imgs = array();
					foreach($this->items as $imgRead) {
						$imgP = explode('|',$imgRead);
						$imgPath = rawurldecode($imgP[0]);

						$rowCopy = array();
						$rowCopy[$field] = $imgPath;

							// Icon + clickmenu:
						$absFilePath = t3lib_div::getFileAbsFileName($config['uploadfolder'] ? $config['uploadfolder'] . '/' . $imgPath : $imgPath);

						$fI = pathinfo($imgPath);
						$fileIcon = t3lib_iconWorks::getSpriteIconForFile(
							strtolower($fI['extension']),
							array(
								'title' => htmlspecialchars(
									$fI['basename'] .
									($absFilePath && @is_file($absFilePath)
										? ' (' . t3lib_div::formatSize(filesize($absFilePath)) . 'bytes)' :
										' - FILE NOT FOUND!'
									)
								)
							)
						);

						$imgs[] = '<span class="nobr">'.t3lib_BEfunc::thumbCode($rowCopy,$table,$field,$this->backPath,'thumbs.php',$config['uploadfolder'],0,' align="middle"').
									($absFilePath ? $this->getClickMenu($fileIcon, $absFilePath) : $fileIcon).
									$imgPath.
									'</span>';
					}
					$thumbsnail = implode('<br />',$imgs);
				}

					// Creating the element:
				$noList = isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'list');
				$params = array(
					'size' => $size,
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="'.htmlspecialchars($config['selectedListStyle']).'"' : ' style="'.$this->defaultMultipleSelectorStyle.'"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readOnly' => $disabled,
					'noBrowser' => $noList || (isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'browser')),
					'noList' => $noList,
				);
				$item.= $this->dbFileIcons($this->formFieldName, 'file', implode(',',$tempFT), $this->items, '', $params ,$this->onFocus);

				if(!$disabled && !(isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'upload'))) {
						// Adding the upload field:
					if ($this->contextObject->isFileUploadEnabled() && $config['uploadfolder']) {
						$item .= '<input type="file" name="' . $this->fileFormFieldName . '"' . $this->formWidth() . ' size="60" onchange="' . implode('', $this->fieldChangeFunc) . '" />';
					}
				}
			break;
			case 'folder':	// If the element is of the internal type "folder":

					// array of folder items:
				$this->items = t3lib_div::trimExplode(',', $this->itemFormElValue, 1);

					// Creating the element:
				$noList = isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'list');
				$params = array(
					'size'              => $size,
					'dontShowMoveIcons' => ($maxitems <= 1),
					'autoSizeMax'       => t3lib_div::intInRange($config['autoSizeMax'], 0),
					'maxitems'          => $maxitems,
					'style'             => isset($config['selectedListStyle']) ?
							' style="'.htmlspecialchars($config['selectedListStyle']).'"'
						:	' style="'.$this->defaultMultipleSelectorStyle.'"',
					'info'              => $info,
					'readOnly'          => $disabled,
					'noBrowser'         => $noList || (isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'browser')),
					'noList'            => $noList,
				);

				$item.= $this->dbFileIcons(
					$this->formFieldName,
					'folder',
					'',
					$this->items,
					'',
					$params,
					$this->onFocus
				);
			break;
			case 'db':	// If the element is of the internal type "db":

					// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',', $allowed, true);
				if (!strcmp(trim($tempFT[0]), '*')) {
					$onlySingleTableAllowed = false;
					$info.='<span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;'.
							htmlspecialchars(t3lib_TCEforms_Helper::getLL('l_allTables')).
							'</span><br />';
				} elseif ($tempFT) {
					$onlySingleTableAllowed = (count($tempFT) == 1);
					foreach ($tempFT as $theT) {
						$info.= '<span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;' .
								t3lib_iconWorks::getSpriteIconForRecord($theT, array()) .
								htmlspecialchars($this->sL($GLOBALS['TCA'][$theT]['ctrl']['title'])) .
									'</span><br />';
					}
				}

				$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
				$imgs = array();

					// Thumbnails:
				$temp_itemArray = t3lib_div::trimExplode(',', $this->itemFormElValue, 1);
				foreach($temp_itemArray as $dbRead)	{
					$recordParts = explode('|', $dbRead);
					list($table,$uid) = t3lib_BEfunc::splitTable_Uid($recordParts[0]);

					$this->items[] = array(
						'table' => $table,
						'id' => $uid
					);

					// For the case that no table was found and only a single table is defined to be allowed, use that one:
					if (!$table && $onlySingleTableAllowed) {
						$this->items[] = $allowed;
					}

					if (!$disabled && $show_thumbs)	{
						$rr = t3lib_BEfunc::getRecordWSOL($table, $uid);
						$imgs[] = '<span class="nobr">'.
								$this->getClickMenu(
									t3lib_iconWorks::getSpriteIconForRecord(
										$table,
										$rr,
										array(
											'style' => 'vertical-align:top',
											'title' => htmlspecialchars(t3lib_BEfunc::getRecordPath($rr['pid'], $perms_clause, 15) . ' [UID: ' . $rr['uid'] . ']')
										)
									),
									$table,
									$uid
								) .
								'&nbsp;' .
								t3lib_BEfunc::getRecordTitle($table, $rr, TRUE) . ' <span class="typo3-dimmed"><em>[' . $rr['uid'] . ']</em></span>' .
								'</span>';
					}
				}
				$thumbsnail='';
				if (!$disabled && $show_thumbs)	{
					$thumbsnail = implode('<br />',$imgs);
				}

					// Creating the element:
				$noList = isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'list');
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems<=1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="'.htmlspecialchars($config['selectedListStyle']).'"' : ' style="'.$this->defaultMultipleSelectorStyle.'"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readOnly' => $disabled,
					'noBrowser' => $noList || (isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'browser')),
					'noList' => $noList,
				);
				$item.= $this->renderItemList('');

			break;
		}

			// Wizards:
		$altItem = '<input type="hidden" name="'.$this->formFieldName.'" value="'.htmlspecialchars($this->itemFormElValue).'" />';
		if (!$disabled) {
			$item = $this->renderWizards(array($item,$altItem),$config['wizards'],$this->formFieldName,$specConf);
		}

		return $item;
	}

	protected function renderOptions() {
		if (!$this->hasItems()) {
			return array();
		}

		$mode = $this->fieldSetup['config']['internal_type'];
		$opt = array();

		// came from dbFileIcons()
		switch($mode) {
			case 'db':
				foreach ($this->items as $pp)	{
					$pRec = t3lib_BEfunc::getRecordWSOL($pp['table'],$pp['id']);
					if (is_array($pRec))	{
						$pTitle = t3lib_BEfunc::getRecordTitle($pp['table'], $pRec, FALSE, TRUE);
						$pUid = $pp['table'].'_'.$pp['id'];
						$this->uidList[] = $pUid;
						$opt[]='<option value="'.htmlspecialchars($pUid).'">'.htmlspecialchars($pTitle).'</option>';
					}
				}
			break;
			case 'file':
			case 'file_reference':
				foreach ($this->items as $item) {
					$itemParts = explode('|', $item);
					$this->uidList[] = $pUid = $pTitle = $itemParts[0];
					$opt[] = '<option value="' . htmlspecialchars(rawurldecode($itemParts[0])) . '">' . htmlspecialchars(basename(rawurldecode($itemParts[0]))) . '</option>';
				}
			break;
			case 'folder':
				while(list(,$pp)=each($this->items))	{
					$pParts = explode('|',$pp);
					$this->uidList[] = $pUid = $pTitle = $pParts[0];
					$opt[]='<option value="'.htmlspecialchars(rawurldecode($pParts[0])).'">'.htmlspecialchars(rawurldecode($pParts[0])).'</option>';
				}
			break;
		}

		return $opt;
	}
}

?>
