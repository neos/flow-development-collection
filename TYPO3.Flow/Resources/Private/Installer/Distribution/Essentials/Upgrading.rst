Upgrading instructions
======================

This file contains instructions for upgrading your Flow 2.3 based
applications to TYPO3 Flow 3.0.

What has changed
----------------

Flow 3.0 comes with numerous fixes and improvements. Here's a list of
changes that might need special attention when upgrading.
For a full list head over to the ChangeLogs:
http://docs.typo3.org/flow/TYPO3FlowDocumentation/TheDefinitiveGuide/PartV/ChangeLogs/

In general make sure to run the commands::

 ./flow flow:cache:flush --force
 ./flow core:migrate
 ./flow database:setcharset
 ./flow doctrine:migrate

when upgrading (see below).

Minimum PHP version requirement: 5.5
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

With Flow 3.0 the minimum PHP version requirement has been increased
from `5.3.2` to `5.5.0`.

If your PHP version is lower, the Bootstrap will stop with a corresponding
error.

See `FLOW-217 <https://jira.typo3.org/browse/FLOW-217>`_

Decoupling of TYPO3.Party package
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

With version 3.0 the Party package is no longer part of the Flow base
distribution.
If it's not required by another package of your application, you should
add a dependency to the package(s) that use classes of the party package
by adjusting the ``composer.json`` file accordingly::

 {
    // ...
    "require": {
        "typo3/party": "~3.0"
    }
 }

Besides, the following methods have been *deprecated*:

* ``\\TYPO3\\Flow\\Security\\Account::getParty()``
* ``\\TYPO3\\Flow\\Security\\Account:::setParty()``
* ``\\TYPO3\\Flow\\Security\\Context::getParty()``
* ``\\TYPO3\\Flow\\Security\\Context::getPartyByType()``

They still work if the Party package is installed, but usage of those methods should
be replaced with custom service calls (see Party package for an example of a simple
PartyService).

See `FLOW-5 <https://jira.typo3.org/browse/FLOW-5>`_

Reworked Security Framework
^^^^^^^^^^^^^^^^^^^^^^^^^^^

The Security Framework has been revised and now introduces the concept of
``privileges``. It also includes a restructuring of the privilege voting process.
This allows for a greater flexibility in regards to Access Control Lists (ACL).

This is a breaking change mainly because it drops support for ``Content Security``
and ``Secure Downloads``.
Besides it is quite likely that custom code that interacts with the (non-public)
API of the security framework won't work without adjustments.

The new Policy.yaml syntax is covered by code migrations.

See `FLOW-11 <https://jira.typo3.org/browse/FLOW-11>`


Multi-Storage / Multi-Target Resource Management
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Flow 3.0 comes with a completely revised Resource Management which allows for storage
and publication of persistent or static resources (assets) in the local file system
or other services, such as Amazon S3 or Rackspace CloudFiles. It also introduces the
concept of collections which allows for grouping resources into collections with specific
storage and publication rules.

Existing persistent resources are migrated through the Doctrine migration contained in
this feature.

See `FLOW-108 <https://jira.typo3.org/browse/FLOW-108>`_

Charset and collation in all MySQL migrations
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

All MySQL migrations now explicitly specify charset and collation as suggested by
`Doctrine <https://github.com/doctrine/dbal/blob/master/UPGRADE.md#creating-mysql-tables-now-defaults-to-utf-8>`_.


This is breaking if you have existing tables that do not use the ``utf8`` charset and
``utf8_unicode_ci`` collation. To solve this you need to convert the existing tables.
This can be done using the command::

 ./flow database:setcharset

See `NEOS-800 <https://jira.typo3.org/browse/NEOS-800>`_

Exclude Non-Flow packages from object management by default
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

All "non-flow-packages" (Packages for which the composer type does not
start with "typo3-flow-*") are now excluded from object management by default.

Previously they had to be excluded explicitly with the
``TYPO3.Flow.object.excludeClasses`` setting.

To activate object management for Non-Flow packages, the newly introduced
setting ``TYPO3.Flow.object.includeClasses`` can be used. It works in
the same way as ``excludeClasses``, apart from not allowing wildcards for
the package.

This is a breaking change in case proxy building for non-flow packages
was expected. In these cases packages have to be included explicitly now::

 TYPO3:
   Flow:
     object:
       includeClasses:
         'non.flow.package' : ['.*']

To exclude classes from Flow packages a non-matching or empty expression
can be specified::

 TYPO3:
   Flow:
     object:
       includeClasses:
         'Some.Flow.Package' : []

The ``excludeClasses`` setting is deprecated but still evaluated.

Hint: To find out whether a package still uses deprecated configuration,
run the::

 ./flow configuration:validate

command.

See `FLOW-103 <https://jira.typo3.org/browse/FLOW-103>`_

