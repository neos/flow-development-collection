=============
Configuration
=============

.. sectionauthor:: Robert Lemke <robert@neos.io>

Configuration is an important aspect of versatile applications. Flow provides you with
configuration mechanisms which have a small footprint and are convenient to use and
powerful at the same time. Hub for all configuration is the configuration manager which
handles all configuration tasks like reading configuration, configuration cascading, and
(later) also writing configuration.

File Locations
==============

There are several locations where configuration files may be placed. All of them are
scanned by the configuration manager during initialization and cascaded into a single
configuration tree. The following locations exist (listed in the order they are loaded,
i.e. later values override prior ones):

``/Packages/<PackageDirectoryAndName>/Configuration/``
  The *Configuration* directory of each package is scanned first. Only at this stage new
  configuration options must be introduced (by defining a default value).

``/Configuration/``
  Configuration in the global *Configuration* directory overrides the default settings
  defined in the package's configuration directories.

``/Packages/<PackageDirectoryAndName>/Configuration/<ApplicationContext>/``
  There may exist a subdirectory for each application context (see Flow Bootstrap
  section). This configuration is only loaded if Flow runs in the respective
  application context.

``/Configuration/<ApplicationContext>/``
  The context specific configuration again overrides the generic settings.

The configuration manager also considers custom contexts, such as ``Production/Live``.
First, the base configuration is loaded, followed by the context specific configuration
for ``Production`` and ``Production/Live``.

Flow's configuration system does not support placing configuration files anywhere except
for in ``Configuration/`` or one of the context directories in ``Configuration/``. Flow
only supports three top-level contexts: Production, Development, and Testing. These
folders are reserved for the Flow configuration system.

Configuration Files
===================

Flow distinguishes between different types of configuration. The most important type of
configuration are the settings, however other configuration types exist for special
purposes.

The configuration format is YAML and the configuration options of each type are
defined in their own dedicated file:

``Settings.yaml``
  Contains user-level settings, i.e. configuration options the users or administrators
  are meant to change. Settings are the highest level of system configuration.
  Settings have `Split configuration sources`_ enabled.

``Routes.yaml``
  Contains routes configuration. This routing information is parsed and used by the MVC
  Web Routing mechanism. Refer to the :ref:`ch-routing` chapter for more information.

``Objects.yaml``
  Contains object configuration, i.e. options which configure objects and the
  combination of those on a lower level. See the :ref:`ch-object-management` chapter for more
  information. Objects have `Split configuration sources`_ enabled.

``Policy.yaml``
  Contains the configuration of the security policies of the system. See the :ref:`ch-security`
  chapter for details. Policy has `Split configuration sources`_ enabled.

``PackageStates.php``
  Contains a list of packages and their current state, for  example if they are active
  or not. Don't edit this file directly, rather use the *flow* command line tool do
  activate and deactivate packages.

``Caches.yaml``
  Contains a list of caches which are registered automatically. Caches defined in this
  configuration file are registered in an early stage of the boot process and profit
  from mechanisms such as automatic flushing by the File Monitor. See the chapter about
  the :ref:`ch-caching` for details.
  Caches have `Split configuration sources`_ enabled.

``Views.yaml``
  Contains configurations for Views, for example the lookup paths for templates.
  See the :ref:`ch-model-view-controller` chapter for details.

Defining Configuration
======================

Configuration Format
--------------------

The format of Flow's configuration files is YAML. YAML is a well-readable format which is
especially well-suited for defining configuration. The full specification among with many
examples can be found on the `YAML website <http://www.yaml.org/>`_. All important parts of the YAML
specification are supported by the parser used by Flow, it might happen though that some
exotic features won't have the desired effect. At best you look at the configuration files
which come with the Flow distribution for getting more examples.

**Example: a package-level Settings.yaml**

