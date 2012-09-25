<?php
namespace TYPO3\Flow\Security\RequestPattern;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * This class holds an URI pattern an decides, if a \TYPO3\Flow\Mvc\ActionRequest object matches against this pattern
 *
 */
class Uri implements \TYPO3\Flow\Security\RequestPatternInterface {

	/**
	 * The preg_match() styled URI pattern
	 * @var string
	 */
	protected $uriPattern = '';

	/**
	 * Returns the set pattern.
	 *
	 * @return string The set pattern
	 */
	public function getPattern() {
		return str_replace('\/', '/', $this->uriPattern);
	}

	/**
	 * Sets an URI pattern (preg_match() syntax)
	 *
	 * Note: the pattern is a full-on regular expression pattern. The only
	 * thing that is touched by the code: forward slashes are escaped before
	 * the pattern is used.
	 *
	 * @param string $uriPattern The preg_match() styled URL pattern
	 * @return void
	 */
	public function setPattern($uriPattern) {
		$this->uriPattern = str_replace('/', '\/', $uriPattern);
	}

	/**
	 * Matches a \TYPO3\Flow\Mvc\RequestInterface against its set URL pattern rules
	 *
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 */
	public function matchRequest(\TYPO3\Flow\Mvc\RequestInterface $request) {
		return (boolean)preg_match('/^' . $this->uriPattern . '$/', $request->getHttpRequest()->getUri()->getPath());
	}
}

?>
