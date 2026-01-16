<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114161245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ident VARCHAR(255) DEFAULT NULL COLLATE "BINARY", name VARCHAR(255) DEFAULT NULL COLLATE "BINARY", created DATETIME NOT NULL, edited DATETIME DEFAULT NULL, rcdb_id INTEGER DEFAULT NULL, rcdb_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY")');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C144E78B2 ON category (ident)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE coaster (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, train_id INTEGER DEFAULT NULL, track_id INTEGER DEFAULT NULL, manufacturer_id INTEGER DEFAULT NULL, status VARCHAR(255) DEFAULT \'Operating since\' NOT NULL COLLATE "BINARY", rcdb_image_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY", cdn_image_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY", opening_year SMALLINT DEFAULT NULL, rating DOUBLE PRECISION DEFAULT \'1200\' NOT NULL, comparisons_count INTEGER DEFAULT 0 NOT NULL, ident VARCHAR(255) DEFAULT NULL COLLATE "BINARY", name VARCHAR(255) DEFAULT NULL COLLATE "BINARY", created DATETIME NOT NULL, edited DATETIME DEFAULT NULL, rcdb_id INTEGER DEFAULT NULL, rcdb_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY", CONSTRAINT FK_F6312A7823BCD4D0 FOREIGN KEY (train_id) REFERENCES train (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F6312A785ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F6312A78A23B42D FOREIGN KEY (manufacturer_id) REFERENCES manufacturer (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F6312A78A23B42D ON coaster (manufacturer_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F6312A785ED23C43 ON coaster (track_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F6312A7823BCD4D0 ON coaster (train_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE coaster_category (coaster_id INTEGER NOT NULL, category_id INTEGER NOT NULL, PRIMARY KEY(coaster_id, category_id), CONSTRAINT FK_C69E1710216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C69E171012469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C69E171012469DE2 ON coaster_category (category_id)');
        $this->addSql('CREATE INDEX IDX_C69E1710216303C ON coaster_category (coaster_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE coaster_detail (coaster_id INTEGER NOT NULL, detail_id INTEGER NOT NULL, PRIMARY KEY(coaster_id, detail_id), CONSTRAINT FK_91C9F841216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_91C9F841D8D003BB FOREIGN KEY (detail_id) REFERENCES detail (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_91C9F841D8D003BB ON coaster_detail (detail_id)');
        $this->addSql('CREATE INDEX IDX_91C9F841216303C ON coaster_detail (coaster_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE coaster_location (coaster_id INTEGER NOT NULL, location_id INTEGER NOT NULL, PRIMARY KEY(coaster_id, location_id), CONSTRAINT FK_9E4C871A216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9E4C871A64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_9E4C871A64D218E ON coaster_location (location_id)');
        $this->addSql('CREATE INDEX IDX_9E4C871A216303C ON coaster_location (coaster_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE coaster_metadata (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, coaster_id INTEGER NOT NULL, images CLOB DEFAULT NULL COLLATE "BINARY" --(DC2Type:json)
        , status_dates CLOB DEFAULT NULL COLLATE "BINARY" --(DC2Type:json)
        , CONSTRAINT FK_8FC63AC5216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8FC63AC5216303C ON coaster_metadata (coaster_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE coaster_model (coaster_id INTEGER NOT NULL, model_id INTEGER NOT NULL, PRIMARY KEY(coaster_id, model_id), CONSTRAINT FK_C873B6EA216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C873B6EA7975B7E7 FOREIGN KEY (model_id) REFERENCES model (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C873B6EA7975B7E7 ON coaster_model (model_id)');
        $this->addSql('CREATE INDEX IDX_C873B6EA216303C ON coaster_model (coaster_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE detail (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, content VARCHAR(255) NOT NULL COLLATE "BINARY", type VARCHAR(255) DEFAULT NULL COLLATE "BINARY", created DATETIME NOT NULL, edited DATETIME DEFAULT NULL, ident VARCHAR(255) DEFAULT NULL COLLATE "BINARY", name VARCHAR(255) DEFAULT NULL COLLATE "BINARY")');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2E067F9344E78B2 ON detail (ident)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE location (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(255) DEFAULT \'not_determined\' NOT NULL COLLATE "BINARY", ident VARCHAR(255) DEFAULT NULL COLLATE "BINARY", name VARCHAR(255) DEFAULT NULL COLLATE "BINARY", rcdb_id INTEGER DEFAULT NULL, rcdb_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY", created DATETIME NOT NULL, edited DATETIME DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5E9E89CB44E78B2 ON location (ident)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE manufacturer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ident VARCHAR(255) DEFAULT NULL COLLATE "BINARY", name VARCHAR(255) DEFAULT NULL COLLATE "BINARY", rcdb_id INTEGER DEFAULT NULL, rcdb_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY", created DATETIME NOT NULL, edited DATETIME DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3D0AE6DC44E78B2 ON manufacturer (ident)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL COLLATE "BINARY", headers CLOB NOT NULL COLLATE "BINARY", queue_name VARCHAR(190) NOT NULL COLLATE "BINARY", created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , available_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE model (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ident VARCHAR(255) DEFAULT NULL COLLATE "BINARY", name VARCHAR(255) DEFAULT NULL COLLATE "BINARY", created DATETIME NOT NULL, edited DATETIME DEFAULT NULL, rcdb_id INTEGER DEFAULT NULL, rcdb_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY")');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D79572D944E78B2 ON model (ident)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE pairwise_comparison (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, coaster_a_id INTEGER NOT NULL, coaster_b_id INTEGER NOT NULL, winner_id INTEGER NOT NULL, loser_id INTEGER NOT NULL, player_id BLOB NOT NULL --(DC2Type:uuid)
        , response_time_ms INTEGER DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5557A8E7990E8B FOREIGN KEY (coaster_a_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E152CA165 FOREIGN KEY (coaster_b_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E1BCAA5F6 FOREIGN KEY (loser_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5557A8E99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_created_at ON pairwise_comparison (created_at)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_coaster_b ON pairwise_comparison (coaster_b_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_coaster_a ON pairwise_comparison (coaster_a_id)');
        $this->addSql('CREATE INDEX idx_pairwise_comparison_player ON pairwise_comparison (player_id)');
        $this->addSql('CREATE INDEX IDX_5557A8E1BCAA5F6 ON pairwise_comparison (loser_id)');
        $this->addSql('CREATE INDEX IDX_5557A8E5DFCD4B8 ON pairwise_comparison (winner_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE park (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ident VARCHAR(255) DEFAULT NULL COLLATE "BINARY", name VARCHAR(255) DEFAULT NULL COLLATE "BINARY", rcdb_id INTEGER DEFAULT NULL, rcdb_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY")');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE player (id BLOB NOT NULL --(DC2Type:uuid)
        , user_id INTEGER DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , last_seen_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , device_hash VARCHAR(64) DEFAULT NULL COLLATE "BINARY", anonymous BOOLEAN NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_98197A65A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65A76ED395 ON player (user_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE player_coaster_affinity (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_id BLOB NOT NULL --(DC2Type:uuid)
        , coaster_id INTEGER NOT NULL, exposure_count INTEGER DEFAULT 0 NOT NULL, win_count INTEGER DEFAULT 0 NOT NULL, loss_count INTEGER DEFAULT 0 NOT NULL, confidence_score DOUBLE PRECISION DEFAULT \'0\' NOT NULL, last_seen_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_4FA765799E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4FA7657216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX uniq_affinity_player_coaster ON player_coaster_affinity (player_id, coaster_id)');
        $this->addSql('CREATE INDEX idx_player_coaster_affinity_player ON player_coaster_affinity (player_id)');
        $this->addSql('CREATE INDEX idx_player_coaster_affinity_coaster ON player_coaster_affinity (coaster_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE player_coaster_rating (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_id BLOB NOT NULL --(DC2Type:uuid)
        , coaster_id INTEGER NOT NULL, rating DOUBLE PRECISION DEFAULT \'1200\' NOT NULL, games_played INTEGER DEFAULT 0 NOT NULL, wins INTEGER DEFAULT 0 NOT NULL, losses INTEGER DEFAULT 0 NOT NULL, CONSTRAINT FK_AB9F361999E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AB9F3619216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_AB9F3619216303C ON player_coaster_rating (coaster_id)');
        $this->addSql('CREATE INDEX IDX_AB9F361999E6F5DF ON player_coaster_rating (player_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE ridden_coaster (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player_id BLOB NOT NULL --(DC2Type:uuid)
        , coaster_id INTEGER NOT NULL, ridden BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_DCB1ED3A99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DCB1ED3A216303C FOREIGN KEY (coaster_id) REFERENCES coaster (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX uniq_ridden_player_coaster ON ridden_coaster (player_id, coaster_id)');
        $this->addSql('CREATE INDEX IDX_DCB1ED3A99E6F5DF ON ridden_coaster (player_id)');
        $this->addSql('CREATE INDEX IDX_DCB1ED3A216303C ON ridden_coaster (coaster_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE track (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, length DOUBLE PRECISION DEFAULT NULL, height DOUBLE PRECISION DEFAULT NULL, "drop" DOUBLE PRECISION DEFAULT NULL, speed DOUBLE PRECISION DEFAULT NULL, inversions INTEGER DEFAULT NULL, duration INTEGER DEFAULT NULL, vertical_angle INTEGER DEFAULT NULL, created DATETIME NOT NULL, edited DATETIME DEFAULT NULL)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE track_element (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, ident VARCHAR(255) DEFAULT NULL COLLATE "BINARY", name VARCHAR(255) DEFAULT NULL COLLATE "BINARY", rcdb_id INTEGER DEFAULT NULL, rcdb_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY", created DATETIME NOT NULL, edited DATETIME DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98DEC62444E78B2 ON track_element (ident)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE track_track_element (track_id INTEGER NOT NULL, track_element_id INTEGER NOT NULL, PRIMARY KEY(track_id, track_element_id), CONSTRAINT FK_192723985ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_19272398A5CEA48F FOREIGN KEY (track_element_id) REFERENCES track_element (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_19272398A5CEA48F ON track_track_element (track_element_id)');
        $this->addSql('CREATE INDEX IDX_192723985ED23C43 ON track_track_element (track_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE train (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, restraint_id INTEGER DEFAULT NULL, built_by_id INTEGER DEFAULT NULL, arrangement VARCHAR(255) DEFAULT NULL COLLATE "BINARY", created DATETIME NOT NULL, edited DATETIME DEFAULT NULL, CONSTRAINT FK_5C66E4A3950622D7 FOREIGN KEY (restraint_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5C66E4A38DE1CBC1 FOREIGN KEY (built_by_id) REFERENCES manufacturer (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5C66E4A38DE1CBC1 ON train (built_by_id)');
        $this->addSql('CREATE INDEX IDX_5C66E4A3950622D7 ON train (restraint_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE unprocessable_entry (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rcdb_id INTEGER NOT NULL, reprocessed BOOLEAN NOT NULL)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL COLLATE "BINARY", roles CLOB NOT NULL COLLATE "BINARY" --(DC2Type:json)
        , password VARCHAR(255) NOT NULL COLLATE "BINARY", is_verified BOOLEAN NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE category');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE coaster');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE coaster_category');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE coaster_detail');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE coaster_location');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE coaster_metadata');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE coaster_model');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE detail');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE location');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE manufacturer');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE messenger_messages');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE model');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE pairwise_comparison');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE park');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE player');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE player_coaster_affinity');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE player_coaster_rating');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE ridden_coaster');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE track');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE track_element');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE track_track_element');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE train');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE unprocessable_entry');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\SqlitePlatform'."
        );

        $this->addSql('DROP TABLE user');
    }
}
