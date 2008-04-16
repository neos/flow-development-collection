<?php
declare(encoding = 'utf-8');

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
 * @version $Id: F3_FLOW3_Validation_ValidatorResolver.php 688 2008-04-03 09:35:36Z andi $
 */

/**
 * Validator resolver to automatically find a appropriate validator for a given subject
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $Id: F3_FLOW3_Validation_ValidatorResolver.php 688 2008-04-03 09:35:36Z andi $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_ValidatorResolver {

	/**
	 * @var F3_FLOW3_Component_ManagerInterface
	 */
	protected $componentManager;

	/**
	 * constructor
	 * @param F3_FLOW3_Component_ManagerInterface A compomenent manager
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Returns an object of an appropriate validator for the given class. If no validator is available
	 * a F3_FLOW3_Validation_Exception_NoValidatorFound exception is thrown.
	 * @param string The classname for which validator is needed
	 * @return object The resolved validator object
	 * @throws F3_FLOW3_Validation_Exception_NoValidatorFound
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidator($class) {
		$validatorName = $class . 'Validator';

		if(!$this->componentManager->isComponentRegistered($validatorName)) throw new F3_FLOW3_Validation_Exception_NoValidatorFound('No validator with name ' . $validatorName . ' found!');

		$validator = $this->componentManager->getComponent($validatorName);
		if(!($validator instanceof F3_FLOW3_Validation_ObjectValidatorInterface)) throw new F3_FLOW3_Validation_Exception_NoValidatorFound('The found validator class did not implement F3_FLOW3_Validation_ObjectValidatorInterface');

		return $validator;
	}

	/**
	 * Returns the name of an appropriate validator for the given class. If no validator is available
	 * a F3_FLOW3_Validation_Exception_NoValidatorFound exception is thrown.
	 * @param string The classname for which validator is needed
	 * @return object The resolved validator object
	 * @throws F3_FLOW3_Validation_Exception_NoValidatorFound
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveValidatorName($class) {
		$validatorName = $class . 'Validator';

		if(!$this->componentManager->isComponentRegistered($validatorName)) throw new F3_FLOW3_Validation_Exception_NoValidatorFound('No validator with name ' . $validatorName . ' found!');

		$validator = $this->componentManager->getComponent($validatorName);
		if(!($validator instanceof F3_FLOW3_Validation_ObjectValidatorInterface)) throw new F3_FLOW3_Validation_Exception_NoValidatorFound('The found validator class did not implement F3_FLOW3_Validation_ObjectValidatorInterface');

		return $validatorName;
	}
}

?>
