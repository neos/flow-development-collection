<?php
namespace TYPO3\FLOW3\AOP;

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
 * Implementation of the property introduction declaration.
 *
 * @scope prototype
 */
class PropertyIntroduction {

	/**
	 * Name of the aspect declaring this introduction
	 * @var string
	 */
	protected $declaringAspectClassName;

	/**
	 * Name of the introduced property
	 * @var string
	 */
	protected $propertyName;

	/**
	 * Visibility of the introduced property
	 * @var string
	 */
	protected $propertyVisibility;

	/**
	 * DocComment of the introduced property
	 * @var string
	 */
	protected $propertyDocComment;

	/**
	 * The poincut this introduction applies to
	 * @var \TYPO3\FLOW3\AOP\Pointcut\Pointcut
	 */
	protected $pointcut;

	/**
	 * Constructor
	 *
	 * @param string $declaringAspectClassName Name of the aspect containing the declaration for this introduction
	 * @param string $propertyName Name of the property to introduce
	 * @param \TYPO3\FLOW3\AOP\Pointcut\Pointcut $pointcut The pointcut for this introduction
	 */
	public function __construct($declaringAspectClassName, $propertyName, \TYPO3\FLOW3\AOP\Pointcut\Pointcut $pointcut) {
		$this->declaringAspectClassName = $declaringAspectClassName;
		$this->propertyName = $propertyName;
		$this->pointcut = $pointcut;

		$propertyReflection = new \ReflectionProperty($declaringAspectClassName, $propertyName);
		if ($propertyReflection->isPrivate()) {
			$this->propertyVisibility = 'private';
		} elseif ($propertyReflection->isProtected()) {
			$this->propertyVisibility = 'protected';
		} else {
			$this->propertyVisibility = 'public';
		}
		$this->propertyDocComment = preg_replace('/@introduce.+$/mi', 'introduced by ' . $declaringAspectClassName, $propertyReflection->getDocComment());
	}

	/**
	 * Returns the name of the introduced property
	 *
	 * @return string Name of the introduced property
	 */
	public function getPropertyName() {
		return $this->propertyName;
	}

	/**
	 * Returns the visibility of the introduced property
	 *
	 * @return string Visibility of the introduced property
	 */
	public function getPropertyVisibility() {
		return $this->propertyVisibility;
	}

	/**
	 * Returns the DocComment of the introduced property
	 *
	 * @return string DocComment of the introduced property
	 */
	public function getPropertyDocComment() {
		return $this->propertyDocComment;
	}

	/**
	 * Returns the poincut this introduction applies to
	 *
	 * @return \TYPO3\FLOW3\AOP\Pointcut\Pointcut The pointcut
	 */
	public function getPointcut() {
		return $this->pointcut;
	}

	/**
	 * Returns the object name of the aspect which declared this introduction
	 *
	 * @return string The aspect object name
	 */
	public function getDeclaringAspectClassName() {
		return $this->declaringAspectClassName;
	}
}
?>