<?php
namespace Neos\Error\Messages\Tests\Unit;

/*
 * This file is part of the Neos.Error.Messages package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Message;

/**
 * Testcase for the Message object
 */
class MessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function constructorSetsMessage()
    {
        $someMessage = 'The message';
        $someMessageCode = 12345;
        $message = new Message($someMessage, $someMessageCode);
        $this->assertEquals($someMessage, $message->getMessage());
    }

    /**
     * @test
     */
    public function constructorSetsArguments()
    {
        $someArguments = ['Foo', 'Bar'];
        $someMessageCode = 12345;
        $message = new Message('', $someMessageCode, $someArguments);
        $this->assertEquals($someArguments, $message->getArguments());
    }

    /**
     * @test
     */
    public function constructorSetsCode()
    {
        $someMessage = 'The message';
        $someMessageCode = 12345;
        $message = new Message($someMessage, $someMessageCode);
        $this->assertEquals($someMessageCode, $message->getCode());
    }

    /**
     * @test
     */
    public function renderReturnsTheMessageTextIfNoArgumentsAreSpecified()
    {
        $someMessage = 'The message';
        $someMessageCode = 12345;
        $message = new Message($someMessage, $someMessageCode);
        $this->assertEquals($someMessage, $message->render());
    }

    /**
     * @test
     */
    public function renderReplacesArgumentsInTheMessageText()
    {
        $someMessage = 'The message with %2$s and %1$s';
        $someArguments = ['Foo', 'Bar'];
        $someMessageCode = 12345;
        $message = new Message($someMessage, $someMessageCode, $someArguments);

        $expectedResult = 'The message with Bar and Foo';
        $actualResult = $message->render();
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function convertingTheMessageToStringRendersIt()
    {
        $someMessage = 'The message with %2$s and %1$s';
        $someArguments = ['Foo', 'Bar'];
        $someMessageCode = 12345;
        $message = new Message($someMessage, $someMessageCode, $someArguments);

        $expectedResult = 'The message with Bar and Foo';
        $actualResult = (string)$message;
        $this->assertEquals($expectedResult, $actualResult);
    }
}
