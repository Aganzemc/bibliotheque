<?php
require_once 'config/database.php';

// Afficher les erreurs pour le debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 RÉINITIALISATION FORCÉE</h1>";

try {
    // 1. Vider la table users
    $pdo->exec("TRUNCATE TABLE users RESTART IDENTITY");
    echo "<p>✅ Table users vidée</p>";
    
    // 2. Créer un nouvel administrateur avec un mot de passe SIMPLE
    $email = 'admin@cps.com';
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role, statut) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Administrateur', $email, $hash, 'admin', 'actif']);
    echo "<p>✅ Administrateur créé avec: $email / $password</p>";
    
    // 3. Créer un bibliothécaire
    $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role, statut) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Bibliothécaire', 'biblio@cps.com', $hash, 'bibliothecaire', 'actif']);
    echo "<p>✅ Bibliothécaire créé avec: biblio@cps.com / $password</p>";
    
    // 4. Vérifier que le hash fonctionne
    $test = $pdo->prepare("SELECT password FROM users WHERE email = ?");
    $test->execute([$email]);
    $stored_hash = $test->fetchColumn();
    
    if (password_verify($password, $stored_hash)) {
        echo "<p style='color:green; font-size:1.2em;'>✅ VÉRIFICATION RÉUSSIE ! Le hash fonctionne parfaitement.</p>";
    } else {
        echo "<p style='color:red'>❌ Le hash ne fonctionne toujours pas...</p>";
    }
    
    echo "<hr>";
    echo "<h3>📋 Informations de connexion :</h3>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> admin@cps.com</li>";
    echo "<li><strong>Mot de passe:</strong> admin123</li>";
    echo "</ul>";
    
    echo "<br><a href='login_simple.php' class='btn btn-success' style='padding:10px 20px;'>Aller à la page de connexion simplifiée</a>";
    
} catch(Exception $e) {
    echo "<p style='color:red'>Erreur: " . $e->getMessage() . "</p>";
}
?>
