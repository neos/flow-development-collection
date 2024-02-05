<?php
namespace Neos\Flow\Tests\Unit\ResourceManagement\Storage;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ResourceManagement\CollectionInterface;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\ResourceManagement\Storage\FileSystemStorage;
use Neos\Flow\Tests\UnitTestCase;

class FileSystemStorageTest extends UnitTestCase
{
    /**
     * Assert that `getObjectsByCollection` returns an empty generator https://github.com/neos/flow-development-collection/pull/2167
     * @test
     */
    public function getObjectsByCollectionWithNoResourcesShouldYieldEmpty(): void
    {
        $mockResourceRepository = $this->getMockBuilder(ResourceRepository::class)->getMock();
        $mockResourceRepository->expects(self::once())->method('findByCollectionNameIterator')->willReturnCallback(function () {
            yield from [];
        });
        $fileSystemStorage = new FileSystemStorage('foo');
        $this->inject($fileSystemStorage, 'resourceRepository', $mockResourceRepository);
        $collectionMock = $this->getMockBuilder(CollectionInterface::class)->getMock();
        $collectionMock->method('getName')->willReturn('abc');

        $objects = $fileSystemStorage->getObjectsByCollection($collectionMock);
        self::assertIsIterable($objects);
        self::assertInstanceOf(\Generator::class, $objects);

        self::assertSame([], iterator_to_array($objects));
    }
}
