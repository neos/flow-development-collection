.. _ch-caching:

===============
Cache Framework
===============

.. sectionauthor:: Robert Lemke <robert@typo3.org>

TYPO3 Flow offers a caching framework to cache data. The system offers a wide variety of
options and storage solutions for different caching needs. Each cache can be configured
individually and can implement its own specific storage strategy.

If configured correctly the caching framework can help to speed up installations,
especially in heavy load scenarios. This can be done by moving all caches to a dedicated
cache server with specialized cache systems like the Redis key-value store (a.k.a. NoSQL
database), or shrinking the needed storage space by enabling compression of data.

Introduction
============

The caching framework can handle multiple caches with different configurations. A single
cache consists of any number of cache entries. A single cache entry is defined by these
parts:

identifier
	A string as unique identifier within this cache. Used to store and retrieve entries.

data
	The data to be cached.

lifetime
	A lifetime in seconds of this cache entry. The entry can not be retrieved from cache
	if lifetime expired.

tags
	Additional tags (an array of strings) assigned to the entry. Used to remove specific
	cache entries.

The difference between identifier and tags is hard to understand at first glance, it is
illustrated with an example.

About the Identifier
--------------------

The identifier used to store ("set") and retrieve ("get") entries from the cache holds all
information to differentiate entries from each other. For performance reasons, it should
be quick to calculate. Suppose there is an resource-intensive extension added as a plugin
on two different pages. The calculated content depends on the page on which it is inserted
and if a user is logged in or not.
So, the plugin creates at maximum four different content outputs, which can be cached in
four different cache entries:

* page 1, no user logged in
* page 1, a user is logged in
* page 2, no user logged in
* page 2, a user is logged in

To differentiate all entries from each other, the identifier is build from the page id
where the plugin is located, combined with the information whether a user is logged in.
These are concatenated and hashed (with ``sha1()``, for example). In PHP this could look
like this: ::

	$identifier = sha1((string)$this->getName() . (string)$this->isUserLoggedIn());

When the plugin is accessed, the identifier is calculated early in the program flow. Next,
the plugin looks up for a cache entry with this identifier. If there is such an entry, the
plugin can return the cached content, else it calculates the content and stores a new
cache entry with this identifier. In general the identifier is constructed from all
dependencies which specify an unique set of data. The identifier should be based on
information which already exist in the system at the point of its calculation. In the
above scenario the page id and whether or not a user is logged in are already determined
during the frontend bootstrap and can be retrieved from the system quickly.

About Tags
----------

Tags are used to drop specific cache entries if the information an entry is constructed
from changes. Suppose the above plugin displays content based on different news entries.
If one news entry is changed in the backend, all cache entries which are compiled from
this news row must be dropped to ensure that the frontend renders the plugin content again
and does not deliver old content on the next frontend call. If for example the plugin uses
news number one and two on one page, and news one on another page, the according cache
entries should be tagged with these tags:

* page 1, tags news_1, news_2
* page 2, tag news_1

If entry two is changed, a simple backend logic could be created, which drops all cache
entries tagged with "news_2", in this case the first entry would be invalidated while the
second entry still exists in the cache after the operation. While there is always exactly
one identifier for each cache entry, an arbitrary number of tags can be assigned to an
entry and one specific tag can be assigned to mulitple cache entries. All tags a cache
entry has are given to the cache when the entry is stored (set).

System Architecture
-------------------

The caching framework architecture is based on these classes:

``TYPO3\Flow\Cache\CacheFactory``
	Factory class to instantiate caches.

``TYPO3\Flow\Cache\CacheManager``
	Returns the cache frontend of a specific cache. Implements methods to handle cache
	instances.

``TYPO3\Flow\Cache\Frontend\FrontendInterface``
	Interface to handle cache entries of a specific cache. Different frontends exist to
	handle different data types.

``TYPO3\Flow\Cache\Backend\BackendInterface``
	Interface for different storage strategies. A set of implementations exist with
	different characteristics.