Adjusted "ignoreTags" configuration syntax
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The ``TYPO3.Flow.reflection.ignoreTags`` setting syntax has been adjusted to allow for
adding and changing tag ignore behavior from 3rd party packages.

The previous syntax::

  TYPO3:
    Flow:
      reflection:
        ignoredTags: ['tag1', 'tag2']

is now deprecated in favor of::

  TYPO3:
    Flow:
      reflection:
        ignoredTags:
          'tag1': TRUE
          'tag2': TRUE

The old syntax is still evaluated so this change is mostly backwards compatible.
However it changes the behavior so that configuration is now *merged* rather than
*replaced*. So this is a breaking change if a package relied on this behavior. To
remove a tag from the list of ignored tags, it has to be set to ``FALSE``
explicitly now::

  TYPO3:
    Flow:
      reflection:
        ignoredTags:
          'someTag': FALSE

See `FLOW-199 <https://jira.typo3.org/browse/FLOW-199>`_

Remove obsolete "security.enable" Setting
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The ``TYPO3.Flow.security.enable`` and all mentions and usages of it have been
removed.

This setting was initially intended for performance reasons (for applications
without security features) and in order to disable security for (functional) tests.
For the latter we use a different approach since a while and the performance hit of
security features is also negligible since Flow pre-compiles classes.
Besides the flag was never evaluated consistently.

See `FLOW-181 <https://jira.typo3.org/browse/FLOW-181>`_

New annotation "InjectConfiguration"
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

A new annotation that allows for injection of arbitrary configuration.

Example::

    /**
     * @var string
     * @Flow\\InjectConfiguration("my.setting")
     */
     protected $mySetting;

    /**
     * @var string
     * @Flow\\InjectConfiguration(package="TYPO3.Flow", path="core.phpBinaryPathAndFilename")
     */
    protected $phpBinary;

    /**
     * @var array
     * @Flow\\InjectConfiguration(type="Views")
     */
    protected $viewsConfiguration;

This is not a breaking change per se, but it deprecates the use of the
``Inject`` annotation for injecting settings.
So if you have code like the following::

 @Flow\Inject(setting="email", package="Some.Package")

you should consider using the new annotation instead.

See `FLOW-148 <https://jira.typo3.org/browse/FLOW-148>`_

Fluid: Consistent escaping behavior
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Fluid 3.0 comes with a major rework of the interceptors that are currently
mostly used to automatically apply ``htmlspecialchars()`` to dynamic strings
in Fluid templates.

This is a breaking change because it affects the basic escaping
behavior of Fluid:

The escaping interceptor is now always enabled by default. Previously
this was only the case if the request format was unknown or equal to
"html".
To disable the automatic escaping add ``{escapingEnabled=false}``
anywhere in the template or (preferably) use the Raw ViewHelper::

  {objectAccess -> f:format.raw()}
  {x:some.viewHelper() -> f:format.raw()}
  {objectAccess -> x:some.viewHelper() -> f:format.raw()}
  <f:format.raw><x:some.viewHelper /></f:format.raw>

Furthermore the ``escapingInterceptorEnabled`` flag in the
``AbstractViewHelper`` has been deprecated in favor of a new flag
``escapeChildren``. The behavior of the flag is still the same though
and the old name will still work.

Lastly the *output* of ViewHelpers is now also escaped by default!
Previously ViewHelper authors had to take care of that themselves
which was error-prone and less flexible.

The escaping of a custom ViewHelper can be disabled by setting the new
flag ``escapeOutput`` to FALSE in the ViewHelper class.
But this should only be necessary if:

a) The result of ``$this->renderChildren()`` is used directly as output
   (child nodes are escaped by default).
b) The ViewHelper renders HTML code.
   *Beware:* In that case the output will need manual data sanitization
   ViewHelpers extending ``AbstractTagBasedViewHelper`` will already
   have the flag set.

All provided ViewHelpers are adjusted accordingly with one exception:
The output of URI-ViewHelpers such as ``uri.action`` or ``widget.uri``
is now escaped for consistency reasons. If those are used to render HTML
tag attributes the new behavior is desired because those will be
properly encoded now. If the result of a URI ViewHelper is used
directly, for example within some inline JavaScript, the new escaping
might break. In this case the Raw ViewHelper can be used, as described
above like done in the ``Index.html`` template of the ``Autocomplete``
widget.

A core migration adjusts existing ViewHelpers by adding
``$escapeOutput = FALSE;`` for backwards compatibility. You should go
through each affected ViewHelper to verify if that flag is really needed.

See `FLOW-26 <https://jira.typo3.org/browse/FLOW-26>`_

Fluid: Submitted form data has precedence over value argument
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The behavior of all Form ViewHelpers has been adjusted so that any submitted
value is redisplayed even if a "value" argument has been specified.

