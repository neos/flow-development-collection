<?php
namespace Neos\Flow\Tests\Unit\I18n\Cldr;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;

/**
 * Testcase for the CldrRepository
 */
class CldrRepositoryTest extends UnitTestCase
{
    /**
     * @var I18n\Cldr\CldrRepository
     */
    protected $repository;

    /**
     * @var I18n\Locale
     */
    protected $dummyLocale;

    /**
     * @return void
     */
    public function setUp()
    {
        vfsStream::setup('Foo');

        $this->repository = $this->getAccessibleMock(I18n\Cldr\CldrRepository::class, ['dummy']);
        $this->repository->_set('cldrBasePath', 'vfs://Foo/');

        $this->dummyLocale = new I18n\Locale('en');
    }

    /**
     * @test
     */
    public function modelIsReturnedCorrectlyForSingleFile()
    {
        file_put_contents('vfs://Foo/Bar.xml', '');

        $result = $this->repository->getModel('Bar');
        $this->assertAttributeContains('vfs://Foo/Bar.xml', 'sourcePaths', $result);

        $result = $this->repository->getModel('NoSuchFile');
        $this->assertEquals(false, $result);
    }

    /**
     * @test
     */
    public function modelIsReturnedCorrectlyForGroupOfFiles()
    {
        mkdir('vfs://Foo/Directory');
        file_put_contents('vfs://Foo/Directory/en.xml', '');

        $result = $this->repository->getModelForLocale($this->dummyLocale, 'Directory');
        $this->assertAttributeContains('vfs://Foo/Directory/root.xml', 'sourcePaths', $result);
        $this->assertAttributeContains('vfs://Foo/Directory/en.xml', 'sourcePaths', $result);

        $result = $this->repository->getModelForLocale($this->dummyLocale, 'NoSuchDirectory');
        $this->assertEquals(null, $result);
    }
}