.. code-block:: yaml

    #                                                                        #
    # Settings Configuration for the Neos.Viewhelpertest Package             #
    #                                                                        #

    Neos:
      Viewhelpertest:
        includeViewHelpers: [alias, base]

        xhprof:
          rootDirectory: '' # path to the XHProf library
          outputDirectory: '%FLOW_PATH_DATA%Temporary/Viewhelpertest/XHProf/' # output directory

        profilingTemplatesDirectory: '%FLOW_PATH_DATA%Temporary/Viewhelpertest/Fluidtemplates/'


.. warning::

  Always use *two spaces* for indentation in YAML files. The parser will not
  accept indentation using tabs.

Constants and Environment
-------------------------

Sometimes it is necessary to use values in your configuration files which are defined as
PHP constants or are environment variables. These values can be included by special markers
which are replaced by the actual value during parse time. The format is ``%<CONSTANT_NAME>%``
where ``<CONSTANT_NAME>`` is the name of a constant or ``%env:<ENVIRONMENT_VARIABLE>%``.
Note that the constant or environment variable name must be all uppercase.

Some examples:

``%FLOW_PATH_WEB%``
  Will be replaced by the path to the public web directory.

``%FLOW_PATH_DATA%``
  Will be replaced by the path to the */Data/* directory.

``%PHP_VERSION%``
  Will be replaced by the current PHP version.

``%Neos\Flow\Core\Bootstrap::MINIMUM_PHP_VERSION%``
  Will be replaced by this class constant's value. Note that
  a leading namespace backslash is generally allowed as of PHP,
  but is not recommended due to CGL (stringed class names should not
  have a leading backslash).

``%env:HOME%``
  Will be replaced by the value of the "HOME" environment variable.

**Type of environment variables**

Evnironment variables are replaced via PHPs ``getenv()`` function. Thus, they always evaluate to *strings*.
Unless a mentioned environment variable does not exist, in which case it evaluates to ``false``.
With version 8.1 Flow allows to cast the type of an environment variable to an *Integer*, *Float*, *Boolean*
or *String* explicitly, specifying the *type* in the replacement string:

``%env(int):SOME_ENVIRONMENT_VARIABLE``

This would lead to the specified configuration to be casted to an integer. When the environment variable
is not defined, the base value of the specified type will be used.

The allowed types and their base values are:

* ``int``: 0
* ``bool``: false
* ``float``: 0.0
* ``string``: "" (empty string)

Custom Configuration Types
--------------------------

Custom configuration types allow to extract parts of the system configuration into
separate files.

The following will register a new type ``Views`` for configuration, using the default
configuration processing handler. The code needs to be in your ``Package``s ``boot()``
method.

**Example: Register a custom configuration type**

.. code-block:: php

    $dispatcher = $bootstrap->getSignalSlotDispatcher();
    $dispatcher->connect(\Neos\Flow\Configuration\ConfigurationManager::class, 'configurationManagerReady',
        function ($configurationManager) {
            $configurationManager->registerConfigurationType('Views');
        }
    );

This will allow to use the new configuration type ``Views`` in the same way as the other types
supported by Flow natively, as soon as you have a file named ``Views.yaml`` in your configuration
folder(s). See `Working with other configuration`_ for details.

If you want to use a custom configuration processing loader, you can pass an implementation of
``\Neos\Flow\Configuration\Loader\LoaderInterface`` when registering the configuration or use one of the implementations
found in ``Configuration\Loader``.

**Example: Register a custom configuration type and loader**

.. code-block:: php

    $dispatcher = $bootstrap->getSignalSlotDispatcher();
    $dispatcher->connect(\Neos\Flow\Configuration\ConfigurationManager::class, 'configurationManagerReady',
        function ($configurationManager) {
            $configurationManager->registerConfigurationType(
                'CustomObjects',
                new class implements LoaderInterface {
                    public function load(array $packages, ApplicationContext $context) : array {
                        // load your configuration into an array $customObjectsConfiguration
                        $customObjectsConfiguration = ...
                        return $customObjectsConfiguration;
                    }
                }
            );
        }
    );

Split configuration sources
---------------------------

For custom types it is possible to allow for *split* configuration sources. For the YAML
source used in Flow it allows to use the configuration type as a prefix for the
configuration filenames.

**Example: Register a custom configuration type, split-source**

.. code-block:: php

    $dispatcher = $bootstrap->getSignalSlotDispatcher();
    $dispatcher->connect(\Neos\Flow\Configuration\ConfigurationManager::class, 'configurationManagerReady',
        function (ConfigurationManager $configurationManager) {
            $configurationManager->registerConfigurationType(
                'Models',
                new MergeLoader(new YamlSource(), 'Models')
            );
        }
    );

The above code will lead to the following files being read, sorted by name and merged if the
configuration of type ``Models`` is requested:

.. code-block:: text

    Configuration/
        Models.yaml
        Models.Foo.yaml
        Models.Bar.yaml
        Models.Quux.yaml

.. note::
    Split configuration is supported for all configuration loader except ``RouteLoader()``.
    This is because Routing uses a custom include semantic that shares the naming convention with split sources.

Accessing Settings
==================

In almost all cases, Flow will automatically provide you with the right configuration.

What you usually want to work with are ``settings``, which are application-specific to
your package. The following example demonstrates how to let Flow inject the settings
of a classes' package and output some option value:

**Example: Settings Injection**

.. code-block:: yaml

    Acme:
      Demo:
        administrator:
          email: 'john@doe.com'
          name: 'John Doe'

.. code-block:: php

    namespace Acme\Demo;

    class SomeClass {

        /**
         * @var array
         */
        protected $settings;

        /**
         * Inject the settings
         *
         * @param array $settings
         * @return void
         */
        public function injectSettings(array $settings) {
            $this->settings = $settings;
        }

        /**
         * Outputs some settings of the "Demo" package.
         *
         * @return void
         */
        public function theMethod() {
            echo ($this->settings['administrator']['name']);
            echo ($this->settings['administrator']['email']);
        }
    }

