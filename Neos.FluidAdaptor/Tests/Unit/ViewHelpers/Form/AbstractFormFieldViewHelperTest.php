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

require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the Abstract Form view helper
 */
class AbstractFormFieldViewHelperTest extends FormFieldViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function ifAnAttributeValueIsAnObjectMaintainedByThePersistenceManagerItIsConvertedToAUUID()
    {
        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('6f487e40-4483-11de-8a39-0800200c9a66'));

        $className = 'Object' . uniqid();
        $fullClassName = 'Neos\\Fluid\\ViewHelpers\\Form\\' . $className;
        eval('namespace Neos\\Fluid\\ViewHelpers\\Form; class ' . $className . ' {
			public function __clone() {}
		}');
        $object = $this->createMock($fullClassName);

        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->injectPersistenceManager($mockPersistenceManager);

        $arguments = ['name' => 'foo', 'value' => $object, 'property' => null];
        $formViewHelper->_set('arguments', $arguments);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));

        $this->assertSame('foo[__identity]', $formViewHelper->_call('getName'));
        $this->assertSame('6f487e40-4483-11de-8a39-0800200c9a66', $formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixFormObjectNameAndPropertyIfInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => 'myObjectName',
                'fieldNamePrefix' => 'formPrefix'
            ]
        ];

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[myObjectName][bla]';
        $actual = $formViewHelper->_call('getName');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixFormObjectNameAndHierarchicalPropertyIfInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => 'myObjectName',
                'fieldNamePrefix' => 'formPrefix'
            ]
        ];

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla.blubb'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[myObjectName][bla][blubb]';
        $actual = $formViewHelper->_call('getName');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixAndPropertyIfInObjectAccessorModeAndNoFormObjectNameIsSpecified()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => null,
                'fieldNamePrefix' => 'formPrefix'
            ]
        ];

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[bla]';
        $actual = $formViewHelper->_call('getName');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameResolvesPropertyPathIfInObjectAccessorModeAndNoFormObjectNameIsSpecified()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => null,
                'fieldNamePrefix' => 'formPrefix'
            ]
        ];

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'some.property.path'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[some][property][path]';
        $actual = $formViewHelper->_call('getName');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixAndFieldNameIfNotInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'fieldNamePrefix' => 'formPrefix'
            ]
        ];

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[fieldName]';
        $actual = $formViewHelper->_call('getName');
        $this->assertSame($expected, $actual);
    }


    /**
     * This is in order to proof that object access behaves similar to a plain array with the same structure
     */
    public function formObjectVariantsDataProvider()
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
    public function getValueAttributeBuildsValueFromPropertyAndFormObjectIfInObjectAccessorMode($formObject)
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode', 'addAdditionalIdentityPropertiesIfNeeded'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObject' => $formObject,
            ]
        ];

        $arguments = ['name' => null, 'value' => null, 'property' => 'value.something'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'MyString';
        $actual = $formViewHelper->_call('getValueAttribute');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getValueAttributeReturnsNullIfNotInObjectAccessorModeAndValueArgumentIsNoSet()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));

        $mockArguments = [];
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertNull($formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function getValueAttributeReturnsValueArgumentIfSpecified()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $mockArguments = ['value' => 'someValue'];
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertEquals('someValue', $formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function getValueAttributeConvertsObjectsToIdentifiers()
    {
        $mockObject = $this->createMock(\stdClass::class);

        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue('6f487e40-4483-11de-8a39-0800200c9a66'));

        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->injectPersistenceManager($mockPersistenceManager);

        $mockArguments = ['value' => $mockObject];
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertSame('6f487e40-4483-11de-8a39-0800200c9a66', $formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function getValueAttributeDoesNotConvertsObjectsToIdentifiersIfTheyAreNotKnownToPersistence()
    {
        $mockObject = $this->createMock(\stdClass::class);

        $mockPersistenceManager = $this->createMock(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue(null));

        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->injectPersistenceManager($mockPersistenceManager);

        $mockArguments = ['value' => $mockObject];
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertSame($mockObject, $formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function isObjectAccessorModeReturnsTrueIfPropertyIsSetAndFormObjectIsGiven()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => 'SomeFormObjectName'
            ]
        ];

        $formViewHelper->_set('arguments', ['name' => null, 'value' => null, 'property' => 'bla']);
        $this->assertTrue($formViewHelper->_call('isObjectAccessorMode'));

        $formViewHelper->_set('arguments', ['name' => null, 'value' => null, 'property' => null]);
        $this->assertFalse($formViewHelper->_call('isObjectAccessorMode'));
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsErrorsFromRequestIfPropertyIsSet()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->once())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', ['property' => 'bar']);

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => 'foo'
            ]
        ];

        $expectedResult = $this->createMock(\Neos\Error\Messages\Result::class);

        $mockFormResult = $this->createMock(\Neos\Error\Messages\Result::class);
        $mockFormResult->expects($this->once())->method('forProperty')->with('foo.bar')->will($this->returnValue($expectedResult));

        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($mockFormResult));

        $actualResult = $formViewHelper->_call('getMappingResultsForProperty');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsErrorsFromRequestIfFormObjectNameIsNotSet()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->once())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', ['property' => 'bar']);

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => null,
            ]
        ];

        $expectedResult = $this->createMock(\Neos\Error\Messages\Result::class);

        $mockFormResult = $this->createMock(\Neos\Error\Messages\Result::class);
        $mockFormResult->expects($this->once())->method('forProperty')->with('bar')->will($this->returnValue($expectedResult));

        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($mockFormResult));

        $actualResult = $formViewHelper->_call('getMappingResultsForProperty');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsEmptyResultIfNoErrorOccurredInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));

        $actualResult = $formViewHelper->_call('getMappingResultsForProperty');
        $this->assertEmpty($actualResult->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsEmptyResultIfNoErrorOccurredInNonObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));

        $actualResult = $formViewHelper->_call('getMappingResultsForProperty');
        $this->assertEmpty($actualResult->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsValidationResultsIfErrorsHappenedInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', ['property' => 'propertyName']);

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => 'someObject'
            ]
        ];

        $validationResults = $this->createMock(\Neos\Error\Messages\Result::class);
        $validationResults->expects($this->once())->method('forProperty')->with('someObject.propertyName')->will($this->returnValue($validationResults));
        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($validationResults));
        $formViewHelper->_call('getMappingResultsForProperty');
    }

    /**
     * @test
     */
    public function getMappingResultsForSubPropertyReturnsValidationResultsIfErrorsHappenedInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', ['property' => 'propertyName.subPropertyName']);

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => 'someObject'
            ]
        ];

        $validationResults = $this->createMock(\Neos\Error\Messages\Result::class);
        $validationResults->expects($this->once())->method('forProperty')->with('someObject.propertyName.subPropertyName')->will($this->returnValue($validationResults));
        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($validationResults));
        $formViewHelper->_call('getMappingResultsForProperty');
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsValidationResultsIfErrorsHappenedInNonObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $formViewHelper->_set('arguments', ['name' => 'propertyName']);

        $validationResults = $this->createMock(\Neos\Error\Messages\Result::class);
        $validationResults->expects($this->once())->method('forProperty')->with('propertyName');
        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($validationResults));
        $formViewHelper->_call('getMappingResultsForProperty');
    }

    /**
     * @test
     */
    public function getMappingResultsForSubPropertyReturnsValidationResultsIfErrorsHappenedInNonObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $formViewHelper->_set('arguments', ['name' => 'propertyName[subPropertyName]']);

        $validationResults = $this->createMock(\Neos\Error\Messages\Result::class);
        $validationResults->expects($this->once())->method('forProperty')->with('propertyName.subPropertyName');
        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($validationResults));
        $formViewHelper->_call('getMappingResultsForProperty');
    }


    /**
     * @test
     */
    public function setErrorClassAttributeDoesNotSetClassAttributeIfNoErrorOccurred()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['hasArgument', 'getErrorsForProperty'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->tagBuilder->expects($this->never())->method('addAttribute');

        $formViewHelper->_call('setErrorClassAttribute');
    }

    /**
     * @test
     */
    public function setErrorClassAttributeSetsErrorClassIfAnErrorOccurred()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['hasArgument', 'getMappingResultsForProperty'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(false));
        $formViewHelper->expects($this->at(2))->method('hasArgument')->with('errorClass')->will($this->returnValue(false));

        $mockResult = $this->createMock(\Neos\Error\Messages\Result::class);
        $mockResult->expects($this->atLeastOnce())->method('hasErrors')->will($this->returnValue(true));
        $formViewHelper->expects($this->once())->method('getMappingResultsForProperty')->will($this->returnValue($mockResult));

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('class', 'error');

        $formViewHelper->_call('setErrorClassAttribute');
    }

    /**
     * @test
     */
    public function setErrorClassAttributeAppendsErrorClassToExistingClassesIfAnErrorOccurred()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['hasArgument', 'getMappingResultsForProperty'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(true));
        $formViewHelper->expects($this->at(2))->method('hasArgument')->with('errorClass')->will($this->returnValue(false));
        $formViewHelper->_set('arguments', ['class' => 'default classes']);

        $mockResult = $this->createMock(\Neos\Error\Messages\Result::class);
        $mockResult->expects($this->atLeastOnce())->method('hasErrors')->will($this->returnValue(true));
        $formViewHelper->expects($this->once())->method('getMappingResultsForProperty')->will($this->returnValue($mockResult));

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('class', 'default classes error');

        $formViewHelper->_call('setErrorClassAttribute');
    }

    /**
     * @test
     */
    public function setErrorClassAttributeSetsCustomErrorClassIfAnErrorOccurred()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['hasArgument', 'getMappingResultsForProperty'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(false));
        $formViewHelper->expects($this->at(2))->method('hasArgument')->with('errorClass')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', ['errorClass' => 'custom-error-class']);

        $mockResult = $this->createMock(\Neos\Error\Messages\Result::class);
        $mockResult->expects($this->atLeastOnce())->method('hasErrors')->will($this->returnValue(true));
        $formViewHelper->expects($this->once())->method('getMappingResultsForProperty')->will($this->returnValue($mockResult));

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('class', 'custom-error-class');

        $formViewHelper->_call('setErrorClassAttribute');
    }

    /**
     * @test
     */
    public function setErrorClassAttributeAppendsCustomErrorClassIfAnErrorOccurred()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['hasArgument', 'getMappingResultsForProperty'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(true));
        $formViewHelper->expects($this->at(2))->method('hasArgument')->with('errorClass')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', ['class' => 'default classes', 'errorClass' => 'custom-error-class']);

        $mockResult = $this->createMock(\Neos\Error\Messages\Result::class);
        $mockResult->expects($this->atLeastOnce())->method('hasErrors')->will($this->returnValue(true));
        $formViewHelper->expects($this->once())->method('getMappingResultsForProperty')->will($this->returnValue($mockResult));

        $this->tagBuilder->expects($this->once())->method('addAttribute')->with('class', 'default classes custom-error-class');

        $formViewHelper->_call('setErrorClassAttribute');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededDoesNotTryToAccessObjectPropertiesIfFormObjectIsNotSet()
    {
        $formFieldViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['renderHiddenIdentityField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => 'some.property.name'];

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => 'someFormObjectName',
            ]
        ];

        $formFieldViewHelper->expects($this->never())->method('renderHiddenIdentityField');
        $formFieldViewHelper->_set('arguments', $arguments);
        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededDoesNotCreateAnythingIfPropertyIsWithoutDot()
    {
        $formFieldViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['renderHiddenIdentityField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => 'simple'];

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => 'someFormObjectName',
                'formObject' => new \stdClass(),
            ]
        ];

        $formFieldViewHelper->expects($this->never())->method('renderHiddenIdentityField');
        $formFieldViewHelper->_set('arguments', $arguments);
        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededCallsRenderIdentityFieldWithTheRightParameters()
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

        $formFieldViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['renderHiddenIdentityField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => $property];
        $formFieldViewHelper->_set('arguments', $arguments);

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => $objectName,
                'formObject' => $mockFormObject,
                'additionalIdentityProperties' => []
            ]
        ];

        $formFieldViewHelper->expects($this->once())->method('renderHiddenIdentityField')->with($mockFormObject, $expectedProperty);

        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededCallsRenderIdentityFieldWithTheRightParametersWithMoreHierarchyLevels()
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

        $formFieldViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['renderHiddenIdentityField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => $property];
        $formFieldViewHelper->_set('arguments', $arguments);

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => $objectName,
                'formObject' => $mockFormObject,
                'additionalIdentityProperties' => []
            ]
        ];

        $formFieldViewHelper->expects($this->at(0))->method('renderHiddenIdentityField')->with($mockFormObject, $expectedProperty1);
        $formFieldViewHelper->expects($this->at(1))->method('renderHiddenIdentityField')->with($mockFormObject, $expectedProperty2);

        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueAddsHiddenFieldNameToVariableContainer()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['getName'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('NewFieldName'));

        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'formObjectName' => 'someFormObjectName',
                'formObject' => new \stdClass(),
                'emptyHiddenFieldNames' => ['OldFieldName' => false]
            ]
        ];
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('addOrUpdate')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'emptyHiddenFieldNames', ['OldFieldName' => false, 'NewFieldName' => false]);

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueDoesNotAddTheSameHiddenFieldNameMoreThanOnce()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['getName'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('SomeFieldName'));
        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'emptyHiddenFieldNames' => ['SomeFieldName' => false]
            ]
        ];
        $this->viewHelperVariableContainer->expects($this->never())->method('addOrUpdate');

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function renderHiddenFieldForEmptyValueRemovesEmptySquareBracketsFromHiddenFieldName()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['getName'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('SomeFieldName[WithBrackets][]'));
        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'emptyHiddenFieldNames' => ['SomeFieldName[WithBrackets]' => false]
            ]
        ];

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function renderHiddenFieldForEmptyValueDoesNotRemoveNonEmptySquareBracketsFromHiddenFieldName()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['getName'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('SomeFieldName[WithBrackets][foo]'));
        $this->viewHelperVariableContainerData = [
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => [
                'emptyHiddenFieldNames' => ['SomeFieldName[WithBrackets][foo]' => false]
            ]
        ];

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueAddsHiddenFieldWithDisabledState()
    {
        $formViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['getName'], [], '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->tagBuilder->expects($this->any())->method('hasAttribute')->with('disabled')->will($this->returnValue(true));
        $this->tagBuilder->expects($this->any())->method('getAttribute')->with('disabled')->will($this->returnValue('disabledValue'));

        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('SomeFieldName'));

        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('addOrUpdate')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'emptyHiddenFieldNames', ['SomeFieldName' => 'disabledValue']);
        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }
}
