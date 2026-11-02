<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Error;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Error\Messages\Error as FlowError;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Error object
 */
final class ErrorTest extends UnitTestCase
{
    #[Test]
    public function theConstructorSetsTheErrorMessageCorrectly()
    {
        $errorMessage = 'The message';
        $error = new FlowError($errorMessage, 0);

        self::assertSame($errorMessage, $error->getMessage());
    }

    #[Test]
    public function theConstructorSetsTheErrorCodeCorrectly()
    {
        $errorCode = 123456789;
        $error = new FlowError('', $errorCode);

        self::assertSame($errorCode, $error->getCode());
    }
}
