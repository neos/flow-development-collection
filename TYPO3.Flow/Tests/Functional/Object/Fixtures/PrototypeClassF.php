<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
