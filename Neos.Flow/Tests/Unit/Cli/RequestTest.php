<?php
namespace Neos\Flow\Tests\Unit\Cli;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cli\Request;
use Neos\Flow\Command\CacheCommandController;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the CLI Request class
 */
class RequestTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getCommandReturnsTheCommandObjectReflectingTheRequestInformation()
    {
        $request = new Request();
        $request->setControllerObjectName(CacheCommandController::class);
        $request->setControllerCommandName('flush');

        $command = $request->getCommand();
        $this->assertEquals('neos.flow:cache:flush', $command->getCommandIdentifier());
    }

    /**
     * @test
     */
    public function setControllerObjectNameAndSetControllerCommandNameUnsetTheBuiltCommandObject()
    {
        $request = new Request();
        $request->setControllerObjectName(CacheCommandController::class);
        $request->setControllerCommandName('flush');
        $request->getCommand();

        $request->setControllerObjectName('Neos\Flow\Command\BeerCommandController');
        $request->setControllerCommandName('drink');

        $command = $request->getCommand();
        $this->assertEquals('neos.flow:beer:drink', $command->getCommandIdentifier());
    }
}
