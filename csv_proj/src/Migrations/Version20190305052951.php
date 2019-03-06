<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190305052951 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql( "CREATE TABLE IF NOT EXISTS tblProductData (
              intProductDataId int(10) unsigned NOT NULL AUTO_INCREMENT,
              strProductName varchar(50) NOT NULL,
              strProductDesc varchar(255) NOT NULL,
              strProductCode varchar(10) NOT NULL,
              dtmAdded datetime DEFAULT NULL,
              dtmDiscontinued datetime DEFAULT NULL,
              stmTimestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (intProductDataId),
              UNIQUE KEY (strProductCode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores product data';");

        $this->addSql(" ALTER TABLE `tblProductData` COLLATE 'utf8_general_ci';");

        $this->addSql(" ALTER TABLE `tblProductData`
                CHANGE `strProductName` `strProductName` varchar(50) COLLATE 'utf8_general_ci',
                CHANGE `strProductDesc` `strProductDesc` varchar(255) COLLATE 'utf8_general_ci',
                CHANGE `strProductCode` `strProductCode` varchar(10) COLLATE 'utf8_general_ci'
              ;");

        $this->addSql("ALTER TABLE tblProductData ADD strProductPrice INT UNSIGNED DEFAULT NULL, ADD strProductStock INT UNSIGNED NOT NULL DEFAULT '0';");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tblProductData DROP strProductPrice, DROP strProductStock');
    }
}
