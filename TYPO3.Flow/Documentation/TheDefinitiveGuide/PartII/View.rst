====
View
====

.. sectionauthor:: Robert Lemke <robert@typo3.org>

The view's responsibility is solely the visual presentation of data provided by
the controller. In TYPO3 Flow views are cleanly decoupled from the rest of the MVC
framework. This allows you to either take advantage of Fluid (TYPO3 Flow's template
engine), write your own custom PHP view class or use almost any other template
engine by writing a thin wrapper building a bridge between TYPO3 Flow's interfaces
and the template engine's functions. In this tutorial we focus on Fluid-based
templates as this is what you usually want to use.

Resources
=========

Before we design our first Fluid template we need to spend a thought on the
resources our template is going to use (I'm talking about all the images, style
sheets and javascript files which are referred to by your HTML code).
You remember that only the ``Web`` directory is accessible from the web, right?
And the resources are part of the package and thus hidden from the public.
That's why TYPO3 Flow comes with a powerful resource manager whose main task is to
manage access to your package's resources.

The deal is this: All files which are located in the **public resources directory**
of your package will automatically be mirrored to the public resources
directory below the ``Web`` folder. Let's take a look at the directory layout of
the *Blog* package:

.. table:: Directory structure of a TYPO3 Flow package

	======================	============================================================
	Directory				Description
	======================	============================================================
	*Classes/*				All the .php class files of your package
	*Documentation/*		The package's manual and other documentation
	*Meta/*					*Package.xml* and other package meta information
	*Resources/*			Top folder for resources
	*Resources/Public/*		Public resources - will be mirrored to the *Web* directory
	*Resources/Private/*	Private resources - won't be mirrored to the *Web* directory
	======================	============================================================


No matter what files and directories you create below ``Resources/Public/`` - all
of them will be symlinked to ``Web/_Resources/Static/Packages/TYPO3.Blog/`` on
the next hit.

.. tip::
 	There are more possible directories in a package and we do have some
 	conventions for naming certain sub directories. All that is explained in
 	fine detail in the `TYPO3 Flow reference manual <http://flow.typo3.org/documentation/>`_.

.. important::
	For the blog example in this tutorial we created some style sheets
	and icons. If you'd like to brush up the following examples a little, then
	it's now time to copy ``Resources/Public/`` in the *CheatSheet* to your
	blog's public resources folder
	(``Packages/Application/TYPO3.Blog/Resources/Public/``).

Layouts
=======

Fluid knows the concepts of layouts, templates and partials. Usually all of
them are just plain HTML files which contain special tags known by the Fluid
template view. The following figure illustrates the use of layout, template and
partials in our blog example:

.. figure:: /Images/GettingStarted/LayoutTemplatePartial.png

	Layout, Template and Partial


A Fluid layout provides the basic layout of the output which is supposed to be
shared by multiple templates. You will use the same layout throughout this
tutorial - only the templates will change depending on the current controller
and action. Elements shared by multiple templates can be extracted as a partial
to assure consistency and avoid duplication.

Let's build a simple layout for your blog. You only need to adjust the file called
``Default.html`` inside the ``TYPO3.Blog/Resources/Private/Layouts`` directory to contain
the following code:

*Resources/Private/Layouts/Default.html*:

.. code-block:: xml

	<!DOCTYPE html
		 PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<f:base />
			<title>{blog.title}</title>
			<link rel="stylesheet" href="{f:uri.resource(path: 'Blog.css')}" type="text/css" media="all" />
		</head>
		<body>
			<div id="header">
				<f:if condition="{blog}">
					<f:link.action action="index" controller="Post">
						<h1 class="title">{blog.title}</h1></f:link.action>
					<p class="description">{blog.description}</p>
				</f:if>
			</div>
			<div id="maincontainer">
				<div id="mainbox"><f:render section="mainbox" /></div>
				<div class="clear"></div>
			</div>
			<div id="footer">
				<a href="http://flow.typo3.org">Powered by TYPO3 Flow
					<img src="{f:uri.resource(path: 'TYPO3-Flow-Logo-11px.png')}" width="11" height="11" />
				</a>
			</div>
		</body>
	</html>

On first sight this looks like plain HTML code, but you'll surely notice the
various ``<f: ... >`` tags. Fluid provides a range of view helpers which are
addressed by these tags. By default they live in the ``f`` namespace resulting
in tags like ``<f:base>`` or ``<f:if>``. You can define your own namespaces
and even develop your own view helpers, but for now let's look at what you used
in your layout:

The first Fluid tag used is the ``<f:base>`` tag. This tag instructs Fluid to
render an HTML ``<base>`` tag containing the correct absolute base URI for your
site – in your case resulting in:

*Resources/Private/Layouts/Default.html*:

.. code-block:: html

	<base href="http://dev.tutorial.local/"></base>

The second occurrence of Fluid markup is actually no tag but a
variable accessor:

*Resources/Private/Layouts/Default.html*:

.. code-block:: html

	<title>{blog.title}</title>

As you will see in a minute, Fluid allows your controller to define variables
for the template view. In order to display the blog's name, you'll need to make
sure that your controller assigns the current ``Blog`` object to the template
variable ``blog``. The value of such a variable can be inserted anywhere in
your layout, template or partial by inserting the variable name wrapped by
curly braces. However, in the above case ``blog`` is not a value you can output
right away – it's an object. Fortunately Fluid can display properties of an
object which are accessible through a getter function: to display the blog
title, you just need to note down ``{blog.title}``.

The third appearance of Fluid syntax is an alternative way to address view
helpers, the view helper shorthand syntax:

*Resources/Private/Layouts/Default.html*:

.. code-block:: html

	<link rel="stylesheet" href="{f:uri.resource(path: 'Blog.css')}" type="text/css" />

This instructs the URI view helper to create a relative resource URL pointing
to your style sheet. The generated HTML code will look like this:

*Resources/Private/Layouts/Default.html*:

.. code-block:: html

	<link rel="stylesheet" href="Resources/Packages/TYPO3.Blog/Blog.css" type="text/css" />

If you look at the remaining markup of the layout you'll find more uses of view
helpers, including conditions and link generation. There's only one more view
helper you need to know about before proceeding with our first template,
the **render** view helper:

*Resources/Private/Layouts/Default.html*:

.. code-block:: html

	<f:render section="mainbox" />

This tag tells Fluid to insert the section ``mainbox`` defined in the current
template at this place. For this to work there must be a section with the
specified name in the template referring to the layout – because that's the way
it works: A template declares on which layout it is based on, defines sections
which in return are included by the layout. Confusing? Let's look at a
concrete example.

Templates
=========

Templates are, as already mentioned, tailored to a specific action. The action
controller chooses the right template automatically according to the current
package, controller and action - if you follow the naming conventions. Let's
replace the automatically generated template for the Post controller's index
action in ``TYPO3.Blog/Resources/Private/Templates/Post/Index.html`` by some more
meaningful HTML:

*Resources/Private/Templates/Post/Index.html*:

.. code-block:: xml

	<f:layout name="Default" />

	<f:section name="mainbox">
		<f:flashMessages class="flashmessages" />
		<f:if condition="{posts}">
			<f:then>
				<div id="navigation">
					<span class="buttons"><f:link.action action="new" controller="Post"><img src="{f:uri.resource(path: 'Icons/FamFamFam/page_add.png')}" title="Create a new post"/></f:link.action></span>
					<div class="clear"></div>
				</div>
				<ol class="posts">
					<f:for each="{posts}" as="post">
						<li class="post">
							<h2>
								<f:link.action action="show" controller="Post" arguments="{post: post}">{post.title}</f:link.action>
							</h2>
							<f:render partial="PostMetaData" arguments="{post: post}"/>
							<p class="content"><f:format.crop maxCharacters="500">{post.content}</f:format.crop> <f:link.action action='show' arguments='{post: post,}'>More</f:link.action></p>
						</li>
					</f:for>
				</ol>
			</f:then>
			<f:else>
				<p>This blog currently doesn't contain any posts. <f:link.action action="new" controller="Post">Create the first post</f:link.action></p>
			</f:else>
		</f:if>
	</f:section>

There you have it: In the first line of your template there's a reference to
the master layout. All HTML code is wrapped in a ``<f:section>`` tag. Even
though this is the way you usually want to design templates, you should know
that using layouts is not mandatory – you could equally put all your code into
one template and omit the ``<f:layout>`` and ``<f:section>`` tags.

Take a quick look at the template. You'll note that we're using a new view
helper right at the top – ``flashMessages`` generates an unordered list with
all flash messages. Well, maybe you remember this line in the ``createAction``
of our ``PostController``::

	$this->addFlashMessage('Created a new post.');

Flash messages are a great way to display success or error messages to
the user. And because they are so useful, TYPO3 Flow provides a ``FlashMessageContainer``
with some helper methods and Fluid offers the ``flashMessages`` view helper.
Therefore, if you create a new post, you'll see the message *Your new post was
created* at the top of your blog index on the next hit.

The main job of this template is to display a list of the most recent posts.
An ``<f:if>`` condition makes sure that the list of posts is only rendered if
``posts`` actually contains posts. But currently the view doesn't know anything
about posts - you need to adapt the ``indexAction`` of the ``PostController``
to assign blogs to the view::

*Classes/Controller/PostController.php*:

.. code-block:: php

	/**
	 * List action for this controller. Displays latest posts
	 *
	 * @return void
	 */
	public function indexAction() {
		$blog = $this->blogRepository->findActive();
		$this->view->assign('blog', $blog);
		$this->view->assign('posts', $blog->getPosts());
	}

