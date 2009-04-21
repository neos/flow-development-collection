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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	 * NULL is returned
	 *
	 * @param string The classname for which validator is needed
	 * @return object The resolved validator object or NULL.
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function resolveValidatorClassName($dataType) {
		$dataType = $this->unifyDataType($dataType);

		$resolvedClassName = '';
		$possibleClassName = $dataType . 'Validator';
		if ($this->objectManager->isObjectRegistered($possibleClassName)) {
			return $possibleClassName;
		}

		$possibleClassName = 'F3\FLOW3\Validation\Validator\\' . $dataType . 'Validator';
		if ($this->objectManager->isObjectRegistered($possibleClassName)) {
			return $possibleClassName;
		}

		return NULL;
	}

	/**
	 * Get a validator for a given data type. Returns NULL if no validator was found,
	 * or an instance of F3\FLOW3\Validation\Validator\ValidatorInterface.
	 *
	 * @param string $validatorName Either one of the built-in data types or fully qualified validator class name
	 * @param array $validatorOptions Options to be passed to the validator
	 * @return F3\FLOW3\Validation\Validator\ValidatorResolver Validator Resolver or NULL if none found.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createValidator($validatorName, array $validatorOptions = array()) {
		$validatorClassName = $this->resolveValidatorClassName($validatorName);
		if ($validatorClassName === NULL) return NULL;
		return $this->objectManager->getObject($validatorClassName, $validatorOptions);
	}

	/**
	 * Preprocess data types. Used to map primitive PHP types to DataTypes in FLOW3.
	 *
	 * @param string $type Data type to unify
	 * @return string unified data type
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function unifyDataType($type) {
		switch ($type) {
			case 'int' :
				$type = 'Integer';
				break;
			case 'string' :
				$type = 'Text';
				break;
			case 'bool' :
				$type = 'Boolean';
				break;
			case 'double' :
				$type = 'Float';
				break;
			case 'numeric' :
				$type = 'Number';
				break;
			case 'mixed' :
				$type = 'Raw';
				break;
		}
		return ucfirst($type);
	}
}

?>