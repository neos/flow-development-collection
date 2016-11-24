<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Neos\Utility\Files;

/**
 * Move persistent resource files from abcde/fghij/../abcdefghij  to  a/b/c/d/abcdefghij
 */
class Version20150611154421 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $resourcesResult = $this->connection->executeQuery('SELECT persistence_object_identifier, sha1, filename FROM typo3_flow_resource_resource');
        while ($resourceInfo = $resourcesResult->fetch(\PDO::FETCH_ASSOC)) {
            $resourcePathAndFilename = FLOW_PATH_DATA . 'Persistent/Resources/' . wordwrap($resourceInfo['sha1'], 5, '/', true) . '/' . $resourceInfo['sha1'];
            $newResourcePathAndFilename = FLOW_PATH_DATA . 'Persistent/Resources/' . $resourceInfo['sha1'][0] . '/' . $resourceInfo['sha1'][1] . '/' . $resourceInfo['sha1'][2] . '/' . $resourceInfo['sha1'][3] . '/' . $resourceInfo['sha1'];

            if (!file_exists($newResourcePathAndFilename)) {
                if (!file_exists($resourcePathAndFilename)) {
                    $this->write(sprintf('<warning>Could not move the data file of resource "%s" from its legacy location at "%s" to the correct location "%s" because the source file does not exist.', $resourceInfo['sha1'], $resourcePathAndFilename, $newResourcePathAndFilename));
                    continue;
                }
                if (!file_exists(dirname($newResourcePathAndFilename))) {
                    Files::createDirectoryRecursively(dirname($newResourcePathAndFilename));
                }
                if (@rename($resourcePathAndFilename, $newResourcePathAndFilename) === false) {
                    $this->write(sprintf('<warning>Could not move the data file of resource "%s" from its legacy location at "%s" to the correct location "%s".', $resourceInfo['sha1'], $resourcePathAndFilename, $newResourcePathAndFilename));
                }
                Files::removeEmptyDirectoriesOnPath(dirname($resourcePathAndFilename));
            }
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        // no need to move anything back, the original migration expects the resources to be at the new location
    }
}
