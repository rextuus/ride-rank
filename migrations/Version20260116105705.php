<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116105705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coaster ADD COLUMN selection_seed INTEGER DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__coaster AS SELECT id, train_id, track_id, manufacturer_id, status, rcdb_image_url, cdn_image_url, opening_year, rating, comparisons_count, ident, name, created, edited, rcdb_id, rcdb_url FROM coaster');
        $this->addSql('DROP TABLE coaster');
        $this->addSql('CREATE TABLE coaster (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, train_id INTEGER DEFAULT NULL, track_id INTEGER DEFAULT NULL, manufacturer_id INTEGER DEFAULT NULL, status VARCHAR(255) DEFAULT \'Operating since\' NOT NULL, rcdb_image_url VARCHAR(255) DEFAULT NULL, cdn_image_url VARCHAR(255) DEFAULT NULL, opening_year SMALLINT DEFAULT NULL, rating DOUBLE PRECISION DEFAULT \'1200\' NOT NULL, comparisons_count INTEGER DEFAULT 0 NOT NULL, ident VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, edited DATETIME DEFAULT NULL, rcdb_id INTEGER DEFAULT NULL, rcdb_url VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_F6312A7823BCD4D0 FOREIGN KEY (train_id) REFERENCES train (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F6312A785ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F6312A78A23B42D FOREIGN KEY (manufacturer_id) REFERENCES manufacturer (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO coaster (id, train_id, track_id, manufacturer_id, status, rcdb_image_url, cdn_image_url, opening_year, rating, comparisons_count, ident, name, created, edited, rcdb_id, rcdb_url) SELECT id, train_id, track_id, manufacturer_id, status, rcdb_image_url, cdn_image_url, opening_year, rating, comparisons_count, ident, name, created, edited, rcdb_id, rcdb_url FROM __temp__coaster');
        $this->addSql('DROP TABLE __temp__coaster');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F6312A7823BCD4D0 ON coaster (train_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F6312A785ED23C43 ON coaster (track_id)');
        $this->addSql('CREATE INDEX IDX_F6312A78A23B42D ON coaster (manufacturer_id)');
    }
}
