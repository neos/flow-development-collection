Eel - Evaluated Expression Language
===================================

created by Christopher Hlubek

FlowQuery - is jQuery for PHP
=============================

created by Sebastian Kurfürst

... a selector and traversal engine for object sets.

Implements a syntax very similar to jQuery; with a subset of CSS selectors
to be used for filtering.

Syntax Examples
---------------

* `children('foo')`: Selects the sub object with name "foo"
* `filter('[attribute="value"]')`: Selects an object if it has an attribute `attribute` with value `value`
* `filter("[  attribute      =   'value'  ]")`: See above
* `filter('[attribute=  Foo.Bar  ]'`: See above; you can leave out the quotes if your value does not contain whitespace, or any of `"'[]`.
* `filter('[attribute1=value1][attribute2=value2]')`: Selects an object only if BOTH conditions match
* `filter('foo[attribute=bar]')`: object identifier and attribute selectors are both supported.
* `children('foo').children('bar')`: select the bar object inside the foo object
* `children('foo[a=b]').children('bar[c=d]')`: select the bar object inside the foo object, but only if the additional attribute matchers fit
* `filter('[a*= b]')`, `filter('[a^= b]')`, `filter('[a$= b]')`: Substring match, beginning-of-string-match, end-of-string-match
* `filter('[instanceof foo]')`: select the object if it is instance of foo
* `filter('[instanceof "foo"]')`: same as above
* `filter('[attribute instanceof object]')`: Selects an object if it has an attribute `attribute` that is an instance of `object`

Operations
----------

* `filter($filter)`
* `children($filter)`
* `first()`
* `last()`
* `attr()` (TODO)

* `get()`
* `count($filter)`
* `is($filter)`

Further documentation
---------------------

Further documentation can be found at
http://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/Eel.html

