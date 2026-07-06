<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ajouter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = $_POST['nom'];
    $description = $_POST['description'];
    $pdo->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)")->execute([$nom, $description]);
    header('Location: categories.php');
    exit();
}

// Modifier
if (isset($_GET['edit'])) {
    $categorie = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $categorie->execute([$_GET['edit']]);
    $categorie = $categorie->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $id = $_POST['id'];
    $nom = $_POST['nom'];
    $description = $_POST['description'];
    $pdo->prepare("UPDATE categories SET nom=?, description=? WHERE id=?")->execute([$nom, $description, $id]);
    header('Location: categories.php');
    exit();
}

// Supprimer
if (isset($_GET['delete'])) {
    try {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$_GET['delete']]);
        header('Location: categories.php?success=' . urlencode("Categorie supprimee avec succes."));
    } catch (PDOException $e) {
        header('Location: categories.php?error=' . urlencode("Suppression impossible : cette categorie est encore liee a des livres."));
    }
    exit();
}

$categories = $pdo->query("SELECT c.*, COUNT(lc.livre_id) as nb_livres 
                          FROM categories c 
                          LEFT JOIN livre_categorie lc ON c.id = lc.categorie_id 
                          GROUP BY c.id, c.nom, c.description, c.created_at 
                          ORDER BY c.nom")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<h2> Gestion des catégories</h2>
<?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
    <div class="alert alert-danger">Attention : <?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><?= isset($categorie) ? 'Modifier' : 'Ajouter' ?> une catégorie</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if(isset($categorie)): ?>
                <input type="hidden" name="id" value="<?= $categorie['id'] ?>">
            <?php endif; ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Nom de la catégorie *</label>
                    <input type="text" name="nom" class="form-control" required value="<?= htmlspecialchars($categorie['nom'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($categorie['description'] ?? '') ?></textarea>
                </div>
            </div>
            <button type="submit" name="<?= isset($categorie) ? 'modifier' : 'ajouter' ?>" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= isset($categorie) ? 'Modifier' : 'Ajouter' ?>
            </button>
            <?php if(isset($categorie)): ?>
                <a href="categories.php" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Liste des catégories</h5>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Nb livres</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($categories as $i => $cat): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($cat['nom']) ?></td>
                    <td><?= htmlspecialchars($cat['description']) ?></td>
                    <td><span class="badge bg-primary"><?= $cat['nb_livres'] ?></span></td>
                    <td>
                        <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="categories.php?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette catégorie ?')">
                            <i class="fas fa-trash"></i>
                        </a>
                     </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

