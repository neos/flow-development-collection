<?php

declare(strict_types=1);

namespace Neos\Flow\Http;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;
use Psr\Http\Message\UriInterface;

/**
 * Decorator around a psr UriInterface.
 *
 * This adds important utility functions around the psr uri, which is otherwise hard to work with.
 */
final readonly class Uri implements UriInterface
{
    #[Flow\Autowiring(false)]
    private function __construct(
        private UriInterface $wrappedUri
    ) {
    }

    public static function decorate(UriInterface $uri): self
    {
        if ($uri instanceof self) {
            return $uri;
        }
        return new self($uri);
    }

    /**
     * Merges recursively into the current {@see getQuery} these additional query parameters.
     *
     * @param array $queryParameters
     * @return static A new instance with the additional query.
     */
    public function withAdditionalQueryParameters(array $queryParameters): self
    {
        if ($queryParameters === []) {
            return $this;
        }
        if ($this->wrappedUri->getQuery() === '') {
            $mergedQuery = $queryParameters;
        } else {
            $queryParametersFromUri = [];
            parse_str($this->wrappedUri->getQuery(), $queryParametersFromUri);
            $mergedQuery = Arrays::arrayMergeRecursiveOverrule($queryParametersFromUri, $queryParameters);
        }
        return new self($this->wrappedUri->withQuery(http_build_query($mergedQuery, '', '&')));
    }

    /** ------------------- aliased methods of UriInterface ------------------------ */

    /**
     * {@inheritdoc}
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->wrappedUri->getScheme();
    }

    /**
     * {@inheritdoc}
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        return $this->wrappedUri->getAuthority();
    }

    /**
     * {@inheritdoc}
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        return $this->wrappedUri->getUserInfo();
    }

    /**
     * {@inheritdoc}
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->wrappedUri->getHost();
    }

    /**
     * {@inheritdoc}
     * @return null|int The URI port.
     */
    public function getPort()
    {
        return $this->wrappedUri->getPort();
    }

    /**
     * {@inheritdoc}
     * @return string The URI path.
     */
    public function getPath()
    {
        return $this->wrappedUri->getPath();
    }

    /**
     * {@inheritdoc}
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return $this->wrappedUri->getQuery();
    }

    /**
     * {@inheritdoc}
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->wrappedUri->getFragment();
    }

    /**
     * {@inheritdoc}
     * @param string $scheme The scheme to use with the new instance.
     * @return static A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme(string $scheme)
    {
        return new self($this->wrappedUri->withScheme($scheme));
    }

    /**
     * {@inheritdoc}
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return static A new instance with the specified user information.
     */
    public function withUserInfo(string $user, ?string $password = null)
    {
        return new self($this->wrappedUri->withUserInfo($user, $password));
    }

    /**
     * {@inheritdoc}
     * @param string $host The hostname to use with the new instance.
     * @return static A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost(string $host)
    {
        return new self($this->wrappedUri->withHost($host));
    }

    /**
     * {@inheritdoc}
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return static A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort(?int $port)
    {
        return new self($this->wrappedUri->withPort($port));
    }

    /**
     * {@inheritdoc}
     * @param string $path The path to use with the new instance.
     * @return static A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath(string $path)
    {
        return new self($this->wrappedUri->withPath($path));
    }

    /**
     * {@inheritdoc}
     * @deprecated you should rather use {@see self::withAdditionalQueryParameters}
     * @param string $query The query string to use with the new instance.
     * @return static A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery(string $query)
    {
        return new self($this->wrappedUri->withQuery($query));
    }

    /**
     * {@inheritdoc}
     * @param string $fragment The fragment to use with the new instance.
     * @return static A new instance with the specified fragment.
     */
    public function withFragment(string $fragment)
    {
        return new self($this->wrappedUri->withFragment($fragment));
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function __toString()
    {
        return $this->wrappedUri->__toString();
    }
}
