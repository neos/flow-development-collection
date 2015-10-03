<?php
namespace TYPO3\Flow\Tests\Unit\Mvc;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the MVC Generic Response
 *
 */
class ResponseTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function toStringReturnsContentOfResponse()
    {
        $response = new \TYPO3\Flow\Mvc\Response();
        $response->setContent('SomeContent');

        $expected = 'SomeContent';
        $actual = $response->__toString();
        $this->assertEquals($expected, $actual);
    }
}
