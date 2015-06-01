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
 * An HTTP component
 *
 * A component is one item of the configurable component chain that is processed for every incoming request. A component can change the current HTTP request and response,
 * can communicate with other components and even change the currently processed chain using the ComponentContext that gets passed to its handle() method.
 *
 * @api
 */
interface ComponentInterface {

	/**
	 * Constructs the component and sets options
	 *
	 * Note: Constructors must not be defined in PHP interfaces, but this should be implemented in custom component implementations
	 *
	 * @param array $options The component options
	 * @api
	 */
	//public function __construct(array $options = array());

	/**
	 * @param ComponentContext $componentContext
	 * @return void
	 * @api
	 */
	public function handle(ComponentContext $componentContext);

}