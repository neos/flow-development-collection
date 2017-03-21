.. _ch-routing:

=======
Routing
=======

.. sectionauthor:: Bastian Waidelich <bastian@neos.io>

As explained in the Model View Controller chapter, in Flow the dispatcher passes the
request to a controller which then calls the respective action. But how to tell, what
controller of what package is the right one for the current request? This is were the
Routing Framework comes into play.

The Router
==========

The request builder asks the router for the correct package, controller and action. For
this it passes the current request path to the routers ``match()`` method. The router then
iterates through all configured routes and invokes their ``matches()`` method. The first
route that matches, determines which action will be called with what parameters.

The same works for the opposite direction: If a link is generated the router calls the
``resolve()`` method of all routes until one route can return the correct URI for the
specified arguments.

.. note::

	If no matching route can be found, a ``NotFoundException`` is thrown which
	results in a 404 status code for the HTTP response and an error page being
	displayed. In Development context that error page contains some more details
	about the error that occurred.

Routes
======

A route describes the way from your browser to the controller - and back.

With the ``uriPattern`` you can define how a route is represented in the browser's address
bar. By setting ``defaults`` you can specify package, controller and action that should
apply when a request matches the route. Besides you can set arbitrary default values that
will be available in your controller. They are called ``defaults`` because you can overwrite
them by so called *dynamic route parts*.

But let's start with an easy example:

*Example: Simple route - Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Homepage'
	  uriPattern: ''
	  defaults:
	    '@package': 'My.Demo'
	    '@controller': 'Standard'
	    '@action': 'index'

.. note::

	``name`` is optional, but it's recommended to set a name for all routes to make debugging
	easier.

