CREATE DATABASE IF NOT EXISTS bibliotheque_cps;
USE bibliotheque_cps;

-- Table des utilisateurs (admin / bibliothécaire)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'bibliothecaire', 'assistant') DEFAULT 'bibliothecaire',
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des catégories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des livres (avec numéro étagère et date d'édition)
CREATE TABLE livres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    auteur VARCHAR(150) NOT NULL,
    isbn VARCHAR(50),
    numero_etagere VARCHAR(50) NOT NULL,
    date_edition DATE NOT NULL,
    quantite_totale INT NOT NULL DEFAULT 1,
    quantite_disponible INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table livre_categorie (relation many-to-many)
CREATE TABLE livre_categorie (
    livre_id INT,
    categorie_id INT,
    FOREIGN KEY (livre_id) REFERENCES livres(id) ON DELETE CASCADE,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
    PRIMARY KEY (livre_id, categorie_id)
);

-- Table des membres (élèves/enseignants)
CREATE TABLE membres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100),
    classe VARCHAR(50) NOT NULL,
    type ENUM('eleve', 'enseignant') DEFAULT 'eleve',
    telephone VARCHAR(20),
    email VARCHAR(100),
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des emprunts
CREATE TABLE emprunts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    membre_id INT NOT NULL,
    livre_id INT NOT NULL,
    date_emprunt DATE NOT NULL,
    date_retour_prevue DATE NOT NULL,
    date_retour_reelle DATE NULL,
    statut ENUM('en_cours', 'retourne', 'en_retard') DEFAULT 'en_cours',
    amende_montant DECIMAL(10,2) DEFAULT 0,
    amende_payee BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (membre_id) REFERENCES membres(id),
    FOREIGN KEY (livre_id) REFERENCES livres(id)
);

-- Table des paramètres système
CREATE TABLE parametres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    duree_emprunt_jours INT DEFAULT 15,
    max_livres_par_membre INT DEFAULT 3,
    amende_par_jour DECIMAL(10,2) DEFAULT 5000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'retard',
    vue BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- INSERTIONS AVEC BONS HASHES POUR admin123


-- Insertion d'un utilisateur admin par défaut (password = admin123)
-- Le hash ci-dessous est généré avec password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (nom, email, password, role, statut) VALUES 
('Administrateur', 'admin@cps.com', 'admin123', 'admin', 'actif');

-- Insertion d'un bibliothécaire par défaut (password = admin123)
INSERT INTO users (nom, email, password, role, statut) VALUES 
('Bibliothécaire', 'biblio@cps.com', 'admin123', 'bibliothecaire', 'actif');

-- Insertion paramètres par défaut
INSERT INTO parametres (duree_emprunt_jours, max_livres_par_membre, amende_par_jour) VALUES (15, 3, 5000);

-- Insertion catégories exemple
INSERT INTO categories (nom, description) VALUES 
('Roman', 'Livres de fiction romanesque'),
('Science', 'Sciences et technologies'),
('Histoire', 'Livres historiques'),
('BD', 'Bandes dessinées');

-- Insertion livres exemple avec numéro étagère et date d'édition
INSERT INTO livres (titre, auteur, numero_etagere, date_edition, quantite_totale, quantite_disponible) VALUES
('Les Misérables', 'Victor Hugo', 'A12', '1862-01-01', 3, 3),
('1984', 'George Orwell', 'B05', '1949-06-08', 2, 2),
('Sapiens', 'Yuval Noah Harari', 'C08', '2011-01-01', 1, 1),
('Le Petit Prince', 'Antoine de Saint-Exupéry', 'A03', '1943-04-06', 5, 5),
('Germinal', 'Emile Zola', 'A12', '1885-01-01', 2, 2);

-- Insertion membres exemple
INSERT INTO membres (nom, prenom, classe, type) VALUES
('DIALLO', 'Mamadou', '6ème A', 'eleve'),
('KONE', 'Aminata', '4ème B', 'eleve'),
('TOURE', 'Ibrahim', '3ème C', 'eleve'),
('OUATTARA', 'Fatou', 'Terminale D', 'eleve'),
('SISSOKO', 'Drissa', 'Professeur', 'enseignant');

// Pour la colonne est configurée en NOT NULL
ALTER TABLE livres MODIFY date_edition DATE NULL; 

// pour nettoyer une base de donnée si il y a un nombre qui est superieur à la quantite totale
SELECT id, titre, quantite_totale, quantite_disponible 
FROM livres 
WHERE quantite_disponible > quantite_totale;