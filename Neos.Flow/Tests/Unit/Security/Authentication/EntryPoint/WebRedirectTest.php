<?php

declare(strict_types=1);

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
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Security\Authentication\EntryPoint\WebRedirect;
use Neos\Flow\Security\Exception\MissingConfigurationException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for web redirect authentication entry point
 */
final class WebRedirectTest extends UnitTestCase
{
    #[Test]
    public function startAuthenticationThrowsAnExceptionIfTheConfigurationOptionsAreMissing()
    {
        $this->expectException(MissingConfigurationException::class);
        $request = new ServerRequest('GET', new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $entryPoint->setOptions(['something' => 'irrelevant']);

        $entryPoint->startAuthentication($request, $response);
    }

    #[Test]
    public function startAuthenticationSetsTheCorrectValuesInTheResponseObjectIfUriIsSpecified()
    {
        $baseUriProviderMock = $this->createMock(BaseUriProvider::class);
        $baseUriProviderMock->method('getConfiguredBaseUriOrFallbackToCurrentRequest')->willReturn(new Uri('http://robertlemke.com/'));

        $request = new ServerRequest('GET', new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $this->inject($entryPoint, 'baseUriProvider', $baseUriProviderMock);
        $entryPoint->setOptions(['uri' => 'some/page']);

        $response = $entryPoint->startAuthentication($request, $response);

        self::assertEquals(303, $response->getStatusCode());
        self::assertEquals('http://robertlemke.com/some/page', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function startAuthenticationDoesNotPrefixAConfiguredUriIfItsAbsolute()
    {
        $request = new ServerRequest('GET', new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $entryPoint->setOptions(['uri' => 'http://some.abs/olute/url']);

        $response = $entryPoint->startAuthentication($request, $response);

        self::assertEquals('http://some.abs/olute/url', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function startAuthenticationThrowsAnExceptionIfTheConfiguredRoutePartsAreInvalid()
    {
        $this->expectException(MissingConfigurationException::class);
        $request = new ServerRequest('GET', new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $entryPoint->setOptions(['routeValues' => 'this/is/invalid']);
        $entryPoint->startAuthentication($request, $response);
    }

    #[Test]
    public function startAuthenticationSetsTheCorrectValuesInTheResponseObjectIfRouteValuesAreSpecified()
    {
        $request = new ServerRequest('GET', new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = $this->getAccessibleMock(WebRedirect::class, []);
        $routeValues = [
            '@package' => 'SomePackage',
            '@subpackage' => 'SomeSubPackage',
            '@controller' => 'SomeController',
            '@action' => 'someAction',
            '@format' => 'someFormat',
            'otherArguments' => ['foo' => 'bar']
        ];
        $entryPoint->setOptions(['routeValues' => $routeValues]);

        $mockUriBuilder = $this->createMock(UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(true)->willReturn(($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('uriFor')->with('someAction', ['otherArguments' => ['foo' => 'bar'], '@format' => 'someFormat'], 'SomeController', 'SomePackage', 'SomeSubPackage')->willReturn(('http://resolved/redirect/uri'));
        $entryPoint->_set('uriBuilder', $mockUriBuilder);

        $response = $entryPoint->startAuthentication($request, $response);

        self::assertEquals('303', $response->getStatusCode());
        self::assertEquals('http://resolved/redirect/uri', $response->getHeaderLine('Location'));
    }
}
