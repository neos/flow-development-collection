<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine;

use Doctrine\Migrations\Finder\Finder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;

/**
 * The GlobFinder class finds migrations in a directory using the PHP glob() function.
 */
final class MigrationFinder extends Finder
{

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var string
     */
    protected $databasePlatformName;

    public function __construct(string $databasePlatformName)
    {
        $this->databasePlatformName = $databasePlatformName;
    }

    /**
     * @param string $directory
     * @param string|null $namespace
     * @return string[]
     */
    public function findMigrations(string $directory, ?string $namespace = null): array
    {
        $files = [];

        foreach ($this->packageManager->getAvailablePackages() as $package) {
            $path = Files::concatenatePaths([
                $package->getPackagePath(),
                'Migrations',
                $this->databasePlatformName
            ]);
            if (is_dir($path)) {
                $files[] = glob($path . '/Version*.php');
            }
        }

        $files = array_merge([], ...$files); // the empty array covers cases when no loops were made

        return $this->loadMigrations($files, $namespace);
    }
}
