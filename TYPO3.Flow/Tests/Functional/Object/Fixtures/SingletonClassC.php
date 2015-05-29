<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

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
 * A class of scope singleton
 *
 * @Flow\Scope("singleton")
 */
class SingletonClassC {

	/**
	 * @var string
	 */
	public $requiredArgument;

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceA
	 */
	public $interfaceAImplementation;

	/**
	 * @var string
	 */
	public $settingsArgument;

	/**
	 * @var string
	 */
	protected $protectedStringPropertySetViaObjectsYaml = '';

	/**
	 * @var float
	 */
	protected $protectedFloatPropertySetViaObjectsYaml = 0.5;

	/**
	 * @var array
	 */
	protected $protectedArrayPropertySetViaObjectsYaml = array();

	/**
	 * @var boolean
	 */
	protected $protectedBooleanTruePropertySetViaObjectsYaml;

	/**
	 * @var boolean
	 */
	protected $protectedBooleanFalsePropertySetViaObjectsYaml;

	/**
	 * @param string $requiredArgument
	 * @param \TYPO3\Flow\Tests\Functional\Object\Fixtures\InterfaceA $interfaceAImplementation
	 * @param string $settingsArgument
	 * @param boolean $optionalArgument
	 * @param integer $alsoOptionalArgument
	 * @param array $thirdOptionalArgument
	 * @param string $fourthOptionalArgument
	 */
	public function __construct($requiredArgument, InterfaceA $interfaceAImplementation, $settingsArgument, $optionalArgument = FALSE, $alsoOptionalArgument = NULL, $thirdOptionalArgument = array(), $fourthOptionalArgument = '') {
		$this->requiredArgument = $requiredArgument;
		$this->interfaceAImplementation = $interfaceAImplementation;
		$this->settingsArgument = $settingsArgument;
		$this->optionalArgument = $optionalArgument;
		$this->thirdOptionalArgument = $thirdOptionalArgument;
	}

	/**
	 * @return string
	 */
	public function getProtectedStringPropertySetViaObjectsYaml() {
		return $this->protectedStringPropertySetViaObjectsYaml;
	}

	/**
	 * @return array
	 */
	public function getProtectedArrayPropertySetViaObjectsYaml() {
		return $this->protectedArrayPropertySetViaObjectsYaml;
	}

	/**
	 * @return float
	 */
	public function getProtectedFloatPropertySetViaObjectsYaml() {
		return $this->protectedFloatPropertySetViaObjectsYaml;
	}

	/**
	 * @return boolean
	 */
	public function getProtectedBooleanTruePropertySetViaObjectsYaml() {
		return $this->protectedBooleanTruePropertySetViaObjectsYaml;
	}

	/**
	 * @return boolean
	 */
	public function getProtectedBooleanFalsePropertySetViaObjectsYaml() {
		return $this->protectedBooleanFalsePropertySetViaObjectsYaml;
	}

}
