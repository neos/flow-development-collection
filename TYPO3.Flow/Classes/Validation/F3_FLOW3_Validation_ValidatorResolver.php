<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation;

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
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 */

/**
 * Validator resolver to automatically find a appropriate validator for a given subject
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class ValidatorResolver {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructs the validator resolver
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface A reference to the compomenent manager
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Returns an object of an appropriate validator for the given class. If no validator is available
	 * a \F3\FLOW3\Validation\Exception\NoValidatorFound exception is thrown.
	 * @param string The classname for which validator is needed
	 * @return object The resolved validator object
	 * @throws \F3\FLOW3\Validation\Exception\NoValidatorFound
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidator($class) {
		$validatorName = $class . 'Validator';
		if (!$this->objectManager->isObjectRegistered($validatorName)) throw new \F3\FLOW3\Validation\Exception\NoValidatorFound('No validator with name ' . $validatorName . ' found!', 1211036055);
		$validator = $this->objectManager->getObject($validatorName);
		if (!($validator instanceof \F3\FLOW3\Validation\ObjectValidatorInterface)) throw new \F3\FLOW3\Validation\Exception\NoValidatorFound('The found validator class did not implement \F3\FLOW3\Validation\ObjectValidatorInterface', 1211036068);
		return $validator;
	}

	/**
	 * Returns the name of an appropriate validator for the given class. If no validator is available
	 * a \F3\FLOW3\Validation\Exception\NoValidatorFound exception is thrown.
	 * @param string The classname for which validator is needed
	 * @return object The resolved validator object
	 * @throws \F3\FLOW3\Validation\Exception\NoValidatorFound
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorName($class) {
		$validatorName = $class . 'Validator';
		if (!$this->objectManager->isObjectRegistered($validatorName)) throw new \F3\FLOW3\Validation\Exception\NoValidatorFound('No validator with name ' . $validatorName . ' found!', 1211036084);
		$validator = $this->objectManager->getObject($validatorName);
		if (!($validator instanceof \F3\FLOW3\Validation\ObjectValidatorInterface)) throw new \F3\FLOW3\Validation\Exception\NoValidatorFound('The found validator class did not implement \F3\FLOW3\Validation\ObjectValidatorInterface', 1211036095);
		return $validatorName;
	}
}

?>