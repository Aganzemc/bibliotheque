<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$stats['total_livres'] = $pdo->query("SELECT SUM(quantite_totale) FROM livres")->fetchColumn() ?? 0;
$stats['livres_disponibles'] = $pdo->query("SELECT SUM(quantite_disponible) FROM livres")->fetchColumn() ?? 0;
$stats['total_membres'] = $pdo->query("SELECT COUNT(*) FROM membres")->fetchColumn();
$stats['emprunts_encours'] = $pdo->query("SELECT COUNT(*) FROM emprunts WHERE statut = 'en_cours'")->fetchColumn();
$stats['emprunts_retard'] = $pdo->query("SELECT COUNT(*) FROM emprunts WHERE statut = 'en_cours' AND date_retour_prevue < CURRENT_DATE")->fetchColumn();
$stats['amendes_impayees'] = $pdo->query("SELECT COALESCE(SUM(amende_montant), 0) FROM emprunts WHERE amende_payee = FALSE AND amende_montant > 0")->fetchColumn();

$livresParCategorie = $pdo->query("
    SELECT c.nom, COUNT(lc.livre_id) as nb
    FROM categories c
    LEFT JOIN livre_categorie lc ON c.id = lc.categorie_id
    GROUP BY c.id, c.nom
")->fetchAll();

$topLivres = $pdo->query("
    SELECT l.titre, COUNT(e.id) as nb_emprunts
    FROM livres l
    JOIN emprunts e ON l.id = e.livre_id
    GROUP BY l.id, l.titre
    ORDER BY nb_emprunts DESC
    LIMIT 10
")->fetchAll();

$empruntsParMois = $pdo->query("
    SELECT TO_CHAR(date_emprunt, 'YYYY-MM') as mois, COUNT(*) as total
    FROM emprunts
    WHERE date_emprunt >= (CURRENT_DATE - INTERVAL '6 months')
    GROUP BY TO_CHAR(date_emprunt, 'YYYY-MM')
    ORDER BY mois ASC
")->fetchAll();

$livresParEtagere = $pdo->query("
    SELECT numero_etagere, COUNT(*) as total, SUM(quantite_disponible) as dispo
    FROM livres
    GROUP BY numero_etagere
    ORDER BY numero_etagere
")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<h2>📊 Statistiques détaillées</h2>

<div class="row mb-4">
    <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body"><h6>Total livres</h6><h2><?= $stats['total_livres'] ?></h2></div></div></div>
    <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h6>Livres disponibles</h6><h2><?= $stats['livres_disponibles'] ?></h2></div></div></div>
    <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h6>Membres</h6><h2><?= $stats['total_membres'] ?></h2></div></div></div>
    <div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body"><h6>Amendes impayées</h6><h2><?= number_format($stats['amendes_impayees'], 0, ',', ' ') ?> FC</h2></div></div></div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card"><div class="card-header">📚 Livres par catégorie</div><div class="card-body"><canvas id="chartCat" height="200"></canvas></div></div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card"><div class="card-header">📈 Évolution des emprunts</div><div class="card-body"><canvas id="chartMois" height="200"></canvas></div></div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card"><div class="card-header">🏆 Top 10 livres</div><div class="card-body"><ul class="list-group"><?php foreach($topLivres as $l): ?><li class="list-group-item d-flex justify-content-between"><?= htmlspecialchars($l['titre']) ?><span class="badge bg-primary"><?= $l['nb_emprunts'] ?></span></li><?php endforeach; ?></ul></div></div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card"><div class="card-header">📍 Livres par étagère</div><div class="card-body"><ul class="list-group"><?php foreach($livresParEtagere as $e): ?><li class="list-group-item d-flex justify-content-between">Étagère <?= $e['numero_etagere'] ?><span><?= $e['total'] ?> livres (<?= $e['dispo'] ?> dispo)</span></li><?php endforeach; ?></ul></div></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartCat'), { type: 'bar', data: { labels: <?= json_encode(array_column($livresParCategorie, 'nom')) ?>, datasets: [{ label: 'Nombre de livres', data: <?= json_encode(array_column($livresParCategorie, 'nb')) ?>, backgroundColor: '#667eea' }] } });
new Chart(document.getElementById('chartMois'), { type: 'line', data: { labels: <?= json_encode(array_column($empruntsParMois, 'mois')) ?>, datasets: [{ label: 'Emprunts', data: <?= json_encode(array_column($empruntsParMois, 'total')) ?>, borderColor: '#764ba2', tension: 0.3 }] } });
</script>