In your code you usually rely on dependency injection to have your caches injected.
Thus you deal mainly with the API defined in the ``FrontendInterface``.

Configuration
=============

The cache framework is configured in the usual TYPO3 Flow way through YAML files. The most
important is *Caches.yaml*, although you may of course use *Objects.yaml* to further
configure the way your caches are used. Caches are given a (unique) name and have three
keys in their configuration:

frontend
	The frontend to use for the cache.

backend
	The backend to use for the cache.

backendOptions
	The backend options to use.

As an example for such a configuration take a look at the default that is inherited for
any cache unless overridden:

*Example: Default cache settings* ::

	##
	# Default cache configuration
	#
	# If no frontend, backend or options are specified for a cache, these values
	# will be taken to create the cache.
	Default:
	  frontend: TYPO3\Flow\Cache\Frontend\VariableFrontend
	  backend: TYPO3\Flow\Cache\Backend\FileBackend
	  backendOptions:
	    defaultLifetime: 0

Some backends have mandatory as well as optional parameters (which are documented below).
If not all mandatory options are defined, the backend will throw an exception on the first
access. To override options for a cache, simply set them in *Caches.yaml* in your global
or package *Configuration* directory.

*Example: Configuration to use RedisBackend for FooCache* ::

	FooCache:
	  backend: TYPO3\Flow\Cache\Backend\RedisBackend
	  backendOptions:
	    database: 3

Cache Frontends
===============

Frontend API
------------

All frontends must implement the API defined in the interface
``TYPO3\Flow\Cache\Frontend\FrontendInterface``. All cache operations must be done
with these methods.

``getIdentifier()``
	Returns the cache identifier.

``getBackend()``
	Returns the backend instance of this cache. It is seldom needed in usual code.

``set()``
	Sets/overwrites an entry in the cache.

``get()``
	Return the cache entry for the given identifier.

``getByTag()``
	Finds and returns all cache entries which are tagged by the specified tag.

``has()``
	Check for existence of a cache entry.

``remove()``
	Remove the entry for the given identifier from the cache.

``flush()``
	Removes all cache entries of this cache.

``flushByTag()``
	Flush all cache entries which are tagged with the given tag.

``collectGarbage()``
	Call the garbage collection method of the backend. This is important for backends
	which are unable to do this internally.

``isValidIdentifier()``
	Checks if a given identifier is valid.

``isValidTag()``
	Checks if a given tag is valid.

Check the API documentation for details on these methods.

Available Frontends
-------------------

Currently three different frontends are implemented, the main difference is the data types
which can be stored using a specific frontend.

``TYPO3\Flow\Cache\Frontend\StringFrontend``
	The string frontend accepts strings as data to be cached.

``TYPO3\Flow\Cache\Frontend\VariableFrontend``
	Strings, arrays and objects are accepted by this frontend. Data is serialized before
	it is given to the backend. The igbinary serializer is used transparently (if
	available in the system) which speeds up the serialization and unserialization and
	reduces data size. The variable frontend is the most frequently used frontend and
	handles the widest range of data types. While it can also handle string data, the
	string frontend should be used in this case to avoid the additional serialization done
	by the variable frontend.

``TYPO3\Flow\Cache\Frontend\PhpFrontend``
	This is a special frontend to cache PHP files. It extends the string frontend with the
	method ``requireOnce()`` and allows PHP files to be ``require()``'d if a cache entry
	exists.

	This can be used to cache and speed up loading of calculated PHP code and becomes handy
	if a lot of reflection and dynamic PHP class construction is done. A backend to be used
	with the PHP frontend must implement the

``TYPO3\Flow\Cache\Backend\PhpCapableBackendInterface``
	Currently the file backend is the only backend which fulfills this requirement.

.. note::
	The PHP frontend can only be used to cache PHP files, it does not work with strings,
	arrays or objects.

Cache Backends
==============

