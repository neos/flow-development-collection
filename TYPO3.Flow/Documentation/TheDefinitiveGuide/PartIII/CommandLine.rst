
============
Command Line
============

TYPO3 Flow features a clean and powerful interface for the command line which allows
for automated and manual execution of low-level or application-specific tasks.
The command line support is available on all platforms generally supported by
TYPO3 Flow.

This chapter describes how to use the help system, how to run existing
commands and how to implement your own custom commands.

Wrapper Script
--------------

TYPO3 Flow uses two platform specific wrapper scripts for running the actual
commands:

* *flow.bat* is used on Windows machines
* *flow* is used on all other platforms

Both files are located and must be run from the main directory of the TYPO3 Flow
installation. The command and further options are passed as arguments to the
respective wrapper script.

In the following examples we refer to these wrapper scripts just as "the *flow*
script".

.. tip::

	If you are a Windows user and use a shell like `msysGit`_, you can mostly
	follow the Unix style examples and use the *flow* script instead of
	*flow.bat*.

Help System
-----------

Without specifying a command, the *flow* script responds by displaying
the current version number and the current context:

.. code-block:: none

	$ ./flow
	TYPO3 Flow 2.x.x ("Development" context)
	usage: ./flow <command identifier>

	See "./flow help" for a list of all available commands.

In addition to the packages delivered with the TYPO3 Flow core, third-party packages
may provide any number of custom commands. A list of all currently available
commands can be obtained with the *help* command:

.. code-block:: none

	$ ./flow help
	TYPO3 Flow 2.x.x ("Development" context)
	usage: ./flow <command identifier>

	The following commands are currently available:

	PACKAGE "TYPO3.Flow":
	----------------------------------------------------------------------------
	* flow:cache:flush                         Flush all caches
	  cache:warmup                             Warm up caches

	  configuration:show                       Show the active configuration
	                                           settings
	  configuration:validate                   Validate the given configuration
	  configuration:generateschema             Generate a schema for the given
	                                           configuration or YAML file.
	...

A list of all commands in a specific package can be obtained by giving the
package key part of the command to the *help* command:

.. code-block:: none

	$ ./flow help kickstart
	5 commands match the command identifier "typo3.kickstart":

	PACKAGE "TYPO3.KICKSTART":
	-------------------------------------------------------------------------------
	kickstart:package                        Kickstart a new package
	kickstart:actioncontroller               Kickstart a new action controller
	kickstart:commandcontroller              Kickstart a new command controller
	kickstart:model                          Kickstart a new domain model
	kickstart:repository                     Kickstart a new domain repository

Further details about specific commands are available by specifying the
respective command identifier:

.. code-block:: none

	$ ./flow help configuration:show


	Show the active configuration settings

	COMMAND:
	  typo3.flow:configuration:show

	USAGE:
	  ./flow configuration:show [<options>]

	OPTIONS:
	  --type               Configuration type to show
	  --path               path to subconfiguration separated by "." like
	                       "TYPO3.Flow

	DESCRIPTION:
	  The command shows the configuration of the current context as it is used by TYPO3 Flow itself.
	  You can specify the configuration type and path if you want to show parts of the configuration.

	  ./flow configuration:show --type Settings --path TYPO3.Flow.persistence

Running a Command
-----------------

Commands are uniquely identified by their *command identifier*. These come in
two variants: a long and a short version.

Fully Qualified Command Identifier
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A fully qualified command identifier is the combination of the package key, the
command controller name and the actual command name, separated by colons:

The command "warmup" implemented by the "CacheCommandController" contained
in the package "TYPO3.Flow" is referred to by the command identifier
*typo3.flow:cache:warmup*.

Short Command Identifier
~~~~~~~~~~~~~~~~~~~~~~~~

In order to save some typing, most commands can be referred to by a shortened
command identifier. The *help* command lists all commands by the shortest
possible identifier which is still unique across all available commands.

For example, the command "warmup" implemented by the "CacheCommandController"
contained in the package "TYPO3.Flow" can also be referred to by the command
identifier *cache:warmup* as long as no other package provides a command
with the same name.

Some special commands can only by referred to by their fully qualified
identifier because they are invoked at a very early stage when the command
resolution mechanism is not yet available. These *Compile Time Commands* are
marked by an asterisk in the list of available commands (see
:ref:`Runtime and Compile Time` for some background information).

Passing Arguments
-----------------

