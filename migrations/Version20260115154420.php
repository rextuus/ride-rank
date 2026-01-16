<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115154420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__player AS SELECT id, user_id, created_at, last_seen_at, device_hash, anonymous FROM player');
        $this->addSql('DROP TABLE player');
        $this->addSql('CREATE TABLE player (id BLOB NOT NULL --(DC2Type:uuid)
        , user_id INTEGER DEFAULT NULL, home_country_id INTEGER DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , last_seen_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , device_hash VARCHAR(64) DEFAULT NULL, anonymous BOOLEAN NOT NULL, experience INTEGER DEFAULT 0 NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_98197A65A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_98197A6588E06F80 FOREIGN KEY (home_country_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player (id, user_id, created_at, last_seen_at, device_hash, anonymous) SELECT id, user_id, created_at, last_seen_at, device_hash, anonymous FROM __temp__player');
        $this->addSql('DROP TABLE __temp__player');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65A76ED395 ON player (user_id)');
        $this->addSql('CREATE INDEX IDX_98197A6588E06F80 ON player (home_country_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__player AS SELECT id, user_id, created_at, last_seen_at, device_hash, anonymous FROM player');
        $this->addSql('DROP TABLE player');
        $this->addSql('CREATE TABLE player (id BLOB NOT NULL --(DC2Type:uuid)
        , user_id INTEGER DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , last_seen_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , device_hash VARCHAR(64) DEFAULT NULL, anonymous BOOLEAN NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_98197A65A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player (id, user_id, created_at, last_seen_at, device_hash, anonymous) SELECT id, user_id, created_at, last_seen_at, device_hash, anonymous FROM __temp__player');
        $this->addSql('DROP TABLE __temp__player');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65A76ED395 ON player (user_id)');
    }
}
