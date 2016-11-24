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

use Neos\Utility\ObjectAccess;
use Neos\Flow\Validation\Validator\CollectionValidator;
use Neos\Flow\Validation\Validator\EmailAddressValidator;
use Neos\Flow\Validation\Validator\GenericObjectValidator;
use Neos\Flow\Validation\Validator\IntegerValidator;
use Neos\Flow\Validation\Validator\NumberRangeValidator;
use Neos\Flow\Validation\ValidatorResolver;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the collection validator
 */
class CollectionValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = CollectionValidator::class;

    protected $mockValidatorResolver;

    public function setUp()
    {
        parent::setUp();
        $this->mockValidatorResolver = $this->getMockBuilder(ValidatorResolver::class)->setMethods(['createValidator', 'buildBaseValidatorConjunction'])->getMock();
        $this->validator->_set('validatorResolver', $this->mockValidatorResolver);
    }

    /**
     * @test
     */
    public function collectionValidatorReturnsNoErrorsForANullValue()
    {
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function collectionValidatorFailsForAValueNotBeingACollection()
    {
        $this->assertTrue($this->validator->validate(new \StdClass())->hasErrors());
    }

    /**
     * @test
     */
    public function collectionValidatorValidatesEveryElementOfACollectionWithTheGivenElementValidator()
    {
        $this->validator->_set('options', ['elementValidator' => 'EmailAddress', 'elementValidatorOptions' => []]);
        $this->mockValidatorResolver->expects($this->exactly(4))->method('createValidator')->with('EmailAddress')->will($this->returnValue(new EmailAddressValidator()));

        $arrayOfEmailAddresses = [
            'andreas@neos.io',
            'not a valid address',
            'robert@neos.io',
            'also not valid'
        ];

        $result = $this->validator->validate($arrayOfEmailAddresses);

        $this->assertTrue($result->hasErrors());
        $this->assertEquals(2, count($result->getFlattenedErrors()));
    }

    /**
     * @test
     */
    public function collectionValidatorValidatesNestedObjectStructuresWithoutEndlessLooping()
    {
        $classNameA = 'A' . md5(uniqid(mt_rand(), true));
        eval('class ' . $classNameA . '{ public $b = array(); public $integer = 5; }');
        $classNameB = 'B' . md5(uniqid(mt_rand(), true));
        eval('class ' . $classNameB . '{ public $a; public $c; public $integer = "Not an integer"; }');
        $A = new $classNameA();
        $B = new $classNameB();
        $A->b = [$B];
        $B->a = $A;
        $B->c = [$A];

        $this->mockValidatorResolver->expects($this->any())->method('createValidator')->with('Integer')->will($this->returnValue(new IntegerValidator()));
        $this->mockValidatorResolver->expects($this->any())->method('buildBaseValidatorConjunction')->will($this->returnValue(new GenericObjectValidator()));

        // Create validators
        $aValidator = new GenericObjectValidator([]);
        $this->validator->_set('options', ['elementValidator' => 'Integer', 'elementValidatorOptions' => []]);
        $integerValidator = new IntegerValidator([]);

        // Add validators to properties
        $aValidator->addPropertyValidator('b', $this->validator);
        $aValidator->addPropertyValidator('integer', $integerValidator);

        $result = $aValidator->validate($A)->getFlattenedErrors();
        $this->assertEquals('A valid integer number is expected.', $result['b.0'][0]->getMessage());
    }

    /**
     * @test
     */
    public function collectionValidatorIsValidEarlyReturnsOnUnitializedDoctrinePersistenceCollections()
    {
        $entityManager = $this->getMockBuilder(\Doctrine\ORM\EntityManager::class)->disableOriginalConstructor()->getMock();
        $persistentCollection = new \Doctrine\ORM\PersistentCollection($entityManager, new \Doctrine\ORM\Mapping\ClassMetadata(''), new \Doctrine\Common\Collections\ArrayCollection());
        ObjectAccess::setProperty($persistentCollection, 'initialized', false, true);

        $this->mockValidatorResolver->expects($this->never())->method('createValidator');

        $this->validator->validate($persistentCollection);
    }

    /**
     * @test
     */
    public function collectionValidatorTransfersElementValidatorOptionsToTheElementValidator()
    {
        $elementValidatorOptions = ['minimum' => 5];
        $this->validator->_set('options', ['elementValidator' => 'NumberRange', 'elementValidatorOptions' => $elementValidatorOptions]);
        $this->mockValidatorResolver->expects($this->any())->method('createValidator')->with('NumberRange', $elementValidatorOptions)->will($this->returnValue(new NumberRangeValidator($elementValidatorOptions)));

        $result = $this->validator->validate([5, 6, 1]);

        $this->assertCount(1, $result->getFlattenedErrors());
    }
}
