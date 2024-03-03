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

use Neos\Flow\Tests\FunctionalTestCase;

class AbstractControllerTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * Additional setup: Routes
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->registerRoute(
            'AbstractControllerTest Route 1',
            'test/mvc/abstractcontrollertesta/{@action}',
            [
                '@package' => 'Neos.Flow',
                '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
                '@controller' => 'AbstractControllerTestA',
                '@format' =>'html'
            ],
            true
        );
    }

    /**
     * Checks if a request is forwarded to the second action.
     *
     * @test
     */
    public function forwardPassesRequestToActionWithoutArguments()
    {
        $response = $this->browser->request('http://localhost/test/mvc/abstractcontrollertesta/forward?actionName=second');
        self::assertEquals('Second action was called', $response->getBody()->getContents());
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
        self::assertEquals('thirdAction-foo-bar--default', $response->getBody()->getContents());
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
        self::assertEquals('fourthAction-First-42-Neos\Error\Messages\Message', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function responseContainsNegotiatedContentType()
    {
        $response = $this->browser->request('http://localhost/test/mvc/abstractcontrollertesta/second');
        self::assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
    }
}
