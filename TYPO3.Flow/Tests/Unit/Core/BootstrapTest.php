<?php
namespace TYPO3\Flow\Tests\Unit\Core;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Tests\UnitTestCase;

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
            [['typo3.flow:core:shell', 'typo3.flow:cache:flush'], 'typo3.flow:core:shell', true],
            [['typo3.flow:core:shell', 'typo3.flow:cache:flush'], 'flow:core:shell', true],
            [['typo3.flow:core:shell', 'typo3.flow:cache:flush'], 'core:shell', false],
            [['typo3.flow:core:*', 'typo3.flow:cache:flush'], 'typo3.flow:core:shell', true],
            [['typo3.flow:core:*', 'typo3.flow:cache:flush'], 'flow:core:shell', true],
            [['typo3.flow:core:shell', 'typo3.flow:cache:flush'], 'typo3.flow:help:help', false],
            [['typo3.flow:core:*', 'typo3.flow:cache:*'], 'flow:cache:flush', true],
            [['typo3.flow:core:*', 'typo3.flow:cache:*'], 'flow5:core:shell', false],
            [['typo3.flow:core:*', 'typo3.flow:cache:*'], 'typo3:core:shell', false],
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
     * @expectedException \TYPO3\Flow\Exception
     */
    public function resolveRequestHandlerThrowsUsefulExceptionIfNoRequestHandlerFound()
    {
        $bootstrap = $this->getAccessibleMock(Bootstrap::class, ['dummy'], [], '', false);
        $bootstrap->_call('resolveRequestHandler');
    }
}
