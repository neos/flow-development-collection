.. _ch-object-management:

================
Object Framework
================

.. sectionauthor:: Robert Lemke <robert@typo3.org>

The lifecycle of objects are managed centrally by the object framework. It offers
convenient support for Dependency Injection and provides some additional features such as
a caching mechanism for objects. Because all packages are built on this foundation it is
important to understand the general concept of objects in FLOW3.

.. tip::

	A very good start to understand the idea of Inversion of Control and Dependency
	Injection is reading `Martin Fowler's article`_ on the topic.

Creating Objects
================

In simple, self-contained applications, creating objects is as simple as using the ``new``
operator. However, as the program gets more complex, a developer is confronted with
solving dependencies to other objects, make classes configurable (maybe through a factory
method) and finally assure a certain scope for the object (such as ``Singleton`` or
``Prototype``). Howard Lewis Ship explained this circumstances nicely in `his blog`_
(quite some time ago):

	Once you start thinking in terms of large numbers of objects, and a whole lot of just
	in time object creation and configuration, the question of *how* to create a new object
	doesn't change (that's what ``new`` is for) ... but the questions *when* and *who*
	become 	difficult to tackle. Especially when the *when* is very dynamic, due to
	just-in-time instantiation, and the *who* is unknown, because there are so many places
	a particular object may be used.

The Object Manager is responsible for object building and dependency resolution (we'll
discover shortly why dependency injection makes such a difference to your application
design). In order to fulfill its task, it is important that all objects are instantiated
only through the object framework.

.. important::

	As a general rule of thumb for those not developing the FLOW3 core itself but your very
	own packages:

		**Use Dependency Injection whenever possible for retrieving singletons.**

Object Scopes
-------------

Objects live in a specific scope. The most commonly used are *prototype* and *singleton*:

+---------------------+------------------------------------------------------------------+
+ Scope               + Description                                                      +
+=====================+==================================================================+
+ singleton           + The object instance is unique during one request - each          +
+                     + injection by the Object Manager or explicit call of              +
+                     + ``get()`` returns the same instance. A request can be an         +
+                     + HTTP request or a run initiated from the command line.           +
+---------------------+------------------------------------------------------------------+
+ prototype (default) + The object instance is not unique - each injection or call of    +
+                     + the Object Factory's ``create`` method returns a fresh instance. +
+---------------------+------------------------------------------------------------------+
+ session             + The object instance is unique during the whole user session -    +
+                     + each injection or ``get()`` call returns the same instance.      +
+---------------------+------------------------------------------------------------------+


.. admonition:: Background: Objects in PHP

	In PHP, objects of the scope ``prototype`` are created with the ``new`` operator::

		$myFreshObject = new \MyCompany\MyPackage\MyClassName();

	In contrast to Prototype, the Singleton design pattern ensures that only one instance of a
	class exists at a time. In PHP the Singleton pattern is often implemented by providing a
	static function (usually called ``getInstance``), which returns a unique instance of the
	class::

		/**
		 * Implementation of the Singleton pattern
		 */
		class ASingletonClass {

			protected static $instance;

			static public function getInstance() {
				if (!is_object(self::$instance)) {
					self::$instance = $this;
				}
				return self::$instance;
			}
		}

	Although this way of implementing the singleton will possibly not conflict with the Object
	Manager, it is counterproductive to the integrity of the system and might raise problems
	with unit testing (sometimes Singleton is referred to as an *Anti Pattern*).
	The above examples are *not recommended* for the use within FLOW3 applications.

The scope of an object is determined from its configuration (see also :ref:`sect-configuring-objects`).
The recommended way to specify the scope is the ``@scope`` annotation::

	namespace MyCompany\MyPackage;

	/**
	 * A sample class
	 *
	 * @FLOW3\Scope("singleton")
	 */
	class SomeClass {
	}

Prototype is the default scope and is therefore assumed if no ``@scope`` annotation or
other configuration was found.

Creating Prototypes
-------------------

To create prototype objects, just use the ``new`` operator as you are used to::

	$myFreshObject = new \MyCompany\MyPackage\MyClassName();

When you do this, some magic is going on behind the scenes which still makes sure the object
you get back is managed by the object framework. Thus, all dependencies are properly injected
into the object, lifecycle callbacks are fired, and you can use Aspect-Oriented Programming, etc.

.. admonition:: Behind the scenes of the Object Framework

	In order to provide the functionality that you can just use ``new`` to create new
	prototype objects, a lot of advanced things happen behind the scenes.

	FLOW3 internally copies all classes to another file, and appends ``_Original`` to their
	class name. Then, it creates a new class under the original name where all the magic is
	happening.

	However, you as a user do not have to deal with that. The only thing you need to remember
	is using ``new`` for creating new Prototype objects. And you might know this from PHP ;-)