Currently already a number of different storage backends exists. They have different
characteristics and can be used for different caching needs. The best backend depends on
given server setup and hardware, as well as cache type and usage. A backend should be
chosen wisely, a wrong decision could slow down an installation in the end.

Common Options
--------------

:title:`Common cache backend options`

+-----------------+--------------------------------------+-----------+---------+---------+
| Options         | Description                          | Mandatory | Type    | Default |
+=================+======================================+===========+=========+=========+
| defaultLifeTime | Default lifetime in seconds of a     | No        | integer | 3600    |
|                 | cache entry if it is                 |           |         |         |
|                 | not specified for a specific entry   |           |         |         |
|                 | on set()                             |           |         |         |
+-----------------+--------------------------------------+-----------+---------+---------+

TYPO3\\Flow\\Cache\\Backend\\FileBackend
----------------------------------------

The file backend stores every cache entry as a single file to the file system. The
lifetime and tags are added after the data part in the same file.

As main advantage the file backend is the only backend which implements the
``PhpCapableInterface`` and can be used in combination with the ``PhpFrontend``. The
backend was specifically adapted to these needs and has low overhead for get and set
operations, it scales very well with the number of entries for those operations. This
mostly depends on the file lookup performance of the underlying file system in large
directories, and most modern file systems use B-trees which can easily handle millions of
files without much performance impact.

A disadvantage is that the performance of ``flushByTag()`` is bad and scales just O(n).
This basically means that with twice the number of entries the file backend needs double
time to flush entries which are tagged with a given tag.
This practically renders the file backend unusable for content caches. The reason for this
design decision in TYPO3 Flow is that the file backend is mainly used as AOP cache, where
``flushByTag()`` is only used if a PHP file changes. This happens very seldom on
production systems, so get and set performance is much more important in this scenario.

.. note::

	Under heavy load the maximum ``set()`` performance depends on the maximum write and
	seek performance of the hard disk. If for example the server system shows lots of I/O
	wait in top, the file backend has reached this bound. A different storage strategy
	like RAM disks, battery backed up RAID systems or SSD hard disks might help then.

Options
~~~~~~~

The file backend has no options.

TYPO3\\Flow\\Cache\\Backend\\PdoBackend
---------------------------------------

The PDO backend can be used as a native PDO interface to databases which are connected to
PHP via PDO. The garbage collection is implemented for this backend and should be called
to clean up hard disk space or memory.

.. note::

	There is currently very little production experience with this  backend, especially
	not with a capable database like Oracle. We appreciate any feedback for real life use
	cases of this cache.

Options
~~~~~~~

:title:`Pdo cache backend options`

+----------------+----------------------------------------+-----------+--------+---------+
| Option         | Description                            | Mandatory | Type   | Default |
+================+========================================+===========+========+=========+
| dataSourceName | Data source name for connecting to the | Yes       | string |         |
|                | database.                              |           |        |         |
|                |                                        |           |        |         |
|                | :title:`Examples:`                     |           |        |         |
|                |                                        |           |        |         |
|                | * mysql:host=localhost;dbname=test     |           |        |         |
|                | * sqlite:/path/to/sqlite.db            |           |        |         |
|                | * sqlite::memory:                      |           |        |         |
+----------------+----------------------------------------+-----------+--------+---------+
| username       | Username to use for the database       | No        |        |         |
|                | connection                             |           |        |         |
+----------------+----------------------------------------+-----------+--------+---------+
| password       | Password to use for the database       | No        |        |         |
|                | connection                             |           |        |         |
+----------------+----------------------------------------+-----------+--------+---------+

TYPO3\\Flow\\Cache\\Backend\\RedisBackend
-----------------------------------------

`Redis`_ is a key-value storage/database. In contrast to memcached, it allows structured
values.Data is stored in RAM but it allows persistence to disk and doesn't suffer from the
design problems which exist with the memcached backend implementation. The redis backend
can be used as an alternative of the database backend for big cache tables and helps to
reduce load on database servers this way. The implementation can handle millions of cache
entries each with hundreds of tags if the underlying server has enough memory.

