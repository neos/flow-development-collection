<?php
namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Tests\Functional\Security\Fixtures;

/**
 * A simple test context that is registered as a global AOP object
 *
 * @Flow\Scope("singleton")
 */
class TestContext
{
    /**
     * @var Fixtures\TestEntityD
     */
    protected $securityFixturesEntityD;

    /**
     * @var array<Fixtures\TestEntityD>
     */
    protected $securityFixturesEntityDCollection = [];

    /**
     * @return string
     */
    public function getNameOfTheWeek()
    {
        return 'Robbie';
    }

    /**
     * @param Fixtures\TestEntityD $securityFixturesEntityD
     */
    public function setSecurityFixturesEntityD($securityFixturesEntityD)
    {
        $this->securityFixturesEntityD = $securityFixturesEntityD;
    }

    /**
     * @return Fixtures\TestEntityD
     */
    public function getSecurityFixturesEntityD()
    {
        return $this->securityFixturesEntityD;
    }

    /**
     * @param array<Fixtures\TestEntityD> $securityFixturesEntityDCollection
     */
    public function setSecurityFixturesEntityDCollection($securityFixturesEntityDCollection)
    {
        $this->securityFixturesEntityDCollection = $securityFixturesEntityDCollection;
    }

    /**
     * @return array<Fixtures\TestEntityD>
     */
    public function getSecurityFixturesEntityDCollection()
    {
        return $this->securityFixturesEntityDCollection;
    }
}
