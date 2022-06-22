﻿==========
Validation
==========

Validation in web applications is a very crucial topic: Almost all data which is entered by
an end user needs some checking rules, no matter if he enters an e-mail address or a subject
for a forum posting.

While validation itself is quite simple, embedding it into the rest of the framework is not:
If the user has entered a wrong value, the original page has to be re-displayed, and the user
needs some well-readable information on what data he should enter.

This chapter explains:

* how to use the validators being part of Flow
* how to write your own validators
* how to use validation in your own code
* how validation is embedded in the model, the persistence and the MVC layer

Automatic Validation Throughout The Framework
=============================================

Inside Flow, validation is triggered automatically at two places: When an object is *persisted*, its
*base validators* are checked as explained in the last section. Furthermore, validation happens in
the MVC layer when a Domain Model is used as a controller argument, directly after Property Mapping.

.. warning::

	If a validation error occurs during persistence, there is no way to catch this error
	and handle it -- as persistence is executed at the end of every request *after the response
	has been sent to the client*.

	Thus, validation on persistence is merely a safeguard for preventing invalid data to be stored
	in the database.

When validation in the MVC layer happens, it is possible to handle errors correctly. In a nutshell,
the process is as follows:

* an array of data is received from the client
* it is transformed to an object using Property Mapping
* this object is validated using the base validators
* if there is a property mapping or validation error, the last page (which usually contains an
  edit-form) is re-displayed, an error message is shown and the erroneous field is highlighted.