If you insert these lines at the beginning of the file ``Configurations/Routes.yaml``,
the ``indexAction`` of the ``StandardController`` in your *My.Demo* package will be called
when you open up the homepage of your Flow installation (``http://localhost/``).

URI patterns
============

The URI pattern defines the appearance of the URI. In a simple setup the pattern only
consists of *static route parts* and is equal to the actual URI (without protocol and
host).

In order to reduce the amount of routes that have to be created, you are allowed to insert
markers, so called *dynamic route parts*, that will be replaced by the Routing Framework.
You can even mark route parts *optional*.

But first things first.

Static route parts
------------------

A static route part is really simple - it will be mapped one-to-one to the resulting URI
without transformation.

Let's create a route that calls the ``listAction`` of the ``ProductController`` when browsing to
``http://localhost/my/demo``:

*Example: Simple route with static route parts Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Static demo route'
	  uriPattern: 'my/demo'
	  defaults:
	    '@package':    'My.Demo'
	    '@controller': 'Product'
	    '@action':     'list'

Dynamic route parts
-------------------

Dynamic route parts are enclosed in curly brackets and define parts of the URI that are
not fixed.

Let's add some dynamics to the previous example:

*Example: Simple route with static and dynamic route parts - Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Dynamic demo route'
	  uriPattern: 'my/demo/{@action}'
	  defaults:
	    '@package':    'My.Demo'
	    '@controller': 'Product'

Now ``http://localhost/my/demo/list`` calls the ``listAction`` just like in the previous
example.

With ``http://localhost/my/demo/new`` you'd invoke the ``newAction`` and so on.

.. note::

	It's not allowed to have successive dynamic route parts in the URI pattern because it
	wouldn't be possible to determine the end of the first dynamic route part then.

The ``@`` prefix should reveal that *action* has a special meaning here. Other predefined keys
are ``@package``, ``@subpackage``, ``@controller`` and ``@format``. But you can use dynamic route parts to
set any kind of arguments:

*Example: dynamic parameters - Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Dynamic demo route with parameter'
	  uriPattern: 'products/list/{sortOrder}.{@format}'
	  defaults:
	    '@package':    'My.Demo'
	    '@controller': 'Product'
	    '@action':     'list'

Browsing to ``http://localhost/products/list/descending.xml`` will then call the ``listAction`` in
your ``Product`` controller and the request argument ``sortOrder`` has the value of
``descending``.

By default, dynamic route parts match any simple type and convert it to a string that is available through
the corresponding request argument. Read on to learn how you can use objects in your routes.

Object Route Parts
------------------

If a route part refers to an object, that is *known to the Persistence Manager*, it will be converted to
its technical identifier (usually the UUID) automatically:

*Example: object parameters - Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Single product route'
	  uriPattern: 'products/{product}'
	  defaults:
	    '@package':    'My.Demo'
	    '@controller': 'Product'
	    '@action':     'show'

If you add this route *above the previously generated dynamic routes*, an URI pointing to the show action of
the ProductController will look like ``http://localhost/products/afb275ed-f4a3-49ab-9f2f-1adff12c674f``.

Probably you prefer more human readable URIs and you get them by specifying the ``object type``:

.. code-block:: yaml

	-
	  name: 'Single product route'
	  uriPattern: 'products/{product}'
	  defaults:
	    '@package':     'My.Demo'
	    '@controller':  'Product'
	    '@action':      'show'
	  routeParts:
	    product:
	      objectType: 'My\Demo\Domain\Model\Product'

This will use the *identity* properties of the specified model to generate the URI representation of the product.

.. note::

	If the model contains no identity, the technical identifier is used!

Try adding the ``@Flow\Identity`` annotation to the name property of the product model.
The resulting URI will be ``http://localhost/products/the-product-name``

.. note::

	The result will be transliterated, so that it does not contain invalid characters

Alternatively you can override the behavior by specifying an ``uriPattern`` for the object route part:

.. code-block:: yaml

	-
	  name: 'Single product route'
	  uriPattern: 'products/{product}'
	  defaults:
	    '@package':     'My.Demo'
	    '@controller':  'Product'
	    '@action':      'show'
	  routeParts:
	    product:
	      objectType: 'My\Demo\Domain\Model\Product'
	      uriPattern: '{category.title}/{name}'

This will add the title of the product category to the resulting URI:
``http://localhost/products/product-category/the-product-name``
The route part URI pattern can contain all properties of the object or it's relations.

.. note::

	For properties of type ``\DateTime`` you can define the date format by appending a PHP
	date format string separated by colon: ``{creationDate:m-Y}``. If no format is specified,
	the default of ``Y-m-d`` is used.

.. note::

	If an ``uriPattern`` is set or the ``objectType`` contains identity properties, mappings from an object to it's
	URI representation are stored in the ``ObjectPathMappingRepository`` in order to make sure that existing links
	work even after a property has changed!
	This mapping is not required if no uriPattern is set because in this case the mapping is ubiquitous.

Internally the above is handled by the so called ``IdentityRoutePart`` that gives you a lot of power and flexibility
when working with entities. If you have more specialized requirements or want to use routing for objects that are not
known to the Persistence Manager, you can create your custom *route part handlers*, as described below.

Route Part Handlers
===================

Route part handlers are classes that implement
``Neos\Flow\Mvc\Routing\DynamicRoutePartInterface``. But for most cases it will be
sufficient to extend ``Neos\Flow\Mvc\Routing\DynamicRoutePart`` and overwrite the
methods ``matchValue`` and ``resolveValue``.

Let's have a look at a (very simple) route part handler that allows you to match values against
configurable regular expressions:

*Example: RegexRoutePartHandler.php* ::

	class RegexRoutePartHandler extends \Neos\Flow\Mvc\Routing\DynamicRoutePart {

		/**
		 * Checks whether the current URI section matches the configured RegEx pattern.
		 *
		 * @param string $requestPath value to match, the string to be checked
		 * @return boolean TRUE if value could be matched successfully, otherwise FALSE.
		 */
		protected function matchValue($requestPath) {
			if (!preg_match($this->options['pattern'], $requestPath, $matches)) {
				return FALSE;
			}
			$this->value = array_shift($matches);
			return TRUE;
		}

		/**
		 * Checks whether the route part matches the configured RegEx pattern.
		 *
		 * @param string $value The route part (must be a string)
		 * @return boolean TRUE if value could be resolved successfully, otherwise FALSE.
		 */
		protected function resolveValue($value) {
			if (!is_string($value) || !preg_match($this->options['pattern'], $value, $matches)) {
				return FALSE;
			}
			$this->value = array_shift($matches);
			return TRUE;
		}

	}

The corresponding route might look like this:

*Example: Route with route part handlers Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'RegEx route - only matches index & list actions'
	  uriPattern: 'blogs/{blog}/{@action}'
	  defaults:
	    '@package':    'My.Blog'
	    '@controller': 'Blog'
	  routeParts:
	    '@action':
	      handler:   'My\Blog\RoutePartHandlers\RegexRoutePartHandler'
	      options:
	        pattern: '/index|list/'

The method ``matchValue()`` is called when translating from an URL to a request argument,
and the method ``resolveValue()`` needs to return an URL segment when being passed a value.

.. note::
 For performance reasons the routing is cached. During development of route part
 handlers it can be useful to disable the routing cache temporarily. You can do so
 by using the following configuration in your `Caches.yaml`:

 .. code-block:: yaml

  Flow_Mvc_Routing_Route:
    backend: Neos\Cache\Backend\NullBackend
  Flow_Mvc_Routing_Resolve:
    backend: Neos\Cache\Backend\NullBackend

.. warning:: Some examples are missing here, which should explain the API better.

.. TODO: fix above warning and then remove it.

Optional route parts
====================

By putting one or more route parts in round brackets you mark them optional. The following
route matches ``http://localhost/my/demo`` and ``http://localhost/my/demo/list.html``.

*Example: Route with optional route parts - Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Dynamic demo route'
	  uriPattern: 'my/demo(/{@action}.html)'
	  defaults:
	    '@package':    'My.Demo'
	    '@controller': 'Product'
	    '@action':     'list'

.. note::

	``http://localhost/my/demo/list`` won't match here, because either all optional parts
	have to match - or none.

.. note::

	You have to define default values for all optional dynamic route parts.

Case Sensitivity
================

By Default URIs are lower-cased. The following example with a
username of "Kasper" will result in ``http://localhost/users/kasper``

*Example: Route with default case handling*

.. code-block:: yaml

	-
	  uriPattern: 'Users/{username}'
	  defaults:
	    '@package':    'My.Demo'
	    '@controller': 'Product'
	    '@action':     'show'

You can change this behavior for routes and/or dynamic route parts:

*Example: Route with customised case handling*

.. code-block:: yaml

	-
	  uriPattern: 'Users/{username}'
	  defaults:
	    '@package':    'My.Demo'
	    '@controller': 'Product'
	    '@action':     'show'
	  toLowerCase: false
	  routeParts:
	    username:
	      toLowerCase: true

The option ``toLowerCase`` will change the default behavior for this route
and reset it for the username route part.
Given the same username of "Kasper" the resulting URI will now be
``http://localhost/Users/kasper`` (note the lower case "k" in "kasper").

.. note::

	The predefined route parts ``@package``, ``@subpackage``, ``@controller``, ``@action`` and
	``@format`` are an exception, they're always lower cased!

Matching of incoming URIs to static route parts is always done case sensitive. So "users/kasper" won't match.
For dynamic route parts the case is usually not defined. If you want to handle data coming in through dynamic
route parts case-sensitive, you need to handle that in your own code.

Exceeding Arguments
===================

By default arguments that are not part of the configured route values are *not
appended* to the resulting URI as *query string*.

If you need this behavior, you have to explicitly enable this by setting
``appendExceedingArguments``:

.. code-block:: yaml

  -
    uriPattern: 'foo/{dynamic}'
    defaults:
      '@package':    'Acme.Demo'
      '@controller': 'Standard'
      '@action':     'index'
    appendExceedingArguments: true

Now route values that are neither defined in the ``uriPattern`` nor specified in the ``defaults`` will be
appended to the resulting URI: ``http://localhost/foo/dynamicValue?someOtherArgument=argumentValue``

This setting is mostly useful for *fallback routes* and it is enabled for the default action route provided
with Flow, so that most links will work out of the box.

.. note::

	The setting ``appendExceedingArguments`` is only relevant for *creating* URIs (resolve).
	While matching an incoming request to a route, this has no effect. Nevertheless, all query parameters
	will be available in the resulting action request via ``$actionRequest::getArguments()``.

Request Methods
===============

Usually the Routing Framework does not care whether it handles a GET or POST request and just looks at the request path.
However in some cases it makes sense to restrict a route to certain HTTP methods. This is especially true for REST APIs
where you often need the same URI to invoke different actions depending on the HTTP method.

This can be achieved with a setting ``httpMethods``, which accepts an array of HTTP verbs:

.. code-block:: yaml

  -
    uriPattern: 'some/path'
    defaults:
      '@package':    'Acme.Demo'
      '@controller': 'Standard'
      '@action':     'action1'
    httpMethods: ['GET']
  -
    uriPattern: 'some/path'
    defaults:
      '@package':    'Acme.Demo'
      '@controller': 'Standard'
      '@action':     'action2'
    httpMethods: ['POST', 'PUT']

Given the above routes a *GET* request to ``http://localhost/some/path`` would invoke the ``action1Action()`` while
*POST* and *PUT* requests to the same URI would call ``action2Action()``.

.. note::

	The setting ``httpMethods`` is only relevant for *matching* URIs.
	While resolving route values to an URI, this setting has no effect.

Subroutes
=========

Flow supports what we call *SubRoutes* enabling you to provide custom routes with your package and
reference them in the global routing setup.

Imagine following routes in the ``Routes.yaml`` file inside your demo package:

*Example: Demo Subroutes - My.Demo/Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Product routes'
	  uriPattern: 'products/{@action}'
	  defaults:
	    '@controller': 'Product'

	-
	  name: 'Standard routes'
	  uriPattern: '{@action}'
	  defaults:
	    '@controller': 'Standard'

And in your global ``Routes.yaml``:

*Example: Referencing SubRoutes - Configuration/Routes.yaml*

.. code-block:: yaml

	-
	  name: 'Demo SubRoutes'
	  uriPattern: 'demo/<DemoSubroutes>(.{@format})'
	  defaults:
	    '@package': 'My.Demo'
	    '@format':  'html'
	  subRoutes:
	    'DemoSubroutes':
	      package: 'My.Demo'

As you can see, you can reference SubRoutes by putting parts of the URI pattern in angle
brackets (like ``<subRoutes>``). With the subRoutes setting you specify where to load the
SubRoutes from.

Instead of adjusting the global ``Routes.yaml`` you can also include sub routes via ``Settings.yaml`` - see `Subroutes from Settings`_.

Internally the ConfigurationManager merges together the main route with its SubRoutes, resulting
in the following routing configuration:

*Example: Merged routing configuration*

.. code-block:: yaml

	-
	  name: 'Demo SubRoutes :: Product routes'
	  uriPattern: 'demo/products/{@action}.{@format}'
	  defaults:
	    '@package':    'My.Demo'
	    '@format':     'html'
	    '@controller': 'Product'

	-
	  name: 'Demo SubRoutes :: Standard routes'
	  uriPattern: 'demo/{@action}.{@format}'
	  defaults:
	    '@package':    'My.Demo'
	    '@format':     'html'
	    '@controller': 'Standard'

You can even reference multiple SubRoutes from one route - that will create one route for
all possible combinations.

Nested Subroutes
----------------

By default a SubRoute is loaded from the ``Routes.yaml`` file of the referred package but it is
possible to load SubRoutes from a different file by specifying a ``suffix``:

.. code-block:: yaml

	-
	  name: 'Demo SubRoutes'
	  uriPattern: 'demo/<DemoSubroutes>'
	  subRoutes:
	    'DemoSubroutes':
	      package: 'My.Demo'
	      suffix:  'Foo'

This will load the SubRoutes from a file ``Routes.Foo.yaml`` in the ``My.Demo`` package.
With that feature you can include multiple Routes with your package (for example providing different URI styles).
Furthermore you can nest routes in order to minimize duplication in your configuration. You nest SubRoutes by including
different SubRoutes from within a SubRoute, using the same syntax as before.
Additionally you can specify a set of ``variables`` that will be replaced in ``name``, ``uriPattern`` and ``defaults``
of merged routes:

Imagine the following setup:


global Routes.yaml (``Configuration/Routes.yaml``):

.. code-block:: yaml

	-
	  name: 'My Package'
	  uriPattern: '<MyPackageSubroutes>'
	  subRoutes:
	    'MyPackageSubroutes':
	      package: 'My.Package'

default package Routes.yaml (``My.Package/Configuration/Routes.yaml``):

.. code-block:: yaml

	-
	  name: 'Product'
	  uriPattern: 'products/<EntitySubroutes>'
	  defaults:
	    '@package':    'My.Package'
	    '@controller': 'Product'
	  subRoutes:
	    'EntitySubroutes':
	      package: 'My.Package'
	      suffix:  'Entity'
	      variables:
	        'entityName': 'product'

	-
	  name: 'Category'
	  uriPattern: 'categories/<EntitySubroutes>'
	  defaults:
	    '@package':    'My.Package'
	    '@controller': 'Category'
	  subRoutes:
	    'EntitySubroutes':
	      package: 'My.Package'
	      suffix:  'Entity'
	      variables:
	        'entityName': 'category'

*And in ``My.Package/Configuration/Routes.Entity.yaml``*:

.. code-block:: yaml

	-
	  name: '<entityName> list view'
	  uriPattern: ''
	  defaults:
	    '@action': 'index'

	-
	  name: '<entityName> detail view'
	  uriPattern: '{<entityName>}'
	  defaults:
	    '@action': 'show'

	-
	  name: '<entityName> edit view'
	  uriPattern: '{<entityName>}/edit'
	  defaults:
	    '@action': 'edit'

This will result in a merged configuration like this:

.. code-block:: yaml

	-
	  name: 'My Package :: Product :: product list view'
	  uriPattern: 'products'
	  defaults:
	    '@package':    'My.Package'
	    '@controller': 'Product'
	    '@action':     'index'

	-
	  name: 'My Package :: Product :: product detail view'
	  uriPattern: 'products/{product}'
	  defaults:
	    '@package':    'My.Package'
	    '@controller': 'Product'
	    '@action':     'show'

	-
	  name: 'My Package :: Product :: product edit view'
	  uriPattern: 'products/{product}/edit'
	  defaults:
	    '@package':    'My.Package'
	    '@controller': 'Product'
	    '@action':     'edit'

	-
	  name: 'My Package :: Category :: category list view'
	  uriPattern: 'categories'
	  defaults:
	    '@package':    'My.Package'
	    '@controller': 'Category'
	    '@action':     'index'

	-
	  name: 'My Package :: Category :: category detail view'
	  uriPattern: 'categories/{category}'
	  defaults:
	    '@package':    'My.Package'
	    '@controller': 'Category'
	    '@action':     'show'

	-
	  name: 'My Package :: Category :: category edit view'
	  uriPattern: 'categories/{category}/edit'
	  defaults:
	    '@package':    'My.Package'
	    '@controller': 'Category'
	    '@action':     'edit'

Subroutes from Settings
-----------------------

Having to adjust the main ``Routes.yaml`` whenever you want to include SubRoutes can be cumbersome and error prone,
especially when working with 3rd party packages that come with their own routes.
Therefore Flow allows you to include SubRoutes via settings, too:

Settings.yaml (``Configuration/Settings.yaml``):

.. code-block:: yaml

	Neos:
	  Flow:
	    mvc:
	      routes:
	        'Some.Package': TRUE

This will include all routes from the main ``Routes.yaml`` file of the ``Some.Package`` (and all its nested SubRoutes
if it defines any).

You can also adjust the position of the included SubRoutes:

.. code-block:: yaml

	Neos:
	  Flow:
	    mvc:
	      routes:
	        'Some.Package':
	          position: 'start'

Internally Flow uses the ``PositionalArraySorter`` to resolve the order of SubRoutes loaded from Settings.
Following values are supported for the ``position`` option:

- start (<weight>)
- end (<weight>)
- before <key> (<weight>)
- after <key> (<weight>)
- <numerical-order>

``<weight>`` defines the priority in case of conflicting configurations. ``<key>`` refers to another package key allowing
you to set order depending on other SubRoutes.

.. note::

	SubRoutes that are loaded via Settings will always be appended **after** Routes loaded via ``Routes.yaml``
	Therefore you should consider getting rid of the main ``Routes.yaml`` and only use settings to include routes
	for greater flexibility.

It's not possible to adjust route defaults or the ``UriPattern`` when including SubRoutes via Settings, but there are
two more options you can use:

.. code-block:: yaml

	Neos:
	  Flow:
	    mvc:
	      routes:
	        'Some.Package':
	          suffix: 'Backend'
	          variables:
	            'variable1': 'some value'
	            'variable2': 'some other value'

With ``suffix`` you can specify a custom filename suffix for the SubRoute. The ``variables`` option allows you to
specify placeholders in the SubRoutes (see `Nested Subroutes`_).

.. tip::

	You can use the ``flow:routing:list`` command to list all routes which are currently active:

	.. code-block:: bash

		$ ./flow routing:list

		Currently registered routes:
		neos/login(/{@action}.{@format})         Neos :: Authentication
		neos/logout                              Neos :: Logout
		neos/setup(/{@action})                   Neos :: Setup
		neos                                     Neos :: Backend Overview
		neos/content/{@action}                   Neos :: Backend - Content Module
		{node}.html/{type}                       Neos :: Frontend content with format and type
		{node}.html                              Neos :: Frontend content with (HTML) format
		({node})                                 Neos :: Frontend content without a specified format
		                                         Neos :: Fallback rule – for when no site has been defined yet


Route Loading Order and the Flow Application Context
====================================================

- routes inside more specific contexts are loaded *first*
- and *after* that, global ones, so you can specify context-specific routes
