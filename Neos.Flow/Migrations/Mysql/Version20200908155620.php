<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceRepository;

class Version20200908155620 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Drop "md5" column of the "resource" table';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     * @throws \Doctrine\DBAL\Exception
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_flow_resourcemanagement_persistentresource DROP md5');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     * @throws \Doctrine\DBAL\Exception
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_flow_resourcemanagement_persistentresource ADD md5 VARCHAR(32) NOT NULL');
    }

    /**
     * Add md5 content hash for resources.
     *
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function postDown(Schema $schema): void
    {
        $resourceRepository = Bootstrap::$staticObjectManager->get(ResourceRepository::class);
        $persistenceManager = Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class);

        $iterator = $resourceRepository->findAllIterator();
        foreach ($resourceRepository->iterate($iterator) as $resource) {
            /* @var PersistentResource $resource */
            if (!is_resource($resource->getStream())) {
                continue;
            }

            $this->connection->executeStatement(
                'UPDATE neos_flow_resourcemanagement_persistentresource SET md5 = ? WHERE persistence_object_identifier = ?',
                [md5(stream_get_contents($resource->getStream())), $persistenceManager->getIdentifierByObject($resource)]
            );
        }
    }
}
