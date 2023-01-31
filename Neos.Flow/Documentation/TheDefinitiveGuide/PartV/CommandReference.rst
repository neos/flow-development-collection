.. _`Flow Command Reference`:

Flow Command Reference
======================

.. note:

  This reference uses ``./flow`` as the command to invoke. If you are on
  Windows, this will probably not work, there you need to use ``flow.bat``
  instead.

The commands in this reference are shown with their full command identifiers.
On your system you can use shorter identifiers, whose availability depends
on the commands available in total (to avoid overlap the shortest possible
identifier is determined during runtime).

To see the shortest possible identifiers on your system as well as further
commands that may be available, use::

  ./flow help

The following reference was automatically generated from code on 2023-01-31


.. _`Flow Command Reference: NEOS.FLOW`:

Package *NEOS.FLOW*
-------------------


.. _`Flow Command Reference: NEOS.FLOW neos.flow:cache:collectgarbage`:

``neos.flow:cache:collectgarbage``
**********************************

**Cache Garbage Collection**

Runs the Garbage Collection (collectGarbage) method on all registered caches.

Though the method is defined in the BackendInterface, the implementation
can differ and might not remove any data, depending on possibilities of
the backend.



Options
^^^^^^^

``--cache-identifier``
  If set, this command only applies to the given cache





.. _`Flow Command Reference: NEOS.FLOW neos.flow:cache:flush`:

``neos.flow:cache:flush``
*************************

**Flush all caches**

The flush command flushes all caches (including code caches) which have been
registered with Flow's Cache Manager. It will NOT remove any session data, unless
you specifically configure the session caches to not be persistent.

If fatal errors caused by a package prevent the compile time bootstrap
from running, the removal of any temporary data can be forced by specifying
the option **--force**.

This command does not remove the precompiled data provided by frozen
packages unless the **--force** option is used.



Options
^^^^^^^

``--force``
  Force flushing of any temporary data



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:cache:warmup``
  Warm up caches
``neos.flow:package:freeze``
  Freeze a package
``neos.flow:package:refreeze``
  Refreeze a package



.. _`Flow Command Reference: NEOS.FLOW neos.flow:cache:flushone`:

``neos.flow:cache:flushone``
****************************

**Flushes a particular cache by its identifier**

Given a cache identifier, this flushes just that one cache. To find
the cache identifiers, you can use the configuration:show command with
the type set to "Caches".

Note that this does not have a force-flush option since it's not
meant to remove temporary code data, resulting into a broken state if
code files lack.

Arguments
^^^^^^^^^

``--identifier``
  Cache identifier to flush cache for





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:cache:flush``
  Flush all caches
``neos.flow:configuration:show``
  Show the active configuration settings



.. _`Flow Command Reference: NEOS.FLOW neos.flow:cache:list`:

``neos.flow:cache:list``
************************

**List all configured caches and their status if available**

This command will exit with a code 1 if at least one cache status contains errors or warnings
This allows the command to be easily integrated in CI setups (the --quiet flag can be used to reduce verbosity)



Options
^^^^^^^

``--quiet``
  If set, this command only outputs errors & warnings



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:cache:show``
  Display details of a cache including a detailed status if available



.. _`Flow Command Reference: NEOS.FLOW neos.flow:cache:setup`:

``neos.flow:cache:setup``
*************************

**Setup the given Cache if possible**

Invokes the setup() method on the configured CacheBackend (if it implements the WithSetupInterface)
which should setup and validate the backend (i.e. create required database tables, directories, ...)

Arguments
^^^^^^^^^

``--cache-identifier``
  





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:cache:list``
  List all configured caches and their status if available
``neos.flow:cache:setupall``
  Setup all Caches



.. _`Flow Command Reference: NEOS.FLOW neos.flow:cache:setupall`:

``neos.flow:cache:setupall``
****************************

**Setup all Caches**

Invokes the setup() method on all configured CacheBackend that implement the WithSetupInterface interface
which should setup and validate the backend (i.e. create required database tables, directories, ...)

This command will exit with a code 1 if at least one cache setup failed
This allows the command to be easily integrated in CI setups (the --quiet flag can be used to reduce verbosity)



Options
^^^^^^^

``--quiet``
  If set, this command only outputs errors & warnings



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:cache:setup``
  Setup the given Cache if possible



