<?php
namespace TYPO3\FLOW3\Annotations;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Annotations\Annotation as DoctrineAnnotation;

/**
 * @Annotation
 * @DoctrineAnnotation\Target({"METHOD", "PROPERTY"})
 */
final class Validate {

	/**
	 * The validator type, either a FQCN or a FLOW3 validator class name.
	 * @var string
	 */
	public $type;

	/**
	 * Options for the validator, validator-specific.
	 * @var array
	 */
	public $options = array();

	/**
	 * The name of the argument this annotation is attached to
	 * @var string
	 */
	public $argumentName;

	/**
	 * The validation groups for which this validator should be executed
	 * @var array
	 */
	public $validationGroups = array('Default');

	/**
	 * @param array $values
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $values) {
		if (!isset($values['type'])) {
			throw new \InvalidArgumentException('Validate annotations must be given a validator type.', 1318494791);
		}
		$this->type = $values['type'];

		if (isset($values['options']) && is_array($values['options'])) {
			$this->options = $values['options'];
		}

		if (isset($values['value']) || isset($values['argumentName'])) {
			$this->argumentName = ltrim(isset($values['argumentName']) ? $values['argumentName'] : $values['value'], '$');
		}

		if (isset($values['validationGroups']) && is_array($values['validationGroups'])) {
			$this->validationGroups = $values['validationGroups'];
		}
	}

}

?>