Retrieving Singletons
---------------------

The Object Manager maintains a registry of all instantiated singletons and ensures that
only one instance of each class exists. The preferred way to retrieve a singleton object
is dependency injection.

*Example: Retrieving the Object Manager through dependency injection* ::

	namespace MyCompany\MyPackage;

	/**
	 * A sample class
	 */
	class SampleClass {

		/**
		 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
		 */
		protected $objectManager;

		/**
		 * Constructor.
		 * The Object Manager will automatically be passed (injected) by the object
		 * framework on instantiating this class.
		 *
		 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager
		 */
		public function __construct(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
			$this->objectManager = $objectManager;
		}
	}


Once the ``SampleClass`` is being instantiated, the object framework will automagically
pass a reference to the Object Manager (which is an object of scope *singleton*) as an
argument to the constructor. This kind of dependency injection is called
*Constructor Injection* and will be explained - together with other kinds of injection -
in one of the later sections.

Although dependency injection is what you should strive for, it might happen that you need
to retrieve object instances directly. The ``ObjectManager`` provides methods for
retrieving object instances for these rare situations. First, you need an instance of the
``ObjectManager`` itself, again by taking advantage of constructor injection::

	public function __construct(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

.. note:: In the text, we commonly refer to the ``ObjectManager``. However, in your code, you should
   always use the ``ObjectManagerInterface`` if you need an instance of the Object Manager injected.

To explicitly retrieve an object instance use the ``get()`` method::

	$myObjectInstance = $objectManager->get('MyCompany\MyPackage\MyClassName');

It is *not* possible to pass arguments to the constructor of the object, as the object might
be already instanciated when you call ``get()``. If the object needs constructor arguments,
these must be :ref:`configured in Objects.yaml <sect-objects-yaml>`.

Lifecycle methods
-----------------

The lifecycle of an object goes through different stages. It boils down to the following
order:

#. Solve dependencies for constructor injection
#. Create an instance of the object class, injecting the constructor dependencies
#. Solve and inject dependencies for setter injection
#. Live a happy object-life and solve exciting tasks
#. Dispose the object instance

Your object might want to take some action after certain of the above steps. Whenever one
of the following methods exists in the object class, it will be invoked after the related
lifecycle step:

#. No action after this step
#. During instantiation the function ``__construct()`` is called (by PHP itself),
   dependencies are passed to the constructor arguments
#. After all dependencies have been injected (through constructor- or setter injection)
   the object's ``initializeObject()`` method is called. The name of this method is configurable
   inside *Objects.yaml*. ``initializeObject()`` is also called if no dependencies were injected.
#. During the life of an object no special lifecycle methods are called
#. Before destruction of the object, the function ``shutdownObject()`` is called. The name of
   this method is also configurable.
#. On disposal, the function ``__destruct()`` is called (by PHP itself)

We strongly recommend that you use the ``shutdownObject`` method instead of PHP's
``__destruct`` method for shutting down your object. If you used ``__destruct`` it might
happen that important parts of the framework are already unavailable. Here's a simple
example with all kinds of lifecycle methods:

*Example: Sample class with lifecycle methods* ::

	class Foo {

		protected $bar;
		protected $identifier = 'Untitled';

		public function __construct() {
			echo ('Constructing object ...');
		}

		public function injectBar(\MyCompany\MyPackage\BarInterface $bar) {
			$this->bar = $bar;
		}

		public function setIdentifier($identifier) {
			$this->identifier = $identifier;
		}

		public function initializeObject() {
			echo ('Initializing object ...');
		}

		public function shutdownObject() {
			echo ('Shutting down object ...')
		}

		public function __destruct() {
			echo ('Destructing object ...');
		}
	}

Output::

	Constructing object ...
	Initializing object ...
	Shutting down object ...
	Destructing object ...

Object Registration and API
===========================

Object Framework API
--------------------

The object framework provides a lean API for registering, configuring and retrieving
instances of objects. Some of the methods provided are exclusively used within FLOW3
package or in test cases and should possibly not be used elsewhere. By offering
Dependency Injection, the object framework helps you to avoid creating rigid
interdependencies between objects and allows for writing code which is hardly or even not
at all aware of the framework it is working in. Calls to the Object Manager should
therefore be the exception.

For a list of available methods please refer to the API documentation of the interface
``TYPO3\FLOW3\Object\ObjectManagerInterface``.

Object Names vs. Class Names
----------------------------

We first need to introduce some namings: A *class name* is the name of a PHP class, while an
*object name* is an identifier which is used inside the object framework to identify a certain
object.

By default, the *object name* is identical to the PHP class which contains the
object's code. A class called ``MyCompany\MyPackage\MyImplementation`` will be
automatically available as an object with the exact same name. Every part of the system
which asks for an object with a certain name will therefore - by default - get an instance
of the class of that name.

It is possible to replace the original implementation of an
object by another one. In that case the class name of the new implementation will
naturally differ from the object name which stays the same at all times. In these cases it
is important to be aware of the fine difference between an *object name* and a *class name*.

All PHP interfaces for which only one implementation class exist are also automatically
registered as *object names*, with the implementation class being returned when asked
for an instance of the interface.

Thus, you can also ask for interface implementations::

	$objectTypeInstance = $objectManager->get('MyCompany\MyPackage\MyInterface');

.. note:: If zero or more than one class implements the interface, the Object Manager will
   throw an exception.

The advantage of programming against interfaces is the increased
flexibility: By referring to interfaces rather than classes it is possible to write code
depending on other classes without the need to be specific about the implementation. Which
implementation will actually be used can be set at a later point in time by simple means
of configuration.

Object Dependencies
===================

The intention to base an application on a combination of packages and objects is to force
a clean separation of domains which are realized by dedicated objects. The less each
object knows about the internals of another object, the easier it is to modify or replace
one of them, which in turn makes the whole system flexible. In a perfect world, each of
the objects could be reused in a variety of contexts, for example independently from
certain packages and maybe even outside the FLOW3 framework.

Dependency Injection
--------------------

An important prerequisite for reusable code is already met by encouraging encapsulation
through object orientation. However, the objects are still aware of their environment as
they need to actively collaborate with other objects and the framework itself: An
authentication object will need a logger for logging intrusion attempts and the code of a
shop system hopefully consists of more than just one class. Whenever an object refers to
another directly, it adds more complexity and removes flexibility by opening new
interdependencies. It is very difficult or even impossible to reuse such hardwired classes
and testing them becomes a nightmare.

By introducing *Dependency Injection*, these interdependencies are minimized by inverting
the control over resolving the dependencies: Instead of asking for the instance of an
object actively, the depending object just gets one *injected* by the Object Manager.
This methodology is also referred to as the "`Hollywood Principle`_": *Don't call us,
we'll call you.* It helps in the development of code with loose coupling and high
cohesion --- or in short: It makes you a better programmer.

In the context of the previous example it means that the authentication object announces
that it needs a logger which implements a certain PHP interface (for example the
``TYPO3\FLOW3\Log\LoggerInterface``).
The object itself has no control over what kind of logger (file-logger,
sms-logger, ...) it finally gets and it doesn't have to care about it anyway as long as it
matches the expected API. As soon as the authentication object is instantiated, the object
manager will resolve these dependencies, prepare an instance of a logger and
inject it to the authentication object.

.. admonition:: Reading Tip

	`An article`_ by Jonathan Amsterdam discusses the difference between creating an object
	and requesting one (i.e. using ``new`` versus using dependency injection). It
	demonstrates why ``new`` should be considered as a low-level tool and outlines issues
	with polymorphism. He doesn't mention dependency injection though ...

Dependencies on other objects can be declared in the object's configuration (see :ref:`sect-configuring-objects`) or they can be solved automatically (so called autowiring).
Generally there are two modes of dependency injection supported by FLOW3:
*Constructor Injection* and *Setter Injection*.

Constructor Injection
---------------------

With constructor injection, the dependencies are passed as constructor arguments to the
depending object while it is instantiated. Here is an example of an object ``Foo`` which
depends on an object ``Bar``:

*Example: A simple example for Constructor Injection*::

	namespace MyCompany\MyPackage;

	class Foo {

		protected $bar;

		public function __construct(\MyCompany\MyPackage\BarInterface $bar) {
			$this->bar = $bar;
		}

		public function doSomething() {
			$this->bar->doSomethingElse();
		}
	}

So far there's nothing special about this class, the type hint just makes sure that an instance of
a class implementing the ``\MyCompany\MyPackage\BarInterface`` is passed to the constructor.
However, this is already a quite flexible approach because the type of ``$bar`` can be
determined from outside by just passing one or the another implementation to the
constructor.

Now the FLOW3 Object Manager does some magic: By a mechanism called *Autowiring* all
dependencies which were declared in a constructor will be injected automagically if the
constructor argument provides a type definition (i.e.
``\MyCompany\MyPackage\BarInterface`` in the above example). Autowiring is activated by
default (but can be switched off), therefore all you have to do is to write your
constructor method.

The object framework can also be configured manually to inject a certain object or object
type. You'll have to do that either if you want to switch off autowiring or want to
specify a configuration which differs from would be done automatically.

*Example: Objects.yaml file for Constructor Injection*:

.. code-block:: yaml

	MyCompany\MyPackage\Foo:
	  arguments:
	    1:
	      object:
	        MyCompany\MyPackage\Bar

The three lines above define that an object instance of ``\MyCompany\MyPackage\Bar`` must
be passed to the first argument of the constructor when an instance of the object
``MyCompany\MyPackage\Foo`` is created.

Setter Injection
----------------

With setter injection, the dependencies are passed by calling *setter methods* of the
depending object right after it has been instantiated. Here is an example of the ``Foo``
class which depends on a ``Bar`` object - this time with setter injection:

*Example: A simple example for Setter Injection*::

	namespace MyCompany\MyPackage;

	class Foo {

		protected $bar;

		public function setBar(\MyCompany\MyPackage\BarInterface $bar) {
			$this->bar = $bar;
		}

		public function doSomething() {
			$this->bar->doSomethingElse();
		}
	}

Analog to the constructor injection example, a ``BarInterface`` compatible object is
injected into the ``Foo`` object. In this case, however, the injection only takes
place after the class has been instantiated and a possible constructor method has been
called. The necessary configuration for the above example looks like this:

*Example: Objects.yaml file for Setter Injection*:

.. code-block:: yaml

	MyCompany\MyPackage\Foo:
	  properties:
	    bar:
	      object:
	        MyCompany\MyPackage\BarInterface

Unlike constructor injection, setter injection like in the above example does not offer
the autowiring feature. All dependencies have to be declared explicitly in the object
configuration.

To save you from writing large configuration files, FLOW3 supports a second
type of setter methods: By convention all methods whose name start with ``inject`` are
considered as setters for setter injection. For those methods no further configuration is
necessary, dependencies will be autowired (if autowiring is not disabled):

*Example: The preferred way of Setter Injection, using an inject method* ::

	namespace MyCompany\MyPackage;

	class Foo {

		protected $bar;

		public function injectBar(\MyCompany\MyPackage\BarInterface $bar) {
			$this->bar = $bar;
		}

		public function doSomething() {
			$this->bar->doSomethingElse();
		}
	}

Note the new method name ``injectBar`` - for the above example no further configuration is
required. Using ``inject*`` methods is the preferred way for setter
injection in FLOW3.

.. note::

	If both, a ``set*`` and a ``inject*`` method exist for the same property, the
	``inject*`` method has precedence.

Constructor- or Setter Injection?
---------------------------------

The natural question which arises at this point is *Should I use constructor- or setter
injection?* There is no answer across-the-board --- it mainly depends on the situation
and your preferences. The authors of the Java-based `Spring Framework`_ for example
prefer Setter Injection for its flexibility. The more puristic developers of
`PicoContainer`_ strongly plead for using Constructor Injection for its cleaner
approach. Reasons speaking in favor of constructor injections are:

* Constructor Injection makes a stronger dependency contract
* It enforces a determinate state of the depending object:
  using setter Injection, the injected object is only available after the constructor
  has been called

However, there might be situations in which constructor injection is not possible or
even cumbersome:

* If an object has many dependencies and maybe even many optional dependencies, setter
  injection is a better solution.
* Subclasses are not always in control over the arguments passed to the constructor or
  might even be incapable of overriding the original constructor.
  Then setter injection is your only chance to get dependencies injected.
* Setter injection can be helpful to avoid circular dependencies between objects.
* Setters provide more flexibility to unit tests than a fixed set of constructor
  arguments

Property Injection
------------------

Setter injection is the academic, clean way to set dependencies from outside. However,
writing these setters can become quite tiresome if all they do is setting the property.
For these cases FLOW3 provides support for *Property Injection*:

*Example: Example for Property Injection*::

	namespace MyCompany\MyPackage;

	class Foo {

		/**
		 * An instance of a BarInterface compatible object.
		 *
		 * @var \MyCompany\MyPackage\BarInterface
		 * @FLOW3\Inject
		 */
		protected $bar;

		public function doSomething() {
			$this->bar->doSomethingElse();
		}
	}

You could say that property injection is the same like setter injection --- just without the
setter. The ``Inject`` annotation tells the object framework that the property is
supposed to be injected and the ``@var`` annotation specifies the type. Note that property
injection even works (and should only be used) with protected properties. The *Objects.yaml*
configuration for property injection is identical to the setter injection configuration.

.. note::

	If a setter method exists for the same property, it has precedence.

Setting properties directly, without a setter method, surely is convenient - but is it
clean enough? In general it is a bad idea to allow direct access to mutable properties
because you never know if at some point you need to take some action while a property is
set. And if thousands of users (or only five) use your API, it's hard to change your
design decision in favor of a setter method.

However, we don't consider injection methods as part of the public API. As you've seen,
FLOW3 takes care of all the object dependencies and the only other code working with
injection methods directly are unit tests. Therefore we consider it safe to say that you
can still switch back from property injection to setter injection without problems if it
turns out that you really need it.

Settings Injection
------------------

No, this headline is not misspelled. FLOW3 offers some convenient feature which allows for
automagically injecting the settings of the current package without the need to configure
the injection. If a class contains a method called ``injectSettings`` and autowiring is
not disabled for that object, the Object Builder will retrieve the settings of the package
the object belongs to and pass it to the ``injectSettings`` method.

*Example: the magic injectSettings method* ::

	namespace MyCompany\MyPackage;

	class Foo {

		protected $settings = array();

		public function injectSettings(array $settings) {
			$this->settings = $settings;
		}

		public function doSomething() {
			var_dump($this->settings);
		}
	}

The ``doSomething`` method will output the settings of the ``MyPackage`` package.

Required Dependencies
---------------------

All dependencies defined in a constructor are, by its nature, required. If a dependency
can't be solved by autowiring or by configuration, FLOW3's object builder will throw an
exception.

Also *autowired setter-injected dependencies* are, by default, required. If the object
builder can't autowire an object for an injection method, it will throw an exception.

Dependency Resolution
---------------------

The dependencies between objects are only resolved during the instantiation process.
Whenever a new instance of an object class needs to be created, the object configuration
is checked for possible dependencies. If there is any, the required objects are built and
only if all dependencies could be resolved, the object class is finally instantiated and
the dependency injection takes place.

During the resolution of dependencies it might happen that circular dependencies occur. If
an object ``A`` requires an object ``B`` to be injected to its constructor and then again object ``B``
requires an object ``A`` likewise passed as a constructor argument, none of the two classes can
be instantiated due to the mutual dependency. Although it is technically possible (albeit
quite complex) to solve this type of reference, FLOW3's policy is not to allow circular
constructor dependencies at all. As a workaround you can use setter injection instead
for either one or both of the objects causing the trouble.

.. _sect-configuring-objects:

Configuring objects
===================

The behavior of objects significantly depends on their configuration. During the
initialization process all classes found in the various *Classes/* directories are
registered as objects and an initial configuration is prepared. In a second step, other
configuration sources are queried for additional configuration options. Definitions found
at these sources are added to the base configuration in the following order:

* If they exist, the *<PackageName>/Configuration/Objects.yaml* will be included.
* Additional configuration defined in the global *Configuration/Objects.yaml* directory is applied.
* Additional configuration defined in the global *Configuration/<ApplicationScope>/Objects.yaml* directory is applied.

Currently there are three important situations in which you want to configure objects:

* Override one object implementation with another
* Set the active implementation for an object type
* Explicitly define and configure dependencies to other objects

.. _sect-objects-yaml:

Configuring Objects Through Objects.yaml
----------------------------------------

If a file named *Objects.yaml* exists in the *Configuration* directory
of a package, it will be included during the configuration process. The YAML file should
stick to FLOW3's general rules for YAML-based configuration.

*Example: Sample Objects.yaml file*:

.. code-block:: yaml

	#                                                                        #
	# Object Configuration for the MyPackage package                         #
	#                                                                        #

	# @package MyPackage

	MyCompany\MyPackage\Foo:
	  arguments:
	    1:
	      object: MyCompany\MyPackage\Baz
	    2:
	      value: "some string"
	    3:
	      value: false
	  properties:
	    bar:
	      object: MyCompany\MyPackage\BarInterface
	    enableCache:
	      setting: MyPackage.Cache.enable

Configuring Objects Through Annotations
---------------------------------------

A very convenient way to configure certain aspects of objects are annotations. You write
down the configuration directly where it takes effect: in the class file. However, this
way of configuring objects is not really flexible, as it is hard coded. That's why only
those options can be set through annotations which are part of the class design and won't
change afterwards. Currently ``scope``, ``inject`` and ``autowiring`` are the only
supported annotations.

It's up to you defining the scope in the class directly or doing it in a *Objects.yaml*
configuration file – both have the same effect. We recommend using annotations in this
case, as the scope usually is a design decision which is very unlikely to be changed.

*Example: Sample scope annotation*::

	/**
	 * This is my great class.
	 *
	 * @FLOW3\Scope("singleton")
	 */
	class SomeClass {

	}

*Example: Sample autowiring annotation for a class*::

	/**
	 * This turns off autowiring for the whole class:
	 *
	 * @FLOW3\Autowiring(false)
	 */
	class SomeClass {

	}

*Example: Sample autowiring annotation for a method*::

	/**
	 * This turns off autowiring for a single method:
	 *
	 * @param \TYPO3\Foo\Bar $bar
	 * @FLOW3\Autowiring(false)
	 */
	public function injectMySpecialDependency(\TYPO3\Foo\Bar $bar) {

	}

Overriding Object Implementations
---------------------------------

One advantage of componentry is the ability to replace objects by others without any bad
impact on those parts depending on them. A prerequisite for replaceable objects is that
their classes implement a common `interface`_ which defines the public API of the original
object. Other objects which implement the same interface can then act as a true
replacement for the original object without the need to change code anywhere in the
system. If this requirement is met, the only necessary step to replace the original
implementation with a substitute is to alter the object configuration and set the class
name to the new implementation.

To illustrate this circumstance, consider the following classes:

*Example: A simple Greeter class*::

	namespace MyCompany\MyPackage;

	class Greeter {
		public function sayHelloTo($name) {
			echo('Hello ' . $name);
		}
	}

During initialization the above class will automatically be registered as the object
``MyCompany\MyPackage\Greeter`` and is available to other objects. In the class code of
another object you might find these lines:

*Example: Code using the object MyCompany\\MyPackage\\Greeter*::

	  // Use setter injection for fetching an instance
	  // of the \MyCompany\MyPackage\Greeter object:
	public function injectGreeter(\MyCompany\MyPackage\Greeter $greeter) {
		$this->greeter = $greeter;
	}

	public function someAction() {
		$this->greeter->sayHelloTo('Heike');
	}

Great, that looks all fine and dandy but what if we want to use the much better object
``\TYPO3\OtherPackage\GreeterWithCompliments``? Well, you just configure the object
``\MyCompany\MyPackage\Greeter`` to use a different class:

*Example: Objects.yaml file for object replacement*::

	  // Change the name of the class which
	  // represents the object MyCompany\MyPackage\Greeter
	MyCompany\MyPackage\Greeter:
	  className: TYPO3\OtherPackage\GreeterWithCompliments

Now all objects who ask for a traditional greeter will get the more polite version.
However, there comes a sour note with the above example: We can't be sure that the
``GreeterWithCompliments`` class really provides the necessary ``sayHello()`` method.
The solution is to let both implementations implement the same interface:

*Example: The Greeter object type*::

	namespace MyCompany\MyPackage;

	interface GreeterInterface {
		public function sayHelloTo($name);
	}

	class Greeter implements \MyCompany\MyPackage\GreeterInterface {
		public function sayHelloTo($name) {
			echo('Hello ' . $name);
		}
	}

	namespace TYPO3\OtherPackage;

	class GreeterWithCompliments implements \MyCompany\MyPackage\GreeterInterface{
		public function sayHelloTo($name) {
			echo('Hello ' . $name . '! You look so great!');
		}
	}

Instead of referring to the original implementation directly we can now refer to the
interface.

*Example: Code using the interface MyCompany\MyPackage\GreeterInterface*::

	public function injectGreeter(\MyCompany\MyPackage\GreeterInterface $greeter) {
		$this->greeter = $greeter;
	}

	public function someAction() {
		$greeter->sayHelloTo('Heike');
	}

Finally we have to set which implementation of the ``MyCompany\MyPackage\GreeterInterface``
should be active:

*Example: Objects.yaml file for object type definition*:

.. code-block:: yaml

	MyCompany\MyPackage\GreeterInterface:
	  className: 'TYPO3\OtherPackage\GreeterWithCompliments'

Configuring Injection
---------------------

The object framework allows for injection of straight values, objects (i.e. dependencies)
or settings either by passing them as constructor arguments during instantiation of the
object class or by calling a setter method which sets the wished property accordingly. The
necessary configuration for injecting objects is usually generated automatically by the
*autowiring* capabilities of the Object Builder. Injection of straight values or settings,
however, requires some explicit configuration.

Injection Values
~~~~~~~~~~~~~~~~

Regardless of what injection type is used (constructor or setter injection), there are
three kinds of value which can be injected:

* *value*: static value of a simple type. Can be string, integer, boolean or array and is
  passed on as is.
* *object*: object name which represents a dependency.
  Dependencies of the injected object are resolved and an instance of the object is
  passed along.
* *setting*: setting defined in one of the *Settings.yaml* files. A path separated by dots
  specifies which setting to inject.

Constructor Injection
~~~~~~~~~~~~~~~~~~~~~

Arguments for constructor injection are defined through the *arguments* option. Each
argument is identified by its position, counting starts with 1.

*Example: Sample class for Constructor Injection*::

	namespace MyCompany\MyPackage;

	class Foo {

		protected $bar;
		protected $identifier;
		protected $enableCache;

		public function __construct(\MyCompany\MyPackage\BarInterface $bar, $identifier,
			    $enableCache) {
			$this->bar = $bar;
			$this->identifier = $identifier;
			$this->enableCache = $enableCache;
		}

		public function doSomething() {
			$this->bar->doSomethingElse();
		}
	}

*Example: Sample configuration for Constructor Injection*:

.. code-block:: yaml

	MyCompany\MyPackage\Foo:
	  arguments:
	    1:
	      object: MyCompany\MyPackage\Bar
	    2:
	      value: "some string"
	    3:
	      setting: "MyPackage.Cache.enable"

.. note::

	It is usually not necessary to configure injection of objects explicitly. It is much
	more convenient to just declare the type of the constructor arguments (like
	``MyCompany\MyPackage\BarInterface`` in the above example) and let the autowiring
	feature configure and resolve the dependencies for you.

Setter Injection
~~~~~~~~~~~~~~~~

The following class and the related *Objects.yaml* file demonstrate the syntax for the
definition of setter injection:

*Example: Sample class for Setter Injection*::

	namespace MyCompany\MyPackage;

	class Foo {

		protected $bar;
		protected $identifier = 'Untitled';
		protected $enableCache = FALSE;

		public function injectBar(\MyCompany\MyPackage\BarInterface $bar) {
			$this->bar = $bar;
		}

		public function setIdentifier($identifier) {
			$this->identifier = $identifier;
		}

		public function setEnableCache($enableCache) {
			$this->enableCache = $enableCache;
		}

		public function doSomething() {
			$this->bar->doSomethingElse();
		}
	}

*Example: Sample configuration for Setter Injection*:

.. code-block:: yaml

	MyCompany\MyPackage\Foo:
	  properties:
	    bar:
	      object: MyCompany\MyPackage\Bar
	    identifier:
	      value: "some string"
	    enableCache:
	      setting: "MyPackage.Cache.enable"

As you can see, it is important that a setter method with the same name as the property,
preceded by ``inject`` or ``set`` exists. It doesn't matter though, if you choose ``inject`` or
``set``, except that ``inject`` has the advantage of being autowireable. As a rule of thumb we
recommend using ``inject`` for required dependencies and values and ``set`` for optional
properties.

.. TODO: is the last sentence still true? (Optional properties...)

Injection of Objects Specified in Settings
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In some cases it might be convenient to specify the name of the object to be injected in
the *settings* rather than in the objects configuration. This can be achieved by
specifying the settings path instead of the object name:

*Example: Injecting an object specified in the settings*:

.. code-block:: yaml

	MyCompany\MyPackage\Foo:
	  properties:
	    bar:
	      object: MyCompany.MyPackage.fooStuff.barImplementation

*Example: Settings.yaml of MyPackage*:

.. code-block:: yaml

	MyCompany:
	  MyPackage:
	    fooStuff:
	      barImplementation: MyCompany\MyPackage\Bars\ASpecialBar

Nested Object Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~

While autowiring and automatic dependency injection offers a great deal of convenience, it
is sometimes necessary to have a fine grained control over which objects are injected with
which third objects injected.

Consider a FLOW3 cache object, a ``VariableCache`` for example: the cache itself depends
on a cache backend which on its part requires a few settings passed to its constructor -
this readily prepared cache should now be injected into another object. Sounds complex?
With the objects configuration it is however possible to configure even that nested object
structure:

*Example: Nesting object configuration*:

.. code-block:: yaml

	MyCompany\MyPackage\Controller\StandardController:
	  properties:
	    cache:
	      object:
	        name: TYPO3\FLOW3\Cache\VariableCache
	        arguments:
	          1:
	            value: MyCache
	          2:
	            object:
	              name: TYPO3\FLOW3\Cache\Backend\File
	              properties:
	                cacheDirectory:
	                  value: /tmp/

Disabling Autowiring
~~~~~~~~~~~~~~~~~~~~

Injecting dependencies is a common task. Because FLOW3 can detect the type of dependencies
a constructor needs, it automatically configures the object to ensure that the necessary
objects are injected. This automation is called *autowiring* and is enabled by default for
every object. As long as autowiring is in effect, the Object Builder will try to autowire
all constructor arguments and all methods named after the pattern ``inject*``.

If, for some reason, autowiring is not wanted, it can be disabled by setting an option in
the object configuration:

*Example: Turning off autowiring support in Objects.yaml*:

.. code-block:: yaml

	MyCompany\MyPackage\MyObject:
	  autowiring: off

Autowiring can also be switched off through the ``@autowiring off`` annotation - either
in the DocComment block of a whole class or of a single method. For the latter the
annotation only has an effect when used in comment blocks of a constructor or of a method
whose name starts with ``inject``.

Custom Factories
----------------

.. warning:: |documentationNotReady|

Complex objects might require a custom factory which takes care of all important settings
and dependencies. As we have seen previously, a cache consists of a frontend, a backend
and configuration options for that backend. Instead of creating and configuring these
objects on your own, you can use the ``TYPO3\FLOW3\Cache\CacheFactory`` which provides a
convenient ``create`` method taking care of all the rest::

	$myCache = $cacheFactory->create('MyCache', 'TYPO3\FLOW3\Cache\VariableCache',
	    'TYPO3\FLOW3\Cache\Backend\File', array('cacheDirectory' => '/tmp'));

It is possible to specify for each object if it should be created by a custom factory
rather than the Object Builder. Consider the following configuration:

*Example: Sample configuration for a Custom Factory*:

.. code-block:: yaml

	TYPO3\FLOW3\Cache\CacheInterface:
	  factoryObjectName: TYPO3\FLOW3\Cache\CacheFactory
	  factoryMethodName: create

From now on the Cache Factory's ``create`` method will be called each time an object of
type ``CacheInterface`` needs to be instantiated. If arguments were passed to the
``ObjectManagerInterface::get()`` method or defined in the configuration, they will be
passed through to the custom factory method:

*Example: YAML configuration for a Custom Factory with default arguments*:

.. code-block:: yaml

	TYPO3\FLOW3\Cache\CacheInterface:
	  factoryObjectName: TYPO3\FLOW3\Cache\CacheFactory
	  arguments:
	    2:
	      value: TYPO3\FLOW3\Cache\VariableCache
	    3:
	      value: TYPO3\FLOW3\Cache\Backend\File
	    4:
	      value: { cacheDirectory: /tmp }

*Example: PHP code using the custom factory*::

	$myCache = $objectManager->create('MyCache');

``$objectManager`` is a reference to the ``TYPO3\FLOW3\Object\ObjectManager``. The
argument with the value ``MyCache`` is passed to the Cache Factory as the first parameter.
The required second and third argument and the optional fourth parameter are automatically
built from the values defined in the object configuration.

Name of Lifecycle Methods
-------------------------

The default name of a lifecycle methods is ``initializeObject`` and ``shutdownObject``.
If these methods exist, the initialization method will be called after the object has been
instantiated or recreated and all dependencies are injected and the shutdown method is
called before the Object Manager quits its service.

As the initialization method is being called after creating an object *and* after
recreating/reconstituting an object, there are cases where different code should be
executed. That is why the initialization method gets a parameter, which is one of the
``\TYPO3\FLOW3\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_*`` constants:

``\TYPO3\FLOW3\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED``
  If the object is newly created (i.e. the constructor has been called)
``\TYPO3\FLOW3\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_RECREATED``
  If the object has been recreated/reconstituted (i.e. the constructor has not been
  called)

The name of both methods is configurable per object for situations you don't have control
over the name of your initialization method (maybe, because you are integrating legacy
code):

*Example: Objects.yaml configuration of the initialization and shutdown method*

.. code-block:: yaml

	MyCompany\MyPackage\MyObject:
	  lifecycleInitializationMethod: myInitializeMethodname
	  lifecycleShutdownMethod: myShutdownMethodname

.. _Martin Fowler's article: http://martinfowler.com/articles/injection.html
.. _his blog:                http://tapestryjava.blogspot.com/2004/08/dependency-injection-mirror-of-garbage.html
.. _Hollywood Principle:     http://en.wikipedia.org/wiki/Hollywood_Principle
.. _An article:              http://www.ddj.com/dept/java/184405016
.. _Spring Framework:        http://www.springframework.org
.. _PicoContainer:           http://www.picocontainer.org
.. _interface:               http://www.php.net/manual/en/language.oop5.interfaces.php