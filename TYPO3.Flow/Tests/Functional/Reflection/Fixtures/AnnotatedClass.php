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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * @ORM\Entity
 * @Flow\Introduce("TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect->isEntityOrValueObject", interfaceName="TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface")
 */
class AnnotatedClass {

	/**
	 * @param string
	 * @Flow\Introduce("within(TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface)")
	 */
	protected $introducedProperty;

	/**
	 * @param string
	 * @Flow\Validate(type="Email")
	 * @Flow\Validate(type="NotEmpty")
	 * @Flow\Identity
	 * @ORM\Column(name="baz")
	 */
	protected $mail;

	/**
	 * This is the description for the regularAnnotations method.
	 *
	 * @param string $foo
	 * @param integer $bar
	 * @return integer
	 * @throws \Exception
	 * @api
	 */
	public function regularAnnotations($foo, $bar) {}

	/**
	 * @param string $foo
	 * @Flow\Validate("foo", type="StringLength", options={ "mininum"=2, "maximum"=5 })
	 */
	public function someFlowAnnotations($foo) {}
}
