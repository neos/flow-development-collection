<?php
namespace Neos\Flow\Tests\Unit\Http;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\ContentStream;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the Http ContentStream class
 */
class ContentStreamTest extends UnitTestCase
{

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function constructorThrowsExceptionWhenBeingPassedAnInvalidResource()
    {
        new ContentStream('invalid resource');
    }

    public function fromContentsCreatesValidContentStream()
    {
        $someContent = 'Lorem ipsum
        dolor';
        $contentStream = ContentStream::fromContents($someContent);
        $this->assertSame($someContent, $contentStream->getContents());
    }
}