.. _`Flow Command Reference: NEOS.FLOW neos.flow:cache:show`:

``neos.flow:cache:show``
************************

**Display details of a cache including a detailed status if available**



Arguments
^^^^^^^^^

``--cache-identifier``
  identifier of the cache (for example "Flow_Core")





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:cache:list``
  List all configured caches and their status if available



.. _`Flow Command Reference: NEOS.FLOW neos.flow:cache:warmup`:

``neos.flow:cache:warmup``
**************************

**Warm up caches**

The warm up caches command initializes and fills – as far as possible – all
registered caches to get a snappier response on the first following request.
Apart from caches, other parts of the application may hook into this command
and execute tasks which take further steps for preparing the app for the big
rush.





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:cache:flush``
  Flush all caches



.. _`Flow Command Reference: NEOS.FLOW neos.flow:configuration:generateschema`:

``neos.flow:configuration:generateschema``
******************************************

**Generate a schema for the given configuration or YAML file.**

./flow configuration:generateschema --type Settings --path Neos.Flow.persistence

The schema will be output to standard output.



Options
^^^^^^^

``--type``
  Configuration type to create a schema for
``--path``
  path to the subconfiguration separated by "." like "Neos.Flow
``--yaml``
  YAML file to create a schema for





.. _`Flow Command Reference: NEOS.FLOW neos.flow:configuration:listtypes`:

``neos.flow:configuration:listtypes``
*************************************

**List registered configuration types**









.. _`Flow Command Reference: NEOS.FLOW neos.flow:configuration:show`:

``neos.flow:configuration:show``
********************************

**Show the active configuration settings**

The command shows the configuration of the current context as it is used by Flow itself.
You can specify the configuration type and path if you want to show parts of the configuration.

Display all settings:
./flow configuration:show

Display Flow persistence settings:
./flow configuration:show --path Neos.Flow.persistence

Display Flow Object Cache configuration
./flow configuration:show --type Caches --path Flow_Object_Classes



Options
^^^^^^^

``--type``
  Configuration type to show, defaults to Settings
``--path``
  path to subconfiguration separated by "." like "Neos.Flow





.. _`Flow Command Reference: NEOS.FLOW neos.flow:configuration:validate`:

``neos.flow:configuration:validate``
************************************

**Validate the given configuration**

**Validate all configuration**
./flow configuration:validate

**Validate configuration at a certain subtype**
./flow configuration:validate --type Settings --path Neos.Flow.persistence

You can retrieve the available configuration types with:
./flow configuration:listtypes



Options
^^^^^^^

``--type``
  Configuration type to validate
``--path``
  path to the subconfiguration separated by "." like "Neos.Flow
``--verbose``
  if true, output more verbose information on the schema files which were used





.. _`Flow Command Reference: NEOS.FLOW neos.flow:core:migrate`:

``neos.flow:core:migrate``
**************************

**Migrate source files as needed**

This will apply pending code migrations defined in packages to the
specified package.

For every migration that has been run, it will create a commit in
the package. This allows for easy inspection, rollback and use of
the fixed code.
If the affected package contains local changes or is not part of
a git repository, the migration will be skipped. With the --force
flag this behavior can be changed, but changes will only be committed
if the working copy was clean before applying the migration.

Arguments
^^^^^^^^^

``--package``
  The key of the package to migrate



Options
^^^^^^^

``--status``
  Show the migration status, do not run migrations
``--packages-path``
  If set, use the given path as base when looking for packages
``--version``
  If set, execute only the migration with the given version (e.g. "20150119114100")
``--verbose``
  If set, notes and skipped migrations will be rendered
``--force``
  By default packages that are not under version control or contain local changes are skipped. With this flag set changes are applied anyways (changes are not committed if there are local changes though)



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrate``
  Migrate the database schema



.. _`Flow Command Reference: NEOS.FLOW neos.flow:core:setfilepermissions`:

``neos.flow:core:setfilepermissions``
*************************************

**Adjust file permissions for CLI and web server access**

This command adjusts the file permissions of the whole Flow application to
the given command line user and webserver user / group.

Arguments
^^^^^^^^^

``--commandline-user``
  User name of the command line user, for example "john
``--webserver-user``
  User name of the webserver, for example "www-data
``--webserver-group``
  Group name of the webserver, for example "www-data







