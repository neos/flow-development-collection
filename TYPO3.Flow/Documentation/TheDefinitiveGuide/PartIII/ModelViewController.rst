Model View Controller
=====================

FLOW3 promotes the use of the `Model View Controller <http://en.wikipedia.org/wiki/Model–view–controller>`_
pattern which clearly separates the information, representation and mediation into
separated building blocks. Although the design pattern and its naïve implementation
are relatively simple, a capable MVC framework also takes care of more complex tasks
such as input sanitizing, validation, form and upload handling and much more.

This chapter puts FLOW3's MVC framework into context with the HTTP request / response
mechanism, explains how to develop controllers and describes various features of
the framework.

HTTP
----

All action starts with an HTTP request sent from a client. The request contains
information about the resource to retrieve or process, the action to take and various
various parameters and headers. FLOW3 converts the raw HTTP request into an HTTP
Request object and, by invoking the :doc:`Routing` mechanism, determines which
controller is responsible for processing the request and creating a matching
response. A dispatcher then passes an internal to the controller and gets a response
in return which can be sent to back to the client.

If you haven't done already, we recommend that you read the chapter about FLOW3's
:doc:`Http`. It contains more detailed information about the application flow and
the specific parts of the HTTP API.

Action Request
--------------

A typical application contains controllers providing one or more *actions*. While
HTTP requests and responses are fine for communication between clients and servers,
FLOW3 uses a different kind of request internally to communicate with a controller,
called ``Action Request``. The default HTTP request handler asks the router to
extract some information from the HTTP request and build an Action Request.

The Action Request contains the all the necessary details for calling the controller
action which was requested by the client:

* the *package key* and optionally sub namespace of the package containing the
  controller supposed to handle the request
* the *controller name*
* the *action name*
* any *arguments* which are passed to the action
* the *format* of the expected response

With this information in place, the request handler can ask the ``Dispatcher`` to
pass control to the specified controller.

Dispatcher
----------

