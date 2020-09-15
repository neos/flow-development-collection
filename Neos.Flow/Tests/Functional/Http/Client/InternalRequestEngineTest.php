<?php
namespace Neos\Flow\Tests\Functional\Http\Client;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the HTTP client internal request engine
 */
class InternalRequestEngineTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $route = new Route();
        $route->setName('Functional Test - Http::Client::InternalRequestEngine');
        $route->setUriPattern('test/security/restricted');
        $route->setDefaults([
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Security\Fixtures',
            '@controller' => 'Restricted',
            '@action' => 'admin',
            '@format' => 'html'
        ]);
        $this->router->addRoute($route);
    }

    /**
     * Make sure that the security context tokens are initialized,
     * making sure that the tokens match the request pattern of the request.
     *
     * Bug #37377
     *
     * @test
     */
    public function securityContextContainsTokens()
    {
        $response = $this->browser->request('http://localhost/test/security/restricted');
        $this->assertEquals('1222268609', $response->getHeader('X-Flow-ExceptionCode'));
    }
}
