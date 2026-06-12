<?php
require_once 'config/database.php';

echo "<h2>🔧 Correction de la connexion</h2>";

// Générer le bon hash pour 'admin123'
$correct_hash = password_hash('admin123', PASSWORD_DEFAULT);

echo "<p>Hash généré : <code>" . htmlspecialchars($correct_hash) . "</code></p>";

try {
    // Mettre à jour l'admin
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@cps.com'");
    $stmt->execute([$correct_hash]);
    echo "<p style='color:green'>✅ Mot de passe admin mis à jour</p>";
    
    // Mettre à jour le bibliothécaire
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'biblio@cps.com'");
    $stmt->execute([$correct_hash]);
    echo "<p style='color:green'>✅ Mot de passe biblio mis à jour</p>";
    
    // Vérifier que ça fonctionne
    $test = $pdo->prepare("SELECT password FROM users WHERE email = 'admin@cps.com'");
    $test->execute();
    $stored_hash = $test->fetchColumn();
    
    if (password_verify('admin123', $stored_hash)) {
        echo "<p style='color:green; font-size:1.2em;'>🎉 SUCCÈS ! La connexion fonctionne maintenant !</p>";
    } else {
        echo "<p style='color:red'>❌ Erreur : le hash ne fonctionne toujours pas</p>";
    }
    
    echo "<br><a href='login.php' class='btn btn-primary'>Aller à la page de connexion</a>";
    
} catch(Exception $e) {
    echo "<p style='color:red'>Erreur : " . $e->getMessage() . "</p>";
}
?>