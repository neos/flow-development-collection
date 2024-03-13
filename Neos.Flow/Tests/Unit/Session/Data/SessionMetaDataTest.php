<?php
namespace Neos\Flow\Tests\Unit\Session\Data;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Session\Data\SessionIdentifier;
use Neos\Flow\Session\Data\SessionMetaData;
use Neos\Flow\Session\Data\StorageIdentifier;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Unit tests for the Flow SessionDataStore implementation
 */
class SessionMetaDataTest extends UnitTestCase
{
    public function isSameDataProvider(): \Generator
    {
        yield "same" => [
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 123, ['baz']),
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 123, ['baz']),
            true
        ];

        yield "different time" => [
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 123, ['baz']),
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 645645876, ['baz']),
            true
        ];

        yield "different session id" => [
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 123, ['baz']),
            new SessionMetaData(SessionIdentifier::createFromString('!foo'), StorageIdentifier::createFromString('bar'), 123, ['baz']),
            false
        ];

        yield "different storage id" => [
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 123, ['baz']),
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('!bar'), 123, ['baz']),
            false
        ];

        yield "different tags 1" => [
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 123, ['baz']),
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 123, ['baz', 'bam']),
            false
        ];

        yield "different tags 2" => [
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 123, ['baz', 'bam']),
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 123, ['baz']),
            false
        ];

        yield "different tags 3" => [
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 123, ['baz']),
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 123, ['bam']),
            false
        ];
    }

    /**
     * @test
     * @dataProvider isSameDataProvider
     */
    public function isSameWorks(SessionMetaData $a, SessionMetaData $b, bool $expectSame): void
    {
        $this->assertEquals($expectSame, $a->isSame($b));
    }


    public function ageDifferenceDataProvider(): \Generator
    {
        yield "same" => [
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 999, ['baz']),
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 333, ['baz']),
            666
        ];

        yield "different" => [
            new SessionMetaData(SessionIdentifier::createFromString('foo'), StorageIdentifier::createFromString('bar'), 999, ['baz']),
            new SessionMetaData(SessionIdentifier::createFromString('!foo'), StorageIdentifier::createFromString('!bar'), 333, ['!baz']),
            666
        ];
    }

    /**
     * @test
     * @dataProvider ageDifferenceDataProvider
     */
    public function ageDifferenceWorks(SessionMetaData $a, SessionMetaData $b, int $expectedAgeDifference): void
    {
        $this->assertEquals($expectedAgeDifference, $a->ageDifference($b));
    }
}
