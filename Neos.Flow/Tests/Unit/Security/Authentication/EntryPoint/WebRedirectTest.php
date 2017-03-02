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

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Security\Authentication\EntryPoint\WebRedirect;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for web redirect authentication entry point
 */
class WebRedirectTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\MissingConfigurationException
     */
    public function startAuthenticationThrowsAnExceptionIfTheConfigurationOptionsAreMissing()
    {
        $request = Request::create(new Uri('http://robertlemke.com/admin'));
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
        $request = Request::create(new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $entryPoint->setOptions(['uri' => 'some/page']);

        $entryPoint->startAuthentication($request, $response);

        $this->assertEquals('303', substr($response->getStatus(), 0, 3));
        $this->assertEquals('http://robertlemke.com/some/page', $response->getHeader('Location'));
    }

    /**
     * @test
     */
    public function startAuthenticationDoesNotPrefixAConfiguredUriIfItsAbsolute()
    {
        $request = Request::create(new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $entryPoint->setOptions(['uri' => 'http://some.abs/olute/url']);

        $entryPoint->startAuthentication($request, $response);

        $this->assertEquals('http://some.abs/olute/url', $response->getHeader('Location'));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\MissingConfigurationException
     */
    public function startAuthenticationThrowsAnExceptionIfTheConfiguredRoutePartsAreInvalid()
    {
        $request = Request::create(new Uri('http://robertlemke.com/admin'));
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
        $request = Request::create(new Uri('http://robertlemke.com/admin'));
        $response = new Response();

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

        $mockUriBuilder = $this->createMock(UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(true)->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('uriFor')->with('someAction', ['otherArguments' => ['foo' => 'bar'], '@format' => 'someFormat'], 'SomeController', 'SomePackage', 'SomeSubPackage')->will($this->returnValue('http://resolved/redirect/uri'));
        $entryPoint->_set('uriBuilder', $mockUriBuilder);

        $entryPoint->startAuthentication($request, $response);

        $this->assertEquals('303', substr($response->getStatus(), 0, 3));
        $this->assertEquals('http://resolved/redirect/uri', $response->getHeader('Location'));
    }
}
