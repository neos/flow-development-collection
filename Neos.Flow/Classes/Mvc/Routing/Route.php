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
use Neos\Flow\Http\Request;
use Neos\Flow\Mvc\Exception\InvalidRoutePartHandlerException;
use Neos\Flow\Mvc\Exception\InvalidRoutePartValueException;
use Neos\Flow\Mvc\Exception\InvalidRouteSetupException;
use Neos\Flow\Mvc\Exception\InvalidUriPatternException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\Arrays;

/**
 * Implementation of a standard route
 */
class Route
{
    const ROUTEPART_TYPE_STATIC = 'static';
    const ROUTEPART_TYPE_DYNAMIC = 'dynamic';
    const PATTERN_EXTRACTROUTEPARTS = '/(?P<optionalStart>\(?)(?P<dynamic>{?)(?P<content>@?[^}{\(\)]+)}?(?P<optionalEnd>\)?)/';

    /**
     * Route name
     *
     * @var string
     */
    protected $name = null;

    /**
     * Default values
     *
     * @var array
     */
    protected $defaults = [];

    /**
     * URI Pattern of this route
     *
     * @var string
     */
    protected $uriPattern = null;

    /**
     * Specifies whether Route Parts of this Route should be converted to lower case when resolved.
     *
     * @var boolean
     */
    protected $lowerCase = true;

    /**
     * Specifies whether Route Values, that are not part of the Routes configuration, should be appended as query string
     *
     * @var boolean
     */
    protected $appendExceedingArguments = false;

    /**
     * Contains the routing results (indexed by "package", "controller" and
     * "action") after a successful call of matches()
     *
     * @var array
     */
    protected $matchResults = [];

    /**
     * Contains the matching uri (excluding protocol and host) after a
     * successful call of resolves()
     *
     * @var string
     */
    protected $resolvedUriPath;

    /**
     * Contains associative array of Route Part options
     * (key: Route Part name, value: array of Route Part options)
     *
     * @var array
     */
    protected $routePartsConfiguration = [];

    /**
     * Container for Route Parts.
     *
     * @var array
     */
    protected $routeParts = [];

    /**
     * If not empty only the specified HTTP verbs are accepted by this route
     *
     * @var array non-associative array e.g. array('GET', 'POST')
     */
    protected $httpMethods = [];

    /**
     * Indicates whether this route is parsed.
     * For better performance, routes are only parsed if needed.
     *
     * @var boolean
     */
    protected $isParsed = false;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Sets Route name.
     *
     * @param string $name The Route name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of this Route.
     *
     * @return string Route name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets default values for this Route.
     * This array is merged with the actual matchResults when match() is called.
     *
     * @param array $defaults
     * @return void
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * Returns default values for this Route.
     *
     * @return array Route defaults
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Sets the URI pattern this route should match with
     *
     * @param string $uriPattern
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setUriPattern($uriPattern)
    {
        if (!is_string($uriPattern)) {
            throw new \InvalidArgumentException(sprintf('URI Pattern must be of type string, %s given.', gettype($uriPattern)), 1223499724);
        }
        $this->uriPattern = $uriPattern;
        $this->isParsed = false;
    }

    /**
     * Returns the URI pattern this route should match with
     *
     * @return string the URI pattern
     */
    public function getUriPattern()
    {
        return $this->uriPattern;
    }

    /**
     * Specifies whether Route parts of this route should be converted to lower case when resolved.
     * This setting can be overwritten for all dynamic Route parts.
     *
     * @param boolean $lowerCase TRUE: Route parts are converted to lower case by default. FALSE: Route parts are not altered.
     * @return void
     */
    public function setLowerCase($lowerCase)
    {
        $this->lowerCase = (boolean)$lowerCase;
    }

    /**
     * Getter for $this->lowerCase.
     *
     * @return boolean TRUE if this Route part will be converted to lower case, otherwise FALSE.
     * @see setLowerCase()
     */
    public function isLowerCase()
    {
        return $this->lowerCase;
    }

