<?php
namespace TYPO3\Flow\Tests\Unit\Cli;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cli\Request;

/**
 * Testcase for the CLI Request class
 */
class RequestTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getCommandReturnsTheCommandObjectReflectingTheRequestInformation()
    {
        $request = new Request();
        $request->setControllerObjectName('TYPO3\Flow\Command\CacheCommandController');
        $request->setControllerCommandName('flush');

        $command = $request->getCommand();
        $this->assertEquals('typo3.flow:cache:flush', $command->getCommandIdentifier());
    }

    /**
     * @test
     */
    public function setControllerObjectNameAndSetControllerCommandNameUnsetTheBuiltCommandObject()
    {
        $request = new Request();
        $request->setControllerObjectName('TYPO3\Flow\Command\CacheCommandController');
        $request->setControllerCommandName('flush');
        $request->getCommand();

        $request->setControllerObjectName('TYPO3\Flow\Command\BeerCommandController');
        $request->setControllerCommandName('drink');

        $command = $request->getCommand();
        $this->assertEquals('typo3.flow:beer:drink', $command->getCommandIdentifier());
    }
}
