<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ajouter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $stmt = $pdo->prepare("INSERT INTO membres (nom, prenom, classe, type, telephone, email) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['classe'], $_POST['type'], $_POST['telephone'], $_POST['email']]);
    header('Location: membres.php');
    exit();
}

// Modifier
if (isset($_GET['edit'])) {
    $membre = $pdo->prepare("SELECT * FROM membres WHERE id = ?");
    $membre->execute([$_GET['edit']]);
    $membre = $membre->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    $stmt = $pdo->prepare("UPDATE membres SET nom=?, prenom=?, classe=?, type=?, telephone=?, email=? WHERE id=?");
    $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['classe'], $_POST['type'], $_POST['telephone'], $_POST['email'], $_POST['id']]);
    header('Location: membres.php');
    exit();
}

// Supprimer
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM membres WHERE id = ?")->execute([$_GET['delete']]);
    header('Location: membres.php');
    exit();
}

$membres = $pdo->query("
    SELECT m.*, COUNT(e.id) as nb_emprunts 
    FROM membres m 
    LEFT JOIN emprunts e ON m.id = e.membre_id AND e.statut = 'en_cours'
    GROUP BY m.id, m.nom, m.prenom, m.classe, m.type, m.telephone, m.email, m.created_at 
    ORDER BY m.nom
")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<h2>👥 Gestion des membres</h2>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><?= isset($membre) ? 'Modifier' : 'Ajouter' ?> un membre</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if(isset($membre)): ?>
                <input type="hidden" name="id" value="<?= $membre['id'] ?>">
            <?php endif; ?>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label>Nom *</label>
                    <input type="text" name="nom" class="form-control" required value="<?= htmlspecialchars($membre['nom'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label>Prénom</label>
                    <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($membre['prenom'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label>Classe *</label>
                    <input type="text" name="classe" class="form-control" required value="<?= htmlspecialchars($membre['classe'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label>Type *</label>
                    <select name="type" class="form-control" required>
                        <option value="eleve" <?= (isset($membre) && $membre['type'] == 'eleve') ? 'selected' : '' ?>>Élève</option>
                        <option value="enseignant" <?= (isset($membre) && $membre['type'] == 'enseignant') ? 'selected' : '' ?>>Enseignant</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label>Téléphone</label>
                    <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($membre['telephone'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($membre['email'] ?? '') ?>">
                </div>
            </div>
            <button type="submit" name="<?= isset($membre) ? 'modifier' : 'ajouter' ?>" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= isset($membre) ? 'Modifier' : 'Ajouter' ?>
            </button>
            <?php if(isset($membre)): ?>
                <a href="membres.php" class="btn btn-secondary">Annuler</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Liste des membres</h5>
    </div>
    <div class="card-body">
        <input type="text" id="search" class="form-control mb-3" placeholder="Rechercher un membre...">
        <table class="table table-bordered" id="tableMembres">
            <thead class="table-dark">
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Classe</th>
                    <th>Type</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Emprunts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($membres as $membre): ?>
                <tr>
                    <td><?= htmlspecialchars($membre['nom']) ?></td>
                    <td><?= htmlspecialchars($membre['prenom']) ?></td>
                    <td><?= htmlspecialchars($membre['classe']) ?></td>
                    <td><?= $membre['type'] == 'eleve' ? '👨‍🎓 Élève' : '👨‍🏫 Enseignant' ?></td>
                    <td><?= htmlspecialchars($membre['telephone']) ?></td>
                    <td><?= htmlspecialchars($membre['email']) ?></td>
                    <td><span class="badge bg-warning"><?= $membre['nb_emprunts'] ?></span></td>
                    <td>
                        <a href="membres.php?edit=<?= $membre['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                        <a href="membres.php?delete=<?= $membre['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?')">🗑️</a>
                     </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('search').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#tableMembres tbody tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>