.. _`Flow Command Reference: NEOS.FLOW neos.flow:database:setcharset`:

``neos.flow:database:setcharset``
*********************************

**Convert the database schema to use the given character set and collation (defaults to utf8mb4 and utf8mb4_unicode_ci).**

This command can be used to convert the database configured in the Flow settings to the utf8mb4 character
set and the utf8mb4_unicode_ci collation (by default, a custom collation can be given). It will only
work when using the pdo_mysql driver.

**Make a backup** before using it, to be on the safe side. If you want to inspect the statements used
for conversion, you can use the $output parameter to write them into a file. This file can be used to do
the conversion manually.

For background information on this, see:

- http://stackoverflow.com/questions/766809/
- http://dev.mysql.com/doc/refman/5.5/en/alter-table.html
- https://medium.com/@adamhooper/in-mysql-never-use-utf8-use-utf8mb4-11761243e434
- https://mathiasbynens.be/notes/mysql-utf8mb4
- https://florian.ec/articles/mysql-doctrine-utf8/

The main purpose of this is to fix setups that were created with Flow before version 5.0. In those cases,
the tables will have a collation that does not match the default collation of later Flow versions, potentially
leading to problems when creating foreign key constraints (among others, potentially).

If you have special needs regarding the charset and collation, you *can* override the defaults with
different ones.

Note: This command **is not a general purpose conversion tool**. It will specifically not fix cases
of actual utf8 stored in latin1 columns. For this a conversion to BLOB followed by a conversion to the
proper type, charset and collation is needed instead.



Options
^^^^^^^

``--character-set``
  Character set, defaults to utf8mb4
``--collation``
  Collation to use, defaults to utf8mb4_unicode_ci
``--output``
  A file to write SQL to, instead of executing it
``--verbose``
  If set, the statements will be shown as they are executed





.. _`Flow Command Reference: NEOS.FLOW neos.flow:doctrine:create`:

``neos.flow:doctrine:create``
*****************************

**Create the database schema**

Creates a new database schema based on the current mapping information.

It expects the database to be empty, if tables that are to be created already
exist, this will lead to errors.



Options
^^^^^^^

``--output``
  A file to write SQL to, instead of executing it



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:update``
  Update the database schema
``neos.flow:doctrine:migrate``
  Migrate the database schema



.. _`Flow Command Reference: NEOS.FLOW neos.flow:doctrine:dql`:

``neos.flow:doctrine:dql``
**************************

**Run arbitrary DQL and display results**

Any DQL queries passed after the parameters will be executed, the results will be output:

doctrine:dql --limit 10 'SELECT a FROM Neos\Flow\Security\Account a'



Options
^^^^^^^

``--depth``
  How many levels deep the result should be dumped
``--hydration-mode``
  One of: object, array, scalar, single-scalar, simpleobject
``--offset``
  Offset the result by this number
``--limit``
  Limit the result to this number





.. _`Flow Command Reference: NEOS.FLOW neos.flow:doctrine:entitystatus`:

``neos.flow:doctrine:entitystatus``
***********************************

**Show the current status of entities and mappings**

Shows basic information about which entities exist and possibly if their
mapping information contains errors or not.

To run a full validation, use the validate command.



Options
^^^^^^^

``--dump-mapping-data``
  If set, the mapping data will be output
``--entity-class-name``
  If given, the mapping data for just this class will be output



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:validate``
  Validate the class/table mappings



.. _`Flow Command Reference: NEOS.FLOW neos.flow:doctrine:migrate`:

``neos.flow:doctrine:migrate``
******************************

**Migrate the database schema**

Adjusts the database structure by applying the pending
migrations provided by currently active packages.



Options
^^^^^^^

``--version``
  The version to migrate to
``--output``
  A file to write SQL to, instead of executing it
``--dry-run``
  Whether to do a dry run or not
``--quiet``
  If set, only the executed migration versions will be output, one per line



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrationstatus``
  Show the current migration status
``neos.flow:doctrine:migrationexecute``
  Execute a single migration
``neos.flow:doctrine:migrationgenerate``
  Generate a new migration
``neos.flow:doctrine:migrationversion``
  Mark/unmark migrations as migrated



.. _`Flow Command Reference: NEOS.FLOW neos.flow:doctrine:migrationexecute`:

``neos.flow:doctrine:migrationexecute``
***************************************

