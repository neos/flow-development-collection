<?php
namespace TYPO3\Flow\Tests\Functional\Utility;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Functional test for the Now class
 */
class NowTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @test
     */
    public function nowReturnsAUniqueTimestamp()
    {
        $now = $this->objectManager->get(\TYPO3\Flow\Utility\Now::class);
        $alsoNow = $this->objectManager->get(\TYPO3\Flow\Utility\Now::class);
        $this->assertSame($now->getTimeStamp(), $alsoNow->getTimeStamp());
    }
}
