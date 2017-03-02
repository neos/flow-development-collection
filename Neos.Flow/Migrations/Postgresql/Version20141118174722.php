<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Neos\Utility\Files;
use Neos\Utility\MediaTypes;

/**
 * New Resource Management
 *
 * - remove the typo3_flow_resource_resourcepointer table
 * - remove the non-used content security configuration table
 * - remove the fileextension column
 * - add and fill new columns for typo3_flow_resource_resource
 */
class Version20141118174722 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql');

        $this->addSql('ALTER TABLE typo3_flow_resource_resource DROP CONSTRAINT fk_b4d45b32a4a851af');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource DROP CONSTRAINT fk_b4d45b323cb65d1');

        $this->addSql('DROP INDEX IF EXISTS idx_b4d45b323cb65d1');

        $this->addSql('ALTER TABLE typo3_flow_resource_resource RENAME resourcepointer TO sha1');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource ALTER sha1 TYPE VARCHAR(40), ALTER sha1 DROP DEFAULT, ALTER sha1 SET NOT NULL');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource ADD md5 VARCHAR(32) NULL');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource ADD collectionname VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource ADD mediatype VARCHAR(100) NULL');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource ADD relativepublicationpath VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource ADD filesize NUMERIC(20, 0) NULL');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource DROP publishingconfiguration');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource DROP fileextension');

        $this->addSql('DROP TABLE typo3_flow_resource_publishing_abstractpublishingconfiguration');
        $this->addSql('DROP TABLE typo3_flow_resource_resourcepointer');
    }

    /**
     * Move resource files to the new locations and adjust records.
     *
     * @param Schema $schema
     * @return void
     */
    public function postUp(Schema $schema)
    {
        $resourcesResult = $this->connection->executeQuery('SELECT persistence_object_identifier, sha1, filename FROM typo3_flow_resource_resource');
        while ($resourceInfo = $resourcesResult->fetch(\PDO::FETCH_ASSOC)) {
            $resourcePathAndFilename = FLOW_PATH_DATA . 'Persistent/Resources/' . $resourceInfo['sha1'];
            $newResourcePathAndFilename = FLOW_PATH_DATA . 'Persistent/Resources/' . $resourceInfo['sha1'][0] . '/' . $resourceInfo['sha1'][1] . '/' . $resourceInfo['sha1'][2] . '/' . $resourceInfo['sha1'][3] . '/' . $resourceInfo['sha1'];

            $mediaType = MediaTypes::getMediaTypeFromFilename($resourceInfo['filename']);
            if (file_exists($resourcePathAndFilename)) {
                $md5 = md5_file($resourcePathAndFilename);
                $filesize = filesize($resourcePathAndFilename);

                if (!file_exists(dirname($newResourcePathAndFilename))) {
                    Files::createDirectoryRecursively(dirname($newResourcePathAndFilename));
                }
                $result = @rename($resourcePathAndFilename, $newResourcePathAndFilename);
            } elseif (file_exists($newResourcePathAndFilename)) {
                $md5 = md5_file($newResourcePathAndFilename);
                $filesize = filesize($newResourcePathAndFilename);

                $result = true;
            } else {
                $this->write(sprintf('Error while migrating database for the new resource management: the resource file "%s" (original filename: %s) was not found, but the resource object with uuid %s needs this file.', $resourcePathAndFilename, $resourceInfo['filename'], $resourceInfo['persistence_object_identifier']));
                continue;
            }

            $this->connection->executeUpdate(
                'UPDATE typo3_flow_resource_resource SET collectionname = ?, mediatype = ?, md5 = ?, filesize = ? WHERE persistence_object_identifier = ?',
                array('persistent', $mediaType, $md5, $filesize, $resourceInfo['persistence_object_identifier'])
            );

            if ($result === false) {
                $this->write(sprintf('Could not move the data file of resource "%s" from its legacy location at %s to the correct location %s.', $resourceInfo['sha1'], $resourcePathAndFilename, $newResourcePathAndFilename));
            }
        }
        $this->connection->exec('ALTER TABLE typo3_flow_resource_resource ALTER md5 SET NOT NULL');
        $this->connection->exec('ALTER TABLE typo3_flow_resource_resource ALTER collectionname SET NOT NULL');
        $this->connection->exec('ALTER TABLE typo3_flow_resource_resource ALTER mediatype SET NOT NULL');
        $this->connection->exec('ALTER TABLE typo3_flow_resource_resource ALTER filesize SET NOT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql');

        $this->addSql('CREATE TABLE typo3_flow_resource_publishing_abstractpublishingconfiguration (persistence_object_identifier VARCHAR(40) NOT NULL, dtype VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier))');
        $this->addSql('CREATE TABLE typo3_flow_resource_resourcepointer (hash VARCHAR(255) NOT NULL, PRIMARY KEY(hash))');

        $this->addSql('ALTER TABLE typo3_flow_resource_resource RENAME sha1 TO resourcepointer');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource ALTER resourcepointer TYPE VARCHAR(255), ALTER resourcepointer DROP NOT NULL, ALTER resourcepointer SET DEFAULT NULL');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource ADD publishingconfiguration VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource ADD fileextension VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource DROP md5');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource DROP collectionname');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource DROP mediatype');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource DROP relativepublicationpath');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource DROP filesize');
        $this->addSql('ALTER TABLE typo3_flow_resource_resource ADD CONSTRAINT fk_b4d45b32a4a851af FOREIGN KEY (publishingconfiguration) REFERENCES typo3_flow_resource_publishing_abstractpublishingconfiguration (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_b4d45b323cb65d1 ON typo3_flow_resource_resource (resourcepointer)');
        $this->addSql('CREATE INDEX idx_b4d45b32a4a851af ON typo3_flow_resource_resource (publishingconfiguration)');
    }

    /**
     * Move resource files back to the old locations and adjust records.
     *
     * @param Schema $schema
     * @return void
     */
    public function postDown(Schema $schema)
    {
        $resourcesResult = $this->connection->executeQuery('SELECT DISTINCT resourcepointer FROM typo3_flow_resource_resource');
        while ($resourceInfo = $resourcesResult->fetch(\PDO::FETCH_ASSOC)) {
            $this->connection->executeQuery(
                'INSERT INTO typo3_flow_resource_resourcepointer (hash) VALUES (?)',
                array($resourceInfo['resourcepointer'])
            );

            $resourcePathAndFilename = FLOW_PATH_DATA . 'Persistent/Resources/' . $resourceInfo['resourcepointer'][0] . '/' . $resourceInfo['resourcepointer'][1] . '/' . $resourceInfo['resourcepointer'][2] . '/' . $resourceInfo['resourcepointer'][3] . '/' . $resourceInfo['resourcepointer'];
            if (!file_exists($resourcePathAndFilename)) {
                $this->write(sprintf('Error while migrating database for the old resource management: the resource file "%s" was not found.', $resourcePathAndFilename));
                continue;
            }
            $newResourcePathAndFilename = FLOW_PATH_DATA . 'Persistent/Resources/' . $resourceInfo['resourcepointer'];
            $result = @rename($resourcePathAndFilename, $newResourcePathAndFilename);
            if ($result === false) {
                $this->write(sprintf('Could not move the data file of resource "%s" from its location at %s to the legacy location %s.', $resourceInfo['resourcepointer'], $resourcePathAndFilename, $newResourcePathAndFilename));
            }
            Files::removeEmptyDirectoriesOnPath(dirname($resourcePathAndFilename));
        }

        $this->connection->exec('UPDATE typo3_flow_resource_resource SET fileextension = substring(filename from \'\\.(.+)$\')');
        $this->connection->exec('ALTER TABLE typo3_flow_resource_resource ALTER fileextension SET NOT NULL');
        $this->connection->exec('ALTER TABLE typo3_flow_resource_resource ADD CONSTRAINT fk_b4d45b323cb65d1 FOREIGN KEY (resourcepointer) REFERENCES typo3_flow_resource_resourcepointer (hash) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