.. tip::

	If you want to suppress the re-display of the last page (which is handled through
	``errorAction()``, you can add a ``@Flow\IgnoreValidation("$comment")`` annotation
	to the docblock of the corresponding controller action.

Normally, you build up your Controller with separate actions for displaying a form to edit an entity
and another action to actually create/remove/update the entity. For those actions the validation for
Domain Model arguments is triggered as explained above. So in order for the automatic re-display of the
previous edit form to work, the validation inside that action needs to be suppressed, or else it would
itself possibly fail the validation and try to redirect to previous action, ending up in an infinite loop.

.. code-block:: php

    class CommentController extends \Neos\Flow\Mvc\Controller\ActionController
    {

        /**
         * @param \YourPackage\Domain\Model\Comment $comment
         * @Flow\IgnoreValidation("$comment")
         */
        public function editAction(\YourPackage\Domain\Model\Comment $comment)
        {
            // here, $comment is not necessarily a valid object
        }

        /**
         * @param \YourPackage\Domain\Model\Comment $comment
         */
        public function updateAction(\YourPackage\Domain\Model\Comment $comment)
        {
            // here, $comment is a valid object
        }
    }

.. warning::

	You should *always* annotate the model arguments of your form displaying actions to ignore
	validation, or else you might end up with an infinite loop on failing validation.

Furthermore, it is also possible to execute *additional validators* only for specific action
arguments using ``@Flow\Validate`` inside a controller action:

.. code-block:: php

    class CommentController extends \Neos\Flow\Mvc\Controller\ActionController {

        /**
         * @param \YourPackage\Domain\Model\Comment $comment
         * @Flow\Validate(argumentName="comment", type="YourPackage:SomeSpecialValidator")
         */
        public function updateAction(\YourPackage\Domain\Model\Comment $comment)
        {
            // here, $comment is a valid object
        }
    }

.. tip::

	It is also possible to add an additional validator for a sub object of the argument, using
	the "dot-notation": ``@Flow\Validate(argumentName="comment.text", type="....")``.

However, it is a rather rare use-case that a validation rule needs to be defined only in the controller.

Using Validators & The ValidatorResolver
========================================

A validator is a PHP class being responsible for checking validity of a certain object or
simple type.

All validators implement ``\Neos\Flow\Validation\Validator\ValidatorInterface``, and
the API of every validator is demonstrated in the following code example:

.. code-block:: php

    // NOTE: you should always use the ValidatorResolver to create new
    // validators, as it is demonstrated in the next section.
    $validator = new \Neos\Flow\Validation\Validator\StringLengthValidator(array(
        'minimum' => 10,
        'maximum' => 20
    ));

    // $result is of type Neos\Error\Messages\Result
    $result = $validator->validate('myExampleString');
    $result->hasErrors(); // is false, as the string is longer than 10 characters.

    $result = $validator->validate('short');
    $result->hasErrors(); // is true, as the string is too short.
    $result->getFirstError()->getMessage(); // contains the human-readable error message

On the above example, it can be seen that validators can be *re-used* for different input.
Furthermore, a validator does not only just return true or false, but instead returns
a ``Result`` object which you can ask whether any errors happened. Please see the API
for a detailed description.

.. note::

	The ``Neos\Error\Messages\Result`` object has been introduced in order to
	make more structured error output possible -- which is especially needed when
	objects with sub-properties should be validated recursively.

Creating Validator Instances: The ValidatorResolver
---------------------------------------------------

As validators can be both singleton or prototype objects (depending if they have internal state),
you should not instantiate them directly as it has been done in the above example. Instead,
you should use the ``\Neos\Flow\Validation\ValidatorResolver`` singleton to get a new instance
of a certain validator::

    $validatorResolver->createValidator($validatorType, array $validatorOptions);

``$validatorType`` can be one of the following:

* a fully-qualified class name to a validator, like ``Your\Package\Validation\Validator\FooValidator``
* If you stick to the ``<PackageKey>\Validation\Validator\<ValidatorName>Validator`` convention,
  you can also fetch the above validator using ``Your.Package:Foo`` as ``$validatorType``.

  **This is the recommended way for custom validators.**
* For the standard validators inside the ``Neos.Flow`` package, you can leave out the package key,
  so you can use ``EmailAddress`` to fetch ``Neos\Flow\Validation\Validator\EmailAddressValidator``

The ``$validatorOptions`` parameter is an associative array of validator options. See the validator
reference in the appendix for the configuration options of the built-in validators.


Default Validators
------------------

Flow is shipped with a big list of validators which are ready to use -- see the appendix for the full
list. Here, we just want to highlight some more special validators.

Additional to the simple validators for strings, numbers and other basic types, Flow has a few powerful
validators shipped:

* ``GenericObjectValidator`` validates an object by validating all of its properties. This validator
  is often used internally, but will rarely be used directly.
* ``CollectionValidator`` validates a collection of objects. This validator is often used internally,
  but will rarely be used directly.
* ``ConjunctionValidator`` and ``DisjunctionValidator`` implement logical AND / OR conditions.

Furthermore, almost all validators of simple types regard ``null`` and the empty string (``''``) as **valid**.
The only exception is the ``NotEmpty`` validator, which disallows both ``null`` and empty string. This means
if you want to validate that a property is e.g. an email address *and* does exist, you need to combine the two
validators using a ``ConjunctionValidator``::

    $conjunctionValidator = $validatorResolver->createValidator('Conjunction');
    $conjunctionValidator->addValidator($validatorResolver->createValidator('NotEmpty'));
    $conjunctionValidator->addValidator($validatorResolver->createValidator('EmailAddress'));

Validating Domain Models
========================

It is very common that a full Domain Model should be validated instead of only a simple type.
To make this use-case more easy, the ``ValidatorResolver`` has a method ``getBaseValidatorConjunction``
which returns a fully-configured validator for an arbitrary Domain Object::

    $commentValidator = $validatorResolver->getBaseValidatorConjunction(
        \YourPackage\Domain\Model\Comment::class, // class name of the object to validate
        ['Default']                               // optional validation groups to use during validation
    );
    $result = $commentValidator->validate($comment);

The returned validator checks the following things:

* All *property validation rules* configured through ``@Flow\Validate`` annotations on properties of the model:

  .. code-block:: php

    namespace YourPackage\Domain\Model;
    use Neos\Flow\Annotations as Flow;

    class Comment
    {

        /**
         * @Flow\Validate(type="NotEmpty")
         */
        protected $text;

        // Add getters and setters here
    }

  It also correctly builds up validators for ``Collections`` or ``arrays``, if they are properly
  typed (``Doctrine\Common\Collection<YourPackage\Domain\Model\Author>``).

* In addition to validating the individual properties on the model, it checks whether a designated *Domain Model
  Validator* exists; i.e. for the Domain Model ``YourPackage\Domain\Model\Comment`` it is checked
  whether ``YourPackage\Domain\Validator\CommentValidator`` exists. If it exists, it is automatically
  called on validation.

  These *Domain Model Validators* can also mark some specific properties as failed and add specific error messages:

  .. code-block:: php

    class CommentValidator extends AbstractValidator
    {
        public function isValid($value)
        {
            if ($value instanceof \YourPackage\Domain\Model\Comment) {
                $this->pushResult()->forProperty('text')->addError(
                                new Error('text can´t be empty.', 1221560910)
                            );
            }
        }
    }

Normally, you would need to annotate Collection and Model type properties, so that the collection elements and
the model would be validated like this:

.. code-block:: php

        /**
         * @var SomeDomainModel
         * @Flow\Validate(type="GenericObject")
         */
        protected $someRelatedModel;

        /**
         * @var Collection<SomeOtherDomainModel>
         * @Flow\Validate(type="Collection")
         */
        protected $someOtherRelatedModels;

For convenience, those validators will be added automatically if they are left out, because Flow will always validate
Model hierarchies. In some cases, it might be necessary to override validation behaviour of those properties,
e.g. when you want to limit validation with Validation Groups (see below). In that case, you can just explicitly annotate
the property with additional options and this will then override the automatically generated validator.

When specifying a Domain Model as an argument of a controller action, all the above validations will be
automatically executed. This is explained in detail in the following section.

Validation on Aggregates
------------------------

In Domain Driven Design, the ``Aggregate`` is to be considered a *consistency boundary*, meaning that the whole
``Aggregate`` needs to preserve it's invariants at all times. For that reason, validation inside an ``Aggregate`` will
cascade into all entities and force relations to be loaded. So if you have designed large ``Aggregates`` with a deep
hierarchy of many n-ToMany relations, validation can easily become a performance bottleneck.

It is therefore, but not limited to this reason, highly recommended to keep your ``Aggregates`` small. The validation
will stop at an ``Aggregate Root``, if the relation to it is lazy and not yet loaded. Entity relations are lazy by default,
and as long as you don't also submit parts of the related ``Aggregate``, it will not get loaded before the validation
kicks in.

.. tip:: Be careful though, that loading the related Aggregate in your Controller will still make it get validated
		 during persistence. That is another good reason why you should try to minimize relations between Aggregates and if
		 possible, try to stick to a simple identifier instead of an object relation.

For a good read on designing Aggregates, you are highly encouraged to take a read on Vaughn Vernon's essay series
`Effective Aggregate Design`_.


Advanced Feature: Partial Validation
====================================

If you only want to validate parts of your objects, f.e. want to store incomplete objects in
the database, you can assign special *Validation Groups* to your validators.

It is possible to specify a list of validation groups at each ``@Flow\Validate`` annotation,
if none is specified the validation group ``Default`` is assigned to the validator.

When *invoking* validation, f.e. in the MVC layer or in persistence, only validators with
certain validation groups are executed:

* In MVC, the validation group ``Default`` and ``Controller`` is used.
* In persistence, the validation group ``Default`` and ``Persistence`` is used.

Additionally, it is possible to specify a list of validation groups at each controller action
via the ``@Flow\ValidationGroups`` annotation. This way, you can override the default
validation groups that are invoked on this action call, for example when you need to
validate uniqueness of a property like an e-mail adress only in your createAction.

A validator is only executed if at least one validation group overlap.

The following example demonstrates this:

.. code-block:: php

    class Comment
    {

        /**
         * @Flow\Validate(type="NotEmpty")
         */
        protected $prop1;

        /**
         * @Flow\Validate(type="NotEmpty", validationGroups={"Default"})
         */
        protected $prop2;

        /**
         * @Flow\Validate(type="NotEmpty", validationGroups={"Persistence"})
         */
        protected $prop3;

        /**
         * @Flow\Validate(type="NotEmpty", validationGroups={"Controller"})
         */
        protected $prop4;

        /**
         * @Flow\Validate(type="NotEmpty", validationGroups={"createAction"})
         */
        protected $prop5;
    }

    class CommentController extends \Neos\Flow\Mvc\Controller\ActionController
    {

        /**
         * @param Comment $comment
         * @Flow\ValidationGroups({"createAction"})
         */
        public function createAction(Comment $comment)
        {
            ...
        }
    }

* validation for prop1 and prop2 are the same, as the "Default" validation group is added if none is specified
* validation for prop1 and prop2 are executed both on persisting and inside the controller
* validation for $prop3 is only executed in persistence, but not in controller
* validation for $prop4 is only executed in controller, but not in persistence
* validation for $prop5 is only executed in createAction, but not in persistence

If interacting with the ``ValidatorResolver`` directly, the to-be-used validation groups
can be specified as the last argument of ``getBaseValidatorConjunction()``.

.. note::
  When trying to set the validation groups of a collection or a whole model, which are normally not annotated for
  you can explicitly specify a "Collection" or "GenericObject" type validator on the property and set the according validationGroup.

Avoiding Duplicate Validation and Recursion
===========================================

Unlike simple types, objects (or collections) may reference other objects, potentially leading
to recursion during the validation and multiple validation of the same instance.

To avoid this the ``GenericObjectValidator`` as well as anything extending ``AbstractCompositeValidator``
keep track of instances that have already been validated. The container to keep track of these instances
can be (re-)set using ``setValidatedInstancesContainer`` defined in the ``ObjectValidatorInterface``.

Flow resets this container before doing validation automatically. If you use validation directly in
your controller, you should reset the container directly before validation, after any changes have been
done.

When implementing your own validators (see below), you need to pass the container around and check instances
against it. See ``AbstractCompositeValidator`` and ``isValidatedAlready`` in the ``GenericObjectValidator``
for examples of how to do this.

Another optimization option of the ``GenericObjectValidator`` is the ``skipUnInitializedProxies`` flag. When
set to true, it allows to skip validation of uninitialized proxy instances, to avoid recursions down into
unchanged hierarchies. This can avoid loading of data for validation and is safe, if you can rely on your data
not being changed and thus making an entity state invalid "from the outside."

Writing Validators
==================

Usually, when writing your own validator, you will not directly implement ``ValidatorInterface``, but
rather subclass ``AbstractValidator``. You only need to specify any options your validator might use and
implement the ``isValid()`` method then:

.. code-block:: php

    /**
     * A validator for checking items against foos.
     */
    class MySpecialValidator extends \Neos\Flow\Validation\Validator\AbstractValidator
    {

        /**
         * @var array
         */
        protected $supportedOptions = array(
            'foo' => array(null, 'The foo value to accept as valid', 'mixed', true)
        );

        /**
         * Check if the given value is a valid foo item. What constitutes a valid foo is determined through the 'foo' option.
         *
         * @param mixed $value
         * @return void
         */
        protected function isValid($value) {
            if (!isset($this->options['foo'])) {
                throw new \Neos\Flow\Validation\Exception\InvalidValidationOptionsException(
                    'The option "foo" for this validator needs to be specified', 12346788
                );
            }

            if ($value !== $this->options['foo']) {
                $this->addError('The value must be equal to "%s"', 435346321, array($this->options['foo']));
            }
        }
    }

In the above example, the ``isValid()`` method has been implemented, and the parameter ``$value`` is the
data we want to check for validity. In case the data is valid, nothing needs to be done.

.. warning:: You should avoid overwriting ``validate()`` and if you do, you should never overwrite ``$this->result``
			 instance variable of the validator. Instead, use ``pushResult()`` to create a new result object and at
			 the end of your validator, return ``popResult()``.

In case the data is invalid, ``$this->addError()`` should be used to add an error message, an error code
(which should be the unix timestamp of the current time) and optional arguments which are inserted into
the error message.

The options of the validator can be accessed in the associative array ``$this->options``. The options
must be declared as shown above. The $supportedOptions array is indexed by option name and each value
is an array with the following numerically indexed elements:

# default value of the option
# description of the option (used for documentation rendering)
# type of the option (used for documentation rendering)
# required option flag (optional, defaults to false)

The default values are set in the constructor of the abstract validators provided with Flow. If the
required flag is set, missing options will cause an ``InvalidValidationOptionsException`` to be thrown
when the validator is instantiated.

In case you do further checks on the options and any of them is invalid, an
``InvalidValidationOptionsException`` should be thrown as well.

.. tip:: Because you extended AbstractValidator in the above example, ``null`` and empty string
         are automatically regarded as valid values; as it is the case for all other validators.
         If you do not want to accept empty values, you need to set the class property
         $acceptsEmptyValues to false.


.. _Effective Aggregate Design: https://vaughnvernon.co/?p=838
