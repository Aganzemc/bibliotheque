<?php
// Démarrer la session si ce n'est pas déjà fait dans config/database.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ajouter un livre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $titre = $_POST['titre'];
    $auteur = $_POST['auteur'];
    $isbn = trim($_POST['isbn']);
    $numero_etagere = $_POST['numero_etagere']; 
    $date_edition = $_POST['date_edition'];
    $quantite_totale = $_POST['quantite_totale'];
    $categories = $_POST['categories'] ?? [];
    
    // Rendre la date d'édition optionnelle (gère le NULL en BDD)
    $date_edition_value = !empty($date_edition) ? $date_edition : null;
    
    try {
        $pdo->beginTransaction();
        
        // CONTROLE STRICT DE L'ID / ISBN UNIQUE (Anti-doublon à l'ajout)
        if (!empty($isbn)) {
            $check_stmt = $pdo->prepare("SELECT id FROM livres WHERE isbn = ?");
            $check_stmt->execute([$isbn]);
            if ($check_stmt->fetch()) {
                throw new Exception("Impossible d'ajouter : Un livre avec cet identifiant unique / ISBN existe déjà dans le système.");
            }
        } else {
            throw new Exception("L'identifiant unique (ISBN) est obligatoire.");
        }
        
        $stmt = $pdo->prepare("INSERT INTO livres (titre, auteur, isbn, numero_etagere, date_edition, quantite_totale, quantite_disponible) 
                               VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id");
        $stmt->execute([$titre, $auteur, $isbn, $numero_etagere, $date_edition_value, $quantite_totale, $quantite_totale]);
        $livre_id = $stmt->fetchColumn();
        
        foreach ($categories as $cat_id) {
            $pdo->prepare("INSERT INTO livre_categorie (livre_id, categorie_id) VALUES (?, ?)")->execute([$livre_id, $cat_id]);
        }
        
        $pdo->commit();
        $success = "Livre ajouté avec succès !";
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Modifier un livre (Chargement des données dans le formulaire)
if (isset($_GET['edit'])) {
    $stmt_livre = $pdo->prepare("SELECT * FROM livres WHERE id = ?");
    $stmt_livre->execute([$_GET['edit']]);
    $livre = $stmt_livre->fetch();
    
    if ($livre) {
        $livre_categories = $pdo->prepare("SELECT categorie_id FROM livre_categorie WHERE livre_id = ?");
        $livre_categories->execute([$_GET['edit']]);
        $livre_categories = $livre_categories->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Enregistrement des modifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $id = $_POST['id'];
    $titre = $_POST['titre'];
    $auteur = $_POST['auteur'];
    $isbn = trim($_POST['isbn']);
    $numero_etagere = $_POST['numero_etagere']; 
    $date_edition = $_POST['date_edition'];
    $quantite_totale = $_POST['quantite_totale'];
    $categories = $_POST['categories'] ?? [];
    
    // Rendre la date d'édition optionnelle (gère le NULL en BDD)
    $date_edition_value = !empty($date_edition) ? $date_edition : null;
    
    try {
        $pdo->beginTransaction();
        
        // CONTROLE STRICT DE L'ID / ISBN UNIQUE 
        if (!empty($isbn)) {
            $check_stmt = $pdo->prepare("SELECT id FROM livres WHERE isbn = ? AND id != ?");
            $check_stmt->execute([$isbn, $id]);
            if ($check_stmt->fetch()) {
                throw new Exception("Impossible de modifier : Cet identifiant unique / ISBN est déjà utilisé par un autre livre.");
            }
        } else {
            throw new Exception("L'identifiant unique (ISBN) ne peut pas être vide.");
        }
        
        $ancienne_qte = $pdo->prepare("SELECT quantite_totale, quantite_disponible FROM livres WHERE id = ?");
        $ancienne_qte->execute([$id]);
        $ancienne = $ancienne_qte->fetch();
        
        $difference = $quantite_totale - $ancienne['quantite_totale'];
        $nouvelle_disponible = $ancienne['quantite_disponible'] + $difference;
        
        $stmt = $pdo->prepare("UPDATE livres SET titre=?, auteur=?, isbn=?, numero_etagere=?, date_edition=?, quantite_totale=?, quantite_disponible=? WHERE id=?");
        $stmt->execute([$titre, $auteur, $isbn, $numero_etagere, $date_edition_value, $quantite_totale, $nouvelle_disponible, $id]);
        
        $pdo->prepare("DELETE FROM livre_categorie WHERE livre_id = ?")->execute([$id]);
        foreach ($categories as $cat_id) {
            $pdo->prepare("INSERT INTO livre_categorie (livre_id, categorie_id) VALUES (?, ?)")->execute([$id, $cat_id]);
        }
        
        $pdo->commit();
        header('Location: livres.php?success=1'); 
        exit();
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Gestion des messages reçus après redirections
if (isset($_GET['success'])) {
    $success = "Opération effectuée avec succès !";
}
if (isset($_GET['error_code']) && $_GET['error_code'] === 'foreign_key') {
    $error = "Action impossible : Ce livre ne peut pas être supprimé car il est lié à des fiches d'emprunts existantes (historique requis).";
}

// Supprimer un livre de manière sécurisée
if (isset($_GET['delete'])) {
    $id_a_supprimer = $_GET['delete'];
    try {
        $pdo->beginTransaction();
        
        $pdo->prepare("DELETE FROM livre_categorie WHERE livre_id = ?")->execute([$id_a_supprimer]);
        $pdo->prepare("DELETE FROM livres WHERE id = ?")->execute([$id_a_supprimer]);
        
        $pdo->commit();
        header('Location: livres.php?success=1');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() === '23000' || strpos($e->getMessage(), '1451') !== false) {
            header('Location: livres.php?error_code=foreign_key');
        } else {
            header('Location: livres.php?error_code=unknown');
        }
        exit();
    }
}

// Récupérer tous les livres
$livres = $pdo->query("
    SELECT l.*, STRING_AGG(DISTINCT c.nom, ', ' ORDER BY c.nom) as categories 
    FROM livres l 
    LEFT JOIN livre_categorie lc ON l.id = lc.livre_id 
    LEFT JOIN categories c ON lc.categorie_id = c.id 
    GROUP BY l.id, l.titre, l.auteur, l.isbn, l.numero_etagere, l.date_edition, l.quantite_totale, l.quantite_disponible, l.created_at 
    ORDER BY l.titre
")->fetchAll();

$categories_liste = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<h2>📖 Gestion des livres</h2>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>
<?php if(isset($error)): ?>
    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><?= isset($livre) ? 'Modifier' : 'Ajouter' ?> un livre</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if(isset($livre)): ?>
                <input type="hidden" name="id" value="<?= $livre['id'] ?>">
            <?php endif; ?>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Titre</label>
                    <input type="text" name="titre" class="form-control" required value="<?= htmlspecialchars($livre['titre'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Auteur</label>
                    <input type="text" name="auteur" class="form-control" required value="<?= htmlspecialchars($livre['auteur'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label>ISBN</label>
                    <input type="text" name="isbn" class="form-control" required value="<?= htmlspecialchars($livre['isbn'] ?? '') ?>" placeholder="Ex: Code-barres ou numéro unique">
                </div>
                <div class="col-md-3 mb-3">
                    <label>Numéro étagère</label>
                    <input type="text" name="numero_etagere" class="form-control" required placeholder="Ex: A12" value="<?= htmlspecialchars($livre['numero_etagere'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label>Date d'édition <span class="text-muted">(Optionnel)</span></label>
                    <input type="date" name="date_edition" class="form-control" value="<?= $livre['date_edition'] ?? '' ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label>Quantité totale *</label>
                    <input type="number" name="quantite_totale" class="form-control" value="<?= $livre['quantite_totale'] ?? 1 ?>" min="1" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Catégorie(s)</label>
                    <select name="categories[]" class="form-control" multiple>
                        <?php foreach($categories_liste as $cat): ?>
                            <option value="<?= $cat['id'] ?>" 
                                <?= isset($livre_categories) && in_array($cat['id'], $livre_categories) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Pour sélection multiple</small>
                </div>
            </div>
            <button type="submit" name="<?= isset($livre) ? 'modifier' : 'ajouter' ?>" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= isset($livre) ? 'Modifier' : 'Ajouter' ?>
            </button>
            <?php if(isset($livre)): ?>
                <a href="livres.php" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Liste des livres</h5>
    </div>
    <div class="card-body">
        <input type="text" id="search" class="form-control mb-3" placeholder="Rechercher par titre ou auteur...">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="tableLivres">
                <thead class="table-dark">
                    <tr>
                        <th>Titre</th>
                        <th>Auteur</th>
                        <th>ISBN / Identifiant</th>
                        <th>Étagère</th>
                        <th>Date édition</th>
                        <th>Catégories</th>
                        <th>Qté Total</th>
                        <th>Qté Dispo</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($livres as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars($l['titre']) ?></td>
                            <td><?= htmlspecialchars($l['auteur']) ?></td>
                            <td><strong class="text-secondary"><?= htmlspecialchars($l['isbn']) ?></strong></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($l['numero_etagere']) ?></span></td>
                            <td>
                                <?php if(!empty($l['date_edition']) && $l['date_edition'] !== '0000-00-00'): ?>
                                    <?= date('d/m/Y', strtotime($l['date_edition'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Inconnue</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($l['categories'] ?: 'Non catégorisé') ?></td>
                            <td><?= $l['quantite_totale'] ?></td>
                            <td>
                                <?php if($l['quantite_disponible'] > 0): ?>
                                    <span class="badge bg-success"><?= $l['quantite_disponible'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Épuisé</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="livres.php?edit=<?= $l['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="livres.php?delete=<?= $l['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Attention : Êtes-vous sûr de vouloir supprimer définitivement ce livre du catalogue ?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                             </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('search').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#tableLivres tbody tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>
