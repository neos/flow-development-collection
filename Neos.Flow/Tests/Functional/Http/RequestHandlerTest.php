<?php
namespace Neos\Flow\Tests\Functional\Http;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\RequestHandler;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the HTTP Request Handler
 */
class RequestHandlerTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @test
     */
    public function httpRequestIsConvertedToAnActionRequestAndDispatchedToTheRespectiveController()
    {
        $foundRoute = false;
        foreach ($this->router->getRoutes() as $route) {
            if ($route->getName() === 'Flow :: Functional Test: HTTP - FooController') {
                $foundRoute = true;
            }
        }

        if (!$foundRoute) {
            $this->markTestSkipped('In this distribution the Flow routes are not included into the global configuration.');
            return;
        }

        $_SERVER = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/typo3/flow/test/http/foo',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
        ];

        $requestHandler = $this->getAccessibleMock(RequestHandler::class, ['boot'], [self::$bootstrap]);
        $requestHandler->exit = function () {
        };
        $requestHandler->handleRequest();

        $this->expectOutputString('FooController responded');
    }
}
