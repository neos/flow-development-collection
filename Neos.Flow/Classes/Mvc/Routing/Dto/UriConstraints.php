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
 * @Flow\Proxy(false)
 */
final class UriConstraints
{
    const CONSTRAINT_SCHEME = 'scheme';
    const CONSTRAINT_HOST = 'host';
    const CONSTRAINT_SUB_DOMAIN = 'subDomain';
    const CONSTRAINT_TOP_LEVEL_DOMAIN = 'topLevelDomain';
    const CONSTRAINT_PORT = 'port';
    const CONSTRAINT_PATH = 'path';
    const CONSTRAINT_PATH_PREFIX = 'pathPrefix';
    const CONSTRAINT_PATH_SUFFIX = 'pathSuffix';

    /**
     * @var array
     */
    private $constraints;

    private function __construct(array $constraints)
    {
        $this->constraints = $constraints;
    }

    public static function create(): self
    {
        return new static([]);
    }

    public function merge(UriConstraints $uriConstraints): self
    {
        $mergedConstraints = array_merge($this->constraints, $uriConstraints->constraints);
        return new static($mergedConstraints);
    }

    public function withScheme(string $scheme): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_SCHEME] = $scheme;
        return new static($newConstraints);
    }

    public function withHost(string $host): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_HOST] = $host;
        return new static($newConstraints);
    }

    public function withSubDomain(string $subDomain): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_SUB_DOMAIN] = $subDomain;
        return new static($newConstraints);
    }

    public function withTopLevelDomain(string $topLevelDomain): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_TOP_LEVEL_DOMAIN] = $topLevelDomain;
        return new static($newConstraints);
    }

    public function withPort(int $port): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_PORT] = $port;
        return new static($newConstraints);
    }

    public function withPath(string $path): self
    {
        $newConstraints = $this->constraints;
        $newConstraints[self::CONSTRAINT_PATH] = $path;
        return new static($newConstraints);
    }

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

    public function withPathSuffix(string $pathSuffix): self
    {
        $newConstraints = $this->constraints;
        if (isset($newConstraints[self::CONSTRAINT_PATH_SUFFIX])) {
            $pathSuffix = $newConstraints[self::CONSTRAINT_PATH_SUFFIX] . $pathSuffix;
        }
        $newConstraints[self::CONSTRAINT_PATH_SUFFIX] = $pathSuffix;
        return new static($newConstraints);
    }

    public function getPathConstraint()
    {
        return $this->constraints[self::CONSTRAINT_PATH] ?? null;
    }

    public function applyTo(UriInterface $requestUri, bool $forceAbsoluteUri): UriInterface
    {
        $uri = new Uri('');
        if (isset($this->constraints[self::CONSTRAINT_SCHEME]) && $this->constraints[self::CONSTRAINT_SCHEME] !== $requestUri->getScheme()) {
            $forceAbsoluteUri = true;
            $uri = $uri->withScheme($this->constraints[self::CONSTRAINT_SCHEME]);
        }
        if (isset($this->constraints[self::CONSTRAINT_HOST]) && $this->constraints[self::CONSTRAINT_HOST] !== $requestUri->getHost()) {
            $forceAbsoluteUri = true;
            $uri = $uri->withHost($this->constraints[self::CONSTRAINT_HOST]);
        }
        if (isset($this->constraints[self::CONSTRAINT_SUB_DOMAIN])) {
            $requestSubDomain = $this->extractSubDomain($requestUri);
            if ($requestSubDomain !== $this->constraints[self::CONSTRAINT_SUB_DOMAIN]) {
                $forceAbsoluteUri = true;
                $host = !empty($uri->getHost()) ? $uri->getHost() : $requestUri->getHost();
                $host = preg_replace('/^([a-z0-9|-]+)(\.[a-z0-9|-]+\.[a-z]+)/', $this->constraints[self::CONSTRAINT_SUB_DOMAIN] . '$2', $host);
                $uri = $uri->withHost($host);
            }
        }
        if (isset($this->constraints[self::CONSTRAINT_TOP_LEVEL_DOMAIN])) {
            $requestTopLevelDomain = $this->extractTopLevelDomain($requestUri);
            if ($requestTopLevelDomain !== $this->constraints[self::CONSTRAINT_TOP_LEVEL_DOMAIN]) {
                $forceAbsoluteUri = true;
                $host = !empty($uri->getHost()) ? $uri->getHost() : $requestUri->getHost();
                $host = preg_replace('/\.([^\.]+)$/', '.' . $this->constraints[self::CONSTRAINT_SUB_DOMAIN], $host);
                $uri = $uri->withHost($host);
            }
        }
        if (isset($this->constraints[self::CONSTRAINT_PORT]) && $this->constraints[self::CONSTRAINT_PORT] !== $requestUri->getPort()) {
            $forceAbsoluteUri = true;
            $uri = $uri->withPort($this->constraints[self::CONSTRAINT_PORT]);
        }

        if (isset($this->constraints[self::CONSTRAINT_PATH]) && $this->constraints[self::CONSTRAINT_PATH] !== $requestUri->getPath()) {
            $uri = $uri->withPath($this->constraints[self::CONSTRAINT_PATH]);
        }
        if (isset($this->constraints[self::CONSTRAINT_PATH_PREFIX])) {
            $uri = $uri->withPath($this->constraints[self::CONSTRAINT_PATH_PREFIX] . $uri->getPath());
        }
        if (isset($this->constraints[self::CONSTRAINT_PATH_SUFFIX])) {
            $uri = $uri->withPath($uri->getPath() . $this->constraints[self::CONSTRAINT_PATH_PREFIX]);
        }

        if ($forceAbsoluteUri) {
            if (empty($uri->getScheme())) {
                $uri = $uri->withScheme($requestUri->getScheme());
            }
            if (empty($uri->getHost())) {
                $uri = $uri->withHost($requestUri->getHost());
            }
            if (empty($uri->getPort()) && $requestUri->getPort() !== null) {
                $uri = $uri->withPort($requestUri->getPort());
            }
        }

        return $uri;
    }

    private function extractSubDomain(UriInterface $uri): string
    {
        if (preg_match('/^([a-z0-9|-]+)\.[a-z0-9|-]+\.[a-z]+/', $uri->getHost(), $matches) !== 1) {
            // no sub domain
            return '';
        }
        return $matches[1];
    }

    private function extractTopLevelDomain(UriInterface $uri): string
    {
        if (preg_match('/\.([^\.]+)$/', $uri->getHost(), $matches) !== 1) {
            // no top level domain
            return '';
        }
        return $matches[1];
    }
}
