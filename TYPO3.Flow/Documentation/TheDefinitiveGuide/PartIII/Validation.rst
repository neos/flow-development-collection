Validation (to be written)
==========================

- Validation API: TYPO3\FLOW3\Validation
- explain ValidatorResolver::createValidator()
- build validator for model object: ValidatorResolver
- by default, validation happens inside the Controller before the action is called and before persisting an object

Partial Validation
==================

- if you only want to validate parts of your objects, f.e. want to store incomplete objects in the database, you can assign special *Validation Groups*.
- It is possible to specify a list of validation groups at each @FLOW3\Validate annotation, if none is specified the validation group "Default" is assigned to the validator
- When *invoking* validation, f.e. in the MVC layer or in persistence, you can also specify a list of to-be-executed validation groups. A validator is only executed if at least one validation group overlap.

Example::

	/**
	 * @FLOW3\Validate(name='Required')
	 */
	protected $prop1;

	/**
	 * @FLOW3\Validate(name='Required', validationGroups={'Default')})
	 */
	protected $prop2;

	/**
	 * @FLOW3\Validate(name='Required', validationGroups={'Persistence')})
	 */
	protected $prop3;

	/**
	 * @FLOW3\Validate(name='Required', validationGroups={'Controller')})
	 */
	protected $prop3;

- In Persistence, validation is invoked with validation groups Default and Persistence
- In Controller, validation is invoked with validation groups Default and Controller

Combined with the above example this means:

- validation for prop1 and prop2 are the same, as the "Default" validation group is added if none is specified
- validation for prop1 and prop2 are executed both on persisting and inside the controller
- validation for $prop3 is only executed in persistence, but not in controller
- validation for $prop4 is only executed in controller, but not in persistence