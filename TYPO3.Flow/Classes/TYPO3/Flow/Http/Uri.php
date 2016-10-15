<?php
namespace TYPO3\Flow\Http;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error as FlowError;
use TYPO3\Flow\Utility\Unicode;

/**
 * Represents a Unique Resource Identifier according to STD 66 / RFC 3986
 *
 * @api
 * @Flow\Proxy(false)
 */
class Uri
{
    const PATTERN_MATCH_SCHEME = '/^[a-zA-Z][a-zA-Z0-9\+\-\.]*$/';
    const PATTERN_MATCH_USERNAME = '/^(?:[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';
    const PATTERN_MATCH_PASSWORD = '/^(?:[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';
    const PATTERN_MATCH_HOST = '/^(?:[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';
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
     */
    protected $arguments = [];

    /**
     * Fragment / anchor, if one was specified.
     * @var string
     */
    protected $fragment;

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

        $parseUrlException = null;
        try {
            $uriParts = Unicode\Functions::parse_url($uriString);
        } catch (FlowError\Exception $exception) {
            $parseUrlException = $exception;
        }
        if (is_array($uriParts)) {
            $this->scheme = isset($uriParts['scheme']) ? $uriParts['scheme'] : null;
            $this->username = isset($uriParts['user']) ? $uriParts['user'] : null;
            $this->password = isset($uriParts['pass']) ? $uriParts['pass'] : null;
            $this->host = isset($uriParts['host']) ? $uriParts['host'] : null;
            $this->port = isset($uriParts['port']) ? $uriParts['port'] : null;
            if ($this->port === null) {
                switch ($this->scheme) {
                    case 'http':
                        $this->port = 80;
                    break;
                    case 'https':
                        $this->port = 443;
                    break;
                }
            }
            $this->path = isset($uriParts['path']) ? $uriParts['path'] : null;
            if (isset($uriParts['query'])) {
                $this->setQuery($uriParts['query']);
            }
            $this->fragment = isset($uriParts['fragment']) ? $uriParts['fragment'] : null;
        } else {
            throw new \InvalidArgumentException('The given URI "' . $uriString . '" is not a valid one.', 1351594202, $parseUrlException);
        }
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
     * @api
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
     * @api
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
     * @api
     */
    public function setUsername($username)
    {
        if (preg_match(self::PATTERN_MATCH_USERNAME, $username) === 1) {
            $this->username = $username;
        } else {
            throw new \InvalidArgumentException('"' . $username . '" is not a valid username.', 1184071238);
        }
    }

    /**
     * Returns the password of a login
     *
     * @return string Password of the login
     * @api
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
     * @api
     */
    public function setPassword($password)
    {
        if (preg_match(self::PATTERN_MATCH_PASSWORD, $password) === 1) {
            $this->password = $password;
        } else {
            throw new \InvalidArgumentException('The specified password is not valid as part of a URI.', 1184071239);
        }
    }

    /**
     * Returns the host(s) of the URI
     *
     * @return string The hostname(s)
     * @api
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the host(s) of the URI
     *
     * @param string $host The hostname(s)
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function setHost($host)
    {
        if (preg_match(self::PATTERN_MATCH_HOST, $host) === 1) {
            $this->host = $host;
        } else {
            throw new \InvalidArgumentException('"' . $host . '" is not valid host as part of a URI.', 1184071240);
        }
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
     * @api
     */
    public function setPort($port)
    {
        if (preg_match(self::PATTERN_MATCH_PORT, $port) === 1) {
            $this->port = (integer)$port;
        } else {
            throw new \InvalidArgumentException('"' . $port . '" is not valid port number as part of a URI.', 1184071241);
        }
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
     * @api
     */
    public function setPath($path)
    {
        if (preg_match(self::PATTERN_MATCH_PATH, $path) === 1) {
            $this->path = $path;
        } else {
            throw new \InvalidArgumentException('"' . $path . '" is not valid path as part of a URI.', 1184071242);
        }
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
     * @api
     */
    public function setQuery($query)
    {
        $this->query = $query;
        parse_str($query, $this->arguments);
    }

    /**
     * Returns the arguments from the URI's query part
     *
     * @return array Associative array of arguments and values of the URI's query part
     * @api
     */
    public function getArguments()
    {
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
     * @api
     */
    public function setFragment($fragment)
    {
        if (preg_match(self::PATTERN_MATCH_FRAGMENT, $fragment) === 1) {
            $this->fragment = $fragment;
        } else {
            throw new \InvalidArgumentException('"' . $fragment . '" is not valid fragment as part of a URI.', 1184071252);
        }
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
        if (isset($this->username)) {
            if (isset($this->password)) {
                $uriString .= $this->username . ':' . $this->password . '@';
            } else {
                $uriString .= $this->username . '@';
            }
        }
        $uriString .= $this->host;
        if ($this->port !== null) {
            switch ($this->scheme) {
                case 'http':
                    $uriString .= ($this->port !== 80 ? ':' . $this->port : '');
                    break;
                case 'https':
                    $uriString .= ($this->port !== 443 ? ':' . $this->port : '');
                    break;
                default:
                    $uriString .= (isset($this->port) ? ':' . $this->port : '');
            }
        }
        $uriString .= isset($this->path) ? $this->path : '';
        $uriString .= isset($this->query) ? '?' . $this->query : '';
        $uriString .= isset($this->fragment) ? '#' . $this->fragment : '';
        return $uriString;
    }
}
