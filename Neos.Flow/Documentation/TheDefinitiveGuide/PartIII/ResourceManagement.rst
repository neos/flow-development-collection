===================
Resource Management
===================

.. sectionauthor:: Christian Müller <christian.mueller@neos.io>


Traditionally a PHP application deals directly with all kinds of files. Realizing a file
upload is usually an excessive task because you need to create a proper upload form, deal
with deciphering the ``$_FILES`` superglobal and move the uploaded file from the temporary
location to a safer place. You also need to analyze the content (is it safe?), control web
access and ultimately delete the file when it's not needed anymore.

Flow relieves you of this hassle and lets you deal with simple ``PersistentResource`` instances
instead. File uploads are handled automatically, enforcing the restrictions which were
configured by means of validation rules. The publishing mechanism was designed to support
a wide range of scenarios, starting from simple publication to the local file system up to
fine grained access control and distribution to one or more content delivery networks.
This all works without any further ado by you, the application developer.

Storage
=======

The file contents belonging to a specific ``PersistentResource`` need to be stored in some place, they
are not stored in the database together with the object. Applications should be able to store this
content in several places as needed, therefor the concept of a *Storage* exists.
A *Storage* is configured via ``Settings.yaml``:

.. code-block:: yaml

  Neos:
    Flow:
      resource:
        storages:
          defaultPersistentResourcesStorage:
            storage: 'Neos\Flow\ResourceManagement\Storage\WritableFileSystemStorage'
            storageOptions:
              path: '%FLOW_PATH_DATA%Persistent/Resources/'

The configuration for the ``defaultPersistentResourceStorage`` (naming for further storages is up
to the developer) uses a specific Storage implementation class that abstracts the operations needed
for a storage. In this case it is the ``WritableFileSystemStorage`` which stores data in a given ``path``
on the local file system of the application. Custom implementations allow you to store their resource
contents in other places as needed. You can configure as many storages as you want to separate
different types of resources, like your users avatars, generated invoices or any other type of resource
you have.

Flow comes configured with two storages by default:

* *defaultStaticResourcesStorage* is the storage for static resources from your packages. This storage
  is readonly and does not operate on ``PersistentResource`` instances. See additional information about package
  resources below.
* *defaultPersistentResourcesStorage* is the general storage for ``PersistentResource`` content. This
  storage is used as default if nothing else is specified. Custom storages will most likely be similar
  to this storage so all of the information below applies.

Target
======

Flow is a web application framework and as such some (or most) of the resources in the system need
to be made accessible online. The resource storages are not meant to be accessible so a ``Target`` is a
configured way of telling how resources are to be published to the web. The default target for our
persistent storage above is configured like this:

.. code-block:: yaml

  Neos:
    Flow:
      resource:
        targets:
          localWebDirectoryPersistentResourcesTarget:
            target: 'Neos\Flow\ResourceManagement\Target\FileSystemSymlinkTarget'
            targetOptions:
              path: '%FLOW_PATH_WEB%_Resources/Persistent/'
              baseUri: '_Resources/Persistent/'

This configures the ``Target`` named ``localWebDirectoryPersistentResourcesTarget``. Resources using this
target will be published into the the given ``path`` which is inside the public web folder of Flow.
The class ``Neos\Flow\ResourceManagement\Target\FileSystemSymlinkTarget`` is the implementation responsible for
publishing the resources and providing public URIs to it. From the name you can guess that it creates
symlinks to the resources stored on the local filesystem to save space. Other ``Target`` implementations
could publish the resources to CDNs or other external locations that are publicly accessible.

If you have lots of resources in your project you might run into problems when executing ``./flow resource:publish`` since the number of folders can be limited depending on the file system you're using.
An error that might occur in this case is "Could not create directory".
To circumvent this error you can tell Flow to split the resources into multiple subfolders in the ``_Resources/Persistent`` folder of your Web root.
The option for your Target you need to set in this case is ``subdivideHashPathSegment: TRUE``.

.. code-block:: yaml

  Neos:
    Flow:
      resource:
        targets:
          localWebDirectoryPersistentResourcesTarget:
            target: 'Neos\Flow\ResourceManagement\Target\FileSystemSymlinkTarget'
            targetOptions:
              path: '%FLOW_PATH_WEB%_Resources/Persistent/'
              baseUri: '_Resources/Persistent/'
              subdivideHashPathSegment: TRUE

Collections
===========

Flow bundles your ``PersistentResource``s into collections to allow separation of different types of
resources. A ``Collection`` is the binding between a ``Storage`` and a ``Target`` and each ``PersistentResource``
belongs to exactly one ``Collection`` and by that is stored in the matching storage and published to the
matching target. You can configure as many collections as you need for specific parts of your application.
Flow comes preconfigured with two default collections:

