=========================
Essential Design Patterns
=========================

.. sectionauthor:: Robert Lemke <robert@typo3.org>, Ryan J. Peterson <ryan@mathusee.com>


TYPO3 Flow Paradigm
===================

TYPO3 Flow was designed from the ground up to be modular, adaptive and agile to
enable developers of all skill levels to build maintainable, extensible and
robust software through the implementation of several proven design paradigms.
Building software based on these principles will allow for faster, better
performing applications that can be extended to meet changing requirements while
avoiding inherent problems introduced by traditional legacy code maintenance.
TYPO3 Flow aims to make what you "should" do what you "want" to do by providing the
framework and community around best practices in the respective essential design
patterns.


Aspect-Oriented Programming
===========================

Aspect-Oriented Programming (AOP) is a programming paradigm which complements
Object-Oriented Programming (OOP) by separating concerns of a software
application to improve modularization. The separation of concerns (SoC) aims for
making a software easier to maintain by grouping features and behavior into
manageable parts which all have a specific purpose and business to take care of.

OOP already allows for modularizing concerns into distinct methods, classes and
packages. However, some concerns are difficult to place as they cross the
boundaries of classes and even packages. One example for such a cross-cutting
concern is security: Although the main purpose of a Forum package is to display
and manage posts of a forum, it has to implement some kind of security to assert
that only moderators can approve or delete posts. And many more packages need a
similar functionality for protect the creation, deletion and update of records. .
AOP enables you to move the security (or any other) aspect into its own package
and leave the other objects with clear responsibilities, probably not
implementing any security themselves.

.. note::
 Planning out the purpose and use cases of a package before you create it will
 allow for backwards compatibility by creating an unchanging interface for
 independent classes to consume.


Dependency Injection
====================

In AOP there is focus on building reusable components that can be wired together
to create a cohesive architecture. This goal becomes increasingly difficult
because as the size and complexity of an application expands, so does its
dependencies. One technique to aliviate dependancy management is through
Dependency Injection (DI).

Dependency Injection (DI) is a technique by which a package can request and gain
access to another package simply by asking the injector. An injector is the
service provided within a framework to instantiate and provide access to
package interfaces upon request.

DI enables a package to control what dependencies it requires while allowing the
framework or another third party system to handle the fullfillment of each
dependancy. This is know as Inversion of Control (IoC). IoC delegates the
responsibility of dependency resolution to the framework while each package
specifies which dependencies it needs.

AOP provides a means for interaction between packages through various interfaces
and aspect. Without Dependency Injection AOP would suffer from creating
untestable code by requiring you to manage dependencies in the constructor
and thus breaking the Law of Demeter by allowing a package to "look" for
its dependencies with a system instead of "asking" for them through the
autonomous injector.


Test Driven Development
=======================

Test Driven Development (TDD) is a means in which a developer can explore,
implement and verify various independent pieces of an application in order to
deliver stable and maintainable code. TDD has become popular in mainstream
development because the first step required is to think about what the purpose
of a class or method is in the scope of your package's feature requirements
incrementally, revising and refining small pieces of code while maintaining
overall integrity of the system as whole.

Five Steps of Test Driven Development
-------------------------------------

1. **Think**: Before you write anything, consider what is required of the code
   you are about to create.

2. **Frame**: Write the simplest test possible, less than five lines of code or
   so that describe what you expect the method to do.

3. **Fulfill**: Again, write a small amount of code to meet the expectations of
   your test so that is passes. (It's acceptable to hard code variables and
   returns as you explore and think about the method, cleaning it up as you go.)

4. **Re-factor**: Now that you have a simple passing test, you know that your
   code as it stands works and can work on making it better while keeping an
   eye on if it breaks of not. Think about ways to improve your code by removing
   duplication and other "ugly" code until you feel it looks correct. Re-run the
   tests and make sure it still passes, if not, fix it.

5. **Repeat**: Do it again. Look at your test to make sure you are testing what
   it should do, not what it is doing. Add to your test if you find something
   missing and continue looping through the process until you're happy that the
   code can't be made any clearer with its current set of requirements. The more
   times you repeat, the better the resulting code will be.


Domain Driven Design
====================

Domain-driven Design (DDD) is a practice where an implementation is deeply
coupled with the evolving business model within its respective domain.
Typically when working with DDD, technical experts are paired with a domain
experts to ensure that each iteration of a system is getting closer to the core
problem.

DDD relies on the following foundational elements:
     * **Domain**: An ontology of concepts related to a specific area of
       knowledge and information.
     * **Model**: An abstract system that describes the various aspects of a
       domain.
     * **Ubiquitous Language**: A glossary of language structured around a
       domain model to connect all aspects of a model with uniformed definitions.
     * **Context**: The relative position in which an expression of words are
       located that determine it's overall meaning.

In DDD the Domain Model that is formed is a guide or measure of the overall
implementation of an applications relationship to the core requirements of the
problem it is trying to solve. DDD is not a specific technique or way of
developing software, it is a system to ensure that the desired result and end
result of a development iteration or aligned. For this reason, DDD is often
coupled with TDD and AOP.