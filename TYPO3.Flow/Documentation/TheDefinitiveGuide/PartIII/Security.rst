.. _ch-security:

========
Security
========

.. sectionauthor:: Andreas Förthner

Security Framework
==================

All tasks related to security of a FLOW3 application are handled centrally by the security
framework. Besides other functionality, this includes especially features like
authentication, authorization, channel security and a powerful policy component. This
chapter describes how you can use FLOW3's security features and how they work internally.

Activation and initialization
=============================

All the described features in this chapter can be enable or disabled by setting the
following value in *Settings.yaml* configuration file:

*Example: Enable the security features in the Settings.yaml configuration file*

.. code-block:: yaml

	FLOW3:
	  security:
	    enable:
		  TRUE

If set to "yes", which is the default, the security framework engages with FLOW3
by vowing in two AOP advices into the MVC dispatcher and another two into the
persistence layer classes.

Security context
----------------

The first security advice (``initializeSecurity`` in the
``TYPO3\FLOW3\Security\Aspect\RequestDispatchingAspect``) initializes the security context
for the current request. The security context (``TYPO3\FLOW3\Security\Context``) shipped
with FLOW3, lies in session scope and holds context data like the current authentication
status. That means, if you need data related to security, the security context (you can
get it easily with dependency injection) will be your main information source. The details
of the context's data will be described in the next chapters.

Authentication
==============

One of the main things people associate with security is authentication. That means to
identify your communication partner - the one sending a request to FLOW3. Therefore the
framework provides an infrastructure to easily use different mechanisms for such a
plausibility proof. The most important achievement of the provided infrastructure is its
flexible extensibility. You can easily write your own authentication mechanisms and
configure the framework to use them without touching the framework code itself. The
details are explained in the section  :ref:`Implementing your own authentication mechanism`.

.. _Using the authentication controller:

Using the authentication controller
-----------------------------------