Arguments and options can be specified for a command in the same manner they
are passed to typical Unix-like commands. A list of required arguments and
further options can be retrieved through the *help* command.

Options
~~~~~~~

Options listed for a command are optional and only have to be specified if
needed. Options must always be passed before any arguments by using their
respective name:

.. code-block:: bash

	./flow foo:bar --some-option BAZ --some-argument QUUX

If an option expects a boolean type (that is, yes/no, true/false, on/off
would be typical states), just specifying the option name is sufficient
to set the option to *true*:

.. code-block:: bash

	./flow foo:bar --force

Alternatively the boolean value can be specified explicitly:

.. code-block:: bash

	./flow foo:bar --force TRUE
	./flow foo:bar --force FALSE

Possible values equivalent to *TRUE* are: *on*, *1*, *y*, *yes*, *true*.
Possible values equivalent to *FALSE* are: *off*, *0*, *n*, *no*, *false*.

Arguments
~~~~~~~~~

The arguments listed for a command are mandatory. They can either be specified
by their name or without an argument name. If the argument name is omitted, the
argument values must be provided in the same order like in the help screen of
the respective command. The following two command lines are synonymic:

.. code-block:: bash

	./flow kickstart:actioncontroller --force --package-key Foo.Bar --controller-name Baz
	./flow kickstart:actioncontroller --force Foo.Bar Baz

Contexts
--------

If not configured differently by the server environment, the *flow* script is
run in the *Development* context by default. It is recommended to set the
*FLOW_CONTEXT* environment variable to *Production* on a production server –
that way you don't execute commands in an unintended context accidentally.

If you usually run the *flow* script in one context but need to call it in
another context occasionally, you can do so by temporarily setting the
respective environment variable for the single command run:

.. code-block:: bash

	FLOW_CONTEXT=Production ./flow flow:cache:flush

In a Windows shell, you need to use the *SET* command:

.. code-block:: bash

	SET FLOW_CONTEXT=Production
	flow.bat flow:cache:flush

Implementing Custom Commands
----------------------------

A lot of effort has been made to make the implementation of custom commands a
breeze. Instead of writing configuration which registers commands or coming up
with files which provide the help screens, creating a new command is only a
matter of writing a simple PHP method.

A set of commands is bundled in a *Command Controller*. The individual commands
are plain PHP methods with a name that ends with the word "Command". The concrete
command controller must be located in a "Command" namespace right below the
package's namespace.

The following example illustrates all the code necessary to introduce a new
command:

.. code-block:: php

	namespace Acme\Demo\Command;
	use TYPO3\Flow\Annotations as Flow;

	/**
	 * @Flow\Scope("singleton")
	 */
	class CoffeeCommandController extends \TYPO3\Flow\Cli\CommandController {

		/**
		 * Brew some coffee
		 *
		 * This command brews the specified type and amount of coffee.
		 *
		 * Make sure to specify a type which best suits the kind of drink
		 * you're aiming for. Some types are better suited for a Latte, while
		 * others make a perfect Espresso.
		 *
		 * @param string $type The type of coffee
		 * @param integer $shots The number of shots
		 * @param boolean $ristretto Make this coffee a ristretto
		 * @return string
		 */
		public function brewCommand($type, $shots=1, $ristretto=FALSE) {
			# implementation
		}
	}

The new controller and its command is detected automatically and the help screen
is rendered by using the information provided by the method code and DocComment:

* the first line of the DocComment contains the short description of the command
* the second line must be empty
* the the following lines contain the long description
* the descriptions of the @param annotations are used for the argument
  descriptions
* the type specified in the @param annotations is used for validation and to
  determine if the argument is a flag (boolean) or not
* the parameters declared in the method set the parameter names and tell if they
  are arguments (mandatory) or options (optional)

The above example will result in a help screen similar to this:

.. code-block:: none

	$ ./flow help coffee:brew

	Brew some coffee

	COMMAND:
	  acme.demo:coffee:brew

	USAGE:
	  ./flow coffee:brew

	DESCRIPTION:
	  This command brews the specified type and amount of coffee.

	  Make sure to specify a type which best suits the kind of drink
	  you're aiming for. Some types are better suited for a Latte, while
	  others make a perfect Espresso.

Handling Exceeding Arguments
----------------------------

Any arguments which are passed additionally to the mandatory arguments
are considered to be *exceeding arguments*. These arguments are not
parsed nor validated by TYPO3 Flow.

A command may use exceeding arguments in order to process an
variable amount of parameters. The exceeding arguments can be retrieved
through the *Request* object as in the following example:

