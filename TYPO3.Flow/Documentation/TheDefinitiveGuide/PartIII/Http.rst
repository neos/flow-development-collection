HTTP Foundation
===============

Most applications which are based on FLOW3 are web applications. As the HTTP
protocol is the foundation of the World Wide Web, it also plays an important role in
the architecture of the FLOW3 framework.

This chapter describes the mechanics behind FLOW3's request-response model, how it
relates to the Model View Controller framework and which API functions you can use
to deal with specific aspects of the HTTP request and response.

The HTTP 1.1 Specification
--------------------------

Although most people using or even developing for the web are aware of the fact that
the Hypertext Transfer Protocol is responsible for carrying data around, considerably
few of them have truly concerned themselves with the HTTP 1.1 specification.

The specification, `RFC 2616`_, has been published in 1999 already but it is
relevant today more than ever. If you've never fully read it, we recommend that you
do so. Although it is a long read, it is important to understand the intentions and
rules of the protocol before you can send cache headers or response codes in good
conscience, or even claim that you developed a true `REST`_ service.

Application Flow
----------------

The basic walk through a FLOW3-based web application is as follows:

* the browser sends an HTTP request to a webserver
* the webserver calls Web/index.php and passes control over to FLOW3
* the Bootstrap [#]_ initializes the bare minimum and passes control to a suitable
  request handler
* by default, the HTTP Request Handler [#]_ takes over and runs a boot sequence
  which initializes all important parts of FLOW3
* the HTTP Request Handler builds an HTTP Request and Response object. The Request
  object [#]_ contains all important properties of the real HTTP request. The
  Response object [#]_ in turn is empty and will be filled with information by a
  controller at a later point.
* the HTTP Request Handler invokes the Router [#]_ to determine which controller
  and action is responsible for processing the request. This information (controller
  name, action name, arguments) are returned in form of an Action Request [#]_
* the Dispatcher [#]_ tries to invoke the controller mentioned in the Action
  Request.
* the controller, usually an Action Controller [#]_, processes the request and
  modifies the given HTTP Response object which will, in the end, contain the
  content to display (body) as well as any headers to be passed back to the client.
* finally control reaches the HTTP Request Handler again which tries to make the
  HTTP Response standards compliant (if not already the case) and sends the response
  to the browser.

In practice, there are a few more intermediate steps being carried out, but in
essence, this is the path a request is taking.

.. figure:: /Images/TheDefinitiveGuide/PartIII/Http_ApplicationFlow.png
	:align: center
	:width: 400pt
	:alt: Simplified application flow

The next sections shed some light on the most important actors of this application
flow.

Request Handler
---------------

The request handler is responsible for taking a request and responding in a manner
the client understands. The default HTTP Request Handler routes requests to
controllers and their actions. Other request handlers may choose a completely
different way to handle requests. Although FLOW3 also supports other types of
requests (most notably, from the comannd line interface), this chapter only deals
with HTTP requests.

FLOW3 comes with a very slim bootstrap, which resulst in  few code being executed
before control is handed over to the request handler. This pays off in situations
where a specialized request handler is supposed to handle specific requests in a
very effective way. In fact, the request handler is responsible for executing big
parts of the initialization procedures and thus can optimize the boot process by
choosing only the parts it actually needs.

A request handler must implement the ``\TYPO3\FLOW3\Core\RequestHandlerInterface``
interface which, among others, contains the following methods::

	public function handleRequest();

	public function canHandleRequest();

	public function getPriority();

On trying to find a suitable request handler, the bootstrap asks each registered
request handler if it can handle the current request using ``canHandleRequest()``
– and if it can, how eager it is to do so through ``getPriority()``. Request
handlers responding with a high number as their priority, are preferred over request
handlers reporting a lower priority. Once the boostrap has identified a matching
request handler, it passes control to it by calling its ``handleRequest()`` method.

Request handlers must first be registered in order to be considered during the
resolving phase. Registration is done in the ``Package`` class of the package
containing the request handler::

	class Package extends BasePackage {

		public function boot(\TYPO3\FLOW3\Core\Bootstrap $bootstrap) {
			$bootstrap->registerRequestHandler(new \Acme\Foo\BarRequestHandler($bootstrap));
		}

	}

Request
-------

The ``TYPO3\FLOW3\Http\Request`` class is, like most other classes in the ``Http``
sub package, a relatively close match of a request according to the HTTP 1.1
specification. You'll be best off studying the API of the class and reading the
respective comments for getting an idea about the available functions. That being
said, we'll pick a few important methods which may need some further explanation.

Constructing a Request
~~~~~~~~~~~~~~~~~~~~~~

You can, in theory, create a new ``Request`` instance by simply using the ``new``
operator and passing the required arguments to the constructor. However, there are
two static factory methods which make life much easier. We recommend using these
instead of the low-level constructor method.

create()
~~~~~~~~

The method ``create()`` accepts an URI, the request method, arguments and a few more
parameters and returns a new ``Request`` instance with sensible default properties
set. This method is best used if you need to create a new ``Request`` object from
scratch without taking any real HTTP request into account.

createFromEnvironment()
~~~~~~~~~~~~~~~~~~~~~~~

The second method, ``createFromEnvironment()``, take the environment provided
by PHP's superglobals and specialized functions into account. It creates a
``Request`` instance which reflects the current HTTP request received from the
web server. This method is best used if you need a ``Request`` object with all
properties set according to the current server environment and incoming HTTP request.

createActionRequest()
~~~~~~~~~~~~~~~~~~~~~

In order to dispatch a request to a controller, you need an ``ActionRequest``.
Such a request is always bound to an ``Http\Request``. The easiest way to create
one and binding it at the same time, is using the ``createActionRequest()`` method::

	$httpRequest = Request::createFromEnvironment();
	$actionRequest = $httpRequest->createActionRequest();

Arguments
~~~~~~~~~

The request features a few methods for retrieving and setting arguments. These
arguments are the result of merging any GET, POST and PUT arguments and even the
information about uploaded files. Be aware that these arguments have not been
sanitized or further processed and thus are not suitable for being used in controller
actions. If you, however, need to access the raw data, these API function are the right way
to retrieve them.

Arguments provided by POST or PUT requests are usually encoded in one or the other
way. FLOW3 detects the encoding through the ``Content-Type`` header and decodes the
arguments and their values automatically.

getContent()
~~~~~~~~~~~~

You can access the request body easily by calling the ``getContent()`` method. For
performance reasons you may also retrieve the content as a stream instead of a
string. Please be aware though that, due to how input streams work in PHP, it is not
possible to retrieve the content as a stream a second time.

Media Types
~~~~~~~~~~~

The best way to determine the media types mentioned in the ``Accept`` header of a
request is to call the ``getAcceptedMediaTypes()`` method. There is also a method
implementing content negotiation in a convenient way: just pass a list of supported
formats to ``getNegotiatedMediaType()`` and in return you'll get the media type
best fitting according to the preferences of the client::

	$preferredType = $request->getNegotiatedMediaType(array('application/json', 'text/html'));

Response
--------

Being the counterpart to the request, the ``Response`` class represents the HTTP
response. Its most important function is to contain the response body and the
response status. Again, it is recommended to take a closer look at the actual
class before you start using the API in earnest.

The ``Response`` class features a few specialities, we'd like to mention at this
point:

Dates
~~~~~

The dates passed to one of the date-related methods must either be a RFC 2822
parsable date string or a PHP ``DateTime`` object. Please note that all methods
returning a date will do so in form of a ``DateTime`` object.

According to `RFC 2616`_ all dates must be given in `Coordinated Universal Time`_,
also known as ``UTC``. UTC is also sometimes referred to as ``GMT``, but in fact
`Greenwich Mean Time`_ is not the correct time standard to use. Just to complicate
things a bit more, according to the standards the HTTP headers will contain dates
with the timezone declared as ``GMT`` – which in reality refers to ``UTC``.

FLOW3 will always return dates related to HTTP as UTC times. Keep that in mind if
you pass dates from a different standard and then retrieve them again: the
``DateTime`` objects will mark the same point in time, but have a different time
zone set.

Headers
-------

Both classes, ``Request`` and ``Response`` inherit methods from the ``Message``
class. Among them are functions for retrieving and setting headers. If you need to
deal with headers, please have a closer look at the ``Headers`` class which not
only contains setters and getters but also some specialized cookie handling and
cache header support.

In general, ``Cache-Control`` directives can be set through the regular ``set()``
method. However, a more convenient way to tweak single directives without overriding
previously set values is the ``setCacheControlDirective()`` method. Here is an
example – from the context of an Action Controller – for setting the ``max-age``
directive one hour::

	$headers = $this->request->getHttpRequest()->getHeaders();
	$headers->setCacheControlDirective('max-age', 3600);


.. _RFC 2616: http://tools.ietf.org/html/rfc2616
.. _REST: http://en.wikipedia.org/wiki/Representational_state_transfer
.. _Coordinated Universal Time: http://en.wikipedia.org/wiki/Coordinated_Universal_Time

.. [#] TYPO3\FLOW3\Core\Bootstrap
.. [#] TYPO3\FLOW3\Http\RequestHandler
.. [#] TYPO3\FLOW3\Http\Request
.. [#] TYPO3\FLOW3\Http\Response
.. [#] TYPO3\FLOW3\Mvc\Routing\Router
.. [#] TYPO3\FLOW3\Mvc\ActionRequest
.. [#] TYPO3\FLOW3\Mvc\Dispatcher
.. [#] TYPO3\FLOW3\Mvc\Controller\ActionController