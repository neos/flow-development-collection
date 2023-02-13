﻿================
Session Handling
================

Flow has excellent support for working with sessions.

This chapter will explain:

* ... how to store specific data in a session
* ... how to store objects in the session
* ... how to delete sessions

Scope Session
=============

Flow does not only support the ``prototype`` and ``singleton`` object scopes, but also the
object scope ``session``. Objects marked like this basically behave like ``singleton`` objects
which are automatically serialized into the user's session.

As an example, when building a shopping basket, the class could look as follows::

	/**
	 * @Flow\Scope("session")
	 */
	class ShoppingBasket {

		/**
		 * @var array
		 */
		protected $items = array();

		/**
		 * @param string $item
		 * @return void
		 * @Flow\Session(autoStart = true)
		 */
		public function addItem($item) {
			$this->items[] = $item;
		}

		/**
		 * @return array
		 */
		public function getItems() {
			return $this->items;
		}
	}

In the above example

* the object scope is set to ``session``, so it behaves like a *user-bound cross-request
  singleton*. This ``ShoppingBasket`` can now be injected where it is needed using *Dependency
  Injection*.
* We only want to start a session when the first element is added to the shopping basket.
  For this the addItem() method needs to be annotated with ``@Flow\Session(autoStart = true)``.

When a user browses the website, the following then happens:

* First, the user's shopping basket is empty, and getItems() returns an empty array.
  No session exists yet. For each page being requested, the ``ShoppingBasket`` is
  newly initialized.

* As soon as the user adds something to the shopping basket, ``addItem()`` is called.
  Because this is annotated with ``@Flow\Session(autoStart = true)``, a new PHP session
  is started, and the ShoppingBasket is placed into the session.

* As the user continues to browse the website, the ``ShoppingBasket`` is being fetched
  from the user's session (which exists now). Thus, ``getItems()`` returns the items
  from the session.


.. admonition:: Why is ``@Flow\Session(autoStart = true)`` necessary?

	If Flow did not have this annotation, there would be no way for it to determine
	when a session must be started. Thus, every user browsing the website would
	*always* need a session as soon as an object of scope ``session`` is accessed.
	This would happens if the ``session``-scoped object is still in its initial state.

	To be able to use proxies such as Varnish, Flow defers the creation of a
	session to a point in time when it is really needed -- and the developer needs
	to tell the framework when that point is reached using the above annotation.


The Flow session scope handles persistent objects and dependency injection correctly:

* Objects which are injected via Dependency Injection are removed before serialization
  and re-injected on deserialization.

* Persistent objects which are unchanged are just stored as a reference and fetched
  from persistence again on deserialization.

* Persistent objects which are modified are fully stored in the session.

Low-level session handling
==========================

It is possible to inject the ``Neos\Flow\Session\SessionInterface`` and interact
with the session on a low level, by using ``start()``, ``getData()`` and ``putData()``.

That should rarely be needed, though. Instead of manually serializing objects object into
the session, the *session scope* should be used whenever possible.

Session Backends
================

The session implementation of Flow is written in pure PHP and uses the caching
framework as its storage. This allows for storing session data in a variety of
backends, including PDO databases, APC and Redis.

The preferred storage backend for the built-in session is defined through a custom
Caches.yaml file, placed in a package or the global configuration directory:

.. code-block:: yaml

	Flow_Session_Storage:
	  backend: Neos\Cache\Backend\RedisBackend

The built-in session implementation provides a few more configuration options, related to
the session cookie and the automatic garbage collection. Please refer to the
Settings.yaml file of the Flow package for a list of all possible options and
their respective documentation.

Session Storage
===============

.. note::

	Since Flow 5.2 Sessions are no longer destroyed by default when caches are flushed. This is
	due to the session caches being marked as "persistent". This previously lead to all sessions
	being destroyed on each deployment, which was undesireable.

If you deploy changes that change the structure of data that is stored in the session or the class
of an `@Flow\scope("session")` object, you need to manually destroy sessions to avoid deserialization
errors. You can do this by running `./flow flow:session:destroyAll` or manually deleting the folder
where the sessions are stored.

Also, sessions are shared among the application contexts, e.g. `Development` and `Production`, so if
your use case requires to have sessions separated for different contexts, you need to configure the
`cacheDirectory` backend option for the `Flow_Session_Storage` and `Flow_Session_MetaData` caches for
each individual context. Please refer to the :doc:`Caching` section of this guide for further information.
