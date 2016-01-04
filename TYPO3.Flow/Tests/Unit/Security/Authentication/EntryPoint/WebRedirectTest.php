<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authentication\EntryPoint;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Security\Authentication\EntryPoint\WebRedirect;

/**
 * Testcase for web redirect authentication entry point
 */
class WebRedirectTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\MissingConfigurationException
     */
    public function startAuthenticationThrowsAnExceptionIfTheConfigurationOptionsAreMissing()
    {
        $request = Request::create(new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $entryPoint->setOptions(array('something' => 'irrelevant'));

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
        $entryPoint->setOptions(array('uri' => 'some/page'));

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
        $entryPoint->setOptions(array('uri' => 'http://some.abs/olute/url'));

        $entryPoint->startAuthentication($request, $response);

        $this->assertEquals('http://some.abs/olute/url', $response->getHeader('Location'));
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\MissingConfigurationException
     */
    public function startAuthenticationThrowsAnExceptionIfTheConfiguredRoutePartsAreInvalid()
    {
        $request = Request::create(new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = new WebRedirect();
        $entryPoint->setOptions(array('routeValues' => 'this/is/invalid'));
        $entryPoint->startAuthentication($request, $response);
    }

    /**
     * @test
     */
    public function startAuthenticationSetsTheCorrectValuesInTheResponseObjectIfRouteValuesAreSpecified()
    {
        $request = Request::create(new Uri('http://robertlemke.com/admin'));
        $response = new Response();

        $entryPoint = $this->getAccessibleMock(\TYPO3\Flow\Security\Authentication\EntryPoint\WebRedirect::class, array('dummy'));
        $routeValues = array(
            '@package' => 'SomePackage',
            '@subpackage' => 'SomeSubPackage',
            '@controller' => 'SomeController',
            '@action' => 'someAction',
            '@format' => 'someFormat',
            'otherArguments' => array('foo' => 'bar')
        );
        $entryPoint->setOptions(array('routeValues' => $routeValues));

        $mockUriBuilder = $this->getMock(\TYPO3\Flow\Mvc\Routing\UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(true)->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('uriFor')->with('someAction', array('otherArguments' => array('foo' => 'bar'), '@format' => 'someFormat'), 'SomeController', 'SomePackage', 'SomeSubPackage')->will($this->returnValue('http://resolved/redirect/uri'));
        $entryPoint->_set('uriBuilder', $mockUriBuilder);

        $entryPoint->startAuthentication($request, $response);

        $this->assertEquals('303', substr($response->getStatus(), 0, 3));
        $this->assertEquals('http://resolved/redirect/uri', $response->getHeader('Location'));
    }
}