Redis is known to be extremely fast but very memory hungry. The implementation is an
option for big caches with lots of data because most important operations perform O(1) in
proportion to the number of keys. This basically means that the access to an entry in a
cache with a million entries is not slower than to a cache with only 10 entries, at least
if there is enough memory available to hold the complete set in memory. At the moment only
one redis server can be used at a time per cache, but one redis instance can handle
multiple caches without performance loss when flushing a single cache.

The garbage collection task should be run once in a while to find and delete old tags.

The implementation is based on the `phpredis`_ module, which must be available on the
system. It is recommended to build this from the git repository. Currently redis version
2.2 is recommended.

.. note::

	It is important to monitor the redis server and tune its settings to the specific
	caching needs and hardware capabilities. There are several articles on the net and the
	redis configuration file contains some important hints on how to speed up the system
	if it reaches bounds. A full documentation of available options is far beyond this
	documentation.

.. warning::

	The redis implementation is pretty young and should be considered as experimental. The
	redis project itself has a very high development speed and it might happen that the
	TYPO3 Flow implementation changes to adapt to new versions.

Options
~~~~~~~

:title:`Redis cache backend options`

+------------------+---------------------------------+-----------+-----------+-----------+
| Option           | Description                     | Mandatory | Type      | Default   |
+==================+=================================+===========+===========+===========+
| host             | IP address or name of redis     | No        | string    | 127.0.0.1 |
|                  | server to connect to            |           |           |           |
+------------------+---------------------------------+-----------+-----------+-----------+
| port             | Port of the Redis server.       | Yes       | integer   | 6379      |
+------------------+---------------------------------+-----------+-----------+-----------+
| database         | Number of the database to store | No        | integer   | 0         |
|                  | entries. Each cache should use  |           |           |           |
|                  | its own database, otherwise all |           |           |           |
|                  | caches sharing a database are   |           |           |           |
|                  | flushed if the flush operation  |           |           |           |
|                  | is issued to one of them.       |           |           |           |
|                  | Database numbers 0 and 1 are    |           |           |           |
|                  | used and flushed by the core    |           |           |           |
|                  | unit tests and should not be    |           |           |           |
|                  | used if possible.               |           |           |           |
+------------------+---------------------------------+-----------+-----------+-----------+
| password         | Password used to connect to the | No        | string    |           |
|                  | redis instance if the redis     |           |           |           |
|                  | server needs authentication.    |           |           |           |
|                  | Warning: The password is sent   |           |           |           |
|                  | to the redis server in plain    |           |           |           |
|                  | text.                           |           |           |           |
+------------------+---------------------------------+-----------+-----------+-----------+
| compressionLevel | Set gzip compression level to a | No        | integer   | -1        |
|                  | specific value. The default     |           | (-1 to 9) |           |
|                  | compression level is usually    |           |           |           |
|                  | sufficient.                     |           |           |           |
+------------------+---------------------------------+-----------+-----------+-----------+

TYPO3\\Flow\\Cache\\Backend\\MemcachedBackend
---------------------------------------------

`Memcached`_ is a simple key/value RAM database which scales across multiple servers. To
use this backend, at least one memcache daemon must be reachable, and the PHP module
memcache must be loaded. There are two PHP memcache implementations: memcache and
memcached, only memcache is currently supported by this backend.

Warning and Design Constraints
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Memcached is by design a simple key-value store. Values must be strings and there is no
relation between keys. Since the caching framework needs to put some structure in it to
store the identifier-data-tags relations, it stores, for each cache entry, an
identifier-to-data, an identifier-to-tags and a tag-to-identifiers entry.

This leads to structural problems:

* If memcache runs out of memory but must store new entries, it will toss *some* other
	entry out of the cache (this is called an eviction in memcached speak).
* If data is shared over multiple memcache servers and some server fails, key/value pairs
	on this system will just vanish from cache.

