<?php
namespace Neos\Flow\Http;

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
use Neos\Flow\Error\Exception as ErrorException;
use Neos\Flow\Http\Helper\UriHelper;
use Neos\Utility\Unicode;
use Psr\Http\Message\UriInterface;

/**
 * Represents a Unique Resource Identifier according to STD 66 / RFC 3986
 *
 * @api
 * @Flow\Proxy(false)
 */
class Uri implements UriInterface
{
    const PATTERN_MATCH_SCHEME = '/^[a-zA-Z][a-zA-Z0-9\+\-\.]*$/';
    const PATTERN_MATCH_USERNAME = '/^(?:[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';
    const PATTERN_MATCH_PASSWORD = '/^(?:[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';
    const PATTERN_MATCH_HOST = '/^(?:(?:[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*|\[[a-f0-9:]+\])$/';
    const PATTERN_MATCH_PORT = '/^[0-9]*$/';
    const PATTERN_MATCH_PATH = '/^.*$/';
    const PATTERN_MATCH_FRAGMENT = '/^(?:[a-zA-Z0-9_~!&\',;=:@\/?\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';

    /**
     * The scheme / protocol of the locator, eg. http
     * @var string
     */
    protected $scheme;

    /**
     * User name of a login, if any
     * @var string
     */
    protected $username;

    /**
     * Password of a login, if any
     * @var string
     */
    protected $password;

    /**
     * Host of the locator, eg. some.subdomain.example.com
     * @var string
     */
    protected $host;

    /**
     * Port of the locator, if any was specified. Eg. 80
     * @var integer
     */
    protected $port = 80;

    /**
     * The hierarchical part of the URI, eg. /products/acme_soap
     * @var string
     */
    protected $path;

    /**
     * Query string of the locator, if any. Eg. color=red&size=large
     * @var string
     */
    protected $query;

    /**
     * Array representation of the URI query
     * @var array
     * @deprecated Since Flow 5.1, can be removed together with the respective method.
     */
    protected $arguments;

    /**
     * Fragment / anchor, if one was specified.
     * @var string
     */
    protected $fragment;

    /**
     * @var array
     */
    protected $defaultPortsForScheme = [
        'http' => 80,
        'https' => 443
    ];

    /**
     * Constructs the URI object from a string
     *
     * @param string $uriString String representation of the URI
     * @throws \InvalidArgumentException
     * @api
     */
    public function __construct($uriString)
    {
        if (!is_string($uriString)) {
            throw new \InvalidArgumentException('The URI must be a valid string.', 1176550571);
        }

        $uriParts = null;
        $parseUrlException = null;

        try {
            $uriParts = Unicode\Functions::parse_url($uriString);
        } catch (ErrorException $exception) {
            $parseUrlException = $exception;
        }

        if (!is_array($uriParts)) {
            throw new \InvalidArgumentException('The given URI "' . $uriString . '" is not a valid one.', 1351594202, $parseUrlException);
        }

        $this->scheme = $uriParts['scheme'] ?? null;
        $this->username = $uriParts['user'] ?? null;
        $this->password = $uriParts['pass'] ?? null;
        $this->host = $uriParts['host'] ?? null;
        $this->port = $uriParts['port'] ?? null;
        if ($this->port === null) {
            $this->port = $this->resolveDefaultPortForScheme($this->scheme);
        }
        $this->path = $uriParts['path'] ?? null;
        if (isset($uriParts['query'])) {
            $this->query = $uriParts['query'];
        }
        $this->fragment = $uriParts['fragment'] ?? null;
    }

    /**
     * Returns the URI's scheme / protocol
     *
     * @return string URI scheme / protocol
     * @api
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Sets the URI's scheme / protocol
     *
     * @param  string $scheme The scheme. Allowed values are "http" and "https"
     * @return void
     * @throws \InvalidArgumentException
     * @deprecated Since Flow 5.1, use withScheme
     * @see withScheme()
     */
    public function setScheme($scheme)
    {
        if (preg_match(self::PATTERN_MATCH_SCHEME, $scheme) === 1) {
            $this->scheme = strtolower($scheme);
        } else {
            throw new \InvalidArgumentException('"' . $scheme . '" is not a valid scheme.', 1184071237);
        }
    }

