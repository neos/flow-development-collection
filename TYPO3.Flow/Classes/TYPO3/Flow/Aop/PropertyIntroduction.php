<?php
namespace TYPO3\Flow\Aop;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Implementation of the property introduction declaration.
 *
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
	 * The pointcut this introduction applies to
	 * @var \TYPO3\Flow\Aop\Pointcut\Pointcut
	 */
	protected $pointcut;

	/**
	 * Constructor
	 *
	 * @param string $declaringAspectClassName Name of the aspect containing the declaration for this introduction
	 * @param string $propertyName Name of the property to introduce
	 * @param \TYPO3\Flow\Aop\Pointcut\Pointcut $pointcut The pointcut for this introduction
	 */
	public function __construct($declaringAspectClassName, $propertyName, \TYPO3\Flow\Aop\Pointcut\Pointcut $pointcut) {
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
		$this->propertyDocComment = preg_replace('/@(TYPO3\\\\Flow\\\\Annotations|Flow)\\\\Introduce.+$/mi', 'introduced by ' . $declaringAspectClassName, $propertyReflection->getDocComment());
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
	 * Returns the pointcut this introduction applies to
	 *
	 * @return \TYPO3\Flow\Aop\Pointcut\Pointcut The pointcut
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
