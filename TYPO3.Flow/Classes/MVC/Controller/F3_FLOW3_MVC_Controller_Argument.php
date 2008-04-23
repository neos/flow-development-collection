<?php
declare(ENCODING = 'utf-8');

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
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Controller_Argument.php 467 2008-02-06 19:34:56Z robert $
 * @copyright Copyright belongs to the respective authors
 */

/**
 * A controller argument
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Controller_Argument.php 467 2008-02-06 19:34:56Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_MVC_Controller_Argument {

	/**
	 * @var string Name of this argument
	 */
	protected $name = '';

	/**
	 * @var string Short name of this argument
	 */
	protected $shortName = NULL;

	/**
	 * @var string Data type of this argument's value
	 */
	protected $dataType = 'Text';

	/**
	 * @var object Actual value of this argument
	 */
	protected $value = NULL;

	/**
	 * @var string Short help message for this argument
	 */
	protected $shortHelpMessage = NULL;

	/**
	 * Constructs this controller argument
	 *
	 * @param string $name Name of this argument
	 * @param string $dataType The data type of this argument
	 * @throws InvalidArgumentException if $name is not a string or empty
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($name, $dataType = 'Text') {
		if (!is_string($name) || F3_PHP6_Functions::strlen($name) < 1) throw new InvalidArgumentException('$name must be of type string, ' . gettype($name) . ' given.', 1187951688);
		$this->name = $name;
		$this->setDataType($dataType);
	}

	/**
	 * Returns the name of this argument
	 *
	 * @return string This argument's name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the short name of this argument.
	 *
	 * @param string $shortName A "short name" - a single character
	 * @return F3_FLOW3_MVC_Controller_Argument $this
	 * @throws InvalidArgumentException if $shortName is not a character
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortName($shortName) {
		if ($shortName !== NULL && (!is_string($shortName) || F3_PHP6_Functions::strlen($shortName) != 1)) throw new InvalidArgumentException('$shortName must be a single character or NULL', 1195824959);
		$this->shortName = $shortName;
		return $this;
	}

	/**
	 * Returns the short name of this argument
	 *
	 * @return string This argument's short name
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getShortName() {
		return $this->shortName;
	}

	/**
	 * Sets the data type of this argument's value
	 *
	 * @param string $dataType: Name of the data type
	 * @return F3_FLOW3_MVC_Controller_Argument $this
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDataType($dataType) {
		$this->dataType = $dataType;
		return $this;
	}

	/**
	 * Returns the data type of this argument's value
	 *
	 * @return string The data type
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDataType() {
		return $this->dataType;
	}

	/**
	 * Sets the value of this argument.
	 *
	 * @param mixed $value: The value of this argument
	 * @return F3_FLOW3_MVC_Controller_Argument $this
	 * @throws F3_FLOW3_MVC_Exception_InvalidArgumentValue if the argument is not a valid object of type $dataType
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValue($value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * Returns the value of this argument
	 *
	 * @return object The value of this argument - if none was set, NULL is returned
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Checks if this argument has a value set.
	 *
	 * @return boolean TRUE if a value was set, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValue() {
		return $this->value !== NULL;
	}

	/**
	 * Sets a short help message for this argument. Mainly used at the command line, but maybe
	 * used elsewhere, too.
	 *
	 * @param  string $message: A short help message
	 * @return F3_FLOW3_MVC_Controller_Argument		$this
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortHelpMessage($message) {
		if (!is_string($message)) throw new InvalidArgumentException('The help message must be of type string, ' . gettype($message) . 'given.', 1187958170);
		$this->shortHelpMessage = $message;
		return $this;
	}

	/**
	 * Returns the short help message
	 *
	 * @return string The short help message
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getShortHelpMessage() {
		return $this->shortHelpMessage;
	}
}
?>