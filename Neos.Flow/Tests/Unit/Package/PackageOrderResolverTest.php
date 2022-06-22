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
                    'neos/flow' => [
                        'name' => 'neos/flow',
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
                    'neos/flow'
                ],
            ],
            [
                [
                    'neos/demo' => [
                        'name' => 'neos/demo',
                        'require' => [
                            'neos/neos' => 'dev-master',
                        ],
                    ],
                    'neos/behat' => [
                        'name' => 'neos/behat',
                        'require' => [
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/imagine' => [
                        'name' => 'neos/imagine',
                        'require' => [
                            'imagine/imagine' => 'dev-master',
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/contentrepository' => [
                        'name' => 'neos/contentrepository',
                        'require' => [
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/neos' => [
                        'name' => 'neos/neos',
                        'require' => [
                            'neos/contentrepository' => 'dev-master',
                            'neos/twitter-bootstrap' => 'dev-master',
                            'neos/setup' => 'dev-master',
                            'neos/fusion' => 'dev-master',
                            'neos/nodetypes' => 'dev-master',
                            'neos/media' => 'dev-master',
                            'neos/extjs' => 'dev-master',
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/setup' => [
                        'name' => 'neos/setup',
                        'require' => [
                            'neos/twitter-bootstrap' => 'dev-master',
                            'neos/form' => 'dev-master',
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/media' => [
                        'name' => 'neos/media',
                        'require' => [
                            'neos/imagine' => 'dev-master',
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/extjs' => [
                        'name' => 'neos/extjs',
                        'require' => [
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/nodetypes' => [
                        'name' => 'neos/nodetypes',
                        'require' => [
                            'neos/fusion' => 'dev-master',
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/fusion' => [
                        'name' => 'neos/fusion',
                        'require' => [
                            'neos/eel' => 'dev-master',
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/form' => [
                        'name' => 'neos/form',
                        'require' => [
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/twitter-bootstrap' => [
                        'name' => 'neos/twitter-bootstrap',
                        'require' => [
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/sitekickstarter' => [
                        'name' => 'neos/sitekickstarter',
                        'require' => [
                            'neos/kickstarter' => 'dev-master',
                            'neos/flow' => 'dev-master',
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
                    'neos/party' => [
                        'name' => 'neos/party',
                        'require' => [
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/flow' => [
                        'name' => 'neos/flow',
                        'require' => [
                            'composer/installers' => 'dev-master',
                            'symfony/domcrawler' => 'dev-master',
                            'symfony/yaml' => 'dev-master',
                            'doctrine/migrations' => 'dev-master',
                            'doctrine/orm' => 'dev-master',
                            'neos/eel' => 'dev-master',
                            'neos/party' => 'dev-master',
                            'neos/fluid' => 'dev-master',
                        ],
                    ],
                    'neos/eel' => [
                        'name' => 'neos/eel',
                        'require' => [
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/kickstarter' => [
                        'name' => 'neos/kickstarter',
                        'require' => [
                            'neos/flow' => 'dev-master',
                        ],
                    ],
                    'neos/fluid' => [
                        'name' => 'neos/fluid',
                        'require' => [
                            'neos/flow' => 'dev-master',
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
                    'neos/eel',
                    'neos/party',
                    'neos/fluid',
                    'neos/flow',
                    'neos/form',
                    'neos/fusion',
                    'neos/nodetypes',
                    'neos/imagine',
                    'neos/media',
                    'neos/extjs',
                    'neos/twitter-bootstrap',
                    'neos/setup',
                    'neos/contentrepository',
                    'neos/neos',
                    'neos/demo',
                    'neos/behat',
                    'neos/kickstarter',
                    'neos/sitekickstarter',
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
        self::assertEquals($expectedPackageOrder, array_keys($sortedPackages), 'The packages have not been ordered according to their require!');
    }
}
