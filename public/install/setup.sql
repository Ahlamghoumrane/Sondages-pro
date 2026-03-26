-- =====================================================
-- SondagePro - Script de création de la base de données
-- =====================================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS sondages 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE sondages;

-- =====================================================
-- Table des sondages
-- =====================================================
CREATE TABLE IF NOT EXISTS sondages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question VARCHAR(500) NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NULL,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actif (actif),
    INDEX idx_dates (date_debut, date_fin)
) ENGINE=InnoDB;

-- =====================================================
-- Table des options de réponse
-- =====================================================
CREATE TABLE IF NOT EXISTS options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sondage_id INT NOT NULL,
    texte VARCHAR(300) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sondage_id) REFERENCES sondages(id) ON DELETE CASCADE,
    INDEX idx_sondage (sondage_id)
) ENGINE=InnoDB;

-- =====================================================
-- Table des votes
-- =====================================================
CREATE TABLE IF NOT EXISTS votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    option_id INT NOT NULL,
    ip_votant VARCHAR(45) NOT NULL,
    date_vote TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (option_id, ip_votant),
    INDEX idx_option (option_id),
    INDEX idx_ip (ip_votant),
    INDEX idx_date (date_vote)
) ENGINE=InnoDB;

-- =====================================================
-- Données de démonstration
-- =====================================================

-- Sondage 1: Actif maintenant
INSERT INTO sondages (question, date_debut, date_fin, actif) VALUES
('Quel est votre langage de programmation préféré ?', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), TRUE);

SET @sondage1 = LAST_INSERT_ID();

INSERT INTO options (sondage_id, texte) VALUES
(@sondage1, 'PHP'),
(@sondage1, 'JavaScript'),
(@sondage1, 'Python'),
(@sondage1, 'Java'),
(@sondage1, 'C#');

-- Sondage 2: Actif maintenant
INSERT INTO sondages (question, date_debut, date_fin, actif) VALUES
('Quel framework CSS utilisez-vous le plus ?', NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), TRUE);

SET @sondage2 = LAST_INSERT_ID();

INSERT INTO options (sondage_id, texte) VALUES
(@sondage2, 'Bootstrap'),
(@sondage2, 'Tailwind CSS'),
(@sondage2, 'Bulma'),
(@sondage2, 'Foundation'),
(@sondage2, 'Materialize');

-- Sondage 3: À venir
INSERT INTO sondages (question, date_debut, date_fin, actif) VALUES
('Quel est votre système de gestion de base de données favori ?', DATE_ADD(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 37 DAY), TRUE);

SET @sondage3 = LAST_INSERT_ID();

INSERT INTO options (sondage_id, texte) VALUES
(@sondage3, 'MySQL'),
(@sondage3, 'PostgreSQL'),
(@sondage3, 'MongoDB'),
(@sondage3, 'SQLite'),
(@sondage3, 'MariaDB');

-- Quelques votes de test pour le sondage 1
INSERT INTO votes (option_id, ip_votant) VALUES
((SELECT id FROM options WHERE sondage_id = @sondage1 AND texte = 'PHP'), '192.168.1.1'),
((SELECT id FROM options WHERE sondage_id = @sondage1 AND texte = 'PHP'), '192.168.1.2'),
((SELECT id FROM options WHERE sondage_id = @sondage1 AND texte = 'JavaScript'), '192.168.1.3'),
((SELECT id FROM options WHERE sondage_id = @sondage1 AND texte = 'JavaScript'), '192.168.1.4'),
((SELECT id FROM options WHERE sondage_id = @sondage1 AND texte = 'JavaScript'), '192.168.1.5'),
((SELECT id FROM options WHERE sondage_id = @sondage1 AND texte = 'Python'), '192.168.1.6'),
((SELECT id FROM options WHERE sondage_id = @sondage1 AND texte = 'Python'), '192.168.1.7'),
((SELECT id FROM options WHERE sondage_id = @sondage1 AND texte = 'Java'), '192.168.1.8'),
((SELECT id FROM options WHERE sondage_id = @sondage1 AND texte = 'C#'), '192.168.1.9');

-- Quelques votes de test pour le sondage 2
INSERT INTO votes (option_id, ip_votant) VALUES
((SELECT id FROM options WHERE sondage_id = @sondage2 AND texte = 'Bootstrap'), '192.168.2.1'),
((SELECT id FROM options WHERE sondage_id = @sondage2 AND texte = 'Bootstrap'), '192.168.2.2'),
((SELECT id FROM options WHERE sondage_id = @sondage2 AND texte = 'Bootstrap'), '192.168.2.3'),
((SELECT id FROM options WHERE sondage_id = @sondage2 AND texte = 'Tailwind CSS'), '192.168.2.4'),
((SELECT id FROM options WHERE sondage_id = @sondage2 AND texte = 'Tailwind CSS'), '192.168.2.5'),
((SELECT id FROM options WHERE sondage_id = @sondage2 AND texte = 'Bulma'), '192.168.2.6');

SELECT 'Base de données SondagePro installée avec succès !' AS message;
