<?php
namespace Neos\Flow\Tests\Unit\Configuration\Source;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the YAML configuration source
 *
 */
class YamlSourceTest extends UnitTestCase
{
    /**
     * Sets up this test case
     *
     */
    protected function setUp()
    {
        vfsStream::setup('testDirectory');
    }

    /**
     * @test
     */
    public function returnsEmptyArrayOnNonExistingFile()
    {
        $configurationSource = new YamlSource();
        $configuration = $configurationSource->load('/ThisFileDoesNotExist');
        $this->assertEquals([], $configuration, 'No empty array was returned.');
    }

    /**
     * @test
     */
    public function optionSetInTheConfigurationFileReallyEndsUpInTheArray()
    {
        $pathAndFilename = __DIR__ . '/../Fixture/YAMLConfigurationFile';
        $configurationSource = new YamlSource();
        $configuration = $configurationSource->load($pathAndFilename);
        $this->assertTrue($configuration['configurationFileHasBeenLoaded'], 'The option has not been set by the fixture.');
    }

    /**
     * @test
     */
    public function saveWritesArrayToGivenFileAsYAML()
    {
        $pathAndFilename = vfsStream::url('testDirectory') . '/YAMLConfiguration';
        $configurationSource = new YamlSource();
        $mockConfiguration = [
            'configurationFileHasBeenLoaded' => true,
            'foo' => [
                'bar' => 'Baz'
            ]
        ];
        $configurationSource->save($pathAndFilename, $mockConfiguration);

        $yaml = 'configurationFileHasBeenLoaded: true' . chr(10) . 'foo:' . chr(10) . '  bar: Baz' . chr(10);
        $this->assertContains($yaml, file_get_contents($pathAndFilename . '.yaml'), 'Configuration was not written to the file as expected.');
    }

    /**
     * @test
     */
    public function saveKeepsQuotedKey()
    {
        $pathAndFilename = vfsStream::url('testDirectory') . '/YAMLConfiguration';
        $configurationSource = new YamlSource();
        $mockConfiguration = array(
            'configurationFileHasBeenLoaded' => true,
            'foo' => array(
                'Foo.Bar:Baz' => 'a quoted key'
            )
        );
        $configurationSource->save($pathAndFilename, $mockConfiguration);

        $yaml = 'configurationFileHasBeenLoaded: true' . chr(10) . 'foo:' . chr(10) . '  \'Foo.Bar:Baz\': \'a quoted key\'' . chr(10);
        $this->assertContains($yaml, file_get_contents($pathAndFilename . '.yaml'), 'Configuration was not written to the file as expected.');
    }

    /**
     * @test
     */
    public function saveDoesNotOverwriteExistingHeaderCommentsIfFileExists()
    {
        $pathAndFilename = vfsStream::url('testDirectory') . '/YAMLConfiguration';
        $comment = '# This comment should stay' . chr(10) . 'Test: foo' . chr(10);
        file_put_contents($pathAndFilename . '.yaml', $comment);

        $configurationSource = new YamlSource();
        $configurationSource->save($pathAndFilename, ['configurationFileHasBeenLoaded' => true]);

        $yaml = file_get_contents($pathAndFilename . '.yaml');
        $this->assertContains('# This comment should stay' . chr(10) . chr(10), $yaml, 'Header comment was removed from file.');
        $this->assertNotContains('Test: foo', $yaml);
    }

    /**
     * @test
     */
    public function yamlFileIsParsedToArray()
    {
        $expectedConfiguration = [
            'configurationFileHasBeenLoaded' => true,
            'Neos' => [
                'Flow' => [
                    'something' => 'foo',
                    '@bar' => 1,
                    'aboolean' => true,
                    'Foo.Bar:Baz' => 'a quoted key'
                ]
            ]
        ];
        $pathAndFilename = __DIR__ . '/../Fixture/YAMLConfigurationFile';
        $configurationSource = new YamlSource();
        $configuration = $configurationSource->load($pathAndFilename);
        $this->assertSame($expectedConfiguration, $configuration);
    }

    /**
     * @test
     */
    public function splitConfigurationFilesAreMergedAsExpected()
    {
        $expectedConfiguration = [
            'configurationFileHasBeenLoaded' => true,
            'Neos' => [
                'Flow' => [
                    'default' => 'test',
                    'toBeOverwritten' => 2,
                    'something' => 'zzz',
                    '@bar' => 1,
                    'aboolean' => true
                ]
            ]
        ];
        $pathAndFilename = __DIR__ . '/../Fixture/SplitYamlConfigurationFile';
        $configurationSource = new YamlSource();
        $configuration = $configurationSource->load($pathAndFilename, true);
        $this->assertSame($expectedConfiguration, $configuration);
    }
}
