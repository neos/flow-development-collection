# Neos Cache Framework

This is a generic cache package for use in projects.
It implements [PSR-6](https://github.com/php-fig/cache) and [PSR-16](https://github.com/php-fig/simple-cache) but 
also brings own interfaces used in Flow and Neos which support additional featuers.

#### Note

This repository is a **read-only subsplit** of a package that is part of the
Flow framework (learn more on `http://flow.neos.io <http://flow.neos.io/>`_).

All pull requests and issues should be opened in the [main repository](https://github.com/neos/flow-development-collection).

The package is usable without the Flow framework, but if you
want to use it, please have a look at the `Flow documentation
<http://flowframework.readthedocs.org/en/stable/>`_

## Installation

Install latest version via composer:
   
`composer require neos/cache`

## Basic usage


    $environmentConfiguration = new \Neos\Cache\EnvironmentConfiguration('appIdentifier', __DIR__);

    // This cache factory can be used for PSR-6 caches
    // and for the Neos CacheInterface
    $cacheFactory = new \Neos\Cache\Psr\Cache\CacheFactory(
        $environmentConfiguration
    );

    // Create a PSR-6 compatible cache
    $cachePool = $cacheFactory->create(
        'myCache', 
        \Neos\Cache\Backend\SimpleFileBackend::class
    );

    // Create a PSR-16 compatible cache
    $simpleCacheFactory = new \Neos\Cache\Psr\SimpleCache\SimpleCacheFactory(
        $environmentConfiguration
    );

    $simpleCache = $simpleCacheFactory->create(
        'myCache', 
        \Neos\Cache\Backend\SimpleFileBackend::class
    );
    
The first argument given to either factory is a unique identifier for the specific cache instance.
If you need different caches you should give them separate identifiers.

## Documenatation

Both the PSR-6 CachePool and the PSR-16 SimpleCache are separate implementations with their respective factories,
but both use the existing [backends](https://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/Caching.html#cache-backends)
that can also be used with the `\Neos\Cache\Frontend\FrontendInterface` implementations, which are slightly 
different than the PSR caches but also implement additional features like tagging.

#### Note

Both PSR implementations are not integrated in Flow yet, so when you use them within a Flow installation
it's your responsibility to flush them correctly as ``./flow flow:cache:flush`` will not do that in this case.

Contribute
----------

If you want to contribute to this package or the Flow framework, please have a look at
https://github.com/neos/flow-development-collection - it is the repository
used for development and all pull requests should go into it.
