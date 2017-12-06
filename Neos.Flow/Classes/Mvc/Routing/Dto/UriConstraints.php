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
use Neos\Flow\Http\Uri;
use Psr\Http\Message\UriInterface;

/**
 * This class allows constraints to be applied to a given URI, transforming it accordingly as a result.
 *
 * Example:
 *
 * $exampleUri = new Uri('http://some-domain.tld/foo');
 * $uriConstraints = UriConstraints::create()
 *   ->withScheme('https')
 *   ->withSubDomain('subdomain')
 *   ->withPort(8080)
 *   ->withPathPrefix('prefix/');
 * $resultingUri = $uriConstraints->applyTo($exampleUri); // https://subdomain.some-domain.tld:8080/prefix/foo
 *
 *
 * @Flow\Proxy(false)
 */
final class UriConstraints
{
    const CONSTRAINT_SCHEME = 'scheme';
    const CONSTRAINT_HOST = 'host';
    const CONSTRAINT_SUB_DOMAIN = 'subDomain';
    const CONSTRAINT_PORT = 'port';
    const CONSTRAINT_PATH = 'path';
    const CONSTRAINT_PATH_PREFIX = 'pathPrefix';
    const CONSTRAINT_PATH_SUFFIX = 'pathSuffix';

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
     * Create a new instance with the sub domain constraint added
     *
     * @param string $subDomain The URI sub-domain part to force, for example "sub-domain"
     * @return UriConstraints
     */
    public function withSubDomain(string $subDomain): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_SUB_DOMAIN] = $subDomain;
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
     * Create a new instance with the path prefix constraint added
     * This can be applied multiple times, later prefixes will be prepended to the start
     *
     * @param string $pathPrefix The URI path prefix to force, for example "some-prefix/"
     * @return UriConstraints
     */
    public function withPathPrefix(string $pathPrefix): self
    {
        if ($pathPrefix === '') {
            return $this;
        }
        $newConstraints = $this->constraints;
        if (isset($newConstraints[self::CONSTRAINT_PATH_PREFIX])) {
            $pathPrefix .= $newConstraints[self::CONSTRAINT_PATH_PREFIX];
        }
        $newConstraints[self::CONSTRAINT_PATH_PREFIX] = $pathPrefix;
        return new static($newConstraints);
    }

    /**
     * Create a new instance with the path suffix constraint added
     * This can be applied multiple times, later suffixes will be appended to the end
     *
     * @param string $pathSuffix The URI path suffix to force, for example ".html"
     * @return UriConstraints
     */
    public function withPathSuffix(string $pathSuffix): self
    {
        $newConstraints = $this->constraints;
        if (isset($newConstraints[self::CONSTRAINT_PATH_SUFFIX])) {
            $pathSuffix = $newConstraints[self::CONSTRAINT_PATH_SUFFIX] . $pathSuffix;
        }
        $newConstraints[self::CONSTRAINT_PATH_SUFFIX] = $pathSuffix;
        return new static($newConstraints);
    }

    /**
     * Returns the URI path constraint, or NULL if none was set
     *
     * @return string|null
     */
    public function getPathConstraint()
    {
        return $this->constraints[self::CONSTRAINT_PATH] ?? null;
    }

    /**
     * Applies all constraints of this instance to the given $templateUri and returns a new UriInterface instance
     * satisfying all of the constraints (see example above)
     *
     * @param UriInterface $templateUri The base URI to transform, usually the current request URI
     * @param bool $forceAbsoluteUri Whether or not to enforce the resulting URI to contain scheme and host (note: some of the constraints force an absolute URI by default)
     * @return UriInterface The transformed URI with all constraints applied
     */
    public function applyTo(UriInterface $templateUri, bool $forceAbsoluteUri): UriInterface
    {
        $uri = new Uri('');
        if (isset($this->constraints[self::CONSTRAINT_SCHEME]) && $this->constraints[self::CONSTRAINT_SCHEME] !== $templateUri->getScheme()) {
            $forceAbsoluteUri = true;
            $uri = $uri->withScheme($this->constraints[self::CONSTRAINT_SCHEME]);
        }
        if (isset($this->constraints[self::CONSTRAINT_HOST]) && $this->constraints[self::CONSTRAINT_HOST] !== $templateUri->getHost()) {
            $forceAbsoluteUri = true;
            $uri = $uri->withHost($this->constraints[self::CONSTRAINT_HOST]);
        }
        if (isset($this->constraints[self::CONSTRAINT_SUB_DOMAIN])) {
            $requestSubDomain = $this->extractSubDomain($templateUri);
            if ($requestSubDomain !== $this->constraints[self::CONSTRAINT_SUB_DOMAIN]) {
                $forceAbsoluteUri = true;
                $host = !empty($uri->getHost()) ? $uri->getHost() : $templateUri->getHost();
                $domainParts = explode('.', $host);
                if (count($domainParts) > 2) {
                    $domainParts[0] = $this->constraints[self::CONSTRAINT_SUB_DOMAIN];
                } else {
                    array_unshift($domainParts, $this->constraints[self::CONSTRAINT_SUB_DOMAIN]);
                }
                $uri = $uri->withHost(implode('.', $domainParts));
            }
        }
        if (isset($this->constraints[self::CONSTRAINT_PORT]) && $this->constraints[self::CONSTRAINT_PORT] !== $templateUri->getPort()) {
            $forceAbsoluteUri = true;
            $uri = $uri->withPort($this->constraints[self::CONSTRAINT_PORT]);
        }

        if (isset($this->constraints[self::CONSTRAINT_PATH]) && $this->constraints[self::CONSTRAINT_PATH] !== $templateUri->getPath()) {
            $uri = $uri->withPath($this->constraints[self::CONSTRAINT_PATH]);
        }
        if (isset($this->constraints[self::CONSTRAINT_PATH_PREFIX]) && $uri->getPath() !== '') {
            $uri = $uri->withPath($this->constraints[self::CONSTRAINT_PATH_PREFIX] . $uri->getPath());
        }
        if (isset($this->constraints[self::CONSTRAINT_PATH_SUFFIX]) && $uri->getPath() !== '') {
            $uri = $uri->withPath($uri->getPath() . $this->constraints[self::CONSTRAINT_PATH_SUFFIX]);
        }

        if ($forceAbsoluteUri) {
            if (empty($uri->getScheme())) {
                $uri = $uri->withScheme($templateUri->getScheme());
            }
            if (empty($uri->getHost())) {
                $uri = $uri->withHost($templateUri->getHost());
            }
            if (empty($uri->getPort()) && $templateUri->getPort() !== null) {
                $uri = $uri->withPort($templateUri->getPort());
            }
        }

        return $uri;
    }

    /**
     * Extracts the sub-domain part from a given $uri
     *
     * @param UriInterface $uri
     * @return string
     */
    private function extractSubDomain(UriInterface $uri): string
    {
        if (preg_match('/^([a-z0-9|-]+)\.[a-z0-9|-]+\.[a-z]+/', $uri->getHost(), $matches) !== 1) {
            // no sub domain
            return '';
        }
        return $matches[1];
    }
}
