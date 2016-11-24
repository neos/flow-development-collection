<?php
namespace Neos\Flow\Tests\Functional\Reflection\Fixtures\Model;

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
 * Just a Plain Old PHP Object as non-abstract base class for the Aggregate Root "EntityExtendingPlainObject"
 */
class NonEntity
{
    /**
     * @var string
     */
    protected $somePropertyOfTheBaseClass;
}
