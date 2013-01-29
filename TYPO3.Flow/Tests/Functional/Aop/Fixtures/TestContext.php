<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

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
 * A simple test context that is registered as a global AOP object
 *
 * @Flow\Scope("singleton")
 */
class TestContext {

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD
	 */
	protected $securityFixturesEntityD;

	/**
	 * @var array<\TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD>
	 */
	protected $securityFixturesEntityDCollection = array();

	/**
	 * @return string
	 */
	public function getNameOfTheWeek() {
		return 'Robbie';
	}

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD $securityFixturesEntityD
	 */
	public function setSecurityFixturesEntityD($securityFixturesEntityD) {
		$this->securityFixturesEntityD = $securityFixturesEntityD;
	}

	/**
	 * @return \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD
	 */
	public function getSecurityFixturesEntityD() {
		return $this->securityFixturesEntityD;
	}

	/**
	 * @param array<\TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD> $securityFixturesEntityDCollection
	 */
	public function setSecurityFixturesEntityDCollection($securityFixturesEntityDCollection) {
		$this->securityFixturesEntityDCollection = $securityFixturesEntityDCollection;
	}

	/**
	 * @return array<\TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD>
	 */
	public function getSecurityFixturesEntityDCollection() {
		return $this->securityFixturesEntityDCollection;
	}

}