.. code-block:: php

	/**
	 * Process words
	 *
	 * This command processes the given words.
	 *
	 * @param string $operation The operation to execute
	 * @return string
	 */
	public function processWordCommand($operation = 'uppercase') {
		$words = $this->request->getExceedingArguments();
		foreach ($words as $word) {
			...
		}
		...
	}

A typical usage of the command above may look like this:

.. code-block:: none

	$ ./flow foo:processword --operation lowercase These Are The Words

	these are the words

See Other and Deprecated Commands
---------------------------------

A command's help screen can contain additional information about relations
to other commands. This information is triggered by specifying one or more
*@see* annotations in the command's doc comment block as follows:

.. code-block:: php

	/**
	 * Drink juice
	 *
	 * This command provides some way of drinking juice.
	 *
	 * @return string
	 * @see acme.demo:drink:coffee
	 */
	public function juiceCommand() {
		...
	}

By adding a *@deprecated* annotation, the respective command will be marked
as deprecated in all help screens and a warning will be displayed when
executing the command. If a *@see* annotation is specified, the deprecation
message additionally suggests to use the command mentioned there.

.. code-block:: php

	/**
	 * Drink tea
	 *
	 * This command urges you to drink tea.
	 *
	 * @return string
	 * @deprecated since 2.8.18
	 * @see acme.demo:drink:coffee
	 */
	public function teaCommand() {
		...
	}


Generating Styled Output
------------------------

The output sent to the user can be processed in three different ways,
each denoted by a PHP constant:

* OUTPUTFORMAT_RAW sends the output as is
* OUTPUTFORMAT_PLAIN tries to convert the output into plain text by
  stripping possible tags
* OUTPUTFORMAT_STYLED sends the output as is but converts certain tags
  into ANSI codes

The output format can be set by calling the *setOutputFormat()* method
on the command controller's *Response* object:

.. code-block:: php

	/**
	 * Example Command
	 *
	 * @return string
	 */
	public function exampleCommand() {
		$this->response->setOutputFormat(Response::OUTPUTFORMAT_RAW);
		$this->response->appendContent(...);
	}

A limited number of tags are supported for brushing up the output in
OUTPUTFORMAT_STYLED mode. They have the following meaning:

+------------------------+---------------------------------------------------------------------------+
| Tag                    | Meaning                                                                   |
+========================+===========================================================================+
| ``<b>…</b>``           | Render the text in a bold / bright style                                  |
+------------------------+---------------------------------------------------------------------------+
| ``<i>…</i>``           | Render the text in a italics                                              |
+------------------------+---------------------------------------------------------------------------+
| ``<u>…</u>``           | Underline the given text                                                  |
+------------------------+---------------------------------------------------------------------------+
| ``<em>…</em>``         | Emphasize the text, usually by inverting foreground and background colors |
+------------------------+---------------------------------------------------------------------------+
| ``<strike>…</strike>`` | Display the text struck through                                           |
+------------------------+---------------------------------------------------------------------------+

The respective styles are only rendered correctly if the console
supports ANSI styles. You can check ANSI support by calling the
response's *hasColorSupport()* method. Contrary to what that method
name suggests, at the time of this writing colored output is not
directly supported by TYPO3 Flow. However, such a feature is planned
for the future.

.. tip::

	The tags supported by TYPO3 Flow can also be used to style the
	description of a command in its DocComment.

.. _Runtime and Compile Time:

Symfony/Console Methods
-----------------------

The CommandController makes use of Symfony/Console internally and
provides various methods directly from the CommandController's ``output`` member:

* TableHelper

	* outputTable($rows, $headers = NULL)

* DialogHelper

	* select($question, $choices, $default = NULL, $multiSelect = false, $attempts = FALSE)
	* ask($question, $default = NULL, array $autocomplete = array())
	* askConfirmation($question, $default = TRUE)
	* askHiddenResponse($question, $fallback = TRUE)
	* askAndValidate($question, $validator, $attempts = FALSE, $default = NULL, array $autocomplete = NULL)
	* askHiddenResponseAndValidate($question, $validator, $attempts = FALSE, $fallback = TRUE)

* ProgressHelper

	* progressStart($max = NULL)
	* progressSet($current)
	* progressAdvance($step = 1)
	* progressFinish()

Here's an example showing of some of those functions:

.. code-block:: php

	namespace Acme\Demo\Command;

	use TYPO3\Flow\Annotations as Flow;
	use TYPO3\Flow\Cli\CommandController;

	/**
	 * @Flow\Scope("singleton")
	 */
	class MyCommandController extends CommandController {

		/**
		 * @return string
		 */
		public function myCommand() {
			// render a table
			$this->output->outputTable(array(
				array('Bob', 34, 'm'),
				array('Sally', 21, 'f'),
				array('Blake', 56, 'm')
			),
			array('Name', 'Age', 'Gender'));

			// select
			$colors = array('red', 'blue', 'yellow');
			$selectedColorIndex = $this->output->select('Please select one color', $colors, 'red');
			$this->outputLine('You choose the color %s.', array($colors[$selectedColorIndex]));

			// ask
			$name = $this->output->ask('What is your name?' . PHP_EOL, 'Bob', array('Bob', 'Sally', 'Blake'));
			$this->outputLine('Hello %s.', array($name));

			// prompt
			$likesDogs = $this->output->askConfirmation('Do you like dogs?');
			if ($likesDogs) {
				$this->outputLine('You do like dogs!');
			}

			// progress
			$this->output->progressStart(600);
			for ($i = 0; $i < 300; $i ++) {
				$this->output->progressAdvance();
				usleep(5000);
			}
			$this->output->progressFinish();

		}
	}

Runtime and Compile Time
------------------------

The majority of the commands are run at point when TYPO3 Flow is fully
initialized and all of the framework features are available. However,
for certain low-level operations it is desirable to execute code
much earlier in the boot process – during *compile time*. Commands
like *typo3.flow:cache:flush* or the internal compilation commands
which render the PHP proxy classes cannot rely on a fully initialized
system.

It is possible – also for custom commands – to run commands run during
compile time. The developer implementing such a command must have a
good understanding of the inner workings of the bootstrap and parts
of the proxy building, because compile time has several limitations,
including but not limited to the following:

* dependency injection does not support property injection
* aspects are not yet active
* persistence is not yet enabled
* certain caches have not been built yet

In general, all functionality which relies on proxy classes will not
be available during compile time.

If you are sure that compile time is the right choice for your command,
you can register it as a compile time command by running an API method
in the *boot()* method of your package's *Package* class:

.. code-block:: php

	namespace Acme\Foo;
	use TYPO3\Flow\Package\Package as BasePackage;

	/**
	 * Acme.Foo Package
	 */
	class Package extends BasePackage {

		/**
		 * Invokes custom PHP code directly after the package manager has been initialized.
		 *
		 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap
		 * @return void
		 */
		public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
			$bootstrap->registerRequestHandler(new \Acme\Foo\Command\MyCommandController($bootstrap));
		}
	}

For more details you are encouraged to study the implementation of
TYPO3 Flow's own compile time commands.

Executing Sub Commands
----------------------

Most command methods are designed to be called exclusively through the
command line and should not be invoked internally through a PHP method
call. They may rely on a certain application state, some exceeding
arguments provided through the *Request* object or simply are compile
time commands which must not be run from runtime commands.
Therefore, the safest way to let a command execute a second command
is through a PHP sub process.

The PHP bootstrap mechanism provides a method for executing arbitrary
commands through a sub process. This method is located in the *Scripts*
class and can be used as follows:

.. code-block:: php

	/**
	 * Some command
	 *
	 * This example command runs another command
	 *
	 * @return string
	 */
	public function runCommand($packageKey) {
		\TYPO3\Flow\Core\Booting\Scripts::executeCommand('acme.foo:bar:baz', $this->settings);
	}

Quitting and Exit Code
----------------------

Commands should not use PHP's *exit()* or *die()* method but rather let
TYPO3 Flow's bootstrap perform a clean shutdown of the framework. The base
*CommandController* provides two API methods for initiating a shutdown
and optionally passing an exit code to the console:

* *quit($exitCode)* stops execution right after this command, performs a clean shutdown of TYPO3 Flow.
* *sendAndExit($exitCode)* sends any output buffered in the *Response* object and exits immediately, without shutting down TYPO3 Flow.

The *quit()* method is the recommended way to exit TYPO3 Flow. The other
command, *sendAndExit()*, is reserved for special cases where TYPO3 Flow
is not stable enough to continue even with the shutdown procedure. An
example for such a case is the *typo3.flow:cache:flush* command which
removes all cache entries which requires an immediate exit because
TYPO3 Flow relies on caches being intact.

.. _msysGit: http://msysgit.github.io
