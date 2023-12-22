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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;

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
    public function httpRequestIsConvertedToAnActionRequestAndDispatchedToTheRespectiveController(): void
    {
        $foundRoute = false;
        if ($this->routes !== null) {
            foreach ($this->routes as $route) {
                if ($route->getName() === 'Neos.Flow :: Functional Test: HTTP - FooController') {
                    $foundRoute = true;
                }
            }
        }
        if (!$foundRoute) {
            self::markTestSkipped('In this distribution the Flow routes are not included into the global configuration.');
        }

        $_SERVER = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/neos/flow/test/http/foo',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'REQUEST_TIME' => $_SERVER['REQUEST_TIME'] ?? null,
            'REQUEST_TIME_FLOAT' => $_SERVER['REQUEST_TIME_FLOAT'] ?? null,
        ];

        /** @var MockObject|RequestHandler $requestHandler */
        $requestHandler = $this->getAccessibleMock(RequestHandler::class, ['boot', 'sendResponse'], [self::$bootstrap]);
        $requestHandler->exit = static function () {
        };
        // Custom sendResponse to avoid sending headers in test
        $requestHandler->method('sendResponse')->willReturnCallback(static function (ResponseInterface $response) {
            $body = $response->getBody()->detach() ?: $response->getBody()->getContents();
            if (is_resource($body)) {
                fpassthru($body);
                fclose($body);
            } else {
                echo $body;
            }
        });
        $requestHandler->handleRequest();

        $this->expectOutputString('FooController responded');
    }
}
