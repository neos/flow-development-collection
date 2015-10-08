<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A simple test context that is registered as a global AOP object
 *
 * @Flow\Scope("singleton")
 */
class TestContext
{
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
    public function getNameOfTheWeek()
    {
        return 'Robbie';
    }

    /**
     * @param \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD $securityFixturesEntityD
     */
    public function setSecurityFixturesEntityD($securityFixturesEntityD)
    {
        $this->securityFixturesEntityD = $securityFixturesEntityD;
    }

    /**
     * @return \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD
     */
    public function getSecurityFixturesEntityD()
    {
        return $this->securityFixturesEntityD;
    }

    /**
     * @param array<\TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD> $securityFixturesEntityDCollection
     */
    public function setSecurityFixturesEntityDCollection($securityFixturesEntityDCollection)
    {
        $this->securityFixturesEntityDCollection = $securityFixturesEntityDCollection;
    }

    /**
     * @return array<\TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityD>
     */
    public function getSecurityFixturesEntityDCollection()
    {
        return $this->securityFixturesEntityDCollection;
    }
}
