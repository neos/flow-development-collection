<?php
namespace Neos\Flow\Tests\Unit\Package;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Package\PackageOrderResolver;

/**
 * Test the PackageOrderResolver
 */
class PackageOrderResolverTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * Data provider for testing if a list of unordered packages gets ordered correctly.
     *
     * @return array
     */
    public function packagesAndDependenciesOrder()
    {
        return [
            [
                [
                    'doctrine/orm' => [
                        'name' => 'doctrine/orm',
                        'require' => ['doctrine/dbal' => 'dev-master'],
                    ],
                    'symfony/component-yaml' => [
                        'name' => 'symfony/component-yaml',
                        'require' => [],
                    ],
                    'typo3/flow' => [
                        'name' => 'typo3/flow',
                        'require' => ['symfony/component-yaml' => 'dev-master', 'doctrine/orm' => 'dev-master'],
                    ],
                    'doctrine/common' => [
                        'name' => 'doctrine/common',
                        'require' => [],
                    ],
                    'doctrine/dbal' => [
                        'name' => 'doctrine/dbal',
                        'require' => ['doctrine/common' => 'dev-master'],
                    ],
                ],
                [
                    'doctrine/common',
                    'doctrine/dbal',
                    'doctrine/orm',
                    'symfony/component-yaml',
                    'typo3/flow'
                ],
            ],
            [
                [
                    'typo3/neosdemotypo3org' => [
                        'name' => 'typo3/neosdemotypo3org',
                        'require' => [
                            'typo3/neos' => 'dev-master',
                        ],
                    ],
                    'flowpack/behat' => [
                        'name' => 'flowpack/behat',
                        'require' => [
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/imagine' => [
                        'name' => 'typo3/imagine',
                        'require' => [
                            'imagine/imagine' => 'dev-master',
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/typo3cr' => [
                        'name' => 'typo3/typo3cr',
                        'require' => [
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/neos' => [
                        'name' => 'typo3/neos',
                        'require' => [
                            'typo3/typo3cr' => 'dev-master',
                            'typo3/twitter-bootstrap' => 'dev-master',
                            'typo3/setup' => 'dev-master',
                            'typo3/typoscript' => 'dev-master',
                            'typo3/neos-nodetypes' => 'dev-master',
                            'typo3/media' => 'dev-master',
                            'typo3/extjs' => 'dev-master',
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/setup' => [
                        'name' => 'typo3/setup',
                        'require' => [
                            'typo3/twitter-bootstrap' => 'dev-master',
                            'typo3/form' => 'dev-master',
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/media' => [
                        'name' => 'typo3/media',
                        'require' => [
                            'typo3/imagine' => 'dev-master',
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/extjs' => [
                        'name' => 'typo3/extjs',
                        'require' => [
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/neos-nodetypes' => [
                        'name' => 'typo3/neos-nodetypes',
                        'require' => [
                            'typo3/typoscript' => 'dev-master',
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/typoscript' => [
                        'name' => 'typo3/typoscript',
                        'require' => [
                            'typo3/eel' => 'dev-master',
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/form' => [
                        'name' => 'typo3/form',
                        'require' => [
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/twitter-bootstrap' => [
                        'name' => 'typo3/twitter-bootstrap',
                        'require' => [
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/sitekickstarter' => [
                        'name' => 'typo3/sitekickstarter',
                        'require' => [
                            'typo3/kickstart' => 'dev-master',
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'imagine/imagine' => [
                        'name' => 'imagine/imagine',
                        'require' => [],
                    ],
                    'mikey179/vfsstream' => [
                        'name' => 'mikey179/vfsstream',
                        'require' => [],
                    ],
                    'composer/installers' => [
                        'name' => 'composer/installers',
                        'require' => [],
                    ],
                    'symfony/console' => [
                        'name' => 'symfony/console',
                        'require' => [],
                    ],
                    'symfony/domcrawler' => [
                        'name' => 'symfony/domcrawler',
                        'require' => [],
                    ],
                    'symfony/yaml' => [
                        'name' => 'symfony/yaml',
                        'require' => [],
                    ],
                    'doctrine/annotations' => [
                        'name' => 'doctrine/annotations',
                        'require' => [
                            'doctrine/lexer' => 'dev-master',
                        ],
                    ],
                    'doctrine/cache' => [
                        'name' => 'doctrine/cache',
                        'require' => [],
                    ],
                    'doctrine/collections' => [
                        'name' => 'doctrine/collections',
                        'require' => [],
                    ],
                    'doctrine/common' => [
                        'name' => 'doctrine/common',
                        'require' => [
                            'doctrine/annotations' => 'dev-master',
                            'doctrine/lexer' => 'dev-master',
                            'doctrine/collections' => 'dev-master',
                            'doctrine/cache' => 'dev-master',
                            'doctrine/inflector' => 'dev-master',
                        ],
                    ],
                    'doctrine/dbal' => [
                        'name' => 'doctrine/dbal',
                        'require' => [
                            'doctrine/common' => 'dev-master',
                        ],
                    ],
                    'doctrine/inflector' => [
                        'name' => 'doctrine/inflector',
                        'require' => [],
                    ],
                    'doctrine/lexer' => [
                        'name' => 'doctrine/lexer',
                        'require' => [],
                    ],
                    'doctrine/migrations' => [
                        'name' => 'doctrine/migrations',
                        'require' => [
                            'doctrine/dbal' => 'dev-master',
                        ],
                    ],
                    'doctrine/orm' => [
                        'name' => 'doctrine/orm',
                        'require' => [
                            'symfony/console' => 'dev-master',
                            'doctrine/dbal' => 'dev-master',
                        ],
                    ],
                    'phpunit/phpcodecoverage' => [
                        'name' => 'phpunit/phpcodecoverage',
                        'require' => [
                            'phpunit/phptexttemplate' => 'dev-master',
                            'phpunit/phptokenstream' => 'dev-master',
                            'phpunit/phpfileiterator' => 'dev-master',
                        ],
                    ],
                    'phpunit/phpfileiterator' => [
                        'name' => 'phpunit/phpfileiterator',
                        'require' => [],
                    ],
                    'phpunit/phptexttemplate' => [
                        'name' => 'phpunit/phptexttemplate',
                        'require' => [],
                    ],
                    'phpunit/phptimer' => [
                        'name' => 'phpunit/phptimer',
                        'require' => [],
                    ],
                    'phpunit/phptokenstream' => [
                        'name' => 'phpunit/phptokenstream',
                        'require' => [],
                    ],
                    'phpunit/phpunitmockobjects' => [
                        'name' => 'phpunit/phpunitmockobjects',
                        'require' => [
                            'phpunit/phptexttemplate' => 'dev-master',
                        ],
                    ],
                    'phpunit/phpunit' => [
                        'name' => 'phpunit/phpunit',
                        'require' => [
                            'symfony/yaml' => 'dev-master',
                            'phpunit/phpunitmockobjects' => 'dev-master',
                            'phpunit/phptimer' => 'dev-master',
                            'phpunit/phpcodecoverage' => 'dev-master',
                            'phpunit/phptexttemplate' => 'dev-master',
                            'phpunit/phpfileiterator' => 'dev-master',
                        ],
                    ],
                    'typo3/party' => [
                        'name' => 'typo3/party',
                        'require' => [
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/flow' => [
                        'name' => 'typo3/flow',
                        'require' => [
                            'composer/installers' => 'dev-master',
                            'symfony/domcrawler' => 'dev-master',
                            'symfony/yaml' => 'dev-master',
                            'doctrine/migrations' => 'dev-master',
                            'doctrine/orm' => 'dev-master',
                            'typo3/eel' => 'dev-master',
                            'typo3/party' => 'dev-master',
                            'typo3/fluid' => 'dev-master',
                        ],
                    ],
                    'typo3/eel' => [
                        'name' => 'typo3/eel',
                        'require' => [
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/kickstart' => [
                        'name' => 'typo3/kickstart',
                        'require' => [
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                    'typo3/fluid' => [
                        'name' => 'typo3/fluid',
                        'require' => [
                            'typo3/flow' => 'dev-master',
                        ],
                    ],
                ],
                [
                    'composer/installers',
                    'symfony/domcrawler',
                    'symfony/yaml',
                    'doctrine/lexer',
                    'doctrine/annotations',
                    'doctrine/collections',
                    'doctrine/cache',
                    'doctrine/inflector',
                    'doctrine/common',
                    'doctrine/dbal',
                    'doctrine/migrations',
                    'symfony/console',
                    'doctrine/orm',
                    'imagine/imagine',
                    'typo3/eel',
                    'typo3/party',
                    'typo3/fluid',
                    'typo3/flow',
                    'typo3/form',
                    'typo3/typoscript',
                    'typo3/neos-nodetypes',
                    'typo3/imagine',
                    'typo3/media',
                    'typo3/extjs',
                    'typo3/twitter-bootstrap',
                    'typo3/setup',
                    'typo3/typo3cr',
                    'typo3/neos',
                    'typo3/neosdemotypo3org',
                    'flowpack/behat',
                    'typo3/kickstart',
                    'typo3/sitekickstarter',
                    'mikey179/vfsstream',
                    'phpunit/phptexttemplate',
                    'phpunit/phptokenstream',
                    'phpunit/phpfileiterator',
                    'phpunit/phpcodecoverage',
                    'phpunit/phptimer',
                    'phpunit/phpunitmockobjects',
                    'phpunit/phpunit',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider packagesAndDependenciesOrder
     * @param array $packages
     * @param array $expectedPackageOrder
     */
    public function availablePackagesAreSortedAfterTheirDependencies($packages, $expectedPackageOrder)
    {
        $orderResolver = new PackageOrderResolver($packages, $packages);
        $sortedPackages = $orderResolver->sort();
        $this->assertEquals($expectedPackageOrder, array_keys($sortedPackages), 'The packages have not been ordered according to their require!');
    }
}
