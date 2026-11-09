<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassA;

/**
 * A class using pure intersection types (as opposed to intersection types being part of a DNF type)
 * for a property, a parameter and a return type.
 */
class ClassWithIntersectionTypes
{
    /* Make sure that this class is proxied, so we can test the proxy compiler */
    #[Flow\Inject]
    protected SingletonClassA $singletonA;

    /* Pure intersection types cannot be nullable, therefore this property stays uninitialized */
    protected IdentifiableInterface&NameableInterface $subject;

    public function setSubject(IdentifiableInterface&NameableInterface $subject): void
    {
        $this->subject = $subject;
    }

    public function getSubject(): IdentifiableInterface&NameableInterface
    {
        return $this->subject;
    }

    public function describe(IdentifiableInterface&NameableInterface $subject): string
    {
        return $subject->getIdentifier() . ': ' . $subject->getName();
    }

    public function getSingletonA(): SingletonClassA
    {
        return $this->singletonA;
    }
}
