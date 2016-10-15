<?php
namespace TYPO3\Flow\Tests\Functional\Package;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Package\PackageManager;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Tests\FunctionalTestCase;

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
        $this->assertSame('TYPO3.Flow', $package->getPackageKey());
    }

    /**
     * @test
     */
    public function getPackageOfObjectReturnsCorrectPackageForAnExistingProxyObject()
    {
        $account = new Account();
        $package = $this->packageManager->getPackageOfObject($account);
        $this->assertSame('TYPO3.Flow', $package->getPackageKey());
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
        $this->assertSame('TYPO3.Flow', $package->getPackageKey());
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