Both cases lead to corrupted caches: If, for example, a tags-to-identifier entry is lost,
``dropByTag()`` will not be able to find the corresponding identifier-to-data entries
which should be removed and they will not be deleted. This results in old data delivered
by the cache. Additionally, there is currently no implementation of the garbage collection
which can rebuild cache integrity. It is thus important to monitor a memcached system for
evictions and server outages and to clear clear caches if that happens.

Furthermore memcache has no sort of namespacing. To distinguish entries of multiple caches
from each other, every entry is prefixed with the cache name. This can lead to very long
runtimes if a big cache needs to be flushed, because every entry has to be handled
separately and it is not possible to just truncate the whole cache with one call as this
would clear the whole memcached data which might even hold non TYPO3 Flow related entries.

Because of the mentioned drawbacks, the memcached backend should be used with care or in
situations where cache integrity is not important or if a cache has no need to use tags at
all.

.. note::

	The current native debian squeeze package (probably other distributions are affected,
	too) suffers from `PHP memcache bug #16927`_.

.. note::

	Since memcached has no sort of namespacing and access control, this backend should not
	be used if other third party systems do have access to the same memcached daemon for
	security reasons. This is a typical problem in cloud deployments where access to
	memcache is cheap (but could be read by third parties) and access to databases is
	expensive.

Options
~~~~~~~

:title:`Memcached cache backend options`

