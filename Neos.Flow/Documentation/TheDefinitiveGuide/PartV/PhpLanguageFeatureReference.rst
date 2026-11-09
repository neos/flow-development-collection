.. _ch-php-language-feature-reference:

============================
PHP Language Feature Support
============================

.. sectionauthor:: Robert Lemke <robert@neos.io>

Flow generates proxy classes for classes managed by the object framework in order to
provide Dependency Injection, Aspect-Oriented Programming, lifecycle handling and
object serialization support (see :ref:`ch-object-management`). A proxy class is a
generated subclass which takes over the original class name, while the original code
is preserved in a class with the ``_Original`` suffix. This works both for objects
retrieved through the Object Manager and for prototype objects created with the
``new`` operator.

Because proxy classes reproduce constructors, method signatures, properties and
attributes of the original class, every PHP language construct which can appear in a
class declaration is potentially relevant to the proxy building mechanism. Most
constructs are fully supported, some have known limitations, and support for the
most recent PHP features may not be implemented yet.

This reference documents the support status of PHP language constructs in proxied
classes. Note that classes which don't get a proxy class (for example because they
were excluded via ``Neos.Flow.object.excludeClassesFromConstructorAutowiring``,
annotated with ``Flow\Proxy(false)``, or simply don't need any injected code) are
plain PHP classes and not affected by any of the limitations documented here.

How to read this reference
==========================

The support status is given separately for the three main subsystems which rely on
generated proxy code:

* **DI** – Dependency Injection: constructor injection, property and setter
  injection, lifecycle methods
* **AOP** – Aspect-Oriented Programming: advices woven into methods of the class
* **Serialization** – the generated ``__sleep()`` / ``__wakeup()`` logic used for
  session-scoped objects and objects holding entity references

The status values are:

* **Yes** – supported, covered by the framework's test suite
* **Partial** – supported with documented limitations (see notes)
* **No** – not supported; using the construct in a proxied class may lead to
  compile errors, runtime errors or silently incorrect behavior
* **–** – not applicable to this subsystem
* **Untested** – expected to work, but not explicitly covered by functional tests;
  treat with care and please report issues

The *Since* column names the Flow version which introduced full (or the documented
partial) support. "≤ 8.3" means the construct has been supported at least since
Flow 8.3. "≤ 9.2" means the construct was verified by tests introduced with
Flow 9.2 – earlier versions may work as well, but were not explicitly verified.

Long-standing language features
===============================

.. list-table::
   :header-rows: 1
   :widths: 24 8 8 10 8 42

   * - Construct
     - DI
     - AOP
     - Serialization
     - Since
     - Notes
   * - ``final`` classes
     - Yes
     - Yes
     - Untested
     - ≤ 8.3
     - The ``final`` modifier is removed from the renamed original class and
       re-added to the proxy class, so the class remains final for userland code.
   * - ``final`` methods
     - Yes
     - Yes
     - –
     - ≤ 8.3
     - Supported for advised methods. Since Flow 9.0 this includes all modifier
       combinations, such as ``final public static`` methods with
       ``Flow\CompileStatic`` (`#3592
       <https://github.com/neos/flow-development-collection/issues/3592>`_).
   * - ``abstract`` classes and methods
     - Yes
     - Yes
     - –
     - ≤ 8.3
     - Proxy classes preserve the ``abstract`` modifier.
   * - Static methods
     - Yes
     - –
     - –
     - ≤ 8.3
     - Static methods are intentionally never advised by AOP.
       ``Flow\CompileStatic`` is supported.
   * - Private constructors
     - Yes
     - Partial
     - –
     - 9.0
     - The proxy makes the constructor technically public but enforces the
       original visibility at runtime. Note that instantiating singletons with
       ``new`` bypasses the Object Manager registry (`#3078
       <https://github.com/neos/flow-development-collection/issues/3078>`_).
   * - Parameters passed by reference (``&$foo``)
     - Yes
     - Yes
     - –
     - ≤ 8.3
     - Reference semantics are preserved, including signal/slot arguments.
   * - Return by reference (``function &foo()``)
     - Yes
     - No
     - –
     - 9.2
     - The generated proxy method preserves the reference when delegating to
       the original implementation (`#3590
       <https://github.com/neos/flow-development-collection/issues/3590>`_).
       Advised methods returning by reference are refused with an explanatory
       exception while compiling, because the reference cannot be preserved
       through an advice chain.
   * - Variadic parameters (``...$items``)
     - Yes
     - Yes
     - –
     - 9.0
     - Supported since Flow 9.0 (`#3589
       <https://github.com/neos/flow-development-collection/issues/3589>`_):
       the generated code unpacks variadic arguments in parent calls and when
       a join point invocation reaches the original method. Known remaining
       gap: variadic methods of session-scoped objects, when called through
       the lazy session object proxy.
   * - Generators (``yield``, ``yield from``)
     - Yes
     - Yes
     - –
     - ≤ 9.2
     - Verified including before and around advices: the generator object is
       passed through the advice chain and remains fully functional.
   * - Magic methods (``__call``, ``__get``, ``__set``, ``__invoke``, …)
     - Untested
     - Partial
     - Untested
     - ≤ 8.3
     - ``__invoke``, ``__toString``, ``__clone`` (including parent chains) and
       ``__construct`` are tested. A user-defined ``__call()`` method may
       intercept internal proxy initialization calls (`#3502
       <https://github.com/neos/flow-development-collection/issues/3502>`_).
   * - ``__sleep()`` / ``__wakeup()``
     - Yes
     - Yes
     - Yes
     - ≤ 8.3
     - A ``__sleep()`` method implemented in the original class replaces the
       generated one. Note that PHP 8.5 soft-deprecates these methods in favor
       of ``__serialize()`` / ``__unserialize()``.
   * - ``__serialize()`` / ``__unserialize()``
     - No
     - No
     - No
     - –
     - Not recognized by the proxy builder. Since PHP prefers these methods over
       ``__sleep()`` / ``__wakeup()``, implementing them in a proxied class
       silently disables Flow's generated serialization logic (dependency
       re-injection and entity reference handling, `#3593
       <https://github.com/neos/flow-development-collection/issues/3593>`_).
   * - Traits used by the original class
     - Yes
     - Yes
     - Yes
     - ≤ 8.3
     - Trait methods can be advised like regular methods. Conflict resolution
       (``insteadof`` / ``as``) is handled by PHP in the original class.
   * - Typed properties
     - Yes
     - Yes
     - Yes
     - ≤ 8.3
     - Including property injection into typed properties.
   * - Nullable types, ``void``, scalar type declarations
     - Yes
     - Yes
     - –
     - ≤ 8.3
     - No known limitations.
   * - ``declare(strict_types=1)``
     - Yes
     - Yes
     - Yes
     - ≤ 8.3
     - The declaration is preserved; generated proxy code runs in the same file
       and therefore under the same strictness.