    /**
     * Specifies whether Route values, that are not part of the Route configuration, should be appended to the
     * Resulting URI as query string.
     * If set to FALSE, the route won't resolve if there are route values left after iterating through all Route Part
     * handlers and removing the matching default values.
     *
     * @param boolean $appendExceedingArguments TRUE: exceeding arguments will be appended to the resulting URI
     * @return void
     */
    public function setAppendExceedingArguments($appendExceedingArguments)
    {
        $this->appendExceedingArguments = (boolean)$appendExceedingArguments;
    }

    /**
     * Returns TRUE if exceeding arguments should be appended to the URI as query string, otherwise FALSE
     *
     * @return boolean
     */
    public function getAppendExceedingArguments()
    {
        return $this->appendExceedingArguments;
    }

    /**
     * By default all Dynamic Route Parts are resolved by
     * \Neos\Flow\Mvc\Routing\DynamicRoutePart.
     * But you can specify different classes to handle particular Route Parts.
     *
     * Note: Route Part handlers must implement
     * \Neos\Flow\Mvc\Routing\DynamicRoutePartInterface.
     *
     * Usage: setRoutePartsConfiguration(array('@controller' =>
     *            array('handler' => \Neos\Package\Subpackage\MyRoutePartHandler::class)));
     *
     * @param array $routePartsConfiguration Route Parts configuration options
     * @return void
     */
    public function setRoutePartsConfiguration(array $routePartsConfiguration)
    {
        $this->routePartsConfiguration = $routePartsConfiguration;
    }

    /**
     * Returns the route parts configuration of this route
     *
     * @return array $routePartsConfiguration
     */
    public function getRoutePartsConfiguration()
    {
        return $this->routePartsConfiguration;
    }

    /**
     * Limits the HTTP verbs that are accepted by this route.
     * If empty all HTTP verbs are accepted
     *
     * @param array $httpMethods non-associative array in the format array('GET', 'POST', ...)
     * @return void
     */
    public function setHttpMethods(array $httpMethods)
    {
        $this->httpMethods = $httpMethods;
    }

    /**
     * @return array
     */
    public function getHttpMethods()
    {
        return $this->httpMethods;
    }

    /**
     * Whether or not this route is limited to one or more HTTP verbs
     *
     * @return boolean
     */
    public function hasHttpMethodConstraints()
    {
        return $this->httpMethods !== [];
    }

    /**
     * Returns an array with the Route match results.
     *
     * @return array An array of Route Parts and their values for further handling by the Router
     * @see \Neos\Flow\Mvc\Routing\Router
     */
    public function getMatchResults()
    {
        return $this->matchResults;
    }

    /**
     * Returns the URI path which corresponds to this Route.
     *
     * @return string A string containing the corresponding uri (excluding protocol and host)
     */
    public function getResolvedUriPath()
    {
        return $this->resolvedUriPath;
    }

    /**
     * Checks whether $routePath corresponds to this Route.
     * If all Route Parts match successfully TRUE is returned and
     * $this->matchResults contains an array combining Route default values and
     * calculated matchResults from the individual Route Parts.
     *
     * @param Request $httpRequest the HTTP request to match
     * @return boolean TRUE if this Route corresponds to the given $routePath, otherwise FALSE
     * @throws InvalidRoutePartValueException
     * @see getMatchResults()
     */
    public function matches(Request $httpRequest)
    {
        $routePath = $httpRequest->getRelativePath();
        $this->matchResults = null;
        if ($this->uriPattern === null) {
            return false;
        }
        if (!$this->isParsed) {
            $this->parse();
        }
        if ($this->hasHttpMethodConstraints() && (!in_array($httpRequest->getMethod(), $this->httpMethods))) {
            return false;
        }
        $matchResults = [];

        $routePath = trim($routePath, '/');
        $skipOptionalParts = false;
        $optionalPartCount = 0;
        /** @var $routePart RoutePartInterface */
        foreach ($this->routeParts as $routePart) {
            if ($routePart->isOptional()) {
                $optionalPartCount++;
                if ($skipOptionalParts) {
                    if ($routePart->getDefaultValue() === null) {
                        return false;
                    }
                    continue;
                }
            } else {
                $optionalPartCount = 0;
                $skipOptionalParts = false;
            }
            if ($routePart->match($routePath) !== true) {
                if ($routePart->isOptional() && $optionalPartCount === 1) {
                    if ($routePart->getDefaultValue() === null) {
                        return false;
                    }
                    $skipOptionalParts = true;
                } else {
                    return false;
                }
            }
            $routePartValue = $routePart->getValue();
            if ($routePartValue !== null) {
                if ($this->containsObject($routePartValue)) {
                    throw new InvalidRoutePartValueException('RoutePart::getValue() must only return simple types after calling RoutePart::match(). RoutePart "' . get_class($routePart) . '" returned one or more objects in Route "' . $this->getName() . '".');
                }
                $matchResults = Arrays::setValueByPath($matchResults, $routePart->getName(), $routePartValue);
            }
        }
        if (strlen($routePath) > 0) {
            return false;
        }

        $this->matchResults = Arrays::arrayMergeRecursiveOverrule($this->defaults, $matchResults);
        return true;
    }

