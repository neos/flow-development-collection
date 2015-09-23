<?php
namespace TYPO3\Flow\Tests\Unit\Mvc;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
