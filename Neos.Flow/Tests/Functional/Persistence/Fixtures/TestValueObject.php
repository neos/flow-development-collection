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
 * @ORM\Table(name="persistence_testvalueobject")
 */
class TestValueObject
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @param string $value The string value of this value object
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
