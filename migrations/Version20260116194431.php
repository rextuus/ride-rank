<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116194431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player_coaster_rating ADD COLUMN presented INTEGER DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE player_coaster_rating ADD COLUMN skipped INTEGER DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__player_coaster_rating AS SELECT id, player_id, coaster_id, rating, games_played, wins, losses FROM player_coaster_rating');
        $this->addSql('DROP TABLE player_coaster_rating');
        $this->addSql('CREATE TABLE player_coaster_rating (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_id INTEGER NOT NULL, coaster_id INTEGER NOT NULL, rating DOUBLE PRECISION DEFAULT \'1200\' NOT NULL, games_played INTEGER DEFAULT 0 NOT NULL, wins INTEGER DEFAULT 0 NOT NULL, losses INTEGER DEFAULT 0 NOT NULL, CONSTRAINT FK_AB9F361999E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AB9F3619216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player_coaster_rating (id, player_id, coaster_id, rating, games_played, wins, losses) SELECT id, player_id, coaster_id, rating, games_played, wins, losses FROM __temp__player_coaster_rating');
        $this->addSql('DROP TABLE __temp__player_coaster_rating');
        $this->addSql('CREATE INDEX IDX_AB9F361999E6F5DF ON player_coaster_rating (player_id)');
        $this->addSql('CREATE INDEX IDX_AB9F3619216303C ON player_coaster_rating (coaster_id)');
    }
}
