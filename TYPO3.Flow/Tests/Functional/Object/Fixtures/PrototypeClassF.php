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
class PrototypeClassF
{
    /**
     * @Flow\Transient
     * @var string
     */
    protected $transientProperty;

    /**
     * @var string
     */
    protected $nonTransientProperty;

    /**
     * @param string $transientProperty
     */
    public function setTransientProperty($transientProperty)
    {
        $this->transientProperty = $transientProperty;
    }

    /**
     * @return string
     */
    public function getTransientProperty()
    {
        return $this->transientProperty;
    }

    /**
     * @param string $nonTransientProperty
     */
    public function setNonTransientProperty($nonTransientProperty)
    {
        $this->nonTransientProperty = $nonTransientProperty;
    }

    /**
     * @return string
     */
    public function getNonTransientProperty()
    {
        return $this->nonTransientProperty;
    }
}