    /**
     * Returns the username of a login
     *
     * @return string User name of the login
     * @deprecated Since Flow 5.1, use getUserInfo and extract via UriHelper::getUsername()
     * @see getUserInfo()
     * @see UriHelper::getUsername()
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the URI's username
     *
     * @param string $username User name of the login
     * @return void
     * @throws \InvalidArgumentException
     * @deprecated Since Flow 5.1, use withUserInfo instead
     * @see withUserInfo()
     */
    public function setUsername($username)
    {
        if (preg_match(self::PATTERN_MATCH_USERNAME, $username) !== 1) {
            throw new \InvalidArgumentException('"' . $username . '" is not a valid username.', 1184071238);
        }

        $this->username = $username;
    }

    /**
     * Returns the password of a login
     *
     * @return string Password of the login
     * @deprecated Since Flow 5.1, use getUserInfo instead and extract via UriHelper::getPassword()
     * @see getUserInfo()
     * @see UriHelper::getPassword()
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Sets the URI's password
     *
     * @param string $password Password of the login
     * @return void
     * @throws \InvalidArgumentException
     * @deprecated Since Flow 5.1, use withUserInfo instead
     * @see withUserInfo()
     */
    public function setPassword($password)
    {
        if (preg_match(self::PATTERN_MATCH_PASSWORD, $password) !== 1) {
            throw new \InvalidArgumentException('The specified password is not valid as part of a URI.', 1184071239);
        }

        $this->password = $password;
    }

    /**
     * Returns the host(s) of the URI
     *
     * @return string The hostname(s)
     * @api
     */
    public function getHost()
    {
        return trim($this->host);
    }

    /**
     * Sets the host(s) of the URI
     *
     * @param string $host The hostname(s)
     * @return void
     * @throws \InvalidArgumentException
     * @deprecated Since Flow 5.1, use withHost instead
     * @see withHost()
     */
    public function setHost($host)
    {
        if (preg_match(self::PATTERN_MATCH_HOST, $host) !== 1) {
            throw new \InvalidArgumentException('"' . $host . '" is not valid host as part of a URI.', 1184071240);
        }

        $this->host = $host;
    }

    /**
     * Returns the port of the URI
     *
     * @return integer Port
     * @api
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Sets the port in the URI
     *
     * @param string $port The port number
     * @return void
     * @throws \InvalidArgumentException
     * @deprecated Since Flow 5.1, use withPort instead
     * @see withPort()
     */
    public function setPort($port)
    {
        if (preg_match(self::PATTERN_MATCH_PORT, $port) !== 1) {
            throw new \InvalidArgumentException('"' . $port . '" is not valid port number as part of a URI.', 1184071241);
        }

        $this->port = (integer)$port;
    }

    /**
     * Returns the URI path
     *
     * @return string URI path
     * @api
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets the path of the URI
     *
     * @param string $path The path
     * @return void
     * @throws \InvalidArgumentException
     * @deprecated Since Flow 5.1, use withPath instead
     * @see withPath()
     */
    public function setPath($path)
    {
        if (preg_match(self::PATTERN_MATCH_PATH, $path) !== 1) {
            throw new \InvalidArgumentException('"' . $path . '" is not valid path as part of a URI.', 1184071242);
        }

        $this->path = $path;
    }

    /**
     * Returns the URI's query part
     *
     * @return string The query part
     * @api
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Sets the URI's query part. Updates (= overwrites) the arguments accordingly!
     *
     * @param string $query The query string.
     * @return void
     * @deprecated Since Flow 5.1, use withQuery instead
     * @see withQuery()
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * Returns the arguments from the URI's query part
     *
     * @return array Associative array of arguments and values of the URI's query part
     * @deprecated Since Flow 5.1, use UriHelper::parseQueryIntoArguments
     * @see UriHelper::parseQueryIntoArguments()
     */
    public function getArguments()
    {
        if ($this->arguments === null) {
            $this->arguments = UriHelper::parseQueryIntoArguments($this);
        }
        return $this->arguments;
    }

