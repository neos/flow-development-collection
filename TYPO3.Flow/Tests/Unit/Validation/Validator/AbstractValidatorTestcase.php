<?php
namespace TYPO3\Flow\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the Abstract Validator
 *
 */
abstract class AbstractValidatorTestcase extends \TYPO3\Flow\Tests\UnitTestCase
{
    protected $validatorClassName;

    /**
     *
     * @var \TYPO3\Flow\Validation\Validator\ValidatorInterface
     */
    protected $validator;

    public function setUp()
    {
        $this->validator = $this->getValidator();
    }

    protected function getValidator($options = array())
    {
        return $this->getAccessibleMock($this->validatorClassName, array('dummy'), array($options), '', true);
    }

    protected function validatorOptions($options)
    {
        $this->validator = $this->getValidator($options);
    }
}