PHP 8.0
=======

.. list-table::
   :header-rows: 1
   :widths: 24 8 8 10 8 42

   * - Construct
     - DI
     - AOP
     - Serialization
     - Since
     - Notes
   * - Constructor property promotion
     - Yes
     - Untested
     - Untested
     - ≤ 8.3
     - The proxy constructor reproduces the parameters without repeating the
       promotion and delegates to the original constructor.
   * - Union types
     - Yes
     - Yes
     - –
     - 9.0
     - Earlier versions supported union types in reflection but could generate
       invalid proxy code. Note: the order of types in a generated signature may
       differ from the original (semantically equivalent).
   * - Named arguments
     - Yes
     - Untested
     - –
     - 9.2
     - Proxy constructors preserve the original parameter list, so instantiation
       with named arguments works since Flow 9.2 (`#3076
       <https://github.com/neos/flow-development-collection/issues/3076>`_).
       The fix was not backported to older branches.
   * - Attributes on classes and methods
     - Partial
     - Partial
     - –
     - 9.0
     - Attributes are reproduced on the proxy class and its methods. Attribute
       arguments are reconstructed from reflection: constant expressions are
       replaced by their values, and complex object arguments may not be
       reproduced faithfully (`#3326
       <https://github.com/neos/flow-development-collection/issues/3326>`_,
       `#3511 <https://github.com/neos/flow-development-collection/issues/3511>`_).
       Nested enums and attribute objects are handled since Flow 8.4.
   * - Attributes on parameters and properties
     - No
     - No
     - –
     - –
     - Attributes on method parameters (including ``#[\SensitiveParameter]`` and
       attributes on promoted constructor properties) and on class properties
       are not reproduced in the proxy class (`#3594
       <https://github.com/neos/flow-development-collection/issues/3594>`_).
   * - ``static`` return type
     - Yes
     - Untested
     - –
     - ≤ 8.3
     - Late static binding resolves to the proxy class, which is the desired
       behavior.
   * - ``mixed``
     - Yes
     - Yes
     - –
     - 9.0
     - No known limitations.
   * - ``match``, nullsafe operator, trailing commas
     - Yes
     - Yes
     - –
     - ≤ 8.3
     - Method body syntax is preserved verbatim.

PHP 8.1
=======

