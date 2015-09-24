============================
JavaScript Coding Guidelines
============================

Here, you will find an explanation of the JavaScript Coding Guidelines we use.
Generally, we strive to follow the Flow Coding Guidelines as closely as
possible, with exceptions which make sense in the JavaScript context.

This guideline explains mostly how we want JavaScript code to be formatted;
and it does **not** deal with the Neos User
Interface structure. If you want to know more about the Neos User
Interface architecture, have a look into the "Neos User Interface
Development" book.


Naming Conventions
==================

- one class per file, with the same naming convention as Flow.
- This means all classes are built like this:
  ``<PackageKey>.<SubNamespace>.<ClassName>``, and this class is
  implemented in a JavaScript file located at
  ``<Package>/.../JavaScript/<SubNamespace>/<ClassName>.js``
- Right now, the base directory for JavaScript in Flow packages
  ``Resources/Public/JavaScript``, but this might still change.
- We suggest that the base directory for JavaScript files is *JavaScript*.
- Files have to be encoded in UTF-8 without byte order mark (BOM).
- Classes and namespaces are written in ``UpperCamelCase``, while properties and methods
  are written in ``lowerCamelCase``.
- The xtype of a class is always the fully qualified class name. Every class which can be
  instantiated needs to have an xtype declaration.
- Never create a class which has classes inside itself. Example: if the class
  ``TYPO3.Foo`` exists, it is prohibited to create a class ``TYPO3.Foo.Bar``.You can
  easily check this: If a directory with the same name as the JavaScript file exists, this
  is prohibited.

  Here follows an example::

  	TYPO3.Foo.Bar // implemented in .../Foo/Bar.js
  	TYPO3.Foo.Bar = ...

  	TYPO3.Foo // implemented in ...Foo.js
  	TYPO3.Foo = ..... **overriding the "Bar" class**

  So, if the class ``TYPO3.Foo.Bar`` is included **before** ``TYPO3.Foo``, then
  the second class definition completely overrides the ``Bar`` object. In order
  to prevent such issues, this constellation is forbidden.
- Every class, method and class property should have a doc comment.
- Private methods and properties should start with an underscore (``_``)
  and have a ``@private`` annotation.

Doc Comments
============

Generally, doc comments follow the following form::

	/**
	 *
	 */

See the sections below on which doc comments are available for the different
elements (classes, methods, ...).

We are using http://code.google.com/p/ext-doc/ for rendering an API
documentation from the code, that's why types inside ``@param``, ``@type`` and
``@cfg`` have to be written in braces like this::

	@param {String} theFirstParameter A Description of the first parameter
	@param {My.Class.Name} theSecondParameter A description of the second parameter

Generally, we do not use ``@api`` annotations, as private methods and attributes
are marked with ``@private`` and prefixed with an underscore. So, **everything
which is not marked as private belongs to the public API!**

We are not sure yet if we should use ``@author`` annotations at all. (TODO Decide!)

