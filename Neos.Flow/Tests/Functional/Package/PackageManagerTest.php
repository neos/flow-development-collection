<?php
namespace Neos\Flow\Tests\Functional\Package;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Package\PackageManager;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for Package Manager
 *
 */
class PackageManagerTest extends FunctionalTestCase
{
    /**
     *
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->packageManager = $this->objectManager->get(PackageManagerInterface::class);
    }

    /**
     * @test
     */
    public function getPackageOfObjectReturnsCorrectPackageForAnExistingObject()
    {
        $package = $this->packageManager->getPackageOfObject($this);
        $this->assertSame('Neos.Flow', $package->getPackageKey());
    }

    /**
     * @test
     */
    public function getPackageOfObjectReturnsCorrectPackageForAnExistingProxyObject()
    {
        $account = new Account();
        $package = $this->packageManager->getPackageOfObject($account);
        $this->assertSame('Neos.Flow', $package->getPackageKey());
    }

    /**
     * @test
     */
    public function getPackageOfObjectReturnsNullForObjectsThatDontBelongToAPackage()
    {
        $genericObject = new \stdClass();
        $this->assertNull($this->packageManager->getPackageOfObject($genericObject));
    }

    /**
     * @test
     */
    public function getPackageByClassNameReturnsCorrectPackageForAnExistingClass()
    {
        $existingClassName = get_class($this);
        $package = $this->packageManager->getPackageByClassName($existingClassName);
        $this->assertSame('Neos.Flow', $package->getPackageKey());
    }

    /**
     * @test
     */
    public function getPackageByClassNameReturnsNullForNonExistingClasses()
    {
        $nonExistingClassName = 'SomeNonExistingClass';
        $this->assertNull($this->packageManager->getPackageByClassName($nonExistingClassName));
    }

    /**
     * @test
     */
    public function getPackageByClassNameReturnsNullForClassesThatAreNotPartOfAPackage()
    {
        $globalClassName = 'stdClass';
        $this->assertNull($this->packageManager->getPackageByClassName($globalClassName));
    }
}