    /**
     * Returns the fragment / anchor, if any
     *
     * @return string The fragment
     * @api
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Sets the fragment in the URI
     *
     * @param string $fragment The fragment (aka "anchor")
     * @return void
     * @throws \InvalidArgumentException
     * @deprecated Since Flow 5.1, use withFragment instead
     * @see withFragment()
     */
    public function setFragment($fragment)
    {
        if (preg_match(self::PATTERN_MATCH_FRAGMENT, $fragment) !== 1) {
            throw new \InvalidArgumentException('"' . $fragment . '" is not valid fragment as part of a URI.', 1184071252);
        }

        $this->fragment = $fragment;
    }

    /**
     * @return string
     */
    protected function getHostAndOptionalPort()
    {
        $hostAndPort = (string)$this->host;
        if ($this->port === null) {
            return $hostAndPort;
        }

        $defaultPort = $this->defaultPortsForScheme[$this->scheme] ?? '';
        $hostAndPort .= ($this->port !== $defaultPort ? ':' . $this->port : '');

        return $hostAndPort;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     * @api PSR-7
     */
    public function getAuthority()
    {
        $result = '';

        $host = $this->getHostAndOptionalPort();
        if (empty($host)) {
            return $result;
        }
        $result = $host;

        $userInfo = $this->getUserInfo();
        if (empty($userInfo)) {
            return $result;
        }
        $result = $userInfo . '@' . $result;

        return $result;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     * @api PSR-7
     */
    public function getUserInfo()
    {
        $result = '';
        $username = $this->username;

        if (empty($username)) {
            return $result;
        }

        $result .= $username;

        $password = $this->password;
        if (empty($password)) {
            return $result;
        }

        $result .= ':' . $password;
        return $result;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return self A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     * @api PSR-7
     */
    public function withScheme($scheme)
    {
        if (preg_match(self::PATTERN_MATCH_SCHEME, $scheme) !== 1) {
            throw new \InvalidArgumentException('"' . $scheme . '" is not a valid scheme.', 1184071237);
        }

        $newUri = clone $this;
        $newUri->scheme = strtolower($scheme);
        return $newUri;
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return self A new instance with the specified user information.
     * @api PSR-7
     */
    public function withUserInfo($user, $password = null)
    {
        $newUri = clone $this;
        $newUri->username = $user;
        $newUri->password = $password;
        return $newUri;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return self A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     * @api PSR-7
     */
    public function withHost($host)
    {
        if (preg_match(self::PATTERN_MATCH_HOST, $host) !== 1) {
            throw new \InvalidArgumentException('"' . $host . '" is not valid host as part of a URI.', 1184071240);
        }
        $newUri = clone $this;
        $newUri->host = $host;
        return $newUri;
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return self A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     * @api PSR-7
     */
    public function withPort($port = null)
    {
        if (preg_match(self::PATTERN_MATCH_PORT, $port) !== 1) {
            throw new \InvalidArgumentException('"' . $port . '" is not valid port number as part of a URI.', 1184071241);
        }
        $newUri = clone $this;
        $newUri->port = ($port !== null ? (int)$port : null);
        return $newUri;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     * @return self A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     * @api PSR-7
     */
    public function withPath($path)
    {
        $newUri = clone $this;
        $newUri->path = $path;
        return $newUri;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return self A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     * @api PSR-7
     */
    public function withQuery($query)
    {
        $newUri = clone $this;
        $newUri->arguments = null;
        $newUri->query = $query;
        return $newUri;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return self A new instance with the specified fragment.
     * @api PSR-7
     */
    public function withFragment($fragment)
    {
        $newUri = clone $this;
        $newUri->fragment = $fragment;
        return $newUri;
    }

    /**
     * @param string $scheme
     * @return integer
     */
    private function resolveDefaultPortForScheme($scheme)
    {
        return $this->defaultPortsForScheme[$scheme] ?? null;
    }

    /**
     * Returns a string representation of this URI
     *
     * @return string This URI as a string
     * @api
     */
    public function __toString()
    {
        $uriString = '';

        $uriString .= isset($this->scheme) ? $this->scheme . '://' : '';
        $uriString .= $this->getAuthority();
        $uriString .= !empty($this->path) ? $this->path : '';
        $uriString .= !empty($this->query) ? '?' . $this->query : '';
        $uriString .= !empty($this->fragment) ? '#' . $this->fragment : '';
        return $uriString;
    }
}
