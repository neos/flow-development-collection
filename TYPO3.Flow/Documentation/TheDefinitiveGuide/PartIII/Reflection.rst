==========
Reflection
==========

.. sectionauthor:: Adrian Föder <adrian@foeder.de>

Reflection describes the practice to retrieve information about a program
itself and it's internals during runtime. It usually also allows to modify
behavior and properties.

PHP already provides reflection capabilities, using them it's possible to, for
example, change the accessibility of properties, e.g. from ``protected`` to
``public``, and access methods even though access to them is restricted.

Additionally it's possible to gain information about what arguments a method
expects, and whether these are required or optional.


Reflection in FLOW3
===================

FLOW3 provides a powerful extension to PHP's own basic reflection
functionality, not only adding more capabilities, but also speeding up
reflection massively. It makes heavy use of the DocComment annotations, this is
not least the reason why you should exercise care about a correct formatting
and respecting some rules when applying these.

.. note::

  A specific description about these DocComment formatting requirements is
  available in the `Coding Guidelines`.

The reflection of FLOW3 is handled via the *Reflection Service* which can be
injected as usual.

*Example: defining and accessing simple reflection information* ::

	/**
	 * This is the description of the class.
	 */
	class CustomizedGoodsOrder extends AbstractOrder {

		/**
		 * @var \Magrathea\Erp\Service\OrderNumberGenerator
		 */
		protected $orderNumberGenerator;

		/**
		 * @var \DateTime
		 */
		protected $timestamp;

		/**
		 * The customer who placed this order
		 * @var \Magrathea\Erp\Domain\Model\Customer
		 */
		protected $customer;

		/**
		 * The order number, for example ME-3020-BB
		 * @var string
		 */
		protected $orderNumber;

		/**
		 * @param \Magrathea\Erp\Domain\Model\Customer $customer;
		 */
		public function __construct(Customer $customer) {
			$this->timestamp = new \DateTime();
			$this->customer = $customer;
			$this->orderNumber = $this->orderNumberGenerator->createOrderNumber();
		}

		/**
		 * @return \Magrathea\Erp\Domain\Model\Customer
		 */
		public function getCustomer() {
			return $this->customer;
		}
	}


In an application, after wiring $reflectionService with
``\TYPO3\FLOW3\Reflection\ReflectionService`` via, for example, Dependency
Injection, there are a couple of options available. The following two examples
just should give a slight overview.

Listing all sub classes of the ``AbstractOrder`` class* ::

	$this->reflectionService->getAllSubClassNamesForClass('Magrathea\Erp\Domain\Model\AbstractOrder'));

returns ``array('Magrathea\Erp\Domain\Model\CustomizedGoodsOrder')``.

Fetching the plain annotation tags of the ``customer`` property from the
``CustomizedGoodsOrder`` class ::

	$this->reflectionService->getPropertyTagsValues('Magrathea\Erp\Domain\Model\CustomizedGoodsOrder', 'customer'));``

returns ``array('var' => '\Magrathea\Erp\Domain\Model\Customer')``

The API doc of the ReflectionService shows all available methods. Generally
said, whatever information is needed to gain information about classes, their
properties and methods and their sub or parent classes and interface
implementations, can be retrieved via the reflection service.


Custom annotation classes
=========================

A powerful feature is the ability to introduce customized annotation classes;
this achieves, for example, what across the framework often can be seen with
the ``@FLOW3\…`` or ``@ORM\…`` annotations.


Create an annotation class
--------------------------

An annotation class is best created in a direct subdirectory of your
``Classes`` one and carries the name ``Annotations``. The class itself receives
the name exactly like the annotation should be.

*Example: a ``Reportable`` annotation for use as class and property annotation*::

	<?php
	namespace Magrathea\Erp\Annotations;

	/**
	 * Marks the class or property as reportable, It will then be doing
	 * foo and bar, but not quux.
	 *
	 * @Annotation
	 * @Target({"CLASS", "PROPERTY"})
	 */
	final class Reportable {

		/**
		 * The name of the report. (Can be given as anonymous argument.)
		 * @var string
		 */
		public $reportName;

		/**
		 * @param array $values
		 */
		public function __construct(array $values) {
			if (!isset($values['value']) && !isset($values['reportName'])) {
				throw new \InvalidArgumentException('A Reporting annotation must specify a report name.', 1234567890);
			}
			$this->reportName = isset($values['reportName']) ? $values['reportName'] : $values['value'];
		}
	}
	?>

This defines a ``Reportable`` annotation, with one argument, ``reportName``,
which is required in this case. It can be given with it's name or *anonymous*,
as the sole (and/or first) argument to the value. The annotation can only be
used on classes or properties, using it on a method will throw an exception.
This is checked by the annotation parser, based on the ``Target`` annotation.
The documentation of the class and it's properties can be used to generate
annotation reference documentation, so provide helpful descriptions and names.

.. note::

  An annotation can also be simpler, using only public properties. The use of
  a constructor allows for some checks and gives the possibility to have
  *anonymous* arguments, if needed.

This annotation now can be set to arbitrary classes or properties, also across
packages. The namespace is introduced using the ``use`` statement and to
shorten the annotation; in the class this annotation can be set to the class
itself and to properties::

	use Magrathea\Erp\Annotations as ERP;

	/**
	 * This is the description of the class.
	 * @ERP\Reportable(reportName="OrderReport")
	 */
	class CustomizedGoodsOrder extends AbstractOrder {

		/**
		 * @ERP\Reportable
		 * @var \Magrathea\Erp\Service\OrderNumberGenerator
		 */
		protected $orderNumberGenerator;


Accessing annotation classes
----------------------------

With the reflection service, just an instance of your created annotation class
is returned, populated with the appropriate information of the annotation
itself! So complying with the walkthrough, the following approach is possible::

	$classAnnotation = $this->reflectionService->getClassAnnotation(
		'Magrathea\Erp\Domain\Model\CustomizedGoodsOrder',
		'Magrathea\Erp\Annotations\Reportable')
	);
	$classAnnotation instanceof \Magrathea\Erp\Annotations\Reportable;
	$classAnnotation->reportName === 'OrderReport';

	$propertyAnnotation = $this->reflectionService->getPropertyAnnotation(
		'Magrathea\Erp\Domain\Model\CustomizedGoodsOrder',
		'orderNumberGenerator',
		'Magrathea\Erp\Annotations\Reportable'
	);
	$propertyAnnotation instanceof \Magrathea\Erp\Annotations\Reportable;
	$propertyAnnotation->reportName === NULL;


It's even possible to collect all annotation classes of a particular class, done via
``reflectionService->getClassAnnotations('Magrathea\Erp\Domain\Model\CustomizedGoodsOrder');``
which returns an array of annotations, in this case  ``TYPO3\FLOW3\Annotations\Entity``
and our ``Magrathea\Erp\Annotations\Reportable``.


.. _Coding Guidelines:                   http://flow3.typo3.org/documentation/codingguidelines.html