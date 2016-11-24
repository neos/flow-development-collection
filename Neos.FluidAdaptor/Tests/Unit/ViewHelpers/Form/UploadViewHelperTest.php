<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Error\Result;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\ResourceManagement\PersistentResource;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\Fluid\ViewHelpers\Fixtures\EmptySyntaxTreeNode;
use TYPO3\Fluid\ViewHelpers\Form\UploadViewHelper;
use TYPO3\Fluid\ViewHelpers\FormViewHelper;

require_once(__DIR__ . '/Fixtures/EmptySyntaxTreeNode.php');
require_once(__DIR__ . '/Fixtures/Fixture_UserDomainClass.php');
require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Upload" Form view helper
 */
class UploadViewHelperTest extends FormFieldViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\UploadViewHelper
     */
    protected $viewHelper;

    /**
     * @var PropertyMapper
     */
    protected $mockPropertyMapper;

    /**
     * @var Result
     */
    protected $mockMappingResult;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\UploadViewHelper::class, array('setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration', 'getMappingResultsForProperty'));
        $this->mockPropertyMapper = $this->createMock(\TYPO3\Flow\Property\PropertyMapper::class);
        $this->viewHelper->_set('propertyMapper', $this->mockPropertyMapper);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagName()
    {
        $this->tagBuilder->expects($this->any())->method('setTagName')->with('input');

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTypeNameAndValueAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(array('setContent', 'render', 'addAttribute'))->getMock();
        $mockTagBuilder->expects($this->at(0))->method('addAttribute')->with('type', 'file');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('name', 'someName');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('someName');
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = array(
            'name' => 'someName'
        );

        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->setViewHelperNode(new \Neos\FluidAdaptor\ViewHelpers\Fixtures\EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function hiddenFieldsAreNotRenderedByDefault()
    {
        $expectedResult = '';
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render();
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function hiddenFieldsContainDataOfTheSpecifiedResource()
    {
        $resource = new PersistentResource();

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($resource)->will($this->returnValue('79ecda60-1a27-69ca-17bf-a5d9e80e6c39'));

        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->viewHelper->setArguments(array('name' => '[foo]', 'value' => $resource));
        $this->viewHelper->initialize();

        $expectedResult = '<input type="hidden" name="[foo][originallySubmittedResource][__identity]" value="79ecda60-1a27-69ca-17bf-a5d9e80e6c39" />';
        $actualResult = $this->viewHelper->render();

        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function hiddenFieldContainsDataOfAPreviouslyUploadedResource()
    {
        $mockResourceUuid = '79ecda60-1a27-69ca-17bf-a5d9e80e6c39';
        $submittedData = array(
            'foo' => array(
                'bar' => array(
                    'name' => 'someFilename.jpg',
                    'type' => 'image/jpeg',
                    'tmp_name' => '/some/tmp/name',
                    'error' => 0,
                    'size' => 123,
                )
            )
        );

        /** @var Result|\PHPUnit_Framework_MockObject_MockObject $mockValidationResults */
        $mockValidationResults = $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
        $mockValidationResults->expects($this->atLeastOnce())->method('hasErrors')->will($this->returnValue(true));
        $this->request->expects($this->at(0))->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($mockValidationResults));
        $this->request->expects($this->at(1))->method('getInternalArgument')->with('__submittedArguments')->will($this->returnValue($submittedData));

        /** @var PersistentResource|\PHPUnit_Framework_MockObject_MockObject $mockResource */
        $mockResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockResource)->will($this->returnValue($mockResourceUuid));
        $this->inject($this->viewHelper, 'persistenceManager', $mockPersistenceManager);


        $this->mockPropertyMapper->expects($this->atLeastOnce())->method('convert')->with($submittedData['foo']['bar'], PersistentResource::class)->will($this->returnValue($mockResource));

        $mockValueResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $this->viewHelper->setArguments(array('name' => 'foo[bar]', 'value' => $mockValueResource));
        $expectedResult = '<input type="hidden" name="foo[bar][originallySubmittedResource][__identity]" value="' . $mockResourceUuid . '" />';
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render();
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function hiddenFieldsContainDataOfValueArgumentIfNoResourceHasBeenUploaded()
    {
        $mockValueResourceUuid = '79ecda60-1a27-69ca-17bf-a5d9e80e6c39';

        /** @var Result|\PHPUnit_Framework_MockObject_MockObject $mockValidationResults */
        $mockValidationResults = $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
        $mockValidationResults->expects($this->atLeastOnce())->method('hasErrors')->will($this->returnValue(false));
        $this->request->expects($this->atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($mockValidationResults));

        /** @var PersistentResource|\PHPUnit_Framework_MockObject_MockObject $mockPropertyResource */
        $mockPropertyResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $mockFormObject = array(
            'foo' => $mockPropertyResource
        );
        $this->viewHelperVariableContainerData[\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class] = array(
            'formObjectName' => 'someObject',
            'formObject' => $mockFormObject
        );
        $mockValueResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($this->identicalTo($mockValueResource))->will($this->returnValue($mockValueResourceUuid));
        $this->inject($this->viewHelper, 'persistenceManager', $mockPersistenceManager);

        $this->viewHelper->setArguments(array('property' => 'foo', 'value' => $mockValueResource));

        $expectedResult = '<input type="hidden" name="someObject[foo][originallySubmittedResource][__identity]" value="' . $mockValueResourceUuid . '" />';
        $actualResult = $this->viewHelper->render();
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function hiddenFieldsContainDataOfBoundPropertyIfNoValueArgumentIsSetAndNoResourceHasBeenUploaded()
    {
        $mockResourceUuid = '79ecda60-1a27-69ca-17bf-a5d9e80e6c39';

        /** @var Result|\PHPUnit_Framework_MockObject_MockObject $mockValidationResults */
        $mockValidationResults = $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
        $mockValidationResults->expects($this->atLeastOnce())->method('hasErrors')->will($this->returnValue(false));
        $this->request->expects($this->atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($mockValidationResults));

        /** @var PersistentResource|\PHPUnit_Framework_MockObject_MockObject $mockPropertyResource */
        $mockPropertyResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $mockFormObject = array(
            'foo' => $mockPropertyResource
        );
        $this->viewHelperVariableContainerData[\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class] = array(
            'formObjectName' => 'someObject',
            'formObject' => $mockFormObject
        );

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($this->identicalTo($mockPropertyResource))->will($this->returnValue($mockResourceUuid));
        $this->inject($this->viewHelper, 'persistenceManager', $mockPersistenceManager);

        $this->viewHelper->setArguments(array('property' => 'foo'));

        $expectedResult = '<input type="hidden" name="someObject[foo][originallySubmittedResource][__identity]" value="' . $mockResourceUuid . '" />';
        $actualResult = $this->viewHelper->render();
        $this->assertSame($expectedResult, $actualResult);
    }
}
