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
 * A model fixture which is used for testing the class schema building
 *
 * @Flow\Entity
 */
class SubSubEntity extends SubEntity
{
    /**
     * Just yet another normal string
     *
     * @var string
     */
    protected $yetAnotherString;
}
