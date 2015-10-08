<?php
namespace TYPO3\Flow\Tests\Functional\Http;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Functional tests for the HTTP Request Handler
 */
class RequestHandlerTest extends \TYPO3\Flow\Tests\FunctionalTestCase
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

        $_SERVER = array(
            'HTTP_HOST' => 'localhost',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => '',
            'REQUEST_URI' => '/typo3/flow/test/http/foo',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
        );

        $requestHandler = $this->getAccessibleMock(\TYPO3\Flow\Http\RequestHandler::class, array('boot'), array(self::$bootstrap));
        $requestHandler->exit = function () {};
        $requestHandler->handleRequest();

        $this->expectOutputString('FooController responded');
    }
}