**Execute a single migration**

Manually runs a single migration in the given direction.

Arguments
^^^^^^^^^

``--version``
  The migration to execute



Options
^^^^^^^

``--direction``
  Whether to execute the migration up (default) or down
``--output``
  A file to write SQL to, instead of executing it
``--dry-run``
  Whether to do a dry run or not



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrate``
  Migrate the database schema
``neos.flow:doctrine:migrationstatus``
  Show the current migration status
``neos.flow:doctrine:migrationgenerate``
  Generate a new migration
``neos.flow:doctrine:migrationversion``
  Mark/unmark migrations as migrated



.. _`Flow Command Reference: NEOS.FLOW neos.flow:doctrine:migrationgenerate`:

``neos.flow:doctrine:migrationgenerate``
****************************************

**Generate a new migration**

If $diffAgainstCurrent is true (the default), it generates a migration file
with the diff between current DB structure and the found mapping metadata.

Otherwise an empty migration skeleton is generated.

Only includes tables/sequences matching the $filterExpression regexp when
diffing models and existing schema. Include delimiters in the expression!
The use of

--filter-expression '/^acme_com/'

would only create a migration touching tables starting with "acme_com".

Note: A filter-expression will overrule any filter configured through the
Neos.Flow.persistence.doctrine.migrations.ignoredTables setting



Options
^^^^^^^

``--diff-against-current``
  Whether to base the migration on the current schema structure
``--filter-expression``
  Only include tables/sequences matching the filter expression regexp
``--force``
  Generate migrations even if there are migrations left to execute



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrate``
  Migrate the database schema
``neos.flow:doctrine:migrationstatus``
  Show the current migration status
``neos.flow:doctrine:migrationexecute``
  Execute a single migration
``neos.flow:doctrine:migrationversion``
  Mark/unmark migrations as migrated



.. _`Flow Command Reference: NEOS.FLOW neos.flow:doctrine:migrationstatus`:

``neos.flow:doctrine:migrationstatus``
**************************************

**Show the current migration status**

Displays the migration configuration as well as the number of
available, executed and pending migrations.



Options
^^^^^^^

``--show-migrations``
  Output a list of all migrations and their status



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrate``
  Migrate the database schema
``neos.flow:doctrine:migrationexecute``
  Execute a single migration
``neos.flow:doctrine:migrationgenerate``
  Generate a new migration
``neos.flow:doctrine:migrationversion``
  Mark/unmark migrations as migrated



.. _`Flow Command Reference: NEOS.FLOW neos.flow:doctrine:migrationversion`:

``neos.flow:doctrine:migrationversion``
***************************************

**Mark/unmark migrations as migrated**

If *all* is given as version, all available migrations are marked
as requested.

Arguments
^^^^^^^^^

``--version``
  The migration to execute



Options
^^^^^^^

``--add``
  The migration to mark as migrated
``--delete``
  The migration to mark as not migrated



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:migrate``
  Migrate the database schema
``neos.flow:doctrine:migrationstatus``
  Show the current migration status
``neos.flow:doctrine:migrationexecute``
  Execute a single migration
``neos.flow:doctrine:migrationgenerate``
  Generate a new migration



.. _`Flow Command Reference: NEOS.FLOW neos.flow:doctrine:update`:

``neos.flow:doctrine:update``
*****************************

**Update the database schema**

Updates the database schema without using existing migrations.

It will not drop foreign keys, sequences and tables, unless *--unsafe-mode* is set.



Options
^^^^^^^

``--unsafe-mode``
  If set, foreign keys, sequences and tables can potentially be dropped.
``--output``
  A file to write SQL to, instead of executing the update directly



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:create``
  Create the database schema
``neos.flow:doctrine:migrate``
  Migrate the database schema



.. _`Flow Command Reference: NEOS.FLOW neos.flow:doctrine:validate`:

``neos.flow:doctrine:validate``
*******************************

**Validate the class/table mappings**

Checks if the current class model schema is valid. Any inconsistencies
in the relations between models (for example caused by wrong or
missing annotations) will be reported.

Note that this does not check the table structure in the database in
any way.





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:doctrine:entitystatus``
  Show the current status of entities and mappings



.. _`Flow Command Reference: NEOS.FLOW neos.flow:help:help`:

``neos.flow:help:help``
***********************

