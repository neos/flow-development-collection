=======
Routing
=======

.. sectionauthor:: Robert Lemke <robert@typo3.org>

Although the basic functions like creating or updating a post work well
already, the URIs still have a little blemish. The index of posts can only be
reached by the cumbersome address http://dev.tutorial.local/typo3.blog/post
and the URL for editing a post refers to the post's UUID instead of the
human-readable identifier.

TYPO3 Flow's routing mechanism allows for beautifying these URIs by simple but
powerful configuration options.

Post Index Route
================

Our first task is to simplify accessing the list of posts. For that you need to
edit a file called *Routes.yaml* in the global *Configuration/* directory
(located at the same level like the *Data* and *Packages* directories).
This file already contains a few routes which we ignore for the time being.

Please insert the following configuration *at the top of the file* (before the
TYPO3CR route) and make sure that you use spaces exactly like in the example
(remember, spaces have a meaning in YAML files and tabs are not allowed):

.. code-block:: yaml

	-
	  name: 'Post index'
	  uriPattern:    '(posts)'
	  defaults:
	    '@package':    'TYPO3.Blog'
	    '@controller': 'Post'
	    '@action':     'index'
	    '@format':     'html'

This configuration adds a new route to the list of routes (``-`` creates a new
list item). The route becomes active if a requests matches the pattern defined
by the ``uriPattern``. In this example empty URIs
(i.e. http://dev.tutorial.local/) and the URI http://dev.tutorial.local/posts
would match because the round brackets make the ``posts`` string optional.

If the URI matches, the route's default values for package, controller action
and format are set and the request dispatcher will choose the right
controller accordingly.

Try calling http://dev.tutorial.local/ and http://dev.tutorial.local/posts now –
you should in both cases see the list of posts produced by the
``PostController``'s ``indexAction``.

Composite Routes
================

As you can imagine, you rarely define only one route per package and storing
all routes in one file can easily become confusing. To keep the global
*Routes.yaml* clean you may define sub routes which include - if their own URI
pattern matches - further routes provided by your package.

The *Flow* sub route configuration for example includes further routes if
the URI path starts with the string '``TYPO3CR``'. Only the URI part contained
in the less-than and greater-than signs will be passed to the sub routes:

.. code-block:: yaml

	##
	# Flow subroutes
	#

	-
	  name: 'Flow'
	  uriPattern: '<FlowSubroutes>'
	  defaults:
	    '@format': 'html'
	  subRoutes:
	    FlowSubroutes:
	      package: 'TYPO3.Flow'

Let's define a similar configuration for the *Blog* package. Please replace
the YAML code you just inserted (the blog index route) by the following sub
route configuration:

.. code-block:: yaml

	##
	# Blog subroutes

	-
	  name: 'Blog'
	  uriPattern: '<BlogSubroutes>'
	  defaults:
	    '@package': 'TYPO3.Blog'
	    '@format':  'html'
	  subRoutes:
	    BlogSubroutes:
	      package: 'TYPO3.Blog'

For this to work you need to create a new *Routes.yaml* file in the
*Configuration* folder of your *Blog* package
(*Packages/Application/TYPO3.Blog/Configuration/Routes.yaml*) and paste the
route you already created:

*Configuration/Routes.yaml*:

.. code-block:: yaml

	#                                                                        #
	# Routes configuration for the Blog package                              #
	#                                                                        #

	-
	  name: 'Post index'
	  uriPattern:    '(posts)'
	  defaults:
	    '@package':    'TYPO3.Blog'
	    '@controller': 'Post'
	    '@action':     'index'
	    '@format':     'html'

.. note::
	As the defaults for ``@package`` and ``@format`` are already defined in the parent route,
	you can omit them in the sub route.

An Action Route
===============

The URI pointing to the ``newAction`` is still http://dev.tutorial.local/typo3.blog/post/new
so let's beautify the action URIs as well by inserting a new route before the
'``Blogs``' route:

*Configuration/Routes.yaml*:

.. code-block:: yaml

	-
	  name: 'Post actions 1'
	  uriPattern:    'posts/{@action}'
	  defaults:
	    '@controller': 'Post'

Reload the post index and check out the new URI of the ``createAction`` - it's
a bit shorter now:

.. image:: /Images/GettingStarted/PostActionRoute1URI.png

However, the edit link still looks it bit ugly:

.. code-block:: none

	http://dev.tutorial.local/post/edit?post%5B__identity%5D=229e2b23-b6f3-4422-8b7a-efb196dbc88b

For getting rid of this long identifier we need the help of a new route that can handle
the post object.

Object Route Parts
==================

Our goal is to produce an URI like:

.. code-block:: none

	http://dev.tutorial.local/post/2010/01/18/post-title/edit

and use this as our edit link. That's done by adding following route at the
*top of the file*:

*Configuration/Routes.yaml*:

.. code-block:: yaml

	-
	  name: 'Post actions 2'
	  uriPattern:     'posts/{post}/{@action}'
	  defaults:
	    '@controller':  'Post'
	  routeParts:
	    post:
	      objectType: 'TYPO3\Blog\Domain\Model\Post'
	      uriPattern: '{date:Y}/{date:m}/{date:d}/{title}'

The "``Post actions 2``" route now handles all actions where a post needs to
be specified (i.e. show, edit, update and delete).

Finally, now that you copied and pasted so much code, you should try out the
new routing setup ...

More on Routing
===============

The more an application grows, the more complex routing can become and
sometimes you'll wonder which route TYPO3 Flow eventually chose. One way to get
this information is looking at the log file which is by default
located in *Data/Logs/System_Development.log*:

.. image:: /Images/GettingStarted/RoutingLogTail.png

More information on routing can be found in the :doc:`The Definitive Guide <../PartIII/Routing>`.
