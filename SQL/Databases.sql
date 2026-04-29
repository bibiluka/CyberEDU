CREATE DATABASE IF NOT EXISTS CyberEDU CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE CyberEDU;

CREATE USER 'webmaster'@'localhost' IDENTIFIED BY 'Admin123';

GRANT ALL PRIVILEGES ON *.* TO 'webmaster'@'localhost' WITH GRANT OPTION;

FLUSH PRIVILEGES;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Sera utilisé avec password_hash() en PHP
    role ENUM('eleve', 'professeur', 'admin') DEFAULT 'eleve',
    points_total INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS signalements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_incident VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    statut ENUM('en_attente', 'traite', 'archive') DEFAULT 'en_attente',
    date_signalement TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reservations_cantine (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date_repas DATE NOT NULL,
    code_validation VARCHAR(10) UNIQUE NOT NULL, -- Le code du QR Code
    consomme TINYINT(1) DEFAULT 0, -- 0 = non passé, 1 = repas pris
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS usage_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    temps_ecran_min INT NOT NULL,
    nb_ouvertures INT NOT NULL,
    points_gagnes INT DEFAULT 0,
    date_collecte DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expediteur_id INT,
    contenu TEXT,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expediteur_id) REFERENCES users(id) ON DELETE CASCADE
);