**Display help for a command**

The help command displays help for a given command:
./flow help <commandIdentifier>



Options
^^^^^^^

``--command-identifier``
  Identifier of a command for more details





.. _`Flow Command Reference: NEOS.FLOW neos.flow:middleware:list`:

``neos.flow:middleware:list``
*****************************

**Lists all configured middleware components in the order they will be executed**









.. _`Flow Command Reference: NEOS.FLOW neos.flow:package:create`:

``neos.flow:package:create``
****************************

**Create a new package**

This command creates a new package which contains only the mandatory
directories and files.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package to create



Options
^^^^^^^

``--package-type``
  The package type of the package to create



Related commands
^^^^^^^^^^^^^^^^

``neos.kickstarter:kickstart:package``
  Kickstart a new package



.. _`Flow Command Reference: NEOS.FLOW neos.flow:package:freeze`:

``neos.flow:package:freeze``
****************************

**Freeze a package**

This function marks a package as **frozen** in order to improve performance
in a development context. While a package is frozen, any modification of files
within that package won't be tracked and can lead to unexpected behavior.

File monitoring won't consider the given package. Further more, reflection
data for classes contained in the package is cached persistently and loaded
directly on the first request after caches have been flushed. The precompiled
reflection data is stored in the **Configuration** directory of the
respective package.

By specifying **all** as a package key, all currently frozen packages are
frozen (the default).



Options
^^^^^^^

``--package-key``
  Key of the package to freeze



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:package:unfreeze``
  Unfreeze a package
``neos.flow:package:refreeze``
  Refreeze a package



.. _`Flow Command Reference: NEOS.FLOW neos.flow:package:list`:

``neos.flow:package:list``
**************************

**List available packages**

Lists all locally available packages. Displays the package key, version and
package title.



Options
^^^^^^^

``--loading-order``
  The returned packages are ordered by their loading order.





.. _`Flow Command Reference: NEOS.FLOW neos.flow:package:refreeze`:

``neos.flow:package:refreeze``
******************************

**Refreeze a package**

Refreezes a currently frozen package: all precompiled information is removed
and file monitoring will consider the package exactly once, on the next
request. After that request, the package remains frozen again, just with the
updated data.

By specifying **all** as a package key, all currently frozen packages are
refrozen (the default).



Options
^^^^^^^

``--package-key``
  Key of the package to refreeze, or 'all'



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:package:freeze``
  Freeze a package
``neos.flow:cache:flush``
  Flush all caches



.. _`Flow Command Reference: NEOS.FLOW neos.flow:package:rescan`:

``neos.flow:package:rescan``
****************************

**Rescan package availability and recreates the PackageStates configuration.**









.. _`Flow Command Reference: NEOS.FLOW neos.flow:package:unfreeze`:

``neos.flow:package:unfreeze``
******************************

**Unfreeze a package**

Unfreezes a previously frozen package. On the next request, this package will
be considered again by the file monitoring and related services – if they are
enabled in the current context.

By specifying **all** as a package key, all currently frozen packages are
unfrozen (the default).



Options
^^^^^^^

``--package-key``
  Key of the package to unfreeze, or 'all'



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:package:freeze``
  Freeze a package
``neos.flow:cache:flush``
  Flush all caches



.. _`Flow Command Reference: NEOS.FLOW neos.flow:resource:clean`:

``neos.flow:resource:clean``
****************************

**Clean up resource registry**

This command checks the resource registry (that is the database tables) for orphaned resource objects which don't
seem to have any corresponding data anymore (for example: the file in Data/Persistent/Resources has been deleted
without removing the related PersistentResource object).

If the Neos.Media package is active, this command will also detect any assets referring to broken resources
and will remove the respective Asset object from the database when the broken resource is removed.

This command will ask you interactively what to do before deleting anything.







.. _`Flow Command Reference: NEOS.FLOW neos.flow:resource:copy`:

``neos.flow:resource:copy``
***************************

**Copy resources**

This command copies all resources from one collection to another storage identified by name.
The target storage must be empty and must not be identical to the current storage of the collection.

This command merely copies the binary data from one storage to another, it does not change the related
PersistentResource objects in the database in any way. Since the PersistentResource objects in the database refer to a
collection name, you can use this command for migrating from one storage to another my configuring
the new storage with the name of the old storage collection after the resources have been copied.

