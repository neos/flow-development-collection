<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Utility;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Flow\Utility\Now;
use PHPUnit\Framework\Attributes\Test;

/**
 * Functional test for the Now class
 */
final class NowTest extends FunctionalTestCase
{
    #[Test]
    public function nowReturnsAUniqueTimestamp()
    {
        $now = $this->objectManager->get(Now::class);
        $alsoNow = $this->objectManager->get(Now::class);
        self::assertSame($now->getTimeStamp(), $alsoNow->getTimeStamp());
    }
}
