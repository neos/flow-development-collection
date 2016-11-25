<?php
namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

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

/**
 * A target class for testing introductions. Set as being an entity to check if
 * property introductions are correctly picked up by the persistence.
 *
 * @Flow\Entity
 */
class TargetClass04
{
}
