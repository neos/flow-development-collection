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

use Neos\Error\Messages\Result;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\ResourceManagement\PersistentResource;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\EmptySyntaxTreeNode;
use Neos\FluidAdaptor\ViewHelpers\Form\UploadViewHelper;
use Neos\FluidAdaptor\ViewHelpers\FormViewHelper;

require_once(__DIR__ . '/Fixtures/EmptySyntaxTreeNode.php');
require_once(__DIR__ . '/Fixtures/Fixture_UserDomainClass.php');
require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Upload" Form view helper
 */
class UploadViewHelperTest extends FormFieldViewHelperBaseTestcase
{
    /**
     * @var UploadViewHelper
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(UploadViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration', 'getMappingResultsForProperty']);
        $this->mockPropertyMapper = $this->createMock(PropertyMapper::class);
        $this->viewHelper->_set('propertyMapper', $this->mockPropertyMapper);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagName(): void
    {
        $this->tagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('input');

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTypeNameAndValueAttributes(): void
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['setContent', 'render', 'addAttribute'])->getMock();
        $mockTagBuilder->expects(self::exactly(2))->method('addAttribute')->withConsecutive(
            ['type', 'file'],
            ['name', 'someName']
        );
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('someName');
        $mockTagBuilder->expects(self::once())->method('render');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = [
            'name' => 'someName'
        ];

        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute(): void
    {
        $this->viewHelper->expects(self::once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function hiddenFieldsAreNotRenderedByDefault(): void
    {
        $expectedResult = '';
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function hiddenFieldsContainDataOfTheSpecifiedResource(): void
    {
        $resource = new PersistentResource();

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::atLeastOnce())->method('getIdentifierByObject')->with($resource)->willReturn('79ecda60-1a27-69ca-17bf-a5d9e80e6c39');

        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->viewHelper->setArguments(['name' => '[foo]', 'value' => $resource]);
        $this->viewHelper->initialize();

        $expectedResult = '<input type="hidden" name="[foo][originallySubmittedResource][__identity]" value="79ecda60-1a27-69ca-17bf-a5d9e80e6c39" />';
        $actualResult = $this->viewHelper->render();

        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function hiddenFieldContainsDataOfAPreviouslyUploadedResource(): void
    {
        $mockResourceUuid = '79ecda60-1a27-69ca-17bf-a5d9e80e6c39';
        $submittedData = [
            'foo' => [
                'bar' => [
                    'name' => 'someFilename.jpg',
                    'type' => 'image/jpeg',
                    'tmp_name' => '/some/tmp/name',
                    'error' => 0,
                    'size' => 123,
                ]
            ]
        ];

        /** @var Result|\PHPUnit\Framework\MockObject\MockObject $mockValidationResults */
        $mockValidationResults = $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
        $mockValidationResults->expects(self::atLeastOnce())->method('hasErrors')->willReturn(true);
        $this->request->expects(self::exactly(2))->method('getInternalArgument')->withConsecutive(
            ['__submittedArgumentValidationResults'],
            ['__submittedArguments']
        )->willReturnOnConsecutiveCalls(
            $mockValidationResults,
            $submittedData
        );

        /** @var PersistentResource|\PHPUnit\Framework\MockObject\MockObject $mockResource */
        $mockResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($mockResource)->willReturn($mockResourceUuid);
        $this->inject($this->viewHelper, 'persistenceManager', $mockPersistenceManager);


        $this->mockPropertyMapper->expects(self::atLeastOnce())->method('convert')->with($submittedData['foo']['bar'], PersistentResource::class)->willReturn($mockResource);

        $mockValueResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $this->viewHelper->setArguments(['name' => 'foo[bar]', 'value' => $mockValueResource]);
        $expectedResult = '<input type="hidden" name="foo[bar][originallySubmittedResource][__identity]" value="' . $mockResourceUuid . '" />';
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function hiddenFieldsContainDataOfValueArgumentIfNoResourceHasBeenUploaded(): void
    {
        $mockValueResourceUuid = '79ecda60-1a27-69ca-17bf-a5d9e80e6c39';

        /** @var Result|\PHPUnit\Framework\MockObject\MockObject $mockValidationResults */
        $mockValidationResults = $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
        $mockValidationResults->expects(self::atLeastOnce())->method('hasErrors')->willReturn(false);
        $this->request->expects(self::atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn($mockValidationResults);

        /** @var PersistentResource|\PHPUnit\Framework\MockObject\MockObject $mockPropertyResource */
        $mockPropertyResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $mockFormObject = [
            'foo' => $mockPropertyResource
        ];
        $this->viewHelperVariableContainerData[FormViewHelper::class] = [
            'formObjectName' => 'someObject',
            'formObject' => $mockFormObject
        ];
        $mockValueResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($this->identicalTo($mockValueResource))->willReturn($mockValueResourceUuid);
        $this->inject($this->viewHelper, 'persistenceManager', $mockPersistenceManager);

        $this->viewHelper->setArguments(['property' => 'foo', 'value' => $mockValueResource]);

        $expectedResult = '<input type="hidden" name="someObject[foo][originallySubmittedResource][__identity]" value="' . $mockValueResourceUuid . '" />';
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function hiddenFieldsContainDataOfBoundPropertyIfNoValueArgumentIsSetAndNoResourceHasBeenUploaded(): void
    {
        $mockResourceUuid = '79ecda60-1a27-69ca-17bf-a5d9e80e6c39';

        /** @var Result|\PHPUnit\Framework\MockObject\MockObject $mockValidationResults */
        $mockValidationResults = $this->getMockBuilder(Result::class)->disableOriginalConstructor()->getMock();
        $mockValidationResults->expects(self::atLeastOnce())->method('hasErrors')->willReturn(false);
        $this->request->expects(self::atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn($mockValidationResults);

        /** @var PersistentResource|\PHPUnit\Framework\MockObject\MockObject $mockPropertyResource */
        $mockPropertyResource = $this->getMockBuilder(PersistentResource::class)->disableOriginalConstructor()->getMock();
        $mockFormObject = [
            'foo' => $mockPropertyResource
        ];
        $this->viewHelperVariableContainerData[FormViewHelper::class] = [
            'formObjectName' => 'someObject',
            'formObject' => $mockFormObject
        ];

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($this->identicalTo($mockPropertyResource))->willReturn($mockResourceUuid);
        $this->inject($this->viewHelper, 'persistenceManager', $mockPersistenceManager);

        $this->viewHelper->setArguments(['property' => 'foo']);

        $expectedResult = '<input type="hidden" name="someObject[foo][originallySubmittedResource][__identity]" value="' . $mockResourceUuid . '" />';
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }
}
