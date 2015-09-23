<?php
namespace TYPO3\Flow\Tests\Unit\Error;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */
use TYPO3\Flow\Error\AbstractExceptionHandler;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the Abstract Exception Handler
 */
class AbstractExceptionHandlerTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function handleExceptionLogsInformationAboutTheExceptionInTheSystemLog()
    {
        $options = array(
            'defaultRenderingOptions' => array(
                'renderTechnicalDetails' => true,
                'logException' => true
            ),
            'renderingGroups' => array()
        );

        $exception = new \Exception('The Message', 12345);

        $mockSystemLogger = $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface');
        $mockSystemLogger->expects($this->once())->method('logException')->with($exception);

        $exceptionHandler = $this->getMockForAbstractClass('TYPO3\Flow\Error\AbstractExceptionHandler', array(), '', false);
        /** @var AbstractExceptionHandler $exceptionHandler */
        $exceptionHandler->setOptions($options);
        $exceptionHandler->injectSystemLogger($mockSystemLogger);
        $exceptionHandler->handleException($exception);
    }

    /**
     * @test
     * @return void
     */
    public function handleExceptionDoesNotLogInformationAboutTheExceptionInTheSystemLogIfLogExceptionWasTurnedOff()
    {
        $options = array(
            'defaultRenderingOptions' => array(
                'renderTechnicalDetails' => true,
                'logException' => true
            ),
            'renderingGroups' => array(
                'notFoundExceptions' => array(
                    'matchingStatusCodes' => array(404),
                    'options' => array(
                        'logException' => false,
                        'templatePathAndFilename' => 'resource://TYPO3.Flow/Private/Templates/Error/Default.html',
                        'variables' => array(
                            'errorDescription' => 'Sorry, the page you requested was not found.'
                        )

                    )
                )
            )
        );

        $exception = $this->getAccessibleMock('TYPO3\Flow\Exception', array('dummy'), array('Not Found ...', 1407751877));
        $exception->_set('statusCode', 404);

        $mockSystemLogger = $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface');
        $mockSystemLogger->expects($this->never())->method('logException')->with($exception);

        $exceptionHandler = $this->getMockForAbstractClass('TYPO3\Flow\Error\AbstractExceptionHandler', array(), '', false);
        /** @var AbstractExceptionHandler $exceptionHandler */
        $exceptionHandler->setOptions($options);
        $exceptionHandler->injectSystemLogger($mockSystemLogger);
        $exceptionHandler->handleException($exception);
    }
}