Arguments
^^^^^^^^^

``--source-collection``
  The name of the collection you want to copy the assets from
``--target-collection``
  The name of the collection you want to copy the assets to



Options
^^^^^^^

``--publish``
  If enabled, the target collection will be published after the resources have been copied





.. _`Flow Command Reference: NEOS.FLOW neos.flow:resource:publish`:

``neos.flow:resource:publish``
******************************

**Publish resources**

This command publishes the resources of the given or - if none was specified, all - resource collections
to their respective configured publishing targets.



Options
^^^^^^^

``--collection``
  If specified, only resources of this collection are published. Example: 'persistent'





.. _`Flow Command Reference: NEOS.FLOW neos.flow:routing:list`:

``neos.flow:routing:list``
**************************

**List the known routes**

This command displays a list of all currently registered routes.







.. _`Flow Command Reference: NEOS.FLOW neos.flow:routing:match`:

``neos.flow:routing:match``
***************************

**Match the given URI to a corresponding route**

This command takes an incoming URI and displays the
matched Route and the mapped routing values (if any):

./flow routing:match "/de" --parameters="{\"requestUriHost\": \"localhost\"}"

Arguments
^^^^^^^^^

``--uri``
  The incoming route, absolute or relative



Options
^^^^^^^

``--method``
  The HTTP method to simulate (default is 'GET')
``--parameters``
  Route parameters as JSON string. Make sure to specify this option as described in the description in order to prevent parsing issues





.. _`Flow Command Reference: NEOS.FLOW neos.flow:routing:resolve`:

``neos.flow:routing:resolve``
*****************************

**Build an URI for the given parameters**

This command takes package, controller and action and displays the
resolved URI and which route matched (if any):

./flow routing:resolve Some.Package --controller SomeController --additional-arguments="{\"some-argument\": \"some-value\"}"

Arguments
^^^^^^^^^

``--package``
  Package key (according to "@package" route value)



Options
^^^^^^^

``--controller``
  Controller name (according to "@controller" route value), default is 'Standard'
``--action``
  Action name (according to "@action" route value), default is 'index'
``--format``
  Requested Format name (according to "@format" route value), default is 'html'
``--subpackage``
  SubPackage name (according to "@subpackage" route value)
``--additional-arguments``
  Additional route values as JSON string. Make sure to specify this option as described in the description in order to prevent parsing issues
``--parameters``
  Route parameters as JSON string. Make sure to specify this option as described in the description in order to prevent parsing issues
``--base-uri``
  Base URI of the simulated request, default ist 'http://localhost'
``--force-absolute-uri``
  Whether or not to force the creation of an absolute URI





.. _`Flow Command Reference: NEOS.FLOW neos.flow:routing:show`:

``neos.flow:routing:show``
**************************

**Show information for a route**

This command displays the configuration of a route specified by index number.

Arguments
^^^^^^^^^

``--index``
  The index of the route as given by routing:list







.. _`Flow Command Reference: NEOS.FLOW neos.flow:schema:validate`:

``neos.flow:schema:validate``
*****************************

**Validate the given configurationfile againt a schema file**





Options
^^^^^^^

``--configuration-file``
  path to the validated configuration file
``--schema-file``
  path to the schema file
``--verbose``
  if true, output more verbose information on the schema files which were used





.. _`Flow Command Reference: NEOS.FLOW neos.flow:security:describerole`:

``neos.flow:security:describerole``
***********************************

**Show details of a specified role**



Arguments
^^^^^^^^^

``--role``
  identifier of the role to describe (for example "Neos.Flow:Everybody")







.. _`Flow Command Reference: NEOS.FLOW neos.flow:security:generatekeypair`:

``neos.flow:security:generatekeypair``
**************************************

**Generate a public/private key pair and add it to the RSAWalletService**





Options
^^^^^^^

``--used-for-passwords``
  If the private key should be used for passwords



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:security:importprivatekey``
  Import a private key



.. _`Flow Command Reference: NEOS.FLOW neos.flow:security:importprivatekey`:

``neos.flow:security:importprivatekey``
***************************************

**Import a private key**

Read a PEM formatted private key from stdin and import it into the
RSAWalletService. The public key will be automatically extracted and stored
together with the private key as a key pair.

You can generate the same fingerprint returned from this using these commands:

