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
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Mvc\Exception\InvalidRoutePartHandlerException;
use Neos\Flow\Mvc\Exception\InvalidRoutePartValueException;
use Neos\Flow\Mvc\Exception\InvalidRouteSetupException;
use Neos\Flow\Mvc\Exception\InvalidUriPatternException;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;

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
     * The tags that have been associated with this route during request matching, or NULL if no tags were set
     *
     * @var RouteTags|null
     */
    protected $matchedTags;

    /**
     * The merged UriConstraints of all Route Parts after resolving
     *
     * @var UriConstraints|null
     */
    protected $resolvedUriConstraints;

    /**
     * The tags that have been associated with this route during resolving, or NULL if no tags were set
     *
     * @var RouteTags|null
     */
    protected $resolvedTags;

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
     * @param boolean $lowerCase true: Route parts are converted to lower case by default. false: Route parts are not altered.
     * @return void
     */
    public function setLowerCase($lowerCase)
    {
        $this->lowerCase = (boolean)$lowerCase;
    }

    /**
     * Getter for $this->lowerCase.
     *
     * @return boolean true if this Route part will be converted to lower case, otherwise false.
     * @see setLowerCase()
     */
    public function isLowerCase()
    {
        return $this->lowerCase;
    }

    /**
     * Specifies whether Route values, that are not part of the Route configuration, should be appended to the
     * Resulting URI as query string.
     * If set to false, the route won't resolve if there are route values left after iterating through all Route Part
     * handlers and removing the matching default values.
     *
     * @param boolean $appendExceedingArguments true: exceeding arguments will be appended to the resulting URI
     * @return void
     */
    public function setAppendExceedingArguments($appendExceedingArguments)
    {
        $this->appendExceedingArguments = (boolean)$appendExceedingArguments;
    }

    /**
     * Returns true if exceeding arguments should be appended to the URI as query string, otherwise false
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
     * Returns the tags that have been associated with this route during request matching, or NULL if no tags were set
     *
     * @return RouteTags|null
     */
    public function getMatchedTags()
    {
        return $this->matchedTags;
    }

    /**
     * Returns the merged UriConstraints of all Route Parts after resolving, or NULL if no constraints were set yet
     *
     * @return UriConstraints|null
     */
    public function getResolvedUriConstraints()
    {
        return $this->resolvedUriConstraints;
    }

    /**
     * Returns the tags that have been associated with this route during resolving, or NULL if no tags were set
     *
     * @return RouteTags|null
     */
    public function getResolvedTags()
    {
        return $this->resolvedTags;
    }

    /**
     * Checks whether $routeContext corresponds to this Route.
     * If all Route Parts match successfully true is returned an $this->matchResults contains an array
     * combining Route default values and calculated matchResults from the individual Route Parts.
     *
     * @param RouteContext $routeContext The Route Context containing the current HTTP request object and, optional, Routing RouteParameters
     * @return boolean true if this Route corresponds to the given $routeContext, otherwise false
     * @throws InvalidRoutePartValueException
     * @see getMatchResults()
     */
    public function matches(RouteContext $routeContext)
    {
        $httpRequest = $routeContext->getHttpRequest();
        $routePath = RequestInformationHelper::getRelativeRequestPath($httpRequest);
        $this->matchResults = null;
        $this->matchedTags = RouteTags::createEmpty();
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
            if ($routePart instanceof ParameterAwareRoutePartInterface) {
                $matchResult = $routePart->matchWithParameters($routePath, $routeContext->getParameters());
            } else {
                $matchResult = $routePart->match($routePath);
            }
            if ($matchResult instanceof MatchResult) {
                $routeMatches = true;
                $routePartValue = $matchResult->getMatchedValue();
                if ($matchResult->hasTags()) {
                    $this->matchedTags = $this->matchedTags->merge($matchResult->getTags());
                }
            } else {
                $routeMatches = $matchResult === true;
                $routePartValue = $routeMatches ? $routePart->getValue() : null;
            }
            if ($routeMatches !== true) {
                if ($routePart->isOptional() && $optionalPartCount === 1) {
                    if ($routePart->getDefaultValue() === null) {
                        return false;
                    }
                    $skipOptionalParts = true;
                } else {
                    return false;
                }
            }
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
     * If all Route Parts can resolve one or more of the $routeValues, true is
     * returned and $this->resolvedUriConstraints contains an instance of UriConstraints that can be applied
     * to a template URI transforming it accordingly (@see Router::resolve())
     *
     * @param ResolveContext $resolveContext context for this resolve invokation
     * @return boolean true if this Route corresponds to the given $routeValues, otherwise false
     * @throws InvalidRoutePartValueException
     */
    public function resolves(ResolveContext $resolveContext)
    {
        $this->resolvedUriConstraints = UriConstraints::create();
        $this->resolvedTags = RouteTags::createEmpty();
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
        $routeValues = $resolveContext->getRouteValues();
        /** @var $routePart RoutePartInterface */
        foreach ($this->routeParts as $routePart) {
            if ($routePart instanceof ParameterAwareRoutePartInterface) {
                $resolveResult = $routePart->resolveWithParameters($routeValues, $resolveContext->getParameters());
            } else {
                $resolveResult = $routePart->resolve($routeValues);
            }
            if (!$resolveResult) {
                if (!$routePart->hasDefaultValue()) {
                    return false;
                }
            }
            if ($resolveResult instanceof ResolveResult) {
                $hasRoutePartValue = true;
                $routePartValue = $resolveResult->getResolvedValue();
                if ($resolveResult->hasUriConstraints()) {
                    $this->resolvedUriConstraints = $this->resolvedUriConstraints->merge($resolveResult->getUriConstraints());
                }
                if ($resolveResult->hasTags()) {
                    $this->resolvedTags = $this->resolvedTags->merge($resolveResult->getTags());
                }
            } else {
                $hasRoutePartValue = $routePart->hasValue();
                $routePartValue = $hasRoutePartValue ? $routePart->getValue() : null;
            }
            if ($routePart->getName() !== null) {
                $remainingDefaults = Arrays::unsetValueByPath($remainingDefaults, $routePart->getName());
            }
            if ($hasRoutePartValue && !is_string($routePartValue)) {
                throw new InvalidRoutePartValueException('RoutePart::getValue() must return a string after calling RoutePart::resolve(), got ' . (is_object($routePartValue) ? get_class($routePartValue) : gettype($routePartValue)) . ' for RoutePart "' . get_class($routePart) . '" in Route "' . $this->getName() . '".');
            }
            $routePartDefaultValue = $routePart->getDefaultValue();
            if ($routePartDefaultValue !== null && !is_string($routePartDefaultValue)) {
                throw new InvalidRoutePartValueException('RoutePart::getDefaultValue() must return a string, got ' . (is_object($routePartDefaultValue) ? get_class($routePartDefaultValue) : gettype($routePartDefaultValue)) . ' for RoutePart "' . get_class($routePart) . '" in Route "' . $this->getName() . '".');
            }
            if (!$routePart->isOptional()) {
                $resolvedUriPath .= $hasRoutePartValue ? $routePartValue : $routePartDefaultValue;
                $requireOptionalRouteParts = false;
                continue;
            }
            if ($hasRoutePartValue && strtolower($routePartValue) !== strtolower($routePartDefaultValue)) {
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
            $this->resolvedUriConstraints = $this->resolvedUriConstraints->withAddedQueryValues($routeValues);
        }

        if (!empty($resolvedUriPath)) {
            $this->resolvedUriConstraints = $this->resolvedUriConstraints->withPath($resolvedUriPath);
        }
        return true;
    }

    /**
     * Recursively iterates through the defaults of this route.
     * If a route value is equal to a default value, it's removed
     * from $routeValues.
     * If a value exists but is not equal to is corresponding default,
     * iteration is interrupted and false is returned.
     *
     * @param array $defaults
     * @param array $routeValues
     * @return boolean false if one of the $routeValues is not equal to it's default value. Otherwise true
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
     * @throws InvalidRoutePartHandlerException|InvalidRouteSetupException|InvalidUriPatternException
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
