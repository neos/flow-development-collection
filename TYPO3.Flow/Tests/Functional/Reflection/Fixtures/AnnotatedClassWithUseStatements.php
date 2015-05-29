<?php
namespace TYPO3\Flow\Tests\Functional\Reflection\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Reflection\ReflectionService;

/**
 * An annotated class with use statements
 */
class AnnotatedClassWithUseStatements extends AbstractAnnotatedClassWithUseStatements {

	/**
	 * @var ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var AnnotatedClass
	 */
	protected $annotatedClass;

	/**
	 * @var Model\SubEntity
	 */
	protected $subEntity;
}
