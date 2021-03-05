<?php
namespace Neos\Flow\Mvc\Routing\Dto;

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
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Http\Helper\UriHelper;
use Neos\Utility\Arrays;
use Psr\Http\Message\UriInterface;

/**
 * This class allows constraints to be applied to a given URI, transforming it accordingly as a result.
 *
 * Example:
 *
 * $exampleUri = new Uri('http://some-domain.tld/foo');
 * $uriConstraints = UriConstraints::create()
 *   ->withScheme('https')
 *   ->withHostPrefix('de.', ['en.', 'ch.'])
 *   ->withPort(8080)
 *   ->withPathPrefix('prefix/');
 * $resultingUri = $uriConstraints->applyTo($exampleUri); // https://de.some-domain.tld:8080/prefix/foo
 *
 *
 * @Flow\Proxy(false)
 */
final class UriConstraints
{
    const CONSTRAINT_SCHEME = 'scheme';
    const CONSTRAINT_HOST = 'host';
    const CONSTRAINT_HOST_PREFIX = 'hostPrefix';
    const CONSTRAINT_HOST_SUFFIX = 'hostSuffix';
    const CONSTRAINT_PORT = 'port';
    const CONSTRAINT_PATH = 'path';
    const CONSTRAINT_PATH_PREFIX = 'pathPrefix';
    const CONSTRAINT_PATH_SUFFIX = 'pathSuffix';
    const CONSTRAINT_QUERY_STRING = 'queryString';
    const CONSTRAINT_FRAGMENT = 'fragment';

    const HTTP_DEFAULT_HOST = 'localhost';

    /**
     * @var array
     */
    private $constraints;

    /**
     * @param array $constraints array of constraints with one of the CONSTRAINT_* constants as keys and corresponding values
     */
    private function __construct(array $constraints)
    {
        $this->constraints = $constraints;
    }

    /**
     * Create a new instance without any constraints
     *
     * @return UriConstraints
     */
    public static function create(): self
    {
        return new static([]);
    }

    /**
     * Create a new instance with constraints extracted from the given $uri
     *
     * @return UriConstraints
     */
    public static function fromUri(UriInterface $uri): self
    {
        $constraints = [];
        if ($uri->getScheme() !== '') {
            $constraints[self::CONSTRAINT_SCHEME] = $uri->getScheme();
            $constraints[self::CONSTRAINT_HOST] = $uri->getHost();
        }
        if ($uri->getPort() !== null) {
            $constraints[self::CONSTRAINT_PORT] = $uri->getPort();
        }
        if ($uri->getPath() !== '') {
            $constraints[self::CONSTRAINT_PATH] = $uri->getPath();
        }
        if ($uri->getQuery() !== '') {
            $constraints[self::CONSTRAINT_QUERY_STRING] = $uri->getQuery();
        }
        if ($uri->getFragment() !== '') {
            $constraints[self::CONSTRAINT_FRAGMENT] = $uri->getFragment();
        }
        return new static($constraints);
    }

    /**
     * Merge two instances of UriConstraints
     * Constraints of the given $uriConstraints instance will overrule similar constraints of this instance
     *
     * @param UriConstraints $uriConstraints
     * @return UriConstraints
     */
    public function merge(UriConstraints $uriConstraints): self
    {
        $mergedConstraints = array_merge($this->constraints, $uriConstraints->constraints);
        return new static($mergedConstraints);
    }

