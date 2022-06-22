<?php
namespace Neos\Flow\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * Static Route Part
 *
 */
class StaticRoutePart extends \Neos\Flow\Mvc\Routing\AbstractRoutePart
{
    /**
     * Gets default value of the Route Part.
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->name;
    }

    /**
     * Checks whether this Static Route Part correspond to the given $routePath.
     * This is true if $routePath is not empty and the first part is equal to the Route Part name.
     *
     * @param string|null $routePath The request path to be matched - without query parameters, host and fragment.
     * @return boolean true if Route Part matched $routePath, otherwise false.
     */
    public function match(&$routePath)
    {
        $this->value = null;
        if ($this->name === null || $this->name === '') {
            return false;
        }
        if ($routePath === '' || $routePath === null) {
            return false;
        }
        $valueToMatch = substr($routePath, 0, strlen($this->name));
        if ($valueToMatch !== $this->name) {
            return false;
        }
        $routePath = substr($routePath, strlen($valueToMatch));

        return true;
    }

    /**
     * Sets the Route Part value to the Route Part name and returns true if successful.
     *
     * @param array $routeValues not used but needed to implement \Neos\Flow\Mvc\Routing\AbstractRoutePart
     * @return boolean
     */
    public function resolve(array &$routeValues)
    {
        if ($this->name === null || $this->name === '') {
            return false;
        }
        $this->value = $this->name;
        if ($this->lowerCase) {
            $this->value = strtolower($this->value);
        }
        return true;
    }
}
