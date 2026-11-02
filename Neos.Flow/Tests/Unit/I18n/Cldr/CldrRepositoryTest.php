<?php

declare(strict_types=1);

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
use Neos\Flow\I18n;
use Neos\Flow\I18n\Cldr\CldrRepository;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the CldrRepository
 */
final class CldrRepositoryTest extends UnitTestCase
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
    protected function setUp(): void
    {
        vfsStream::setup('Foo');

        $this->repository = $this->getAccessibleMock(CldrRepository::class, []);
        $this->repository->_set('cldrBasePath', 'vfs://Foo/');

        $this->dummyLocale = new Locale('en');
    }

    #[Test]
    public function modelIsReturnedCorrectlyForSingleFile()
    {
        file_put_contents('vfs://Foo/Bar.xml', '');

        $result = $this->repository->getModel('Bar');
        self::assertContains('vfs://Foo/Bar.xml', ObjectAccess::getProperty($result, 'sourcePaths', true));

        $result = $this->repository->getModel('NoSuchFile');
        self::assertEquals(false, $result);
    }

    #[Test]
    public function modelIsReturnedCorrectlyForGroupOfFiles()
    {
        mkdir('vfs://Foo/Directory');
        file_put_contents('vfs://Foo/Directory/en.xml', '');

        $result = $this->repository->getModelForLocale($this->dummyLocale, 'Directory');
        self::assertContains('vfs://Foo/Directory/root.xml', ObjectAccess::getProperty($result, 'sourcePaths', true));
        self::assertContains('vfs://Foo/Directory/en.xml', ObjectAccess::getProperty($result, 'sourcePaths', true));

        $result = $this->repository->getModelForLocale($this->dummyLocale, 'NoSuchDirectory');
        self::assertEquals(null, $result);
    }
}
