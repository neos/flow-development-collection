<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement;

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
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for Issue #3493: Classes with entity properties should get serialization code
 */
class ProxySerializationTest extends FunctionalTestCase
{
    protected static $testablePersistenceEnabled = true;

    #[Test]
    public function classWithEntityPropertyCanBeSerialized(): void
    {
        $entity = new Fixtures\SimpleEntity('Test Entity');
        $object = new Fixtures\ClassWithEntityProperty($entity, 'some value');

        // This should not fail - the proxy should have __sleep() method
        // Before the fix, this would fail because entity references would not be stripped
        $serialized = serialize($object);
        $unserialized = unserialize($serialized);

        self::assertInstanceOf(Fixtures\ClassWithEntityProperty::class, $unserialized);
        self::assertEquals('some value', $unserialized->someValue);
    }
}