.. note::
  Injecting all settings creates tight coupling to the settings. If you only need
  a few settings you might want to inject those specifically with the Inject
  annotation described below.

Injection of single settings into properties
--------------------------------------------

Flow provides a way to inject specific settings through the ``InjectConfiguration`` annotation directly into your
properties.
The annotation provides three optional attributes related to configuration injection:

* ``package`` specifies the package to get the configuration from. Defaults to the package the current class belongs to.
* ``path`` specifies the path to the setting that should be injected. If it's not set all settings of the current (or
* ``type`` one of the ConfigurationManager::CONFIGURATION_TYPE_* constants to define where the configuration is fetched
  from, defaults to ConfigurationManager::CONFIGURATION_TYPE_SETTINGS.

.. note::
  As a best-practice for testing and extensibility you should also provide setters for
  any setting you add to your class, although this is not required for the injection
  to work.

**Example: single setting injection**

.. code-block:: yaml

    Acme:
      Demo:
        administrator:
          name: 'John Doe'
    SomeOther:
      Package:
        email: 'john@doe.com'


.. code-block:: php

    namespace Acme\Demo;

    use Neos\Flow\Annotations as Flow;

    class SomeClass
    {

      /**
       * @Flow\InjectConfiguration(path="administrator.name")
       * @var string
       */
      protected $name;

      /**
       * @Flow\InjectConfiguration(package="SomeOther.Package", path="email")
       * @var string
       */
      protected $email;

      /**
       * @Flow\InjectConfiguration(package="SomeOther.Package")
       * @var array
       */
      protected $someOtherPackageSettings = array();

      /**
       * Overrides the name
       */
      public function setName($name): void
      {
        $this->name = $name;
      }

      /**
       * Overrides the email
       */
      public function setEmail($email): void
      {
        $this->email = $email;
      }
    }

Working with other configuration
--------------------------------

