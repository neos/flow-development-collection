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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\Arrays;

/**
 * Dynamic Route Part
 *
 * @api
 */
class DynamicRoutePart extends AbstractRoutePart implements DynamicRoutePartInterface
{
    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * The split string represents the end of a Dynamic Route Part.
     * If it is empty, Route Part will be equal to the remaining request path.
     *
     * @var string
     */
    protected $splitString = '';

    /**
     * Sets split string of the Route Part.
     *
     * @param string $splitString
     * @return void
     * @api
     */
    public function setSplitString($splitString)
    {
        $this->splitString = $splitString;
    }

    /**
     * Checks whether this Dynamic Route Part corresponds to the given $routePath.
     *
     * On successful match this method sets $this->value to the corresponding uriPart
     * and shortens $routePath respectively.
     *
     * @param string $routePath The request path to be matched - without query parameters, host and fragment.
     * @return boolean TRUE if Route Part matched $routePath, otherwise FALSE.
     */
    final public function match(&$routePath)
    {
        $this->value = null;
        if ($this->name === null || $this->name === '') {
            return false;
        }
        $valueToMatch = $this->findValueToMatch($routePath);
        $matchResult = $this->matchValue($valueToMatch);
        if ($matchResult !== true) {
            return $matchResult;
        }
        $this->removeMatchingPortionFromRequestPath($routePath, $valueToMatch);

        return true;
    }

    /**
     * Returns the first part of $routePath.
     * If a split string is set, only the first part of the value until location of the splitString is returned.
     * This method can be overridden by custom RoutePartHandlers to implement custom matching mechanisms.
     *
     * @param string $routePath The request path to be matched
     * @return string value to match, or an empty string if $routePath is empty or split string was not found
     * @api
     */
    protected function findValueToMatch($routePath)
    {
        if (!isset($routePath) || $routePath === '' || $routePath[0] === '/') {
            return '';
        }
        $valueToMatch = $routePath;
        if ($this->splitString !== '') {
            $splitStringPosition = strpos($valueToMatch, $this->splitString);
            if ($splitStringPosition !== false) {
                $valueToMatch = substr($valueToMatch, 0, $splitStringPosition);
            }
        }
        if (strpos($valueToMatch, '/') !== false) {
            return '';
        }
        return $valueToMatch;
    }

    /**
     * Checks, whether given value can be matched.
     * In the case of default Dynamic Route Parts a value matches when it's not empty.
     * This method can be overridden by custom RoutePartHandlers to implement custom matching mechanisms.
     *
     * @param string $value value to match
     * @return boolean TRUE if value could be matched successfully, otherwise FALSE.
     * @api
     */
    protected function matchValue($value)
    {
        if ($value === null || $value === '') {
            return false;
        }
        $this->value = rawurldecode($value);
        return true;
    }

    /**
     * Removes matching part from $routePath.
     * This method can be overridden by custom RoutePartHandlers to implement custom matching mechanisms.
     *
     * @param string $routePath The request path to be matched
     * @param string $valueToMatch The matching value
     * @return void
     * @api
     */
    protected function removeMatchingPortionFromRequestPath(&$routePath, $valueToMatch)
    {
        if ($valueToMatch !== null && $valueToMatch !== '') {
            $routePath = substr($routePath, strlen($valueToMatch));
        }
    }

    /**
     * Checks whether $routeValues contains elements which correspond to this Dynamic Route Part.
     * If a corresponding element is found in $routeValues, this element is removed from the array.
     *
     * @param array $routeValues An array with key/value pairs to be resolved by Dynamic Route Parts.
     * @return boolean TRUE if current Route Part could be resolved, otherwise FALSE
     */
    final public function resolve(array &$routeValues)
    {
        $this->value = null;
        if ($this->name === null || $this->name === '') {
            return false;
        }
        $valueToResolve = $this->findValueToResolve($routeValues);
        if (!$this->resolveValue($valueToResolve)) {
            return false;
        }
        $routeValues = Arrays::unsetValueByPath($routeValues, $this->name);
        return true;
    }

    /**
     * Returns the route value of the current route part.
     * This method can be overridden by custom RoutePartHandlers to implement custom resolving mechanisms.
     *
     * @param array $routeValues An array with key/value pairs to be resolved by Dynamic Route Parts.
     * @return string|array value to resolve.
     * @api
     */
    protected function findValueToResolve(array $routeValues)
    {
        return ObjectAccess::getPropertyPath($routeValues, $this->name);
    }

    /**
     * Checks, whether given value can be resolved and if so, sets $this->value to the resolved value.
     * If $value is empty, this method checks whether a default value exists.
     * This method can be overridden by custom RoutePartHandlers to implement custom resolving mechanisms.
     *
     * @param mixed $value value to resolve
     * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
     * @api
     */
    protected function resolveValue($value)
    {
        if ($value === null) {
            return false;
        }
        if (is_object($value)) {
            $value = $this->persistenceManager->getIdentifierByObject($value);
            if ($value === null || (!is_string($value) && !is_integer($value))) {
                return false;
            }
        }
        $this->value = rawurlencode($value);
        if ($this->lowerCase) {
            $this->value = strtolower($this->value);
        }
        return true;
    }
}
