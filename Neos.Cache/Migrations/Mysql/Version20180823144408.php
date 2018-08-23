<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180823144408 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Create needed tables for PDO cache backend';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws AbortMigrationException
     * @throws DBALException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        if (array_search('cache', $this->sm->listTableNames()) === false) {
            $this->addSql('CREATE TABLE cache (identifier VARCHAR(250) NOT NULL, cache VARCHAR(250) NOT NULL, context VARCHAR(150) NOT NULL, created INTEGER UNSIGNED NOT NULL, lifetime INTEGER UNSIGNED DEFAULT 0 NOT NULL, content LONGTEXT, PRIMARY KEY (identifier, cache, context)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        }

        if (array_search('tags', $this->sm->listTableNames()) === false) {
            $this->addSql('CREATE TABLE tags (identifier VARCHAR(250) NOT NULL, cache VARCHAR(250) NOT NULL, context VARCHAR(150) NOT NULL, tag VARCHAR(250) NOT NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $this->addSql('CREATE INDEX identifier ON tags (identifier, cache, context)');
            $this->addSql('CREATE INDEX tag ON tags (tag)');
        }
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws AbortMigrationException
     * @throws DBALException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        if (array_search('cache', $this->sm->listTableNames()) !== false) {
            $this->addSql('DROP TABLE cache');
        }

        if (array_search('tags', $this->sm->listTableNames()) !== false) {
            $this->addSql('DROP TABLE tags');
        }
    }
}
