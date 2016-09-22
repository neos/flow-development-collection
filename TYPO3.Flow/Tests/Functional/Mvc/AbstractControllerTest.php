<?php
namespace TYPO3\Flow\Tests\Functional\Mvc;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Mvc\Routing\Route;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the ActionController
 */
class AbstractControllerTest extends FunctionalTestCase
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

        $route = new Route();
        $route->setName('AbstractControllerTest Route 1');
        $route->setUriPattern('test/mvc/abstractcontrollertesta/{@action}');
        $route->setDefaults([
            '@package' => 'TYPO3.Flow',
            '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
            '@controller' => 'AbstractControllerTestA',
            '@format' =>'html'
        ]);
        $route->setAppendExceedingArguments(true);
        $this->router->addRoute($route);
    }

    /**
     * Checks if a request is forwarded to the second action.
     *
     * @test
     */
    public function forwardPassesRequestToActionWithoutArguments()
    {
        $response = $this->browser->request('http://localhost/test/mvc/abstractcontrollertesta/forward?actionName=second');
        $this->assertEquals('Second action was called', $response->getContent());
    }

    /**
     * Checks if a request is forwarded to the second action and passes the givn
     * straight-value arguments.
     *
     * @test
     */
    public function forwardPassesRequestToActionWithArguments()
    {
        $response = $this->browser->request('http://localhost/test/mvc/abstractcontrollertesta/forward?actionName=third&arguments[firstArgument]=foo&arguments[secondArgument]=bar');
        $this->assertEquals('thirdAction-foo-bar--default', $response->getContent());
    }

    /**
     * Checks if a request is forwarded to the second action and passes the givn
     * straight-value arguments.
     *
     * @test
     */
    public function forwardPassesRequestToActionWithInternalArgumentsContainingObjects()
    {
        $response = $this->browser->request('http://localhost/test/mvc/abstractcontrollertesta/forward?actionName=fourth&passSomeObjectArguments=1&arguments[nonObject1]=First&arguments[nonObject2]=42');
        $this->assertEquals('fourthAction-First-42-TYPO3\Flow\Error\Message', $response->getContent());
    }
}