To make a reference to another method of a class, use the
``{@link #methodOne This is an example link to method one}`` syntax.

If you want to do multi-line doc comments, you need to format them with ``<br>``,
``<pre>`` and other HTML tags::

	/**
	 * Description of the class. Make it as long as needed,
	 * feel free to explain how to use it.
	 * This is a sample class <br/>
	 * The file encoding should be utf-8 <br/>
	 * UTF-8 Check: öäüß <br/>
	 * {@link #methodOne This is an example link to method one}
	 */

Code Style
----------

- use single quotes(') instead of double quotes(") for string quoting
- Multi-line strings (using ``\``) are forbidden. Instead, multi-line strings should be
  written like this::

	'Some String' +
	' which spans' +
	' multiple lines'

- There is no limitation on line length.
- JavaScript constants (true, false, null) must be written in lowercase, and not uppercase.
- Custom JavaScript constants should be avoided.
- Use a single ``var`` statement at the top of a method to declare all variables::

	function() {
		var myVariable1, myVariable2, someText;
		// now, use myVariable1, ....
	}

	Please do **not assign** values to the variables in the initialization, except empty
	default values::

	// DO:
	function() {
		var myVariable1, myVariable2;
		...
	}
	// DO:
	function() {
		var myVariable1 = {}, myVariable2 = [], myVariable3;
		...
	}
	// DON'T
	function() {
		var variable1 = 'Hello',
			variable2 = variable1 + ' World';
		...
	}

- We use **a single TAB** for indentation.

- Use inline comments sparingly, they are often a hint that a new method must be
  introduced.

  Inline Comments must be indented **one level deeper** than the current nesting level::

	function() {
		var foo;
			// Explain what we are doing here.
		foo = '123';
	}

- Whitespace around control structures like ``if``, ``else``, ... should be inserted like
  in the Flow CGLs::

	if (myExpression) {
		// if part
	} else {
		// Else Part
	}

- Arrays and Objects should **never** have a trailing comma after their last element

- Arrays and objects should be formatted in the following way::

	[
		{
			foo: 'bar'
		}, {
			x: y
		}
	]

- Method calls should be formatted the following way::

	// for simple parameters:
	new Ext.blah(options, scope, foo);
	object.myMethod(foo, bar, baz);

	// when the method takes a **single** parameter of type **object** as argument, and this object is specified directly in place:
	new Ext.Panel({
		a: 'b',
		c: 'd'
	});

	// when the method takes more parameters, and one is a configuration object which is specified in place:
	new Ext.blah(
		{
			foo: 'bar'
		},
		scope,
		options
	);<

TODO: are there JS Code Formatters / Indenters, maybe the Spket JS Code Formatter?

Using JSLint to validate your JavaScript
========================================

JSLint is a JavaScript program that looks for problems in JavaScript programs. It is a
code quality tool. When C was a young programming language, there were several common
programming errors that were not caught by the primitive compilers, so an accessory
program called ``lint`` was developed that would scan a source file, looking for problems.
``jslint`` is the same for JavaScript.

JavaScript code ca be validated on-line at http://www.jslint.com/. When validating the
JavaScript code, "The Good Parts" family options should be set. For that purpose, there is
a button "The Good Parts" to be clicked.

Instead of using it online, you can also use JSLint locally, which is now described. For
the sake of convenience, the small tutorial bellow demonstrates how to use JSlint with the
help of CLI wrapper to enable recursive validation among directories which streamlines the
validation process.

- Download Rhino from http://www.mozilla.org/rhino/download.html and put it for instance
  into ``/Users/john/WebTools/Rhino``
- Download ``JSLint.js`` (@see attachment "jslint.js", line 5667-5669 contains the
  configuration we would like to have, still to decide) (TODO)
- Download ``jslint.php`` (@see attachment "jslint.php" TODO), for example into
  ``/Users/fudriot/WebTools/JSLint``
- Open and edit path in ``jslint.php`` -> check variable ``$rhinoPath`` and
  ``$jslintPath``

- Add an alias to make it more convenient in the terminal::

  	alias jslint '/Users/fudriot/WebTools/JSLint/jslint.php'

Now, you can use JSLint locally::

	// scan one file or multi-files
	jslint file.js
	jslint file-1.js file-2.js

	// scan one directory or multi-directory
	jslint directory
	jslint directory-1 directory-2

	// scan current directory
	jslint .

It is also possible to adjust the validation rules JSLint uses. At the end of file
``jslint.js``, it is possible to customize the rules to be checked by JSlint by changing
options' value. By default, the options are taken over the book "JavaScript: The Good
Parts" which is written by the same author of JSlint.

Below are the options we use for Flow::

	bitwise: true, eqeqeq: true, immed: true,newcap: true, nomen: false,
	onevar: true, plusplus: false, regexp: true, rhino: true, undef: false,
	white: false, strict: true

In case some files needs to be evaluated with special rules, it is possible to add a
comment on the top of file which can override the default ones::

	/* jslint white: true, evil: true, laxbreak: true, onevar: true, undef: true,
	nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: true,
	newcap: true, immed: true */

More information about the meaning and the reasons of the rules can be found at
http://www.jslint.com/lint.html

Event Handling
==============

When registering an event handler, always use explicit functions instead of inline
functions to allow overriding of the event handler.

Additionally, this function needs to be prefixed with ``on`` to mark it as event handler
function. Below follows an example for good and bad code.

*Good Event Handler Code*::

	TYPO3.TYPO3.Application.on('theEventName', this._onCustomEvent, this);

*Bad Event Handler Code*::

	TYPO3.TYPO3.Application.on(
		'theEventName',
		function() {
			alert('Text');
		},
		this
	);

All events need to be explicitly documented inside the class where they are fired onto
with an ``@event`` annotation::

	TYPO3.TYPO3.Core.Application = Ext.apply(new Ext.util.Observable, {
		/**
		 * @event eventOne Event declaration
		 */

		/**
		 * @event eventTwo Event with parameters
		 * @param {String} param1 Parameter name
		 * @param {Object} param2 Parameter name
		 * <ul>
		 * <li><b>property1:</b> description of property1</li>
		 * <li><b>property2:</b> description of property2</li>
		 * </ul>
		 */
		...
	}

Additionally, make sure to document if the scope of the event handler is not set to
``this``, i.e. does not point to its class, as the user expects this.


ExtJS specific things
=====================

TODO

- explain initializeObject
- how to extend Ext components
- can be extended by using constructor() not initComponents() like it is for panels and so
  on

How to extend data stores
-------------------------

This is an example for how to extend an ExtJS data store::

	TYPO3.TYPO3.Content.DummyStore = Ext.extend(Ext.data.Store, {

		constructor: function(cfg) {
			cfg = cfg || {};
			var config = Ext.apply(
				{
					autoLoad: true
				},
				cfg
			);

			TYPO3.TYPO3.Content.DummyStore.superclass.constructor.call(
				this,
				config
			);
		}
	});
	Ext.reg('TYPO3.TYPO3.Content.DummyStore', TYPO3.TYPO3.Content.DummyStore);


Unit Testing
============

- It's highly recommended to write unit tests for javascript classes. Unit tests should be
  located in the following location: ``Package/Tests/JavaScript/...``
- The structure below this folder should reflect the structure below
  ``Package/Resources/Public/JavaScript/...`` if possible.
- The namespace for the Unit test classes is ``Package.Tests``.
- TODO: Add some more information about Unit Testing for JS
- TODO: Add note about the testrunner when it's added to the package
- TODO: http://developer.yahoo.com/yui/3/test/
