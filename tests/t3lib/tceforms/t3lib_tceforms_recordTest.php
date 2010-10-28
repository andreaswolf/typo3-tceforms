<?php

class t3lib_TCEforms_RecordTest extends tx_phpunit_testcase {
	/**
	 * @test
	 */
	public function elementIdentifierStackGetsExtended() {
		$table = 'pages';
		$uid = 1;
		$fakeRecord = array(
			'uid' => $uid,
			'pid' => 0
		);
		$dataStructure = $this->getMock('t3lib_TCA_DataStructure');
		$contextObject = $this->getMock('t3lib_TCEforms_Form');
		$recordObject = new t3lib_TCEforms_Record($table, $fakeRecord, $dataStructure);
		$recordObject->setContextObject($contextObject)
		             ->setElementIdentifierStack(array('formName'));

		$elementIdentifierStack = $this->readAttribute($recordObject, 'elementIdentifierStack');
		$this->assertEquals(3, count($elementIdentifierStack));
		$this->assertEquals($table, $elementIdentifierStack[1]);
		$this->assertEquals($uid, $elementIdentifierStack[2]);
	}
}