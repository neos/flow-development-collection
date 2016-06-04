<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Form;

/*
 * This file is part of the TYPO3.Fluid package.
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
        $mockPersistenceManager = $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('6f487e40-4483-11de-8a39-0800200c9a66'));

        $className = 'Object' . uniqid();
        $fullClassName = 'TYPO3\\Fluid\\ViewHelpers\\Form\\' . $className;
        eval('namespace TYPO3\\Fluid\\ViewHelpers\\Form; class ' . $className . ' {
			public function __clone() {}
		}');
        $object = $this->createMock($fullClassName);

        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->injectPersistenceManager($mockPersistenceManager);

        $arguments = array('name' => 'foo', 'value' => $object, 'property' => null);
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => 'myObjectName',
                'fieldNamePrefix' => 'formPrefix'
            )
        );

        $arguments = array('name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla');
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => 'myObjectName',
                'fieldNamePrefix' => 'formPrefix'
            )
        );

        $arguments = array('name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla.blubb');
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => null,
                'fieldNamePrefix' => 'formPrefix'
            )
        );

        $arguments = array('name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla');
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => null,
                'fieldNamePrefix' => 'formPrefix'
            )
        );

        $arguments = array('name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'some.property.path');
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'fieldNamePrefix' => 'formPrefix'
            )
        );

        $arguments = array('name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla');
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
        return array(
            array($mockObject),
            array('value' => array('value' => array('something' => 'MyString')))
        );
    }

    /**
     * @test
     * @dataProvider formObjectVariantsDataProvider
     */
    public function getValueAttributeBuildsValueFromPropertyAndFormObjectIfInObjectAccessorMode($formObject)
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode', 'addAdditionalIdentityPropertiesIfNeeded'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObject' => $formObject,
            )
        );

        $arguments = array('name' => null, 'value' => null, 'property' => 'value.something');
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));

        $mockArguments = array();
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertNull($formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function getValueAttributeReturnsValueArgumentIfSpecified()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $mockArguments = array('value' => 'someValue');
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertEquals('someValue', $formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function getValueAttributeConvertsObjectsToIdentifiers()
    {
        $mockObject = $this->createMock('stdClass');

        $mockPersistenceManager = $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        $mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue('6f487e40-4483-11de-8a39-0800200c9a66'));

        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->injectPersistenceManager($mockPersistenceManager);

        $mockArguments = array('value' => $mockObject);
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertSame('6f487e40-4483-11de-8a39-0800200c9a66', $formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function getValueAttributeDoesNotConvertsObjectsToIdentifiersIfTheyAreNotKnownToPersistence()
    {
        $mockObject = $this->createMock('stdClass');

        $mockPersistenceManager = $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        $mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue(null));

        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->injectPersistenceManager($mockPersistenceManager);

        $mockArguments = array('value' => $mockObject);
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertSame($mockObject, $formViewHelper->_call('getValueAttribute'));
    }

    /**
     * @test
     */
    public function isObjectAccessorModeReturnsTrueIfPropertyIsSetAndFormObjectIsGiven()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('dummy'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => 'SomeFormObjectName'
            )
        );

        $formViewHelper->_set('arguments', array('name' => null, 'value' => null, 'property' => 'bla'));
        $this->assertTrue($formViewHelper->_call('isObjectAccessorMode'));

        $formViewHelper->_set('arguments', array('name' => null, 'value' => null, 'property' => null));
        $this->assertFalse($formViewHelper->_call('isObjectAccessorMode'));
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsErrorsFromRequestIfPropertyIsSet()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->once())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', array('property' => 'bar'));

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => 'foo'
            )
        );

        $expectedResult = $this->createMock('TYPO3\Flow\Error\Result');

        $mockFormResult = $this->createMock('TYPO3\Flow\Error\Result');
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->once())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', array('property' => 'bar'));

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => null,
            )
        );

        $expectedResult = $this->createMock('TYPO3\Flow\Error\Result');

        $mockFormResult = $this->createMock('TYPO3\Flow\Error\Result');
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', array('property' => 'propertyName'));

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => 'someObject'
            )
        );

        $validationResults = $this->createMock('TYPO3\Flow\Error\Result');
        $validationResults->expects($this->once())->method('forProperty')->with('someObject.propertyName')->will($this->returnValue($validationResults));
        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($validationResults));
        $formViewHelper->_call('getMappingResultsForProperty');
    }

    /**
     * @test
     */
    public function getMappingResultsForSubPropertyReturnsValidationResultsIfErrorsHappenedInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', array('property' => 'propertyName.subPropertyName'));

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => 'someObject'
            )
        );

        $validationResults = $this->createMock('TYPO3\Flow\Error\Result');
        $validationResults->expects($this->once())->method('forProperty')->with('someObject.propertyName.subPropertyName')->will($this->returnValue($validationResults));
        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($validationResults));
        $formViewHelper->_call('getMappingResultsForProperty');
    }

    /**
     * @test
     */
    public function getMappingResultsForPropertyReturnsValidationResultsIfErrorsHappenedInNonObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $formViewHelper->_set('arguments', array('name' => 'propertyName'));

        $validationResults = $this->createMock('TYPO3\Flow\Error\Result');
        $validationResults->expects($this->once())->method('forProperty')->with('propertyName');
        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($validationResults));
        $formViewHelper->_call('getMappingResultsForProperty');
    }

    /**
     * @test
     */
    public function getMappingResultsForSubPropertyReturnsValidationResultsIfErrorsHappenedInNonObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('isObjectAccessorMode'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $formViewHelper->_set('arguments', array('name' => 'propertyName[subPropertyName]'));

        $validationResults = $this->createMock('TYPO3\Flow\Error\Result');
        $validationResults->expects($this->once())->method('forProperty')->with('propertyName.subPropertyName');
        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($validationResults));
        $formViewHelper->_call('getMappingResultsForProperty');
    }


    /**
     * @test
     */
    public function setErrorClassAttributeDoesNotSetClassAttributeIfNoErrorOccurred()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('hasArgument', 'getErrorsForProperty'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->tagBuilder->expects($this->never())->method('addAttribute');

        $formViewHelper->_call('setErrorClassAttribute');
    }

    /**
     * @test
     */
    public function setErrorClassAttributeSetsErrorClassIfAnErrorOccurred()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('hasArgument', 'getMappingResultsForProperty'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(false));
        $formViewHelper->expects($this->at(2))->method('hasArgument')->with('errorClass')->will($this->returnValue(false));

        $mockResult = $this->createMock('TYPO3\Flow\Error\Result');
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('hasArgument', 'getMappingResultsForProperty'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(true));
        $formViewHelper->expects($this->at(2))->method('hasArgument')->with('errorClass')->will($this->returnValue(false));
        $formViewHelper->_set('arguments', array('class' => 'default classes'));

        $mockResult = $this->createMock('TYPO3\Flow\Error\Result');
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('hasArgument', 'getMappingResultsForProperty'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(false));
        $formViewHelper->expects($this->at(2))->method('hasArgument')->with('errorClass')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', array('errorClass' => 'custom-error-class'));

        $mockResult = $this->createMock('TYPO3\Flow\Error\Result');
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
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('hasArgument', 'getMappingResultsForProperty'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->at(0))->method('hasArgument')->with('class')->will($this->returnValue(true));
        $formViewHelper->expects($this->at(2))->method('hasArgument')->with('errorClass')->will($this->returnValue(true));
        $formViewHelper->_set('arguments', array('class' => 'default classes', 'errorClass' => 'custom-error-class'));

        $mockResult = $this->createMock('TYPO3\Flow\Error\Result');
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
        $formFieldViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('renderHiddenIdentityField'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = array('property' => 'some.property.name');

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => 'someFormObjectName',
            )
        );

        $formFieldViewHelper->expects($this->never())->method('renderHiddenIdentityField');
        $formFieldViewHelper->_set('arguments', $arguments);
        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededDoesNotCreateAnythingIfPropertyIsWithoutDot()
    {
        $formFieldViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('renderHiddenIdentityField'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = array('property' => 'simple');

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => 'someFormObjectName',
                'formObject' => new \stdClass(),
            )
        );

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

        $formFieldViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('renderHiddenIdentityField'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = array('property' => $property);
        $formFieldViewHelper->_set('arguments', $arguments);

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => $objectName,
                'formObject' => $mockFormObject,
                'additionalIdentityProperties' => array()
            )
        );

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

        $formFieldViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('renderHiddenIdentityField'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = array('property' => $property);
        $formFieldViewHelper->_set('arguments', $arguments);

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => $objectName,
                'formObject' => $mockFormObject,
                'additionalIdentityProperties' => array()
            )
        );

        $formFieldViewHelper->expects($this->at(0))->method('renderHiddenIdentityField')->with($mockFormObject, $expectedProperty1);
        $formFieldViewHelper->expects($this->at(1))->method('renderHiddenIdentityField')->with($mockFormObject, $expectedProperty2);

        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueAddsHiddenFieldNameToVariableContainer()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('getName'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('NewFieldName'));

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'formObjectName' => 'someFormObjectName',
                'formObject' => new \stdClass(),
                'emptyHiddenFieldNames' => array('OldFieldName' => false)
            )
        );
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('addOrUpdate')->with('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'emptyHiddenFieldNames', array('OldFieldName' => false, 'NewFieldName' => false));

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueDoesNotAddTheSameHiddenFieldNameMoreThanOnce()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('getName'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('SomeFieldName'));
        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'emptyHiddenFieldNames' => array('SomeFieldName' => false)
            )
        );
        $this->viewHelperVariableContainer->expects($this->never())->method('addOrUpdate');

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueRemovesEmptySquareBracketsFromHiddenFieldName()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('getName'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('SomeFieldName[WithBrackets][]'));
        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'emptyHiddenFieldNames' => array('SomeFieldName[WithBrackets]' => false)
            )
        );

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');

        // dummy assertion to avoid "risky test" warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueDoesNotRemoveNonEmptySquareBracketsFromHiddenFieldName()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('getName'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('SomeFieldName[WithBrackets][foo]'));
        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\FormViewHelper' => array(
                'emptyHiddenFieldNames' => array('SomeFieldName[WithBrackets][foo]' => false)
            )
        );

        $formViewHelper->_call('renderHiddenFieldForEmptyValue');

        // dummy assertion to avoid "risky test" warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueAddsHiddenFieldWithDisabledState()
    {
        $formViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper', array('getName'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->tagBuilder->expects($this->any())->method('hasAttribute')->with('disabled')->will($this->returnValue(true));
        $this->tagBuilder->expects($this->any())->method('getAttribute')->with('disabled')->will($this->returnValue('disabledValue'));

        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('SomeFieldName'));

        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('addOrUpdate')->with('TYPO3\Fluid\ViewHelpers\FormViewHelper', 'emptyHiddenFieldNames', array('SomeFieldName' => 'disabledValue'));
        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }
}
