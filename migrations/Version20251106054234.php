<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106054234 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE houses DROP is_available');
        $this->addSql('ALTER TABLE houses RENAME COLUMN beds TO sleeping_places');
        $this->addSql('DROP INDEX uniq_1483a5e9e7927c74');
        $this->addSql('ALTER TABLE users DROP email');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE houses ADD is_available BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE houses RENAME COLUMN sleeping_places TO beds');
        $this->addSql('ALTER TABLE users ADD email VARCHAR(155) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_1483a5e9e7927c74 ON users (email)');
    }
}
