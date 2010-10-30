<?php

########################################################################
# Extension Manager/Repository config file for ext "fluid".
#
# Auto generated 24-08-2010 12:05
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Fluid Templating Engine',
	'description' => 'Fluid is a next-generation templating engine which makes the life of extension authors a lot easier!',
	'category' => 'fe',
	'author' => 'Sebastian Kurfürst, Bastian Waidelich',
	'author_email' => 'sebastian@typo3.org, bastian@typo3.org',
	'shy' => '',
	'dependencies' => 'extbase',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.3.0alpha3',
	'constraints' => array(
		'depends' => array(
			'extbase' => '1.3.0alpha2',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:181:{s:13:"ChangeLog.txt";s:4:"c24e";s:16:"ext_autoload.php";s:4:"3c6d";s:12:"ext_icon.gif";s:4:"e922";s:19:"last_synched_target";s:4:"bd3a";s:21:"Classes/Exception.php";s:4:"7373";s:17:"Classes/Fluid.php";s:4:"ac13";s:49:"Classes/Compatibility/DocbookGeneratorService.php";s:4:"02ed";s:39:"Classes/Compatibility/ObjectManager.php";s:4:"f87b";s:47:"Classes/Compatibility/TemplateParserBuilder.php";s:4:"7b18";s:26:"Classes/Core/Exception.php";s:4:"79b3";s:37:"Classes/Core/Parser/Configuration.php";s:4:"6f9b";s:33:"Classes/Core/Parser/Exception.php";s:4:"1cbd";s:44:"Classes/Core/Parser/InterceptorInterface.php";s:4:"28a6";s:47:"Classes/Core/Parser/ParsedTemplateInterface.php";s:4:"17a1";s:36:"Classes/Core/Parser/ParsingState.php";s:4:"dbf5";s:38:"Classes/Core/Parser/TemplateParser.php";s:4:"2d45";s:42:"Classes/Core/Parser/Interceptor/Escape.php";s:4:"c653";s:47:"Classes/Core/Parser/SyntaxTree/AbstractNode.php";s:4:"900d";s:44:"Classes/Core/Parser/SyntaxTree/ArrayNode.php";s:4:"f404";s:48:"Classes/Core/Parser/SyntaxTree/NodeInterface.php";s:4:"51bf";s:53:"Classes/Core/Parser/SyntaxTree/ObjectAccessorNode.php";s:4:"a670";s:65:"Classes/Core/Parser/SyntaxTree/RenderingContextAwareInterface.php";s:4:"68ab";s:43:"Classes/Core/Parser/SyntaxTree/RootNode.php";s:4:"3207";s:43:"Classes/Core/Parser/SyntaxTree/TextNode.php";s:4:"eb67";s:49:"Classes/Core/Parser/SyntaxTree/ViewHelperNode.php";s:4:"0539";s:43:"Classes/Core/Rendering/RenderingContext.php";s:4:"5862";s:52:"Classes/Core/Rendering/RenderingContextInterface.php";s:4:"a729";s:55:"Classes/Core/ViewHelper/AbstractConditionViewHelper.php";s:4:"ecaf";s:54:"Classes/Core/ViewHelper/AbstractTagBasedViewHelper.php";s:4:"3b2c";s:46:"Classes/Core/ViewHelper/AbstractViewHelper.php";s:4:"a2cc";s:46:"Classes/Core/ViewHelper/ArgumentDefinition.php";s:4:"c4d5";s:37:"Classes/Core/ViewHelper/Arguments.php";s:4:"b59e";s:37:"Classes/Core/ViewHelper/Exception.php";s:4:"f1d0";s:46:"Classes/Core/ViewHelper/TagBasedViewHelper.php";s:4:"593a";s:38:"Classes/Core/ViewHelper/TagBuilder.php";s:4:"ed1c";s:53:"Classes/Core/ViewHelper/TemplateVariableContainer.php";s:4:"afd1";s:47:"Classes/Core/ViewHelper/ViewHelperInterface.php";s:4:"8274";s:55:"Classes/Core/ViewHelper/ViewHelperVariableContainer.php";s:4:"396d";s:62:"Classes/Core/ViewHelper/Exception/InvalidVariableException.php";s:4:"6dd4";s:76:"Classes/Core/ViewHelper/Exception/RenderingContextNotAccessibleException.php";s:4:"bbb7";s:59:"Classes/Core/ViewHelper/Facets/ChildNodeAccessInterface.php";s:4:"7ec9";s:53:"Classes/Core/ViewHelper/Facets/PostParseInterface.php";s:4:"35d5";s:36:"Classes/Service/DocbookGenerator.php";s:4:"c6a5";s:37:"Classes/View/AbstractTemplateView.php";s:4:"c0d9";s:26:"Classes/View/Exception.php";s:4:"4168";s:29:"Classes/View/TemplateView.php";s:4:"a686";s:38:"Classes/View/TemplateViewInterface.php";s:4:"56b5";s:50:"Classes/View/Exception/InvalidSectionException.php";s:4:"6cb2";s:59:"Classes/View/Exception/InvalidTemplateResourceException.php";s:4:"d589";s:39:"Classes/ViewHelpers/AliasViewHelper.php";s:4:"9450";s:38:"Classes/ViewHelpers/BaseViewHelper.php";s:4:"74c7";s:41:"Classes/ViewHelpers/CObjectViewHelper.php";s:4:"9eb7";s:39:"Classes/ViewHelpers/CountViewHelper.php";s:4:"d116";s:39:"Classes/ViewHelpers/CycleViewHelper.php";s:4:"7c75";s:39:"Classes/ViewHelpers/DebugViewHelper.php";s:4:"8476";s:38:"Classes/ViewHelpers/ElseViewHelper.php";s:4:"4cc2";s:40:"Classes/ViewHelpers/EscapeViewHelper.php";s:4:"9176";s:47:"Classes/ViewHelpers/FlashMessagesViewHelper.php";s:4:"f679";s:37:"Classes/ViewHelpers/ForViewHelper.php";s:4:"fce4";s:38:"Classes/ViewHelpers/FormViewHelper.php";s:4:"faec";s:44:"Classes/ViewHelpers/GroupedForViewHelper.php";s:4:"6d6b";s:36:"Classes/ViewHelpers/IfViewHelper.php";s:4:"4dbb";s:39:"Classes/ViewHelpers/ImageViewHelper.php";s:4:"e517";s:40:"Classes/ViewHelpers/LayoutViewHelper.php";s:4:"ce70";s:53:"Classes/ViewHelpers/RenderFlashMessagesViewHelper.php";s:4:"1e72";s:40:"Classes/ViewHelpers/RenderViewHelper.php";s:4:"fa98";s:41:"Classes/ViewHelpers/SectionViewHelper.php";s:4:"90ae";s:38:"Classes/ViewHelpers/ThenViewHelper.php";s:4:"499d";s:43:"Classes/ViewHelpers/TranslateViewHelper.php";s:4:"253b";s:52:"Classes/ViewHelpers/Be/AbstractBackendViewHelper.php";s:4:"7856";s:46:"Classes/ViewHelpers/Be/ContainerViewHelper.php";s:4:"2dc9";s:45:"Classes/ViewHelpers/Be/PageInfoViewHelper.php";s:4:"f926";s:45:"Classes/ViewHelpers/Be/PagePathViewHelper.php";s:4:"629b";s:46:"Classes/ViewHelpers/Be/TableListViewHelper.php";s:4:"aaaf";s:48:"Classes/ViewHelpers/Be/Buttons/CshViewHelper.php";s:4:"f613";s:49:"Classes/ViewHelpers/Be/Buttons/IconViewHelper.php";s:4:"bc33";s:53:"Classes/ViewHelpers/Be/Buttons/ShortcutViewHelper.php";s:4:"b103";s:57:"Classes/ViewHelpers/Be/Menus/ActionMenuItemViewHelper.php";s:4:"eb4d";s:53:"Classes/ViewHelpers/Be/Menus/ActionMenuViewHelper.php";s:4:"db3a";s:56:"Classes/ViewHelpers/Form/AbstractFormFieldViewHelper.php";s:4:"1f16";s:51:"Classes/ViewHelpers/Form/AbstractFormViewHelper.php";s:4:"52f5";s:47:"Classes/ViewHelpers/Form/CheckboxViewHelper.php";s:4:"20bb";s:45:"Classes/ViewHelpers/Form/ErrorsViewHelper.php";s:4:"0777";s:45:"Classes/ViewHelpers/Form/HiddenViewHelper.php";s:4:"8a9d";s:47:"Classes/ViewHelpers/Form/PasswordViewHelper.php";s:4:"85b5";s:44:"Classes/ViewHelpers/Form/RadioViewHelper.php";s:4:"e698";s:45:"Classes/ViewHelpers/Form/SelectViewHelper.php";s:4:"22d8";s:45:"Classes/ViewHelpers/Form/SubmitViewHelper.php";s:4:"d9e7";s:47:"Classes/ViewHelpers/Form/TextareaViewHelper.php";s:4:"3b97";s:46:"Classes/ViewHelpers/Form/TextboxViewHelper.php";s:4:"125d";s:48:"Classes/ViewHelpers/Form/TextfieldViewHelper.php";s:4:"2512";s:45:"Classes/ViewHelpers/Form/UploadViewHelper.php";s:4:"403e";s:45:"Classes/ViewHelpers/Format/CropViewHelper.php";s:4:"6866";s:49:"Classes/ViewHelpers/Format/CurrencyViewHelper.php";s:4:"a697";s:45:"Classes/ViewHelpers/Format/DateViewHelper.php";s:4:"fd20";s:45:"Classes/ViewHelpers/Format/HtmlViewHelper.php";s:4:"9f77";s:46:"Classes/ViewHelpers/Format/Nl2brViewHelper.php";s:4:"68b8";s:47:"Classes/ViewHelpers/Format/NumberViewHelper.php";s:4:"d47a";s:48:"Classes/ViewHelpers/Format/PaddingViewHelper.php";s:4:"ed6b";s:47:"Classes/ViewHelpers/Format/PrintfViewHelper.php";s:4:"131f";s:45:"Classes/ViewHelpers/Link/ActionViewHelper.php";s:4:"ca29";s:44:"Classes/ViewHelpers/Link/EmailViewHelper.php";s:4:"2d48";s:47:"Classes/ViewHelpers/Link/ExternalViewHelper.php";s:4:"aff1";s:43:"Classes/ViewHelpers/Link/PageViewHelper.php";s:4:"f144";s:44:"Classes/ViewHelpers/Uri/ActionViewHelper.php";s:4:"55f6";s:43:"Classes/ViewHelpers/Uri/EmailViewHelper.php";s:4:"463a";s:46:"Classes/ViewHelpers/Uri/ExternalViewHelper.php";s:4:"dd6c";s:43:"Classes/ViewHelpers/Uri/ImageViewHelper.php";s:4:"6c21";s:42:"Classes/ViewHelpers/Uri/PageViewHelper.php";s:4:"abde";s:46:"Classes/ViewHelpers/Uri/ResourceViewHelper.php";s:4:"f7fd";s:42:"Tests/Unit/Core/TagBasedViewHelperTest.php";s:4:"ff2b";s:34:"Tests/Unit/Core/TagBuilderTest.php";s:4:"df17";s:43:"Tests/Unit/Core/Fixtures/TestViewHelper.php";s:4:"253f";s:43:"Tests/Unit/Core/Parser/ParsingStateTest.php";s:4:"b76f";s:52:"Tests/Unit/Core/Parser/TemplateParserPatternTest.php";s:4:"bfde";s:45:"Tests/Unit/Core/Parser/TemplateParserTest.php";s:4:"6423";s:66:"Tests/Unit/Core/Parser/Fixtures/ChildNodeAccessFacetViewHelper.php";s:4:"132a";s:60:"Tests/Unit/Core/Parser/Fixtures/PostParseFacetViewHelper.php";s:4:"13e9";s:79:"Tests/Unit/Core/Parser/Fixtures/TemplateParserTestFixture01-shorthand-split.php";s:4:"f743";s:74:"Tests/Unit/Core/Parser/Fixtures/TemplateParserTestFixture01-shorthand.html";s:4:"e949";s:69:"Tests/Unit/Core/Parser/Fixtures/TemplateParserTestFixture06-split.php";s:4:"01cb";s:64:"Tests/Unit/Core/Parser/Fixtures/TemplateParserTestFixture06.html";s:4:"92c2";s:69:"Tests/Unit/Core/Parser/Fixtures/TemplateParserTestFixture14-split.php";s:4:"34db";s:64:"Tests/Unit/Core/Parser/Fixtures/TemplateParserTestFixture14.html";s:4:"1ec8";s:49:"Tests/Unit/Core/Parser/Interceptor/EscapeTest.php";s:4:"9b07";s:54:"Tests/Unit/Core/Parser/SyntaxTree/AbstractNodeTest.php";s:4:"ff9c";s:50:"Tests/Unit/Core/Parser/SyntaxTree/TextNodeTest.php";s:4:"95d3";s:66:"Tests/Unit/Core/Parser/SyntaxTree/ViewHelperNodeComparatorTest.php";s:4:"775d";s:56:"Tests/Unit/Core/Parser/SyntaxTree/ViewHelperNodeTest.php";s:4:"20ae";s:50:"Tests/Unit/Core/Rendering/RenderingContextTest.php";s:4:"b4f9";s:53:"Tests/Unit/Core/ViewHelper/AbstractViewHelperTest.php";s:4:"8828";s:53:"Tests/Unit/Core/ViewHelper/ArgumentDefinitionTest.php";s:4:"5ba4";s:54:"Tests/Unit/Core/ViewHelper/ConditionViewHelperTest.php";s:4:"98dd";s:60:"Tests/Unit/Core/ViewHelper/TemplateVariableContainerTest.php";s:4:"28c6";s:62:"Tests/Unit/Core/ViewHelper/ViewHelperVariableContainerTest.php";s:4:"f0db";s:44:"Tests/Unit/View/AbstractTemplateViewTest.php";s:4:"56d4";s:36:"Tests/Unit/View/TemplateViewTest.php";s:4:"4493";s:43:"Tests/Unit/View/Fixtures/LayoutFixture.html";s:4:"cca1";s:48:"Tests/Unit/View/Fixtures/TemplateViewFixture.php";s:4:"31e2";s:56:"Tests/Unit/View/Fixtures/TemplateViewSectionFixture.html";s:4:"aa5f";s:54:"Tests/Unit/View/Fixtures/TransparentSyntaxTreeNode.php";s:4:"925e";s:53:"Tests/Unit/View/Fixtures/UnparsedTemplateFixture.html";s:4:"59dd";s:46:"Tests/Unit/ViewHelpers/AliasViewHelperTest.php";s:4:"20e6";s:45:"Tests/Unit/ViewHelpers/BaseViewHelperTest.php";s:4:"e862";s:46:"Tests/Unit/ViewHelpers/CountViewHelperTest.php";s:4:"ee81";s:46:"Tests/Unit/ViewHelpers/CycleViewHelperTest.php";s:4:"0afb";s:45:"Tests/Unit/ViewHelpers/ElseViewHelperTest.php";s:4:"6bd7";s:44:"Tests/Unit/ViewHelpers/ForViewHelperTest.php";s:4:"30c0";s:45:"Tests/Unit/ViewHelpers/FormViewHelperTest.php";s:4:"a494";s:51:"Tests/Unit/ViewHelpers/GroupedForViewHelperTest.php";s:4:"1f25";s:43:"Tests/Unit/ViewHelpers/IfViewHelperTest.php";s:4:"cca2";s:45:"Tests/Unit/ViewHelpers/ThenViewHelperTest.php";s:4:"1d57";s:49:"Tests/Unit/ViewHelpers/ViewHelperBaseTestcase.php";s:4:"2942";s:60:"Tests/Unit/ViewHelpers/Fixtures/ConstraintSyntaxTreeNode.php";s:4:"97f2";s:46:"Tests/Unit/ViewHelpers/Fixtures/IfFixture.html";s:4:"8458";s:54:"Tests/Unit/ViewHelpers/Fixtures/IfThenElseFixture.html";s:4:"62f0";s:63:"Tests/Unit/ViewHelpers/Form/AbstractFormFieldViewHelperTest.php";s:4:"ee9e";s:58:"Tests/Unit/ViewHelpers/Form/AbstractFormViewHelperTest.php";s:4:"3e6e";s:54:"Tests/Unit/ViewHelpers/Form/CheckboxViewHelperTest.php";s:4:"526d";s:52:"Tests/Unit/ViewHelpers/Form/ErrorsViewHelperTest.php";s:4:"d816";s:52:"Tests/Unit/ViewHelpers/Form/HiddenViewHelperTest.php";s:4:"5e0d";s:51:"Tests/Unit/ViewHelpers/Form/RadioViewHelperTest.php";s:4:"3f15";s:52:"Tests/Unit/ViewHelpers/Form/SelectViewHelperTest.php";s:4:"e78a";s:52:"Tests/Unit/ViewHelpers/Form/SubmitViewHelperTest.php";s:4:"d850";s:54:"Tests/Unit/ViewHelpers/Form/TextareaViewHelperTest.php";s:4:"6ce8";s:53:"Tests/Unit/ViewHelpers/Form/TextboxViewHelperTest.php";s:4:"2f61";s:52:"Tests/Unit/ViewHelpers/Form/UploadViewHelperTest.php";s:4:"a870";s:60:"Tests/Unit/ViewHelpers/Form/Fixtures/EmptySyntaxTreeNode.php";s:4:"6601";s:64:"Tests/Unit/ViewHelpers/Form/Fixtures/Fixture_UserDomainClass.php";s:4:"4da3";s:52:"Tests/Unit/ViewHelpers/Format/CropViewHelperTest.php";s:4:"b290";s:56:"Tests/Unit/ViewHelpers/Format/CurrencyViewHelperTest.php";s:4:"816d";s:52:"Tests/Unit/ViewHelpers/Format/DateViewHelperTest.php";s:4:"ccb3";s:53:"Tests/Unit/ViewHelpers/Format/Nl2brViewHelperTest.php";s:4:"c922";s:54:"Tests/Unit/ViewHelpers/Format/NumberViewHelperTest.php";s:4:"4a91";s:55:"Tests/Unit/ViewHelpers/Format/PaddingViewHelperTest.php";s:4:"c9a2";s:54:"Tests/Unit/ViewHelpers/Format/PrintfViewHelperTest.php";s:4:"cc7a";s:51:"Tests/Unit/ViewHelpers/Link/EmailViewHelperTest.php";s:4:"feea";s:54:"Tests/Unit/ViewHelpers/Link/ExternalViewHelperTest.php";s:4:"aab6";s:61:"Tests/Unit/ViewHelpers/Persistence/IdentityViewHelperTest.php";s:4:"c6d2";s:50:"Tests/Unit/ViewHelpers/Uri/EmailViewHelperTest.php";s:4:"ec42";s:53:"Tests/Unit/ViewHelpers/Uri/ExternalViewHelperTest.php";s:4:"6848";}',
	'suggests' => array(
	),
);

?>