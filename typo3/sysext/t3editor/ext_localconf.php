<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook'][] = 'EXT:t3editor/class.tx_t3editor.php:&tx_t3editor->preStartPageHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postTCEProcessingHook'][] = 'EXT:t3editor/class.tx_t3editor.php:&tx_t3editor->postTCEProcessingHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postOutputProcessingHook'][] = 'EXT:t3editor/class.tx_t3editor.php:&tx_t3editor->postOutputProcessingHook';

 ?>