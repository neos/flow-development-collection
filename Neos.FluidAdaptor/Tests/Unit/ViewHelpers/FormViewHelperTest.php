<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\ViewHelpers\FormViewHelper;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for the Form view helper
 */
class FormViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var HashService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $hashService;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    /**
     * @var AuthenticationManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockAuthenticationManager;

    /**
     * @var MvcPropertyMappingConfigurationService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mvcPropertyMappingConfigurationService;

    /**
     * Set up test dependencies
     */
    public function setUp()
    {
        parent::setUp();
        $this->arguments['action'] = '';
        $this->arguments['arguments'] = array();
        $this->arguments['controller'] = '';
        $this->arguments['package'] = '';
        $this->arguments['subpackage'] = '';
        $this->arguments['method'] = '';
        $this->arguments['object'] = null;
        $this->arguments['section'] = '';
        $this->arguments['absolute'] = false;
        $this->arguments['addQueryString'] = false;
        $this->arguments['format'] = '';
        $this->arguments['additionalParams'] = array();
        $this->arguments['argumentsToBeExcludedFromQueryString'] = array();
        $this->arguments['useParentRequest'] = false;
    }

    /**
     * @param AbstractViewHelper $viewHelper
     */
    protected function injectDependenciesIntoViewHelper(AbstractViewHelper $viewHelper)
    {
        $this->hashService = $this->createMock(\Neos\Flow\Security\Cryptography\HashService::class);
        $this->inject($viewHelper, 'hashService', $this->hashService);
        $this->mvcPropertyMappingConfigurationService = $this->createMock(\Neos\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        $this->inject($viewHelper, 'mvcPropertyMappingConfigurationService', $this->mvcPropertyMappingConfigurationService);
        $this->securityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $this->inject($viewHelper, 'securityContext', $this->securityContext);
        $this->mockAuthenticationManager = $this->createMock(\Neos\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $this->inject($viewHelper, 'authenticationManager', $this->mockAuthenticationManager);
        parent::injectDependenciesIntoViewHelper($viewHelper);
    }

    /**
     * @test
     */
    public function renderAddsObjectToViewHelperVariableContainer()
    {
        $formObject = new \stdClass();

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'addFormObjectNameToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectNameFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'), array(), '', false);
        $this->arguments['object'] = $formObject;
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));

        $this->viewHelperVariableContainer->expects($this->at(0))->method('add')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'formObject', $formObject);
        $this->viewHelperVariableContainer->expects($this->at(1))->method('add')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties', array());
        $this->viewHelperVariableContainer->expects($this->at(2))->method('add')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'emptyHiddenFieldNames', array());
        $this->viewHelperVariableContainer->expects($this->at(3))->method('remove')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'formObject');
        $this->viewHelperVariableContainer->expects($this->at(4))->method('remove')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'additionalIdentityProperties');
        $this->viewHelperVariableContainer->expects($this->at(5))->method('remove')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'emptyHiddenFieldNames');
        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderAddsObjectNameToTemplateVariableContainer()
    {
        $objectName = 'someObjectName';

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormObjectToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'), array(), '', false);
        $this->arguments['name'] = $objectName;
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'formObjectName', $objectName);
        $this->viewHelperVariableContainer->expects($this->once())->method('remove')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'formObjectName');
        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function formObjectNameArgumentOverrulesNameArgument()
    {
        $objectName = 'someObjectName';

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormObjectToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'), array(), '', false);
        $this->arguments['name'] = 'formName';
        $this->arguments['objectName'] = $objectName;
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'formObjectName', $objectName);
        $this->viewHelperVariableContainer->expects($this->once())->method('remove')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'formObjectName');
        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderCallsRenderHiddenReferrerFields()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren', 'renderHiddenReferrerFields', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'), array(), '', false);
        $viewHelper->expects($this->once())->method('renderHiddenReferrerFields');
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));

        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderCallsRenderHiddenIdentityField()
    {
        $object = new \stdClass();
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren', 'renderHiddenIdentityField', 'getFormObjectName'), array(), '', false);

        $this->viewHelperVariableContainerData = array(
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => array(
                'formFieldNames' => array(),
            )
        );

        $this->arguments['object'] = $object;
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));

        $viewHelper->expects($this->atLeastOnce())->method('getFormObjectName')->will($this->returnValue('MyName'));
        $viewHelper->expects($this->once())->method('renderHiddenIdentityField')->with($object, 'MyName');

        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderWithMethodGetAddsActionUriQueryAsHiddenFields()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren'), array(), '', false);

        $this->arguments['method'] = 'GET';
        $this->arguments['actionUri'] = 'http://localhost/fluid/test?foo=bar%20baz';
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));
        $viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('formContent'));

        $this->viewHelperVariableContainerData = array(
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => array(
                'formFieldNames' => array(),
            )
        );

        $expectedResult = chr(10) .
            '<div style="display: none">' . chr(10) .
            '<input type="hidden" name="foo" value="bar baz" />' . chr(10) .
            '<input type="hidden" name="__referrer[@package]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@subpackage]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@controller]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@action]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[arguments]" value="" />' . chr(10) .
            '<input type="hidden" name="__trustedProperties" value="" />' . chr(10) .
            '</div>' . chr(10) .
            'formContent';
        $this->tagBuilder->expects($this->once())->method('setContent')->with($expectedResult);

        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderWithMethodGetAddsActionUriQueryAsHiddenFieldsWithHtmlescape()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren'), array(), '', false);

        $this->arguments['method'] = 'GET';
        $this->arguments['actionUri'] = 'http://localhost/fluid/test?foo=<bar>';
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));
        $viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('formContent'));

        $this->viewHelperVariableContainerData = array(
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => array(
                'formFieldNames' => array(),
            )
        );

        $expectedResult = '<input type="hidden" name="foo" value="&lt;bar&gt;" />';
        $this->tagBuilder->expects($this->once())->method('setContent')->with($this->stringContains($expectedResult));

        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderWithMethodGetDoesNotBreakInRenderHiddenActionUriQueryParametersIfNoQueryStringExists()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren'), array(), '', false);

        $this->arguments['method'] = 'GET';
        $this->arguments['actionUri'] = 'http://localhost/fluid/test';
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));
        $viewHelper->expects($this->any())->method('renderChildren')->will($this->returnValue('formContent'));

        $this->viewHelperVariableContainerData = array(
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => array(
                'formFieldNames' => array(),
            )
        );

        $expectedResult = chr(10) .
            '<div style="display: none">' . chr(10) .
            '<input type="hidden" name="__referrer[@package]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@subpackage]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@controller]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@action]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[arguments]" value="" />' . chr(10) .
            '<input type="hidden" name="__trustedProperties" value="" />' . chr(10) .
            '</div>' . chr(10) .
            'formContent';
        $this->tagBuilder->expects($this->once())->method('setContent')->with($expectedResult);

        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderCallsRenderAdditionalIdentityFields()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren', 'renderAdditionalIdentityFields'), array(), '', false);
        $viewHelper->expects($this->once())->method('renderAdditionalIdentityFields');
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));

        $this->viewHelperVariableContainerData = array(
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => array(
                'formFieldNames' => array(),
            )
        );

        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderWrapsHiddenFieldsWithDivForXhtmlCompatibility()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));
        $viewHelper->expects($this->once())->method('renderHiddenIdentityField')->will($this->returnValue('hiddenIdentityField'));
        $viewHelper->expects($this->once())->method('renderAdditionalIdentityFields')->will($this->returnValue('additionalIdentityFields'));
        $viewHelper->expects($this->once())->method('renderHiddenReferrerFields')->will($this->returnValue('hiddenReferrerFields'));
        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('formContent'));
        $viewHelper->expects($this->once())->method('renderEmptyHiddenFields')->will($this->returnValue('emptyHiddenFields'));
        $viewHelper->expects($this->once())->method('renderTrustedPropertiesField')->will($this->returnValue('trustedPropertiesField'));

        $expectedResult = chr(10) . '<div style="display: none">hiddenIdentityFieldadditionalIdentityFieldshiddenReferrerFieldsemptyHiddenFieldstrustedPropertiesField' . '</div>' . chr(10) . 'formContent';
        $this->tagBuilder->expects($this->once())->method('setContent')->with($expectedResult);

        $viewHelper->render('index');
    }


    /**
     * @test
     */
    public function renderAdditionalIdentityFieldsFetchesTheFieldsFromViewHelperVariableContainerAndBuildsHiddenFieldsForThem()
    {
        $identityProperties = array(
            'object1[object2]' => '<input type="hidden" name="object1[object2][__identity]" value="42" />',
            'object1[object2][subobject]' => '<input type="hidden" name="object1[object2][subobject][__identity]" value="21" />'
        );
        $this->viewHelperVariableContainerData = array(
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => array(
                'additionalIdentityProperties' => $identityProperties,
            )
        );
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $expected = chr(10) . '<input type="hidden" name="object1[object2][__identity]" value="42" />' . chr(10) .
            '<input type="hidden" name="object1[object2][subobject][__identity]" value="21" />';
        $actual = $viewHelper->_call('renderAdditionalIdentityFields');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderHiddenReferrerFieldsAddCurrentControllerAndActionAsHiddenFields()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('dummy'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));

        $this->request->expects($this->atLeastOnce())->method('getControllerPackageKey')->will($this->returnValue('packageKey'));
        $this->request->expects($this->atLeastOnce())->method('getControllerSubpackageKey')->will($this->returnValue('subpackageKey'));
        $this->request->expects($this->atLeastOnce())->method('getControllerName')->will($this->returnValue('controllerName'));
        $this->request->expects($this->atLeastOnce())->method('getControllerActionName')->will($this->returnValue('controllerActionName'));

        $hiddenFields = $viewHelper->_call('renderHiddenReferrerFields');
        $expectedResult = chr(10) . '<input type="hidden" name="__referrer[@package]" value="packageKey" />' . chr(10) .
            '<input type="hidden" name="__referrer[@subpackage]" value="subpackageKey" />' . chr(10) .
            '<input type="hidden" name="__referrer[@controller]" value="controllerName" />' . chr(10) .
            '<input type="hidden" name="__referrer[@action]" value="controllerActionName" />' . chr(10) .
            '<input type="hidden" name="__referrer[arguments]" value="" />' . chr(10);
        $this->assertEquals($expectedResult, $hiddenFields);
    }

    /**
     * @test
     */
    public function renderHiddenReferrerFieldsAddCurrentControllerAndActionOfParentAndSubRequestAsHiddenFields()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('dummy'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));

        $mockSubRequest = $this->createMock(\Neos\Flow\Mvc\ActionRequest::class, array(), array(), 'Foo', false);
        $mockSubRequest->expects($this->atLeastOnce())->method('isMainRequest')->will($this->returnValue(false));
        $mockSubRequest->expects($this->atLeastOnce())->method('getControllerPackageKey')->will($this->returnValue('subRequestPackageKey'));
        $mockSubRequest->expects($this->atLeastOnce())->method('getControllerSubpackageKey')->will($this->returnValue('subRequestSubpackageKey'));
        $mockSubRequest->expects($this->atLeastOnce())->method('getControllerName')->will($this->returnValue('subRequestControllerName'));
        $mockSubRequest->expects($this->atLeastOnce())->method('getControllerActionName')->will($this->returnValue('subRequestControllerActionName'));
        $mockSubRequest->expects($this->atLeastOnce())->method('getParentRequest')->will($this->returnValue($this->request));
        $mockSubRequest->expects($this->atLeastOnce())->method('getArgumentNamespace')->will($this->returnValue('subRequestArgumentNamespace'));

        $this->request->expects($this->atLeastOnce())->method('getControllerPackageKey')->will($this->returnValue('packageKey'));
        $this->request->expects($this->atLeastOnce())->method('getControllerSubpackageKey')->will($this->returnValue('subpackageKey'));
        $this->request->expects($this->atLeastOnce())->method('getControllerName')->will($this->returnValue('controllerName'));
        $this->request->expects($this->atLeastOnce())->method('getControllerActionName')->will($this->returnValue('controllerActionName'));

        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects($this->atLeastOnce())->method('getRequest')->will($this->returnValue($mockSubRequest));
        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $hiddenFields = $viewHelper->_call('renderHiddenReferrerFields');
        $expectedResult = chr(10) . '<input type="hidden" name="subRequestArgumentNamespace[__referrer][@package]" value="subRequestPackageKey" />' . chr(10) .
            '<input type="hidden" name="subRequestArgumentNamespace[__referrer][@subpackage]" value="subRequestSubpackageKey" />' . chr(10) .
            '<input type="hidden" name="subRequestArgumentNamespace[__referrer][@controller]" value="subRequestControllerName" />' . chr(10) .
            '<input type="hidden" name="subRequestArgumentNamespace[__referrer][@action]" value="subRequestControllerActionName" />' . chr(10) .
            '<input type="hidden" name="subRequestArgumentNamespace[__referrer][arguments]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@package]" value="packageKey" />' . chr(10) .
            '<input type="hidden" name="__referrer[@subpackage]" value="subpackageKey" />' . chr(10) .
            '<input type="hidden" name="__referrer[@controller]" value="controllerName" />' . chr(10) .
            '<input type="hidden" name="__referrer[@action]" value="controllerActionName" />' . chr(10) .
            '<input type="hidden" name="__referrer[arguments]" value="" />' . chr(10);

        $this->assertEquals($expectedResult, $hiddenFields);
    }

    /**
     * @test
     */
    public function renderAddsSpecifiedPrefixToTemplateVariableContainer()
    {
        $prefix = 'somePrefix';

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'), array(), '', false);
        $this->arguments['fieldNamePrefix'] = $prefix;
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix', $prefix);
        $this->viewHelperVariableContainer->expects($this->once())->method('remove')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix');
        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderAddsNoFieldNamePrefixToTemplateVariableContainerIfNoPrefixIsSpecified()
    {
        $expectedPrefix = '';

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix', $expectedPrefix);
        $this->viewHelperVariableContainer->expects($this->once())->method('remove')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix');
        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderAddsDefaultFieldNamePrefixToTemplateVariableContainerIfNoPrefixIsSpecifiedAndRequestIsASubRequest()
    {
        $expectedPrefix = 'someArgumentPrefix';
        $mockSubRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockSubRequest->expects($this->once())->method('getArgumentNamespace')->will($this->returnValue($expectedPrefix));

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('getFormActionUri', 'renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'), array(), '', false);
        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockSubRequest));
        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix', $expectedPrefix);
        $this->viewHelperVariableContainer->expects($this->once())->method('remove')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix');
        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderAddsDefaultFieldNamePrefixToTemplateVariableContainerIfNoPrefixIsSpecifiedAndUseParentRequestArgumentIsSet()
    {
        $expectedPrefix = 'parentRequestsPrefix';
        $mockParentRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockParentRequest->expects($this->once())->method('getArgumentNamespace')->will($this->returnValue($expectedPrefix));
        $mockSubRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockSubRequest->expects($this->once())->method('getParentRequest')->will($this->returnValue($mockParentRequest));

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('getFormActionUri', 'renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'), array(), '', false);
        $this->arguments['useParentRequest'] = true;
        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockSubRequest));
        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(false));

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix', $expectedPrefix);
        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderEmptyHiddenFieldsRendersEmptyStringByDefault()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $expected = '';
        $actual = $viewHelper->_call('renderEmptyHiddenFields');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderEmptyHiddenFieldsRendersOneHiddenFieldPerEntry()
    {
        $emptyHiddenFieldNames = array('fieldName1' => false, 'fieldName2' => false);
        $this->viewHelperVariableContainerData = array(
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => array(
                'emptyHiddenFieldNames' => $emptyHiddenFieldNames,
            )
        );
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $expected = '<input type="hidden" name="fieldName1" value="" />' . chr(10) . '<input type="hidden" name="fieldName2" value="" />' . chr(10);
        $actual = $viewHelper->_call('renderEmptyHiddenFields');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderResetsFormActionUri()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->_set('formActionUri', 'someUri');

        $this->viewHelperVariableContainerData = array(
            \Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class => array(
                'formFieldNames' => array(),
            )
        );

        $viewHelper->render('index');
        $this->assertNull($viewHelper->_get('formActionUri'));
    }

    /**
     * @test
     * @expectedException \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfNeitherActionNorActionUriArgumentIsSpecified()
    {
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->render();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfUseParentRequestIsSetAndTheCurrentRequestHasNoParentRequest()
    {
        $this->setExpectedException(\Neos\FluidAdaptor\Core\ViewHelper\Exception::class, '', 1361354942);

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('renderChildren'), array(), '', false);
        $this->arguments['useParentRequest'] = true;
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->render('index');
    }

    /**
     * @test
     */
    public function renderUsesParentRequestIfUseParentRequestIsSet()
    {
        $mockParentRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $mockSubRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockSubRequest->expects($this->once())->method('isMainRequest')->will($this->returnValue(false));
        $mockSubRequest->expects($this->once())->method('getParentRequest')->will($this->returnValue($mockParentRequest));

        $this->uriBuilder->expects($this->once())->method('setRequest')->with($mockParentRequest);

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, array('dummy'), array(), '', false);
        $this->arguments['useParentRequest'] = true;

        $this->controllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->controllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockSubRequest));
        $this->controllerContext->expects($this->once())->method('getUriBuilder')->will($this->returnValue($this->uriBuilder));
        $this->renderingContext->setControllerContext($this->controllerContext);

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->_call('getFormActionUri');
    }

    /**
     * @test
     */
    public function csrfTokenFieldIsNotRenderedIfFormMethodIsSafe()
    {
        $this->arguments['method'] = 'get';

        /** @var FormViewHelper|\PHPUnit_Framework_MockObject_MockObject $viewHelper */
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, null, array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->never())->method('getCsrfProtectionToken');

        $this->assertEquals('', $viewHelper->_call('renderCsrfTokenField'));
    }

    /**
     * @test
     */
    public function csrfTokenFieldIsNotRenderedIfSecurityContextIsNotInitialized()
    {
        /** @var FormViewHelper|\PHPUnit_Framework_MockObject_MockObject $viewHelper */
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, null, array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->atLeastOnce())->method('isInitialized')->will($this->returnValue(false));
        $this->mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));
        $this->securityContext->expects($this->never())->method('getCsrfProtectionToken');

        $this->assertEquals('', $viewHelper->_call('renderCsrfTokenField'));
    }

    /**
     * @test
     */
    public function csrfTokenFieldIsNotRenderedIfNoAccountIsAuthenticated()
    {
        /** @var FormViewHelper|\PHPUnit_Framework_MockObject_MockObject $viewHelper */
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, null, array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(true));
        $this->mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(false));
        $this->securityContext->expects($this->never())->method('getCsrfProtectionToken');

        $this->assertEquals('', $viewHelper->_call('renderCsrfTokenField'));
    }

    /**
     * @test
     */
    public function csrfTokenFieldIsRenderedForUnsafeRequests()
    {
        /** @var FormViewHelper|\PHPUnit_Framework_MockObject_MockObject $viewHelper */
        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\FormViewHelper::class, null, array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(true));
        $this->mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $this->securityContext->expects($this->atLeastOnce())->method('getCsrfProtectionToken')->will($this->returnValue('CSRFTOKEN'));

        $this->assertEquals('<input type="hidden" name="__csrfToken" value="CSRFTOKEN" />' . chr(10), $viewHelper->_call('renderCsrfTokenField'));
    }
}
