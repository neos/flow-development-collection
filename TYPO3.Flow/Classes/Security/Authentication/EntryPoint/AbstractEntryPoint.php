<?php
namespace TYPO3\FLOW3\Security\Authentication\EntryPoint;

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
 * An abstract authentication entry point.
 */
abstract class AbstractEntryPoint implements \TYPO3\FLOW3\Security\Authentication\EntryPointInterface {

	/**
	 * The configurations options
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Returns TRUE if the given request can be authenticated by the authentication provider
	 * represented by this entry point
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request The current request
	 * @return boolean TRUE if authentication is possible
	 */
	public function canForward(\TYPO3\FLOW3\MVC\RequestInterface $request) {
		return ($request instanceof \TYPO3\FLOW3\MVC\Web\Request);
	}

	/**
	 * Sets the options array
	 *
	 * @param array $options An array of configuration options
	 * @return void
	 */
	public function setOptions(array $options) {
		$this->options = $options;
	}

	/**
	 * Returns the options array
	 *
	 * @return array The configuration options of this entry point
	 */
	public function getOptions() {
		return $this->options;
	}

}
?>