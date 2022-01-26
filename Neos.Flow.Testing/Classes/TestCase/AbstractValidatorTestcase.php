<?php
namespace Neos\Flow\Testing\TestCase;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Validation\Validator\ValidatorInterface;

/**
 * Testcase for the Abstract Validator
 */
abstract class AbstractValidatorTestcase extends \Neos\Flow\Testing\TestCase\UnitTestCase
{
    protected $validatorClassName;

    /**
     *
     * @var ValidatorInterface
     */
    protected $validator;

    protected function setUp(): void
    {
        $this->validator = $this->getValidator();
    }

    protected function getValidator($options = [])
    {
        return $this->getAccessibleMock($this->validatorClassName, ['dummy'], [$options], '', true);
    }

    protected function validatorOptions($options)
    {
        $this->validator = $this->getValidator($options);
    }
}
