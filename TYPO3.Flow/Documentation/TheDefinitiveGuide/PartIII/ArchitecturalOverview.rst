======================
Architectural Overview
======================

TYPO3 Flow is a PHP-based application framework. It is especially well-suited
for enterprise-grade applications and explicitly supports Domain-Driven
Design, a powerful software design philosophy. Convention over
configuration, Test-Driven Development, Continuous Integration and an
easy-to-read source code are other important principles we follow for
the development of TYPO3 Flow.

Although we created TYPO3 Flow as the foundation for the TYPO3 Content
Management System, its approach is general enough to be useful as a
basis for any other PHP application. We're happy to share the TYPO3 Flow
framework with the whole PHP community and are looking forward to the
hundreds of new features and enhancements contributed as packages by
other enthusiastic developers. In fact most of the packages which will
be developed for the TYPO3 CMS can be used in any other TYPO3 Flow-based
application. In essence this reflects the vision of the TYPO3 project:
"Inspiring People to Share".

This reference describes all features of TYPO3 Flow and provides you with
in-depth information. If you'd like to get a feeling for TYPO3 Flow and get
started quickly, we suggest that you try out our Getting Started
tutorial first.

System Parts
============

The TYPO3 Flow framework is composed of the following submodules:

* The *TYPO3 Flow Bootstrap* takes care of configuring and initializing the
  whole framework.

* The *Package* Manager allows you to download, install, configure and
  uninstall packages.

* The *Object* Manager is in charge of building, caching and combining
  objects.

* The *Configuration* framework reads and cascades various kinds of
  configuration from different sources and provides access to it.

* The *Resource* module contains functions for publishing, caching,
  securing and retrieving resources.

* The *HTTP* component is a standards-compliant implementation of a
  number of RFCs around HTTP, Cookies, content negotiation and more.

* The *MVC* framework takes care of requests and responses and provides
  you with a powerful, easy-to use Model-View-Controller
  implementation.

* The *Cli* module provides a very easy way to implement CLI commands
  using TYPO3 Flow, including built-in help based on code documentation.

* The *Cache* framework provides different kinds of caches with can be
  combined with a selection of cache backends.

* The *Error* module handles errors and exceptions and provides utility
  classes for this purpose.

* The *Log* module provides simple but powerful means to log any kind
  of event or signal into different types of backends.

* The *Signal Slot* module implements the event-driven concept of
  signals and slots through AOP aspects.

* The *Validation* module provides a validation and filtering framework
  with built-in rules as well as support for custom validation of any
  object.

* The *Property* module implements the concept of property editors and
  is used for setting and retrieving object properties.

* The *Reflection* API complements PHP's built-in reflection support by
  advanced annotation handling and a cached reflection service.

* The *AOP* framework enables you to use the powerful techniques of
  Aspect Oriented Programming.

* The *Persistence* module allows you to transparently persist your
  objects following principles of *Domain Driven Design*.

* The *Security* framework enforces security policies and provides an
  API for managing those.

* The *Session* framework takes care of session handling and storing
  session information in different backends

* The *I18n* service manages languages and other regional settings
  and makes them accessible to other packages and TYPO3 Flow sub packages.

* The *Utility* module is a library of useful general-purpose functions
  for file handling, algorithms, environment abstraction and more.

If you are overwhelmed by the amount of information in this reference,
just keep in mind that you don't need to know all of it to write your
own TYPO3 Flow packages. You can always come back and look up a specific
topic once you need to know about it - that's what references are for.

But even if you don't need to know everything, we recommend that you get
familiar with the concepts of each module and read the whole manual.
This way you make sure that you don't miss any of the great features
TYPO3 Flow provides and hopefully feel inspired to produce clean and
easy-maintainable code.
