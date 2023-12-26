<?php
namespace Neos\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\SimpleActionController;
use Neos\Flow\Mvc\Exception\NoSuchActionException;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Tests for
 * @see SimpleActionController
 */
class SimpleActionControllerTest extends UnitTestCase
{
    /**
     * Note: additional checks like "123" or "Foo" might seem sensible but those cases are prevented in the ActionRequest already.
     *
     * @return array[]
     */
    public function wrongActionNameDataProvider()
    {
        return [
            ['foo'],
            ['fooAction'],
            ['addTestContentAction']
        ];
    }

    /**
     * @test
     * @dataProvider wrongActionNameDataProvider
     */
    public function exceptionIfActionDoesNotExist(string $actionName)
    {
        $request = $this->createMock(ActionRequest::class);
        $request->method('getControllerActionName')->willReturn($actionName);

        $this->expectException(NoSuchActionException::class);

        $testObject = new Fixtures\SimpleActionTestController();
        $testObject->processRequest($request);
    }

    /**
     * @return void
     * @throws NoSuchActionException
     * @throws \Neos\Flow\SignalSlot\Exception\InvalidSlotException
     * @test
     */
    public function responseOnlyContainsWhatActionSets()
    {
        $request = $this->createMock(ActionRequest::class);
        $request->method('getControllerActionName')->willReturn('addTestContent');

        $testObject = new Fixtures\SimpleActionTestController();
        $response = $testObject->processRequest($request);
        self::assertInstanceOf(ActionResponse::class, $response);
        self::assertEquals('Simple', $response->getContent());
        // default
        self::assertEquals(200, $response->getStatusCode());
        self::assertFalse($response->hasContentType());

    }
}
