<?php
require_once 'config/database.php';

echo "<h2>🔍 Vérification des mots de passe</h2>";

$users = $pdo->query("SELECT id, nom, email, password FROM users")->fetchAll();

foreach ($users as $user) {
    echo "<h3>Utilisateur : {$user['nom']} ({$user['email']})</h3>";
    echo "Hash stocké : <code>{$user['password']}</code><br>";
    
    // Tester le mot de passe 'admin123'
    if (password_verify('admin123', $user['password'])) {
        echo "<span style='color:green'>✅ Le mot de passe 'admin123' est CORRECT pour cet utilisateur</span><br>";
    } else {
        echo "<span style='color:red'>❌ Le mot de passe 'admin123' est INCORRECT pour cet utilisateur</span><br>";
    }
    
    echo "<hr>";
}
?>