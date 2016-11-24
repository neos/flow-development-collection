<?php
namespace Neos\Flow\Tests\Unit\Core;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Bootstrap class
 */
class BootstrapTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function commandIdentifiersAndCompiletimeControllerInfo()
    {
        return [
            [['neos.flow:core:shell', 'neos.flow:cache:flush'], 'neos.flow:core:shell', true],
            [['neos.flow:core:shell', 'neos.flow:cache:flush'], 'flow:core:shell', true],
            [['neos.flow:core:shell', 'neos.flow:cache:flush'], 'core:shell', false],
            [['neos.flow:core:*', 'neos.flow:cache:flush'], 'neos.flow:core:shell', true],
            [['neos.flow:core:*', 'neos.flow:cache:flush'], 'flow:core:shell', true],
            [['neos.flow:core:shell', 'neos.flow:cache:flush'], 'neos.flow:help:help', false],
            [['neos.flow:core:*', 'neos.flow:cache:*'], 'flow:cache:flush', true],
            [['neos.flow:core:*', 'neos.flow:cache:*'], 'flow5:core:shell', false],
            [['neos.flow:core:*', 'neos.flow:cache:*'], 'typo3:core:shell', false],
        ];
    }

    /**
     * @test
     * @dataProvider commandIdentifiersAndCompiletimeControllerInfo
     */
    public function isCompileTimeCommandControllerChecksIfTheGivenCommandIdentifierRefersToACompileTimeController($compiletimeCommandControllerIdentifiers, $givenCommandIdentifier, $expectedResult)
    {
        $bootstrap = new Bootstrap('Testing');
        foreach ($compiletimeCommandControllerIdentifiers as $compiletimeCommandControllerIdentifier) {
            $bootstrap->registerCompiletimeCommand($compiletimeCommandControllerIdentifier);
        }

        $this->assertSame($expectedResult, $bootstrap->isCompiletimeCommand($givenCommandIdentifier));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Exception
     */
    public function resolveRequestHandlerThrowsUsefulExceptionIfNoRequestHandlerFound()
    {
        $bootstrap = $this->getAccessibleMock(Bootstrap::class, ['dummy'], [], '', false);
        $bootstrap->_call('resolveRequestHandler');
    }
}
