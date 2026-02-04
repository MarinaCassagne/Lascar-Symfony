<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204160158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, note INT NOT NULL, date_avis DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_8F91ABF0A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE etape_trajet (id INT AUTO_INCREMENT NOT NULL, latitude_etape DOUBLE PRECISION NOT NULL, longitude_etape DOUBLE PRECISION NOT NULL, trajet_id INT DEFAULT NULL, INDEX IDX_9C566F33D12A823 (trajet_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE moderation (id INT AUTO_INCREMENT NOT NULL, motif LONGTEXT NOT NULL, date_de_creation DATETIME NOT NULL, canal_de_moderation LONGTEXT NOT NULL, type_de_cible VARCHAR(255) NOT NULL, action_de_moderation VARCHAR(255) NOT NULL, id_trajet_id INT DEFAULT NULL, avis_id INT DEFAULT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_C0EA6AA48D271404 (id_trajet_id), UNIQUE INDEX UNIQ_C0EA6AA4197E709F (avis_id), UNIQUE INDEX UNIQ_C0EA6AA4A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_notification DATETIME NOT NULL, canal_de_notification VARCHAR(255) NOT NULL, statut_notification VARCHAR(255) NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, numero_reservation VARCHAR(255) NOT NULL, date_reservation DATETIME NOT NULL, longitude_point_de_depart_passager DOUBLE PRECISION NOT NULL, latitude_point_de_depart_passager DOUBLE PRECISION NOT NULL, longitude_point_arrive_passager DOUBLE PRECISION NOT NULL, latitude_point_arrive_passager DOUBLE PRECISION NOT NULL, longitude_point_de_rdv_passager DOUBLE PRECISION NOT NULL, latitude_point_de_rdv_passager DOUBLE PRECISION NOT NULL, date_heure_depart DATETIME NOT NULL, date_heure_arrive DATETIME NOT NULL, nombre_de_passager SMALLINT NOT NULL, montant_total_reservation NUMERIC(5, 2) NOT NULL, statut_reservation VARCHAR(255) NOT NULL, trajet_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_42C84955D12A823 (trajet_id), INDEX IDX_42C84955A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE solde (id INT AUTO_INCREMENT NOT NULL, montant_solde NUMERIC(5, 2) NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_66918367A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE trajet (id INT AUTO_INCREMENT NOT NULL, date_de_depart DATETIME NOT NULL, lieu_depart_conducteur VARCHAR(255) NOT NULL, lieu_arrivee_conducteur VARCHAR(255) NOT NULL, longitude_lieu_depart_conducteur DOUBLE PRECISION NOT NULL, latitude_lieu_depart_conducteur DOUBLE PRECISION NOT NULL, longitude_lieu_arrive_conducteur DOUBLE PRECISION NOT NULL, latitude_lieu_arrive_conducteur DOUBLE PRECISION NOT NULL, duree INT NOT NULL, nombre_de_km INT NOT NULL, nombre_de_place INT NOT NULL, prix NUMERIC(5, 2) NOT NULL, date_de_publication DATETIME NOT NULL, nature_trajet VARCHAR(255) NOT NULL, type_trajet VARCHAR(255) NOT NULL, statut_valide VARCHAR(255) NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_2B5BA98CA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, reference_transaction VARCHAR(255) NOT NULL, montant_transaction NUMERIC(5, 2) NOT NULL, date_de_paiement DATETIME NOT NULL, statut_paiement VARCHAR(255) NOT NULL, moyen_paiement LONGTEXT NOT NULL, id_compte_stripe INT DEFAULT NULL, user_id INT DEFAULT NULL, reservation_id INT NOT NULL, INDEX IDX_723705D1A76ED395 (user_id), INDEX IDX_723705D1B83297E7 (reservation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, age INT NOT NULL, telephone VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, permis_de_conduire TINYINT DEFAULT NULL, compte_valide TINYINT NOT NULL, solde_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649BC7F70A9 (solde_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vehicule (id INT AUTO_INCREMENT NOT NULL, marque VARCHAR(255) NOT NULL, modele VARCHAR(255) NOT NULL, couleur VARCHAR(255) NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_292FFF1DA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE etape_trajet ADD CONSTRAINT FK_9C566F33D12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id)');
        $this->addSql('ALTER TABLE moderation ADD CONSTRAINT FK_C0EA6AA48D271404 FOREIGN KEY (id_trajet_id) REFERENCES trajet (id)');
        $this->addSql('ALTER TABLE moderation ADD CONSTRAINT FK_C0EA6AA4197E709F FOREIGN KEY (avis_id) REFERENCES avis (id)');
        $this->addSql('ALTER TABLE moderation ADD CONSTRAINT FK_C0EA6AA4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955D12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE solde ADD CONSTRAINT FK_66918367A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE trajet ADD CONSTRAINT FK_2B5BA98CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649BC7F70A9 FOREIGN KEY (solde_id) REFERENCES solde (id)');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0A76ED395');
        $this->addSql('ALTER TABLE etape_trajet DROP FOREIGN KEY FK_9C566F33D12A823');
        $this->addSql('ALTER TABLE moderation DROP FOREIGN KEY FK_C0EA6AA48D271404');
        $this->addSql('ALTER TABLE moderation DROP FOREIGN KEY FK_C0EA6AA4197E709F');
        $this->addSql('ALTER TABLE moderation DROP FOREIGN KEY FK_C0EA6AA4A76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955D12A823');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE solde DROP FOREIGN KEY FK_66918367A76ED395');
        $this->addSql('ALTER TABLE trajet DROP FOREIGN KEY FK_2B5BA98CA76ED395');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A76ED395');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1B83297E7');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649BC7F70A9');
        $this->addSql('ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1DA76ED395');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE etape_trajet');
        $this->addSql('DROP TABLE moderation');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE solde');
        $this->addSql('DROP TABLE trajet');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE vehicule');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
