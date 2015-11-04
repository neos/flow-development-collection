<?php
namespace TYPO3\Flow\Tests\Unit\Validation\Validator;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