To fully understand the above code you need to know two facts:

-	``$this->view`` is automatically set by the action controller and
	points to a Fluid template view.
-	if an action method returns ``NULL``, the controller will automatically
	call ``$this->view->render()`` after executing the action.

After creating the folder ``Resources/Private/Partials/`` add the following to a file named
``PostMetaData.html``:

*Resources/Private/Partials/PostMetaData.html*:

.. code-block:: xml

	<p class="metadata">
		Published on <f:format.date format='d.m.Y'>{post.date}</f:format.date> by {post.author}
		<f:link.action action="show" controller="Post" arguments="{post: post}" section="comments"><img src="../../Public/Icons/FamFamFam/comments.png" title="Comments"/>
			<f:if condition="{post.numberOfComments} > 0">
				<f:then>
					<f:if condition="{post.numberOfComments} == 1">
						<f:then>{post.numberOfComments} comment</f:then>
						<f:else>{post.numberOfComments} comments</f:else>
					</f:if>
				</f:then>
				<f:else>No comments</f:else>
			</f:if>
		</f:link.action>
		<br />
		<f:if condition="{post.category}">Filed under: <f:link.action action="index" controller="Post" arguments="{category: post.category.name}" format="html">{post.category}</f:link.action></f:if>
		<f:if condition="{post.tags}">
			| Tags: <f:for each="{post.tags}" as="tag"><f:link.action action="index" controller="Post" arguments="{tag: tag.name}" >{tag.name}</f:link.action> </f:for>
		</f:if>
	</p>

