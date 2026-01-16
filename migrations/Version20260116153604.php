<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116153604 extends AbstractMigration
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
        $this->addSql('CREATE TABLE pairwise_comparison (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, coaster_a_id INTEGER NOT NULL, coaster_b_id INTEGER NOT NULL, winner_id INTEGER NOT NULL, loser_id INTEGER NOT NULL, player_id INTEGER NOT NULL, response_time_ms INTEGER DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5557A8E7990E8B FOREIGN KEY (coaster_a_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E152CA165 FOREIGN KEY (coaster_b_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E1BCAA5F6 FOREIGN KEY (loser_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO pairwise_comparison (id, coaster_a_id, coaster_b_id, winner_id, loser_id, player_id, response_time_ms, created_at) SELECT id, coaster_a_id, coaster_b_id, winner_id, loser_id, player_id, response_time_ms, created_at FROM __temp__pairwise_comparison');
        $this->addSql('DROP TABLE __temp__pairwise_comparison');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_created_at ON pairwise_comparison (created_at)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_coaster_b ON pairwise_comparison (coaster_b_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_coaster_a ON pairwise_comparison (coaster_a_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_player ON pairwise_comparison (player_id)');
        $this->addSql('CREATE INDEX IDX_5557A8E1BCAA5F6 ON pairwise_comparison (loser_id)');
        $this->addSql('CREATE INDEX IDX_5557A8E5DFCD4B8 ON pairwise_comparison (winner_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__player AS SELECT id, user_id, home_country_id, created_at, last_seen_at, device_hash, anonymous, experience FROM player');
        $this->addSql('DROP TABLE player');
        $this->addSql('CREATE TABLE player (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, home_country_id INTEGER DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , last_seen_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , device_hash VARCHAR(64) DEFAULT NULL, anonymous BOOLEAN NOT NULL, experience INTEGER DEFAULT 0 NOT NULL, CONSTRAINT FK_98197A65A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_98197A6588E06F80 FOREIGN KEY (home_country_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player (id, user_id, home_country_id, created_at, last_seen_at, device_hash, anonymous, experience) SELECT id, user_id, home_country_id, created_at, last_seen_at, device_hash, anonymous, experience FROM __temp__player');
        $this->addSql('DROP TABLE __temp__player');
        $this->addSql('CREATE INDEX IDX_98197A6588E06F80 ON player (home_country_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65A76ED395 ON player (user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__player_coaster_affinity AS SELECT id, player_id, coaster_id, exposure_count, win_count, loss_count, confidence_score, last_seen_at FROM player_coaster_affinity');
        $this->addSql('DROP TABLE player_coaster_affinity');
        $this->addSql('CREATE TABLE player_coaster_affinity (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_id INTEGER NOT NULL, coaster_id INTEGER NOT NULL, exposure_count INTEGER DEFAULT 0 NOT NULL, win_count INTEGER DEFAULT 0 NOT NULL, loss_count INTEGER DEFAULT 0 NOT NULL, confidence_score DOUBLE PRECISION DEFAULT \'0\' NOT NULL, last_seen_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_4FA765799E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4FA7657216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player_coaster_affinity (id, player_id, coaster_id, exposure_count, win_count, loss_count, confidence_score, last_seen_at) SELECT id, player_id, coaster_id, exposure_count, win_count, loss_count, confidence_score, last_seen_at FROM __temp__player_coaster_affinity');
        $this->addSql('DROP TABLE __temp__player_coaster_affinity');
        $this->addSql('CREATE UNIQUE INDEX uniq_affinity_player_coaster ON player_coaster_affinity (player_id, coaster_id)');
        $this->addSql('CREATE INDEX idx_player_coaster_affinity_coaster ON player_coaster_affinity (coaster_id)');
        $this->addSql('CREATE INDEX idx_player_coaster_affinity_player ON player_coaster_affinity (player_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__player_coaster_rating AS SELECT id, player_id, coaster_id, rating, games_played, wins, losses FROM player_coaster_rating');
        $this->addSql('DROP TABLE player_coaster_rating');
        $this->addSql('CREATE TABLE player_coaster_rating (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_id INTEGER NOT NULL, coaster_id INTEGER NOT NULL, rating DOUBLE PRECISION DEFAULT \'1200\' NOT NULL, games_played INTEGER DEFAULT 0 NOT NULL, wins INTEGER DEFAULT 0 NOT NULL, losses INTEGER DEFAULT 0 NOT NULL, CONSTRAINT FK_AB9F361999E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AB9F3619216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player_coaster_rating (id, player_id, coaster_id, rating, games_played, wins, losses) SELECT id, player_id, coaster_id, rating, games_played, wins, losses FROM __temp__player_coaster_rating');
        $this->addSql('DROP TABLE __temp__player_coaster_rating');
        $this->addSql('CREATE INDEX IDX_AB9F3619216303C ON player_coaster_rating (coaster_id)');
        $this->addSql('CREATE INDEX IDX_AB9F361999E6F5DF ON player_coaster_rating (player_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ridden_coaster AS SELECT id, player_id, coaster_id, ridden, created_at FROM ridden_coaster');
        $this->addSql('DROP TABLE ridden_coaster');
        $this->addSql('CREATE TABLE ridden_coaster (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_id INTEGER NOT NULL, coaster_id INTEGER NOT NULL, ridden BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_DCB1ED3A99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DCB1ED3A216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO ridden_coaster (id, player_id, coaster_id, ridden, created_at) SELECT id, player_id, coaster_id, ridden, created_at FROM __temp__ridden_coaster');
        $this->addSql('DROP TABLE __temp__ridden_coaster');
        $this->addSql('CREATE UNIQUE INDEX uniq_ridden_player_coaster ON ridden_coaster (player_id, coaster_id)');
        $this->addSql('CREATE INDEX IDX_DCB1ED3A216303C ON ridden_coaster (coaster_id)');
        $this->addSql('CREATE INDEX IDX_DCB1ED3A99E6F5DF ON ridden_coaster (player_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__pairwise_comparison AS SELECT id, coaster_a_id, coaster_b_id, winner_id, loser_id, player_id, response_time_ms, created_at FROM pairwise_comparison');
        $this->addSql('DROP TABLE pairwise_comparison');
        $this->addSql('CREATE TABLE pairwise_comparison (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, coaster_a_id INTEGER NOT NULL, coaster_b_id INTEGER NOT NULL, winner_id INTEGER NOT NULL, loser_id INTEGER NOT NULL, player_id BLOB NOT NULL --(DC2Type:uuid)
        , response_time_ms INTEGER DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5557A8E7990E8B FOREIGN KEY (coaster_a_id) REFERENCES coaster (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E152CA165 FOREIGN KEY (coaster_b_id) REFERENCES coaster (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES coaster (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E1BCAA5F6 FOREIGN KEY (loser_id) REFERENCES coaster (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO pairwise_comparison (id, coaster_a_id, coaster_b_id, winner_id, loser_id, player_id, response_time_ms, created_at) SELECT id, coaster_a_id, coaster_b_id, winner_id, loser_id, player_id, response_time_ms, created_at FROM __temp__pairwise_comparison');
        $this->addSql('DROP TABLE __temp__pairwise_comparison');
        $this->addSql('CREATE INDEX IDX_5557A8E5DFCD4B8 ON pairwise_comparison (winner_id)');
        $this->addSql('CREATE INDEX IDX_5557A8E1BCAA5F6 ON pairwise_comparison (loser_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_player ON pairwise_comparison (player_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_coaster_a ON pairwise_comparison (coaster_a_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_coaster_b ON pairwise_comparison (coaster_b_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_created_at ON pairwise_comparison (created_at)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__player AS SELECT id, user_id, home_country_id, created_at, last_seen_at, device_hash, anonymous, experience FROM player');
        $this->addSql('DROP TABLE player');
        $this->addSql('CREATE TABLE player (id BLOB NOT NULL --(DC2Type:uuid)
        , user_id INTEGER DEFAULT NULL, home_country_id INTEGER DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , last_seen_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , device_hash VARCHAR(64) DEFAULT NULL, anonymous BOOLEAN NOT NULL, experience INTEGER DEFAULT 0 NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_98197A65A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_98197A6588E06F80 FOREIGN KEY (home_country_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player (id, user_id, home_country_id, created_at, last_seen_at, device_hash, anonymous, experience) SELECT id, user_id, home_country_id, created_at, last_seen_at, device_hash, anonymous, experience FROM __temp__player');
        $this->addSql('DROP TABLE __temp__player');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65A76ED395 ON player (user_id)');
        $this->addSql('CREATE INDEX IDX_98197A6588E06F80 ON player (home_country_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__player_coaster_affinity AS SELECT id, player_id, coaster_id, exposure_count, win_count, loss_count, confidence_score, last_seen_at FROM player_coaster_affinity');
        $this->addSql('DROP TABLE player_coaster_affinity');
        $this->addSql('CREATE TABLE player_coaster_affinity (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_id BLOB NOT NULL --(DC2Type:uuid)
        , coaster_id INTEGER NOT NULL, exposure_count INTEGER DEFAULT 0 NOT NULL, win_count INTEGER DEFAULT 0 NOT NULL, loss_count INTEGER DEFAULT 0 NOT NULL, confidence_score DOUBLE PRECISION DEFAULT \'0\' NOT NULL, last_seen_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_4FA765799E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4FA7657216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player_coaster_affinity (id, player_id, coaster_id, exposure_count, win_count, loss_count, confidence_score, last_seen_at) SELECT id, player_id, coaster_id, exposure_count, win_count, loss_count, confidence_score, last_seen_at FROM __temp__player_coaster_affinity');
        $this->addSql('DROP TABLE __temp__player_coaster_affinity');
        $this->addSql('CREATE INDEX idx_player_coaster_affinity_player ON player_coaster_affinity (player_id)');
        $this->addSql('CREATE INDEX idx_player_coaster_affinity_coaster ON player_coaster_affinity (coaster_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_affinity_player_coaster ON player_coaster_affinity (player_id, coaster_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__player_coaster_rating AS SELECT id, player_id, coaster_id, rating, games_played, wins, losses FROM player_coaster_rating');
        $this->addSql('DROP TABLE player_coaster_rating');
        $this->addSql('CREATE TABLE player_coaster_rating (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_id BLOB NOT NULL --(DC2Type:uuid)
        , coaster_id INTEGER NOT NULL, rating DOUBLE PRECISION DEFAULT \'1200\' NOT NULL, games_played INTEGER DEFAULT 0 NOT NULL, wins INTEGER DEFAULT 0 NOT NULL, losses INTEGER DEFAULT 0 NOT NULL, CONSTRAINT FK_AB9F361999E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AB9F3619216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player_coaster_rating (id, player_id, coaster_id, rating, games_played, wins, losses) SELECT id, player_id, coaster_id, rating, games_played, wins, losses FROM __temp__player_coaster_rating');
        $this->addSql('DROP TABLE __temp__player_coaster_rating');
        $this->addSql('CREATE INDEX IDX_AB9F361999E6F5DF ON player_coaster_rating (player_id)');
        $this->addSql('CREATE INDEX IDX_AB9F3619216303C ON player_coaster_rating (coaster_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__ridden_coaster AS SELECT id, player_id, coaster_id, ridden, created_at FROM ridden_coaster');
        $this->addSql('DROP TABLE ridden_coaster');
        $this->addSql('CREATE TABLE ridden_coaster (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_id BLOB NOT NULL --(DC2Type:uuid)
        , coaster_id INTEGER NOT NULL, ridden BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_DCB1ED3A99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DCB1ED3A216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO ridden_coaster (id, player_id, coaster_id, ridden, created_at) SELECT id, player_id, coaster_id, ridden, created_at FROM __temp__ridden_coaster');
        $this->addSql('DROP TABLE __temp__ridden_coaster');
        $this->addSql('CREATE INDEX IDX_DCB1ED3A99E6F5DF ON ridden_coaster (player_id)');
        $this->addSql('CREATE INDEX IDX_DCB1ED3A216303C ON ridden_coaster (coaster_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_ridden_player_coaster ON ridden_coaster (player_id, coaster_id)');
    }
}
