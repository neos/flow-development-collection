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
 * @ORM\Table(name="persistence_testvalueobjectwithtransientproperties")
 */
class TestValueObjectWithTransientProperties
{
    /**
     * @var string
     */
    protected $value1;

    /**
     * @Flow\Transient
     * @var string
     */
    protected $value2;

    /**
     * @Flow\Inject
     * @var TestEntityRepository
     */
    protected $dependency;

    /**
     * @var int
     */
    protected $calculatedValue;

    /**
     * @param string $value1
     * @param string $value2
     */
    public function __construct($value1, $value2)
    {
        $this->value1 = trim($value1);
        $this->value2 = trim($value2);

        if (strlen($this->value1) > 5) {
            $this->calculatedValue = 100;
        } else {
            $this->calculatedValue = 50;
        }
    }
}
