<?php
require_once 'config/database.php';

// Recherche
$search = $_GET['search'] ?? '';
$categorie_id = $_GET['categorie'] ?? '';

$sql = "SELECT l.*, STRING_AGG(DISTINCT c.nom, ', ' ORDER BY c.nom) as categories 
        FROM livres l 
        LEFT JOIN livre_categorie lc ON l.id = lc.livre_id 
        LEFT JOIN categories c ON lc.categorie_id = c.id 
        WHERE l.quantite_disponible > 0";

$params = [];

if (!empty($search)) {
    $sql .= " AND (l.titre ILIKE ? OR l.auteur ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($categorie_id)) {
    $sql .= " AND c.id = ?";
    $params[] = $categorie_id;
}

$sql .= " GROUP BY l.id, l.titre, l.auteur, l.isbn, l.numero_etagere, l.date_edition, l.quantite_totale, l.quantite_disponible, l.created_at ORDER BY l.titre";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$livres = $stmt->fetchAll();

// Récupérer les catégories pour le filtre
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Catalogue - Bibliothèque Congo Peace School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background-image: url(./assets/images/C.jpg); 
            min-height: 100vh; 
        }
        .p{
            font-weight :bold;
            color: #000;
         }
        .card { 
            border-radius: 15px; 
            transition: transform 0.3s; 
        }
        .card:hover { 
            transform: translateY(-5px);
         }
        @media (max-width: 767.98px) {
            body {
                background-attachment: fixed;
            }
            .container {
                padding-left: 16px;
                padding-right: 16px;
            }
            h1 {
                font-size: 1.65rem;
                line-height: 1.25;
            }
            form.d-flex {
                flex-direction: column;
            }
            form.d-flex .form-select,
            form.d-flex .btn {
                width: 100% !important;
            }
            .card:hover {
                transform: none;
            }
        }
         
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="text-white mb-3">
                <i class="fas fa-book-open"></i> Bibliothèque Congo Peace School
            </h1>
            <p class="text-white-50">Découvrez notre catalogue de livres disponibles</p>
        </div>

        <!-- Barre de recherche -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-8">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control form-control-lg" 
                           placeholder="Rechercher par titre ou auteur..." 
                           value="<?= htmlspecialchars($search) ?>">
                    <select name="categorie" class="form-select w-auto">
                        <option value="">Toutes catégories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categorie_id == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-light btn-lg">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Résultats -->
        <div class="row">
            <?php if(count($livres) > 0): ?>
                <?php foreach($livres as $livre): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($livre['titre']) ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($livre['auteur']) ?></h6>
                                <p class="card-text small">
                                    <i class="fas fa-tag"></i> <?= htmlspecialchars($livre['categories'] ?? 'Non catégorisé') ?><br>
                                    <i class="fas fa-layer-group"></i> Étagère: <?= htmlspecialchars($livre['numero_etagere']) ?><br>
                                    <i class="fas fa-calendar"></i> Édition: <?= date('d/m/Y', strtotime($livre['date_edition'])) ?>
                                </p>
                                <span class="badge bg-success">
                                    <i class="fas fa-check"></i> Disponible (<?= $livre['quantite_disponible'] ?>)
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle"></i> Aucun livre trouvé
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4">
            <a href="login.php" class="btn btn-light">
                <i class="fas fa-sign-in-alt"></i> Espace Bibliothécaire
            </a>
        </div>
    </div>
</body>
</html>
