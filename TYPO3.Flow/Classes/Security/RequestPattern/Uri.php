<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\RequestPattern;

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
 * This class holds an URI pattern an decides, if a \F3\FLOW3\MVC\Web\Request object matches against this pattern
 * Note: This pattern can only be used for web requests.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Uri implements \F3\FLOW3\Security\RequestPatternInterface {

	/**
	 * The preg_match() styled URI pattern
	 * @var string
	 */
	protected $uriPattern = '';

	/**
	 * Returns TRUE, if this pattern can match against the given request object.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if this pattern can match
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canMatch(\F3\FLOW3\MVC\RequestInterface $request) {
		if ($request instanceof \F3\FLOW3\MVC\Web\Request) return TRUE;
		return FALSE;
	}

	/**
	 * Returns the set pattern.
	 *
	 * @return string The set pattern
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setPattern($uriPattern) {
		$this->uriPattern = str_replace('/', '\/', $uriPattern);
	}

	/**
	 * Matches a \F3\FLOW3\MVC\RequestInterface against its set URL pattern rules
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 * @throws \F3\FLOW3\Security\Exception\RequestTypeNotSupportedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchRequest(\F3\FLOW3\MVC\RequestInterface $request) {
		if (!($request instanceof \F3\FLOW3\MVC\Web\Request)) throw new \F3\FLOW3\Security\Exception\RequestTypeNotSupportedException('The given request type is not supported.', 1216903641);

		return (boolean)preg_match('/^' . $this->uriPattern . '$/', $request->getRequestUri()->getPath());
	}
}

?>