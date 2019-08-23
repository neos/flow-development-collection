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

use Neos\Error\Messages\Result;

/**
 * Testcase for the Error Container object
 */
class ResultTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     * @var Result
     */
    protected $result;

    protected function setUp(): void
    {
        $this->result = new Result();
    }

    public function dataTypes()
    {
        return [
            ['Error', 'Errors'],
            ['Warning', 'Warnings'],
            ['Notice', 'Notices']
        ];
    }

    protected function getMockMessage(string $type)
    {
        return $this->getMockBuilder('Neos\Error\Messages\\' . $type)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     * @dataProvider dataTypes
     */
    public function addedMessagesShouldBeRetrievableAgain(string $dataTypeInSingular, string $dataTypeInPlural)
    {
        $message = $this->getMockMessage($dataTypeInSingular);
        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->$addMethodName($message);

        $getterMethodName = 'get' . $dataTypeInPlural;
        self::assertEquals([$message], $this->result->$getterMethodName());
    }

    /**
     * @test
     * @dataProvider dataTypes
     */
    public function getMessageShouldNotBeRecursive(string $dataTypeInSingular, string $dataTypeInPlural)
    {
        $message = $this->getMockMessage($dataTypeInSingular);
        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->forProperty('foo')->$addMethodName($message);

        $getterMethodName = 'get' . $dataTypeInPlural;
        self::assertEquals([], $this->result->$getterMethodName());
    }

    /**
     * @test
     * @dataProvider dataTypes
     */
    public function getFirstMessageShouldReturnFirstMessage(string $dataTypeInSingular, string $dataTypeInPlural)
    {
        $message1 = $this->getMockMessage($dataTypeInSingular);
        $message2 = $this->getMockMessage($dataTypeInSingular);
        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->$addMethodName($message1);
        $this->result->$addMethodName($message2);

        $getterMethodName = 'getFirst' . $dataTypeInSingular;
        self::assertSame($message1, $this->result->$getterMethodName());
    }

    /**
     * @test
     */
    public function forPropertyShouldReturnSubResult()
    {
        $container2 = $this->result->forProperty('foo.bar');
        self::assertInstanceOf(Result::class, $container2);
        self::assertSame($container2, $this->result->forProperty('foo')->forProperty('bar'));
    }

    /**
     * @test
     */
    public function forPropertyWithEmptyStringShouldReturnSelf()
    {
        $container2 = $this->result->forProperty('');
        self::assertSame($container2, $this->result);
    }

    /**
     * @test
     */
    public function forPropertyWithNullShouldReturnSelf()
    {
        $container2 = $this->result->forProperty(null);
        self::assertSame($container2, $this->result);
    }

    /**
     * @test
     * @dataProvider dataTypes
     */
    public function hasMessagesShouldReturnTrueIfTopLevelObjectHasMessages(string $dataTypeInSingular, string $dataTypeInPlural)
    {
        $message = $this->getMockMessage($dataTypeInSingular);
        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->$addMethodName($message);

        $methodName = 'has' . $dataTypeInPlural;
        self::assertTrue($this->result->$methodName());
    }

    /**
     * @test
     * @dataProvider dataTypes
     */
    public function hasMessageshouldReturnTrueIfSubObjectHasErrors(string $dataTypeInSingular, string $dataTypeInPlural)
    {
        $addMethodName = 'add' . $dataTypeInSingular;
        $methodName = 'has' . $dataTypeInPlural;

        $message = $this->getMockMessage($dataTypeInSingular);
        $this->result->forProperty('foo.bar')->$addMethodName($message);
        self::assertTrue($this->result->$methodName());
    }

    /**
     * @test
     * @dataProvider dataTypes
     */
    public function hasMessagesShouldReturnFalseIfSubObjectHasNoErrors(string $dataTypeInSingular, string $dataTypeInPlural)
    {
        $methodName = 'has' . $dataTypeInPlural;

        $this->result->forProperty('foo.baz');
        $this->result->forProperty('foo.bar');
        self::assertFalse($this->result->$methodName());
    }

    /**
     * @test
     * @dataProvider dataTypes
     */
    public function getFlattenedMessagesShouldReturnAllSubMessages(string $dataTypeInSingular, string $dataTypeInPlural)
    {
        $message1 = $this->getMockMessage($dataTypeInSingular);
        $message2 = $this->getMockMessage($dataTypeInSingular);
        $message3 = $this->getMockMessage($dataTypeInSingular);
        $message4 = $this->getMockMessage($dataTypeInSingular);
        $message5 = $this->getMockMessage($dataTypeInSingular);

        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->forProperty('foo.bar')->$addMethodName($message1);
        $this->result->forProperty('foo.baz')->$addMethodName($message2);
        $this->result->forProperty('foo')->$addMethodName($message3);
        $this->result->$addMethodName($message4);
        $this->result->$addMethodName($message5);

        $getMethodName = 'getFlattened' . $dataTypeInPlural;
        $expected = [
            '' => [$message4, $message5],
            'foo' => [$message3],
            'foo.bar' => [$message1],
            'foo.baz' => [$message2]

        ];
        self::assertEquals($expected, $this->result->$getMethodName());
    }

    /**
     * @test
     * @dataProvider dataTypes
     */
    public function getFlattenedMessagesShouldNotContainEmptyResults(string $dataTypeInSingular, string $dataTypeInPlural)
    {
        $message1 = $this->getMockMessage($dataTypeInSingular);
        $message2 = $this->getMockMessage($dataTypeInSingular);

        $addMethodName = 'add' . $dataTypeInSingular;
        $this->result->forProperty('foo.bar')->$addMethodName($message1);
        $this->result->forProperty('foo.baz')->$addMethodName($message2);

        $getMethodName = 'getFlattened' . $dataTypeInPlural;
        $expected = [
            'foo.bar' => [$message1],
            'foo.baz' => [$message2]

        ];
        self::assertEquals($expected, $this->result->$getMethodName());
    }

    /**
     * @test
     */
    public function mergeShouldMergeTwoResults()
    {
        $notice1 = $this->getMockMessage('Notice');
        $notice2 = $this->getMockMessage('Notice');
        $notice3 = $this->getMockMessage('Notice');
        $warning1 = $this->getMockMessage('Warning');
        $warning2 = $this->getMockMessage('Warning');
        $warning3 = $this->getMockMessage('Warning');
        $error1 = $this->getMockMessage('Error');
        $error2 = $this->getMockMessage('Error');
        $error3 = $this->getMockMessage('Error');

        $otherResult = new Result();

        $otherResult->addNotice($notice1);
        $otherResult->forProperty('foo.bar')->addNotice($notice2);
        $this->result->forProperty('foo')->addNotice($notice3);

        $otherResult->addWarning($warning1);
        $this->result->addWarning($warning2);
        $this->result->addWarning($warning3);

        $otherResult->forProperty('foo')->addError($error1);
        $otherResult->forProperty('foo')->addError($error2);
        $otherResult->addError($error3);

        $this->result->merge($otherResult);

        self::assertSame([$notice1], $this->result->getNotices(), 'Notices are not merged correctly without recursion');
        self::assertSame([$notice3], $this->result->forProperty('foo')->getNotices(), 'Original sub-notices are overridden.');
        self::assertSame([$notice2], $this->result->forProperty('foo')->forProperty('bar')->getNotices(), 'Sub-notices are not copied.');

        self::assertSame([$warning2, $warning3, $warning1], $this->result->getWarnings());

        self::assertSame([$error3], $this->result->getErrors());
        self::assertSame([$error1, $error2], $this->result->forProperty('foo')->getErrors());
    }
}
