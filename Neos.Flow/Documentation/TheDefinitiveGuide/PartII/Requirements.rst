============
Requirements
============

Flow is being developed and tested on multiple platforms and pretty easy to set
up. Nevertheless we recommend that you go through the following list before installing
Flow, because a server with exotic *php.ini* settings or wrong file permissions can
easily spoil your day.

Server Environment
==================

Not surprisingly, you'll need a web server for running your Flow-based web
application. We recommend *Apache* (though *nginx*, *IIS* and others work too – we just
haven't really tested them). Please make sure that the
`mod_rewrite <http://httpd.apache.org/docs/current/mod/mod_rewrite.html>`_ module is
enabled.

.. tip::

	To enable Flow to create symlinks on Windows Server 2008 and higher you need
	to do some extra configuration. In IIS you need to configure `Authentication` for
	your site configuration to use a specific user in the `Anonymous Authentication`
	setting. The configured user should also be allowed to create symlinks using the
	local security policy `Local Policies > User Rights Assignments > Create symbolic links`

Flow's persistence mechanism requires a `database supported by Doctrine DBAL
<http://www.doctrine-project.org/projects/dbal.html>`_. Make sure to use at least 10.2.2
for MariaDB, and 5.7.7 when using MySQL.

PHP
===

Flow was one of the first PHP projects taking advantage of namespaces and
other features introduced in PHP version 5.3. By now we started using features of
PHP 7.3, so make sure you have **PHP 7.3.0** or later available on your web server. Make
sure your PHP CLI binary is the **same version**!

The default settings and extensions of the PHP distribution should work fine
with Flow but it doesn't hurt checking if the PHP modules ``mbstring``, ``tokenizer``
and ``pdo_mysql`` are enabled, especially if you compiled PHP yourself.

.. note::

  Make sure the PHP functions ``exec()``, ``shell_exec()``,
  ``escapeshellcmd()`` and ``escapeshellarg()`` are not disabled in you PHP
  installation. They are required for the system to run.

The development context might need more than the default amount of memory.
At least during development you should raise the memory limit to about 250 MB
in your *php.ini* file.

In case you get a fatal error message saying something like ``Maximum function nesting
level of '100' reached, aborting!``, check your *php.ini* file for settings regarding
Xdebug and modify/add a line ``xdebug.max_nesting_level = 500`` (suggested value).