* *static* which is the collection using the ``defaultStaticResourcesStorage`` and
  ``localWebDirectoryStaticResourcesTarget`` to work with (static) package resources. This Collection
  is meant read-only, which is reflected by the storage used. In this Collection all resources from all
  packages ``Resources/Public/`` folders reside.
* *persistent* which is the collection using the ``Storage`` and ``Target`` described in the respective
  section above to store any ``PersistentResource`` contents by default. Any new ``PersistentResource`` you create will
  end up in this storage if not set differently.


Package Resources
=================

Flow packages may provide any amount of static resources. They might be images,
stylesheets, javascripts, templates or any other file which is used within the application
or published to the web. Static resources may either be public or private:

* *public resources* are represented by the ``static`` ``Collection`` described above and published to
  a web accessible path.
* *private resources* are not published by default. They can either be used internally (for
  example as templates) or published with certain access restrictions.

Whether a static package resource is public or private is determined by its parent
directory. For a package *Acme.Demo* the public resources reside in a folder called
*Acme.Demo/Resources/Public/* while the private resources are stored in
*Acme.Demo/Resources/Private/*. The directory structure below *Public* and *Private* is up
to you but there are some suggestions in the :doc:`chapter about package management <PackageManagement>`.
Both private and public package resources are not represented by ``PersistentResource``s in the database.


Persistent Resources
====================

Data which was uploaded by a user or generated by your application is called a *persistent
resource*. Although these resources are usually stored as files, they are never referred
to by their path and filename directly but are represented by ``PersistentResource`` instances.

.. note::
  It is important to completely ignore the fact that resources are stored as files
  somewhere – you should only deal with resource objects, this allows your application to scale by
  using remote resource storages.

New persistent resources can be created by either importing or uploading a file. In either
case the result is a new ``PersistentResource`` which can be attached to any other object. As soon as the
``PersistentResource`` is removed (can happen by cascade operations of related domain objects if you want)
the file data is removed too if it is no longer needed by another ``PersistentResource``.

Importing Resources
-------------------

Importing resources is one way to create a new resource object. The ``ResourceManager``
provides a simple API method for this purpose:

*Example: Importing a new resource* ::

	class ImageController {

		/**
		 * @Flow\Inject
		 * @var \Neos\Flow\ResourceManagement\ResourceManager
		 */
		protected $resourceManager;

		// ... more code here ...

		/**
		 * Imports an image
		 *
		 * @param string $imagePathAndFilename
		 * @return void
		 */
		public function importImageAction($imagePathAndFilename) {
			$newResource = $this->resourceManager->importResource($imagePathAndFilename);

			$newImage = new \Acme\Demo\Domain\Model\Image();
			$newImage->setOriginalResource($newResource);

			$this->imageRepository->add($newImage);
		}
	}

The ``ImageController`` in our example provides a method to import a new image. Because an
image consists of more than just the image file (we need a title, caption, generate a
thumbnail, ...) we created a whole new model representing an image. The imported resource
is considered as the "original resource" of the image and the ``Image`` model could easily
provide a "thumbnail resource" for a smaller version of the original.

This is what happens in detail while executing the ``importImageAction`` method:

#. The URI (in our case an absolute path and filename) is passed to the ``importResource()``
   method which analyzes the file found at that location.
#. The file is imported into Flow's persistent resources storage using the sha1 hash over
   the file content as its filename. If a file with exactly the same content is imported
   it will reuse the already stored file data.
#. The ResourceManager returns a new ``PersistentResource`` which refers to the newly
   imported file.
#. A new ``Image`` object is created and the resource is attached to it.
#. The image is added to the ``ImageRepository`` to persist it.

In order to delete a resource just disconnect the resource object from the persisted
object, for example by unsetting ``originalResource`` in the ``Image`` object and call the
``deleteResource()`` method in the ResourceManager.

The ``importResource()`` method also accepts stream resources instead of file URIs to fetch the
content from and you can give the name of the resource ``Collection`` as second argument to define
where to store your new resource.