Being able to specify the "value" argument in Form ViewHelpers is a good way
to pre-format the initial value::

 <f:form.textfield property="price"
    value="{product.price -> f:format.number()}" />

Previously the ``value`` argument had precedence over previously submitted
value, so it would be re-display the original values overriding changes upon
re-display of the form due to property-mapping or validation errors.


This is a breaking change if you expect the previous behavior of form
ViewHelpers always being pre-populated with the specified value
attribute / bound object property even when re-displaying the form upon
validation errors. Besides this change deprecates
``AbstractFormFieldViewHelper::getValue()``.
If you call that method in your custom ViewHelpers you should use
``AbstractFormFieldViewHelper::getValueAttribute()`` instead and call ``AbstractFormFieldViewHelper::addAdditionalIdentityPropertiesIfNeeded()``
explicitly if the ViewHelper might be bound to (sub)entities.

See `FLOW-213 <https://jira.typo3.org/browse/FLOW-213>`_

Fluid: Throw exception for unresolved namespaces
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

With this change the Fluid parser now throws an exception when it comes
across an unknown ViewHelper namespace.

That is especially helpful if you forgot to import a namespace or
mistyped a ViewHelper name.

It is a breaking change if you rely on the previous behavior of
ignoring ViewHelpers with unknown namespaces.
In that case you can ignore all unknown namespaces with::

  {namespace *}

Specific namespaces can be ignored like this::

  {namespace xs*}  <!-- ignores namespaces starting with "xs" -->
  {namespace foo}  <!-- ignores the namespace "foo" -->

See `FLOW-150 <https://jira.typo3.org/browse/FLOW-150>`_

Further breaking changes
------------------------

* [BUGFIX] Generate Value Object hash from property values (see `#55953 <https://forge.typo3.org/issues/55953>`_)
* [TASK] Do not use LoggerFactory in a static context(see `c4a9350 <https://git.typo3.org/Packages/TYPO3.Flow.git/commit/c4a935054d840a49394559a128296b2812dbfca2>`_)
* [TASK] Fix order of DB migrations related to role handling (see `d1641d4 <https://git.typo3.org/Packages/TYPO3.Flow.git/commit/d1641d40b73f5cc716693e0fd1ae7e79abbb07d2>`_)
* [BUGFIX] SessionManagerInterface and SessionInterface are incomplete (see `0c8ed7d <https://git.typo3.org/Packages/TYPO3.Flow.git/commit/0c8ed7daed836e80b36b951d61fbd24295f7f24c>`_)
* [BUGFIX] Correct object modification exception trigger (see `525a894 <https://git.typo3.org/Packages/TYPO3.Flow.git/commit/525a8942af2866966c8b86c6995734b7885e451c>`_)
* [BUGFIX] Skip automatic persistence for updated entities (see `FLOW-84 <https://jira.typo3.org/browse/FLOW-84>`_)
* [TASK] Remove usage of ReflectionService in ViewHelpers (see `3adb3c3 <https://git.typo3.org/Packages/TYPO3.Fluid.git/commit/3adb3c3ded8ff90bbce1a0386a6a120fe0dde322>`_)

Upgrading your Packages
-----------------------

Upgrading existing code
^^^^^^^^^^^^^^^^^^^^^^^

Here comes the easier part. As with earlier changes to TYPO3 Flow that
required code changes on the user side we provide a code migration tool.
Given you have a TYPO3 Flow system with your (outdated) package in place
you should run the following before attempting to fix anything by hand::

 ./flow core:migrate --package-key Acme.Demo

The package key is optional, if left out it will work on all packages
it finds (except for library packages and packages prefixed with
"TYPO3.*") - for the first run you might want to limit things a little to
keep the overview, though.

Inside core:migrate
"""""""""""""""""""

The tool roughly works like this:

* Collect all code migrations from packages

* Collect all files from all packages (except *Framework* and
  *Libraries*) or the package given with ``--package-key``
* For each migration and package

  * Check for clean git working copy (otherwise skip it)
  * Check if migration is needed (looks for Migration footers in commit
    messages)
  * Apply migration and commit the changes

Afterwards you probably get a list of warnings and notes from the
migrations, check those to see if anything needs to be done manually.

Check the created commits and feel free to amend as needed, should
things be missing or wrong. The only thing you must keep in place from
the generated commit messages is the Migration: … footer. It is used to
detect if a migration has been applied already, so if you drop it,
things might get out of hands in the future.

Upgrading the database schema
-----------------------------

Upgrading the schema is done by running::

 ./flow doctrine:migrate

to update your database with any changes to the framework-supplied
schema.

Famous last words
-----------------

In a nutshell, running::

 ./flow core:migrate
 ./flow doctrine:migrationgenerate

padded with some manual checking and adjustments needs to be done. That
should result in a working package.

If it does not and you have no idea what to do next, please get in touch
with us. The `support page <http://flow.typo3.org/support/>`_ provides more
information.