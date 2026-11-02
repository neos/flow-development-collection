<?php

declare(strict_types=1);

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
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\EmptySyntaxTreeNode;
use Neos\FluidAdaptor\ViewHelpers\Form\UploadViewHelper;
use Neos\FluidAdaptor\ViewHelpers\FormViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/Fixtures/EmptySyntaxTreeNode.php');
require_once(__DIR__ . '/Fixtures/Fixture_UserDomainClass.php');
require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the "Upload" Form view helper
 */
final class UploadViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var UploadViewHelper
     */
    protected $viewHelper;

    /**
     * @var PropertyMapper
     */
    protected $mockPropertyMapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(UploadViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration', 'getMappingResultsForProperty']);
        $this->mockPropertyMapper = $this->createMock(PropertyMapper::class);
        $this->viewHelper->_set('propertyMapper', $this->mockPropertyMapper);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function renderCorrectlySetsTagName(): void
    {
        $this->tagBuilder->expects($this->atLeastOnce())->method('setTagName')->with('input');

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCorrectlySetsTypeNameAndValueAttributes(): void
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['setContent', 'render', 'addAttribute'])->getMock();
        $matcher = self::exactly(2);
        $mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('file', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('someName', $parameters[1]);
            }
        });
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

    #[Test]
    public function renderCallsSetErrorClassAttribute(): void
    {
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    #[Test]
    public function hiddenFieldsAreNotRenderedByDefault(): void
    {
        $expectedResult = '';
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function hiddenFieldsContainDataOfTheSpecifiedResource(): void
    {
        $resource = new PersistentResource();

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($resource)->willReturn('79ecda60-1a27-69ca-17bf-a5d9e80e6c39');

        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->viewHelper->setArguments(['name' => '[foo]', 'value' => $resource]);
        $this->viewHelper->initialize();

        $expectedResult = '<input type="hidden" name="[foo][originallySubmittedResource][__identity]" value="79ecda60-1a27-69ca-17bf-a5d9e80e6c39" />';
        $actualResult = $this->viewHelper->render();

        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
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

        /** @var Result|MockObject $mockValidationResults */
        $mockValidationResults = $this->createMock(Result::class);
        $mockValidationResults->expects($this->atLeastOnce())->method('hasErrors')->willReturn(true);
        $matcher = self::exactly(2);
        $this->request->expects($matcher)->method('getInternalArgument')->willReturnCallback(function (...$parameters) use ($matcher, $mockValidationResults, $submittedData) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('__submittedArgumentValidationResults', $parameters[0]);
                return $mockValidationResults;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('__submittedArguments', $parameters[0]);
                return $submittedData;
            }
        });

        /** @var PersistentResource|MockObject $mockResource */
        $mockResource = $this->createStub(PersistentResource::class);
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockResource)->willReturn($mockResourceUuid);
        $this->inject($this->viewHelper, 'persistenceManager', $mockPersistenceManager);


        $this->mockPropertyMapper->expects($this->atLeastOnce())->method('convert')->with($submittedData['foo']['bar'], PersistentResource::class)->willReturn($mockResource);

        $mockValueResource = $this->createStub(PersistentResource::class);
        $this->viewHelper->setArguments(['name' => 'foo[bar]', 'value' => $mockValueResource]);
        $expectedResult = '<input type="hidden" name="foo[bar][originallySubmittedResource][__identity]" value="' . $mockResourceUuid . '" />';
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function hiddenFieldsContainDataOfValueArgumentIfNoResourceHasBeenUploaded(): void
    {
        $mockValueResourceUuid = '79ecda60-1a27-69ca-17bf-a5d9e80e6c39';

        /** @var Result|MockObject $mockValidationResults */
        $mockValidationResults = $this->createMock(Result::class);
        $mockValidationResults->expects($this->atLeastOnce())->method('hasErrors')->willReturn(false);
        $this->request->expects($this->atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn($mockValidationResults);

        /** @var PersistentResource|MockObject $mockPropertyResource */
        $mockPropertyResource = $this->createStub(PersistentResource::class);
        $mockFormObject = [
            'foo' => $mockPropertyResource
        ];
        $this->viewHelperVariableContainerData[FormViewHelper::class] = [
            'formObjectName' => 'someObject',
            'formObject' => $mockFormObject
        ];
        $mockValueResource = $this->createStub(PersistentResource::class);

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($this->identicalTo($mockValueResource))->willReturn($mockValueResourceUuid);
        $this->inject($this->viewHelper, 'persistenceManager', $mockPersistenceManager);

        $this->viewHelper->setArguments(['property' => 'foo', 'value' => $mockValueResource]);

        $expectedResult = '<input type="hidden" name="someObject[foo][originallySubmittedResource][__identity]" value="' . $mockValueResourceUuid . '" />';
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function hiddenFieldsContainDataOfBoundPropertyIfNoValueArgumentIsSetAndNoResourceHasBeenUploaded(): void
    {
        $mockResourceUuid = '79ecda60-1a27-69ca-17bf-a5d9e80e6c39';

        /** @var Result|MockObject $mockValidationResults */
        $mockValidationResults = $this->createMock(Result::class);
        $mockValidationResults->expects($this->atLeastOnce())->method('hasErrors')->willReturn(false);
        $this->request->expects($this->atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn($mockValidationResults);

        /** @var PersistentResource|MockObject $mockPropertyResource */
        $mockPropertyResource = $this->createStub(PersistentResource::class);
        $mockFormObject = [
            'foo' => $mockPropertyResource
        ];
        $this->viewHelperVariableContainerData[FormViewHelper::class] = [
            'formObjectName' => 'someObject',
            'formObject' => $mockFormObject
        ];

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($this->identicalTo($mockPropertyResource))->willReturn($mockResourceUuid);
        $this->inject($this->viewHelper, 'persistenceManager', $mockPersistenceManager);

        $this->viewHelper->setArguments(['property' => 'foo']);

        $expectedResult = '<input type="hidden" name="someObject[foo][originallySubmittedResource][__identity]" value="' . $mockResourceUuid . '" />';
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }
}