If you already have the new resource`s content available as a string you can use
``importResourceFromContent()`` to create a resource object from that.


Resource Uploads
----------------

The second way to create new resources is uploading them via a POST request. Flow's MVC
framework detects incoming file uploads and automatically converts them into ``PersistentResource``
instances. In order to persist an uploaded resource you only need to persist the resulting
object.

Consider the following Fluid template:

.. code-block:: xml

	<f:form method="post" action="create" object="{newImage}" objectName="newImage"
		enctype="multipart/form-data">
		<f:form.textfield property="title" value="My image title" />
		<f:form.upload property="originalResource" />
		<f:form.submit value="Submit new image"/>
	</f:form>


This form allows for submitting a new image which consists of an image title and the image
resource (e.g. a JPEG file). The following controller can handle the submission of the above
form::

	class ImageController {

	   /**
	    * Creates a new image
	    *
	    * @param \Acme\Demo\Domain\Model\Image $newImage The new image
	    * @return void
	    */
	   public function createAction(\Acme\Demo\Domain\Model\Image $newImage) {
	      $this->imageRepository->add($newImage);
	      $this->forward('index');
	   }
	}

Provided that the ``Image`` class has a ``$title`` and a ``$originalResource`` property and
that they are accessible through ``setTitle()`` and ``setOriginalResource()`` respectively the
above code will work just as expected::

	use Doctrine\ORM\Mapping as ORM;

	class Image {

	   /**
	    * @var string
	    */
	   protected $title;

	   /**
	    * @var \Neos\Flow\ResourceManagement\PersistentResource
	    * @ORM\OneToOne
	    */
	   protected $originalResource;

	   /**
	    * @param string $title
	    * @return void
	    */
	   public function setTitle($title) {
	      $this->title = $title;
	   }

	   /**
	    * @return string
	    */
	   public function getTitle() {
	      return $this->title;
	   }

	   /**
	    * @param \Neos\Flow\ResourceManagement\PersistentResource $originalResource
	    * @return void
	    */
	   public function setOriginalResource(\Neos\Flow\ResourceManagement\PersistentResource $originalResource) {
	      $this->originalResource = $originalResource;
	   }

	   /**
	    * @return \Neos\Flow\ResourceManagement\PersistentResource
	    */
	   public function getOriginalResource() {
	      return $this->originalResource;
	   }
	}

All resources are imported into the default *persistent* ``Collection`` if nothing else was configured.
You can either set an alternative collection name in the template.

.. code-block:: xml

	<f:form method="post" action="create" object="{newImage}" objectName="newImage"
		enctype="multipart/form-data">
		<f:form.textfield property="title" value="My image title" />
		<f:form.upload property="originalResource" collection="images" />
		<f:form.submit value="Submit new image"/>
	</f:form>

Or you can define it in your property mapping configuration like this::

	$propertyMappingConfiguration
		->forProperty('originalResource')
		->setTypeConverterOption(
			\Neos\Flow\ResourceManagement\ResourceTypeConverter::class,
			\Neos\Flow\ResourceManagement\ResourceTypeConverter::CONFIGURATION_COLLECTION_NAME,
			'images'
		);

Both variants would import the uploaded resource into a collection named *images*.
All import methods in the ``ResourceManager`` described above allow setting the collection as well.

.. tip::
	If you want to see the internals of file uploads you can check the ``ResourceTypeConverter`` code.


Accessing Resources
===================

There are multiple ways of accessing your resource`s data depending on what you want to do.
Either you need a web accessible URI to a resource to display or link to it or you need the raw data
to process it further (like image manipulation for example).

To provide URIs your resources have to be published. For newly created ``PersistentResource``s this happens
automatically. Package resources have to be published at least once by running the ``resource:publish``
command:

.. code-block:: none

	path$ ./flow resource:publish

This will publish all collections, you can also just publish the *static* ``Collection`` by using the
``--collection`` argument.


.. admonition:: Why Flow uses symbolic links by default

  Publishing resources basically means copying files from the ``Storage`` location to the ``Target``.
  In the default configuration Flow instead creates symbolic links, making the resources
  consume less disk space and work faster. By changing the ``Target`` configuration you can change this.

Package Resources
-----------------

Static resources (provided by packages) need to be published by the ``resource:publish`` command.
If you do not change the default configuration the whole ``Resources/Public/`` folder is symlinked, which
means you probably never need to publish again. If you configure some other ``Target`` make sure to
publish the *static* collection whenever your package resources change.

To get the URI to a published package resource you can use the ``getPublicPersistentResourceUri()``
method in the ``ResourceManager`` like this:

.. code-block:: php

	$resourceUri = $this->resourceManager->getPublicPackageResourceUri('Acme.Demo', 'Images/Icons/FooIcon.png');

The same can be done in Fluid templates by using the the built-in resource ViewHelper:

.. code-block:: html

	<img src="{f:uri.resource(path: 'Images/Icons/FooIcon.png', package: 'Acme.Demo')}" />

Note that the ``package`` parameter is optional and defaults to the
package containing the currently active controller.

.. warning::

	Although it might be a tempting shortcut, never refer to the resource files directly
	through a URL like ``_Resources/Static/Packages/Acme.Demo/Images/Icons/FooIcon.png``
	because you can't really rely on this path. Always use the resource view helper
	instead.

Persistent Resources
--------------------

Persistent resources are published on creation to the configured ``Target``. To get the URI for it
you can rely on the ``ResourceManager`` and use the ``getPublicPersistentResourceUri`` method with
your resource object::

	$resourceUri = $this->resourceManager->getPublicPersistentResourceUri($image->getOriginalResource());

