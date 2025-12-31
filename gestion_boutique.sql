
-- =========================================
-- Base de données et tables pour Gestion Boutique
-- =========================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS gestion_boutique;
USE gestion_boutique;

-- -------------------------------------------------
-- Table admin pour la connexion
-- -------------------------------------------------
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Création d'un admin par défaut (login: admin, mot de passe: 1234)
-- Si admin existe déjà, ignore l'insertion
INSERT INTO admin (username, password) 
SELECT 'admin', MD5('1234') 
WHERE NOT EXISTS (SELECT 1 FROM admin WHERE username='admin');

-- -------------------------------------------------
-- Table des produits
-- -------------------------------------------------
CREATE TABLE IF NOT EXISTS produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    categorie VARCHAR(255) NOT NULL,
    prix INT NOT NULL,
    stock INT NOT NULL,
    ventes INT DEFAULT 0
);

-- -------------------------------------------------
-- Table des ventes
-- -------------------------------------------------
CREATE TABLE IF NOT EXISTS ventes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit VARCHAR(255) NOT NULL,
    categorie VARCHAR(255) NOT NULL,
    prix INT NOT NULL,
    quantite INT NOT NULL,
    total INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