+-------------+------------------------------------------+-----------+---------+---------+
| Option      | Description                              | Mandatory | Type    | Default |
+=============+==========================================+===========+=========+=========+
| servers     | Array of used memcached servers, at      | Yes       | array   |         |
|             |                                          |           |         |         |
|             | least one server must be defined. Each   |           |         |         |
|             | server definition is a string, allowed   |           |         |         |
|             | syntaxes:                                |           |         |         |
|             |                                          |           |         |         |
|             | * **host**                               |           |         |         |
|             |     TCP connect to host on memcached     |           |         |         |
|             |     default port (usually 11211, defined |           |         |         |
|             |     by PHP ini                           |           |         |         |
|             |     variable memcache.default_port       |           |         |         |
|             | * **host:port**                          |           |         |         |
|             |     TCP connect to host on port          |           |         |         |
|             | * **tcp://hostname:port**                |           |         |         |
|             |     Same as above                        |           |         |         |
|             | * **unix:///path/to/memcached.sock**     |           |         |         |
|             |     Connect to memcached server using    |           |         |         |
|             |     unix sockets                         |           |         |         |
+-------------+------------------------------------------+-----------+---------+---------+
| compression | Enable memcached internal data           | No        | boolean | FALSE   |
|             | compression. Can be used to reduce       |           |         |         |
|             | memcached memory consumption but adds    |           |         |         |
|             | additional compression / decompression   |           |         |         |
|             | CPU overhead on the according memcached  |           |         |         |
|             | servers.                                 |           |         |         |
+-------------+------------------------------------------+-----------+---------+---------+

TYPO3\\Flow\\Cache\\Backend\\ApcBackend
---------------------------------------

`APC`_ is mostly known as an opcode cache for PHP source files but can be used to store
user data as well. As main advantage the data can be shared between different PHP
processes and requests. All calls are direct memory calls. This makes this backend
lightning fast for get() and set() operations. It can be an option for relatively small
caches (few dozens of megabytes) which are read and written very often and becomes handy
if APC is used as opcode cache anyway.

The implementation is very similar to the memcached backend implementation and suffers
from the same problems if APC runs out of memory.

The garbage collection is currently not implemented. In its latest version, APC will fail
to store data with a `PHP warning`_ if it runs out of memory. This may change in the
future. Even without using the cache backend, it is advisable to increase the memory
cache size of APC to at least 64MB when working with TYPO3 Flow, simply due to the large number
of PHP files to be cached. A minimum of 128MB is recommended when using the additional
content cache. Cache TTL for file and user data should be set to zero (disabled) to avoid
heavy memory fragmentation.

.. note::
	It is not advisable to use the APC backend in shared hosting environments for security
	reasons: The user cache in APC is not aware of different virtual hosts. Basically
	every PHP script which is executed on the system can read and write any data to this
	shared cache, given data is not encapsulated or namespaced in any way. Only use the
	APC backend in environments which are completely under your control and where no third
	party can read or tamper your data.

Options
~~~~~~~

The APC backend has no options.

TYPO3\\Flow\\Cache\\Backend\\TransientMemoryBackend
---------------------------------------------------

The transient memory backend stores data in a local array. It is only valid for one
request. This becomes handy if code logic needs to do expensive calculations or must look
up identical information from a database over and over again during its execution. In this
case it is useful to store the data in an array once and just lookup the entry from the
cache for consecutive calls to get rid of the otherwise additional overhead. Since caches
are available system wide and shared between core and extensions they can profit from each
other if they need the same information.

Since the data is stored directly in memory, this backend is the quickest backend
available. The stored data adds to the memory consumed by the PHP process and can hit the
``memory_limit`` PHP setting.

Options
~~~~~~~

The transient memory backend has no options.

TYPO3\\Flow\\Cache\\Backend\\NullBackend
----------------------------------------

The null backend is a dummy backend which doesn't store any data and always returns
``FALSE`` on ``get()``.

Options
~~~~~~~

The null backend has no options.

How to Use the Caching Framework
================================

This section is targeted at developers who want to use caches for arbitrary needs. It is
only about proper initialization, not a discussion about identifier, tagging and lifetime
decisions that must be taken during development.

Register a Cache
----------------

To register a cache it must be configured in *Caches.yaml* of a package::

	MyPackage_FooCache:
	  frontend: TYPO3\Flow\Cache\Frontend\StringFrontend

In this case ``\TYPO3\Flow\Cache\Frontend\StringFrontend`` was chosen, but that depends
on individual needs. This setting is usually not changed by users. Any option not given is
inherited from the configuration of the "Default" cache. The name (``MyPackage_FooCache``
in this case) can be chosen freely, but keep possible name clashes in mind and adopt a
meaningful schema.

Retrieve and Use a Cache
------------------------

Using dependency injection
~~~~~~~~~~~~~~~~~~~~~~~~~~

A cache is usually retrieved through dependency injection, either constructor or setter
injection. Which is chosen depends on when you need the cache to be available. Keep in
mind that even if you seem to need a cache in the constructor, you could always make use
of ``initializeObject()``. Here is an example for setter injection matching the
configuration given above. First you need to configure the injection in *Objects.yaml*::

	MyCompany\MyPackage\SomeClass:
	  properties:
	    fooCache:
	      object:
	        factoryObjectName: TYPO3\Flow\Cache\CacheManager
	        factoryMethodName: getCache
	        arguments:
	          1:
	            value: MyPackage_FooCache

This configures what will be injected into the following setter::

	/**
	 * Sets the foo cache
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\StringFrontend $cache Cache for foo data
	 * @return void
	 */
	public function setFooCache(\TYPO3\Flow\Cache\Frontend\StringFrontend $cache) {
		$this->fooCache = $cache;
	}

To make it even simpler you could omit the setter method and annotate the member with the
``Inject`` annotations. The injected cache is fully initialized, all available frontend
operations like ``get()``, ``set()`` and ``flushByTag()`` can be executed on ``$this->fooCache``.

Using the CacheFactory
~~~~~~~~~~~~~~~~~~~~~~

Of course you can also manually ask the CacheManager (have it injected for your
convenience) for a cache::

	$this->fooCache = $this->cacheManager->getCache('MyPackage_FooCache');

.. _Redis:                       http://redis.io/
.. _phpredis:                    https://github.com/owlient/phpredis
.. _Memcached:                   http://memcached.org/
.. _PHP memcache bug #16927:     https://bugs.php.net/bug.php?id=58943
.. _APC:                         http://pecl.php.net/package/APC
.. _PHP warning:                 https://bugs.php.net/bug.php?id=58982
