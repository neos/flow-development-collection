<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

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
use TYPO3\Flow\Utility\Algorithms;


/**
 * A simple entity for persistence tests that is not proxied (no AOP/DI)
 *
 * @Flow\Entity
 * @Flow\Proxy(false)
 * @ORM\Table(name="persistence_unproxiedtestentity")
 */
class UnproxiedTestEntity {

	/**
	 * @var string
	 * @ORM\Id
	 * @ORM\Column(length=40)
	 */
	protected $uuid;

	/**
	 * @var string
	 * @Flow\Validate(type="StringLength", options={"minimum"=3})
	 */
	protected $name = '';

	public function __construct() {
		$this->uuid = Algorithms::generateUUID();
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

}