First, let's see how you can use FLOW3's authentication features. There is a special
controller in the security package: the ``AuthenticationController``. This controller has
two actions, namely ``authenticateAction()`` and ``logoutAction()``, an appropriate route
is configured. If you call ``http://localhost/flow3/authenticate`` in your Browser, the
default authentication mechanism will be triggered. This mechanism, implemented in a so
called authentication provider, authenticates a user account by checking a username and
password against accounts stored in the content repository. [#]_

The configuration for this default provider, which is shipped with FLOW3's default
configuration looks like this:

*Example: Configuration of the default username/password authentication mechanism in Settings.yaml*

.. code-block:: yaml

	FLOW3:
	  security:
	    authentication:
	      providers:
	        DefaultProvider:
	          provider: PersistedUsernamePasswordProvider

This registers the ``PersistedUsernamePasswordProvider`` authentication provider under
the name "``DefaultProvider``" as the only, global authentication mechanism. To
successfully authenticate an account with this default provider, you'll obviously have to
provide a username and password. This is done by sending two POST variables to the
authentication controller. Have a look at the following HTML snippet with a simple login
form you can use for that:

*Example: A simple login form*

.. code-block:: html

	<form action="flow3/authenticate" method="post" name="loginform">
		<input type="text" id="username"
			name="__authentication[TYPO3][FLOW3][Security][Authentication][Token][UsernamePassword][username]"
			value="" tabindex="1" />
		<input type="password" id="password"
			name="__authentication[TYPO3][FLOW3][Security][Authentication][Token][UsernamePassword][password]"
			value="" tabindex="2" />
		<input type="submit" value="Login" tabindex="3" />
	</form>

After submitting the form, the internal authentication process will be triggered and if
you provided valid credentials an account will be authenticated afterwards. [#]_

.. note::

	After authentication the ``authenticate()`` action will automatically redirect to the
	original request, if the authentication process has been triggered due missing privileges
	while handling this original request.

The internal authentication process
-----------------------------------

Now that you know, how you can authenticate, let's have a look at the internal process.
The following sequence diagram shows the participating components and their interaction:

.. figure:: /Images/TheDefinitiveGuide/PartIII/Security_BasicAuthenticationProcess.png
	:align: center
	:width: 400pt
	:alt: Internal authentication process

	Internal authentication process

As already explained, the security framework is initialized in the dispatcher by vowing in
an AOP advice, which resides in the ``RequestDispatchingAspect`` class. This advice
intercepts the request dispatching before any controller is called. Regarding
authentication, you can see, that a so called authentication token will be stored in the
security context and some credentials will be updated in it.

Authentication tokens
~~~~~~~~~~~~~~~~~~~~~

An authentication token holds the status of a specific authentication mechanism, for
example it receives the credentials (e.g. a username and password) needed for
authentication and stores one of the following authentication states in the session. [#]_

These constants are defined in the authentication token interface
(``TYPO3\FLOW3\Security\Authentication\TokenInterface``) and the status can be obtained
from the ``getAuthenticationStatus()`` method of any token.

.. tip::

	If you only want to know, if authentication was successful, you can call the
	convenient method ``isAuthenticated()``.

``NO_CREDENTIALS_GIVEN``
	This is the default state. The token is not authenticated and holds no credentials,
	that could be used for authentication.
``WRONG_CREDENTIALS``
	It was tried to authenticate the token, but the credentials were wrong.
``AUTHENTICATION_SUCCESSFUL``
	The token has been successfully authenticated.
``AUTHENTICATION_NEEDED``
	This indicates, that the token received credentials, but has not been authenticated yet.

Now you might ask yourself, how a token receives its credentials. The simple answer
is: It's up to the token, to fetch them from somewhere. The default ``UsernamePassword``
token for example looks for a username and password in the two POST parameters:
``__authentication[TYPO3][FLOW3][Security][Authentication][Token][UsernamePassword][username]`` and
``__authentication[TYPO3][FLOW3][Security][Authentication][Token][UsernamePassword][password]`` (see
:ref:`Using the authentication controller`). The framework only makes sure that
``updateCredentials()`` is called on every token, then the token has to set possibly
available credentials itself, e.g. from available headers or parameters or anything else
you can provide credentials with.

Authentication manager and provider
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

After the tokens have been initialized the original request will be processed by the
resolved controller. In our case this is the special authentication controller
(``TYPO3\FLOW3\Security\Authentication\Controller\AuthenticationController``)
of FLOW3, which will call the authentication manager to authenticate the tokens. In turn
the authentication manager calls all authentication providers in the configured order. A
provider implements a specific authentication mechanism and is therefore responsible for
a specific token type. E.g. the already mentioned ``PersistedUsernamePasswordProvider``
provider is able to authenticate the ``UsernamePassword`` token.

After checking the credentials, it is the responsibility of an authentication provider to
set the correct authentication status (see above) and ``Roles`` in its corresponding token.
The role implementation resides in the ``TYPO3\FLOW3\Security\Policy`` namespace. (see the
Policy section for details).

.. _Account management:

Account management
------------------

In the previous section you have seen, how accounts can be authenticated in FLOW3. What
was concealed so far is, how these accounts are created or what is exactly meant by the
word "account". First of all let's define what accounts are in FLOW3 and how they are used
for authentication. Following the OASIS CIQ V3.0 [#]_ specification, an account used for
authentication is separated from a user or more
general a party. The advantage of this separation is the possibility of one user having
more than one account. E.g. a user could have an account for the ``UsernamePassword``
provider and one account connected to an LDAP authentication provider. Another scenario
would be to have different accounts for different parts of your FLOW3 application. Read
the next section :ref:`Advanced authentication configuration` to see how this can be
accomplished.

As explained above, the account stores the credentials needed for authentication.
Obviously these credentials are provider specific and therefore every account is only
valid for a specific authentication provider. This provider - account connection is stored
in a property of the account object named ``authenticationProviderName``. Appropriate
getters and setters are provided. The provider name is configured in the *Settings.yaml*
file. If you look back to the default configuration, you'll find the name of the default
authentication provider: ``DefaultProvider``. Besides that, each account has another
property called ``credentialsSource``, which points to the place or describes the
credentials needed for this account. This could be an LDAP query string, or in case of the
``PersistedUsernamePasswordProvider`` provider, the username, password hash and salt are
stored directly in this member variable.

It is the responsibility of the authentication provider to check the given credentials
from the authentication token, find the correct account for them [#]_ and to decide about
the authentication status of this account.

.. note::

	In case of a directory service, the real authentication will probably not take place
	in the provider itself, but the provider will pass the result of the directory service
	on to the authentication token.

.. note::

	The ``DefaultProvider`` authentication provider used in the examples is not shipped
	with FLOW3, you have to configure all available authentication providers in your application.

Creating accounts
~~~~~~~~~~~~~~~~~

Creating an account is as easy as creating a new account object and add it to the account
repository. Look at the following example, which uses the ``TYPO3\FLOW3\Security\AccountFactory``
to create a simple username/password account for the DefaultProvider:

*Example: Add a new username/password account* ::

	$identifier = 'andi';
	$password = 'secret';
	$roles = array('Administrator');
	$authenticationProviderName = 'DefaultProvider';

	$account = $this->accountFactory->createAccountWithPassword($identifier, $password, $roles, $authenticationProviderName);
	$this->accountRepository->add($account);

The way the credentials are stored internally is completely up to the authentication provider.
The ``PersistedUsernamePasswordProvider`` uses the
``TYPO3\FLOW3\Security\Cryptography\HashService`` to verify the given password. In the
example above, the given plaintext password will be securely hashed by the ``HashService``.
The hashing is the main magic happening in the ``AccountFactory`` and the reason why we don't
create  the account object directly. If you want to learn more about secure password hashing
in FLOW3, you should read the section about :ref:`Cryptography` below. You can also see, that there
is an array of roles added to the account. This is used by the policy system and will be
explained in the according section below.

.. note::

	This example expects the account factory and account repository to be available in
	``$this->accountFactory`` and ``$this->accountRepository`` respectively. If you
	use this snippet in an action controller, these can be injected very easily by
	dependency injection.

.. _Advanced authentication configuration:

Advanced authentication configuration
-------------------------------------

Parallel authentication
~~~~~~~~~~~~~~~~~~~~~~~

Now that you have seen all components, taking part in the authentication process, it is
time to have a look at some advance configuration possibilities. Just to remember, here is
again the configuration of the default authentication provider:

.. code-block:: yaml

	security:
	  authentication:
	    providers:
	      DefaultProvider:
	        provider: PersistedUsernamePasswordProvider

If you have a closer look at this configuration, you can see, that the word providers is
plural. That means, you have the possibility to configure more than one provider and use
them in "parallel".

.. note::

	You will have to make sure, that each provider has a unique name. In the example above
	the provider name is ``DefaultProvider``.

*Example: Configuration of two authentication providers*

.. code-block:: yaml

	security:
	  authentication:
	    providers:
	      MyLDAPProvider:
	        provider: TYPO3\MyCoolPackage\Security\Authentication\MyLDAPProvider
	        providerOptions: 'Some LDAP configuration options'
	      DefaultProvider:
	        provider: PersistedUsernamePasswordProvider

This will advice the authentication manager to first authenticate over the LDAP provider
and if that fails it will try to authenticate the default provider. So this configuration
can be seen as an authentication fallback chain, of course you can configure as many
providers as you like, but keep in mind that the order matters.

.. note::

	As you can see in the example, the LDAP provider is provided with some options. These
	are specific configuration options for each provider, have a look in the detailed
	description to know if a specific provider needs more options to be configured and
	which.

Multi-factor authentication strategy
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

There is another configuration option to realize a multi-factor-authentication. It
defaults to ``oneToken``. A configurable authentication strategy of ``allTokens`` forces
the authentication manager to always authenticate all configured providers and to make
sure that every single provider returned a positive authentication status to one of its
tokens. The authentication strategy ``atLeastOneToken`` will try to authenticate as many
tokens as possible but at least one. This is helpful to realize policies with additional
security only for some resources (e.g. SSL client certificates for an admin backend).

.. code-block:: yaml

	configuration:
	  security:
	    authentication:
	      authenticationStrategy: allTokens

Reuse of tokens and providers
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

There is another configuration option for authentication providers called ``tokenClass``,
which can be specified in the provider settings. By this option you can specify which
token should be used for a provider. Remember the token is responsible for the credentials
retrieval, i.e. if you want to authenticate let's say via username and password this setting
enables to to specify where these credentials come from. So e.g. you could reuse the one
username/password provider class and specify, whether authentication credentials are sent
in a POST request or set in an HTTP Basic authentication header.

*Example: Specifying a specific token type for an authentication provider*

.. code-block:: yaml

	security:
	  authentication:
	    providers:
	      DefaultProvider:
	        provider: PersistedUsernamePasswordProvider
	        tokenClass: UsernamePasswordHttpBasic

.. _Request Patterns:

Request Patterns
~~~~~~~~~~~~~~~~

Now that you know about the possibility of configuring more than one authentication
provider another scenario may come to your mind. Just imagine an application with two
areas: One user area and one administration area. Both must be protected, so we need some
kind of authentication. However for the administration area we want a stronger
authentication mechanism than for the user area. Have a look at the following provider
configuration:

*Example: Using request patterns*

.. code-block:: yaml

	security:
	  authentication:
	    providers:
	      MyLDAPProvider:
	        provider: TYPO3\MyCoolPackage\Security\Authentication\MyLDAPProvider
	        providerOptions: 'Some LDAP configuration options'
	        requestPatterns:
	         controllerObjectName: TYPO3\MyApplication\AdministrationArea\.*
	      DefaultProvider:
	        provider: PersistedUsernamePasswordProvider
	        requestPatterns:
	         controllerObjectName: TYPO3\MyApplication\UserArea\.*

Look at the new configuration option ``requestPatterns``. This enables or disables an
authentication provider, depending on given patterns. The patterns will look into the
data of the current request and tell the authentication system, if they match or not.
The patterns in the example above will match, if the controller object name of the current
request (the controller to be called) matches on the given regular expression. If a
pattern does not match, the corresponding provider will be ignored in the whole
authentication process. In the above scenario this means, all controllers responsible for
the administration area will use the LDAP authentication provider, the user area
controllers will be authenticated by the default username/password provider.

.. note::

	You can use more than one pattern in the configuration. Then the provider will only be
	active, if all patterns match on the current request.

.. tip::

	There can be patterns that match on different data of the request. Just imagine an IP
	pattern, that matches on the request IP. You could, e.g. provide different
	authentication mechanisms for people coming from your internal network, than for
	requests coming from the outside.

.. tip::

	You can easily implement your own pattern. Just implement the interface
	``TYPO3\FLOW3\Security\RequestPatternInterface`` and configure the pattern with its
	full qualified namespace.

:title:`Available request patterns`

+----------------------+------------------------+------------------------------------------+
+ Request Pattern      + Match criteria         + Configuration options                    +
+======================+========================+==========================================+
+ controllerObjectName + Matches on the object  + Expects one regular expression, to       +
+                      + name of the controller + match on the object name.                +
+                      + that has been resolved +                                          +
+                      + by the MVC dispatcher  + For example.:                            +
+                      + for the current .      +                                          +
+                      + request                + ``My\Application\AdministrationArea\.*`` +
+----------------------+------------------------+------------------------------------------+

Authentication entry points
---------------------------

One question that has not been answered so far is: what happens if the authentication
process fails? In this case the authentication manager will throw an
``AuthenticationRequired`` exception. It might not be the best idea to let this exception
settle its way up to the browser, right? Therefore we introduced a concept called
authentication entry points. These entry points catch the mentioned exception and should
redirect the user to a place where she can provide proper credentials. This could be a
login page for the username/password provider or an HTTP header for HTTP authentication. An
entry point can be configured for each authentication provider. Look at the following
example, that redirects to a login page (Using the ``WebRedirect`` entry point).

*Example: Redirect an ``AuthenticationRequired`` exception to the login page*

.. code-block:: yaml

	security:
	  authentication:
	    providers:
	      DefaultProvider:
	        provider: PersistedUsernamePasswordProvider
	        entryPoint: 'WebRedirect'
	        entryPointOptions:
	            uri: 'login/'

.. note::

	Of course you can implement your own entry point and configure it by using its full
	qualified class name. Just make sure to implement the
	``TYPO3\FLOW3\Security\Authentication\EntryPointInterface`` interface.

.. tip::

	If a request has been intercepted by an ``AuthenticationRequired`` exception, this
	request will be stored in the security context. By this, the authentication process
	can resume this request afterwards. Have a look at the FLOW3 authentication controller
	if you want to see this feature in action.

:title:`Available authentication entry points`

+--------------+---------------------------+---------------------------------------------+
+ Entry Point  + Description               + Configuration options                       +
+==============+===========================+=============================================+
+ WebRedirect  + Triggers an HTTP redirect + Expects an associative array with           +
+              + to a given uri.           + one entry.                                  +
+              + that has been resolved    +                                             +
+              + by the MVC dispatcher     + For example.:                               +
+              + for the current .         +                                             +
+              + request                   + ``uri: login/``                             +
+--------------+---------------------------+---------------------------------------------+
+ HttpBasic    + Adds a WWW-Authenticate   + Optionally takes an option realm, which     +
+              + header to the response,   + will be displayed in the authentication     +
+              + which will trigger the    + prompt.                                     +
+              + browsers authentication   +                                             +
+              + form.                     +                                             +
+--------------+---------------------------+---------------------------------------------+

.. _Authentication mechanisms shipped with FLOW3:

Authentication mechanisms shipped with FLOW3
--------------------------------------------

This section explains the details of each authentication mechanism shipped with FLOW3.
Mainly the configuration options and usage will be exposed, if you want to know more about
the entire authentication process and how the components will work together, please have a
look in the previous sections.

Simple username/password authentication
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

*Provider*

The implementation of the corresponding authentication provider resides in the class
``TYPO3\FLOW3\Security\Authentication\Provider\PersistedUsernamePasswordProvider``.
It is able to authenticate tokens of the type
``TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword``. It expects a credentials
array in the token which looks like that::

	array(
	  'username' => 'admin',
	  'password' => 'plaintextPassword'
	);

It will try to find an account in the ``TYPO3\FLOW3\Security\AccountRepository`` that has
the username value as account identifier and fetch the credentials source, which has to be
in the following format: ``HashOfThePassword,Salt``

.. tip::

	You should always use the FLOW3 hash service to generate hashes! This will make sure
	that you really have secure hashes.

The provider will explode the credentials source by the "," and try to authenticate the
token by asking the FLOW3 hash service to verify the hashed password against the given
plaintext password in from the token.
If you want to know more about accounts and how you can create them, look in the
corresponding section above.

* Token*

The username/password token is implemented in the class
``TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword``. It fetches the credentials
from the HTTP POST data, look at the following program listing for details::

	$postArguments = $this->environment->getRawPostArguments();
	$username = \TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($postArguments,
	    '__authentication.TYPO3.FLOW3.Security.Authentication.Token.UsernamePassword.username');
	$password = \TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($postArguments,
	    '__authentication.TYPO3.FLOW3.Security.Authentication.Token.UsernamePassword.password'');

.. note::

	The token expects a plaintext password in the POST data. That does not mean, you have
	to transfer plaintext passwords, however it is not the responsibility of the
	authentication layer to encrypt the transfer channel. Look in the section about
	:ref:`Channel security` for any details.

.. _Implementing your own authentication mechanism:

Implementing your own authentication mechanism
----------------------------------------------

One of the main goals for the authentication architecture was to provide an easily
extensible infrastructure. Now that the authentication process has been explained, you'll
here find the steps needed to implement your own authentication mechanism:

*Authentication token*

You'll have to provide an authentication token, that implements the interface
``TYPO3\FLOW3\Security\Authentication\TokenInterface``:

#. The most interesting method is ``updateCredentials()``. There you'll get the current
request and you'll have to make sure that credentials sent from the client will be
fetched and stored in the token.

#. Implement the remaining methods of the interface. These are  mostly getters and setters,
have a look in one of the existing  tokens (for example
``TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword``), if you need more
information.

*Authentication provider*

After that you'll have to implement your own authentication strategy by providing a class,
that implements the interface
``TYPO3\FLOW3\Security\Authentication\AuthenticationProviderInterface``:

#. In the constructor you will get the name, that has been configured for the provider and
an optional options array. Basically you can decide on your own which options you need
and how the corresponding yaml configuration will look like.

#. Then there has to be a ``canAuthenticate()`` method, which gets an authentication token
and returns a boolean value whether your provider can authenticate that token or not.
Most likely you will call ``getAuthenticationProviderName()`` on the token and check,
if it matches the provider name given to you in your provider's constructor. In
addition to this, the method ``getTokenClassNames()`` has to return an array with all
authentication token classes, your provider is able to authenticate.

#. All the magic will happen in the ``authenticate()`` method, which will get an appropriate
authentication token. Basically you could do whatever you want in this method, the
only thing you'll have to make sure is to set the correct status (possible values are
defined as constants in the token interface and explained above). If authentication
succeeds you might also want to set an account in the given token, to add some roles
to the current security context. However, here is the recommended way of what should
be done in this method and if you don't have really good reasons, you shouldn't
deviate from this procedure.

  #. Get the credentials provided by the client from the authentication token
     (``getCredentials()``)

  #. Retrieve the corresponding account object from the account repository, which
     you should inject into your provider by dependency injection. The repository
     provides a convenient find method for this task:
     ``findActiveByAccountIdentifierAndAuthenticationProviderName()``.

  #. The ``credentialsSource`` property of the account will hold the credentials
     you'll need to compare or at least the information, where these credentials lie.

  #. Start the authentication process (e.g. compare credentials/call directory service/...).

  #. Depending on the authentication result, set the correct status in the
     authentication token, by ``calling setAuthenticationStatus()``.

  #. Set the account in the authentication token, if authentication succeeded. This
     will add the roles of this token to the security context.

Authorization
=============

In this section we will deal with the authorization features of FLOW3. You won't find any
advices, how to configure access rights here, please refer to the next section about
:ref:`Access Control Lists`, which form the default method to model and configure access
rules.

Authorize method invocations
----------------------------

The most general thing, which you want to protect in every
application is the invocation of certain methods. By controlling, which
methods are allowed to be called and which not, it can be globally
ensured, that no unprivileged action will be executed at any time. This
is what you would usually do, by adding an access check at the beginning
of your privileged method. In FLOW3, there is the opportunity to enforce
these checks without touching the actual method at all. Of course
FLOW3's AOP features are used to realize this completely new perspective
on authorization. If you want to learn more about AOP, please refer to
the corresponding chapter in this reference.

First, let's have a look at the following sequence diagram to get an overview of what is
happening when an authorization decision is formed and enforced:

.. figure:: /Images/TheDefinitiveGuide/PartIII/Security_BasicAuthorizationProcess.png
	:align: center
	:width: 400pt
	:alt: How an authorization decision is formed and enforced in FLOW3

	How an authorization decision is formed and enforced in FLOW3

As already said, the whole authorization starts with an intercepted method, or in other
words with a method that should be protected and only be called by privileged users. In
the chapter about AOP you've already read, that every method interception is implemented
in a so called advice, which resides in an aspect class. Here we are: the
``TYPO3\FLOW3\Security\Aspect\PolicyEnforcementAspect``. Inside this aspect there is the
``enforcePolicy()`` advice, which hands over to FLOW3's authorization components.

The next thing to be called is a security interceptor. This interceptor calls the
authentication manager before it continues with the authorization process, to make sure
that the authentication status is up to date. Then an access decision manager is called,
which has to decide, if it is allowed to call the intercepted method. If not it throws an
access denied exception. If you want, you could implement your own access decision manager.
However, there is a very flexible one shipped with FLOW3
(``TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterManager``), which uses the
following voting process to meet its decision:

#. Check for registered access decision voters.

#. Ask every voter, to vote for the given method call (or join point in AOP nomenclature).

#. Count the votes and grant access, if there is at least one ``VOTE_GRANT`` vote and no
   ``VOTE_DENY`` vote. In all other cases an access denied exception will be thrown.

*On access decision voters*

As you have seen, the default way of deciding on access is done by voting. This makes the
whole authorization process very flexible and very easily extensible. You can at any time
write your own voter classes and register them, just make sure to implement the interface
``TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface``. Then you have to
register your custom voter as shown below:

.. code-block:: yaml

	security:
	  authorization:
	    accessDecisionVoters: [TYPO3\FLOW3\Security\Authorization\Voter\Policy, MyCompany\MyPackage\Security\MyCustomVoter]

.. note::

	By default there is always one voter registered:
	``TYPO3\FLOW3\Security\Authorization\Voter\Policy``. This voter connects the
	authorization system to the policy component, by returning a vote depending on the
	configured security policy. Read the section about Policies, to learn more about the
	default policy handling in FLOW3.

If asked, each voter has to return one of the three possibles votes: grant, deny or
abstain. There are appropriate constants defined in the voter interface, which you should
use for that. You might imagine that a voter has to return an abstain vote, if it is not
able to give a proper grant or deny vote.

Now it could be the case that all registered voters abstain. Usually the access decision
manager will deny access then. However, you can change that behavior by configuring the
following option:

.. code-block:: yaml

	security:
	  authorization:
	    allowAccessIfAllVotersAbstain: FALSE

Request Integrity (HMAC)
------------------------

(FIXME)

* selection of form fields and the objects / properties which should be allowed or
  not be allowed to being modified must manipulable
* HMAC is a hash which can assure that only those form fields were submitted which
  were intended - additional fields would be detected
* HMAC is generated automatically and added as a query parameter to the form action
  URI
* Link to Property Mapping: "The Common Case: Fluid Forms"

Application firewall
--------------------

Besides the AOP powered authorization, there is another line of defense: the filter
firewall. This firewall is triggered directly when a request arrives at the MVC dispatcher.
After that the request is analyzed and can be blocked/filtered out. This adds a second
level of security right at the beginning of the whole framework run, which means
that a minimal amount of potentially insecure code will be executed before that.

.. figure:: /Images/TheDefinitiveGuide/PartIII/Security_FilterFirewall.png
	:align: center
	:width: 400pt
	:alt: Blocking request with FLOW3's filter firewall

	Blocking request with FLOW3's filter firewall

The firewall itself is added to the MVC dispatcher by AOP, to completely decouple security
from the MVC framework and to have the possibility of disabling security. Blocking
requests with the firewall is not a big thing at all, basically a request filter object is
called, which consists of a request pattern and a security interceptor. The simple rules
is: if the pattern matches on the request, the interceptor is invoked.
:ref:`Request Patterns` are also used by the authentication components and are explained
in detail there. Talking about security interceptors: you already know the policy
enforcement interceptor, which triggers the authorization process. Here is a table of
available interceptors, shipped with FLOW3:

.. note::

	Of course you can implement your own interceptor. Just make sure to implement the
	interface: ``TYPO3\FLOW3\Security\Authorization\InterceptorInterface``.

:title:`FLOW3's built-in security interceptors`

+-----------------------+---------------------------------------+
+ Security interceptor  + Invocation action                     +
+=======================+=======================================+
+ PolicyEnforcement     + Triggers the authorization process as +
+                       + described one section above.          +
+-----------------------+---------------------------------------+
+ RequireAuthentication + Calls the authentication manager to   +
+                       + authenticate all active tokens for    +
+                       + the current request.                  +
+-----------------------+---------------------------------------+

Of course you are able to configure as many request filters as
you like. Have a look at the following example to get an idea how a
firewall configuration will look like:

*Example: Firewall configuration in the Settings.yaml file*

.. code-block:: yaml

	TYPO3:
	  FLOW3:
	    security:
	      firewall:
	        rejectAll: FALSE

	        filters:
	          -
	            patternType:  'URI'
	            patternValue: '/some/url/.*'
	            interceptor:  'AccessGrant'
	          -
	            patternType:  'URI'
	            patternValue: '/some/url/blocked.*'
	            interceptor:  'AccessDeny'
	          -
	            patternType:  'MyCompany\MyPackage\Security\MyOwnRequestPattern'
	            patternValue: 'some pattern value'
	            interceptor:  'MyCompany\MyPackage\Security\MyOwnSecurityInterceptor'

As you can see, you can easily use your own implementations for request patterns and
security interceptors.

.. note::

	You might have noticed the ``rejectAll`` option. If this is set to ``yes``,
	only request which are explicitly allowed by a request filter will be able
	to pass the firewall.

.. _Access Control Lists:

Policies aka Access Control Lists (ACLs)
========================================

This section will introduce the recommended and default way of connecting authentication
with authorization. The special and really powerful part of FLOW3's way is the possibility
to do that completely declarative. This gives you the possibility to change the security
policy of your application without touching any PHP code. The policy system deals with
three major objects, which are explained below: roles, resources and acl entries. All
policy definitions are configured in the ``Policy.yaml`` files.

*Roles*

In the section about authentication so called roles were introduced. A role can be
attached to a user's security context, to determine which privileges should be granted to
her. I.e. the access rights of a user are decoupled from the user object itself, making it
a lot more flexible, if you want to change them. In FLOW3 a role is mainly just a string,
which must be unique in the whole FLOW3 instance. Following there is an example
configuration, that will proclaim the roles ``Administrator``, ``Customer``, and
``PrivilegedCustomer`` to the system.

*Example: roles definition in the Policy.yaml file*

.. code-block:: yaml

	roles:
	  Administrator: []
	  Customer: []
	  PrivilegedCustomer: [Customer]

The role ``PrivilegedCustomer`` is configured as a sub role of ``Customer``, for
example it will inherit the privileges from the ``Customer`` role.

FLOW3 will always add the magic ``Everybody`` role, which you don't have to
configure yourself. This role will also be present, if no account is authenticated.

Likewise, the magic role ``Anonymous`` is added to the security context if a user
is not authenticated.

*Resources*

The counterpart to roles are resources. A resource in general is an object, you want to
protect, for example you want to configure which roles are allowed to access a certain
resource. The policy configuration deals with method and entity resources.

Entity resources are related to content security, which are explained in the
:ref:`Content security` section below. In this section we will deal with method
resources only.

*Example: resources definition in the Policy.yaml file*

.. code-block:: yaml

	resources:
	  methods:
	    listMethods: 'method(TYPO3\FooPackage\SomeClass->list.*())'
	    updateMethods: 'method(TYPO3\FooPackage\SomeClass->update.*())'
	    deleteMethods: 'method(TYPO3\FooPackage\.*->delete.*(force == TRUE))'
	    modifyMethods: 'TYPO3_FooPackage_update || TYPO3_FooPackage_delete'

Each resource is defined by a unique name [#]_ and a so called pointcut expression.
Practically a pointcut expression is a regular expression that matches on certain methods.
There are more pointcut expressions you can use to describe the methods addressed by a
specific resource, the whole syntax is described in detail in the chapter about AOP.

.. tip:

	To make your resource definitions better readable you can cascade them by connecting
	two or more via logical operators. In the above example this is shown in the
	configuration of the third resource. Again the details about combined pointcuts are
	described in the AOP reference.

*ACL entries*

The last step is to connect resources with roles by assigning access privileges. Let's
have a look at an example for such ACL entries:

*Example: ACL entry definitions in the Policy.yaml file*

.. code-block:: yaml

	acls:
	  Administrator:
	    methods:
	      listMethods:         GRANT
	      updateMethods:       GRANT
	      deleteMethods:       GRANT
	  Customer:
	    methods:
	      listMethods:         GRANT
	  PrivilegedCustomer:
	    methods:
	      updateMethods:       GRANT
	      deleteMethods:       DENY

This will end up in ``Administrators`` being able to call all ``update*`` and ``list*``
methods in the class ``SomeClass`` and all ``delete*`` methods no matter which class in
the whole package ``FooPackage``. However, ``Customers`` are only able to call the ``list*``
methods, while ``PrivilegedCustomers`` are also allowed to call the ``update*`` methods.
And all this without touching one line of PHP code, isn't that convenient?

*Privilege evaluation*

Privilege evaluation is a really complex task, when you think carefully about it. However,
if you remember the following two rules, you will have no problems or unexpected behaviour
when writing your policies:

1. If a DENY privilege is configured for one of the user's roles, access will be denied
	no matter how many grant privileges there are in other roles.

2. If no privilege has been defined for any of the user's roles, access will be denied.

*Runtime constraints*

Runtime constraints are a very powerful feature of FLOW3's AOP framework. A full reference
of the possibilities can be found in the AOP chapter of this documentation. However, this
features was mainly implemented to support sophisticated policy definitions and therefore
here is a short introduction by two simple examples on how to use it:

*Example: runtime constraints usage in the security policy*

.. code-block:: yaml

	-
	  resources:
	    methods:
	      TYPO3_FooPackage_firstResource: 'method(TYPO3\FooPackage\SomeClass->updateProject(title != "FLOW3"))'
	      TYPO3_FooPackage_secondResource: TYPO3_FooPackage_firstResource && evaluate(current.securityContext.party.name == "Andi")

The above configuration defines a resource that matches on the ``updateProject`` method
only if it is not called with the ``title`` argument equal to "FLOW3". The second resource
matches if the first one matches and the ``name`` property of the currently authenticated
``party`` is equal to "Andi".

.. _Content security:

Content security
================

Security for persisted objects
------------------------------

.. warning::

	**This section is not complete yet!**

	* TODO: Explain query rewriting via aspect to the persistence layer
	* NOTE: Content security not working for DQL queries currently (only QOM!)

.. code-block:: yaml

	resources:
	  entities:
	    Acme_MyPackage_Domain_Model_Customer:
	      Acme_MyPackage_Customers_All: 'ANY'
	      Acme_MyPackage_Customers_Vip: 'this.vip == TRUE'
	      Acme_MyPackage_Customers_Me: 'current.securityContext.account == this.account && this.account != NULL'

The ``Acme_MyPackage_Customer_All`` resource will match any customer object.
The ``Acme_MyPackage_Customer_Vip`` resource matches all customer's which have their
``vip`` attribute set.
The ``Acme_MyPackage_Customer_Me`` resource matches any customer object whose account
property matches the currently logged in account.

* if an entity resource is defined, access is denied automatically to all who don't
  have access granted to that new resource explicitly defined in the ACLs.
* if there is no ``ANY`` resource defined, only objects explicitly matched by one of
  the other resources are denied by default.
* if there is a ``ANY`` resource define, all objects of this type will be denied for
  all users not have a grant privilege for this ``ANY`` resource.
* The key ``Acme_MyPackage_Domain_Model_Customer`` has to reflect the full qualified
  class name of your entity, while '\' is replaced by '_' due to YAML syntax
  constraints.
* The ``DENY`` privilege works the same as for methods. If it is set for one of the
  resources you will never see entities matched by this resource, no matter how many
  ``GRANT`` privileges there might be set for other roles you also have.


Security for files aka secure downloads
---------------------------------------

* add publishing configuration to resource objects
* publishing in subfolder named like session id
* optimization with role subdirs -> only publish once for a role
* server specific restriction publishing like .htaccess files for apache

Fluid (view) integration
========================

Now that the policy is technically enforced, these rules should also be reflected in the
view. E.g. a button or link to delete a customer should not be shown, if the user has not
the privilege to do so. If you are using the recommended Fluid templating engine, you can
simply use the security view helpers shipped with Fluid. Otherwise you would have to ask
the policy service (``TYPO3\FLOW3\Security\Policy\PolicyService``) for the current
privilege situation and implement the view logic on your own, however this seems not to be
the best idea one can have. Below you'll find a short description of the available Fluid
view helpers.

``ifAccess`` view helper
------------------------

This view helper implements an ifAccess/else condition, have a look at the following
example, which should be more or less self-explanatory:

*Example: the ifAccess view helper*

.. code-block:: xml

	<f:security.ifAccess resource="someResource">
		This is being shown in case you have access to the given resource
	</f:security.ifAccess>

	<f:security.ifAccess resource="someResource">
		<f:then>
			This is being shown in case you have access.
		</f:then>
		<f:else>
			This is being displayed in case you do not have access.
		</f:else>
	</f:security.ifAccess>

As you can imagine, the main advantage is, that the view will automatically reflect the
configured policy rules, without the need of changing any template code.

``ifHasRole`` view helper
-------------------------

This view helper is pretty similar to the ``ifAccess`` view helper, however it does not
check the access privilege for a given resource, but the availability of a certain role.
For example you could check, if the current user has the ``Administrator`` role assigned:

*Example: the ifHasRole view helper*

.. code-block:: xml

	<f:security.ifHasRole role="Administrator">
		This is being shown in case you have the Administrator role (aka role).
	</f:security.ifHasRole>

	<f:security.ifHasRole role="Administrator">
		<f:then>
			This is being shown in case you have the role.
		</f:then>
		<f:else>
			This is being displayed in case you do not have the role.
		</f:else>
	</f:security.ifHasRole>

.. _Channel security:

Channel security
================

Currently channel security is an open task. Stay tuned for great features!

.. _Cryptography:

Cryptography
============

Hash service
------------

* hashing/verifying hashes
* special hashing strategies/algorithms
* random number generation

RSA wallet service
------------------

* CLI commands to save keys
* encrypting/decrypting/verifying signatures

.. _http://www.oasis-open.org/committees/tc_home.php?wg_abbrev=ciq:  http://www.oasis-open.org/committees/tc_home.php?wg_abbrev=ciq

-----

.. [#] The details about the ``PersistedUsernamePasswordProvider`` provider are explained
	below, in the section about :ref:`Authentication mechanisms shipped with FLOW3`.

.. [#] If you don't know any credentials, you'll have to read the section about
	:ref:`Account management`

.. [#] Well, it holds them in member variables, but lies itself in the security context,
	which is a class configured as scope session.

.. [#] The specification can be downloaded from
	`http://www.oasis-open.org/committees/tc_home.php?wg_abbrev=ciq`_. The implementation of
	this specification resides in the "Party" package, which is part of the official FLOW3
	distribution.

.. [#] The ``AccountRepository`` provides a convenient find method called
	``findActiveByAccountIdentifierAndAuthenticationProviderName()``
	for this task.

.. [#] As a convention you have to prefix at least your package's namespace to avoid ambiguity.
