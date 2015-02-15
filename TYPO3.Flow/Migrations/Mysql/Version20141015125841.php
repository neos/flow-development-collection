<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Utility\MediaTypes;

/**
 * New Resource Management
 *
 * - remove the typo3_flow_resource_resourcepointer table
 * - remove the non-used content security configuration table
 * - remove the fileextension column
 * - add and fill new columns for typo3_flow_resource_resource
 */
class Version20141015125841 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 * @throws \Exception
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql');

		$this->addSql('ALTER TABLE typo3_flow_resource_resource DROP FOREIGN KEY FK_B4D45B32A4A851AF');
		$this->addSql('ALTER TABLE typo3_flow_resource_resource DROP FOREIGN KEY typo3_flow_resource_resource_ibfk_1');
		$this->addSql('ALTER TABLE typo3_flow_resource_resource CHANGE resourcepointer sha1 VARCHAR(40) NOT NULL, ADD md5 VARCHAR(32) DEFAULT NULL, ADD collectionname VARCHAR(255) DEFAULT NULL, DROP publishingconfiguration, DROP fileextension, ADD mediatype VARCHAR(100) DEFAULT NULL, ADD relativepublicationpath VARCHAR(255) NOT NULL, ADD filesize NUMERIC(20, 0) DEFAULT NULL');
		$this->addSql('DROP INDEX IDX_B4D45B323CB65D1 ON typo3_flow_resource_resource');
		$this->addSql('DROP TABLE typo3_flow_resource_resourcepointer');
	}

	/**
	 * Move resource files to the new locations and adjust records.
	 *
	 * @param Schema $schema
	 * @return void
	 */
	public function postUp(Schema $schema) {
		$resourcesResult = $this->connection->executeQuery('SELECT persistence_object_identifier, sha1, filename FROM typo3_flow_resource_resource');
		while ($resourceInfo = $resourcesResult->fetch(\PDO::FETCH_ASSOC)) {
			$resourcePathAndFilename = FLOW_PATH_DATA . 'Persistent/Resources/' . $resourceInfo['sha1'];
			$newResourcePathAndFilename = FLOW_PATH_DATA . 'Persistent/Resources/' . wordwrap($resourceInfo['sha1'], 5, '/', TRUE) . '/' . $resourceInfo['sha1'];

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

				$result = TRUE;
			} else {
				$this->write(sprintf('Error while migrating database for the new resource management: the resource file "%s" (original filename: %s) was not found, but the resource object with uuid %s needs this file.', $resourcePathAndFilename, $resourceInfo['filename'], $resourceInfo['persistence_object_identifier']));
				continue;
			}

			$this->connection->executeUpdate(
				'UPDATE typo3_flow_resource_resource SET collectionname = ?, mediatype = ?, md5 = ?, filesize = ? WHERE persistence_object_identifier = ?',
				array('persistent', $mediaType, $md5, $filesize, $resourceInfo['persistence_object_identifier'])
			);

			if ($result === FALSE) {
				$this->write(sprintf('Could not move the data file of resource "%s" from its legacy location at %s to the correct location %s.', $resourceInfo['sha1'], $resourcePathAndFilename, $newResourcePathAndFilename));
			}
		}
		$this->connection->exec('ALTER TABLE typo3_flow_resource_resource CHANGE collectionname collectionname VARCHAR(255) NOT NULL, CHANGE mediatype mediatype VARCHAR(100) NOT NULL, CHANGE md5 md5 VARCHAR(32) NOT NULL, CHANGE filesize filesize NUMERIC(20, 0) NOT NULL');
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql');
		$this->addSql('CREATE TABLE typo3_flow_resource_resourcepointer (hash VARCHAR(255) NOT NULL, PRIMARY KEY(hash)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');

		$this->addSql('ALTER TABLE typo3_flow_resource_resource ADD publishingconfiguration VARCHAR(40) DEFAULT NULL, CHANGE sha1 resourcepointer VARCHAR(255) NOT NULL, DROP md5, DROP collectionname, DROP mediatype, DROP relativepublicationpath, DROP filesize, ADD fileextension VARCHAR(255) NOT NULL');
		$this->addSql('ALTER TABLE typo3_flow_resource_resource ADD CONSTRAINT FK_B4D45B32A4A851AF FOREIGN KEY (publishingconfiguration) REFERENCES typo3_flow_resource_publishing_abstractpublishingconfiguration (persistence_object_identifier)');
		$this->addSql('CREATE INDEX IDX_B4D45B323CB65D1 ON typo3_flow_resource_resource (resourcepointer)');
		$this->addSql('CREATE INDEX IDX_B4D45B32A4A851AF ON typo3_flow_resource_resource (publishingconfiguration)');
	}

	/**
	 * Move resource files back to the old locations and adjust records.
	 *
	 * @param Schema $schema
	 * @return void
	 */
	public function postDown(Schema $schema) {
		$resourcesResult = $this->connection->executeQuery('SELECT DISTINCT resourcepointer FROM typo3_flow_resource_resource');
		while ($resourceInfo = $resourcesResult->fetch(\PDO::FETCH_ASSOC)) {
			$this->connection->executeQuery(
				'INSERT INTO typo3_flow_resource_resourcepointer (hash) VALUES (?)',
				array($resourceInfo['resourcepointer'])
			);

			$resourcePathAndFilename = FLOW_PATH_DATA . 'Persistent/Resources/' . wordwrap($resourceInfo['resourcepointer'], 5, '/', TRUE) . '/' . $resourceInfo['resourcepointer'];
			if (!file_exists($resourcePathAndFilename)) {
				$this->write(sprintf('Error while migrating database for the old resource management: the resource file "%s" was not found.', $resourcePathAndFilename));
				continue;
			}
			$newResourcePathAndFilename = FLOW_PATH_DATA . 'Persistent/Resources/' . $resourceInfo['resourcepointer'];
			$result = @rename($resourcePathAndFilename, $newResourcePathAndFilename);
			if ($result === FALSE) {
				$this->write(sprintf('Could not move the data file of resource "%s" from its location at %s to the legacy location %s.', $resourceInfo['resourcepointer'], $resourcePathAndFilename, $newResourcePathAndFilename));
			}
			Files::removeEmptyDirectoriesOnPath(dirname($resourcePathAndFilename));
		}

		$this->connection->exec('UPDATE typo3_flow_resource_resource SET fileextension =  SUBSTRING_INDEX(filename, \'.\', -1)');
		$this->connection->exec('ALTER TABLE typo3_flow_resource_resource ADD CONSTRAINT typo3_flow_resource_resource_ibfk_1 FOREIGN KEY (resourcepointer) REFERENCES typo3_flow_resource_resourcepointer (hash)');
	}
}
