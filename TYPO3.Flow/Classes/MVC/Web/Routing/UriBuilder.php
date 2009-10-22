<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing;

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
 * An URI Builder
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class UriBuilder {

	/**
	 * @var \F3\FLOW3\MVC\Web\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \F3\FLOW3\MVC\Web\Request
	 */
	protected $request;

	/**
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * Arguments which have been used for building the last URI
	 * @var array
	 */
	protected $lastArguments = array();

	/**
	 * @var string
	 */
	protected $section = '';

	/**
	 * @var boolean
	 */
	protected $createAbsoluteUri = FALSE;

	/**
	 * @var boolean
	 */
	protected $addQueryString = FALSE;

	/**
	 * @var array
	 */
	protected $argumentsToBeExcludedFromQueryString = array();

	/**
	 * @var string
	 */
	protected $format = '';

	/**
	 * Injects the Router
	 *
	 * @param \F3\FLOW3\MVC\Web\Routing\RouterInterface $router
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function injectRouter(\F3\FLOW3\MVC\Web\Routing\RouterInterface $router) {
		$this->router = $router;
	}

	/**
	 * Injects the environment
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Sets the current request
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request
	 * @return void
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRequest(\F3\FLOW3\MVC\RequestInterface $request) {
		$this->request = $request;
	}

	/**
	 * Gets the current request
	 *
	 * @return \F3\FLOW3\MVC\Web\Request
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Additional query parameters.
	 * If you want to "prefix" arguments, you can pass in multidimensional arrays:
	 * array('prefix1' => array('foo' => 'bar')) gets "&prefix1[foo]=bar"
	 *
	 * @param array $arguments
	 * @return \F3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArguments(array $arguments) {
		$this->arguments = $arguments;
		return $this;
	}

	/**
	 * @return array
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * If specified, adds a given HTML anchor to the URI (#...)
	 *
	 * @param string $section
	 * @return \F3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setSection($section) {
		$this->section = $section;
		return $this;
	}

	/**
	 * @return string
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getSection() {
		return $this->section;
	}

	/**
	 * Specifies the format of the target (e.g. "html" or "xml")
	 *
	 * @param string $format (e.g. "html" or "xml")
	 * @return \F3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setFormat($format) {
		$this->format = $format;
		return $this;
	}

	/**
	 * @return string
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * If set, the URI is prepended with the current base URI. Defaults to FALSE.
	 *
	 * @param boolean $createAbsoluteUri
	 * @return \F3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setCreateAbsoluteUri($createAbsoluteUri) {
		$this->createAbsoluteUri = (boolean)$createAbsoluteUri;
		return $this;
	}

	/**
	 * @return boolean
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCreateAbsoluteUri() {
		return $this->createAbsoluteUri;
	}

	/**
	 * If set, the current query parameters will be merged with $this->arguments. Defaults to FALSE.
	 *
	 * @param boolean $addQueryString
	 * @return \F3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setAddQueryString($addQueryString) {
		$this->addQueryString = (boolean)$addQueryString;
		return $this;
	}

	/**
	 * @return boolean
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getAddQueryString() {
		return $this->addQueryString;
	}

	/**
	 * A list of arguments to be excluded from the query parameters
	 * Only active if addQueryString is set
	 *
	 * @param array $argumentsToBeExcludedFromQueryString
	 * @return \F3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArgumentsToBeExcludedFromQueryString(array $argumentsToBeExcludedFromQueryString) {
		$this->argumentsToBeExcludedFromQueryString = $argumentsToBeExcludedFromQueryString;
		return $this;
	}

	/**
	 * @return array
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getArgumentsToBeExcludedFromQueryString() {
		return $this->argumentsToBeExcludedFromQueryString;
	}

	/**
	 * Returns the arguments being used for the last URI being built.
	 * This is only set after build() / uriFor() has been called.
	 *
	 * @return array The last arguments
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getLastArguments() {
		return $this->lastArguments;
	}

	/**
	 * Resets all UriBuilder options to their default value
	 *
	 * @return \F3\FLOW3\MVC\Web\Routing\UriBuilder the current UriBuilder to allow method chaining
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function reset() {
		$this->arguments = array();
		$this->section = '';
		$this->format = '';
		$this->createAbsoluteUri = FALSE;
		$this->addQueryString = FALSE;
		$this->argumentsToBeExcludedFromQueryString = array();

		return $this;
	}

	/**
	 * Creates an URI used for linking to an Controller action.
	 *
	 * @param string $actionName Name of the action to be called
	 * @param array $controllerArguments Additional query parameters. Will be merged with $this->arguments.
	 * @param string $controllerName Name of the target controller. If not set, current ControllerName is used.
	 * @param string $packageKey Name of the target package. If not set, current Package is used.
	 * @param string $subPackageKey Name of the target SubPackage. If not set, current SubPackage is used.
	 * @return string the rendered URI
	 * @api
	 * @see build()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriFor($actionName = NULL, $controllerArguments = array(), $controllerName = NULL, $packageKey = NULL, $subPackageKey = NULL) {
		if ($actionName !== NULL) {
			$controllerArguments['@action'] = $actionName;
		}
		if ($controllerName !== NULL) {
			$controllerArguments['@controller'] = $controllerName;
		} else {
			$controllerArguments['@controller'] = $this->request->getControllerName();
		}
		if ($packageKey === NULL) {
			$packageKey = $this->request->getControllerPackageKey();
		}
		$controllerArguments['@package'] = $packageKey;
		if (strlen($subPackageKey) === 0) {
			$subPackageKey = $this->request->getControllerSubpackageKey();
		}
		if (strlen($subPackageKey) > 0) {
			$controllerArguments['@subpackage'] = $subPackageKey;
		}
		if ($this->format !== '') {
			$controllerArguments['@format'] = $this->format;
		}
		$this->arguments = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->arguments, $controllerArguments);

		return $this->build();
	}

	/**
	 * Builds the URI
	 *
	 * @return string The URI
	 * @api
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function build() {
		$arguments = array();
		if ($this->addQueryString === TRUE) {
			$arguments = $this->request->getArguments();
			foreach($this->argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
				unset($arguments[$argumentToBeExcluded]);
			}
		}
		$arguments = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($arguments, $this->arguments);
		$uri = $this->router->resolve($arguments);
		$this->lastArguments = $arguments;
		if ($this->section !== '') {
			$uri .= '#' . $this->section;
		}
		if (!$this->environment->isRewriteEnabled()) {
			$uri = 'index.php/' . $uri;
		}
		if ($this->createAbsoluteUri === TRUE) {
			$uri = $this->request->getBaseURI() . $uri;
		}
		return $uri;
	}

}

?>