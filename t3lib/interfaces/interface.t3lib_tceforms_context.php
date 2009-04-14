<?php

interface t3lib_TCEforms_Context {

	public function addHtmlForHiddenField($elementName, $code);

	public function getEditFieldHelpMode();

	public function setEditFieldHelpMode($mode);
}

?>