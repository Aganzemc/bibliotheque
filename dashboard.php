<?php
// Démarrer la session pour récupérer les données de l'utilisateur connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// --- STATISTIQUES EN TEMPS RÉEL (MISES À JOUR AVEC VOS EMPRUNTS) ---
// Récupère la somme de toutes les quantités totales actuelles en base de données
$totalLivres = $pdo->query("SELECT SUM(quantite_totale) as total FROM livres")->fetch()['total'] ?? 0;

// Récupère la somme de toutes les quantités disponibles physiques restées sur vos étagères
$livresDispo = $pdo->query("SELECT SUM(quantite_disponible) as total FROM livres")->fetch()['total'] ?? 0;

// Compte précis des lignes d'emprunts actifs
$empruntsEncours = $pdo->query("SELECT COUNT(*) FROM emprunts WHERE statut = 'en_cours'")->fetchColumn();

// Compte total de vos membres enregistrés
$totalMembres = $pdo->query("SELECT COUNT(*) FROM membres")->fetchColumn();

// Compte des emprunts dont la date de retour prévue est dépassée par rapport à aujourd'hui
$empruntsRetard = $pdo->query("SELECT COUNT(*) FROM emprunts WHERE statut = 'en_cours' AND date_retour_prevue < CURRENT_DATE")->fetchColumn();

// Top 5 des livres les plus empruntés de l'histoire de la bibliothèque
$topLivres = $pdo->query("
    SELECT l.titre, COUNT(e.id) as nb_emprunts 
    FROM livres l 
    JOIN emprunts e ON l.id = e.livre_id 
    GROUP BY l.id, l.titre 
    ORDER BY nb_emprunts DESC 
    LIMIT 5
")->fetchAll();

// Liste des 10 premiers retards en cours pour affichage direct
$retards = $pdo->query("
    SELECT m.nom, m.prenom, l.titre, e.date_retour_prevue 
    FROM emprunts e 
    JOIN membres m ON e.membre_id = m.id 
    JOIN livres l ON e.livre_id = l.id 
    WHERE e.statut = 'en_cours' AND e.date_retour_prevue < CURRENT_DATE
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Bibliothèque CPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
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
        .sidebar .nav-link:hover {
            background-color: #e9ecef;
            transform: translateX(5px);
        }
        .sidebar .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .content {
            margin-left: 250px;
            margin-top: 56px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                top: 0;
                height: auto;
            }
            .content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-book"></i> Bibliothèque CPS
            </a>
            <div class="d-flex">
                <span class="text-white me-3 align-self-center">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?> 
                    (<?= htmlspecialchars($_SESSION['user_role'] ?? '') ?>)
                </span>
                <a href="logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar">
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
            </li>
            <li class="nav-item">
                <a href="livres.php" class="nav-link">
                    <i class="fas fa-book"></i> Livres
                </a>
            </li>
            <li class="nav-item">
                <a href="categories.php" class="nav-link">
                    <i class="fas fa-tags"></i> Catégories
                </a>
            </li>
            <li class="nav-item">
                <a href="membres.php" class="nav-link">
                    <i class="fas fa-users"></i> Membres
                </a>
            </li>
            <li class="nav-item">
                <a href="emprunts.php" class="nav-link">
                    <i class="fas fa-exchange-alt"></i> Emprunts/Retours
                </a>
            </li>
            <li class="nav-item">
                <a href="statistiques.php" class="nav-link">
                    <i class="fas fa-chart-line"></i> Statistiques
                </a>
            </li>
            <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
            <li class="nav-item">
                <a href="utilisateurs.php" class="nav-link">
                    <i class="fas fa-user-shield"></i> Utilisateurs
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="export.php" class="nav-link">
                    <i class="fas fa-file-export"></i> Export
                </a>
            </li>
        </ul>
    </div>

    <div class="content">
        <h2 class="mb-4">Tableau de bord</h2>

        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary h-100">
                    <div class="card-body">
                        <h6 class="card-title">📚 Total Livres</h6>
                        <h2 class="mb-0 fw-bold"><?= $totalLivres ?></h2>
                        <small><?= $livresDispo ?> disponibles</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-success h-100">
                    <div class="card-body">
                        <h6 class="card-title">👥 Membres</h6>
                        <h2 class="mb-0 fw-bold"><?= $totalMembres ?></h2>
                        <small>Inscrits à la bibliothèque</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-warning h-100">
                    <div class="card-body">
                        <h6 class="card-title">🔄 Emprunts en cours</h6>
                        <h2 class="mb-0 fw-bold"><?= $empruntsEncours ?></h2>
                        <small class="text-dark fw-bold"><?= $empruntsRetard ?> en retard</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-info h-100">
                    <div class="card-body">
                        <h6 class="card-title">📖 Taux d'occupation</h6>
                        <h2 class="mb-0 fw-bold"><?= $totalLivres > 0 ? round(($totalLivres - $livresDispo) / $totalLivres * 100) : 0 ?>%</h2>
                        <small>Volume des livres hors rayons</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-trophy"></i> Top 5 livres les plus empruntés</h6>
                    </div>
                    <div class="card-body">
                        <?php if(count($topLivres) > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach($topLivres as $livre): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span><?= htmlspecialchars($livre['titre']) ?></span>
                                        <span class="badge bg-primary rounded-pill"><?= $livre['nb_emprunts'] ?> emprunts</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted text-center my-4">Aucun emprunt enregistré pour le moment</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Retards en cours</h6>
                    </div>
                    <div class="card-body">
                        <?php if(count($retards) > 0): ?>
                            <ul class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                                <?php foreach($retards as $retard): ?>
                                    <li class="list-group-item text-danger px-0 small">
                                        <i class="fas fa-user-clock"></i> 
                                        <strong><?= htmlspecialchars($retard['nom'] . ' ' . $retard['prenom']) ?></strong><br>
                                        <span class="text-muted">Livre :</span> "<?= htmlspecialchars($retard['titre']) ?>" <br>
                                        <span class="badge bg-danger">Attendu depuis le <?= date('d/m/Y', strtotime($retard['date_retour_prevue'])) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-success text-center my-4">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p class="mb-0">Aucun retard à signaler, excellent travail !</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
