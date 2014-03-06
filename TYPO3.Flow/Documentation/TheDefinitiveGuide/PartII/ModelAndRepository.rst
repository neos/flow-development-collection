====================
Model and Repository
====================

.. sectionauthor:: Robert Lemke <robert@typo3.org>

Usually this would now be the time to write a database schema which contains
table definitions and lays out relations between the different tables. But
TYPO3 Flow doesn't deal with tables. You won't even access a database manually nor
will you write SQL. The very best is if you completely forget about tables and
databases and think only in terms of objects.

.. tip:: **Code Examples**

    The following sections contain a lot of code which we'll go through step
    by step. To make things a little simpler, the code has been simplified a
    little, e.g. by leaving out some non-essential properties and methods.
    If you want to follow the example closely or to peek at the final code,
    check the *CheatSheet* folder.

    It contains everything explained in this tutorial, and more. To be on the
    safe side, do not copy the code explained here into new files, but rather
    copy the needed files from there to "your" sandbox project.

    To see the full-scale code of the Blog as used by some of us, take a look at
    the `Blog package <https://git.typo3.org/Packages/TYPO3.Blog.git>`_ in
    our Git repository.

Domain models are really the heart of your application and therefore it is
vital that this layer stays clean and legible. In a TYPO3 Flow application a model
is just a plain old PHP object [#]_. There's no need to write a schema
definition, subclass a special base model or implement a required interface.
All TYPO3 Flow requires from you as a specification for a model is a proper
documented PHP class containing properties.

All your domain models need a place to live. The directory structure and filenames follow
the conventions of our `Coding Guidelines
<http://flow.typo3.org/documentation/codingguidelines>`_ which basically means that the
directories reflect the classes' namespace while the filename is identical to the class
name. The base directory for the domain models is ``Classes/<VendorName>/<PackageName>/Domain/Model/``.

Blog Model
==========

The code for your ``Blog`` model can be kickstarted like this:

.. code-block:: none

	myhost:tutorial johndoe$ ./flow kickstart:model TYPO3.Blog Blog title:string \
	description:string 'posts:\Doctrine\Common\Collections\Collection'

That command will output the created file and a hint:

.. code-block:: none

	Created .../TYPO3.Blog/Classes/TYPO3/Blog/Domain/Model/Blog.php
	As a new model was generated, don't forget to update the database schema with the respective doctrine:* commands.

Open the generated file and complete it to look like the following:

*Classes/TYPO3/Blog/Domain/Model/Blog.php*:

.. code-block:: php

	...

	class Blog {

		/**
		 * The blog's title.
		 *
		 * @var string
		 * @Flow\Validate(type="Text")
		 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=80 })
		 * @ORM\Column(length=80)
		 */
		protected $title = '';

		/**
		 * A short description of the blog
		 *
		 * @var string
		 * @Flow\Validate(type="Text")
		 * @Flow\Validate(type="StringLength", options={ "maximum"=150 })
		 * @ORM\Column(length=150)
		 */
		protected $description = '';

		/**
		 * The posts contained in this blog
		 *
		 * @var \Doctrine\Common\Collections\Collection<\TYPO3\Blog\Domain\Model\Post>
		 * @ORM\OneToMany(mappedBy="blog")
		 * @ORM\OrderBy({"date" = "DESC"})
		 */
		protected $posts;

		/**
		 * Constructs a new Blog
		 */
		public function __construct() {
			$this->posts = new \Doctrine\Common\Collections\ArrayCollection();
		}

		...

		/**
		 * Adds a post to this blog
		 *
		 * @param \TYPO3\Blog\Domain\Model\Post $post
		 * @return void
		 */
		public function addPost(\TYPO3\Blog\Domain\Model\Post $post) {
			$post->setBlog($this);
			$this->posts->add($post);
		}

		/**
		 * Removes a post from this blog
		 *
		 * @param \TYPO3\Blog\Domain\Model\Post $post
		 * @return void
		 */
		public function removePost(\TYPO3\Blog\Domain\Model\Post $post) {
			$this->posts->removeElement($post);
		}

	}
	?>

*Please remove the* ``setPosts`` *method as we don't want that to be possible.*

.. tip::

	The `@Flow\…` and `@ORM\…` strings in the code are called *Annotations*.
	They are namespaced like PHP classes, so for the above code to work you
	**must** add a line like::

		use Doctrine\ORM\Mapping as ORM;

	to the files as well. Add it right after the `use` statement for the TYPO3 Flow
	annotations that is already there.

.. tip:: **Namespaces**

	Namespaces have been introduced in PHP 5.3. If you're unfamiliar with its
	funny backslash syntax you might want to have a look at the
	`PHP manual <http://php.net/manual/en/language.namespaces.php>`_.

As you can see there's nothing really fancy in it, the class mostly consists of
getters and setters. Let's take a closer look at the model line-by-line:

*Classes/TYPO3/Blog/Domain/Model/Blog.php*:

.. code-block:: php

	namespace TYPO3\Blog\Domain\Model;

This namespace declaration must be the very first code in your file.

*Classes/TYPO3/Blog/Domain/Model/Blog.php*:

.. code-block:: php

	/**
	 * A blog
	 *
	 * @Flow\Entity
	 */
	class Blog {

On the first glance this looks like a regular comment block, but it's not. This
comment contains **annotations** which are an important building block in
TYPO3 Flow's configuration mechanism.

The annotation marks this class as an entity. This is an important piece
of information for the persistence framework because it declares that

- this model is an **entity** according to the concepts of Domain-Driven
  Design
- instances of this class can be persisted (i.e. stored in the database)
- According to DDD, an entity is an object which has an identity, that
  is even if two objects with the same values exist, their identity matters.

The model's properties are implemented as regular class properties:

*Classes/TYPO3/Blog/Domain/Model/Blog.php*:

.. code-block:: php

	/**
	 * The blog's title.
	 *
	 * @var string
	 * @Flow\Validate(type="Text")
	 * @Flow\Validate(type="StringLength", options={ "minimum"=1, "maximum"=80 })
	 * @ORM\Column(length=80)
	 */
	protected $title = '';

	/**
	 * A short description of the blog
	 *
	 * @var string
	 * @Flow\Validate(type="Text")
	 * @Flow\Validate(type="StringLength", options={ "maximum"=150 })
	 * @ORM\Column(length=150)
	 */
	protected $description = '';

	/**
	 * The posts contained in this blog
	 *
	 * @var \Doctrine\Common\Collections\Collection<\TYPO3\Blog\Domain\Model\Post>
	 * @ORM\OneToMany(mappedBy="blog")
	 * @ORM\OrderBy({"date" = "DESC"})
	 */
	protected $posts;


Each property comes with a ``@var`` annotation which declares its type. Any type is fine,
be it simple types like ``string``, ``integer``, ``boolean`` or classes like ``\DateTime``
and ``\TYPO3\Foo\Domain\Model\Bar``.

The ``@var`` annotation of the ``$posts`` property differs a bit from the remaining
comments when it comes to the type. This property holds a list of ``Post`` objects
contained by this blog – in fact this could easily have been an array. However, an array
does not allow the collection to be persisted by Doctrine 2 properly. We therefore use a
``Doctrine\Common\Collections\Collection`` [#]_ instance. The class name bracketed by the
less-than and greater-than signs gives an important hint on the content of the collection
(or array). There are a few situations in which TYPO3 Flow relies on this information.

The ``OneToMany`` annotation is Doctrine 2 specific and provides more detail on the
type association a property represents. In this case it tells Doctrine that a ``Blog`` may
be associated with many ``Post`` instances, but those in turn may only belong to one
``Blog``. Furthermore the ``mappedBy`` attribute says the association is bidirectional and
refers to the property ``$blog`` in the ``Post`` class.

The ``OrderBy`` annotation is regular Doctrine 2 functionality and makes sure the
posts are always ordered by their date property when the collection is loaded.

The remaining code shouldn't hold any surprises - it only serves for setting and
retrieving the blog's properties. This again, is no requirement by TYPO3 Flow - if you don't
want to expose your properties it's fine to not define any setters or getters at all. The
persistence framework uses other ways to access the properties' values ...

We need a model for the posts as well, so kickstart it like this:

.. code-block:: none

	./flow kickstart:model --force TYPO3.Blog Post \
		'blog:\TYPO3\Blog\Domain\Model\Blog' \
		title:string \
		linkTitle:string \
		date:\DateTime \
		author:string \
		content:string

Note that we use the ``--force`` option to overwrite the model - it was created along with
the Post controller earlier because we used the ``--generate-related`` flag.

Adjust the generated code as follows:

*Classes/TYPO3/Blog/Domain/Model/Post.php*:

.. code-block:: php

	/**
	 * The blog
	 * @var \TYPO3\Blog\Domain\Model\Blog
	 * @ORM\ManyToOne(inversedBy="posts")
	 */
	protected $blog;

	...

	/**
	 * The content
	 * @var string
	 * @ORM\Column(type="text")
	 */
	protected $content;

	/**
	 * Constructs this post
	 */
	public function __construct() {
		$this->date = new \DateTime();
	}

	...

	/**
	 * Sets this Post's title
	 *
	 * @param string $title The Post's title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
		if ($this->linkTitle === '') {
			$this->linkTitle = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', str_replace(' ', '-', $title)));
		}
	}

	...

	/**
	 * Get the Post's link title
	 *
	 * @return string The Post's link title
	 */
	public function getLinkTitle() {
		if ($this->linkTitle === '') {
			$this->linkTitle = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', str_replace(' ', '-', $this->title)));
		}
		return $this->linkTitle;
	}

Blog Repository
===============

According to our earlier reasonings, you need a repository for storing the blog:

.. figure:: Images/DomainModel-3.png
	:alt: Blog Repository and Blog
	:class: screenshot-detail

	Blog Repository and Blog

A repository acts as the bridge between the holy lands of business logic
(domain models) and the dirty underground of infrastructure (data storage).
This is the only place where queries to the persistence framework take place -
you never want to have those in your domain models.

Similar to models the directory for your repositories is ``Classes/TYPO3/Blog/Domain/Repository/``.
You can kickstart the repository with:

.. code-block:: none

	myhost:tutorial johndoe$ ./flow kickstart:repository TYPO3.Blog Blog

This will generate a vanilla repository for blogs containing this code:

*Classes/TYPO3/Blog/Domain/Repository/BlogRepository.php*:

.. code-block:: php

	<?php
	namespace TYPO3\Blog\Domain\Repository;

	/*                                                                        *
	 * This script belongs to the TYPO3 Flow package "TYPO3.Blog".            *
	 *                                                                        *
	 *                                                                        */

	/**
	 * A repository for Blogs
	 *
	 * @Flow\Scope("singleton")
	 */
	class BlogRepository extends \TYPO3\Flow\Persistence\Repository {

		// add customized methods here

	}
	?>

As you see there's no code you need to write for the standard cases because
the base repository already comes with methods like ``add``, ``remove``,
``findAll``, ``findBy*`` and ``findOneBy*`` [#]_ methods.

Remember that a repository can only store one kind of an object, in this case
blogs. The type is derived from the repository name: because you named this
repository ``BlogRepository`` TYPO3 Flow assumes that it's supposed to store
``Blog`` objects.

To finish up, open the repository for our posts (which was generated along with the Post
controller we kickstarted earlier) and add the following find methods to the generated
code:

*Classes/TYPO3/Blog/Domain/Repository/PostRepository.php*:

.. code-block:: php

	/**
	 * Finds posts by the specified blog
	 *
	 * @param \TYPO3\Blog\Domain\Model\Blog $blog The blog the post must refer to
	 * @param integer $limit The number of posts to return at max
	 * @return \TYPO3\Flow\Persistence\QueryResultProxy The posts
	 */
	public function findByBlog(\TYPO3\Blog\Domain\Model\Blog $blog) {
		$query = $this->createQuery();
		return $query->matching($query->equals('blog', $blog))
			->setOrderings(array('date' => QueryInterface::ORDER_DESCENDING))
			->execute();
	}

	/**
	 * Finds the previous of the given post
	 *
	 * @param \TYPO3\Blog\Domain\Model\Post $post The reference post
	 * @return \TYPO3\Blog\Domain\Model\Post
	 */
	public function findPrevious(\TYPO3\Blog\Domain\Model\Post $post) {
		$query = $this->createQuery();
		return $query->matching($query->lessThan('date', $post->getDate()))
			->setOrderings(array('date' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING))
			->execute()
			->getFirst();
	}

	/**
	 * Finds the post next to the given post
	 *
	 * @param \TYPO3\Blog\Domain\Model\Post $post The reference post
	 * @return \TYPO3\Blog\Domain\Model\Post
	 */
	public function findNext(\TYPO3\Blog\Domain\Model\Post $post) {
		$query = $this->createQuery();
		return $query->matching($query->greaterThan('date', $post->getDate()))
			->setOrderings(array('date' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING))
			->execute()
			->getFirst();
	}

-----

.. [#]	We love to call them POPOs, similar to POJOs
		http://en.wikipedia.org/wiki/Plain_Old_Java_Object
.. [#]	http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#collections
.. [#]	``findBy*`` and ``findOneBy*`` are magic methods provided by the base
		repository which allow you to find objects by properties. The
		``BlogRepository`` for example would allow you to call magic methods
		like ``findByDescription('foo')`` or ``findOneByTitle('bar')``.