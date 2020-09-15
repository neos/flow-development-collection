.. _eel:

===
Eel
===

.. sectionauthor:: Daniel Siepmann <coding@daniel-siepmann.de>

Eel stands for Embedded Expression Language and enables developers to create a Domain Specific
Language.

E.g. Neos Fusion has Eel embedded to parse some parts in combination with FlowQuery.

.. _eel-quickstart:

Quickstart
==========

The evaluation consists of two parts, the first one is the expression to evaluate. The second one is
the context needed to evaluate the expression.

An expression can be something like::

    'foo.bar == "Test" || foo.baz == "Test" || reverse(foo).bar == "Test"'

To enable this expression to be parsed, a context is needed to define ``foo.bar``, ``foo.baz`` and
``reverse()``.

Basically a context is nothing more then an array defining the parts as key value pairs. The above
will need a context like::

    [
        'foo' => [
            'bar' => 'Test1',
            'baz' => 'Test2',
        ],
        'reverse' => function ($array) {
            return array_reverse($array, true);
        },
    ]


To parse the above expression the following code can be used::

        $expression = 'foo.bar == "Test" || foo.baz == "Test" || reverse(foo).bar == "Test"';
        $context = new Context([
            'foo' => [
                'bar' => 'Test1',
                'baz' => 'Test2',
            ],
            'reverse' => function ($array) {
                return array_reverse($array, true);
            }
        ]);
        $result = (new CompilingEvaluator)->evaluate($expression, $context);

In the above example ``$result`` will be a boolean. But the result depends on the expression and can
be of any type.

.. _eel-context-types:

Context Types
=============

Two context types are available.

``Context`` will just provide everything you put into the array for the constructor.

``ProtectedContext`` will provide the same, except that methods are disallowed by default. You need
to explicitly whitelist methods::

    $context = new ProtectedContext([
        'String' => new \Neos\Eel\Helper\StringHelper,
    ]);
    $context->whitelist('String.*');
    $result = (new CompilingEvaluator)->evaluate(
        'String.substr("Hello World", 6, 5)',
        $context
    );

In the above example, all methods for ``String`` are whitelisted and therefore the result will be
``"World"``.

In case a non whitelisted method is called, a ``\Neos\Eel\NotAllowedException`` is thrown.

.. _eel-evaluators:

Evaluators
==========

Two evaluator types are available.

``CompilingEvaluator`` will generate PHP Code for expression and cache the expressions.

``InterpretedEvaluator`` will not generate PHP Code and evaluate the expression every time. That's
useful if you are using Eel outside of Flow context.

.. _eel-helpers:

Helpers
=======

Helpers provide convenient features like working with math, strings, arrays and dates. Each helper
is implemented as a class. No helpers are available out of the box while parsing an expression. To
include helpers add them to the context, e.g. ::

    $context = new Context([
        'String' => new \Neos\Eel\Helper\StringHelper,
    ]);
    $result = (new CompilingEvaluator)->evaluate(
        'String.substr("Hello World", 6, 5)',
        $context
    );

The package comes with some predefined helpers you can include in your context. A full,
auto generated, list of helpers can be found at Neos :ref:`neos:Eel Helpers Reference`.

.. _eel-grammar:

Grammar
=======

The full grammar can be found at `the Eel repository
<https://github.com/neos/eel/blob/master/Documentation/FLOW3-Eel-Grammar.txt>`_.
