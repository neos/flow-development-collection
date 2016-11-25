<?php
namespace Neos\Flow\Tests\Functional\Security\Fixtures;

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
 * An entity for tests
 *
 * @Flow\Entity
 */
class TestEntityA
{
    /**
     * @var TestEntityB
     * @ORM\OneToOne(inversedBy="relatedEntityA")
     */
    protected $relatedEntityB;

    /**
     * Constructor
     *
     * @param TestEntityB $relatedEntityB
     */
    public function __construct($relatedEntityB)
    {
        $this->relatedEntityB = $relatedEntityB;
    }

    /**
     * @param TestEntityB $relatedEntityB
     */
    public function setRelatedEntityB($relatedEntityB)
    {
        $this->relatedEntityB = $relatedEntityB;
    }

    /**
     * @return TestEntityB
     */
    public function getRelatedEntityB()
    {
        return $this->relatedEntityB;
    }
}
