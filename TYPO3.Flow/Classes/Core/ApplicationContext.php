<?php
namespace TYPO3\FLOW3\Core;

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
 * The FLOW3 Context object.
 *
 * A FLOW3 Application context is something like "Production", "Development",
 * "Production/StagingSystem", and is set using the FLOW3_CONTEXT environment variable.
 *
 * A context can contain arbitrary sub-contexts, which are delimited with slash
 * ("Production/StagingSystem", "Production/Staging/Server1"). The top-level
 * contexts, however, must be one of "Testing", "Development" and "Production".
 *
 * Mainly, you will use $context->isProduction(), $context->isTesting() and
 * $context->isDevelopment() inside your custom code.
 *
 * @api
 * @FLOW3\Proxy(false)
 */
class ApplicationContext {

	/**
	 * The (internal) context string; could be something like "Development" or "Development/MyLocalMacBook"
	 *
	 * @var string
	 */
	protected $contextString;

	/**
	 * The root context; must be one of "Development", "Testing" or "Production"
	 *
	 * @var string
	 */
	protected $rootContextString;

	/**
	 * The parent context, or NULL if there is no parent context
	 *
	 * @var TYPO3\FLOW3\Core\ApplicationContext
	 */
	protected $parentContext;

	/**
	 * Initialize the context object.
	 *
	 * @param string $contextString
	 * @throws \TYPO3\FLOW3\Exception if the parent context is none of "Development", "Production" or "Testing"
	 */
	public function __construct($contextString) {
		if (strstr($contextString, '/') === FALSE) {
			$this->rootContextString = $contextString;
			$this->parentContext = NULL;
		} else {
			$contextStringParts = explode('/', $contextString);
			$this->rootContextString = $contextStringParts[0];
			array_pop($contextStringParts);
			$this->parentContext = new ApplicationContext(implode('/', $contextStringParts));
		}

		if (!in_array($this->rootContextString, array('Development', 'Production', 'Testing'))) {
			throw new \TYPO3\FLOW3\Exception('The given context "' . $contextString . '" was not valid. Only allowed are Development, Production and Testing, including their sub-contexts', 1335436551);
		}

		$this->contextString = $contextString;
	}

	/**
	 * Returns the full context string, for example "Development", or "Production/LiveSystem"
	 *
	 * @return string
	 * @api
	 */
	public function __toString() {
		return $this->contextString;
	}

	/**
	 * Returns TRUE if this context is the Development context or a sub-context of it
	 *
	 * @return boolean
	 * @api
	 */
	public function isDevelopment() {
		return ($this->rootContextString === 'Development');
	}

	/**
	 * Returns TRUE if this context is the Production context or a sub-context of it
	 *
	 * @return boolean
	 * @api
	 */

	public function isProduction() {
		return ($this->rootContextString === 'Production');
	}

	/**
	 * Returns TRUE if this context is the Testing context or a sub-context of it
	 *
	 * @return boolean
	 * @api
	 */
	public function isTesting() {
		return ($this->rootContextString === 'Testing');
	}

	/**
	 * Returns the parent context object, if any
	 *
	 * @return TYPO3\FLOW3\Core\ApplicationContext the parent context or NULL, if there is none
	 * @api
	 */
	public function getParent() {
		return $this->parentContext;
	}
}
?>