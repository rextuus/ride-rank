<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116154837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__pairwise_comparison AS SELECT id, coaster_a_id, coaster_b_id, winner_id, loser_id, player_id, response_time_ms, created_at FROM pairwise_comparison');
        $this->addSql('DROP TABLE pairwise_comparison');
        $this->addSql('CREATE TABLE pairwise_comparison (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, coaster_a_id INTEGER NOT NULL, coaster_b_id INTEGER NOT NULL, winner_id INTEGER DEFAULT NULL, loser_id INTEGER DEFAULT NULL, player_id INTEGER NOT NULL, response_time_ms INTEGER DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , outcome VARCHAR(10) DEFAULT \'win\' NOT NULL, CONSTRAINT FK_5557A8E7990E8B FOREIGN KEY (coaster_a_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E152CA165 FOREIGN KEY (coaster_b_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E1BCAA5F6 FOREIGN KEY (loser_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO pairwise_comparison (id, coaster_a_id, coaster_b_id, winner_id, loser_id, player_id, response_time_ms, created_at) SELECT id, coaster_a_id, coaster_b_id, winner_id, loser_id, player_id, response_time_ms, created_at FROM __temp__pairwise_comparison');
        $this->addSql('DROP TABLE __temp__pairwise_comparison');
        $this->addSql('CREATE INDEX IDX_5557A8E5DFCD4B8 ON pairwise_comparison (winner_id)');
        $this->addSql('CREATE INDEX IDX_5557A8E1BCAA5F6 ON pairwise_comparison (loser_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_player ON pairwise_comparison (player_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_coaster_a ON pairwise_comparison (coaster_a_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_coaster_b ON pairwise_comparison (coaster_b_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_created_at ON pairwise_comparison (created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__pairwise_comparison AS SELECT id, coaster_a_id, coaster_b_id, winner_id, loser_id, player_id, response_time_ms, created_at FROM pairwise_comparison');
        $this->addSql('DROP TABLE pairwise_comparison');
        $this->addSql('CREATE TABLE pairwise_comparison (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, coaster_a_id INTEGER NOT NULL, coaster_b_id INTEGER NOT NULL, winner_id INTEGER NOT NULL, loser_id INTEGER NOT NULL, player_id INTEGER NOT NULL, response_time_ms INTEGER DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5557A8E7990E8B FOREIGN KEY (coaster_a_id) REFERENCES coaster (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E152CA165 FOREIGN KEY (coaster_b_id) REFERENCES coaster (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES coaster (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E1BCAA5F6 FOREIGN KEY (loser_id) REFERENCES coaster (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO pairwise_comparison (id, coaster_a_id, coaster_b_id, winner_id, loser_id, player_id, response_time_ms, created_at) SELECT id, coaster_a_id, coaster_b_id, winner_id, loser_id, player_id, response_time_ms, created_at FROM __temp__pairwise_comparison');
        $this->addSql('DROP TABLE __temp__pairwise_comparison');
        $this->addSql('CREATE INDEX IDX_5557A8E5DFCD4B8 ON pairwise_comparison (winner_id)');
        $this->addSql('CREATE INDEX IDX_5557A8E1BCAA5F6 ON pairwise_comparison (loser_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_player ON pairwise_comparison (player_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_coaster_a ON pairwise_comparison (coaster_a_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_coaster_b ON pairwise_comparison (coaster_b_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_created_at ON pairwise_comparison (created_at)');
    }
}
