<?php

namespace Neos\Flow\Tests\Unit\ObjectManagement\Fixture;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\Aspect\PersistenceMagicInterface;

/**
 * A fixture class which acts like a persisted entity, used for testing the
 * ObjectSerializationTrait and the RelatedEntitiesContainer.
 */
class SomeEntity implements PersistenceMagicInterface
{
    public function __construct(public string $title = 'some title')
    {
    }
}
