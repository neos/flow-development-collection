<?php
namespace TYPO3\FLOW3\Http;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Container for HTTP header fields
 *
 * @api
 */
class Headers {

	/**
	 * @var array
	 */
	protected $fields = array();

	/**
	 * @var array
	 */
	protected $cookies = array();

	/**
	 * Constructs a new Headers object.
	 *
	 * @param array $fields Field names and their values (either as single value or array of values)
	 */
	public function __construct(array $fields = array()) {
		foreach ($fields as $name => $values) {
			$this->set($name, $values);
		}
	}

	/**
	 * Creates a new Headers instance from the given $_SERVER-superglobal-like array.
	 *
	 * @param array $server An array similar or equal to $_SERVER, containing headers in the form of "HTTP_FOO_BAR"
	 * @return \TYPO3\FLOW3\Http\Headers
	 */
	static public function createFromServer(array $server) {
		$headerFields = array();
		if (isset($server['PHP_AUTH_USER']) && isset($server['PHP_AUTH_PW'])) {
			$headerFields['Authorization'] = 'Basic ' . base64_encode($server['PHP_AUTH_USER'] . ':' . $server['PHP_AUTH_PW']);
		}

		foreach($server as $name => $value) {
			if (strpos($name, 'HTTP_') === 0) {
				$name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
				$headerFields[$name] = $value;
			} elseif ($name == 'REDIRECT_REMOTE_AUTHORIZATION' && !isset($headerFields['Authorization'])) {
				$headerFields['Authorization'] = $value;
			} elseif (in_array($name, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
				$name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
				$headerFields[$name] = $value;
			}
		}
		return new self($headerFields);
	}

	/**
	 * Sets the specified HTTP header
	 *
	 * @param string $name Name of the header, for example "Location", "Content-Description" etc.
	 * @param array|string|\DateTime $values An array of values or a single value for the specified header field
	 * @param boolean $replaceExistingHeader If a header with the same name should be replaced. Default is TRUE.
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function set($name, $values, $replaceExistingHeader = TRUE) {
		if (strtoupper(substr($name, 0, 4)) === 'HTTP') {
			throw new \InvalidArgumentException('The "HTTP" status header must be set via setStatus().', 1220541963);
		}

		if ($values instanceof \DateTime) {
			$values = array($values->format(DATE_RFC2822));
		} else {
			$values = (array) $values;
		}
		if ($replaceExistingHeader === TRUE || !isset($this->fields[$name])) {
			$this->fields[$name] = $values;
		} else {
			$this->fields[$name] = array_merge($this->fields[$name], $values);
		}
	}

	/**
	 * Returns the specified HTTP header
	 *
	 * @param string $name Name of the header, for example "Location", "Content-Description" etc.
	 * @return array|string An array of field values if multiple headers of that name exist, a string value if only one value exists and NULL if there is no such header.
	 * @api
	 */
	public function get($name) {
		if (!isset($this->fields[$name])) {
			return NULL;
		}

		$convertedValues = array();
		foreach ($this->fields[$name] as $index => $value) {
			$convertedValues[$index] = \DateTime::createFromFormat(DATE_RFC2822, $value);
			if ($convertedValues[$index] === FALSE) {
				$convertedValues[$index] = $value;
			}
		}

		return (count($convertedValues) > 1) ? $convertedValues : reset($convertedValues);
	}

	/**
	 * Returns all header fields
	 *
	 * Note that even for those header fields which exist only one time, the value is
	 * returned as an array (with a single item).
	 *
	 * @return array
	 * @api
	 */
	public function getAll() {
		return $this->fields;
	}

	/**
	 * Checks if the specified HTTP header exists
	 *
	 * @param string $name Name of the header
	 * @return boolean
	 * @api
	 */
	public function has($name) {
		return isset($this->fields[$name]);
	}

	/**
	 * Removes the specified header field
	 *
	 * @param string $name Name of the field
	 * @return void
	 * @api
	 */
	public function remove($name) {
		unset($this->fields[$name]);
	}

	/**
	 * Sets a cookie
	 *
	 * @param \TYPO3\FLOW3\Http\Cookie $cookie
	 * @return void
	 * @api
	 */
	public function setCookie(Cookie $cookie) {
		$this->cookies[$cookie->getName()] = $cookie;
	}

	/**
	 * Returns a cookie specified by the given name
	 *
	 * @param string $name Name of the cookie
	 * @return \TYPO3\FLOW3\Http\Cookie The cookie or NULL if no such cookie exists
	 * @api
	 */
	public function getCookie($name) {
		return isset($this->cookies[$name]) ? $this->cookies[$name] : NULL;
	}

	/**
	 * Returns all cookies
	 *
	 * @return array
	 * @api
	 */
	public function getCookies() {
		return $this->cookies;
	}

	/**
	 * Checks if the specified cookie exists
	 *
	 * @param string $name Name of the cookie
	 * @return boolean
	 * @api
	 */
	public function hasCookie($name) {
		return isset($this->cookies[$name]);
	}

	/**
	 * Removes the specified cookie if it exists
	 *
	 * @param string $name Name of the cookie to remove
	 * @return void
	 * @api
	 */
	public function removeCookie($name) {
		unset ($this->cookies[$name]);
	}

	/**
	 * Although not 100% semantically correct, an alias for removeCookie()
	 *
	 * @param string $name Name of the cookie to eat
	 * @return void
	 * @api
	 */
	public function eatCookie($name) {
		$this->removeCookie($name);
	}

}

?>