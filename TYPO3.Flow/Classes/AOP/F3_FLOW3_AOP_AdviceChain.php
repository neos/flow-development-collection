<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 */

/**
 * The advice chain holds a number of subsequent advices that
 * match a given join point and calls the advices in the right order.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\AdviceChain.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AdviceChain {

	/**
	 * @var array An array of \F3\FLOW3\AOP\Advice objects which form the advice chain
	 */
	protected $advices;

	/**
	 * @var integer The number of the next advice which will be invoked on a proceed() call
	 */
	protected $adviceIndex = -1;

	/**
	 * Initializes the advice chain
	 *
	 * @param array $advices An array of \F3\FLOW3\AOP\AdviceInterface compatible objects which form the chain of advices
	 * @param \F3\FLOW3\AOP\ProxyInterface $proxy A reference to the proxy object using the advice chain
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(&$advices, \F3\FLOW3\AOP\ProxyInterface &$proxy) {
		$this->advices = $advices;
		$this->proxy = $proxy;
	}

	/**
	 * An advice usually calls (but doesn't have to neccessarily) this method
	 * in order to proceed with the next advice in the chain. If no advice is
	 * left in the chain, the proxy classes' method invokeJoinpoint() will finally
	 * be called.
	 *
	 * @param  \F3\FLOW3\AOP\JoinPointInterface $joinPoint: The current join point (ie. the context)
	 * @return mixed Result of the advice or the original method of the target class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function &proceed(\F3\FLOW3\AOP\JoinPointInterface &$joinPoint) {
		$this->adviceIndex++;
		if ($this->adviceIndex < count($this->advices)) {
			$result = $this->advices[$this->adviceIndex]->invoke($joinPoint);
		} else {
			$result = $this->proxy->AOPProxyInvokeJoinpoint($joinPoint);
		}
		return $result;
	}

	/**
	 * Re-initializes the index to start a new run through the advice chain
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function rewind() {
		$this->adviceIndex = -1;
	}
}
?>