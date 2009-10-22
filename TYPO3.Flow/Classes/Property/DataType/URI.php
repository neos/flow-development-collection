<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property\DataType;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Represents a Unique Resource Identifier according to STD 66 / RFC 3986
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class URI {

	const PATTERN_MATCH_SCHEME = '/^[a-zA-Z][a-zA-Z0-9\+\-\.]*$/';
	const PATTERN_MATCH_USERNAME = '/^(?:[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';
	const PATTERN_MATCH_PASSWORD = '/^(?:[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';
	const PATTERN_MATCH_HOST = '/^[a-zA-Z0-9_~!&\',;=\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';
	const PATTERN_MATCH_PORT = '/^[0-9]*$/';
	const PATTERN_MATCH_PATH = '/^.*$/';
	const PATTERN_MATCH_FRAGMENT = '/^(?:[a-zA-Z0-9_~!&\',;=:@\/?\.\-\$\(\)\*\+]|(?:%[0-9a-fA-F]{2}))*$/';

	/**
	 * @var string The scheme / protocol of the locator, eg. http
	 */
	protected $scheme;

	/**
	 * @var string User name of a login, if any
	 */
	protected $username;

	/**
	 * @var string Password of a login, if any
	 */
	protected $password;

	/**
	 * @var string Host of the locator, eg. some.subdomain.example.com
	 */
	protected $host;

	/**
	 * @var integer Port of the locator, if any was specified. Eg. 80
	 */
	protected $port;

	/**
	 * @var string The hierarchical part of the URI, eg. /products/acme_soap
	 */
	protected $path;

	/**
	 * @var string Query string of the locator, if any. Eg. color=red&size=large
	 */
	protected $query;

	/**
	 * @var array Array representation of the URI query
	 */
	protected $arguments = array();

	/**
	 * @var string Fragment / anchor, if one was specified.
	 */
	protected $fragment;

	/**
	 * Constructs the URI object from a string
	 *
	 * @param string $URIString String representation of the URI
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct($URIString) {
		if (!is_string($URIString)) throw new \InvalidArgumentException('The URI must be a valid string.', 1176550571);

		$URIParts = parse_url($URIString);
		if (is_array($URIParts)) {
			$this->scheme = isset($URIParts['scheme']) ? $URIParts['scheme'] : NULL;
			$this->username = isset($URIParts['user']) ? $URIParts['user'] : NULL;
			$this->password = isset($URIParts['pass']) ? $URIParts['pass'] : NULL;
			$this->host = isset($URIParts['host']) ? $URIParts['host'] : NULL;
			$this->port = isset($URIParts['port']) ? $URIParts['port'] : NULL;
			$this->path = isset($URIParts['path']) ? $URIParts['path'] : NULL;
			if (isset($URIParts['query'])) {
				$this->setQuery ($URIParts['query']);
			}
			$this->fragment = isset($URIParts['fragment']) ? $URIParts['fragment'] : NULL;
		}
	}

	/**
	 * Returns the URI's scheme / protocol
	 *
	 * @return string URI scheme / protocol
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getScheme() {
		return $this->scheme;
	}

	/**
	 * Sets the URI's scheme / protocol
	 *
	 * @param  string $scheme The scheme. Allowed values are "http" and "https"
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setScheme($scheme) {
		if (preg_match(self::PATTERN_MATCH_SCHEME, $scheme) === 1) {
			$this->scheme = \F3\PHP6\Functions::strtolower($scheme);
		} else {
			throw new \InvalidArgumentException('"' . $scheme . '" is not a valid scheme.', 1184071237);
		}
	}

	/**
	 * Returns the username of a login
	 *
	 * @return string User name of the login
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * Sets the URI's username
	 *
	 * @param string $username User name of the login
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setUsername($username) {
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * Sets the URI's password
	 *
	 * @param string $password Password of the login
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setPassword($password) {
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * Sets the host(s) of the URI
	 *
	 * @param string $host The hostname(s)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setHost($host) {
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * Sets the port in the URI
	 *
	 * @param string $port The port number
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setPort($port) {
		if (preg_match(self::PATTERN_MATCH_PORT, $port) === 1) {
			$this->port = $port;
		} else {
			throw new \InvalidArgumentException('"' . $port . '" is not valid port number as part of a URI.', 1184071241);
		}
	}

	/**
	 * Returns the URI path
	 *
	 * @return string URI path
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Sets the path of the URI
	 *
	 * @param string $path The path
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setPath($path) {
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Sets the URI's query part. Updates (= overwrites) the arguments accordingly!
	 *
	 * @param string $query The query string.
	 * @return void
	 * @api
	 */
	public function setQuery($query) {
		$this->query = $query;
		parse_str($query, $this->arguments);
	}

	/**
	 * Returns the arguments from the URI's query part
	 *
	 * @return array Associative array of arguments and values of the URI's query part
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Returns the fragment / anchor, if any
	 *
	 * @return string The fragment
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getFragment() {
		return $this->fragment;
	}

	/**
	 * Sets the fragment in the URI
	 *
	 * @param string $fragment The fragment (aka "anchor")
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setFragment($fragment) {
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
	 * @author Robert Lemke	<robert@typo3.org>
	 * @api
	 */
	public function __toString() {
		$URIString = '';

		$URIString .= isset($this->scheme) ? $this->scheme . '://' : '';
		if (isset($this->username)) {
			if (isset($this->password)) {
				$URIString .= $this->username . ':' . $this->password . '@';
			} else {
				$URIString .= $this->username . '@';
			}
		}
		$URIString .= $this->host;
		$URIString .= isset($this->port) ? ':' . $this->port : '';
		if (isset($this->path)) {
			$URIString .= $this->path;
			$URIString .= isset($this->query) ? '?' . $this->query : '';
			$URIString .= isset($this->fragment) ? '#' . $this->fragment : '';
		}
		return $URIString;
	}
}

?>