Now you should now see the list of recent posts by accessing
http://dev.tutorial.local/typo3.blog/post:

.. image:: /Images/GettingStarted/PostIndex.png

Creating a new post won't work yet because, you even though there exists a ``newAction`` already, the template for it doesn't contain a ``mainbox`` section yet:

.. image:: /Images/GettingStarted/NoNewActionMainbox.png

Forms
=====

Create a New Post
-----------------

Time to create a form which allows you to enter details for a new post.
The first component you need is the ``newAction`` whose sole purpose is
displaying the form:

*Classes/Controller/PostController.php*:

.. code-block:: php

	/**
	 * Shows a form for creating a new post object
	 *
	 * @return void
	 */
	public function newAction() {
		$blog = $this->blogRepository->findActive();
		$this->view->assign('blog', $blog);
	}

No code? Almost no code. What will happen is this: the action controller selects the
``New.html`` template and assigns it to ``$this->view`` which will automatically
be rendered after ``newAction`` has been called. That's enough for displaying
the form. The only thing we need to assign here is the ``blog``, otherwise the title
and description in our header (defined in ``Master.html``) would be empty.

The second component is the actual form. Adjust the template  ``New.html`` in
the ``Resources/Public/Templates/Post/`` folder:

*Resources/Public/Templates/Post/New.html*:

.. code-block:: xml

	<f:layout name="Default" />

	<f:section name="mainbox">
		<h2 class="flow-firstHeader">Create a new post</h2>
		<f:flashMessages class="flashmessages"/>
		<f:form method="post" action="create" object="{newPost}" name="newPost" enctype="multipart/form-data">
			<f:form.hidden name="blog" value="{blog}" />
			<label for="author">Author</label><br />
			<f:form.textbox property="author" id="author" /><br />
			<label for="title">Title</label><br />
			<f:form.textbox property="title" id="title" /><br />
			<label for="linkTitle">Link Title (optional)</label><br />
			<f:form.textbox property="linkTitle" id="linkTitle" /><br />
			<label for="content">Content</label><br />
			<f:form.textarea property="content" rows="5" cols="40" id="content" /><br />
			<f:form.submit value="Submit post"/>
		</f:form>
	</f:section>

