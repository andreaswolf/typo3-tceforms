<?php

interface t3lib_TCEforms_Element {
	public function render();

	public function setContainer(t3lib_TCEforms_Container $container);

	public function getFieldname();
}

?>