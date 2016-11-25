<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

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
 * A class that is declared singleton from Objects.yaml with constructor injection
 */
class SingletonClassG
{
    /**
     * @var PrototypeClassAishInterface
     */
    public $prototypeA;

    /**
     * @param PrototypeClassAishInterface $prototypeA
     */
    public function __construct(PrototypeClassAishInterface $prototypeA)
    {
        $this->prototypeA = $prototypeA;
    }
}
