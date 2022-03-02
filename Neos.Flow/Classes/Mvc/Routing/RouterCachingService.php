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

use Neos\Cache\Exception as CacheException;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\CacheAwareInterface;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\Arrays;
use Psr\Log\LoggerInterface;

/**
 * Caching of findMatchResults() and resolve() calls on the web Router.
 *
 * @Flow\Scope("singleton")
 */
class RouterCachingService
{
    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $routeCache;

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $resolveCache;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
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
     * @throws CacheException
     */
    public function initializeObject(): void
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
     * @throws CacheException
     */
    public function storeMatchResults(RouteContext $routeContext, array $matchResults, RouteTags $matchedTags = null): void
    {
        if ($this->containsObject($matchResults)) {
            return;
        }
        $tags = $this->generateRouteTags(RequestInformationHelper::getRelativeRequestPath($routeContext->getHttpRequest()));
        if ($matchedTags !== null) {
            $tags = array_unique(array_merge($matchedTags->getTags(), $tags));
        }
        $this->routeCache->set($routeContext->getCacheEntryIdentifier(), $matchResults, $tags);
    }

    /**
     * Checks the cache for the given ResolveContext and returns the cached UriConstraints if a cache entry is found
     *
     * @return UriConstraints|bool the cached URI or false if no cache entry was found
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
     * @throws CacheException
     */
    public function storeResolvedUriConstraints(ResolveContext $resolveContext, UriConstraints $uriConstraints, RouteTags $resolvedTags = null): void
    {
        $routeValues = $this->convertObjectsToHashes($resolveContext->getRouteValues());
        if ($routeValues === null) {
            return;
        }

        $cacheIdentifier = $this->buildResolveCacheIdentifier($resolveContext, $routeValues);
        $tags = $this->generateRouteTags((string)$uriConstraints->toUri());
        if ($resolvedTags !== null) {
            $tags = array_unique(array_merge($resolvedTags->getTags(), $tags));
        }
        $this->resolveCache->set($cacheIdentifier, $uriConstraints, $tags);
    }

    private function generateRouteTags(string $uriPath): array
    {
        $tags = [];
        $path = '';
        foreach (explode('/', trim($uriPath, '/')) as $uriPathSegment) {
            $path .= '/' . $uriPathSegment;
            $path = trim($path, '/');
            $tags[] = md5($path);
        }
        return $tags;
    }

    /**
     * Flushes 'route' and 'resolve' caches.
     */
    public function flushCaches(): void
    {
        $this->routeCache->flush();
        $this->resolveCache->flush();
    }

    /**
     * Flushes 'findMatchResults' and 'resolve' caches for the given $tag
     */
    public function flushCachesByTag(string $tag): void
    {
        $this->routeCache->flushByTag($tag);
        $this->resolveCache->flushByTag($tag);
    }

    /**
     * Flushes 'findMatchResults' caches that are tagged with the given $uriPath
     */
    public function flushCachesForUriPath(string $uriPath): void
    {
        $uriPathTag = md5(trim($uriPath, '/'));
        $this->flushCachesByTag($uriPathTag);
    }

    /**
     * Checks if the given subject contains an object
     *
     * @return bool true if $subject contains an object, otherwise false
     */
    private function containsObject($subject): bool
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
     * @return array|null the modified array or NULL if $routeValues contain an object and its identifier could not be determined
     */
    private function convertObjectsToHashes(array $routeValues): ?array
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
     */
    private function buildResolveCacheIdentifier(ResolveContext $resolveContext, array $routeValues): string
    {
        Arrays::sortKeysRecursively($routeValues);

        return md5(sprintf('abs:%s|prefix:%s|routeValues:%s', $resolveContext->isForceAbsoluteUri() ? 1 : 0, $resolveContext->getUriPathPrefix(), trim(http_build_query($routeValues), '/')));
    }
}