Here is how it works: The ``<f:form>`` view helper renders a form tag. Its
attributes are similar to the action link view helper you might have seen in
previous examples: ``action`` specifies the action to be called on submission
of the form, ``controller`` would specify the controller and ``package`` the
package respectively. If ``controller`` or ``package`` are not set, the URI
builder will assume the current controller or package respectively.
``name`` finally declares the name of the form and at the same time specifies
**the name of the action method argument** which will receive the form values.

It is important to know that the whole form is (usually) bound to one object
and that the values of the form's elements become property values of
this object. In this example the form contains (property) values for a
post object. The form's elements are named after the class properties of the
``Post`` domain model: ``blog``, ``author``, ``title``, ``content`` and
``relatedPosts``. Let's look at the ``createAction`` again:

.. note::

	Mind that ``newPost`` is not assigned to the view in this example. Assigning
	this object is only needed if you have set default values to your model
	properties. So if you for example have a ``protected $foo = TRUE``
	definition in your model, a ``<f:form.checkbox property="foo" />`` will not
	be checked by default, unless you instantiate ``$newPost`` in your index
	action and assign it to the view.

*Classes/Controller/PostController.php*:

.. code-block:: php

	/**
	 * Creates a new post
	 *
	 * @param \TYPO3\Blog\Domain\Model\Post $post
	 * @return void
	 */
	public function createAction(\TYPO3\Blog\Domain\Model\Post $newPost) {
		$blog = $this->blogRepository->findActive();
		$blog->addPost($newPost);
		$this->postRepository->add($newPost);
		$this->addFlashMessage('Created a new post.');
		$this->redirect('index');
	}

It's important that the ``createAction`` uses the type hint
``\TYPO3\Blog\Domain\Model\Post`` and comes with a proper ``@param`` annotation
because this is how TYPO3 Flow determines the type to which the submitted form
values must be converted. Because this action requires a ``Post`` it gets a
post (object) - as long as the property names of the object and the form match.

Time to test your new ``newAction`` and its template – click on the little plus
sign above the first post lets the ``newAction`` render this form:

.. image:: /Images/GettingStarted/CreateNewPost.png

Enter some data and click the submit button:

.. image:: /Images/GettingStarted/CreatedNewPost.png

You should now find your new post in the list of posts.

Edit a Post
-----------

While you're dealing with forms you should also create form for editing an
existing post. The ``editAction`` will display this form.

This is pretty straight forward: we add a link to each post in the *Index.html*
template which passes an argument ``$post`` to the edit action and the action
on its part assigns the blog to the template.

First you need to add the "edit" link to the post index template:

*Resources/Public/Templates/Post/Index.html*:

.. code-block:: xml

	...
			<h2>
				<f:link.action action="show" controller="Post" arguments="{post: post}">{post.title}</f:link.action>
				<f:link.action action="edit" arguments="{post: post}" controller="Post">
					<img src="{f:uri.resource(path: 'Icons/FamFamFam/page_edit.png')}" title="Edit this post"/>
				</f:link.action>
			</h2>
	...

The modified template will now render a little pencil next to each post:

.. image:: /Images/GettingStarted/PostEditLink.png

Adjust the template ``Templates/Post/Edit.html`` and insert the following
HTML code:

*Resources/Public/Templates/Post/Edit.html*:

.. code-block:: html

	<f:layout name="Default" />

	<f:section name="mainbox">
		<h2 class="flow-firstHeader">Edit post "{post.title}"</h2>
		<f:form method="post" action="update" object="{post}" name="post" enctype="multipart/form-data">
			<label for="author">Author</label><br />
			<f:form.textbox property="author" id="author" /><br />
			<label for="title">Title</label><br />
			<f:form.textbox property="title" id="title" /><br />
			<label for="linkTitle">Link Title (optional)</label><br />
			<f:form.textbox property="linkTitle" id="linkTitle" /><br />
			<label for="content">Content</label><br />
			<f:form.textarea property="content" rows="5" cols="40" id="content" /><br />
			<f:form.submit value="Update"/>
		</f:form>
	</f:section>

