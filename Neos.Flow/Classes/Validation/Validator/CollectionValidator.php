<?php
namespace Neos\Flow\Validation\Validator;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Utility\TypeHandling;

/**
 * A generic collection validator.
 *
 * @api
 */
class CollectionValidator extends GenericObjectValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'elementValidator' => [null, 'The validator type to use for the collection elements', 'string'],
        'elementValidatorOptions' => [[], 'The validator options to use for the collection elements', 'array'],
        'elementType' => [null, 'The type of the elements in the collection', 'string'],
        'validationGroups' => [null, 'The validation groups to link to', 'string'],
    ];

    /**
     * @var \Neos\Flow\Validation\ValidatorResolver
     * @Flow\Inject
     */
    protected $validatorResolver;

    /**
     * Checks for a collection and if needed validates the items in the collection.
     * This is done with the specified element validator or a validator based on
     * the given element type and validation group.
     *
     * Either elementValidator or elementType must be given, otherwise validation
     * will be skipped.
     *
     * @param mixed $value A collection to be validated
     * @return void
     */
    protected function isValid($value)
    {
        if ($value instanceof \Doctrine\Common\Collections\AbstractLazyCollection && !$value->isInitialized()) {
            return;
        } elseif ((is_object($value) && !TypeHandling::isCollectionType(get_class($value))) && !is_array($value)) {
            $this->addError('The given subject was not a collection.', 1317204797);
            return;
        } elseif (is_object($value) && $this->isValidatedAlready($value)) {
            return;
        }

        foreach ($value as $index => $collectionElement) {
            if (isset($this->options['elementValidator'])) {
                $collectionElementValidator = $this->validatorResolver->createValidator($this->options['elementValidator'], $this->options['elementValidatorOptions']);
            } elseif (isset($this->options['elementType'])) {
                if (isset($this->options['validationGroups'])) {
                    $collectionElementValidator = $this->validatorResolver->getBaseValidatorConjunction($this->options['elementType'], $this->options['validationGroups']);
                } else {
                    $collectionElementValidator = $this->validatorResolver->getBaseValidatorConjunction($this->options['elementType']);
                }
            } else {
                return;
            }
            if ($collectionElementValidator instanceof ObjectValidatorInterface) {
                $collectionElementValidator->setValidatedInstancesContainer($this->validatedInstancesContainer);
            }

            $this->getResult()->forProperty($index)->merge($collectionElementValidator->validate($collectionElement));
        }
    }
}