Although infrequently necessary, it is also possible to retrieve options of the more
special configuration types. The ``ConfigurationManager`` provides a method called
``getConfiguration()`` for this purpose. The result this method returns depends on the
actual configuration type you are requesting.

Bottom line is that you should be highly aware of what you're doing when working with
these special options and that they might change in a later version of Flow. Usually
there are much better ways to get the desired information (e.g. ask the Object Manager for
object configuration).

Configuration Cache
===================

Parsing the YAML configuration files takes a bit of time which remarkably slows down the
initialization of Flow. That's why all configuration is cached by default, the
configuration manager will compile all loaded configuration into a PHP file which will be
loaded in subsequent calls instead of parsing the YAML files again.

Changes to the configuration are detected and the cache is flushed when needed. In order to
flush caches manually (should that be needed), use the following command:

.. code-block:: bash

    $ ./flow flow:cache:flush

Configuration Validation
========================

Errors in configuration can lead to hard to spot errors and seemingly random
weird behavior. Flow therefore comes with a general purpose array validator
which can check PHP arrays for validity according to some schema.

This validator is used in the ``configuration:validate`` command::

  $ ./flow configuration:validate --type Settings
  Validating configuration for type: "Settings"

  16 schema files were found:
   - package:"Neos.Flow" schema:"Settings/Neos.Flow.aop" -> is valid
  …
   - package:"Neos.Flow" schema:"Settings/Neos.Flow.utility" -> is valid

  The configuration is valid!

See the command help for details on how to use the validation.

Writing Schemata
----------------

The schema format is adapted from the `JSON Schema standard <http://json-schema.org>`_;
currently the Parts 5.1 to 5.25 of the json-schema specification are implemented,
with the following deviations from the specification:

* The "type" constraint is required for all properties.
* The validator only executes the checks that make sense for a specific type,
  see list of possible constraints below.
* The "format" constraint for string type has additional class-name and
  instance-name options.
* The "dependencies" constraint of the spec is not implemented.
* Similar to "patternProperties" "formatProperties" can be specified specified
  for dictionaries

.. warning::

 While the `configuration:validate` command will stay like it is, the inner workings
 of the schema validation are still subject to change. The location of schema files
 and the syntax might be adjusted in the future, as we (and you) gather real-world
 experience with this.

 With that out of the way: feel free to create custom schemata and let us know
 of any issues you find or suggestion you have!


The schemas are searched in the path *Resources/Private/Schema* of all active
Packages. The schema-filenames must match the pattern
``<type>.<path>.schema.yaml``. The type and/or the path can also be expressed
as subdirectories of *Resources/Private/Schema*. So
*Settings/Neos/Flow.persistence.schema.yaml* will match the same paths as
*Settings.Neos.Flow.persistence.schema.yaml* or
*Settings/Neos.Flow/persistence.schema.yaml*.

Here is an example of a schema, from *Neos.Flow.core.schema.yaml*:

.. code-block:: yaml

 type: dictionary
 additionalProperties: false
 properties:
   'context': { type: string, required: true }
   'phpBinaryPathAndFilename': { type: string, required: true }

It declares the constraints for the *Neos.Flow.core* setting:

* the setting is a dictionary (an associative array in PHP nomenclature)
* properties not defined in the schema are not not allowed
* the properties ``context`` and ``phpBinaryPathAndFilename`` are both required
  and of type string

General constraints for all types (for implementation see ``validate`` method in
``SchemaValidator``):

* type
* disallow
* enum

Additional constraints allowed per type:

:string: pattern, minLength, maxLength, format(date-time|date|time|uri|email|ipv4|ipv6|ip-address|host-name|class-name|interface-name)
:number: maximum, minimum, exclusiveMinimum, exclusiveMaximum, divisibleBy
:integer: maximum, minimum, exclusiveMinimum, exclusiveMaximum, divisibleBy
:boolean: --
:array: minItems, maxItems, items
:dictionary: properties, patternProperties, formatProperties, additionalProperties
:null: --
:any: --
