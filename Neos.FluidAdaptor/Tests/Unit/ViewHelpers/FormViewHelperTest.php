<?php

declare(strict_types=1);

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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\Exception;
use Neos\FluidAdaptor\ViewHelpers\FormViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test for the Form view helper
 */
final class FormViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var HashService|MockObject
     */
    protected $hashService;

    /**
     * @var Context|MockObject
     */
    protected $securityContext;

    /**
     * @var AuthenticationManagerInterface|MockObject
     */
    protected $mockAuthenticationManager;

    /**
     * @var MvcPropertyMappingConfigurationService|MockObject
     */
    protected $mvcPropertyMappingConfigurationService;

    /**
     * Set up test dependencies
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->arguments['action'] = '';
        $this->arguments['arguments'] = [];
        $this->arguments['controller'] = '';
        $this->arguments['package'] = '';
        $this->arguments['subpackage'] = '';
        $this->arguments['method'] = '';
        $this->arguments['object'] = null;
        $this->arguments['section'] = '';
        $this->arguments['absolute'] = false;
        $this->arguments['addQueryString'] = false;
        $this->arguments['format'] = '';
        $this->arguments['additionalParams'] = [];
        $this->arguments['argumentsToBeExcludedFromQueryString'] = [];
        $this->arguments['useParentRequest'] = false;
    }

    /**
     * @param AbstractViewHelper $viewHelper
     */
    protected function injectDependenciesIntoViewHelper(AbstractViewHelper $viewHelper)
    {
        $this->hashService = $this->createMock(HashService::class);
        $this->inject($viewHelper, 'hashService', $this->hashService);
        $this->mvcPropertyMappingConfigurationService = $this->createMock(MvcPropertyMappingConfigurationService::class);
        $this->mvcPropertyMappingConfigurationService->method('generateTrustedPropertiesToken')->willReturn('some-token');
        $this->inject($viewHelper, 'mvcPropertyMappingConfigurationService', $this->mvcPropertyMappingConfigurationService);
        $this->securityContext = $this->createMock(Context::class);
        $this->inject($viewHelper, 'securityContext', $this->securityContext);
        $this->mockAuthenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $this->inject($viewHelper, 'authenticationManager', $this->mockAuthenticationManager);
        parent::injectDependenciesIntoViewHelper($viewHelper);
    }

    #[Test]
    public function renderAddsObjectToViewHelperVariableContainer()
    {
        $formObject = new \stdClass();

        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'addFormObjectNameToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectNameFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'], [], '', false);
        $this->arguments['object'] = $formObject;
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));
        $matcher = self::exactly(3);

        $this->viewHelperVariableContainer->expects($matcher)->method('add')->willReturnCallback(function (...$parameters) use ($matcher, $formObject) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame(FormViewHelper::class, $parameters[0]);
                $this->assertSame('formObject', $parameters[1]);
                $this->assertSame($formObject, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame(FormViewHelper::class, $parameters[0]);
                $this->assertSame('additionalIdentityProperties', $parameters[1]);
                $this->assertSame([], $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame(FormViewHelper::class, $parameters[0]);
                $this->assertSame('emptyHiddenFieldNames', $parameters[1]);
                $this->assertSame([], $parameters[2]);
            }
        });
        $matcher = self::exactly(3);
        $this->viewHelperVariableContainer->expects($matcher)->method('remove')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame(FormViewHelper::class, $parameters[0]);
                $this->assertSame('formObject', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame(FormViewHelper::class, $parameters[0]);
                $this->assertSame('additionalIdentityProperties', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame(FormViewHelper::class, $parameters[0]);
                $this->assertSame('emptyHiddenFieldNames', $parameters[1]);
            }
        });
        $viewHelper->render('index');
    }

    #[Test]
    public function renderAddsObjectNameToTemplateVariableContainer()
    {
        $objectName = 'someObjectName';

        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormObjectToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'], [], '', false);
        $this->arguments['name'] = $objectName;
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(FormViewHelper::class, 'formObjectName', $objectName);
        $this->viewHelperVariableContainer->expects($this->once())->method('remove')->with(FormViewHelper::class, 'formObjectName');
        $viewHelper->render('index');
    }

    #[Test]
    public function formObjectNameArgumentOverrulesNameArgument()
    {
        $objectName = 'someObjectName';

        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormObjectToViewHelperVariableContainer', 'addFieldNamePrefixToViewHelperVariableContainer', 'removeFormObjectFromViewHelperVariableContainer', 'removeFieldNamePrefixFromViewHelperVariableContainer', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'], [], '', false);
        $this->arguments['name'] = 'formName';
        $this->arguments['objectName'] = $objectName;
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(FormViewHelper::class, 'formObjectName', $objectName);
        $this->viewHelperVariableContainer->expects($this->once())->method('remove')->with(FormViewHelper::class, 'formObjectName');
        $viewHelper->render('index');
    }

    #[Test]
    public function renderCallsRenderHiddenReferrerFields()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenReferrerFields', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'], [], '', false);
        $viewHelper->expects($this->once())->method('renderHiddenReferrerFields');
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));

        $viewHelper->render('index');
    }

    #[Test]
    public function renderCallsRenderHiddenIdentityField()
    {
        $object = new \stdClass();
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'getFormObjectName'], [], '', false);

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formFieldNames' => [],
            ]
        ];

        $this->arguments['object'] = $object;
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));

        $viewHelper->expects($this->atLeastOnce())->method('getFormObjectName')->willReturn(('MyName'));
        $viewHelper->expects($this->once())->method('renderHiddenIdentityField')->with($object, 'MyName');

        $viewHelper->render('index');
    }

    #[Test]
    public function renderWithMethodGetAddsActionUriQueryAsHiddenFields()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren'], [], '', false);

        $this->arguments['method'] = 'GET';
        $this->arguments['actionUri'] = 'http://localhost/fluid/test?foo=bar%20baz';
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));
        $viewHelper->method('renderChildren')->willReturn(('formContent'));

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formFieldNames' => [],
            ]
        ];

        $expectedResult = chr(10) .
            '<div style="display: none">' . chr(10) .
            '<input type="hidden" name="foo" value="bar baz" />' . chr(10) .
            '<input type="hidden" name="__referrer[@package]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@subpackage]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@controller]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@action]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[arguments]" value="" />' . chr(10) .
            '<input type="hidden" name="__trustedProperties" value="some-token" />' . chr(10) .
            '</div>' . chr(10) .
            'formContent';
        $this->tagBuilder->expects($this->once())->method('setContent')->with($expectedResult);

        $viewHelper->render('index');
    }

    #[Test]
    public function renderWithMethodGetAddsActionUriQueryAsHiddenFieldsWithHtmlescape()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren'], [], '', false);

        $this->arguments['method'] = 'GET';
        $this->arguments['actionUri'] = 'http://localhost/fluid/test?foo=<bar>';
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));
        $viewHelper->method('renderChildren')->willReturn(('formContent'));

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formFieldNames' => [],
            ]
        ];

        $expectedResult = '<input type="hidden" name="foo" value="&lt;bar&gt;" />';
        $this->tagBuilder->expects($this->once())->method('setContent')->with($this->stringContains($expectedResult));

        $viewHelper->render('index');
    }

    #[Test]
    public function renderWithMethodGetDoesNotBreakInRenderHiddenActionUriQueryParametersIfNoQueryStringExists()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren'], [], '', false);

        $this->arguments['method'] = 'GET';
        $this->arguments['actionUri'] = 'http://localhost/fluid/test';
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));
        $viewHelper->method('renderChildren')->willReturn(('formContent'));

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formFieldNames' => [],
            ]
        ];

        $expectedResult = chr(10) .
            '<div style="display: none">' . chr(10) .
            '<input type="hidden" name="__referrer[@package]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@subpackage]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@controller]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[@action]" value="" />' . chr(10) .
            '<input type="hidden" name="__referrer[arguments]" value="" />' . chr(10) .
            '<input type="hidden" name="__trustedProperties" value="some-token" />' . chr(10) .
            '</div>' . chr(10) .
            'formContent';
        $this->tagBuilder->expects($this->once())->method('setContent')->with($expectedResult);

        $viewHelper->render('index');
    }

    #[Test]
    public function renderCallsRenderAdditionalIdentityFields()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderAdditionalIdentityFields'], [], '', false);
        $viewHelper->expects($this->once())->method('renderAdditionalIdentityFields');
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formFieldNames' => [],
            ]
        ];

        $viewHelper->render('index');
    }

    #[Test]
    public function renderWrapsHiddenFieldsWithDivForXhtmlCompatibility()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderAdditionalIdentityFields', 'renderHiddenReferrerFields', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));
        $viewHelper->expects($this->once())->method('renderHiddenIdentityField')->willReturn(('hiddenIdentityField'));
        $viewHelper->expects($this->once())->method('renderAdditionalIdentityFields')->willReturn(('additionalIdentityFields'));
        $viewHelper->expects($this->once())->method('renderHiddenReferrerFields')->willReturn(('hiddenReferrerFields'));
        $viewHelper->expects($this->once())->method('renderChildren')->willReturn(('formContent'));
        $viewHelper->expects($this->once())->method('renderEmptyHiddenFields')->willReturn(('emptyHiddenFields'));
        $viewHelper->expects($this->once())->method('renderTrustedPropertiesField')->willReturn(('trustedPropertiesField'));

        $expectedResult = chr(10) . '<div style="display: none">hiddenIdentityFieldadditionalIdentityFieldshiddenReferrerFieldsemptyHiddenFieldstrustedPropertiesField' . '</div>' . chr(10) . 'formContent';
        $this->tagBuilder->expects($this->once())->method('setContent')->with($expectedResult);

        $viewHelper->render('index');
    }


    #[Test]
    public function renderAdditionalIdentityFieldsFetchesTheFieldsFromViewHelperVariableContainerAndBuildsHiddenFieldsForThem()
    {
        $identityProperties = [
            'object1[object2]' => '<input type="hidden" name="object1[object2][__identity]" value="42" />',
            'object1[object2][subobject]' => '<input type="hidden" name="object1[object2][subobject][__identity]" value="21" />'
        ];
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'additionalIdentityProperties' => $identityProperties,
            ]
        ];
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $expected = chr(10) . '<input type="hidden" name="object1[object2][__identity]" value="42" />' . chr(10) .
            '<input type="hidden" name="object1[object2][subobject][__identity]" value="21" />';
        $actual = $viewHelper->_call('renderAdditionalIdentityFields');
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function renderHiddenReferrerFieldsAddCurrentControllerAndActionAsHiddenFields()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, [], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));

        $this->request->expects($this->atLeastOnce())->method('getControllerPackageKey')->willReturn(('packageKey'));
        $this->request->expects($this->atLeastOnce())->method('getControllerSubpackageKey')->willReturn(('subpackageKey'));
        $this->request->expects($this->atLeastOnce())->method('getControllerName')->willReturn(('controllerName'));
        $this->request->expects($this->atLeastOnce())->method('getControllerActionName')->willReturn(('controllerActionName'));

        $hiddenFields = $viewHelper->_call('renderHiddenReferrerFields');
        $expectedResult = chr(10) . '<input type="hidden" name="__referrer[@package]" value="packageKey" />' . chr(10) .
            '<input type="hidden" name="__referrer[@subpackage]" value="subpackageKey" />' . chr(10) .
            '<input type="hidden" name="__referrer[@controller]" value="controllerName" />' . chr(10) .
            '<input type="hidden" name="__referrer[@action]" value="controllerActionName" />' . chr(10) .
            '<input type="hidden" name="__referrer[arguments]" value="" />' . chr(10);
        self::assertEquals($expectedResult, $hiddenFields);
    }

    #[Test]
    public function renderHiddenReferrerFieldsAddCurrentControllerAndActionOfParentAndSubRequestAsHiddenFields()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, [], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));

        $mockSubRequest = $this->createMock(ActionRequest::class, [], [], 'Foo', false);
        $mockSubRequest->expects($this->atLeastOnce())->method('isMainRequest')->willReturn((false));
        $mockSubRequest->expects($this->atLeastOnce())->method('getControllerPackageKey')->willReturn(('subRequestPackageKey'));
        $mockSubRequest->expects($this->atLeastOnce())->method('getControllerSubpackageKey')->willReturn(('subRequestSubpackageKey'));
        $mockSubRequest->expects($this->atLeastOnce())->method('getControllerName')->willReturn(('subRequestControllerName'));
        $mockSubRequest->expects($this->atLeastOnce())->method('getControllerActionName')->willReturn(('subRequestControllerActionName'));
        $mockSubRequest->expects($this->atLeastOnce())->method('getParentRequest')->willReturn(($this->request));
        $mockSubRequest->expects($this->atLeastOnce())->method('getArgumentNamespace')->willReturn(('subRequestArgumentNamespace'));

        $this->request->expects($this->atLeastOnce())->method('getControllerPackageKey')->willReturn(('packageKey'));
        $this->request->expects($this->atLeastOnce())->method('getControllerSubpackageKey')->willReturn(('subpackageKey'));
        $this->request->expects($this->atLeastOnce())->method('getControllerName')->willReturn(('controllerName'));
        $this->request->expects($this->atLeastOnce())->method('getControllerActionName')->willReturn(('controllerActionName'));

        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->expects($this->atLeastOnce())->method('getRequest')->willReturn(($mockSubRequest));
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

        self::assertEquals($expectedResult, $hiddenFields);
    }

    #[Test]
    public function renderAddsSpecifiedPrefixToTemplateVariableContainer()
    {
        $prefix = 'somePrefix';

        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'], [], '', false);
        $this->arguments['fieldNamePrefix'] = $prefix;
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(FormViewHelper::class, 'fieldNamePrefix', $prefix);
        $this->viewHelperVariableContainer->expects($this->once())->method('remove')->with(FormViewHelper::class, 'fieldNamePrefix');
        $viewHelper->render('index');
    }

    #[Test]
    public function renderAddsNoFieldNamePrefixToTemplateVariableContainerIfNoPrefixIsSpecified()
    {
        $expectedPrefix = '';

        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(FormViewHelper::class, 'fieldNamePrefix', $expectedPrefix);
        $this->viewHelperVariableContainer->expects($this->once())->method('remove')->with(FormViewHelper::class, 'fieldNamePrefix');
        $viewHelper->render('index');
    }

    #[Test]
    public function renderAddsDefaultFieldNamePrefixToTemplateVariableContainerIfNoPrefixIsSpecifiedAndRequestIsASubRequest()
    {
        $expectedPrefix = 'someArgumentPrefix';
        $mockSubRequest = $this->createMock(ActionRequest::class);
        $mockSubRequest->expects($this->once())->method('getArgumentNamespace')->willReturn($expectedPrefix);

        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['getFormActionUri', 'renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'], [], '', false);
        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->method('getRequest')->willReturn($mockSubRequest);
        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(FormViewHelper::class, 'fieldNamePrefix', $expectedPrefix);
        $this->viewHelperVariableContainer->expects($this->once())->method('remove')->with(FormViewHelper::class, 'fieldNamePrefix');
        $viewHelper->render('index');
    }

    #[Test]
    public function renderAddsDefaultFieldNamePrefixToTemplateVariableContainerIfNoPrefixIsSpecifiedAndUseParentRequestArgumentIsSet()
    {
        $expectedPrefix = 'parentRequestsPrefix';
        $mockParentRequest = $this->createMock(ActionRequest::class);
        $mockParentRequest->expects($this->once())->method('getArgumentNamespace')->willReturn(($expectedPrefix));
        $mockSubRequest = $this->createMock(ActionRequest::class);
        $mockSubRequest->expects($this->once())->method('getParentRequest')->willReturn(($mockParentRequest));

        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['getFormActionUri', 'renderChildren', 'renderHiddenIdentityField', 'renderHiddenReferrerFields', 'addFormFieldNamesToViewHelperVariableContainer', 'removeFormFieldNamesFromViewHelperVariableContainer', 'addEmptyHiddenFieldNamesToViewHelperVariableContainer', 'removeEmptyHiddenFieldNamesFromViewHelperVariableContainer', 'renderEmptyHiddenFields', 'renderTrustedPropertiesField'], [], '', false);
        $this->arguments['useParentRequest'] = true;
        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->method('getRequest')->willReturn(($mockSubRequest));
        $this->renderingContext->setControllerContext($this->controllerContext);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((false));

        $this->viewHelperVariableContainer->expects($this->once())->method('add')->with(FormViewHelper::class, 'fieldNamePrefix', $expectedPrefix);
        $viewHelper->render('index');
    }

    #[Test]
    public function renderEmptyHiddenFieldsRendersEmptyStringByDefault()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $expected = '';
        $actual = $viewHelper->_call('renderEmptyHiddenFields');
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function renderEmptyHiddenFieldsRendersOneHiddenFieldPerEntry()
    {
        $emptyHiddenFieldNames = ['fieldName1' => false, 'fieldName2' => false];
        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'emptyHiddenFieldNames' => $emptyHiddenFieldNames,
            ]
        ];
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $expected = '<input type="hidden" name="fieldName1" value="" />' . chr(10) . '<input type="hidden" name="fieldName2" value="" />' . chr(10);
        $actual = $viewHelper->_call('renderEmptyHiddenFields');
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function renderResetsFormActionUri()
    {
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->_set('formActionUri', 'someUri');

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formFieldNames' => [],
            ]
        ];

        $viewHelper->render('index');
        self::assertNull($viewHelper->_get('formActionUri'));
    }

    #[Test]
    public function renderThrowsExceptionIfNeitherActionNorActionUriArgumentIsSpecified()
    {
        $this->expectException(Exception::class);
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper = $this->prepareArguments($viewHelper, []);
        $viewHelper->render();
    }

    #[Test]
    public function renderThrowsExceptionIfUseParentRequestIsSetAndTheCurrentRequestHasNoParentRequest()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1361354942);
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, ['renderChildren'], [], '', false);
        $this->arguments['useParentRequest'] = true;
        $this->arguments['action'] = 'index';
        $this->injectDependenciesIntoViewHelper($viewHelper);

        $this->viewHelperVariableContainerData = [
            FormViewHelper::class => [
                'formFieldNames' => [],
            ]
        ];

        $viewHelper = $this->prepareArguments($viewHelper, $this->arguments);
        $viewHelper->render();
    }

    #[Test]
    public function renderUsesParentRequestIfUseParentRequestIsSet()
    {
        $mockParentRequest = $this->createStub(ActionRequest::class);

        $mockSubRequest = $this->createMock(ActionRequest::class);
        $mockSubRequest->expects($this->once())->method('isMainRequest')->willReturn((false));
        $mockSubRequest->expects($this->once())->method('getParentRequest')->willReturn(($mockParentRequest));

        $this->uriBuilder->expects($this->once())->method('setRequest')->with($mockParentRequest);

        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, [], [], '', false);
        $this->arguments['useParentRequest'] = true;

        $this->controllerContext = $this->createMock(ControllerContext::class);
        $this->controllerContext->method('getRequest')->willReturn(($mockSubRequest));
        $this->controllerContext->expects($this->once())->method('getUriBuilder')->willReturn(($this->uriBuilder));
        $this->renderingContext->setControllerContext($this->controllerContext);

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->_call('getFormActionUri');
    }

    #[Test]
    public function csrfTokenFieldIsNotRenderedIfFormMethodIsSafe()
    {
        $this->arguments['method'] = 'get';

        /** @var FormViewHelper|MockObject $viewHelper */
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, [], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->never())->method('getCsrfProtectionToken');

        self::assertEquals('', $viewHelper->_call('renderCsrfTokenField'));
    }

    #[Test]
    public function csrfTokenFieldIsNotRenderedIfSecurityContextIsNotInitialized()
    {
        /** @var FormViewHelper|MockObject $viewHelper */
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, [], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->expects($this->atLeastOnce())->method('isInitialized')->willReturn((false));
        $this->mockAuthenticationManager->method('isAuthenticated')->willReturn((true));
        $this->securityContext->expects($this->never())->method('getCsrfProtectionToken');

        self::assertEquals('', $viewHelper->_call('renderCsrfTokenField'));
    }

    #[Test]
    public function csrfTokenFieldIsNotRenderedIfNoAccountIsAuthenticated()
    {
        /** @var FormViewHelper|MockObject $viewHelper */
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, [], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((true));
        $this->mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((false));
        $this->securityContext->expects($this->never())->method('getCsrfProtectionToken');

        self::assertEquals('', $viewHelper->_call('renderCsrfTokenField'));
    }

    #[Test]
    public function csrfTokenFieldIsRenderedForUnsafeRequests()
    {
        /** @var FormViewHelper|MockObject $viewHelper */
        $viewHelper = $this->getAccessibleMock(FormViewHelper::class, [], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->securityContext->method('isInitialized')->willReturn((true));
        $this->mockAuthenticationManager->method('isAuthenticated')->willReturn((true));

        $this->securityContext->expects($this->atLeastOnce())->method('getCsrfProtectionToken')->willReturn(('CSRFTOKEN'));

        self::assertEquals('<input type="hidden" name="__csrfToken" value="CSRFTOKEN" />' . chr(10), $viewHelper->_call('renderCsrfTokenField'));
    }
}
