<?php
namespace TYPO3\FLOW3\Mvc\Controller;

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
 * A generic Controller exception
 *
 * @api
 */
class Exception extends \TYPO3\FLOW3\Exception {

	/**
	 * @var \TYPO3\FLOW3\Mvc\RequestInterface
	 */
	protected $request;

	/**
	 * Overwrites parent constructor to be able to inject current request object.
	 *
	 * @param string $message
	 * @param integer $code
	 * @param \Exception $previousException
	 * @param \TYPO3\FLOW3\Mvc\RequestInterface $request
	 * @see \Exception
	 */
	public function __construct($message = '', $code = 0, \Exception $previousException = NULL, \TYPO3\FLOW3\Mvc\RequestInterface $request) {
		$this->request = $request;
		parent::__construct($message, $code, $previousException);
	}

	/**
	 * Returns the request object that exception belongs to.
	 *
	 * @return \TYPO3\FLOW3\Mvc\RequestInterface
	 */
	protected function getRequest() {
		return $this->request;
	}

}

?>