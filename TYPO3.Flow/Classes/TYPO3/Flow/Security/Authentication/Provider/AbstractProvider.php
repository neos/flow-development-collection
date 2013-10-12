<?php
namespace TYPO3\Flow\Security\Authentication\Provider;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An abstract authentication provider.
 */
abstract class AbstractProvider implements \TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * Constructor
	 *
	 * @param string $name The name of this authentication provider
	 * @param array $options Additional configuration options
	 */
	public function __construct($name, array $options = array()) {
		$this->name = $name;
		$this->options = $options;
	}

	/**
	 * Returns TRUE if the given token can be authenticated by this provider
	 *
	 * @param \TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken The token that should be authenticated
	 * @return boolean TRUE if the given token class can be authenticated by this provider
	 */
	public function canAuthenticate(\TYPO3\Flow\Security\Authentication\TokenInterface $authenticationToken) {
		if ($authenticationToken->getAuthenticationProviderName() === $this->name) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

}
