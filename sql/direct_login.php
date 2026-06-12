<?php
require_once 'config/database.php';

// Cette page vous connecte automatiquement et vous permet de réinitialiser

// Récupérer l'admin
$admin = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@cps.com'");
$admin->execute();
$user = $admin->fetch();

if ($user) {
    // Connecter directement
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nom'] = $user['nom'];
    $_SESSION['user_role'] = $user['role'];
    
    // Maintenant qu'on est connecté, on peut réinitialiser le mot de passe
    $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$new_hash, $user['id']]);
    
    echo "<h2>✅ Connexion automatique réussie !</h2>";
    echo "<p>Votre mot de passe a été réinitialisé à : <strong>admin123</strong></p>";
    echo "<a href='dashboard.php' class='btn btn-success'>Aller au tableau de bord</a>";
    exit();
} else {
    // Créer un admin si inexistant
    $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role, statut) VALUES (?, ?, ?, ?, ?) RETURNING id");
    $stmt->execute(['Administrateur', 'admin@cps.com', $new_hash, 'admin', 'actif']);
    
    $user_id = $stmt->fetchColumn();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_nom'] = 'Administrateur';
    $_SESSION['user_role'] = 'admin';
    
    echo "<h2>✅ Compte admin créé et connecté !</h2>";
    echo "<p>Email : admin@cps.com</p>";
    echo "<p>Mot de passe : admin123</p>";
    echo "<a href='dashboard.php' class='btn btn-success'>Aller au tableau de bord</a>";
}
?>
