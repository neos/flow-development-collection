<?php
namespace Neos\Flow\Tests\Unit\Error;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Error\DebugExceptionHandler;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Debug Exception Handler
 *
 */
class DebugExceptionHandlerTest extends UnitTestCase
{
    public function splitExceptionMessageDataProvider()
    {
        return [
            [
                'message' => '',
                'expectedSubject' => '',
                'expectedBody' => ''
            ],
            [
                'message' => 'Some short message',
                'expectedSubject' => 'Some short message',
                'expectedBody' => ''
            ],
            [
                'message' => 'Just one phrase.',
                'expectedSubject' => 'Just one phrase.',
                'expectedBody' => ''
            ],
            [
                'message' => 'First phrase. Second phrase. Third phrase.',
                'expectedSubject' => 'First phrase.',
                'expectedBody' => 'Second phrase. Third phrase.'
            ],
            [
                'message' => 'First line
Second line
Third line',
                'expectedSubject' => 'First line',
                'expectedBody' => 'Second line
Third line'
            ],
            [
                'message' => 'First line' . PHP_EOL . 'Second line',
                'expectedSubject' => 'First line',
                'expectedBody' => 'Second line'
            ],
            [
                'message' => 'Line break and sentence.
				indented body.',
                'expectedSubject' => 'Line break and sentence.',
                'expectedBody' => 'indented body.'
            ],
            [
                'message' => 'Invalid path "foo.bar.baz"! New phrase',
                'expectedSubject' => 'Invalid path "foo.bar.baz"!',
                'expectedBody' => 'New phrase'
            ],
            [
                'message' => 'Question?',
                'expectedSubject' => 'Question?',
                'expectedBody' => ''
            ],
            [
                'message' => 'Question? Answer',
                'expectedSubject' => 'Question?',
                'expectedBody' => 'Answer'
            ],
            [
                'message' => 'Filter() needs arguments if it follows an empty children(): children().filter()',
                'expectedSubject' => 'Filter() needs arguments if it follows an empty children(): children().filter()',
                'expectedBody' => ''
            ],
            [
                'message' => 'children() only supports a single filter group right now, i.e. nothing of the form "filter1, filter2"',
                'expectedSubject' => 'children() only supports a single filter group right now, i.e. nothing of the form "filter1, filter2"',
                'expectedBody' => ''
            ],
        ];
    }

    /**
     * @param string $message
     * @param string $expectedSubject
     * @param string $expectedBody
     * @test
     * @dataProvider splitExceptionMessageDataProvider
     */
    public function splitExceptionMessageTests($message, $expectedSubject, $expectedBody)
    {
        $debugExceptionHandler = $this->getAccessibleMock(DebugExceptionHandler::class, ['dummy']);

        $expectedResult = ['subject' => $expectedSubject, 'body' => $expectedBody];
        $actualResult = $debugExceptionHandler->_call('splitExceptionMessage', $message);

        $this->assertSame($expectedResult, $actualResult);
    }
}
