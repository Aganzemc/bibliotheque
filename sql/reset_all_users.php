<?php
require_once 'config/database.php';

try {
    // Supprimer tous les utilisateurs
    $pdo->exec("DELETE FROM users");
    
    // Créer un nouvel admin avec un mot de passe simple
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role, statut) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Administrateur', 'admin@cps.com', $hash, 'admin', 'actif']);
    
    $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role, statut) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Bibliothécaire', 'biblio@cps.com', $hash, 'bibliothecaire', 'actif']);
    
    echo "<h2 style='color:green'>✅ Base de données réinitialisée !</h2>";
    echo "<p>Utilisateurs recréés avec succès.</p>";
    echo "<p><strong>admin@cps.com</strong> / <strong>admin123</strong></p>";
    echo "<p><strong>biblio@cps.com</strong> / <strong>admin123</strong></p>";
    echo "<br><a href='login.php' class='btn btn-primary'>Aller à la connexion</a>";
    
} catch(Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>