Most of this should already look familiar. However, there is a tiny difference
to the ``new`` form you created earlier: in this edit form you added
``object="{post}"`` to the ``<f:form>`` tag. This attribute binds the variable
``{post}`` to the form and it simplifies the further definition of the
form's elements. Each element – in our case the text box and the text
area – comes with a ``property`` attribute declaring the name of the property
which is supposed to be displayed and edited by the respective element.

Because you specified ``property="title"`` for the text box, Fluid will fetch
the value of the post's ``title`` property and display it as the default value
for the rendered text box. The resulting ``input`` tag will also contain the
name ``"title"`` due to the ``property`` attribute you defined. The ``id``
attribute only serves as a target for the ``label`` tag and is not required
by Fluid.

What's missing now is a small adjustment to the PHP code displaying the edit form::

*Classes/Controller/PostController.php*:

.. code-block:: php

	/**
	 * Shows a form for editing an existing post object
	 *
	 * @param \TYPO3\Blog\Domain\Model\Post $post The post to edit
	 * @return void
	 */
	public function editAction(\TYPO3\Blog\Domain\Model\Post $post) {
		$blog = $this->blogRepository->findActive();
		$this->view->assign('blog', $blog);
		$this->view->assign('post', $post);
	}

Enough theory, let's try out the edit form in practice. A click on the edit
link of your list of posts should result in a screen similar to this:

.. image:: /Images/GettingStarted/EditPost.png

When you submit the form you call the ``updateAction``::

*Classes/Controller/PostController.php*:

.. code-block:: php

	/**
	 * Updates the given post object
	 *
	 * @param \TYPO3\Blog\Domain\Model\Post $post The post to update
	 */
	public function updateAction(\TYPO3\Blog\Domain\Model\Post $post) {
		$this->postRepository->update($post);
		$this->addFlashMessage('Updated the post.');
		$this->redirect('index');
	}

Quite easy as well, isn't it? The ``updateAction`` expects the edited post as
its argument and passes it to the repository's ``update`` method (note that we
used the ``PostRepository``!). Before we disclose the secret how this magic
actually works behind the scenes try out if updating the post really works:

.. image:: /Images/GettingStarted/UpdatedPost.png

A Closer Look on Updates
------------------------

Although updating objects is very simple on the user's side (that's where
you live), it is a bit complex on behalf of the framework. You may skip this
section if you like - but if you dare to take a quick look behind the scenes to
get a better understanding of the mechanism  behind the ``updateAction``
read on ...

The ``updateAction`` expects one argument, namely the **edited post**. "Edited
post" means that this is a ``Post`` object which already contains the values
submitted by the edit form.

These modifications will **not be persisted** automatically. To persist the
changes to the post object, call the PostRepository's ``update`` method. It schedules
an object for the dirty check at the end of the request.

If all these details didn't scare you, you might now ask yourself how TYPO3 Flow
could know that the ``updateAction`` expects a modified object and not the original?
Great question. And the answer is – literally – hidden in the form generated
by Fluid's form view helper:

HTML Code::

	<form method="post" name="post" action="post/update">
	   <input type="hidden" name="post[__identity]"
			value="2d064493-ce45-4bc9-9d0c-38e40f2c4afe" />
	   ...
	</form>

Fluid automatically renders a hidden field containing information about the
technical identity of the form's object, if the object is an original, previously
retrieved from a repository.

On receiving a request, the MVC framework checks if a special identity field
(such as the above hidden field) is present and if further properties have been
submitted. This results in three different cases:

.. table:: Create, Show, Update detection

	+-------------------+---------------+---------------------------------------+
	| Situation         | Case          | Consequence                           |
	+===================+===============+=======================================+
	| identity missing, | New /         | Create a completely new object and    |
	| properties present| Create        | set the given properties              |
	+-------------------+---------------+---------------------------------------+
	| identity present, | Show /        | Retrieve original object with         |
	| properties missing| Delete / ...  | given identifier                      |
	+-------------------+---------------+---------------------------------------+
	| identity present, | Edit /        | Retrieve original object, and set the |
	| properties present| Update        | given properties                      |
	+-------------------+---------------+---------------------------------------+

Because the edit form contained both identity and properties, TYPO3 Flow prepared an
instance with the given properties for our ``updateAction``.