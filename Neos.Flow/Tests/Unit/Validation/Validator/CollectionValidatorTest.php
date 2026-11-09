<?php

declare(strict_types=1);

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
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Neos\Flow\Validation\Validator\CollectionValidator;
use Neos\Flow\Validation\Validator\GenericObjectValidator;
use Neos\Flow\Validation\Validator\IntegerValidator;
use Neos\Flow\Validation\Validator\NumberRangeValidator;
use Neos\Flow\Validation\ValidatorResolver;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Attributes\Test;

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the collection validator
 */
final class CollectionValidatorTest extends AbstractValidatorTestcase
{
    protected $validatorClassName = CollectionValidator::class;

    protected $mockValidatorResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockValidatorResolver = $this->getMockBuilder(ValidatorResolver::class)->onlyMethods(['createValidator', 'buildBaseValidatorConjunction'])->getMock();
        $this->validator->_set('validatorResolver', $this->mockValidatorResolver);
    }

    #[Test]
    public function collectionValidatorReturnsNoErrorsForANullValue()
    {
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    #[Test]
    public function collectionValidatorFailsForAValueNotBeingACollection()
    {
        self::assertTrue($this->validator->validate(new \StdClass())->hasErrors());
    }

    #[Test]
    public function collectionValidatorValidatesEveryElementOfACollectionWithTheGivenElementValidator()
    {
        $this->validator->_set('options', ['elementValidator' => 'Integer', 'elementValidatorOptions' => []]);
        $this->mockValidatorResolver->expects($this->exactly(4))->method('createValidator')->with('Integer')->willReturn(new IntegerValidator());

        $arrayOfIntegers = [
            1,
            'not a valid integer',
            10,
            'also not valid'
        ];

        $result = $this->validator->validate($arrayOfIntegers);

        self::assertTrue($result->hasErrors());
        self::assertCount(2, $result->getFlattenedErrors());
    }

    #[Test]
    public function collectionValidatorValidatesNestedObjectStructuresWithoutEndlessLooping()
    {
        $classNameA = 'A' . md5(uniqid((string)mt_rand(), true));
        eval('class ' . $classNameA . '{ public $b = array(); public $integer = 5; }');
        $classNameB = 'B' . md5(uniqid((string)mt_rand(), true));
        eval('class ' . $classNameB . '{ public $a; public $c; public $integer = "Not an integer"; }');
        $A = new $classNameA();
        $B = new $classNameB();
        $A->b = [$B];
        $B->a = $A;
        $B->c = [$A];

        $this->mockValidatorResolver->method('createValidator')->with('Integer')->willReturn((new IntegerValidator()));
        $this->mockValidatorResolver->method('buildBaseValidatorConjunction')->willReturn((new GenericObjectValidator()));

        // Create validators
        $aValidator = new GenericObjectValidator([]);
        $this->validator->_set('options', ['elementValidator' => 'Integer', 'elementValidatorOptions' => []]);
        $integerValidator = new IntegerValidator([]);

        // Add validators to properties
        $aValidator->addPropertyValidator('b', $this->validator);
        $aValidator->addPropertyValidator('integer', $integerValidator);

        $result = $aValidator->validate($A)->getFlattenedErrors();
        self::assertEquals('A valid integer number is expected.', $result['b.0'][0]->getMessage());
    }

    #[Test]
    public function collectionValidatorIsValidEarlyReturnsOnUnitializedDoctrinePersistenceCollections()
    {
        $entityManager = $this->createStub(EntityManager::class);
        $persistentCollection = new PersistentCollection($entityManager, new ClassMetadata(''), new ArrayCollection());
        ObjectAccess::setProperty($persistentCollection, 'initialized', false, true);

        $this->mockValidatorResolver->expects($this->never())->method('createValidator');

        $this->validator->validate($persistentCollection);
    }

    #[Test]
    public function collectionValidatorIsValidEarlyReturnsOnUnitializedDoctrineAbstractLazyCollections()
    {
        $doctrineArrayCollection = $this->createMock(AbstractLazyCollection::class);
        $doctrineArrayCollection->method('isInitialized')->willReturn(false);

        $this->mockValidatorResolver->expects($this->never())->method('createValidator');

        $this->validator->validate($doctrineArrayCollection);
    }

    #[Test]
    public function collectionValidatorTransfersElementValidatorOptionsToTheElementValidator()
    {
        $elementValidatorOptions = ['minimum' => 5];
        $this->validator->_set('options', ['elementValidator' => 'NumberRange', 'elementValidatorOptions' => $elementValidatorOptions]);
        $this->mockValidatorResolver->method('createValidator')->with('NumberRange', $elementValidatorOptions)->willReturn((new NumberRangeValidator($elementValidatorOptions)));

        $result = $this->validator->validate([5, 6, 1]);

        self::assertCount(1, $result->getFlattenedErrors());
    }
}
