<?php
namespace Neos\Flow\Tests\Unit\Validation\Validator;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Exception\InvalidValidationOptionsException;
use Neos\Flow\Validation\Validator\AbstractValidator;

/**
 * Testcase for the Abstract Validator
 */
class AbstractValidatorTest extends UnitTestCase
{
    protected $validator;

    protected function setUp(): void
    {
        $this->validator = $this->getAccessibleMockForAbstractClass(AbstractValidator::class, [], '', false);
        $this->validator->_set('supportedOptions', [
            'placeHolder'    => ['default', 'Desc', 'mixed', false],
            'secondPlaceHolder' => ['default', 'Desc', 'mixed'],
            'thirdPlaceHolder'  => ['default', 'Desc', 'mixed', true],
        ]);
    }

    /**
     * @test
     */
    public function abstractValidatorConstructWithRequiredOptionShouldNotFail()
    {
        $this->validator->__construct(['thirdPlaceHolder' => 'dummy']);

        self::assertInstanceOf(AbstractValidator::class, $this->validator);
    }

    /**
     * @test
     */
    public function abstractValidatorConstructWithoutRequiredOptionShouldFail()
    {
        $this->expectException(InvalidValidationOptionsException::class);
        $this->validator->__construct([]);
    }
}
