<?php
namespace Neos\Flow\Tests\Unit\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Response;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC Generic Response
 */
class ResponseTest extends UnitTestCase
{
    /**
     * @test
     */
    public function toStringReturnsContentOfResponse()
    {
        $response = new Response();
        $response->setContent('SomeContent');

        $expected = 'SomeContent';
        $actual = $response->__toString();
        $this->assertEquals($expected, $actual);
    }
}
