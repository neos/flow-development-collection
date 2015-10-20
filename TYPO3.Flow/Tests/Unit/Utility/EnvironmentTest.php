<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Core\ApplicationContext;

/**
 * Testcase for the Utility Environment class
 */
class EnvironmentTest extends \TYPO3\Flow\Tests\UnitTestCase
{

    /**
     * @test
     */
    public function getMaximumPathLengthReturnsCorrectValue()
    {
        $environment = new \TYPO3\Flow\Utility\Environment(new ApplicationContext('Testing'));
        $expectedValue = PHP_MAXPATHLEN;
        if ((integer)$expectedValue <= 0) {
            $this->fail('The PHP Constant PHP_MAXPATHLEN is not available on your system! Please file a PHP bug report.');
        }
        $this->assertEquals($expectedValue, $environment->getMaximumPathLength());
    }
}