    /**
     * Checks whether $routeValues can be resolved to a corresponding uri.
     * If all Route Parts can resolve one or more of the $routeValues, TRUE is
     * returned and $this->matchingURI contains the generated URI (excluding
     * protocol and host).
     *
     * @param array $routeValues An array containing key/value pairs to be resolved to uri segments
     * @return boolean TRUE if this Route corresponds to the given $routeValues, otherwise FALSE
     * @throws InvalidRoutePartValueException
     */
    public function resolves(array $routeValues)
    {
        $this->resolvedUriPath = null;
        if ($this->uriPattern === null) {
            return false;
        }
        if (!$this->isParsed) {
            $this->parse();
        }

        $resolvedUriPath = '';
        $remainingDefaults = $this->defaults;
        $requireOptionalRouteParts = false;
        $matchingOptionalUriPortion = '';
        /** @var $routePart RoutePartInterface */
        foreach ($this->routeParts as $routePart) {
            if (!$routePart->resolve($routeValues)) {
                if (!$routePart->hasDefaultValue()) {
                    return false;
                }
            }
            if ($routePart->getName() !== null) {
                $remainingDefaults = Arrays::unsetValueByPath($remainingDefaults, $routePart->getName());
            }
            $routePartValue = null;
            if ($routePart->hasValue()) {
                $routePartValue = $routePart->getValue();
                if (!is_string($routePartValue)) {
                    throw new InvalidRoutePartValueException('RoutePart::getValue() must return a string after calling RoutePart::resolve(), got ' . (is_object($routePartValue) ? get_class($routePartValue) : gettype($routePartValue)) . ' for RoutePart "' . get_class($routePart) . '" in Route "' . $this->getName() . '".');
                }
            }
            $routePartDefaultValue = $routePart->getDefaultValue();
            if ($routePartDefaultValue !== null && !is_string($routePartDefaultValue)) {
                throw new InvalidRoutePartValueException('RoutePart::getDefaultValue() must return a string, got ' . (is_object($routePartDefaultValue) ? get_class($routePartDefaultValue) : gettype($routePartDefaultValue)) . ' for RoutePart "' . get_class($routePart) . '" in Route "' . $this->getName() . '".');
            }
            if (!$routePart->isOptional()) {
                $resolvedUriPath .= $routePart->hasValue() ? $routePartValue : $routePartDefaultValue;
                $requireOptionalRouteParts = false;
                continue;
            }
            if ($routePart->hasValue() && strtolower($routePartValue) !== strtolower($routePartDefaultValue)) {
                $matchingOptionalUriPortion .= $routePartValue;
                $requireOptionalRouteParts = true;
            } else {
                $matchingOptionalUriPortion .= $routePartDefaultValue;
            }
            if ($requireOptionalRouteParts) {
                $resolvedUriPath .= $matchingOptionalUriPortion;
                $matchingOptionalUriPortion = '';
            }
        }

        if ($this->compareAndRemoveMatchingDefaultValues($remainingDefaults, $routeValues) !== true) {
            return false;
        }
        if (isset($routeValues['@format']) && $routeValues['@format'] === '') {
            unset($routeValues['@format']);
        }

        // add query string
        if (count($routeValues) > 0) {
            $routeValues = Arrays::removeEmptyElementsRecursively($routeValues);
            $routeValues = $this->persistenceManager->convertObjectsToIdentityArrays($routeValues);
            if (!$this->appendExceedingArguments) {
                $internalArguments = $this->extractInternalArguments($routeValues);
                if ($routeValues !== []) {
                    return false;
                }
                $routeValues = $internalArguments;
            }
            $queryString = http_build_query($routeValues, null, '&');
            if ($queryString !== '') {
                $resolvedUriPath .= strpos($resolvedUriPath, '?') !== false ? '&' . $queryString : '?' . $queryString;
            }
        }
        $this->resolvedUriPath = $resolvedUriPath;
        return true;
    }

