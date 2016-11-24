<?php
namespace Neos\Flow\Mvc;

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
use Neos\Utility\ObjectAccess;

/**
 * This class is a helper that can be used as a
 * context for an Eel evaluation to match a request
 * using the Eel Syntax. This is primarily used
 * in the requestFilter of the Views.yaml configuration.
 *
 * @Flow\Proxy(false)
 */
class RequestMatcher
{
    /**
     * Request that will be used for the matcher.
     * If the Request is NULL this class will always
     * return FALSE. This case is primarily needed
     * if no parentRequest exists.
     *
     * @var ActionRequest
     */
    protected $request;

    /**
     * This property is set if parentRequest or
     * mainRequest is used. The main purpose is
     * to properly track the weight of the parentRequest
     * and mainRequest Matchers through the addWeight
     * method
     *
     * @var RequestMatcher
     */
    protected $parentMatcher;

    /**
     * The weight is a value that's added up through
     * various matching functions in here. This is
     * needed to have a way to determine, how specific
     * a configuration Method is because that's how the
     * configuration will be sorted.
     *
     * @var integer
     */
    protected $weight = 0;

    /**
     *
     * @param ActionRequest $actionRequest
     * @param RequestMatcher $parentMatcher
     */
    public function __construct(ActionRequest $actionRequest = null, $parentMatcher = null)
    {
        $this->request = $actionRequest;
        $this->parentMatcher = $parentMatcher;
    }

    /**
     * Check if the current Request's Package equals the argument
     *
     * @param string $package
     * @return boolean
     * @api
     */
    public function isPackage($package)
    {
        return $this->matchRequestProperty('controllerPackageKey', $package, 1);
    }

    /**
     * Check if the current Request's SubPackage equals the argument
     *
     * @param string $subPackage
     * @return boolean
     * @api
     */
    public function isSubPackage($subPackage)
    {
        return $this->matchRequestProperty('controllerSubpackageKey', $subPackage, 10);
    }

    /**
     * Check if the current Request's Controller equals the argument
     *
     * @param string $controller
     * @return boolean
     * @api
     */
    public function isController($controller)
    {
        return $this->matchRequestProperty('controllerName', $controller, 100);
    }

    /**
     * Check if the current Request's Action equals the argument
     *
     * @param string $action
     * @return boolean
     * @api
     */
    public function isAction($action)
    {
        return $this->matchRequestProperty('controllerActionName', $action, 1000);
    }

    /**
     * Check if the current Request's Format equals the argument
     *
     * @param string $format
     * @return boolean
     * @api
     */
    public function isFormat($format)
    {
        return $this->matchRequestProperty('format', $format, 10000);
    }

    /**
     * Compare a request propertyValue against an expected
     * value and add the weight if it's TRUE
     *
     * @param string $propertyName
     * @param string $expectedValue
     * @param integer $weight
     * @return boolean
     */
    protected function matchRequestProperty($propertyName, $expectedValue, $weight)
    {
        if ($this->request === null) {
            return false;
        }

        $value = ObjectAccess::getProperty($this->request, $propertyName);
        if ($value === $expectedValue) {
            $this->addWeight($weight);
            return true;
        }

        return false;
    }

    /**
     * Get a new RequestMatcher for the Request's ParentRequest
     *
     * @return RequestMatcher
     * @api
     */
    public function getParentRequest()
    {
        if ($this->request === null || $this->request->isMainRequest()) {
            return new RequestMatcher();
        }
        $this->addWeight(1000000);
        return new RequestMatcher($this->request->getParentRequest(), $this);
    }

    /**
     * Get a new RequestMatcher for the Request's MainRequest
     *
     * @return RequestMatcher
     * @api
     */
    public function getMainRequest()
    {
        $this->addWeight(100000);
        return new RequestMatcher($this->request->getMainRequest(), $this);
    }

    /**
     * Return the current weight for this match
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Add a weight to the total
     *
     * @param integer $weight
     * @return void
     */
    public function addWeight($weight)
    {
        $this->weight += $weight;
        if ($this->parentMatcher !== null) {
            $this->parentMatcher->addWeight($weight);
        }
    }

    /**
     * Reset the match weight
     *
     * @return void
     */
    public function resetWeight()
    {
        $this->weight = 0;
    }
}
