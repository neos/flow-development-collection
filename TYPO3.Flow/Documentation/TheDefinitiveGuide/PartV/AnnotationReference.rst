FLOW3 Annotation Reference
==========================

This reference was automatically generated from code on 2012-07-05


After
-----

Declares a method as an after advice to be triggered after any
pointcut matching the given expression.

:Applicable to: Method




Arguments
*********

* ``pointcutExpression`` (string): The pointcut expression. (Can be given as anonymous argument.)




AfterReturning
--------------

Declares a method as an after returning advice to be triggered
after any pointcut matching the given expression returns.

:Applicable to: Method




Arguments
*********

* ``pointcutExpression`` (string): The pointcut expression. (Can be given as anonymous argument.)




AfterThrowing
-------------

Declares a method as an after throwing advice to be triggered
after any pointcut matching the given expression throws an exception.

:Applicable to: Method




Arguments
*********

* ``pointcutExpression`` (string): The pointcut expression. (Can be given as anonymous argument.)




Around
------

Declares a method as an around advice to be triggered around any
pointcut matching the given expression.

:Applicable to: Method




Arguments
*********

* ``pointcutExpression`` (string): The pointcut expression. (Can be given as anonymous argument.)




Aspect
------

Marks a class as an aspect.

The class will be then by the AOP framework of FLOW3 and inspected for
pointcut expressions and advice.

:Applicable to: Class





Autowiring
----------

Used to disable autowiring for Dependency Injection on the
whole class or on the annotated property only.

:Applicable to: Method, Class




Arguments
*********

* ``enabled`` (boolean): Whether autowiring is enabled. (Can be given as anonymous argument.)




Before
------

Declares a method as an before advice to be triggered before any
pointcut matching the given expression.

:Applicable to: Method




Arguments
*********

* ``pointcutExpression`` (string): The pointcut expression. (Can be given as anonymous argument.)




Entity
------

Marks an object as an entity.

Behaves like \Doctrine\ORM\Mapping\Entity so it is interchangeable
with that.

:Applicable to: Class




Arguments
*********

* ``repositoryClass`` (string): Name of the repository class to use for managing the entity.

* ``readOnly`` (boolean): Whether the entity should be read-only.




FlushesCaches
-------------

Marks a CLI command as a cache-flushing command.

Usually used for framework purposes only.

:Applicable to: Method





Identity
--------

Marks a property as being (part of) the identity of an object.

If multiple properties are annotated as Identity, a compound
identity is created.

For Doctrine a unique key over all involved properties will be
created - thus the limitations of that need to be observed.

:Applicable to: Property





IgnoreValidation
----------------

Used to ignore validation on a specific method argument.

:Applicable to: Method




Arguments
*********

* ``argumentName`` (string): Name of the argument to skip validation for. (Can be given as anonymous argument.)




Inject
------

Used to enable property injection.

FLOW3 will build Dependency Injection code for the property and try
to inject a value as specified by the var annotation.

:Applicable to: Property





Internal
--------

Used to mark a command as internal - it will not be shown in
CLI help output.

Usually used for framework purposes only.

:Applicable to: Method





Introduce
---------

Introduces the given interface or property into any target class matching
the given pointcut expression.

:Applicable to: Class, Property




Arguments
*********

* ``pointcutExpression`` (string): The pointcut expression. (Can be given as anonymous argument.)

* ``interfaceName`` (string): The interface name to introduce.




Lazy
----

Marks a property or class as lazy-loaded.

This is only relevant for anything based on the generic persistence
layer of FLOW3. For Doctrine based persistence this is ignored.

:Applicable to: Class, Property





Pointcut
--------

Declares a named pointcut. The annotated method does not become an advice
but can be used as a named pointcut instead of the given expression.

:Applicable to: Method




Arguments
*********

* ``expression`` (string): The pointcut expression. (Can be given as anonymous argument.)




Proxy
-----

Used to disable proxy building for an object.

If disabled, neither Dependency Injection nor AOP can be used
on the object.

:Applicable to: Class




Arguments
*********

* ``enabled`` (boolean): Whether proxy building for the target is disabled. (Can be given as anonymous argument.)




Scope
-----

Used to set the scope of an object.

:Applicable to: Class




Arguments
*********

* ``value`` (string): The scope of an object: prototype, singleton, session. (Usually given as anonymous argument.)




Session
-------

Used to control the behavior of session handling when the annotated
method is called.

:Applicable to: Method




Arguments
*********

* ``autoStart`` (boolean): Whether the annotated method triggers the start of a session.




Signal
------

Marks a method as a signal for the signal/slot implementation
of FLOW3. The method will be augmented as needed (using AOP)
to be a usable signal.

:Applicable to: Method





SkipCsrfProtection
------------------

Action methods marked with this annotation will not be secured
against CSRF.

Since CSRF is a risk for write operations, this is useful for read-only
actions. The overhead for CRSF token generation and validation can be
skipped in those cases.

:Applicable to: Method





Transient
---------

Marks a property as transient - it will never be considered by the
persistence layer for storage and retrieval.

Useful for calculated values and any other properties only needed
during runtime.

:Applicable to: Property





Validate
--------

Controls how a property or method argument will be validated by FLOW3.

:Applicable to: Method, Property




Arguments
*********

* ``type`` (string): The validator type, either a FQCN or a FLOW3 validator class name.

* ``options`` (array): Options for the validator, validator-specific.

* ``argumentName`` (string): The name of the argument this annotation is attached to, if used on a method. (Can be given as anonymous argument.)

* ``validationGroups`` (array): The validation groups for which this validator should be executed.




ValueObject
-----------

Marks the annotate class as a value object.

Regarding Doctrine the object is treated like an entity, but FLOW3
applies some optimizations internally, e.g. to store only one instance
of a value object.

:Applicable to: Class




