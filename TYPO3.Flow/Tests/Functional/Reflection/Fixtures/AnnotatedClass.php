<?php
namespace TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * @ORM\Entity
 * @FLOW3\Introduce("TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicAspect->isEntityOrValueObject", interfaceName="TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicInterface")
 */
class AnnotatedClass {

	/**
	 * @param string
	 * @FLOW3\Introduce("within(TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicInterface)")
	 */
	protected $introducedProperty;

	/**
	 * @param string
	 * @FLOW3\Validate(type="Email")
	 * @FLOW3\Validate(type="NotEmpty")
	 * @FLOW3\Identity
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
	 * @FLOW3\Validate("foo", type="StringLength", options={ "mininum"=2, "maximum"=5 })
	 */
	public function someFlow3Annotations($foo) {}
}

?>