The Dispatcher has the function to invoke a controller specified in the given
request and make sure that the request was processed correctly. The Dispatcher class
provides one important method::

	public function dispatch(RequestInterface $request, ResponseInterface $response) {

On calling this method, the Dispatcher resolves the controller class name of the
controller mentioned in the request object and calls its ``processRequest()``
method. A fresh ``Response`` object is also passed to the controller which is
expected to deliver its response data by calling the respective setter methods on
that object.

Each request carries a ``dispatched`` flag which is set or unset by the controller.
The Action Controller for example sets this flag by default and only unsets it if
an action initiated a forward to another action or controller. If the flag is not
set, the Dispatcher assumes that the request object has been updated with a new
controller, action or arguments and that it should try again to dispatch the request.
If dispatching the request did not succeed after several trials, the Dispatcher
will throw an exception.

Sub Requests
------------

An ``Http\Request`` object always reflects the original HTTP request sent by the
client. It is not possible to create an *HTTP* sub request because requests which
are passed along within the application must be instances of ``Mvc\ActionRequest``.
Creating an Action Request as a sub request of the original HTTP Request is simple,
although you rarely need to do that::

	$actionRequest = $httpRequest->createActionRequest();

An Action Request always holds a reference to a *parent request*. In most cases
the hierarchy is shallow and the Action Request is just a direct sub request of
the HTTP Request. In the context of a controller, it is easy to get a hold of the
parent request::

	public function fooAction() {
		$parentRequest = $this->request->getParentRequest();
		$httpRequest = $this->request->getHttpRequest();
		// in case of a shallow hierarchy, $parentRequest == $httpRequest
	}

In a more complex scenario, an Action Request can be a sub request of another
Action Request. This is the case in most implementations of plugins, widgets or
other inline elements of a rendered page because each of them is a part of the
whole and can be arbitrarily nested. Each element (plugin, widget …) needs its own
Action Request instance in order to keep track of invocation details like arguments
and other context information.

A sub request can be created manually by passing the parent request to the
constructor of the new Action Request::

	$subRequest = new ActionRequest($parentRequest);

The top level Action Request (just below the HTTP Request) is referred to as the
*Main Request*::

	public function fooAction() {
		$parentRequest = $this->request->getParentRequest();
		$httpRequest = $this->request->getHttpRequest();
		$mainRequest = $this->request->getMainRequest();

		if ($this->request === $mainRequest) {
			$message = 'This is the main request';
		}

			// same like above:
		if ($this->request->isMainRequest()) {
			$message = 'This is the main request';
		}
	}

Manual creation of sub requests is rarely necessary. In most cases the framework
will take care of creating and managing sub requests if plugins or widgets are in
the game.

Controllers
-----------

A controller is responsible for preparing a model and collecting the necessary data
which should be returned as a response. It also controls the application flow and
decided if certain operations should be executed and how the application should
proceed, for example after the user has submitted a form.

A controller should only sparingly contain logic which goes beyond these tasks.
Operations which belong to the domain of the application should be rather be
implemented by *domain services*. This allows for a clear separation of application
flow and business logic and enables other parts of the application (for example
web services) to execute these operations through a well-defined API.

A controller suitable for being used in FLOW3 needs to implement the
``Mvc\Controller\ControllerInterface``. At the bare minimum it must provide a
``processRequest()`` method which accepts a request and response.

If needed, custom controllers can be implemented in a convenient way by extending
the ``Mvc\Controller\AbstractController`` class. The most common case though is to
use the *Action Controller* provided by the framework.

Action Controller
-----------------

Most web applications will interact with the client through execution of specific
*actions* provided by an Action Controller. FLOW3 provides a base class which
contains all the logic to map and validate arguments found in the raw request to
method arguments of an action. It also provides various convenience methods which
are typically needed in Action Controller implementations.

A Simple Action
~~~~~~~~~~~~~~~

The most simple way to implement an action is to extend the ActionController class,
declare an action method and return a plain string as the response::

	namespace Acme\Demo\Controller;
	use TYPO3\FLOW3\Mvc\Controller\ActionController;

	class HelloWorldController extends ActionController {

		/**
		 * The default action of this controller.
		 *
		 * @return string
		 */
		public function indexAction() {
			return 'Hello world.';
		}

	}

Note that the controller must reside in the ``Controller`` sub namespace of your
package in order to be detected by the default routing configuration. In the example
above, ``Acme\Demo`` corresponds with the package key ``Acme.Demo``.

By convention, ``indexAction`` is the action being called if no specific action was
requested. An action method name must be camelCased and always end with the suffix
"Action". In the Action Request and other parts of the routing system, it is
referred to simply by its *action name*, in this case ``index``.

If an action returns a string or an object which can be cast to a string, it will
be set as the content of the response automatically.

Defining Arguments
~~~~~~~~~~~~~~~~~~

The unified arguments sent through the HTTP request (that includes query parameters
from the URI, possible POST arguments and uploaded files) are pre-processed and
mapped to method arguments of an action. That means: all arguments a action needs
in order to work should be declared as *method parameters* of the action method and
not be retrieved from one of the superglobals ($_GET, $_POST, …) or the HTTP request.

Declaring arguments in an action controller is very simple::

	/**
	 * Says hello to someone.
	 *
	 * @param string $name Name of the someone
	 * @param boolean $formal If the message should be formal (or casual)
	 * @return string
	 */
	public function sayHelloAction($name, $formal = TRUE) {
		$message = ($formal ? 'Greetings, Mr. ' : 'Hello, ') . $name;
		return $message
	}

The first argument ``$name`` is mandatory. The ``@param`` annotation gives FLOW3
a hint of the expected type, in this case a string.

The second argument ``$boolean`` is optional because a default value has been
defined. The ``@param`` annotation declares this argument to be a boolean, so you
can expect that ``$formal`` will be, in any case, either ``TRUE`` or ``FALSE``.

A simple way to pass an argument to the action is through the query parameters in
a URL::

	http://localhost/acme.demo/helloworld/sayhello.html?name=Robert&formal=0

.. note::

	Please note that the documentation block of the action method is mandatory – the
	annotations (tags) you see in the example are important for FLOW3 to recognize
	the correct type of each argument.

Additionally to passing the arguments to the action method, all registered arguments
are also available through ``$this->arguments``.

Argument Mapping
~~~~~~~~~~~~~~~~

Internally the Action Controller uses the Property Mapper for mapping the raw
arguments of the HTTP request to an ``Mvc\Controller\Arguments`` object. The
Property Mapper can convert and validate properties while mapping them, which allows
for example to transparently map values of a submitted form to a new or existing
model instance. It also makes sure that validation rules are considered and that
only certain parts of a nested object structure can be modified through user input.

In order to understand the mapping process, we recommend that you take a look at
the respective chapter about :doc:`PropertyMapping`.

Here are some more examples illustrating the mapping process of submitted arguments
to the method arguments of an action:

Besides simple types, also special object types, like ``DateTime`` are supported::

	# http://localhost/acme.demo/foo/bar.html?date=2012-08-10T14:51:01+02:00

	/**
	 * @param \DateTime $date Some date
	 * @return string
	 */
	public function barAction(\DateTime $date) {
		# …
	}

Properties of domain models (or any other objects) can be set through an array-like
syntax. The property mapper creates a new object by default::

	# http://localhost/acme.demo/foo/create.html?customer[name]=Robert

	/**
	 * @param Acme\Demo\Domain\Model\Customer $customer A new customer
	 * @return string
	 */
	public function createAction(\Acme\Demo\Domain\Model\Customer $customer) {
		return 'Hello, new customer: ' . $customer->getName();
	}

If an identity was specified, the Property Mapper will try to retrieve an object of
that type::

	# http://localhost/acme.demo/foo/create.html?customer[number]=42&customer[name]=Robert

	/**
	 * @param Acme\Demo\Domain\Model\Customer $customer An existing customer
	 * @param string $name The name to set
	 * @return string
	 */
	public function updateAction(\Acme\Demo\Domain\Model\Customer $customer, $name) {
		$customer->setName($name);
		$this->customerRepository->update($customer);
	}

.. note::

	``number`` must be declared as (part of) the identity of a ``Customer``	object
	through an ``@Identity`` annotation. You'll find more information about
	identities and also about the creation and update of objects in the
	:doc:`Persistence` chapter.

Instead of passing the arguments through the query string, like in the previous
examples, they can also be submitted as POST or PUT arguments in the body of a
request or even be a mixture of both, query parameters and parameters contained
in the HTTP body. Argument values are merged in the following order, while the
later sources replace earlier ones

* query string (derived from $_GET)
* body (typically from POST or PUT requests)
* file uploads (derived from $_FILES)

Internal Arguments
~~~~~~~~~~~~~~~~~~

In some situations FLOW3 needs to set special arguments in order to simplify
handling of objects, widgets or other complex operations. In order to avoid
name clashes with arguments declared by a package author, a special prefix
consisting of two underscores ``__`` is used. Two examples of internal arguments
are the automatically generated *HMAC* and *CSRF* hashes [#]_ which are sent along
with the form data::

	<form enctype="multipart/form-data" name="newPost" method="post"
			action="posts/create?__csrfToken=cca240aa13af5bdacea3756b85ed12a2">
		<input type="hidden" name="__trustedProperties" value="a:3:{s:4:&quot;blog&quot;;…
		<label for="author">Author</label><br />
		<input id="author" type="text" name="newPost[author]" value="First Last" /><br />
		…

Although internal arguments can be retrieved through a method provided by the
``ActionRequest`` object, they are, as the name suggests, only for internal use.
You should not use or rely on these arguments in your own applications.

Plugin Arguments
~~~~~~~~~~~~~~~~

Besides internal arguments, FLOW3 stores arguments being used by recursive controller
invocations, like plugins, in a separate namespace, the so called ``pluginArguments``.

They are prefixed with two dashes ``--`` and normally, you do not interact with them.

initialize*()
~~~~~~~~~~~~~

The Action Controller's ``processRequest()`` method initializes important parts of
the controller, maps and validates arguments and finally calls the requested action
method. In order to execute code before the action method is called, it is possible
to implement one or more initialization methods. The following methods are currently
supported:

* ``initializeAction()``
* ``initialize[ActionName]()``
* ``initializeView()``

The first method executed after the base initialization is ``initializeAction()``.
The Action Controller only provides an empty method which can be overriden by a
concrete Action Controller. The information about action method arguments and
the corresponding validators has already been collected at this point, but any
arguments sent through the request have not yet been mapped or validated. Therefore,
``initializeAction()`` can still modify the list of possible arguments or add /
remove certain validators by altering ``$this->arguments``.

Right after the generic ``initializeAction()`` method has been called, the
Action Controller checks if a more specific initialization method was implemented.
For example, if the action name is "create" and thus the action method name is
``createAction()``, the controller would try to call a method
``initializeCreateAction()``. This allows for execution of code which is targeted
directly to a specific action.

Finally, after arguments have been mapped and the controller is almost ready to
call the action method, it tries to resolve a suitable *view* and, if it was
successful, runs the ``initializeView()`` method. In many applications, the view
implementation will be a Fluid Template View. The ``initializeView()`` method can
be used to assign template variables which are needed in any of the existing
actions or conduct other template-specific configuration steps.

Media Type / Format
-------------------

Any implementation based on ``AbstractController`` can support one or more formats
for its response. Depending on the preferences of the client sending the request
and the route which matched the request the controller needs render the response
in a format the client understands.

The supported and requested formats are specified as an `IANA Media Type`_ and is,
by default, ``text/html``. In order to support a different or more than one media
type, the controller needs override the default simply by declaring a class property
like in the following example::

	class FooController extends ActionController {

		/**
		 * A list of IANA media types which are supported by this controller
		 *
		 * @var array
		 */
		protected $supportedMediaTypes = array('application/json', 'text/html');

		# …
	}

The media types listed in ``$supportedMediaTypes`` don't need to be in any
particular order.

The Abstract Controller determines the preferred format through `Content Negotiation`_.
More specifically, FLOW3 will check if any specific format was defined in the route
which matched the request (see chapter :doc:`Routing`). If no particular format was
defined, the ``Accept`` header of the HTTP Request is consulted for a weighted list
of preferred media types. This list is then matched with the list of supported media
types and hopefully results in one media type which is set as the ``format`` in the
Action Request.

.. hint::

	With "format" we are referring to the typical file extension which corresponds to
	a specific media type. For example, the format for ``text/html`` is "html" and
	the format corresponding to the media type ``application/json`` would be "json".
	For a complete list of supported media types and their corresponding formats
	please refer to the class ``TYPO3\FLOW3\Utility\MediaTypes``.

The controller implementation must take care of the actual media type support by
supplying a corresponding view or template.

Fluid Template View
-------------------

An Action Controller can directly return the rendered content by means of a string
returned by the action method. However, this approach is not very flexible and
ignores the separation of concerns as laid out by the Model View Controller pattern.
Instead of rendering an output itself, a controller delegates this task to a view.

FLOW3 uses the Fluid template engine as the default view for action controllers. By
following a naming convention for directories and template files, developers of a
concrete controller don't need to configure the view or paths to the respective
templates – they are resolved automatically by converting the combination of
package key, controller name and action name into a Fluid template path.

Given that the package key is ``Acme.Demo``, the controller name is ``HelloWorld``,
the action name is ``sayHello`` and the format is ``html``, the following path and
filename would be used for the corresponding Fluid template:

.. code-block:: none

	./Packages/…/Acme.Demo/Resources/Private/Templates/HelloWorld/SayHello.html

If a template file matching the current request was found, the Action Controller
initializes a Fluid Template View with the correct path name. This pre-initialized
view is available via ``$this->view`` in any Action Controller and can be used for
assigning template variables::

	$this->view->assign('products', $this->productRepository->findAll());

If an action does not return a result (that is, the result is ``NULL``), an
Action Controller automatically calls the ``render()`` method of the current view.
That means, apart from assigning variables to the template (if any), there is rarely
a need to deal further with a Fluid Template View.

Json View
---------

When used as a web service, controllers may want to return data in a format which
can be easily used by other applications. Especially in a web context JSON has
become an often used format which is very light-weight and easy to parse. Although
it is theoretically possible to render a JSON response through a Fluid Template
View, a specialized view does a much better job in a more convenient way.

The JSON View provided by FLOW3 can be used by declaring it as the default view
in the concrete Action Controller implementation::

	class FooController extends ActionController {

		/**
		 * @var string
		 */
		protected $defaultViewObjectName = 'TYPO3\FLOW3\Mvc\View\JsonView';

		# …
	}

Alternatively, if more than only the JSON format should be supported, the format
to view mapping feature can be used::

	class FooController extends ActionController {

		/**
		 * @var string
		 */
		protected $viewFormatToObjectNameMap = array(
			'html' => 'TYPO3\Fluid\View\TemplateView',
			'json' => 'TYPO3\FLOW3\Mvc\View\JsonView'
		);

		/**
		 * A list of IANA media types which are supported by this controller
		 *
		 * @var array
		 */
		protected $supportedMediaTypes = array('application/json', 'text/html');

		# …
	}

In either case, the JSON View is now invoked if a request is sent which prefers
the media type ``application/json``. In order to return something useful, the data
which should be rendered as JSON must be set through the ``assign()`` method. By
default JSON View uses the variable named "value"::

	/**
	 * @param \Acme\Demo\Model\Product $product
	 * @return void
	 */
	public function showAction(Product $product) {
		$this->view->assign('value', $product);
	}

To change the name of the rendered variables, use the ``setVariablesToRender()``
method on the view.

If the controller is configured to use the JSON View, this action may return JSON
code like the following:

.. code-block:: javascript

	{"name":"Arabica","weight":1000,"price":23.95}


Furthermore, the JSON view can be configured to determine which variables of the object
should be included in the output. For that, a configuration array needs to be provided
with ``setConfiguration()``::

	/**
	 * @param \Acme\Demo\Model\Product $product
	 * @return void
	 */
	public function showAction(Product $product) {
		$this->view->assign('value', $product);
		$this->view->setConfiguration(/* configuration follows here */);
	}

The configuration is an array which is structured like in the following example::

	array(
		'value' => array(

				// only render the "name" property of value
			'_only' => array('name')
		),
		'anothervalue' => array(

				// render every property except the "password"
				// property of anothervalue
			'_exclude' => array('password')

				// we also want to include the sub-object
				// "address" as nested JSON object
			'_descend' => array(
				'address' => array(
					// here, you can again configure
					// _only, _exclude and _descend if needed
				)
			)
		),
		'arrayvalue' => array(

				// descend into all array elements
			'_descendAll' => array(
				// here, you can again configure _only,
				// _exclude and _descend for each element
			)
		),
		'valueWithObjectIdentifier' => array(

				// by default, the object identifier is not
				// included in the output, but you can enable it
			'_exposeObjectIdentifier' => TRUE,

				// the object identifier should not be rendered
				// as "__identity", but as "guid"
			'_exposedObjectIdentifierKey' => 'guid'
		)
	)

To sum it up, the JSON view has the following configuration options to control
the output structure:

* ``_only`` (array): Only include the specified property names in the output
* ``_exclude`` (array): Include all except the specified property names in
  the output
* ``_descend`` (associative array): Descend into the specified sub-objects
* ``_descendAll`` (array): Descend into all array elements and generate a
  numeric array
* ``_exposeObjectIdentifier`` (boolean): if TRUE, the object identifier is
  displayed inside ``__identifier``
* ``_exposeObjectIdentifierKey`` (string): the JSON field name inside which
  the object identifier should be displayed

Custom View
-----------

Similar to the Fluid Template View and the JSON View, packages can provide their
own custom views. The only requirement for such a view is the implementation of
all methods defined in the ``TYPO3\FLOW3\Mvc\View\ViewInterface``.

An Action Controller can be configured to use a custom view through the
``$defaultViewObjectName`` and ``$viewFormatToObjectNameMap`` properties, as
explained in the section about JSON View.

Controller Context
~~~~~~~~~~~~~~~~~~

The Controller Context is an object which encapsulates all the controller-related
objects and makes them accessible to the view. Thus, the ``$this->request`` property
of the controller is available inside the view as
``$this->controllerContext->getRequest()``.

Validation
----------

Arguments which were sent along with the HTTP request are usually sanitized and
valdidated before they are passed to an action method of a controller. Behind the
scenes, the :doc:`Property Mapper <PropertyMapping>` is used for mapping and
validating the raw input. During this process, the validators are invoked:

* *base validation* as defined in the model to be validated (if any)
* *argument validation* as defined in the controller or action

The chapter about :doc:`Validation` outlines the general validation mechanism and
how declare and configure *base validation*. While the rules declared in a model
describe the minimum requirements for a valid entity, the rules declared in a
controller define additional preconditions before arguments may be passed to an
action method.

Per-action validation rules are declared through the ``Validate`` annotation. As
an example, an email address maybe optional in a Customer model, but it may be
required when a customer entity is passed to a ``signUpAction()`` method::

		/**
		 * @param \Acme\Demo\Domain\Model\Customer $customer
		 * @FLOW3\Validate(argumentName="emailAddress", type="EmailAddress")
		 */
		public function signUpAction(Customer $customer) {
			# …
		}

While ``Validate`` defines additional rules, the ``IgnoreValidation`` annotation
does the opposite: any base validation rules declared for the specified argument
will be ignored::

		/**
		 * @param \Acme\Demo\Domain\Model\Customer $customer
		 * @FLOW3\IgnoreValidation("$customer")
		 */
		public function signUpAction(Customer $customer) {
			# …
		}

The next section explains how to get a hold of the validation results and react
on warnings or errors which occurred during the mapping and validation step.

Error Handling
--------------

The argument mapping step based on the validation rules mentioned earlier makes
sure that an action method is only called if its arguments are valid. In the reverse
it means that the action specified by the request will not be called if a mapping
or validation error ocurred. In order to deal with these errors and provide a
meaningful error message to the user, a special action is called instead of the
originally intended action.

The default implementation of the ``errorAction()`` method will redirect the browser
to the URI it came from, for example to redisplay the originally submitted form.

Any errors or warnings which ocurred during the argument mapping process are stored
in a special object, the *mapping results*. These mapping results can be
conviently access through a Fluid view helper in order to display warnings and
errors along the submitted form or on top of it::

	<f:form.validationResults>
		<f:if condition="{validationResults.flattenedErrors}">
			<ul class="errors">
				<f:for each="{validationResults.flattenedErrors}" as="errors" key="propertyPath">
					<li>{propertyPath}
						<ul>
							<f:for each="{errors}" as="error">
								<li>{error.code}: {error}</li>
							</f:for>
						</ul>
					</li>
				</f:for>
			</ul>
		</f:if>
	</f:form.validationResults>

Besides using the view helper to display the validation results, you can also
completely replace the ``errorAction()`` method with your own custom method.

Upload Handling
---------------

The handling of file uploads is pretty straight forward. Files are handled
internally as ``Resource`` objects and thus, storing an uploaded file is just a
matter of declaring a property of type ``Resource`` in the respective model.

There is a full example explaining file uploads in the
:doc:`chapter about resource management <ResourceManagement>`.

REST Controller
---------------

tbd.

Generating Links
----------------

Links to other controller and their actions should not be rendered manually because
hardcoded or manually rendered links circumvent many of FLOW3's features, including
some security-related ones.

For generating links to other controllers, the ``UriBuilder`` which is available
as ``$this->uriBuilder`` can be used. However, in most cases, the user does not
directly interact with this one, but rather uses ``forward()``, ``redirect()``
in the Controller and ``<f:link.action />`` / ``<f:uri.action />`` inside Fluid
templates.

forward() and redirect()
------------------------

Often, controllers need to defer execution to other controllers or actions. For
that to happen, FLOW3 supports both, internal and external redirects:

* in an internal redirect which is triggered by ``forward()``, the URI does not
change.
* in an external redirect, the browser receives a HTTP ``Location`` header, redirecting
him to the new controller. Thus, the URI changes.

As a consequence, ``forward()`` can also call controllers or actions which are
not exposed through the routing mechanism, while ``redirect()`` only works with
publicly callable controllers.

This example demonstrates the usage of ``redirect()``::

	public function createAction(Product $product) {
			// TODO: store the product somewhere

		$this->redirect('show', NULL, NULL, array('product' => $product));

			// This line is never executed, as redirect() and
			// forward() immediately stop execution of this method.
	}

It is good practice to have different actions for *modifying* and *showing* data.
Often, redirects are used to link between them. As an example, an ``updateAction()``
which updates an object should then ``redirect()`` to the ``show`` action of the
controller, then displays the updated object.

``forward()`` supports the following arguments:

* ``$actionName`` (required): Name of the target action
* ``$controllerName``: Name of the target controller. If not specified, the current
  controller is used.
* ``$packageKey``: Name of the package, optionally with sub-package. If not specified,
  the current package key / subpackage key is specified. The package and sub-package
  need to be delimited by ``\``, so ``Foo.Bar\Test`` will set the package to ``Foo.Bar``
  and the subpackage to ``Test``.
* ``$arguments``: array of request arguments. Objects are automatically converted to their
  identity.

``redirect()`` supports all of the above arguments, additionally with the following ones:

* ``$delay``: Delay in seconds before redirecting
* ``$statusCode``: the status code to be used for redirecting. By default, 303 is used.
* ``$format``: The target format for the redirect. If not set, the current format is used.


Flash Messages
--------------

In many applications users need to be notified about the application flow, telling
him for example that an object has been successfully saved or deleted. Such messages,
which should be displayed to the user only once, are called *Flash Messages*.

A Flash Message can be added inside the controller by using the ``addFlashMessage`` method,
which expects the following arguments:

* ``$messageBody`` (required): The message which should be shown
* ``$messageTitle``: The title of the message
* ``$severity``: The severity of the message; by default "OK" is used. Needs to be one
  of TYPO3\FLOW3\Error\Message::SEVERITY_* constants (OK, NOTICE, WARNING, ERROR)
* ``$messageArguments`` (array): If the message contains any placeholders, these can be
  filled here. See the PHP function ``printf`` for details on the placeholder format.
* ``$messageCode`` (integer): unique code of this message, can be used f.e. for localization.
  By convention, if you set this, it should be the UNIX timestamp at time of writing the
  source code to be roughly unique.

Creating a Flash Messages is a matter of a single line of code::

	$this->addFlashMessage('Everything is all right.');
	$this->addFlashMessage('Sorry, I messed it all up!', 'My Fault', \TYPO3\FLOW3\Error\Message::SEVERITY_ERROR);

The flash messages can be rendered inside the template using the ``<f:flashMessages />``
ViewHelper. Please consult the ViewHelper for a full reference.


.. _IANA Media Type: http://www.iana.org/assignments/media-types/index.html

.. _Content Negotiation: http://en.wikipedia.org/wiki/Content_negotiation

.. [#] The HMAC and CSRF hashes improve security for form submissions and actions
       on restricted resources. Please refer to the :doc:`Security` chapter for more
       details.