Again in a Fluid template the resource ViewHelper generates the URI for you:

.. code-block:: html

	<img src="{f:uri.resource(resource: image.originalResource)}" />

A persistent resource published to the default ``Target`` is accessible through a web URI like
``http://example.local/_Resources/Persistent/107bed85ba5e9bae0edbae879bbc2c26d72033ab/your_filename.jpg``.
One advantage of using the sha1 hash of the resource content as part of the path is that once the
resource changes it gets a new path and is displayed correctly regardless of the cache
settings in the user's web browser.

If you need to access a resource`s data directly in your code you can aquire a stream via the ``getStream()``
method of the ``PersistentResource``. If a stream is not enough and you need a file path to work with
the ``createTemporaryLocalCopy()`` will return one for you.

.. warning::
  The file in the path returned by ``createTemporaryLocalCopy()`` is just valid for the current
  request and also just for reading. You should neither delete nor write to this temporary file.
  Also don't store this path.

Resource Stream Wrapper
=======================

Static resources are often used by packages internally. Typical use cases are templates,
XML, YAML or other data files and images for further processing. You might be tempted to
refer to these files by using one of the ``FLOW_PATH_*`` constants or by creating a path
relative to your package. A much better and more convenient way is using Flow's built-in
package resources stream wrapper.

The following example reads the content of the file
``Acme.Demo/Resources/Private/Templates/SomeTemplate.html`` into a variable:

*Example: Accessing static resources* ::

	$template = file_get_contents(
		'resource://Acme.Demo/Private/Templates/SomeTemplate.html'
	);

Some situations might require access to persistent resources. The resource stream wrapper also supports
this. To use this feature, just pass the resource hash:

*Example: Accessing persisted resources* ::

	$imageFile = file_get_contents('resource://' . $resource->getSha1());

You are encouraged to use this stream wrapper wherever you need to access a static or
persistent resource in your PHP code.

Publishing to a Content Delivery Network (CDN)
==============================================

Flow can publish resources to Content Delivery Networks or other remote services by using specialized connectors.

First you need to install your desired connector (a third-party package which usually can be obtained through
packagist.org9 configure it according to its documentation (provide correct credentials etc).

Once the connector package is in place, you add a new publishing target which uses that connect and assign this target
to your collection.

.. code-block:: yaml

  Neos:
    Flow:
      resource:
        collections:
          persistent:
            target: 'cloudFrontPersistentResourcesTarget'
        targets:
          cloudFrontPersistentResourcesTarget:
            target: 'Flownative\Aws\S3\S3Target'
            targetOptions:
              bucket: 'media.example.com'
              keyPrefix: '/'
              baseUri: 'https://abc123def456.cloudfront.net/'

Since the new publishing target will be empty initially, you need to publish your assets to the new target by using
the  ``resource:publish`` command:

.. code-block:: none

    path$ ./flow resource:publish

This command will upload your files to the target and use the calculated remote URL for all your assets from now on.


Switching the storage of a collection (move to CDN)
===================================================

If you want to migrate from your default local filesystem storage to a remote storage, you need to copy
all your existing persistent resources to that new storage and use that storage afterwards by default.

You start by adding a new storage with the desired driver that connects the resource management to your CDN.
As you might want also want to serve your assets by the remote storage system, you also add a target that
contains your published resources (as with local storage this can't be the same as the storage).

.. code-block:: yaml

  Neos:
    Flow:
      resource:
        storages:
          s3PersistentResourcesStorage:
            storage: 'Flownative\Aws\S3\S3Storage'
            storageOptions:
              bucket: 'storage.example.com'
              keyPrefix: 'my/assets/'
        targets:
          s3PersistentResourcesTarget:
            target: 'Flownative\Aws\S3\S3Target'
            targetOptions:
              bucket: 'media.example.com'
              keyPrefix: '/'
              baseUri: 'https://abc123def456.cloudfront.net/'

In order to copy the resources to the new storage we need a temporary collection that uses the storage and the new
publication target.

.. code-block:: yaml

  Neos:
    Flow:
      resource:
        collections:
          tmpNewCollection:
            storage: 's3PersistentResourcesStorage'
            target: 's3PersistentResourcesTarget'

Now you can use the ``resource:copy`` command:

.. code-block:: none

    path$ ./flow resource:copy --publish persistent tmpNewCollection

This will copy all your files from your current storage (local filesystem) to the new remote storage.
The ``--publish`` flag means that this command also publishes all the resources to the new target, and you have the
same state on your current storage and publication target as on the new one.

Now you can overwrite your old collection configuration and remove the temporary one:

.. code-block:: yaml

  Neos:
    Flow:
      resource:
        collections:
          persistent:
            storage: 's3PersistentResourcesStorage'
            target: 's3PersistentResourcesTarget'

Clear caches and you're done.
