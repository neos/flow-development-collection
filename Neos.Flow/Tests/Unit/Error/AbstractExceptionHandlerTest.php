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
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the Abstract Exception Handler
 */
class AbstractExceptionHandlerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function handleExceptionLogsInformationAboutTheExceptionInTheSystemLog()
    {
        $options = [
            'defaultRenderingOptions' => [
                'renderTechnicalDetails' => true,
                'logException' => true
            ],
            'renderingGroups' => []
        ];

        $exception = new \Exception('The Message', 12345);

        $mockSystemLogger = $this->createMock(SystemLoggerInterface::class);
        $mockSystemLogger->expects($this->once())->method('logException')->with($exception);

        $exceptionHandler = $this->getMockForAbstractClass(AbstractExceptionHandler::class, [], '', false, true, true, ['echoExceptionCli']);
        /** @var AbstractExceptionHandler $exceptionHandler */
        $exceptionHandler->setOptions($options);
        $exceptionHandler->injectSystemLogger($mockSystemLogger);
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

        /** @var Exception|\PHPUnit_Framework_MockObject_MockObject $exception */
        $exception = new NoMatchingRouteException();

        /** @var SystemLoggerInterface|\PHPUnit_Framework_MockObject_MockObject $mockSystemLogger */
        $mockSystemLogger = $this->getMockBuilder(SystemLoggerInterface::class)->getMock();
        $mockSystemLogger->expects($this->never())->method('logException');

        $exceptionHandler = $this->getMockForAbstractClass(AbstractExceptionHandler::class, [], '', false, true, true, ['echoExceptionCli']);
        /** @var AbstractExceptionHandler $exceptionHandler */
        $exceptionHandler->setOptions($options);
        $exceptionHandler->injectSystemLogger($mockSystemLogger);
        $exceptionHandler->handleException($exception);
    }
}
