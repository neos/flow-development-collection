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

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Http\ContentStream;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Http\Cookie;
use Neos\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService;
use Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\StandardController;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestEntity;
use Neos\Flow\Tests\FunctionalTestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;

class ActionControllerTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;

    /**
     * Additional setup: Routes
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerRoute('test', 'test/mvc/actioncontrollertest(/{@action})', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'Standard',
            '@action' => 'index',
            '@format' =>'html'
        ]);

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
                'objectType' => TestEntity::class
            ]
        ]);

        $this->serverRequestFactory = $this->objectManager->get(ServerRequestFactoryInterface::class);
    }

    /**
     * Checks if a simple request is handled correctly. The route matching the
     * specified URI defines a default action "first" which results in firstAction
     * being called.
     *
     * @test
     */
    public function defaultActionSpecifiedInRouteIsCalledAndResponseIsReturned()
    {
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta');
        self::assertEquals('First action was called', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
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
        self::assertEquals('Second action was called', $response->getBody()->getContents());
        self::assertEquals(200, $response->getStatusCode());
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
        self::assertEquals('thirdAction-foo-bar-baz-default', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function defaultTemplateIsResolvedAndUsedAccordingToConventions()
    {
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta/fourth?emailAddress=example@neos.io');
        self::assertEquals('Fourth action <b>example@neos.io</b>', $response->getBody()->getContents());
    }

    /**
     * Bug #36913
     *
     * @test
     */
    public function argumentsOfPutRequestArePassedToAction()
    {
        $request = $this->serverRequestFactory->createServerRequest('PUT', new Uri('http://localhost/test/mvc/actioncontrollertesta/put?getArgument=getValue'));
        $request = $request
            ->withBody(ContentStream::fromContents('putArgument=first value'))
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Content-Length', 54);

        $response = $this->browser->sendRequest($request);
        self::assertEquals('putAction-first value-getValue', $response->getBody()->getContents());
    }

    /**
     * RFC 2616 / 10.4.5 (404 Not Found)
     *
     * @test
     */
    public function notFoundStatusIsReturnedIfASpecifiedObjectCantBeFound()
    {
        $request = new ServerRequest('GET', new Uri('http://localhost/test/mvc/actioncontrollertestc/non-existing-id'));

        $response = $this->browser->sendRequest($request);
        self::assertSame(404, $response->getStatusCode());
    }


    /**
     * RFC 2616 / 10.4.7 (406 Not Acceptable)
     *
     * @test
     */
    public function notAcceptableStatusIsReturnedIfMediaTypeDoesNotMatchSupportedMediaTypes()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://localhost/test/mvc/actioncontrollertesta'))
            ->withHeader('Content-Type', 'application/xml')
            ->withHeader('Accept', 'application/xml')
            ->withBody(ContentStream::fromContents('<xml></xml>'));

        $response = $this->browser->sendRequest($request);
        self::assertSame(406, $response->getStatusCode());
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
        self::assertEquals($expectedResult, $response->getBody()->getContents());
    }

    /**
     * See http://forge.typo3.org/issues/37385
     * @test
     */
    public function ignoreValidationAnnotationIsObservedWithAndWithoutDollarSign()
    {
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertesta/ignorevalidation?brokenArgument1=toolong&brokenArgument2=tooshort');
        self::assertEquals('action was called', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function argumentsOfPutRequestWithJsonOrXmlTypeAreAlsoPassedToAction()
    {
        $request = $this->serverRequestFactory->createServerRequest('PUT', new Uri('http://localhost/test/mvc/actioncontrollertesta/put?getArgument=getValue'))
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Content-Length', 29)
            ->withBody(ContentStream::fromContents('{"putArgument":"first value"}'));

        $response = $this->browser->sendRequest($request);
        self::assertEquals('putAction-first value-getValue', $response->getBody()->getContents());
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
        self::assertEquals($expectedResult, $response->getBody()->getContents());
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
        self::assertEquals($expectedResult, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function optionalObjectArgumentsCanBeOmitted()
    {
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/optionalobject');

        $expectedResult = 'null';
        self::assertEquals($expectedResult, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function optionalObjectArgumentsCanBeAnnotatedNullable()
    {
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/optionalannotatedobject');

        $expectedResult = 'null';
        self::assertEquals($expectedResult, $response->getBody()->getContents());
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
        self::assertEquals($expectedResult, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function notValidatedGroupCollectionsAreNotValidated()
    {
        $arguments = [
            'argument' => [
                'name' => 'Foo',
                'collection' => [
                    [
                        'name' => 'Bar',
                        'emailAddress' => '-invalid-'
                    ]
                ]
            ]
        ];
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/notvalidatedgroupcollection', 'POST', $arguments);

        $expectedResult = '-invalid-';
        self::assertEquals($expectedResult, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function notValidatedGroupModelRelationIsNotValidated()
    {
        $arguments = [
            'argument' => [
                'name' => 'Foo',
                'emailAddress' => '-invalid-',
                'related' => [
                    'name' => 'Bar',
                    'emailAddress' => '-invalid-'
                ]
            ]
        ];
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/notvalidatedgroupobject', 'POST', $arguments);

        $expectedResult = '-invalid-';
        self::assertEquals($expectedResult, $response->getBody()->getContents());
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
        self::assertEquals($expectedResult, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function validatedGroupCollectionsAreValidated()
    {
        $arguments = [
            'argument' => [
                'name' => 'Foo',
                'collection' => [
                    [
                        'name' => 'Bar',
                        'emailAddress' => '-invalid-'
                    ]
                ]
            ]
        ];
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/validatedgroupcollection', 'POST', $arguments);

        $expectedResult = 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->validatedGroupCollectionAction().' . PHP_EOL;
        self::assertEquals($expectedResult, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function validatedGroupModelRelationIsValidated()
    {
        $arguments = [
            'argument' => [
                'name' => 'Foo',
                'related' => [
                    'name' => 'Bar',
                    'emailAddress' => '-invalid-'
                ]
            ]
        ];
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/validatedgroupobject', 'POST', $arguments);

        $expectedResult = 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->validatedGroupObjectAction().' . PHP_EOL;
        self::assertEquals($expectedResult, $response->getBody()->getContents());
    }

    /**
     * Data provider for argumentTests()
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
            'optional string - nullable'        => ['optionalNullableString', null, 'NULL'],
            'required integer'                  => ['requiredInteger', '234', 234],
            'required integer - missing value'  => ['requiredInteger', null, $requiredArgumentExceptionText],
            'required integer - mapping error'  => ['requiredInteger', 'not an integer', 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->requiredIntegerAction().'],
            'required integer - empty value'    => ['requiredInteger', '', 'NULL'],
            'optional integer'                  => ['optionalInteger', 456, 456],
            'optional integer - default value'  => ['optionalInteger', null, 123],
            'optional integer - mapping error'  => ['optionalInteger', 'not an integer', 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->optionalIntegerAction().'],
            'optional integer - empty value'    => ['optionalInteger', '', 123],
            'optional integer - nullable'       => ['optionalNullableInteger', null, 'NULL'],
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
            'optional float - nullable'         => ['optionalNullableFloat', null, 'NULL'],
            'required date'                     => ['requiredDate', ['date' => '1980-12-13', 'dateFormat' => 'Y-m-d'], '1980-12-13'],
            'required date string'              => ['requiredDate', '1980-12-13T14:22:12+02:00', '1980-12-13'],
            'required date - missing value'     => ['requiredDate', null, $requiredArgumentExceptionText],
            'required date - mapping error'     => ['requiredDate', 'no date', 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->requiredDateAction().'],
            'required date - empty value'       => ['requiredDate', '', 'Uncaught Exception in Flow Argument 1 passed to Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController_Original::requiredDateAction() must be an instance of DateTime, null given'],
            'optional date string'              => ['optionalDate', '1980-12-13T14:22:12+02:00', '1980-12-13'],
            'optional date - default value'     => ['optionalDate', null, 'null'],
            'optional date - mapping error'     => ['optionalDate', 'no date', 'Validation failed while trying to call Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller\ActionControllerTestBController->optionalDateAction().'],
            'optional date - missing value'     => ['optionalDate', null, 'null'],
            'optional date - empty value'       => ['optionalDate', '', 'null'],
        ];

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
        self::assertTrue(strpos(trim($response->getBody()->getContents()), (string)$expectedResult) === 0, sprintf('The resulting string did not start with the expected string. Expected: "%s", Actual: "%s"', $expectedResult, $response->getBody()->getContents()));
    }

    /**
     * @test
     */
    public function wholeRequestBodyCanBeMapped()
    {
        $arguments = [
            'name' => 'Foo',
            'emailAddress' => 'foo@bar.org'
        ];
        $body = json_encode($arguments, JSON_PRETTY_PRINT);
        $this->browser->addAutomaticRequestHeader('Content-Type', 'application/json');
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/mappedrequestbody', 'POST', [], [], [], $body);

        $expectedResult = 'Foo-foo@bar.org';
        self::assertEquals($expectedResult, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function wholeRequestBodyCanBeMappedWithoutAnnotation()
    {
        $arguments = [
            'name' => 'Foo',
            'emailAddress' => 'foo@bar.org'
        ];
        $body = json_encode($arguments, JSON_PRETTY_PRINT);
        $this->browser->addAutomaticRequestHeader('Content-Type', 'application/json');
        $response = $this->browser->request('http://localhost/test/mvc/actioncontrollertestb/mappedrequestbodywithoutannotation', 'POST', [], [], [], $body);

        $expectedResult = 'Foo-foo@bar.org';
        self::assertEquals($expectedResult, $response->getBody()->getContents());
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

        $request = $this->serverRequestFactory->createServerRequest('POST', new Uri('http://localhost/test/mvc/actioncontrollertestc/' . $identifier . '/update'))
            ->withParsedBody($form);

        $response = $this->browser->sendRequest($request);
        self::assertSame('Entity "Foo" updated', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function flashMessagesGetRenderedAfterRedirect()
    {
        $request = $this->serverRequestFactory->createServerRequest('GET', new Uri('http://localhost/test/mvc/actioncontrollertest/redirectWithFlashMessage'));
        $response = $this->browser->sendRequest($request);

        $sessionCookies = array_map(static function ($cookie) {
            return Cookie::createFromRawSetCookieHeader($cookie);
        }, $response->getHeader('Set-Cookie'));
        self::assertNotEmpty($sessionCookies);

        $redirect = $response->getHeaderLine('Location');
        self::assertNotEmpty($redirect);

        $this->objectManager->forgetInstance(StandardController::class);

        $cookies = array_reduce($sessionCookies, static function ($out, $cookie) {
            $out[$cookie->getName()] = $cookie->getValue();
            return $out;
        }, []);
        $redirectRequest = $this->serverRequestFactory->createServerRequest('GET', new Uri($redirect))
            ->withCookieParams($cookies);
        $redirectResponse = $this->browser->sendRequest($redirectRequest);

        $expected = json_encode(['Redirect FlashMessage']);
        self::assertSame($expected, $redirectResponse->getBody()->getContents());
    }
}
