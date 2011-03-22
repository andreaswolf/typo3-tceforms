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
		$dataStructureMock = array('sheets' => array(
			'defaultSheet' => array('ROOT' => array('el' => array(
				'sectionField' => array(
					'section' => 1
				),
				'nonSectionField' => array(
					'TCEforms' => array()
				)
			)))
		));

		$fixture = new t3lib_TCA_DataStructure_FlexFormsResolver();
		$TcaEntry = $fixture->extractInformationFromDataStructureArray($dataStructureMock);
		$fields = $TcaEntry['columns'];

		$this->assertEquals('section', $fields['sectionField']['_type']);
		$this->assertEquals('field', $fields['nonSectionField']['_type']);
	}

	public function sectionFieldsAreRecognized_createDataStructureObject_callback($TCAentry) {
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_FlexFormsResolver::extractSheetInformation
	 */
	public function fieldNamesAreExtractedForSheets() {
		$dataStructureMock = array('sheets' => array(
			'defaultSheet' => array('ROOT' => array('el' => array(
				uniqid('el-') => array(),
				uniqid('el-') => array()
			))),
			'sheet_' . uniqid() => array('ROOT' => array('el' => array(
				uniqid('el-') => array(),
				uniqid('el-') => array()
			))),
		));

		$fixture = new t3lib_TCA_DataStructure_FlexFormsResolver();
		$TcaEntry = $fixture->extractInformationFromDataStructureArray($dataStructureMock);
		$sheets = $TcaEntry['sheets'];

		foreach ($sheets as $sheet) {
			$sheetName = $sheet['name'];
			$mockedElements = array_keys($dataStructureMock['sheets'][$sheetName]['ROOT']['el']);
			$this->assertNotEmpty($mockedElements);

			foreach ($mockedElements as $element) {
				$this->assertContains($element, $sheet['elements']);
			}
		}
	}

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_FlexFormsResolver::extractSheetInformation
	 */
	public function fieldConfigIsCorrectlyExtracted() {
		$dataStructureMock = array('sheets' => array(
			'defaultSheet' => array('ROOT' => array('el' => array(
				uniqid('el-') => array(
					'TCEforms' => array(
						'label' => uniqid(),
						'config' => array('type' => uniqid())
					)
				),
				uniqid('el-') => array(
					'TCEforms' => array(
						'config' => array('type' => uniqid())
					)
				)
			))),
		));

		$fixture = new t3lib_TCA_DataStructure_FlexFormsResolver();
		$TcaEntry = $fixture->extractInformationFromDataStructureArray($dataStructureMock);

		$elements = $dataStructureMock['sheets']['defaultSheet']['ROOT']['el'];
		$elementNames = array_keys($elements);

		foreach ($elementNames as $elementName) {
			$this->assertArrayHasKey($elementName, $TcaEntry['columns']);
			$this->assertEquals(array_merge($elements[$elementName]['TCEforms'], array('_type' => 'field')),
			  $TcaEntry['columns'][$elementName]);
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

	/**
	 * @test
	 * @covers t3lib_TCA_DataStructure_FlexFormsResolver::extractColumnsFromDataStructureArray
	 */
	public function configArrayIsCorrectlySetForSectionFields() {
		$dataStructureMock = array('sheets' => array(
			'defaultSheet' => array('ROOT' => array('el' => array(
				'sectionField' => array(
					'section' => 1
				)
			)))
		));

		/** @var $fixture t3lib_TCA_DataStructure_FlexFormsResolver */
		$fixture = $this->getMock('t3lib_TCA_DataStructure_FlexFormsResolver', array('resolveDataStructureXml', 'createDataStructureObject'));

		$TcaEntry = $fixture->extractInformationFromDataStructureArray($dataStructureMock);
		$column = array_shift($TcaEntry['columns']);

		$this->assertArrayHasKey('config', $column);
		$this->assertEquals('flexsection', $column['config']['type']);
	}
}

?>