<?php
// Démarrer la session pour accéder aux données de l'administrateur connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// Vérification stricte des accès de sécurité
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$success = null;
$error = null;

// Traitement : Ajouter un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];
    $role = $_POST['role'];
    
    if (empty($nom) || empty($email) || empty($password_raw) || empty($role)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            // Vérifier si l'adresse email n'existe pas déjà
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmail->execute([$email]);
            
            if ($checkEmail->fetch()) {
                $error = "Cette adresse email est déjà utilisée par un autre utilisateur.";
            } else {
                // Hachage sécurisé du mot de passe
                $password = password_hash($password_raw, PASSWORD_DEFAULT);
                
                // Insertion avec statut 'actif' par défaut
                $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role, statut) VALUES (?, ?, ?, ?, 'actif')");
                $stmt->execute([$nom, $email, $password, $role]);
                
                // Redirection propre pour éviter la double soumission du formulaire
                header('Location: utilisateurs.php?success=' . urlencode("Utilisateur ajouté avec succès !"));
                exit();
            }
        } catch (Exception $e) {
            $error = "Une erreur est survenue lors de l'enregistrement : " . $e->getMessage();
        }
    }
}

// Traitement : Basculer le statut (Actif / Inactif)
if (isset($_GET['toggle'])) {
    $userIdToToggle = $_GET['toggle'];
    
    // Sécurité : Empêcher l'administrateur de désactiver son propre compte
    if ($userIdToToggle == $_SESSION['user_id']) {
        header('Location: utilisateurs.php?error=' . urlencode("Vous ne pouvez pas désactiver votre propre compte."));
        exit();
    }
    
    $user = $pdo->prepare("SELECT statut FROM users WHERE id = ?");
    $user->execute([$userIdToToggle]);
    $currentStatut = $user->fetchColumn();
    
    if ($currentStatut) {
        $nouveauStatut = ($currentStatut == 'actif') ? 'inactif' : 'actif';
        $pdo->prepare("UPDATE users SET statut = ? WHERE id = ?")->execute([$nouveauStatut, $userIdToToggle]);
        header('Location: utilisateurs.php?success=' . urlencode("Le statut de l'utilisateur a été mis à jour."));
        exit();
    }
}

// Traitement : Supprimer un utilisateur
if (isset($_GET['delete'])) {
    $userIdToDelete = $_GET['delete'];
    
    // Sécurité additionnelle : Empêcher l'admin de s'auto-supprimer
    if ($userIdToDelete != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userIdToDelete]);
        header('Location: utilisateurs.php?success=' . urlencode("Utilisateur supprimé définitivement."));
        exit();
    } else {
        header('Location: utilisateurs.php?error=' . urlencode("Action impossible : Vous ne pouvez pas vous supprimer vous-même."));
        exit();
    }
}

// Récupération des messages de redirection
if (isset($_GET['success'])) { $success = $_GET['success']; }
if (isset($_GET['error'])) { $error = $_GET['error']; }

// Récupérer la liste complète des utilisateurs mis à jour
$users = $pdo->query("SELECT * FROM users ORDER BY role, nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - Bibliothèque CPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-brand { font-weight: bold; }
        .card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: none; }
        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            height: 100%;
            width: 250px;
            background-color: #f8f9fa;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        .sidebar .nav-link {
            color: #333;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 5px 10px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover { background-color: #e9ecef; transform: translateX(5px); }
        .sidebar .nav-link.active { background-color: #0d6efd; color: white; }
        .content { margin-left: 250px; margin-top: 56px; padding: 20px; }
        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; top: 0; height: auto; }
            .content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-book"></i> Bibliothèque CPS</a>
            <div class="d-flex">
                <span class="text-white me-3 align-self-center">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?> (<?= htmlspecialchars($_SESSION['user_role'] ?? '') ?>)
                </span>
                <a href="logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>
    </nav>

    <div class="sidebar">
        <ul class="nav flex-column mt-3">
            <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
            <li class="nav-item"><a href="livres.php" class="nav-link"><i class="fas fa-book"></i> Livres</a></li>
            <li class="nav-item"><a href="categories.php" class="nav-link"><i class="fas fa-tags"></i> Catégories</a></li>
            <li class="nav-item"><a href="membres.php" class="nav-link"><i class="fas fa-users"></i> Membres</a></li>
            <li class="nav-item"><a href="emprunts.php" class="nav-link"><i class="fas fa-exchange-alt"></i> Emprunts/Retours</a></li>
            <li class="nav-item"><a href="statistiques.php" class="nav-link"><i class="fas fa-chart-line"></i> Statistiques</a></li>
            <li class="nav-item"><a href="utilisateurs.php" class="nav-link active"><i class="fas fa-user-shield"></i> Utilisateurs</a></li>
            <li class="nav-item"><a href="export.php" class="nav-link"><i class="fas fa-file-export"></i> Export</a></li>
        </ul>
    </div>

    <div class="content">
        <h2 class="mb-4"><i class="fas fa-user-shield text-primary"></i> Gestion des utilisateurs (Admin)</h2>

        <?php if(!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-plus"></i> Ajouter un nouvel utilisateur</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold">Nom complet *</label>
                            <input type="text" name="nom" class="form-control" placeholder="Ex: Jean Dupont" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold">Adresse Email *</label>
                            <input type="email" name="email" class="form-control" placeholder="Ex: jean.dupont@cps.org" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold">Mot de passe *</label>
                            <input type="password" name="password" class="form-control" placeholder="Mot de passe robuste" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label small fw-bold">Rôle système *</label>
                            <select name="role" class="form-select" required>
                                <option value="bibliothecaire" selected>📚 Bibliothécaire</option>
                                <option value="assistant">🤝 Assistant</option>
                                <option value="admin">👑 Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="ajouter" class="btn btn-primary px-4 mt-2">
                        <i class="fas fa-save shadow-sm"></i> Enregistrer l'utilisateur
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-secondary"><i class="fas fa-list"></i> Utilisateurs possédant un accès</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-4">Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th class="text-center" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                            <tr>
                                <td class="ps-4">
                                    <strong><?= htmlspecialchars($user['nom']) ?></strong> 
                                    <?= $user['id'] == $_SESSION['user_id'] ? '<span class="badge bg-info ms-1">Moi</span>' : '' ?>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php if($user['role'] == 'admin'): ?>
                                        <span class="badge bg-danger">👑 Admin</span>
                                    <?php elseif($user['role'] == 'bibliothecaire'): ?>
                                        <span class="badge bg-primary">📚 Bibliothécaire</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">🤝 Assistant</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $user['statut'] == 'actif' ? 'success' : 'danger' ?> px-2 py-1">
                                        <?= $user['statut'] == 'actif' ? '🟢 Actif' : '🔴 Inactif' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="utilisateurs.php?toggle=<?= $user['id'] ?>" class="btn btn-sm btn-outline-warning me-1" title="<?= $user['statut'] == 'actif' ? 'Désactiver le compte' : 'Activer le compte' ?>">
                                            <?= $user['statut'] == 'actif' ? '<i class="fas fa-lock"></i>' : '<i class="fas fa-lock-open"></i>' ?>
                                        </a>
                                        <a href="utilisateurs.php?delete=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous certain de vouloir supprimer définitivement cet utilisateur ?')" title="Supprimer">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small italic">Aucune action</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>