.. list-table::
   :header-rows: 1
   :widths: 24 8 8 10 8 42

   * - Construct
     - DI
     - AOP
     - Serialization
     - Since
     - Notes
   * - Enums
     - –
     - –
     - –
     - ≤ 8.3
     - Enums never get a proxy class (they cannot be subclassed). Dependency
       injection and AOP are therefore not available in enums, which is by
       design.
   * - ``readonly`` properties
     - Partial
     - Untested
     - No
     - 9.0
     - Supported when initialized via constructor arguments (promoted or
       regular). Property
       injection into readonly properties is not possible, and deserialization
       cannot restore injected readonly properties (`#3282
       <https://github.com/neos/flow-development-collection/issues/3282>`_).
   * - ``never`` return type
     - Yes
     - Yes
     - –
     - 9.0
     - Supported including before, after-throwing and around advices.
   * - Pure intersection types (``A&B``)
     - Yes
     - Yes
     - –
     - 9.0
     - Verified for parameter, return and property types, including advised
       methods; the types remain enforced by PHP in proxied classes.
   * - First-class callable syntax (``foo(...)``)
     - Yes
     - Yes
     - –
     - ≤ 9.2
     - A callable created from a proxied method binds to the proxy
       implementation, which is usually the desired behavior – advices are
       also invoked when the method is called through such a callable.
   * - ``new`` in initializers (parameter defaults)
     - No
     - No
     - –
     - –
     - A parameter default like ``$foo = new Foo()`` in a proxied method
       currently aborts proxy compilation (`#2968
       <https://github.com/neos/flow-development-collection/issues/2968>`_).
   * - ``final`` class constants
     - Untested
     - –
     - –
     - –
     - Constants of the original class are inherited by the proxy; constants
       *generated into* the proxy class do not carry types, visibility or the
       ``final`` modifier.
   * - ``self`` in type declarations
     - No
     - No
     - –
     - –
     - Flow replaces ``self`` with ``static`` in the original class to make
       factory methods return the proxy type. This replacement is currently
       also applied to type declaration positions, where it produces invalid
       or incompatible code (`#3581
       <https://github.com/neos/flow-development-collection/issues/3581>`_).
       ``new self()`` and ``self::CONSTANT`` work as expected.

PHP 8.2
=======

.. list-table::
   :header-rows: 1
   :widths: 24 8 8 10 8 42

   * - Construct
     - DI
     - AOP
     - Serialization
     - Since
     - Notes
   * - ``readonly`` classes
     - Partial
     - No
     - Partial
     - 9.0
     - Constructor injection works; the proxy class preserves the ``readonly``
       modifier since Flow 9.2. Combining a readonly class with AOP advices is
       not supported, because the woven proxy code requires additional
       (non-readonly) properties (`#3591
       <https://github.com/neos/flow-development-collection/issues/3591>`_).
       Since Flow 9.2 this is refused with a clear exception while compiling the
       proxy classes instead of producing a proxy class which crashes with a
       fatal error when it is loaded.
       Serialization of readonly classes works as long as no advice applies;
       since the session scope introduces the lazy loading aspect, readonly
       classes cannot be session-scoped – that case is refused with a dedicated
       message as well. See also `#3282
       <https://github.com/neos/flow-development-collection/issues/3282>`_.
   * - DNF types (``(A&B)|C``)
     - Yes
     - Yes
     - –
     - 9.0
     - Reflected and rendered correctly, including parentheses and fully
       qualified type names; advised methods with DNF-typed parameters and
       return values are covered by tests.
   * - ``null``, ``false``, ``true`` as standalone types
     - Yes
     - Partial
     - –
     - 9.0
     - ``true`` and ``false`` are fully supported. A standalone ``null`` return
       type is currently not supported due to a limitation in the underlying
       code generator library.
   * - Constants in traits
     - Yes
     - –
     - –
     - ≤ 9.2
     - Constants declared in traits used by proxied classes work as expected.
   * - Dynamic properties / ``#[AllowDynamicProperties]``
     - Yes
     - Yes
     - Yes
     - ≤ 8.3
     - All properties added by generated code are properly declared.

PHP 8.3
=======

.. list-table::
   :header-rows: 1
   :widths: 24 8 8 10 8 42

   * - Construct
     - DI
     - AOP
     - Serialization
     - Since
     - Notes
   * - Typed class constants
     - Yes
     - –
     - –
     - ≤ 9.2
     - Typed constants of the original class work as expected and their types
       are visible through reflection on the proxied class. Constants
       *generated into* the proxy class are still rendered without their type
       declaration.
   * - ``#[\Override]``
     - Yes
     - Untested
     - –
     - ≤ 9.2
     - Verified for methods overriding interface and abstract class methods;
       the attribute remains visible through reflection on the proxied class.
   * - Dynamic class constant fetch (``self::{$name}``)
     - Yes
     - –
     - –
     - ≤ 9.2
     - Works in proxied classes, including the ``self::`` and ``static::``
       variants.
   * - Readonly property re-initialization in ``__clone()``
     - Untested
     - Untested
     - –
     - –
     - No dedicated tests exist.
   * - Explicitly nullable parameters (PHP 8.4 deprecation)
     - Yes
     - Yes
     - –
     - 8.3
     - Implicitly nullable parameters are reflected and rendered as explicitly
       nullable, so proxy classes do not trigger the PHP 8.4 deprecation.