ssh-keygen -yf my-key.pem > my-key.pub
ssh-keygen -lf my-key.pub

To create a private key to import using this method, you can use:

ssh-keygen -t rsa -f my-key
./flow security:importprivatekey < my-key

Again, the fingerprint can also be generated using:

ssh-keygen -lf my-key.pub



Options
^^^^^^^

``--used-for-passwords``
  If the private key should be used for passwords



Related commands
^^^^^^^^^^^^^^^^

``neos.flow:security:importpublickey``
  Import a public key
``neos.flow:security:generatekeypair``
  Generate a public/private key pair and add it to the RSAWalletService



.. _`Flow Command Reference: NEOS.FLOW neos.flow:security:importpublickey`:

``neos.flow:security:importpublickey``
**************************************

**Import a public key**

Read a PEM formatted public key from stdin and import it into the
RSAWalletService.





Related commands
^^^^^^^^^^^^^^^^

``neos.flow:security:importprivatekey``
  Import a private key



.. _`Flow Command Reference: NEOS.FLOW neos.flow:security:listroles`:

``neos.flow:security:listroles``
********************************

**List all configured roles**





Options
^^^^^^^

``--include-abstract``
  Set this flag to include abstract roles





.. _`Flow Command Reference: NEOS.FLOW neos.flow:security:showeffectivepolicy`:

``neos.flow:security:showeffectivepolicy``
******************************************

**Shows a list of all defined privilege targets and the effective permissions**



Arguments
^^^^^^^^^

``--privilege-type``
  The privilege type ("entity", "method" or the FQN of a class implementing PrivilegeInterface)



Options
^^^^^^^

``--roles``
  A comma separated list of role identifiers. Shows policy for an unauthenticated user when left empty.





.. _`Flow Command Reference: NEOS.FLOW neos.flow:security:showmethodsforprivilegetarget`:

``neos.flow:security:showmethodsforprivilegetarget``
****************************************************

**Shows the methods represented by the given security privilege target**

If the privilege target has parameters those can be specified separated by a colon
for example "parameter1:value1" "parameter2:value2".
But be aware that this only works for parameters that have been specified in the policy

Arguments
^^^^^^^^^

``--privilege-target``
  The name of the privilegeTarget as stated in the policy







.. _`Flow Command Reference: NEOS.FLOW neos.flow:security:showunprotectedactions`:

``neos.flow:security:showunprotectedactions``
*********************************************

**Lists all public controller actions not covered by the active security policy**









.. _`Flow Command Reference: NEOS.FLOW neos.flow:server:run`:

``neos.flow:server:run``
************************

**Run a standalone development server**

Starts an embedded server, see http://php.net/manual/en/features.commandline.webserver.php
Note: This requires PHP 5.4+

To change the context Flow will run in, you can set the **FLOW_CONTEXT** environment variable:
*export FLOW_CONTEXT=Development && ./flow server:run*



Options
^^^^^^^

``--host``
  The host name or IP address for the server to listen on
``--port``
  The server port to listen on





.. _`Flow Command Reference: NEOS.FLOW neos.flow:session:destroyall`:

``neos.flow:session:destroyall``
********************************

**Destroys all sessions.**

This special command is needed, because sessions are kept in persistent storage and are not flushed
with other caches by default.

This is functionally equivalent to
`./flow flow:cache:flushOne Flow_Session_Storage && ./flow flow:cache:flushOne Flow_Session_MetaData`







.. _`Flow Command Reference: NEOS.FLOW neos.flow:signal:listconnected`:

``neos.flow:signal:listconnected``
**********************************

**Lists all connected signals with their slots.**





Options
^^^^^^^

``--class-name``
  if specified, only signals matching the given fully qualified class name will be shown. Note: escape namespace separators or wrap the value in quotes, e.g. "--class-name Neos\\Flow\\Core\\Bootstrap".
``--method-name``
  if specified, only signals matching the given method name will be shown. This is only useful in conjunction with the "--class-name" option.





.. _`Flow Command Reference: NEOS.FLOW neos.flow:typeconverter:list`:

``neos.flow:typeconverter:list``
********************************

**Lists all currently active and registered type converters**

All active converters are listed with ordered by priority and grouped by
source type first and target type second.



Options
^^^^^^^

``--source``
  Filter by source
``--target``
  Filter by target type





