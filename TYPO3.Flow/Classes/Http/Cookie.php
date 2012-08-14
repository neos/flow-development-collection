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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Represents a HTTP Cookie as of RFC 2965
 *
 * @api
 * @see http://tools.ietf.org/html/rfc2965
 * @FLOW3\Proxy(false)
 */
class Cookie {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $value;

	/**
	 * @var integer
	 */
	protected $expire;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $domain;

	/**
	 * @var boolean
	 */
	protected $secure;

	/**
	 * @var boolean
	 */
	protected $httpOnly;

	/**
	 * @param string $name
	 * @param string $value
	 * @param integer $expire
	 * @param string $path
	 * @param string $domain
	 * @param boolean $secure
	 * @param boolean $httpOnly
	 */
	public function __construct($name, $value = NULL, $expire = 0, $path = '/', $domain = NULL, $secure = FALSE, $httpOnly = TRUE) {
		$this->name = $name;
	}

	/**
	 * Returns the name of this cookie
	 *
	 * @return string The name
	 * @api
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getDomain() {
		return $this->domain;
	}

	/**
	 * @return int
	 */
	public function getExpire() {
		return $this->expire;
	}

	/**
	 * @return boolean
	 */
	public function getHttpOnly() {
		return $this->httpOnly;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return boolean
	 */
	public function getSecure() {
		return $this->secure;
	}

	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}

}

?>