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
use Neos\Flow\Error\AbstractExceptionHandler;
use Neos\Flow\Exception;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Test case for the Abstract Exception Handler
 */
class AbstractExceptionHandlerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function handleExceptionLogsInformationAboutTheExceptionInTheThrowableStorage()
    {
        $options = [
            'defaultRenderingOptions' => [
                'renderTechnicalDetails' => true,
                'logException' => true
            ],
            'renderingGroups' => []
        ];

        $exception = new \Exception('The Message', 12345);

        $mockThrowableStorage = $this->createMock(ThrowableStorageInterface::class);
        $mockThrowableStorage->expects(self::once())->method('logThrowable')->with($exception);

        $mockLogger = $this->createMock(LoggerInterface::class);

        $exceptionHandler = $this->getMockForAbstractClass(AbstractExceptionHandler::class, [], '', false, true, true, ['echoExceptionCli']);
        /** @var AbstractExceptionHandler $exceptionHandler */
        $exceptionHandler->setOptions($options);
        $exceptionHandler->injectThrowableStorage($mockThrowableStorage);
        $exceptionHandler->injectLogger($mockLogger);
        $exceptionHandler->handleException($exception);
    }

    /**
     * @test
     */
    public function handleExceptionDoesNotLogInformationAboutTheExceptionInTheSystemLogIfLogExceptionWasTurnedOff()
    {
        $options = [
            'defaultRenderingOptions' => [
                'renderTechnicalDetails' => true,
                'logException' => true
            ],
            'renderingGroups' => [
                'notFoundExceptions' => [
                    'matchingStatusCodes' => [404],
                    'options' => [
                        'logException' => false,
                        'templatePathAndFilename' => 'resource://Neos.Flow/Private/Templates/Error/Default.html',
                        'variables' => [
                            'errorDescription' => 'Sorry, the page you requested was not found.'
                        ]

                    ]
                ]
            ]
        ];

        /** @var Exception|\PHPUnit\Framework\MockObject\MockObject $exception */
        $exception = new NoMatchingRouteException();

        /** @var ThrowableStorageInterface|\PHPUnit\Framework\MockObject\MockObject $mockThrowableStorage */
        $mockThrowableStorage = $this->getMockBuilder(ThrowableStorageInterface::class)->getMock();
        $mockThrowableStorage->expects(self::never())->method('logThrowable');

        $exceptionHandler = $this->getMockForAbstractClass(AbstractExceptionHandler::class, [], '', false, true, true, ['echoExceptionCli']);
        /** @var AbstractExceptionHandler $exceptionHandler */
        $exceptionHandler->setOptions($options);
        $exceptionHandler->injectThrowableStorage($mockThrowableStorage);
        $exceptionHandler->handleException($exception);
    }
}
