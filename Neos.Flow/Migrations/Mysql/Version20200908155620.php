<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceRepository;

class Version20200908155620 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Drop "md5" column of the "resource" table';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_flow_resourcemanagement_persistentresource DROP md5');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_flow_resourcemanagement_persistentresource ADD md5 VARCHAR(32) NOT NULL');
    }

    /**
     * Add md5 content hash for resources.
     *
     * @param Schema $schema
     * @return void
     */
    public function postDown(Schema $schema)
    {
        $resourceRepository = Bootstrap::$staticObjectManager->get(ResourceRepository::class);
        $persistenceManager = Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class);
        $filename = FLOW_PATH_DATA . 'tmp_md5_migration';

        $iterator = $resourceRepository->findAllIterator();
        foreach ($resourceRepository->iterate($iterator) as $resource) {
            /* @var PersistentResource $resource */
            $stream = $resource->getStream();
            if (is_resource($stream)) {
                $file = fopen($filename, 'w');
                stream_copy_to_stream($resource->getStream(), $file);
                fclose($file);

                $this->connection->executeUpdate(
                    'UPDATE neos_flow_resourcemanagement_persistentresource SET md5 = ? WHERE persistence_object_identifier = ?',
                    [md5_file($filename), $persistenceManager->getIdentifierByObject($resource)]
                );

                unlink($filename);
            }
        }
    }
}
