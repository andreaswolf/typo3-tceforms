<?php


class t3lib_TCEforms_Irre {

 	/**
	 * Generates an error message that transferred as JSON for AJAX calls
	 *
	 * @param   string  $message: The error message to be shown
	 * @return  array   The error message in a JSON array
	 */
	public static function getErrorMessageForAJAX($message) {
		$jsonArray = array(
			'data'	=> $message,
			'scriptCall' => array(
				'alert("' . $message . '");'
			)
		);
		return $jsonArray;
	}
}