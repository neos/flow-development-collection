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
use Neos\Cache\CacheAwareInterface;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\Arrays;
use Neos\Flow\Validation\Validator\UuidValidator;
use Psr\Log\LoggerInterface;

/**
 * Caching of findMatchResults() and resolve() calls on the web Router.
 *
 * @Flow\Scope("singleton")
 */
class RouterCachingService
{
    /**
     * @var VariableFrontend
     * @Flow\Inject
     */
    protected $routeCache;

    /**
     * @var VariableFrontend
     * @Flow\Inject
     */
    protected $resolveCache;

    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\InjectConfiguration("mvc.routes")
     * @var array
     */
    protected $routingSettings;

    /**
     * @param LoggerInterface $logger
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function initializeObject()
    {
        // flush routing caches if in Development context & routing settings changed
        if ($this->objectManager->getContext()->isDevelopment() && $this->routeCache->get('routingSettings') !== $this->routingSettings) {
            $this->flushCaches();
            $this->routeCache->set('routingSettings', $this->routingSettings);
        }
    }

    /**
     * Checks the cache for the given RouteContext and returns the result or false if no matching ache entry was found
     *
     * @param RouteContext $routeContext
     * @return array|boolean the cached route values or false if no cache entry was found
     */
    public function getCachedMatchResults(RouteContext $routeContext)
    {
        $cachedResult = $this->routeCache->get($routeContext->getCacheEntryIdentifier());
        if ($cachedResult !== false) {
            $this->logger->debug(sprintf('Router route(): A cached Route with the cache identifier "%s" matched the request "%s (%s)".', $routeContext->getCacheEntryIdentifier(), $routeContext->getHttpRequest()->getUri(), $routeContext->getHttpRequest()->getMethod()));
        }

        return $cachedResult;
    }

    /**
     * Stores the $matchResults in the cache
     *
     * @param RouteContext $routeContext
     * @param array $matchResults
     * @param RouteTags|null $matchedTags
     * @return void
     */
    public function storeMatchResults(RouteContext $routeContext, array $matchResults, RouteTags $matchedTags = null)
    {
        if ($this->containsObject($matchResults)) {
            return;
        }
        $tags = $this->generateRouteTags($routeContext->getHttpRequest()->getRelativePath(), $matchResults);
        if ($matchedTags !== null) {
            $tags = array_unique(array_merge($matchedTags->getTags(), $tags));
        }
        $this->routeCache->set($routeContext->getCacheEntryIdentifier(), $matchResults, $tags);
    }

    /**
     * Checks the cache for the given ResolveContext and returns the cached UriConstraints if a cache entry is found
     *
     * @param ResolveContext $resolveContext
     * @return UriConstraints|boolean the cached URI or false if no cache entry was found
     */
    public function getCachedResolvedUriConstraints(ResolveContext $resolveContext)
    {
        $routeValues = $this->convertObjectsToHashes($resolveContext->getRouteValues());
        if ($routeValues === null) {
            return false;
        }
        return $this->resolveCache->get($this->buildResolveCacheIdentifier($resolveContext, $routeValues));
    }

    /**
     * Stores the resolved UriConstraints in the cache together with the $routeValues
     *
     * @param ResolveContext $resolveContext
     * @param UriConstraints $uriConstraints
     * @param RouteTags|null $resolvedTags
     * @return void
     */
    public function storeResolvedUriConstraints(ResolveContext $resolveContext, UriConstraints $uriConstraints, RouteTags $resolvedTags = null)
    {
        $routeValues = $this->convertObjectsToHashes($resolveContext->getRouteValues());
        if ($routeValues === null) {
            return;
        }

        $cacheIdentifier = $this->buildResolveCacheIdentifier($resolveContext, $routeValues);
        $tags = $this->generateRouteTags($uriConstraints->getPathConstraint(), $routeValues);
        if ($resolvedTags !== null) {
            $tags = array_unique(array_merge($resolvedTags->getTags(), $tags));
        }
        $this->resolveCache->set($cacheIdentifier, $uriConstraints, $tags);
    }

    /**
     * @param string $uriPath
     * @param array $routeValues
     * @return array
     */
    protected function generateRouteTags($uriPath, $routeValues)
    {
        $uriPath = trim($uriPath, '/');
        $tags = $this->extractUuids($routeValues);
        $path = '';
        $uriPath = explode('/', $uriPath);
        foreach ($uriPath as $uriPathSegment) {
            $path .= '/' . $uriPathSegment;
            $path = trim($path, '/');
            $tags[] = md5($path);
        }

        return $tags;
    }

    /**
     * Flushes 'route' and 'resolve' caches.
     *
     * @return void
     */
    public function flushCaches()
    {
        $this->routeCache->flush();
        $this->resolveCache->flush();
    }

    /**
     * Flushes 'findMatchResults' and 'resolve' caches for the given $tag
     *
     * @param string $tag
     * @return void
     */
    public function flushCachesByTag($tag)
    {
        $this->routeCache->flushByTag($tag);
        $this->resolveCache->flushByTag($tag);
    }

    /**
     * Flushes 'findMatchResults' caches that are tagged with the given $uriPath
     *
     * @param string $uriPath
     * @return void
     */
    public function flushCachesForUriPath($uriPath)
    {
        $uriPathTag = md5(trim($uriPath, '/'));
        $this->flushCachesByTag($uriPathTag);
    }

    /**
     * Checks if the given subject contains an object
     *
     * @param mixed $subject
     * @return boolean true if $subject contains an object, otherwise false
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
     * Recursively converts objects in an array to their identifiers
     *
     * @param array $routeValues the array to be processed
     * @return array the modified array or NULL if $routeValues contain an object and its identifier could not be determined
     */
    protected function convertObjectsToHashes(array $routeValues)
    {
        foreach ($routeValues as &$value) {
            if (is_object($value)) {
                if ($value instanceof CacheAwareInterface) {
                    $identifier = $value->getCacheEntryIdentifier();
                } else {
                    $identifier = $this->persistenceManager->getIdentifierByObject($value);
                }
                if ($identifier === null) {
                    return null;
                }
                $value = $identifier;
            } elseif (is_array($value)) {
                $value = $this->convertObjectsToHashes($value);
                if ($value === null) {
                    return null;
                }
            }
        }
        return $routeValues;
    }

    /**
     * Generates the Resolve cache identifier for the given Request
     *
     * @param ResolveContext $resolveContext
     * @param array $routeValues
     * @return string
     */
    protected function buildResolveCacheIdentifier(ResolveContext $resolveContext, array $routeValues)
    {
        Arrays::sortKeysRecursively($routeValues);

        return md5(sprintf('abs:%s|prefix:%s|routeValues:%s', $resolveContext->isForceAbsoluteUri() ? 1 : 0, $resolveContext->getUriPathPrefix(), trim(http_build_query($routeValues), '/')));
    }

    /**
     * Helper method to generate tags by taking all UUIDs contained
     * in the given $routeValues or $matchResults
     *
     * @param array $values
     * @return array
     */
    protected function extractUuids(array $values)
    {
        $uuids = [];
        foreach ($values as $value) {
            if (is_string($value)) {
                if (preg_match(UuidValidator::PATTERN_MATCH_UUID, $value) !== 0) {
                    $uuids[] = $value;
                }
            } elseif (is_array($value)) {
                $uuids = array_merge($uuids, $this->extractUuids($value));
            }
        }
        return $uuids;
    }
}
