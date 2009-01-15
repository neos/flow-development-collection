<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * Implementation of the Introduction declaration.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 */
class Introduction {

	/**
	 * @var string Name of the aspect declaring this introduction
	 */
	protected $declaringAspectClassName;

	/**
	 * @var string Name of the introduced interface
	 */
	protected $interfaceName;

	/**
	 * @var \F3\FLOW3\AOP\Pointcut The poincut this introduction applies to
	 */
	protected $pointcut;

	/**
	 * Constructor
	 *
	 * @param string $declaringAspectClassName Name of the aspect containing the declaration for this introduction
	 * @param string $interface Name of the interface to introduce
	 * @param \F3\FLOW3\AOP\Pointcut $pointcut The pointcut for this introduction
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($declaringAspectClassName, $interfaceName, \F3\FLOW3\AOP\Pointcut $pointcut) {
		$this->declaringAspectClassName = $declaringAspectClassName;
		$this->interfaceName = $interfaceName;
		$this->pointcut = $pointcut;
	}

	/**
	 * Returns the name of the introduced interface
	 *
	 * @return string Name of the introduced interface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getInterfaceName() {
		return $this->interfaceName;
	}

	/**
	 * Returns the poincut this introduction applies to
	 *
	 * @return \F3\FLOW3\AOP\Pointcut The pointcut
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcut() {
		return $this->pointcut;
	}

	/**
	 * Returns the object name of the aspect which declared this introduction
	 *
	 * @return string The aspect object name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringAspectClassName() {
		return $this->declaringAspectClassName;
	}
}
?>