<?php

require_once(PATH_t3lib.'tceforms/class.t3lib_tceforms_abstractelement.php');


class t3lib_TCEforms_GroupElement extends t3lib_TCEforms_AbstractElement {
	protected function renderField() {
			// Init:
		$config = $this->fieldConfig['config'];
		$internal_type = $config['internal_type'];
		$show_thumbs = $config['show_thumbs'];
		$size = intval($config['size']);
		$maxitems = t3lib_div::intInRange($config['maxitems'],0);
		if (!$maxitems)	$maxitems=100000;
		$minitems = t3lib_div::intInRange($config['minitems'],0);
		$allowed = $config['allowed'];
		$disallowed = $config['disallowed'];

		$disabled = $this->getDisabledCode();

		$item.= '<input type="hidden" name="'.$this->itemFormElName.'_mul" value="'.($config['multiple']?1:0).'"'.$disabled.' />';
		$this->containingTab->registerRequiredProperty('range', $this->itemFormElName, array($minitems,$maxitems,'imgName'=>$table.'_'.$row['uid'].'_'.$field));
		$info='';

			// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		$specConf = $this->_TCEformsObject->getSpecConfFromString($this->extra, $this->fieldConfig['defaultExtras']);

			// Acting according to either "file" or "db" type:
		switch((string)$config['internal_type'])	{
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
				$itemArray = t3lib_div::trimExplode(',',$this->itemFormElValue,1);

					// Showing thumbnails:
				$thumbsnail = '';
				if ($show_thumbs)	{
					$imgs = array();
					foreach($itemArray as $imgRead)	{
						$imgP = explode('|',$imgRead);
						$imgPath = rawurldecode($imgP[0]);

						$rowCopy = array();
						$rowCopy[$field] = $imgPath;

							// Icon + clickmenu:
						$absFilePath = t3lib_div::getFileAbsFileName($config['uploadfolder'].'/'.$imgPath);

						$fI = pathinfo($imgPath);
						$fileIcon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));
						$fileIcon = '<img'.t3lib_iconWorks::skinImg($this->_TCEformsObject->backPath,'gfx/fileicons/'.$fileIcon,'width="18" height="16"').' class="absmiddle" title="'.htmlspecialchars($fI['basename'].($absFilePath && @is_file($absFilePath) ? ' ('.t3lib_div::formatSize(filesize($absFilePath)).'bytes)' : ' - FILE NOT FOUND!')).'" alt="" />';

						$imgs[] = '<span class="nobr">'.t3lib_BEfunc::thumbCode($rowCopy,$table,$field,$this->_TCEformsObject->backPath,'thumbs.php',$config['uploadfolder'],0,' align="middle"').
									($absFilePath ? $this->TCEformsObject->getClickMenu($fileIcon, $absFilePath) : $fileIcon).
									$imgPath.
									'</span>';
					}
					$thumbsnail = implode('<br />',$imgs);
				}

					// Creating the element:
				$noList = isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'list');
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems<=1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="'.htmlspecialchars($config['selectedListStyle']).'"' : ' style="'.$this->TCEformsObject->defaultMultipleSelectorStyle.'"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readOnly' => $disabled,
					'noBrowser' => $noList || isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'browser'),
					'noList' => $noList,
				);
				$item.= $this->TCEformsObject->dbFileIcons($this->itemFormElName,'file',implode(',',$tempFT),$itemArray,'',$params,$this->onFocus);

				if(!$disabled && !(isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'upload'))) {
						// Adding the upload field:
					if ($this->TCEformsObject->edit_docModuleUpload)	$item.='<input type="file" name="'.$this->itemFormElName_file.'"'.$this->TCEformsObject->formWidth().' size="60" />';
				}
			break;
			case 'folder':	// If the element is of the internal type "folder":

					// array of folder items:
				$itemArray = t3lib_div::trimExplode(',', $this->itemFormElValue, 1);

					// Creating the element:
				$params = array(
					'size'              => $size,
					'dontShowMoveIcons' => ($maxitems <= 1),
					'autoSizeMax'       => t3lib_div::intInRange($config['autoSizeMax'], 0),
					'maxitems'          => $maxitems,
					'style'             => isset($config['selectedListStyle']) ?
							' style="'.htmlspecialchars($config['selectedListStyle']).'"'
						:	' style="'.$this->TCEformsObject->defaultMultipleSelectorStyle.'"',
					'info'              => $info,
					'readOnly'          => $disabled
				);

				$item.= $this->TCEformsObject->dbFileIcons(
					$this->itemFormElName,
					'folder',
					'',
					$itemArray,
					'',
					$params,
					$this->onFocus
				);
			break;
			case 'db':	// If the element is of the internal type "db":

					// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',',$allowed,1);
				if (!strcmp(trim($tempFT[0]),'*'))	{
					$info.='<span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;'.
							htmlspecialchars($this->TCEformsObject->getLL('l_allTables')).
							'</span><br />';
				} else {
					while(list(,$theT)=each($tempFT))	{
						if ($theT)	{
							$info.='<span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;'.
									t3lib_iconWorks::getIconImage($theT,array(),$this->_TCEformsObject->backPath,'align="top"').
									htmlspecialchars($this->TCEformsObject->sL($GLOBALS['TCA'][$theT]['ctrl']['title'])).
									'</span><br />';
						}
					}
				}

				$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
				$itemArray = array();
				$imgs = array();

					// Thumbnails:
				$temp_itemArray = t3lib_div::trimExplode(',',$this->itemFormElValue,1);
				foreach($temp_itemArray as $dbRead)	{
					$recordParts = explode('|',$dbRead);
					list($this->TCEformsObject_table,$this->TCEformsObject_uid) = t3lib_BEfunc::splitTable_Uid($recordParts[0]);
					$itemArray[] = array('table'=>$this->TCEformsObject_table, 'id'=>$this->TCEformsObject_uid);
					if (!$disabled && $show_thumbs)	{
						$rr = t3lib_BEfunc::getRecordWSOL($this->TCEformsObject_table,$this->TCEformsObject_uid);
						$imgs[] = '<span class="nobr">'.
								$this->TCEformsObject->getClickMenu(t3lib_iconWorks::getIconImage($this->TCEformsObject_table,$rr,$this->_TCEformsObject->backPath,'align="top" title="'.htmlspecialchars(t3lib_BEfunc::getRecordPath($rr['pid'],$perms_clause,15)).' [UID: '.$rr['uid'].']"'),$this->TCEformsObject_table, $this->TCEformsObject_uid).
								'&nbsp;'.
								t3lib_BEfunc::getRecordTitle($this->TCEformsObject_table,$rr,TRUE).' <span class="typo3-dimmed"><em>['.$rr['uid'].']</em></span>'.
								'</span>';
					}
				}
				$thumbsnail='';
				if (!$disabled && $show_thumbs)	{
					$thumbsnail = implode('<br />',$imgs);
				}

					// Creating the element:
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems<=1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="'.htmlspecialchars($config['selectedListStyle']).'"' : ' style="'.$this->TCEformsObject->defaultMultipleSelectorStyle.'"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readOnly' => $disabled
				);
				$item.= $this->TCEformsObject->dbFileIcons($this->itemFormElName,'db',implode(',',$tempFT),$itemArray,'',$params,$this->onFocus,$table,$field,$row['uid']);

			break;
		}

			// Wizards:
		$altItem = '<input type="hidden" name="'.$this->itemFormElName.'" value="'.htmlspecialchars($this->itemFormElValue).'" />';
		if (!$disabled) {
			$item = $this->TCEformsObject->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$this->itemFormElName,$specConf);
		}

		return $item;
	}
}

?>