<?php
namespace Neos\Flow\Aop\Builder;

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
 * A filterable index of class names
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("prototype")
 */
class ClassNameIndex
{
    /**
     * Indexed array by class name
     * @var array
     */
    protected $classNames = [];

    /**
     * Constructor. Note: If you pass a data array here, make sure
     * to key sort it before!
     *
     * @param array $classNames Array with class names as keys
     */
    public function __construct(array $classNames = [])
    {
        $this->classNames = $classNames;
    }

    /**
     * Set the data of this index to the given class
     * names. Note: Make sure to sort the array before!
     *
     * @param array $classNames
     * @return void
     */
    public function setClassNames(array $classNames)
    {
        $this->classNames = count($classNames) > 0 ? array_combine($classNames, array_fill(0, count($classNames), true)) : [];
    }

    /**
     * Returns the class names contained in this index
     *
     * @return array An array of class names contained in this index
     */
    public function getClassNames()
    {
        return array_keys($this->classNames);
    }

    /**
     * Checks, if a class name is contained in this index
     *
     * @param string $className The class name to check for
     * @return boolean TRUE, if the given class name is contained in this index
     */
    public function hasClassName($className)
    {
        return isset($this->classNames[$className]);
    }

    /**
     * Returns a new index object with all class names contained in this and
     * the given index
     *
     * @param \Neos\Flow\Aop\Builder\ClassNameIndex $classNameIndex
     * @return \Neos\Flow\Aop\Builder\ClassNameIndex A new index object
     */
    public function intersect(ClassNameIndex $classNameIndex)
    {
        return new ClassNameIndex(array_intersect_key($this->classNames, $classNameIndex->classNames));
    }

    /**
     * Sets this index to all class names which are present currently and
     * contained in the given index
     *
     * @param \Neos\Flow\Aop\Builder\ClassNameIndex $classNameIndex
     * @return void
     */
    public function applyIntersect(ClassNameIndex $classNameIndex)
    {
        $this->classNames = array_intersect_key($this->classNames, $classNameIndex->classNames);
    }

    /**
     * Returns a new index object containing all class names of
     * this index and the given one
     *
     * @param \Neos\Flow\Aop\Builder\ClassNameIndex $classNameIndex
     * @return \Neos\Flow\Aop\Builder\ClassNameIndex A new index object
     */
    public function union(ClassNameIndex $classNameIndex)
    {
        $result = clone $classNameIndex;
        $result->applyUnion($this);
        return $result;
    }

    /**
     * Sets this index to all class names which are either already present or are
     * contained in the given index
     *
     * @param \Neos\Flow\Aop\Builder\ClassNameIndex $classNameIndex
     * @return void
     */
    public function applyUnion(ClassNameIndex $classNameIndex)
    {
        if (count($this->classNames) > count($classNameIndex->classNames)) {
            foreach ($classNameIndex->classNames as $className => $value) {
                $this->classNames[$className] = true;
            }
        } else {
            $unionClassNames = $classNameIndex->classNames;
            foreach ($this->classNames as $className => $value) {
                $unionClassNames[$className] = true;
            }
            $this->classNames = $unionClassNames;
        }
    }

    /**
     * @return array An key sorted array with all class names of this index as keys
     */
    public function sort()
    {
        ksort($this->classNames);
    }

    /**
     * @return int The number of class names contained in this index
     */
    public function count()
    {
        return count($this->classNames);
    }

    /**
     * Returns a new index object which contains all class names of this index
     * starting with the given prefix
     *
     * @param string $prefixFilter A prefix string to filter the class names of this index
     * @return \Neos\Flow\Aop\Builder\ClassNameIndex A new index object
     */
    public function filterByPrefix($prefixFilter)
    {
        $pointcuts = array_keys($this->classNames);
        $result = new ClassNameIndex();

        $right = count($pointcuts) - 1;
        $left = 0;

        $found = false;
        $currentPosition = -1;
        while ($found === false) {
            if ($left > $right) {
                break;
            }
            $currentPosition = $left + floor(($right - $left) / 2);
            if (strpos($pointcuts[$currentPosition], $prefixFilter) === 0) {
                $found = true;
                break;
            } else {
                $comparisonResult = strcmp($prefixFilter, $pointcuts[$currentPosition]);
                if ($comparisonResult > 0) {
                    $left = $currentPosition + 1;
                } else {
                    $right = $currentPosition - 1;
                }
            }
        }

        if ($found === true) {
            $startIndex = $currentPosition;
            while ($startIndex >= 0 && strpos($pointcuts[$startIndex], $prefixFilter) === 0) {
                $startIndex--;
            }
            $startIndex++;
            $endIndex = $currentPosition;
            while ($endIndex < count($pointcuts) && strpos($pointcuts[$endIndex], $prefixFilter) === 0) {
                $endIndex++;
            }

            $result->setClassNames(array_slice($pointcuts, $startIndex, $endIndex - $startIndex));
        }
        return $result;
    }
}