    /**
     * Recursively iterates through the defaults of this route.
     * If a route value is equal to a default value, it's removed
     * from $routeValues.
     * If a value exists but is not equal to is corresponding default,
     * iteration is interrupted and FALSE is returned.
     *
     * @param array $defaults
     * @param array $routeValues
     * @return boolean FALSE if one of the $routeValues is not equal to it's default value. Otherwise TRUE
     */
    protected function compareAndRemoveMatchingDefaultValues(array $defaults, array &$routeValues)
    {
        foreach ($defaults as $key => $defaultValue) {
            if (!isset($routeValues[$key])) {
                if ($defaultValue === '' || ($key === '@format' && strtolower($defaultValue) === 'html')) {
                    continue;
                }
                return false;
            }
            if (is_array($defaultValue)) {
                if (!is_array($routeValues[$key])) {
                    return false;
                }
                if ($this->compareAndRemoveMatchingDefaultValues($defaultValue, $routeValues[$key]) === false) {
                    return false;
                }
                continue;
            } elseif (is_array($routeValues[$key])) {
                return false;
            }
            if (strtolower($routeValues[$key]) !== strtolower($defaultValue)) {
                return false;
            }
            unset($routeValues[$key]);
        }
        return true;
    }

    /**
     * Removes all internal arguments (prefixed with two underscores) from the given $arguments
     * and returns them as array
     *
     * @param array $arguments
     * @return array the internal arguments
     */
    protected function extractInternalArguments(array &$arguments)
    {
        $internalArguments = [];
        foreach ($arguments as $argumentKey => &$argumentValue) {
            if (substr($argumentKey, 0, 2) === '__') {
                $internalArguments[$argumentKey] = $argumentValue;
                unset($arguments[$argumentKey]);
                continue;
            }
            if (substr($argumentKey, 0, 2) === '--' && is_array($argumentValue)) {
                $internalArguments[$argumentKey] = $this->extractInternalArguments($argumentValue);
                if ($internalArguments[$argumentKey] === []) {
                    unset($internalArguments[$argumentKey]);
                }
                if ($argumentValue === []) {
                    unset($arguments[$argumentKey]);
                }
            }
        }
        return $internalArguments;
    }

