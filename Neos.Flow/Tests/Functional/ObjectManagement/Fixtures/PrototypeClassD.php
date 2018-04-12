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
 * A class of scope prototype (but without explicit scope annotation)
 */
class PrototypeClassD
{
    /**
     * @var SingletonClassB
     */
    protected $objectB;

    /**
     * Note: please leave this class name imported/simplified to cover the proper reflection of this tag.
     *
     * @var PrototypeClassE
     */
    protected $objectE;

    /**
     * @var integer
     */
    public $injectionRuns = 0;

    /**
     * @var boolean
     */
    public $injectedPropertyWasUnavailable = false;

    /**
     * @param SingletonClassB $objectB
     * @return void
     */
    public function injectObjectB(SingletonClassB $objectB)
    {
        $this->injectionRuns++;
        $this->objectB = $objectB;
    }

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     *
     */
    public function initializeObject()
    {
        if (!is_object($this->objectB)) {
            $this->injectedPropertyWasUnavailable = true;
        }
    }
}
