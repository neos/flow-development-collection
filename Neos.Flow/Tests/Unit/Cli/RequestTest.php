<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the CLI Request class
 */
final class RequestTest extends UnitTestCase
{
    #[Test]
    public function getCommandReturnsTheCommandObjectReflectingTheRequestInformation()
    {
        $request = new Request();
        $request->setControllerObjectName(CacheCommandController::class);
        $request->setControllerCommandName('flush');

        $command = $request->getCommand();
        self::assertSame('neos.flow:cache:flush', $command->getCommandIdentifier());
    }

    #[Test]
    public function setControllerObjectNameAndSetControllerCommandNameUnsetTheBuiltCommandObject()
    {
        $request = new Request();
        $request->setControllerObjectName(CacheCommandController::class);
        $request->setControllerCommandName('flush');
        $request->getCommand();

        $request->setControllerObjectName('Neos\Flow\Command\BeerCommandController');
        $request->setControllerCommandName('drink');

        $command = $request->getCommand();
        self::assertSame('neos.flow:beer:drink', $command->getCommandIdentifier());
    }
}
