<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210401114208 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE postal_voting_registration (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', email VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, student_number VARCHAR(255) NOT NULL, voting_kit_requested TINYINT(1) NOT NULL, secret VARCHAR(255) NOT NULL, confirmation_token VARCHAR(255) NOT NULL, confirmation_date DATETIME DEFAULT NULL, printed TINYINT(1) NOT NULL, counted TINYINT(1) NOT NULL, last_modified DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, creation_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, addressstreet_and_number VARCHAR(255) NOT NULL, addressaddress_addition VARCHAR(255) NOT NULL, addresspostal_code VARCHAR(255) NOT NULL, addresscity VARCHAR(255) NOT NULL, addresscountry VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8017F476E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE postal_voting_registration');
    }
}