    /**
     * Checks if the given subject contains an object
     *
     * @param mixed $subject
     * @return boolean If it contains an object or not
     */
    protected function containsObject($subject)
    {
        if (is_object($subject)) {
            return true;
        }
        if (!is_array($subject)) {
            return false;
        }
        foreach ($subject as $value) {
            if ($this->containsObject($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Iterates through all segments in $this->uriPattern and creates
     * appropriate RoutePart instances.
     *
     * @return void
     * @throws InvalidRoutePartHandlerException
     * @throws InvalidUriPatternException
     */
    public function parse()
    {
        if ($this->isParsed || $this->uriPattern === null || $this->uriPattern === '') {
            return;
        }
        $this->routeParts = [];
        $currentRoutePartIsOptional = false;
        if (substr($this->uriPattern, -1) === '/') {
            throw new InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" ends with a slash, which is not allowed. You can put the trailing slash in brackets to make it optional.', 1234782997);
        }
        if ($this->uriPattern[0] === '/') {
            throw new InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" starts with a slash, which is not allowed.', 1234782983);
        }

        $matches = [];
        preg_match_all(self::PATTERN_EXTRACTROUTEPARTS, $this->uriPattern, $matches, PREG_SET_ORDER);

        /** @var $lastRoutePart RoutePartInterface */
        $lastRoutePart = null;
        foreach ($matches as $match) {
            $routePartType = empty($match['dynamic']) ? self::ROUTEPART_TYPE_STATIC : self::ROUTEPART_TYPE_DYNAMIC;
            $routePartName = $match['content'];
            if (!empty($match['optionalStart'])) {
                if ($lastRoutePart !== null && $lastRoutePart->isOptional()) {
                    throw new InvalidUriPatternException('the URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains successive optional Route sections, which is not allowed.', 1234562050);
                }
                $currentRoutePartIsOptional = true;
            }
            $routePart = null;
            switch ($routePartType) {
                case self::ROUTEPART_TYPE_DYNAMIC:
                    if ($lastRoutePart instanceof DynamicRoutePartInterface) {
                        throw new InvalidUriPatternException('the URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains successive Dynamic Route Parts, which is not allowed.', 1218446975);
                    }
                    if (isset($this->routePartsConfiguration[$routePartName]['handler'])) {
                        $routePart = $this->objectManager->get($this->routePartsConfiguration[$routePartName]['handler']);
                        if (!$routePart instanceof DynamicRoutePartInterface) {
                            throw new InvalidRoutePartHandlerException(sprintf('routePart handlers must implement "%s" in route "%s"', DynamicRoutePartInterface::class, $this->getName()), 1218480972);
                        }
                    } elseif (isset($this->routePartsConfiguration[$routePartName]['objectType'])) {
                        $routePart = new IdentityRoutePart();
                        $routePart->setObjectType($this->routePartsConfiguration[$routePartName]['objectType']);
                        if (isset($this->routePartsConfiguration[$routePartName]['uriPattern'])) {
                            $routePart->setUriPattern($this->routePartsConfiguration[$routePartName]['uriPattern']);
                        }
                    } else {
                        $routePart = new DynamicRoutePart();
                    }
                    $routePartDefaultValue = ObjectAccess::getPropertyPath($this->defaults, $routePartName);
                    if ($routePartDefaultValue !== null) {
                        $routePart->setDefaultValue($routePartDefaultValue);
                    }
                    break;
                case self::ROUTEPART_TYPE_STATIC:
                    $routePart = new StaticRoutePart();
                    if ($lastRoutePart !== null && $lastRoutePart instanceof DynamicRoutePartInterface) {
                        /** @var DynamicRoutePartInterface $lastRoutePart */
                        $lastRoutePart->setSplitString($routePartName);
                    }
            }
            $routePart->setName($routePartName);
            if ($currentRoutePartIsOptional) {
                $routePart->setOptional(true);
                if ($routePart instanceof DynamicRoutePartInterface && !$routePart->hasDefaultValue()) {
                    throw new InvalidRouteSetupException(sprintf('There is no default value specified for the optional route part "{%s}" of route "%s", but all dynamic optional route parts need a default.', $routePartName, $this->getName()), 1477140679);
                }
            }
            $routePart->setLowerCase($this->lowerCase);
            if (isset($this->routePartsConfiguration[$routePartName]['options'])) {
                $routePart->setOptions($this->routePartsConfiguration[$routePartName]['options']);
            }
            if (isset($this->routePartsConfiguration[$routePartName]['toLowerCase'])) {
                $routePart->setLowerCase($this->routePartsConfiguration[$routePartName]['toLowerCase']);
            }

            $this->routeParts[] = $routePart;
            if (!empty($match['optionalEnd'])) {
                if (!$currentRoutePartIsOptional) {
                    throw new InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains an unopened optional section.', 1234564495);
                }
                $currentRoutePartIsOptional = false;
            }
            $lastRoutePart = $routePart;
        }
        if ($currentRoutePartIsOptional) {
            throw new InvalidUriPatternException('The URI pattern "' . $this->uriPattern . '" of route "' . $this->getName() . '" contains an unterminated optional section.', 1234563922);
        }
        $this->isParsed = true;
    }
}