PHP 8.4
=======

.. note::

   Flow 9.2 requires PHP 8.4 or later. Support for the major new language
   features of PHP 8.4 in proxied classes is still in development — the current
   status is documented below. Classes which don't need a proxy can use all of
   these features without restrictions.

.. list-table::
   :header-rows: 1
   :widths: 24 8 8 10 8 42

   * - Construct
     - DI
     - AOP
     - Serialization
     - Since
     - Notes
   * - Property hooks
     - No
     - No
     - No
     - –
     - Property hooks are not yet considered by the proxy builder, the
       reflection service or the generated serialization code. Using hooks
       (especially virtual properties) in proxied classes can lead to runtime
       errors.
   * - Asymmetric visibility (``private(set)``, ``protected(set)``)
     - No
     - No
     - No
     - –
     - Generated proxy code writes to properties from a subclass scope, which
       is not allowed for ``private(set)`` properties. Property injection,
       introduced properties and deserialization are affected.
   * - Lazy objects API
     - –
     - –
     - –
     - –
     - Not used by Flow yet; replacing Flow's ``DependencyProxy`` with native
       lazy objects is under discussion (`#3499
       <https://github.com/neos/flow-development-collection/issues/3499>`_).
   * - ``new`` without parentheses
     - Yes
     - Yes
     - –
     - ≤ 9.2
     - Verified including the combination ``new self()->someMethod()``, which
       interacts with the ``self``-to-``static`` rewriting and creates a fully
       initialized proxy instance.
   * - ``#[\Deprecated]``
     - Untested
     - Untested
     - –
     - –
     - Reproduced like any other attribute, with the same limitations regarding
       attribute arguments.

PHP 8.5
=======

.. list-table::
   :header-rows: 1
   :widths: 24 8 8 10 8 42

   * - Construct
     - DI
     - AOP
     - Serialization
     - Since
     - Notes
   * - ``clone with`` / ``clone(...)``
     - Untested
     - Untested
     - Untested
     - –
     - Expression syntax; expected to be unaffected by proxy building, but not
       covered by tests yet.
   * - Pipe operator (``|>``)
     - Untested
     - Untested
     - –
     - –
     - Method body syntax; expected to be unaffected by proxy building.
   * - Closures in constant expressions
     - No
     - No
     - –
     - –
     - Closures as parameter default values or attribute arguments cannot be
       reproduced in generated proxy code yet and abort proxy compilation.
   * - Final property promotion
     - Untested
     - –
     - –
     - –
     - Expected to work, since promotion is not repeated in the proxy
       constructor.
   * - Asymmetric visibility for static properties
     - No
     - No
     - No
     - –
     - Same limitations as asymmetric visibility on instance properties.
   * - ``#[\NoDiscard]``, attributes on constants
     - Untested
     - Untested
     - –
     - –
     - Attributes on constants are not reproduced on constants generated into
       proxy classes.

Known limitations at a glance
=============================

The following constructs currently don't work in proxied classes and should be
avoided until the referenced issues are resolved. As a workaround, most of them
can be used in classes which are excluded from proxy building (see
:ref:`sect-configuring-objects`).

* ``self`` as a parameter, return or property type declaration —
  `#3581 <https://github.com/neos/flow-development-collection/issues/3581>`_
* Advised methods returning by reference (refused at compile time with an
  explanatory exception) —
  `#3590 <https://github.com/neos/flow-development-collection/issues/3590>`_
* AOP advices on ``readonly`` classes (refused at compile time with an
  explanatory exception) —
  `#3591 <https://github.com/neos/flow-development-collection/issues/3591>`_
* ``new`` expressions as parameter default values —
  `#2968 <https://github.com/neos/flow-development-collection/issues/2968>`_
* ``__serialize()`` / ``__unserialize()`` in classes relying on Flow's generated
  serialization logic —
  `#3593 <https://github.com/neos/flow-development-collection/issues/3593>`_
* Attributes on method parameters and class properties (not reproduced in the
  proxy) —
  `#3594 <https://github.com/neos/flow-development-collection/issues/3594>`_
* Property hooks and asymmetric visibility (PHP 8.4)
* Closures in constant expressions (PHP 8.5)

If you run into a limitation which is not documented here, please report it in
the `flow-development-collection issue tracker
<https://github.com/neos/flow-development-collection/issues>`_.
