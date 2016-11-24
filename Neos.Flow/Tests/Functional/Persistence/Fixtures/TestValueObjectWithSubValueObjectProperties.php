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
 * @ORM\Table(name="persistence_testvalueobjectwithsubvalueobjectproperties")
 */
class TestValueObjectWithSubValueObjectProperties
{
    /**
     * @var TestValueObject
     */
    protected $value1;

    /**
     * @var string
     */
    protected $value2;

    /**
     * @param TestValueObject $value1
     * @param string $value2
     */
    public function __construct(TestValueObject $value1, $value2)
    {
        $this->value1 = $value1;
        $this->value2 = trim($value2);
    }
}
