<?php
namespace Neos\Flow\Tests\Unit\Security\Authentication\EntryPoint;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\Routing\ActionUriBuilderFactory;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Neos\Flow\Security\Authentication\EntryPoint\WebRedirect;
use Neos\Flow\Security\Exception\MissingConfigurationException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for web redirect authentication entry point
 */
class WebRedirectTest extends UnitTestCase
{
    /**
     * @test
     */
    public function startAuthenticationThrowsAnExceptionIfTheConfigurationOptionsAreMissing()
    {
        $this->expectException(MissingConfigurationException::class);
        $request = new ServerRequest('GET', new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $entryPoint->setOptions(['something' => 'irrelevant']);

        $entryPoint->startAuthentication($request, $response);
    }

    /**
     * @test
     */
    public function startAuthenticationSetsTheCorrectValuesInTheResponseObjectIfUriIsSpecified()
    {
        $request = new ServerRequest('GET', new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $entryPoint->setOptions(['uri' => 'some/page']);

        $response = $entryPoint->startAuthentication($request, $response);

        self::assertEquals(303, substr($response->getStatusCode(), 0, 3));
        self::assertEquals('http://robertlemke.com/some/page', $response->getHeaderLine('Location'));
    }

    /**
     * @test
     */
    public function startAuthenticationDoesNotPrefixAConfiguredUriIfItsAbsolute()
    {
        $request = new ServerRequest('GET', new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $entryPoint->setOptions(['uri' => 'http://some.abs/olute/url']);

        $response = $entryPoint->startAuthentication($request, $response);

        self::assertEquals('http://some.abs/olute/url', $response->getHeaderLine('Location'));
    }

    /**
     * @test
     */
    public function startAuthenticationThrowsAnExceptionIfTheConfiguredRoutePartsAreInvalid()
    {
        $this->expectException(MissingConfigurationException::class);
        $request = new ServerRequest('GET', new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $entryPoint->setOptions(['routeValues' => 'this/is/invalid']);
        $entryPoint->startAuthentication($request, $response);
    }

    /**
     * @test
     */
    public function startAuthenticationSetsTheCorrectValuesInTheResponseObjectIfRouteValuesAreSpecified()
    {
        $request = new ServerRequest('GET', new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        /** @var WebRedirect|MockObject $entryPoint */
        $entryPoint = $this->getAccessibleMock(WebRedirect::class, ['dummy']);
        $routeValues = [
            '@package' => 'SomePackage',
            '@subpackage' => 'SomeSubPackage',
            '@controller' => 'SomeController',
            '@action' => 'someAction',
            '@format' => 'someFormat',
            'otherArguments' => ['foo' => 'bar']
        ];
        $entryPoint->setOptions(['routeValues' => $routeValues]);

        /** @var RouterInterface|MockObject $mockRouter */
        $mockRouter = $this->getMockBuilder(RouterInterface::class)->getMock();

        $mockRouter->expects(self::atLeastOnce())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) {
            $expectedRuteValues = [
                '@package' => 'somepackage',
                '@subpackage' => 'SomeSubPackage',
                '@controller' => 'somecontroller',
                '@action' => 'someaction',
                '@format' => 'someFormat',
                'otherArguments' => ['foo' => 'bar']
            ];
            self::assertEquals($expectedRuteValues, $resolveContext->getRouteValues());
            return new Uri('http://resolved/redirect/uri');
        });

        $mockActionUriBuilderFactory = new ActionUriBuilderFactory($mockRouter);
        $this->inject($entryPoint, 'actionUriBuilderFactory', $mockActionUriBuilderFactory);

        $response = $entryPoint->startAuthentication($request, $response);

        self::assertEquals('303', substr($response->getStatusCode(), 0, 3));
        self::assertEquals('http://resolved/redirect/uri', $response->getHeaderLine('Location'));
    }
}
