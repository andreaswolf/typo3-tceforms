<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Testcase for the FlexForm resolver class in TCA.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCA_DataStructure_FlexFormsResolverTest extends Tx_Phpunit_TestCase {
	protected $dataStructureMock;

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_FlexFormsResolver::extractColumnsFromDataStructureArray
	 */
	public function fieldTypeIsCorrectlyRecognized() {
		$this->dataStructureMock = array('sheets' => array(
			'defaultSheet' => array('ROOT' => array('el' => array(
				'sectionField' => array(
					'section' => 1
				),
				'nonSectionField' => array(
					'TCEforms' => array()
				)
			)))
		));

		$mockedRecord = $this->getMock('t3lib_TCEforms_Record', array(), array(), '', NULL);
		$mockedField = $this->getMock('t3lib_TCEforms_Element_Flex', array(), array(), '', NULL);
		$mockedField->expects($this->any())->method('getRecordObject')->will($this->returnValue($mockedRecord));
		/** @var $fixture t3lib_TCA_DataStructure_FlexFormsResolver */
		$fixture = $this->getMock('t3lib_TCA_DataStructure_FlexFormsResolver', array('resolveDataStructureXml', 'createDataStructureObject'));
		$fixture->expects($this->once())->method('resolveDataStructureXml')->will($this->returnValue($this->dataStructureMock));
		$fixture->expects($this->once())->method('createDataStructureObject')->will($this->returnCallback(array($this, 'sectionFieldsAreRecognized_createDataStructureObject_callback')));

		$fixture->resolveDataStructure($mockedField);
	}

	public function sectionFieldsAreRecognized_createDataStructureObject_callback($TCAentry) {
		$fields = $TCAentry['columns'];

		$this->assertEquals('section', $fields['sectionField']['_type']);
		$this->assertEquals('field', $fields['nonSectionField']['_type']);
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_FlexFormsResolver::extractSheetInformation
	 */
	public function fieldNamesAreExtractedForSheets() {
		$this->dataStructureMock = array('sheets' => array(
			'defaultSheet' => array('ROOT' => array('el' => array(
				uniqid('el-') => array(),
				uniqid('el-') => array()
			))),
			'sheet_' . uniqid() => array('ROOT' => array('el' => array(
				uniqid('el-') => array(),
				uniqid('el-') => array()
			))),
		));

		$mockedRecord = $this->getMock('t3lib_TCEforms_Record', array(), array(), '', NULL);
		$mockedField = $this->getMock('t3lib_TCEforms_Element_Flex', array(), array(), '', NULL);
		$mockedField->expects($this->any())->method('getRecordObject')->will($this->returnValue($mockedRecord));
		/** @var $fixture t3lib_TCA_DataStructure_FlexFormsResolver */
		$fixture = $this->getMock('t3lib_TCA_DataStructure_FlexFormsResolver', array('resolveDataStructureXml', 'createDataStructureObject'));
		$fixture->expects($this->once())->method('resolveDataStructureXml')->will($this->returnValue($this->dataStructureMock));
		$fixture->expects($this->once())->method('createDataStructureObject')->will($this->returnCallback(array($this, 'fieldNamesAreExtractedForSheets_callback')));

		$fixture->resolveDataStructure($mockedField);
	}

	public function fieldNamesAreExtractedForSheets_callback($TCAentry) {
		$sheets = $TCAentry['sheets'];

		foreach ($sheets as $sheet) {
			$sheetName = $sheet['name'];
			$mockedElements = array_keys($this->dataStructureMock['sheets'][$sheetName]['ROOT']['el']);
			$this->assertNotEmpty($mockedElements);

			foreach ($mockedElements as $element) {
				$this->assertContains($element, $sheet['elements']);
			}
		}
	}

	protected function mockContainerDataStructure() {
		return array(
			'tx_templavoila' => array('title' => uniqid()),
			'el' => array(
				'TCEforms' => array(
					uniqid() => array()
				)
			)
		);
	}

	protected function getMockedDataStructureWithContainer() {
		return array('sheets' => array(
			'defaultSheet' => array('ROOT' => array('el' => array(
				'sectionField' => array(
					'section' => 1,
					'el' => array(
						'containerOne' => $this->mockContainerDataStructure(),
						'containerTwo' => $this->mockContainerDataStructure(),
					)
				)
			)))
		));
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_FlexFormsResolver::extractContainersFromFlexformSection
	 */
	public function containersAreRecognizedWhileResolvingDataStructure() {
		$dataStructure = $this->getMockedDataStructureWithContainer();
		$container1 = $dataStructure['sheets']['defaultSheet']['ROOT']['el']['sectionField']['el']['containerOne'];
		$container2 = $dataStructure['sheets']['defaultSheet']['ROOT']['el']['sectionField']['el']['containerTwo'];

		/** @var $fixture t3lib_TCA_DataStructure_FlexFormsResolver */
		$fixture = $this->getMock('t3lib_TCA_DataStructure_FlexFormsResolver', array('extractFieldInformationFromFlexformContainer'));
		$fixture->expects($this->at(0))->method('extractFieldInformationFromFlexformContainer')->with($this->equalTo($container1));
		$fixture->expects($this->at(1))->method('extractFieldInformationFromFlexformContainer')->with($this->equalTo($container2));

		$TcaEntry = $fixture->extractInformationFromDataStructureArray($dataStructure);
		$sectionField = $TcaEntry['columns']['sectionField'];
		$this->assertArrayHasKey('containerOne', $sectionField['containers']);
		$this->assertArrayHasKey('containerTwo', $sectionField['containers']);
		$this->assertEquals($container1['tx_templavoila']['title'], $sectionField['containers']['containerOne']['title']);
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_FlexFormsResolver::extractFieldInformationFromFlexformContainer
	 */
	public function containerFieldsAreCorrectlyExtractedFromDataStructure() {
		$dataStructure = $this->getMockedDataStructureWithContainer();
		$containers = $dataStructure['sheets']['defaultSheet']['ROOT']['el']['sectionField']['el'];

		$fixture = new t3lib_TCA_DataStructure_FlexFormsResolver();

		$TcaEntry = $fixture->extractInformationFromDataStructureArray($dataStructure);

		$sectionField = $TcaEntry['columns']['sectionField'];
		foreach ($containers as $containerName => $containerDataStructure) {
			$containerTca = $sectionField['containers'][$containerName];

			foreach ($containerDataStructure['el'] as $fieldName => $fieldConfig) {
				$this->assertEquals($fieldConfig['TCEforms'], $containerTca['columns'][$fieldName]);
			}
		}
	}
}

?>