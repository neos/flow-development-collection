=================
Signals and Slots
=================

.. sectionauthor:: Karsten Dambekalns <karsten@neos.io>

The concept of *signals* and *slots* has been introduced by the Qt toolkit and allows
for easy implementation of the Observer pattern in software.

A *signal*, which contains event information as it makes sense in the case at hand, can be
emitted (sent) by any part of the code and is received by one or more *slots*, which can be
any function in Flow. Almost no registration, deregistration or invocation code need be
written, because Flow automatically generates the needed infrastructure using AOP.

Defining and Using Signals
==========================

To define a signal, simply create a method stub which starts with ``emit`` and
annotate it with a ``Signal`` annotation:

*Example: Definition of a signal in PHP* ::

    /**
     * @param Comment $comment
     * @return void
     * @Flow\Signal
     */
    protected function emitCommentCreated(Comment $comment) {}

The method signature can be freely defined to fit the needs of the event that is to be
signalled. Whatever parameters are defined will be handed over to any slots
listening to that signal.

.. note::

	The ``Signal`` annotation is picked up by the AOP framework and the method is filled
	with implementation code as needed.

To emit a signal in your code, simply call the signal method whenever it makes sense,
like in this example:

*Example: Emitting a Signal* ::

    public function createAction(Comment $newComment): void
    {
        ...
        $this->emitCommentCreated($newComment);
        ...
    }

The signal will be dispatched to all slots listening to it.

Defining Slots
==============

You can use any method as a slot, or write one specifically for use as a slot.

Using arbitrary methods as slots
--------------------------------

Any method of any class can be used as a slot, even if never written specifically
for being a slot. The only requirement is a matching signature between signal and
slot, so that the parameters passed to the signal can be handed over to the slot
without problems. The following shows a slot, as you can see it differs in no way
from any non-slot method.

*Example: A method that can be used as a slot* ::

    public function sendNewCommentNotification(Comment $comment): void
    {
        $mail = new \Neos\SwiftMailer\Message();
        $mail->setFrom(array('john@doe.org ' => 'John Doe'))
            ->setTo(array('karsten@neos.io ' => 'Karsten Dambekalns'))
            ->setSubject('New comment')
            ->setBody($comment->getContent())
            ->send();
    }

Such a method must be connected to a signal using the ``connect()`` method, and
depending on the wiring there might be an extra parameter being given to the slot that
contains signal information (see below.)


Writing explicit slot methods
-----------------------------

The other way of writing a slot is to define a method that accepts a single argument
of type ``Neos\Flow\SignalSlot\SignalInformation``.

*Example: A dedicated slot method* ::

    public function dedicatedSendNewCommentNotificationSlot(SignalInformation $signal): void
    {
        $comment = $signal->getSignalArgument('comment');
        if ($comment !== null) {
            $mail = new \Neos\SwiftMailer\Message();
            $mail->setFrom(array('john@doe.org ' => 'John Doe'))
                ->setTo(array('karsten@neos.io ' => 'Karsten Dambekalns'))
                ->setSubject('New comment')
                ->setBody($comment->getContent())
                ->send();
        }
    }

Such a method must be connected to a signal using the ``wire()`` method (see below.)

Wiring Signals and Slots Together
=================================

Which slot is actually listening for which signal is configured ("wired") in the bootstrap
code of a package. Any package can of course freely wire its own signals to its own
slots, but also wiring any other signal to any other slot is possible. You should be a
little careful when wiring your own or even other package's signals to slots in other
packages, as the results could be non-obvious to someone using your package.

When Flow initializes, it runs the ``boot()`` method in a package's ``Package`` class. This
is the place to wire signals to slots as needed for your package. This can be done using
the ``connect()`` and ``wire()`` methods, depending on the slot you want to use:

- ``connect()`` can be used with arbitrary methods as slots
- ``wire()`` is expecting a dedicated slot accepting a ``SignalInformation`` parameter

	/**
	 * Boot the package. We connect a signal to a slot here.
	 *
	 * @param \Neos\Flow\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\Neos\Flow\Core\Bootstrap $bootstrap) {
		$dispatcher = $bootstrap->getSignalSlotDispatcher();
		$dispatcher->connect(
			\Some\Package\Controller\CommentController::class, 'commentCreated',
			\Some\Package\Service\Notification::class, 'sendNewCommentNotification'
		);
	}

The first pair of parameters given to ``connect()`` and ``wire()`` identifies the signal,
the second pair of parameters identifies the slot.

The signal is identified by the class name and the signal name, which is the method name
without ``emit``. In the above example, the method which triggers the ``commentCreated``
signal is called ``emitCommentCreated()``.

The slot is identified by the class name and method name which should be called. If the
method name starts with ``::`` the slot will be called statically.

.. note::
   - Use the ``::class`` constant to specify the class name
   - The signal name is the method name **without** ``emit``

When using ``connect()``, there is one more parameter available: ``$passSignalInformation``.
It controls whether or not the signal information should be passed to the slot as the
last parameter (as a string: class name and method name of the signal emitter, separated
by ``::``.) ``$passSignalInformation`` is ``true`` by default.

.. note:: Slots with a variable number of arguments may use the signal information in
   unexpected ways. If in doubt, set ``$passSignalInformation`` to ``false``.

*Example: Wiring signals and slots together* ::

    /**
     * Boot the package. We wire some signals to slots here.
     */
    public function boot(\Neos\Flow\Core\Bootstrap $bootstrap): void
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $dispatcher->connect(
            \Some\Package\Controller\CommentController::class, 'commentCreated',
            \Some\Package\Service\Notification::class, 'sendNewCommentNotification'
        );

        $dispatcher->wire(
            \Some\Package\Controller\CommentController::class, 'commentCreated',
            \Some\Package\Service\Notification::class, 'dedicatedSendNewCommentNotificationSlot'
        );
    }

An alternative way of specifying the slot is to pass an object instance instead of a
class name to ``connect()`` or ``wire()``. This can also be used to pass a ``Closure``
instance to react to signals, in this case the slot method name can be omitted::

    $dispatcher->connect(\Acme\Com\Service::class, 'thingsChanged', function ($changedThings) {
        // do something here
    });

    $dispatcher->wire(
        \Acme\Com\Service::class,
        'thingsChanged',
        function (SignalInformation $signalInformation) {
            // do something here
        }
    );
