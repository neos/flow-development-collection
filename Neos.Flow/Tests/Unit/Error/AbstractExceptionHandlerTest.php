<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Test case for the Abstract Exception Handler
 */
final class AbstractExceptionHandlerTest extends UnitTestCase
{
    #[Test]
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
        $mockThrowableStorage->expects($this->once())->method('logThrowable')->with($exception)->willReturn('Exception got logged!');

        $mockLogger = $this->createStub(LoggerInterface::class);

        $exceptionHandler = $this->createPartialMock(AbstractExceptionHandler::class, ['echoExceptionWeb', 'echoExceptionCli']);
        /** @var AbstractExceptionHandler $exceptionHandler */
        $exceptionHandler->setOptions($options);
        $exceptionHandler->injectThrowableStorage($mockThrowableStorage);
        $exceptionHandler->injectLogger($mockLogger);
        $exceptionHandler->handleException($exception);
    }

    #[Test]
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
                        'viewOptions' => [
                            'templatePathAndFilename' => 'resource://Neos.Flow/Private/Templates/Error/Default.html',
                        ],
                        'variables' => [
                            'errorDescription' => 'Sorry, the page you requested was not found.'
                        ]

                    ]
                ]
            ]
        ];

        /** @var Exception|MockObject $exception */
        $exception = new NoMatchingRouteException();

        /** @var ThrowableStorageInterface|MockObject $mockThrowableStorage */
        $mockThrowableStorage = $this->createMock(ThrowableStorageInterface::class);
        $mockThrowableStorage->expects($this->never())->method('logThrowable');

        $exceptionHandler = $this->createPartialMock(AbstractExceptionHandler::class, ['echoExceptionWeb', 'echoExceptioncli']);
        /** @var AbstractExceptionHandler $exceptionHandler */
        $exceptionHandler->setOptions($options);
        $exceptionHandler->injectThrowableStorage($mockThrowableStorage);
        $exceptionHandler->handleException($exception);
    }
}
