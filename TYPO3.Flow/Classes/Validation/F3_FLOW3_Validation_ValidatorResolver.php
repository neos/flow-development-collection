<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Validation;

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
 * @subpackage Validation
 * @version $Id$
 */

/**
 * Validator resolver to automatically find a appropriate validator for a given subject
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ValidatorResolver {

	/**
	 * @var F3::FLOW3::Object::ManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructs the validator resolver
	 *
	 * @param F3::FLOW3::Object::ManagerInterface A reference to the compomenent manager
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3::FLOW3::Object::ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Returns an object of an appropriate validator for the given class. If no validator is available
	 * a F3::FLOW3::Validation::Exception::NoValidatorFound exception is thrown.
	 * @param string The classname for which validator is needed
	 * @return object The resolved validator object
	 * @throws F3::FLOW3::Validation::Exception::NoValidatorFound
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidator($class) {
		$validatorName = $class . 'Validator';
		if (!$this->objectManager->isObjectRegistered($validatorName)) throw new F3::FLOW3::Validation::Exception::NoValidatorFound('No validator with name ' . $validatorName . ' found!', 1211036055);
		$validator = $this->objectManager->getObject($validatorName);
		if (!($validator instanceof F3::FLOW3::Validation::ObjectValidatorInterface)) throw new F3::FLOW3::Validation::Exception::NoValidatorFound('The found validator class did not implement F3::FLOW3::Validation::ObjectValidatorInterface', 1211036068);
		return $validator;
	}

	/**
	 * Returns the name of an appropriate validator for the given class. If no validator is available
	 * a F3::FLOW3::Validation::Exception::NoValidatorFound exception is thrown.
	 * @param string The classname for which validator is needed
	 * @return object The resolved validator object
	 * @throws F3::FLOW3::Validation::Exception::NoValidatorFound
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorName($class) {
		$validatorName = $class . 'Validator';
		if (!$this->objectManager->isObjectRegistered($validatorName)) throw new F3::FLOW3::Validation::Exception::NoValidatorFound('No validator with name ' . $validatorName . ' found!', 1211036084);
		$validator = $this->objectManager->getObject($validatorName);
		if (!($validator instanceof F3::FLOW3::Validation::ObjectValidatorInterface)) throw new F3::FLOW3::Validation::Exception::NoValidatorFound('The found validator class did not implement F3::FLOW3::Validation::ObjectValidatorInterface', 1211036095);
		return $validatorName;
	}
}

?>