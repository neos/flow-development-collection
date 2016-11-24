<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * A simple value object for persistence tests
 *
 * @Flow\ValueObject
 * @ORM\Table(name="persistence_testvalueobjectwithdatetimeproperty")
 */
class TestValueObjectWithDateTimeProperty
{
    /**
     * @var \DateTime
     */
    protected $value1;

    /**
     * @param \DateTime $value1
     */
    public function __construct($value1)
    {
        $this->value1 = $value1;
    }
}
