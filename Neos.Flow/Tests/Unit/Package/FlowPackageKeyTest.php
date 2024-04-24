<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Package;

use Neos\Flow\Package\Exception\InvalidPackageKeyException;
use Neos\Flow\Package\FlowPackageKey;
use PHPUnit\Framework\TestCase;

class FlowPackageKeyTest extends TestCase
{
    public static function validPackageKeys(): iterable
    {
        return [
            ['Neos.Flow'],
            // Numbers are possible but NOT RECOMMEND!!!
            ['123.Company'],
            ['Company.456'],
            ['123.456'],
            ['Multi.Dots.And.More'],
        ];
    }

    public static function invalidPackageKeys(): iterable
    {
        return [
            ['neos/flow'],
            ['NoDot'],
            ['Foo.Bar-Buz'],
            ['My:Colon'],
            ['Späcial.Chars']
        ];
    }

    /**
     * @dataProvider validPackageKeys
     * @test
     */
    public function validPackageKeysAreAccepted(string $packageKey)
    {
        self::assertSame($packageKey, FlowPackageKey::fromString($packageKey)->value);
    }

    /**
     * @dataProvider invalidPackageKeys
     * @test
     */
    public function invalidPackageKeysAreRejected(string $packageKey)
    {
        $this->expectException(InvalidPackageKeyException::class);
        FlowPackageKey::fromString($packageKey);
    }

    public static function deriveFromManifestOrPathExamples(): iterable
    {
        yield 'for libraries the package key inferred from composer name' => [
            'manifest' => json_decode(<<<'JSON'
            {
                "name": "my/package"
            }
            JSON, true, 512, JSON_THROW_ON_ERROR),
            'packagePath' => '',
            'expected' => 'my.package'
        ];

        yield 'for libraries the package key inferred from composer name (hyphens are removed)' => [
            'manifest' => json_decode(<<<'JSON'
            {
                "name": "vendor/foo-bar"
            }
            JSON, true, 512, JSON_THROW_ON_ERROR),
            'packagePath' => '',
            'expected' => 'vendor.foobar'
        ];

        yield 'for type neos packages the case sensitive name from the folder is used' => [
            'manifest' => json_decode(<<<'JSON'
            {
                "name": "my/flow-package",
                "type": "neos-plugin"
            }
            JSON, true, 512, JSON_THROW_ON_ERROR),
            'packagePath' => '/app/Packages/Framework/My.Flow.Package',
            'expected' => 'My.Flow.Package'
        ];

        yield 'empty defined Flow package key' => [
            'manifest' => json_decode(<<<'JSON'
            {
                "name": "my/package",
                "extra": {
                    "neos": {
                        "package-key": ""
                    }
                }
            }
            JSON, true, 512, JSON_THROW_ON_ERROR),
            'packagePath' => '',
            'expected' => 'my.package'
        ];


        yield 'invalid defined Flow package key' => [
            'manifest' => json_decode(<<<'JSON'
            {
                "name": "my/package",
                "extra": {
                    "neos": {
                        "package-key": "Floh:"
                    }
                }
            }
            JSON, true, 512, JSON_THROW_ON_ERROR),
            'packagePath' => '',
            'expected' => 'my.package'
        ];

        yield 'explicitly (differently) defined Flow package key' => [
            'manifest' => json_decode(<<<'JSON'
            {
                "name": "my/package",
                "extra": {
                    "neos": {
                        "package-key": "MyCustom.PackageKey"
                    }
                }
            }
            JSON, true, 512, JSON_THROW_ON_ERROR),
            'packagePath' => '',
            'expected' => 'MyCustom.PackageKey'
        ];

        yield 'legacy psr-0 autoload path will be used as fallback' => [
            'manifest' => json_decode(<<<'JSON'
            {
                "name": "acme/mypackage",
                "autoload": {
                    "psr-0": {
                        "Acme\\MyPackage": "Classes/"
                    }
                }
            }
            JSON, true, 512, JSON_THROW_ON_ERROR),
            'packagePath' => '',
            'expected' => 'Acme.MyPackage'
        ];
    }

    /**
     * @dataProvider deriveFromManifestOrPathExamples
     * @test
     */
    public function deriveFromManifestOrPath(array $manifest, string $packagePath, string $expected)
    {
        $actual = FlowPackageKey::deriveFromManifestOrPath($manifest, $packagePath);
        self::assertSame($expected, $actual->value);
    }

    /**
     * @test
     */
    public function deriveComposerPackageName()
    {
        self::assertSame(
            'neos/flow',
            FlowPackageKey::fromString('Neos.Flow')->deriveComposerPackageName()
        );

        self::assertSame(
            'vendor/foo-bar',
            FlowPackageKey::fromString('Vendor.Foo.Bar')->deriveComposerPackageName()
        );
    }
}
