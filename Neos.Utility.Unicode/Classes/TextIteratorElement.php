<?php
namespace Neos\Utility\Unicode;

/*
 * This file is part of the Neos.Utility.Unicode package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A UTF8-aware TextIterator
 *
 */
class TextIteratorElement
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var integer
     */
    private $offset;

    /**
     * @var integer
     */
    private $length;

    /**
     * @var boolean
     */
    private $boundary;

    /**
     * Constructor
     *
     * @param string $value The value of the element
     * @param integer $offset The offset in the original string
     * @param integer $length
     * @param boolean $boundary
     */
    public function __construct($value, $offset, $length = 0, $boundary = false)
    {
        $this->value = $value;
        $this->offset = $offset;
        $this->length = $length;
        $this->boundary = $boundary;
    }

    /**
     * Returns the element's value
     *
     * @return string	The element's value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the element's offset
     *
     * @return int		The element's offset
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Returns the element's length
     *
     * @return int		The element's length
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Returns TRUE for a boundary element
     *
     * @return boolean		TRUE for boundary elements
     */
    public function isBoundary()
    {
        return $this->boundary;
    }
}
