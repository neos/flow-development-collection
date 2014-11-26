<?php
namespace TYPO3\Flow\Http\Component;

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
 * HTTP component that makes sure that the current response is standards-compliant. It is usually the last component in the chain.
 */
class StandardsComplianceComponent implements ComponentInterface {

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @param array $options
	 */
	public function __construct(array $options = array()) {
		$this->options = $options;
	}

	/**
	 * Just call makeStandardsCompliant on the Response for now
	 *
	 * @param ComponentContext $componentContext
	 * @return void
	 */
	public function handle(ComponentContext $componentContext) {
		$response = $componentContext->getHttpResponse();
		$response->makeStandardsCompliant($componentContext->getHttpRequest());
	}

}