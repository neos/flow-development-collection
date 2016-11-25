<?php
namespace Neos\Flow\Tests\Functional\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use Neos\Flow\Tests\FunctionalTestCase;

class ActionControllerTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * Additional setup: Routes
     */
    public function setUp()
    {
        parent::setUp();

        $this->registerRoute('testa', 'test/mvc/actioncontrollertesta(/{@action})', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'ActionControllerTestA',
            '@action' => 'first',
            '@format' =>'html'
        ]);

        $this->registerRoute('testb', 'test/mvc/actioncontrollertestb(/{@action})', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'ActionControllerTestB',
            '@action' => 'first',
            '@format' => 'html'
        ]);

        $route = $this->registerRoute('testc', 'test/mvc/actioncontrollertestc/{entity}(/{@action})', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'Entity',
            '@action' => 'show',
            '@format' => 'html'
        ]);
        $route->setRoutePartsConfiguration([
            'entity' => [
                'objectType' => \Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEntity::class
            ]
        ]);
    }

    /**
     * Checks if a simple request is handled correctly. The route matching the
     * specified URI defines a default action "first" which results in firstAction
     * being called.
     *
     * @test
     */
    public function defaultActionSpecifiedInrouteIsCalledAndResponseIsReturned()
    {
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta');
        $this->assertEquals('First action was called', $response->getContent());
        $this->assertEquals('200 OK', $response->getStatus());
    }

    /**
     * Checks if a simple request is handled correctly if another than the default
     * action is specified.
     *
     * @test
     */
    public function actionSpecifiedInActionRequestIsCalledAndResponseIsReturned()
    {
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta/second');
        $this->assertEquals('Second action was called', $response->getContent());
        $this->assertEquals('200 OK', $response->getStatus());
    }

    /**
     * Checks if query parameters are handled correctly and default arguments are
     * respected / overridden.
     *
     * @test
     */
    public function queryStringOfAGetRequestIsParsedAndPassedToActionAsArguments()
    {
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta/third?secondArgument=bar&firstArgument=foo&third=baz');
        $this->assertEquals('thirdAction-foo-bar-baz-default', $response->getContent());
    }

    /**
     * @test
     */
    public function defaultTemplateIsResolvedAndUsedAccordingToConventions()
    {
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta/fourth?emailAddress=example@neos.io');
        $this->assertEquals('Fourth action <b>example@neos.io</b>', $response->getContent());
    }

    /**
     * Bug #36913
     *
     * @test
     */
    public function argumentsOfPutRequestArePassedToAction()
    {
        $request = Request::create(new Uri('http://localhost/test/mvc/actioncontrollertesta/put?getArgument=getValue'), 'PUT');
        $request->setContent('putArgument=first value');
        $request->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $request->setHeader('Content-Length', 54);

        $response = $this->browser->sendRequest($request);
        $this->assertEquals('putAction-first value-getValue', $response->getContent());
    }

    /**
     * RFC 2616 / 10.4.5 (404 Not Found)
     *
     * @test
     */
    public function notFoundStatusIsReturnedIfASpecifiedObjectCantBeFound()
    {
        $request = Request::create(new Uri('http://localhost/test/mvc/actioncontrollertestc/non-existing-id'), 'GET');

        $response = $this->browser->sendRequest($request);
        $this->assertSame(404, $response->getStatusCode());
    }


    /**
     * RFC 2616 / 10.4.7 (406 Not Acceptable)
     *
     * @test
     */
    public function notAcceptableStatusIsReturnedIfMediaTypeDoesNotMatchSupportedMediaTypes()
    {
        $request = Request::create(new Uri('http://localhost/test/mvc/actioncontrollertesta'), 'GET');
        $request->setHeader('Content-Type', 'application/xml');
        $request->setHeader('Accept', 'application/xml');
        $request->setContent('<xml></xml>');

        $response = $this->browser->sendRequest($request);
        $this->assertSame(406, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function ignoreValidationAnnotationsAreObservedForPost()
    {
        $arguments = [
            'argument' => [
                'name' => 'Foo',
                'emailAddress' => '-invalid-'
            ]
        ];
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/showobjectargument', 'POST', $arguments);

        $expectedResult = '-invalid-';
        $this->assertEquals($expectedResult, $response->getContent());
    }

    /**
     * See http://forge.typo3.org/issues/37385
     * @test
     */
    public function ignoreValidationAnnotationIsObservedWithAndWithoutDollarSign()
    {
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta/ignorevalidation?brokenArgument1=toolong&brokenArgument2=tooshort');
        $this->assertEquals('action was called', $response->getContent());
    }

    /**
     * @test
     */
    public function argumentsOfPutRequestWithJsonOrXmlTypeAreAlsoPassedToAction()
    {
        $request = Request::create(new Uri('http://localhost/test/mvc/actioncontrollertesta/put?getArgument=getValue'), 'PUT');
        $request->setHeader('Content-Type', 'application/json');
        $request->setHeader('Content-Length', 29);
        $request->setContent('{"putArgument":"first value"}');

        $response = $this->browser->sendRequest($request);
        $this->assertEquals('putAction-first value-getValue', $response->getContent());
    }

    /**
     * @test
     */
    public function objectArgumentsAreValidatedByDefault()
    {
        $arguments = [
            'argument' => [
                'name' => 'Foo',
                'emailAddress' => '-invalid-'
            ]
        ];
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/requiredobject', 'POST', $arguments);

        $expectedResult = 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->requiredObjectAction().' . PHP_EOL;
        $this->assertEquals($expectedResult, $response->getContent());
    }

    /**
     * @test
     */
    public function optionalObjectArgumentsAreValidatedByDefault()
    {
        $arguments = [
            'argument' => [
                'name' => 'Foo',
                'emailAddress' => '-invalid-'
            ]
        ];
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/optionalobject', 'POST', $arguments);

        $expectedResult = 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->optionalObjectAction().' . PHP_EOL;
        $this->assertEquals($expectedResult, $response->getContent());
    }

    /**
     * @test
     */
    public function optionalObjectArgumentsCanBeOmitted()
    {
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/optionalobject');

        $expectedResult = 'null';
        $this->assertEquals($expectedResult, $response->getContent());
    }

    /**
     * @test
     */
    public function notValidatedGroupObjectArgumentsAreNotValidated()
    {
        $arguments = [
            'argument' => [
                'name' => 'Foo',
                'emailAddress' => '-invalid-'
            ]
        ];
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/notvalidatedgroupobject', 'POST', $arguments);

        $expectedResult = '-invalid-';
        $this->assertEquals($expectedResult, $response->getContent());
    }

    /**
     * @test
     */
    public function validatedGroupObjectArgumentsAreValidated()
    {
        $arguments = [
            'argument' => [
                'name' => 'Foo',
                'emailAddress' => '-invalid-'
            ]
        ];
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/validatedgroupobject', 'POST', $arguments);

        $expectedResult = 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->validatedGroupObjectAction().' . PHP_EOL;
        $this->assertEquals($expectedResult, $response->getContent());
    }

    /**
     * Data provider for argumentTests()
     *
     * @TODO Using 'optional float - default value'    => array('optionalFloat', NULL, 12.34),
     * this fails (on some machines) because the value is 12.33999999...
     *
     * @return array
     */
    public function argumentTestsDataProvider()
    {
        $requiredArgumentExceptionText = 'Uncaught Exception in Flow #1298012500: Required argument "argument" is not set.';
        $data = [
            'required string            '       => ['requiredString', 'some String', '\'some String\''],
            'required string - missing value'   => ['requiredString', null, $requiredArgumentExceptionText],
            'optional string'                   => ['optionalString', '123', '\'123\''],
            'optional string - default'         => ['optionalString', null, '\'default\''],
            'required integer'                  => ['requiredInteger', '234', 234],
            'required integer - missing value'  => ['requiredInteger', null, $requiredArgumentExceptionText],
            'required integer - mapping error'  => ['requiredInteger', 'not an integer', 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->requiredIntegerAction().'],
            'required integer - empty value'    => ['requiredInteger', '', 'NULL'],
            'optional integer'                  => ['optionalInteger', 456, 456],
            'optional integer - default value'  => ['optionalInteger', null, 123],
            'optional integer - mapping error'  => ['optionalInteger', 'not an integer', 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->optionalIntegerAction().'],
            'optional integer - empty value'    => ['optionalInteger', '', 123],
            'required float'                    => ['requiredFloat', 34.56, 34.56],
            'required float - integer'          => ['requiredFloat', 485, '485'],
            'required float - integer2'         => ['requiredFloat', '888', '888'],
            'required float - missing value'    => ['requiredFloat', null, $requiredArgumentExceptionText],
            'required float - mapping error'    => ['requiredFloat', 'not a float', 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->requiredFloatAction().'],
            'required float - empty value'      => ['requiredFloat', '', 'NULL'],
            'optional float'                    => ['optionalFloat', 78.90, 78.9],
            'optional float - default value'    => ['optionalFloat', null, 112.34],
            'optional float - mapping error'    => ['optionalFloat', 'not a float', 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->optionalFloatAction().'],
            'optional float - empty value'      => ['optionalFloat', '', 112.34],
            'required date'                     => ['requiredDate', ['date' => '1980-12-13', 'dateFormat' => 'Y-m-d'], '1980-12-13'],
            'required date string'              => ['requiredDate', '1980-12-13T14:22:12+02:00', '1980-12-13'],
            'required date - missing value'     => ['requiredDate', null, $requiredArgumentExceptionText],
            'required date - mapping error'     => ['requiredDate', 'no date', 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->requiredDateAction().'],
            'optional date string'              => ['optionalDate', '1980-12-13T14:22:12+02:00', '1980-12-13'],
            'optional date - default value'     => ['optionalDate', null, 'null'],
            'optional date - mapping error'     => ['optionalDate', 'no date', 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->optionalDateAction().'],
            'optional date - missing value'     => ['optionalDate', null, 'null'],
            'optional date - empty value'       => ['optionalDate', '', 'null']
        ];

        if (version_compare(PHP_VERSION, '6.0.0') >= 0) {
            $data['required date - empty value'] = ['requiredDate', '', 'Uncaught Exception in Flow Argument 1 passed to Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController_Original::requiredDateAction() must be an instance of DateTime, null given'];
        } else {
            $data['required date - empty value'] = ['requiredDate', '', 'Uncaught Exception in Flow #1: Catchable Fatal Error: Argument 1 passed to Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController_Original::requiredDateAction() must be an instance of DateTime, null given'];
        }

        return $data;
    }

    /**
     * Tut Dinge.
     *
     * @param string $action
     * @param mixed $argument
     * @param string $expectedResult
     * @test
     * @dataProvider argumentTestsDataProvider
     */
    public function argumentTests($action, $argument, $expectedResult)
    {
        $arguments = [
            'argument' => $argument,
        ];

        $uri = str_replace('{@action}', strtolower($action), 'http://localhost/test/mvc/actioncontrollertestb/{@action}');
        $response = $this->browser->request($uri, 'POST', $arguments);
        $this->assertTrue(strpos(trim($response->getContent()), (string)$expectedResult) === 0, sprintf('The resulting string did not start with the expected string. Expected: "%s", Actual: "%s"', $expectedResult, $response->getContent()));
    }

    /**
     * @test
     */
    public function trustedPropertiesConfigurationDoesNotIgnoreWildcardConfigurationInController()
    {
        $entity = new TestEntity();
        $entity->setName('Foo');
        $this->persistenceManager->add($entity);
        $identifier = $this->persistenceManager->getIdentifierByObject($entity);

        $trustedPropertiesService = new MvcPropertyMappingConfigurationService();
        $trustedProperties = $trustedPropertiesService->generateTrustedPropertiesToken(['entity[__identity]', 'entity[subEntities][0][content]', 'entity[subEntities][0][date]', 'entity[subEntities][1][content]', 'entity[subEntities][1][date]']);

        $form = [
            'entity' => [
                '__identity' => $identifier,
                'subEntities' => [
                    [
                        'content' => 'Bar',
                        'date' => '1.1.2016'
                    ],
                    [
                        'content' => 'Baz',
                        'date' => '30.12.2016'
                    ]
                ]
            ],
            '__trustedProperties' => $trustedProperties
        ];
        $request = Request::create(new Uri('http://localhost/test/mvc/actioncontrollertestc/' . $identifier . '/update'), 'POST', $form);

        $response = $this->browser->sendRequest($request);
        $this->assertSame('Entity "Foo" updated', $response->getContent());
    }
}