    /**
     * Create a new instance with the scheme constraint added
     *
     * @param string $scheme The URI scheme to force, usually "http" or "https"
     * @return UriConstraints
     */
    public function withScheme(string $scheme): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_SCHEME] = $scheme;
        return new static($newConstraints);
    }

    /**
     * Create a new instance with the host constraint added
     *
     * @param string $host The URI host part to force, for example "neos.io"
     * @return UriConstraints
     */
    public function withHost(string $host): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_HOST] = $host;
        return new static($newConstraints);
    }

    /**
     * Create a new instance with the host prefix constraint added
     *
     * @param string $prefix The URI host prefix to force, for example "en."
     * @param string[] $replacePrefixes a list of prefixes that should be replaced with the given prefix. if the list is empty or does not match the current host $prefix will be prepended as is
     * @return UriConstraints
     */
    public function withHostPrefix(string $prefix, array $replacePrefixes = []): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_HOST_PREFIX] = [
            'prefix' => $prefix,
            'replacePrefixes' => $replacePrefixes,
        ];
        return new static($newConstraints);
    }

    /**
     * Create a new instance with the host suffix constraint added
     *
     * @param string $suffix The URI host suffix to force, for example ".com"
     * @param string[] $replaceSuffixes a list of suffixes that should be replaced with the given suffix. if the list is empty or does not match, no replacement happens
     * @return UriConstraints
     */
    public function withHostSuffix(string $suffix, array $replaceSuffixes = []): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_HOST_SUFFIX] = [
            'suffix' => $suffix,
            'replaceSuffixes' => $replaceSuffixes,
        ];
        return new static($newConstraints);
    }

    /**
     * Create a new instance with the port constraint added
     *
     * @param int $port The URI port to force, for example 80
     * @return UriConstraints
     */
    public function withPort(int $port): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_PORT] = $port;
        return new static($newConstraints);
    }

    /**
     * Create a new instance with the path constraint added
     *
     * @param string $path The URI path to force, for example "some/path/"
     * @return UriConstraints
     */
    public function withPath(string $path): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_PATH] = $path;
        return new static($newConstraints);
    }

    /**
     * Create a new instance with the query string constraint added
     *
     * @param string $queryString
     * @return UriConstraints
     */
    public function withQueryString(string $queryString): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_QUERY_STRING] = $queryString;
        return new static($newConstraints);
    }

    /**
     * Create a new instance with a query string corresponding to the given $values merged with any existing query string constraint
     *
     * @param array $values
     * @return $this
     */
    public function withAddedQueryValues(array $values): self
    {
        $newConstraints = $this->constraints;
        if (isset($this->constraints[self::CONSTRAINT_QUERY_STRING])) {
            parse_str($this->constraints[self::CONSTRAINT_QUERY_STRING], $existingValues);
            $values = Arrays::arrayMergeRecursiveOverrule($existingValues, $values);
        }
        $newConstraints[self::CONSTRAINT_QUERY_STRING] = http_build_query($values, '', '&');
        return new static($newConstraints);
    }

    /**
     * Create a new instance with the fragment constraint added
     *
     * @param string $fragment
     * @return UriConstraints
     */
    public function withFragment(string $fragment): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_FRAGMENT] = $fragment;
        return new static($newConstraints);
    }

    /**
     * Create a new instance with the path prefix constraint added
     * This can be applied multiple times, later prefixes will be prepended to the start
     *
     * @param string $pathPrefix The URI path prefix to force, for example "some-prefix/"
     * @param bool $append If true the $pathPrefix will be added *after* previous path prefix constraints. By default prefixes are added *before* any existing prefix
     * @return UriConstraints
     */
    public function withPathPrefix(string $pathPrefix, bool $append = false): self
    {
        if ($pathPrefix === '') {
            return $this;
        }
        $newConstraints = $this->constraints;
        if (isset($newConstraints[self::CONSTRAINT_PATH_PREFIX])) {
            $pathPrefix = $append ? $newConstraints[self::CONSTRAINT_PATH_PREFIX] . $pathPrefix : $pathPrefix . $newConstraints[self::CONSTRAINT_PATH_PREFIX];
        }

        if (strncmp($pathPrefix, '/', 1) === 0) {
            throw new \InvalidArgumentException('path prefix is not allowed to start with "/". The passed-in path prefix was "' . $pathPrefix . '", and the computed path prefix (including previously set path prefixes) is "' . $pathPrefix . '". The computed path prefix may never start with "/".', 1570187341);
        }

        $newConstraints[self::CONSTRAINT_PATH_PREFIX] = $pathPrefix;
        return new static($newConstraints);
    }

    /**
     * Create a new instance with the path suffix constraint added
     * This can be applied multiple times, later suffixes will be appended to the end
     *
     * @param string $pathSuffix The URI path suffix to force, for example ".html"
     * @param bool $prepend If true the $pathSuffix will be added *before* previous path suffix constraints. By default suffixes are added *after* any existing suffix
     * @return UriConstraints
     */
    public function withPathSuffix(string $pathSuffix, bool $prepend = false): self
    {
        $newConstraints = $this->constraints;
        if (isset($newConstraints[self::CONSTRAINT_PATH_SUFFIX])) {
            $pathSuffix = $prepend ? $pathSuffix . $newConstraints[self::CONSTRAINT_PATH_SUFFIX] : $newConstraints[self::CONSTRAINT_PATH_SUFFIX] . $pathSuffix;
        }
        $newConstraints[self::CONSTRAINT_PATH_SUFFIX] = $pathSuffix;
        return new static($newConstraints);
    }

    /**
     * Returns the URI path constraint, which consists of the path and query string parts, or NULL if none was set
     *
     * @return string|null
     * @deprecated With Flow 7.0, use toUri()->getPath() and/or toUri()->getQuery() instead. @see toUri()
     */
    public function getPathConstraint(): ?string
    {
        $pathPart = $this->constraints[self::CONSTRAINT_PATH] ?? null;
        $queryPart = $this->constraints[self::CONSTRAINT_QUERY_STRING] ?? null;
        if ($pathPart === null && $queryPart === null) {
            return null;
        }
        return $pathPart . ($queryPart ? '?' . $queryPart : '');
    }

    /**
     * Applies all constraints of this instance to the given $templateUri and returns a new UriInterface instance
     * satisfying all of the constraints (see example above)
     *
     * @param UriInterface $baseUri The base URI to transform, usually the current request's base URI
     * @param bool $forceAbsoluteUri Whether or not to enforce the resulting URI to contain scheme and host (note: some of the constraints force an absolute URI by default)
     * @return UriInterface The transformed URI with all constraints applied
     */
    public function applyTo(UriInterface $baseUri, bool $forceAbsoluteUri): UriInterface
    {
        $uri = new Uri('');
        if (isset($this->constraints[self::CONSTRAINT_SCHEME]) && $this->constraints[self::CONSTRAINT_SCHEME] !== $baseUri->getScheme()) {
            $forceAbsoluteUri = true;
            $uri = $uri->withScheme($this->constraints[self::CONSTRAINT_SCHEME]);
        }
        if (isset($this->constraints[self::CONSTRAINT_HOST]) && $this->constraints[self::CONSTRAINT_HOST] !== $baseUri->getHost()) {
            $forceAbsoluteUri = true;
            $uri = $uri->withHost($this->constraints[self::CONSTRAINT_HOST]);
        }
        if (isset($this->constraints[self::CONSTRAINT_HOST_PREFIX])) {
            $host = !empty($uri->getHost()) ? $uri->getHost() : $baseUri->getHost();
            $originalHost = $host;
            $prefix = $this->constraints[self::CONSTRAINT_HOST_PREFIX]['prefix'];
            $replacePrefixes = $this->constraints[self::CONSTRAINT_HOST_PREFIX]['replacePrefixes'];
            foreach ($replacePrefixes as $replacePrefix) {
                if ($this->stringStartsWith($host, $replacePrefix)) {
                    $host = substr($host, strlen($replacePrefix));
                    break;
                }
            }
            $host = $prefix . $host;
            if ($host !== $originalHost) {
                $forceAbsoluteUri = true;
                $uri = $uri->withHost($host);
            }
        }
        if (isset($this->constraints[self::CONSTRAINT_HOST_SUFFIX])) {
            $host = !empty($uri->getHost()) ? $uri->getHost() : $baseUri->getHost();
            $originalHost = $host;
            $suffix = $this->constraints[self::CONSTRAINT_HOST_SUFFIX]['suffix'];
            $replaceSuffixes = $this->constraints[self::CONSTRAINT_HOST_SUFFIX]['replaceSuffixes'];

            // This is different from prefix handling, because we don't want a suffix added if no replacement match was found
            if ($replaceSuffixes === []) {
                $host .= $suffix;
            } else {
                foreach ($replaceSuffixes as $replaceSuffix) {
                    if ($this->stringEndsWith($host, $replaceSuffix)) {
                        $host = substr($host, 0, -strlen($replaceSuffix)) . $suffix;
                        break;
                    }
                }
            }
            if ($host !== $originalHost) {
                $forceAbsoluteUri = true;
                $uri = $uri->withHost($host);
            }
        }
        if (isset($this->constraints[self::CONSTRAINT_PORT]) && ($forceAbsoluteUri || $this->constraints[self::CONSTRAINT_PORT] !== $baseUri->getPort()) && ($baseUri->getPort() !== null || $this->constraints[self::CONSTRAINT_PORT] !== UriHelper::getDefaultPortForScheme($baseUri->getScheme()))) {
            $forceAbsoluteUri = true;
            $uri = $uri->withPort($this->constraints[self::CONSTRAINT_PORT]);
        }

        if (isset($this->constraints[self::CONSTRAINT_PATH]) && $this->constraints[self::CONSTRAINT_PATH] !== $baseUri->getPath()) {
            $uri = $uri->withPath($this->constraints[self::CONSTRAINT_PATH]);
        }
        if (isset($this->constraints[self::CONSTRAINT_PATH_PREFIX])) {
            // we need to trim the leading "/" from $uri->getPath() (as it is always absolute); so
            // that the Path Prefix can build strings like <prepended><url>, or
            // <prepended>/<url>
            $uri = $uri->withPath($this->constraints[self::CONSTRAINT_PATH_PREFIX] . ltrim($uri->getPath(), '/'));
        }
        if (isset($this->constraints[self::CONSTRAINT_PATH_SUFFIX])) {
            $uri = $uri->withPath($uri->getPath() . $this->constraints[self::CONSTRAINT_PATH_SUFFIX]);
        }
        if (isset($this->constraints[self::CONSTRAINT_QUERY_STRING])) {
            $uri = $uri->withQuery($this->constraints[self::CONSTRAINT_QUERY_STRING]);
        }

        // In case the base URL contains a path segment, prepend this to the URL (to generate proper host-absolute URLs)
        $baseUriPath = trim($baseUri->getPath(), '/');
        if ($baseUriPath !== '') {
            $mergedUriPath = $baseUriPath;
            if ($uri->getPath() !== '') {
                $mergedUriPath .= '/' . ltrim($uri->getPath(), '/');
            }
            $uri = $uri->withPath($mergedUriPath);
        }

        // Ensure the URL always starts with "/", no matter if it is empty of non-empty.
        // HINT: We need to enforce at least a "/" URL,a s otherwise e.g. linking to the root node of a Neos
        // site would not work.
        if ($uri->getPath() === '' || $uri->getPath()[0] !== '/') {
            $uri = $uri->withPath('/' . $uri->getPath());
        }

        if ($forceAbsoluteUri) {
            if (empty($uri->getScheme())) {
                $uri = $uri->withScheme($baseUri->getScheme());
            }
            if (empty($uri->getHost()) || $uri->getHost() === self::HTTP_DEFAULT_HOST) {
                $uri = $uri->withHost($baseUri->getHost());
            }
            if (!isset($this->constraints[self::CONSTRAINT_PORT]) && $uri->getPort() === null) {
                $port = $baseUri->getPort() ?? UriHelper::getDefaultPortForScheme($baseUri->getScheme());
                $uri = $uri->withPort($port);
            }
        }
        if (isset($this->constraints[self::CONSTRAINT_FRAGMENT])) {
            $uri = $uri->withFragment($this->constraints[self::CONSTRAINT_FRAGMENT]);
        }

        return $uri;
    }

    /**
     * @return UriInterface
     */
    public function toUri(): UriInterface
    {
        return $this->applyTo(new Uri(''), false);
    }

    /**
     * Whether the given $string starts with the specified $prefix
     *
     * @param string $string
     * @param string $prefix
     * @return bool
     */
    private function stringStartsWith(string $string, string $prefix): bool
    {
        return strpos($string, $prefix) === 0;
    }

    /**
     * Whether the given $string ends with the specified $suffix
     *
     * @param string $string
     * @param string $suffix
     * @return bool
     */
    private function stringEndsWith(string $string, string $suffix): bool
    {
        return substr($string, -strlen($suffix)) === $suffix;
    }
}
