<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A class of scope prototype (but without explicit scope annotation)
 */
class PrototypeClassD
{
    /**
     * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB
     */
    protected $objectB;

    /**
     * @var integer
     */
    public $injectionRuns = 0;

    /**
     * @var boolean
     */
    public $injectedPropertyWasUnavailable = false;

    /**
     * @param \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB $objectB
     * @return void
     */
    public function injectObjectB(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB $objectB)
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
