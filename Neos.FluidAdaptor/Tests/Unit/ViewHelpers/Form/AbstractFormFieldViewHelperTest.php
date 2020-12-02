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
use Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper;
use Neos\FluidAdaptor\ViewHelpers\FormViewHelper;
use PHPUnit\Framework\MockObject\MockObject;

require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the Abstract Form view helper
 */
class AbstractFormFieldViewHelperTest extends FormFieldViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function ifAnAttributeValueIsAnObjectMaintainedByThePersistenceManagerItIsConvertedToAUUID(): void
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::any())->method('getIdentifierByObject')->willReturn('6f487e40-4483-11de-8a39-0800200c9a66');

        $className = 'Object' . uniqid();
        $fullClassName = 'Neos\\Fluid\\ViewHelpers\\Form\\' . $className;
        eval('namespace Neos\\Fluid\\ViewHelpers\\Form; class ' . $className . ' {
            public function __clone() {}
        }');
        $object = $this->createMock($fullClassName);

        /** @var AbstractFormFieldViewHelper|MockObject $formViewHelper */
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->injectPersistenceManager($mockPersistenceManager);

        $arguments = ['name' => 'foo', 'value' => $object, 'property' => null];
        $formViewHelper->_set('arguments', $arguments);
        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(false);

        self::assertSame('foo[__identity]', $formViewHelper->_call('getName'));
        self::assertSame('6f487e40-4483-11de-8a39-0800200c9a66', $formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixFormObjectNameAndPropertyIfInObjectAccessorMode(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => 'myObjectName',
                'fieldNamePrefix' => 'formPrefix'
            ]
        ];

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[myObjectName][bla]';
        $actual = $formViewHelper->_call('getName');
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixFormObjectNameAndHierarchicalPropertyIfInObjectAccessorMode(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => 'myObjectName',
                'fieldNamePrefix' => 'formPrefix'
            ]
        ];

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla.blubb'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[myObjectName][bla][blubb]';
        $actual = $formViewHelper->_call('getName');
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixAndPropertyIfInObjectAccessorModeAndNoFormObjectNameIsSpecified(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => null,
                'fieldNamePrefix' => 'formPrefix'
            ]
        ];

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[bla]';
        $actual = $formViewHelper->_call('getName');
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameResolvesPropertyPathIfInObjectAccessorModeAndNoFormObjectNameIsSpecified(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => null,
                'fieldNamePrefix' => 'formPrefix'
            ]
        ];

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'some.property.path'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[some][property][path]';
        $actual = $formViewHelper->_call('getName');
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixAndFieldNameIfNotInObjectAccessorMode(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(false);
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'fieldNamePrefix' => 'formPrefix'
            ]
        ];

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[fieldName]';
        $actual = $formViewHelper->_call('getName');
        self::assertSame($expected, $actual);
    }


    /**
     * This is in order to proof that object access behaves similar to a plain array with the same structure
     */
    public function formObjectVariantsDataProvider(): array
    {
        $className = 'test_' . uniqid();
        $mockObject = eval('
            class ' . $className . ' {
                public function getSomething() {
                    return "MyString";
                }
                public function getValue() {
                    return new ' . $className . ';
                }
            }
            return new ' . $className . ';
        ');
        return [
            [$mockObject],
            ['value' => ['value' => ['something' => 'MyString']]]
        ];
    }

    /**
     * @test
     * @dataProvider formObjectVariantsDataProvider
     */
    public function getValueAttributeBuildsValueFromPropertyAndFormObjectIfInObjectAccessorMode($formObject): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode', 'addAdditionalIdentityPropertiesIfNeeded'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObject' => $formObject,
            ]
        ];

        $arguments = ['name' => null, 'value' => null, 'property' => 'value.something'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'MyString';
        $actual = $formViewHelper->_call('getValueAttribute');
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getValueAttributeReturnsNullIfNotInObjectAccessorModeAndValueArgumentIsNoSet(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(false);

        $mockArguments = [];
        $formViewHelper->_set('arguments', $mockArguments);

        self::assertNull($formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function getValueAttributeReturnsValueArgumentIfSpecified(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $mockArguments = ['value' => 'someValue'];
        $formViewHelper->_set('arguments', $mockArguments);

        self::assertEquals('someValue', $formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function getValueAttributeConvertsObjectsToIdentifiers(): void
    {
        $mockObject = $this->createMock(\stdClass::class);

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::atLeastOnce())->method('getIdentifierByObject')->with($mockObject)->willReturn('6f487e40-4483-11de-8a39-0800200c9a66');

        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->injectPersistenceManager($mockPersistenceManager);

        $mockArguments = ['value' => $mockObject];
        $formViewHelper->_set('arguments', $mockArguments);

        self::assertSame('6f487e40-4483-11de-8a39-0800200c9a66', $formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function getValueAttributeDoesNotConvertsObjectsToIdentifiersIfTheyAreNotKnownToPersistence(): void
    {
        $mockObject = $this->createMock(\stdClass::class);

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects(self::atLeastOnce())->method('getIdentifierByObject')->with($mockObject)->willReturn(null);

        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->injectPersistenceManager($mockPersistenceManager);

        $mockArguments = ['value' => $mockObject];
        $formViewHelper->_set('arguments', $mockArguments);

        self::assertSame($mockObject, $formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function isObjectAccessorModeReturnsTrueIfPropertyIsSetAndFormObjectIsGiven(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => 'SomeFormObjectName'
            ]
        ];

        $formViewHelper->_set('arguments', ['name' => null, 'value' => null, 'property' => 'bla']);
        self::assertTrue($formViewHelper->_call('isObjectAccessorMode'));

        $formViewHelper->_set('arguments', ['name' => null, 'value' => null, 'property' => null]);
        self::assertFalse($formViewHelper->_call('isObjectAccessorMode'));
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsErrorsFromRequestIfPropertyIsSet(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::once())->method('isObjectAccessorMode')->willReturn(true);
        $formViewHelper->_set('arguments', ['property' => 'bar']);

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => 'foo'
            ]
        ];

        $expectedResult = $this->createMock(Result::class);

        $mockFormResult = $this->createMock(Result::class);
        $mockFormResult->expects(self::once())->method('forProperty')->with('foo.bar')->willReturn($expectedResult);

        $this->request->expects(self::once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn($mockFormResult);

        $actualResult = $formViewHelper->_call('getMappingResultsForProperty');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsErrorsFromRequestIfFormObjectNameIsNotSet(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::once())->method('isObjectAccessorMode')->willReturn(true);

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => null
            ]
        ];

        $expectedResult = $this->createMock(Result::class);

        $mockFormResult = $this->createMock(Result::class);
        $mockFormResult->expects(self::once())->method('forProperty')->with('bar')->willReturn($expectedResult);

        $this->request->expects(self::once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn($mockFormResult);

        $formViewHelper = $this->prepareArguments($formViewHelper, ['property' => 'bar']);
        $actualResult = $formViewHelper->_call('getMappingResultsForProperty');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsEmptyResultIfNoErrorOccurredInObjectAccessorMode(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);

        $actualResult = $formViewHelper->_call('getMappingResultsForProperty');
        self::assertEmpty($actualResult->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsEmptyResultIfNoErrorOccurredInNonObjectAccessorMode(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(false);

        $actualResult = $formViewHelper->_call('getMappingResultsForProperty');
        self::assertEmpty($actualResult->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsValidationResultsIfErrorsHappenedInObjectAccessorMode(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);
        $formViewHelper->_set('arguments', ['property' => 'propertyName']);

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => 'someObject'
            ]
        ];

        $validationResults = $this->createMock(Result::class);
        $validationResults->expects(self::once())->method('forProperty')->with('someObject.propertyName')->willReturn($validationResults);
        $this->request->expects(self::once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn($validationResults);
        $formViewHelper->_call('getMappingResultsForProperty');
    }

    /**
     * @test
     */
    public function getMappingResultsForSubPropertyReturnsValidationResultsIfErrorsHappenedInObjectAccessorMode(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);
        $formViewHelper->_set('arguments', ['property' => 'propertyName.subPropertyName']);

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => 'someObject'
            ]
        ];

        $validationResults = $this->createMock(Result::class);
        $validationResults->expects(self::once())->method('forProperty')->with('someObject.propertyName.subPropertyName')->willReturn($validationResults);
        $this->request->expects(self::once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn($validationResults);
        $formViewHelper->_call('getMappingResultsForProperty');
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsValidationResultsIfErrorsHappenedInNonObjectAccessorMode(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(false);
        $formViewHelper->_set('arguments', ['name' => 'propertyName']);

        $validationResults = $this->createMock(Result::class);
        $validationResults->expects(self::once())->method('forProperty')->with('propertyName');
        $this->request->expects(self::once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn($validationResults);
        $formViewHelper->_call('getMappingResultsForProperty');
    }

    /**
     * @test
     */
    public function getMappingResultsForSubPropertyReturnsValidationResultsIfErrorsHappenedInNonObjectAccessorMode(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(false);
        $formViewHelper->_set('arguments', ['name' => 'propertyName[subPropertyName]']);

        $validationResults = $this->createMock(Result::class);
        $validationResults->expects(self::once())->method('forProperty')->with('propertyName.subPropertyName');
        $this->request->expects(self::once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->willReturn($validationResults);
        $formViewHelper->_call('getMappingResultsForProperty');
    }


    /**
     * @test
     */
    public function setErrorClassAttributeDoesNotSetClassAttributeIfNoErrorOccurred(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['hasArgument', 'getErrorsForProperty'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->tagBuilder->expects(self::never())->method('addAttribute');

        $formViewHelper->_call('setErrorClassAttribute');
    }

    /**
     * @test
     */
    public function setErrorClassAttributeSetsErrorClassIfAnErrorOccurred(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['hasArgument', 'getMappingResultsForProperty'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::exactly(2))->method('hasArgument')->withConsecutive(['class'], ['errorClass'])->willReturn(false);

        $mockResult = $this->createMock(Result::class);
        $mockResult->expects(self::atLeastOnce())->method('hasErrors')->willReturn(true);
        $formViewHelper->expects(self::once())->method('getMappingResultsForProperty')->willReturn($mockResult);

        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('class', 'error');

        $formViewHelper->_call('setErrorClassAttribute');
    }

    /**
     * @test
     */
    public function setErrorClassAttributeAppendsErrorClassToExistingClassesIfAnErrorOccurred(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['hasArgument', 'getMappingResultsForProperty'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::exactly(2))->method('hasArgument')->withConsecutive(['class'], ['errorClass'])->willReturnOnConsecutiveCalls(true, false);
        $formViewHelper->_set('arguments', ['class' => 'default classes']);

        $mockResult = $this->createMock(Result::class);
        $mockResult->expects(self::atLeastOnce())->method('hasErrors')->willReturn(true);
        $formViewHelper->expects(self::once())->method('getMappingResultsForProperty')->willReturn($mockResult);

        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('class', 'default classes error');

        $formViewHelper->_call('setErrorClassAttribute');
    }

    /**
     * @test
     */
    public function setErrorClassAttributeSetsCustomErrorClassIfAnErrorOccurred(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['hasArgument', 'getMappingResultsForProperty'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::exactly(2))->method('hasArgument')->withConsecutive(['class'], ['errorClass'])->willReturnOnConsecutiveCalls(false, true);
        $formViewHelper->_set('arguments', ['errorClass' => 'custom-error-class']);

        $mockResult = $this->createMock(Result::class);
        $mockResult->expects(self::atLeastOnce())->method('hasErrors')->willReturn(true);
        $formViewHelper->expects(self::once())->method('getMappingResultsForProperty')->willReturn($mockResult);

        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('class', 'custom-error-class');

        $formViewHelper->_call('setErrorClassAttribute');
    }

    /**
     * @test
     */
    public function setErrorClassAttributeAppendsCustomErrorClassIfAnErrorOccurred(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['hasArgument', 'getMappingResultsForProperty'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::exactly(2))->method('hasArgument')->withConsecutive(['class'], ['errorClass'])->willReturn(true);
        $formViewHelper->_set('arguments', ['class' => 'default classes', 'errorClass' => 'custom-error-class']);

        $mockResult = $this->createMock(Result::class);
        $mockResult->expects(self::atLeastOnce())->method('hasErrors')->willReturn(true);
        $formViewHelper->expects(self::once())->method('getMappingResultsForProperty')->willReturn($mockResult);

        $this->tagBuilder->expects(self::once())->method('addAttribute')->with('class', 'default classes custom-error-class');

        $formViewHelper->_call('setErrorClassAttribute');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededDoesNotTryToAccessObjectPropertiesIfFormObjectIsNotSet(): void
    {
        $formFieldViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['renderHiddenIdentityField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => 'some.property.name'];

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => 'someFormObjectName',
            ]
        ];

        $formFieldViewHelper->expects(self::never())->method('renderHiddenIdentityField');
        $formFieldViewHelper->_set('arguments', $arguments);
        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededDoesNotCreateAnythingIfPropertyIsWithoutDot(): void
    {
        $formFieldViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['renderHiddenIdentityField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => 'simple'];

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => 'someFormObjectName',
                'formObject' => new \stdClass(),
            ]
        ];

        $formFieldViewHelper->expects(self::never())->method('renderHiddenIdentityField');
        $formFieldViewHelper->_set('arguments', $arguments);
        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededCallsRenderIdentityFieldWithTheRightParameters(): void
    {
        $className = 'test_' . uniqid();
        $mockFormObject = eval('
            class ' . $className . ' {
                public function getSomething() {
                    return "MyString";
                }
                public function getValue() {
                    return new ' . $className . ';
                }
            }
            return new ' . $className . ';
        ');
        $property = 'value.something';
        $objectName = 'myObject';
        $expectedProperty = 'myObject[value]';

        $formFieldViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['renderHiddenIdentityField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => $property];
        $formFieldViewHelper->_set('arguments', $arguments);

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => $objectName,
                'formObject' => $mockFormObject,
                'additionalIdentityProperties' => []
            ]
        ];

        $formFieldViewHelper->expects(self::once())->method('renderHiddenIdentityField')->with($mockFormObject, $expectedProperty);

        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededCallsRenderIdentityFieldWithTheRightParametersWithMoreHierarchyLevels(): void
    {
        $className = 'test_' . uniqid();
        $mockFormObject = eval('
            class ' . $className . ' {
                public function getSomething() {
                    return "MyString";
                }
                public function getValue() {
                    return new ' . $className . ';
                }
            }
            return new ' . $className . ';
        ');
        $property = 'value.value.something';
        $objectName = 'myObject';
        $expectedProperty1 = 'myObject[value]';
        $expectedProperty2 = 'myObject[value][value]';

        $formFieldViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['renderHiddenIdentityField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => $property];
        $formFieldViewHelper->_set('arguments', $arguments);

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => $objectName,
                'formObject' => $mockFormObject,
                'additionalIdentityProperties' => []
            ]
        ];

        $formFieldViewHelper->expects(self::exactly(2))->method('renderHiddenIdentityField')->withConsecutive([$mockFormObject, $expectedProperty1], [$mockFormObject, $expectedProperty2]);

        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueAddsHiddenFieldNameToVariableContainer(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['getName'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('getName')->willReturn('NewFieldName');

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formObjectName' => 'someFormObjectName',
                'formObject' => new \stdClass(),
                'emptyHiddenFieldNames' => ['OldFieldName' => false]
            ]
        ];
        $this->viewHelperVariableContainer->expects(self::atLeastOnce())->method('addOrUpdate')->with(FormViewHelper::class, 'emptyHiddenFieldNames', ['OldFieldName' => false, 'NewFieldName' => false]);

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueDoesNotAddTheSameHiddenFieldNameMoreThanOnce(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['getName'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('getName')->willReturn('SomeFieldName');
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'emptyHiddenFieldNames' => ['SomeFieldName' => false]
            ]
        ];
        $this->viewHelperVariableContainer->expects(self::never())->method('addOrUpdate');

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function renderHiddenFieldForEmptyValueRemovesEmptySquareBracketsFromHiddenFieldName(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['getName'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('getName')->willReturn('SomeFieldName[WithBrackets][]');
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'emptyHiddenFieldNames' => ['SomeFieldName[WithBrackets]' => false]
            ]
        ];

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function renderHiddenFieldForEmptyValueDoesNotRemoveNonEmptySquareBracketsFromHiddenFieldName(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['getName'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('getName')->willReturn('SomeFieldName[WithBrackets][foo]');
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'emptyHiddenFieldNames' => ['SomeFieldName[WithBrackets][foo]' => false]
            ]
        ];

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueAddsHiddenFieldWithDisabledState(): void
    {
        $formViewHelper = $this->getAccessibleMock(AbstractFormFieldViewHelper::class, ['getName'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->tagBuilder->expects(self::any())->method('hasAttribute')->with('disabled')->willReturn(true);
        $this->tagBuilder->expects(self::any())->method('getAttribute')->with('disabled')->willReturn('disabledValue');

        $formViewHelper->expects(self::any())->method('getName')->willReturn('SomeFieldName');

        $this->viewHelperVariableContainer->expects(self::atLeastOnce())->method('addOrUpdate')->with(FormViewHelper::class, 'emptyHiddenFieldNames', ['SomeFieldName' => 'disabledValue']);
        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }
}
