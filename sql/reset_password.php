<?php
require_once 'config/database.php';

echo "<h2>🔐 Réinitialisation des mots de passe</h2>";

// Les mots de passe que vous voulez
$admin_password = 'admin123';
$biblio_password = 'admin123';

// Générer les hashs corrects
$admin_hash = password_hash($admin_password, PASSWORD_DEFAULT);
$biblio_hash = password_hash($biblio_password, PASSWORD_DEFAULT);

echo "<p>Hash généré pour admin : <code>$admin_hash</code></p>";
echo "<p>Hash généré pour biblio : <code>$biblio_hash</code></p>";

try {
    // Vérifier si les utilisateurs existent
    $check_admin = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@cps.com'");
    $check_admin->execute();
    
    if ($check_admin->rowCount() > 0) {
        // Mettre à jour
        $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@cps.com'")->execute([$admin_hash]);
        $pdo->prepare("UPDATE users SET password = ? WHERE email = 'biblio@cps.com'")->execute([$biblio_hash]);
        echo "<p style='color:green'>✅ Mots de passe mis à jour avec succès !</p>";
    } else {
        // Insérer de nouveaux utilisateurs
        $pdo->prepare("INSERT INTO users (nom, email, password, role, statut) VALUES (?, ?, ?, ?, ?)")
            ->execute(['Administrateur', 'admin@cps.com', $admin_hash, 'admin', 'actif']);
        $pdo->prepare("INSERT INTO users (nom, email, password, role, statut) VALUES (?, ?, ?, ?, ?)")
            ->execute(['Bibliothécaire', 'biblio@cps.com', $biblio_hash, 'bibliothecaire', 'actif']);
        echo "<p style='color:green'>✅ Utilisateurs créés avec succès !</p>";
    }
    
    echo "<h3>🎉 Vous pouvez maintenant vous connecter :</h3>";
    echo "<ul>";
    echo "<li><strong>Admin :</strong> admin@cps.com / admin123</li>";
    echo "<li><strong>Bibliothécaire :</strong> biblio@cps.com / admin123</li>";
    echo "</ul>";
    
    echo "<br><a href='login.php' class='btn btn-primary'>Aller à la page de connexion</a>";
    
} catch(Exception $e) {
    echo "<p style='color:red'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>